<?php
function md_h($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function md_num($value, $decimals = 0)
{
  return number_format((float)$value, $decimals, ',', '.');
}

function md_money($value)
{
  return number_format((float)$value, 0, ',', '.');
}

function md_month_start()
{
  return date('Y-m-01');
}

function md_today()
{
  return date('Y-m-d');
}

function md_scalar($db, $sql, $params = array(), $field = 'total', $default = 0)
{
  $row = $db->fetch($sql, $params);
  if (!$row || !isset($row->$field)) {
    return $default;
  }
  return $row->$field;
}

function md_rows($db, $sql, $params = array())
{
  $rows = $db->query($sql, $params);
  if (!$rows) {
    return array();
  }
  $result = array();
  foreach ($rows as $row) {
    $result[] = $row;
  }
  return $result;
}

function md_kpis($db)
{
  $from = md_month_start();
  $to = md_today();

  $sales = $db->fetch("SELECT COUNT(*) total_order,
                              COALESCE(SUM(COALESCE(d.nilai,d.qty*d.price,0)),0) sales_value
                       FROM sales_order so
                       LEFT JOIN sales_order_detail d ON d.id_sales_order=so.id_sales_order
                       WHERE so.so_date BETWEEN ? AND ?", array($from, $to));

  $purchase = $db->fetch("SELECT COUNT(DISTINCT po.id) total_po,
                                 COALESCE(SUM(COALESCE(d.amount,d.qty*d.harga,0)),0) po_value,
                                 COALESCE(SUM(GREATEST(COALESCE(d.qty,0)-COALESCE(d.received_qty,0),0)),0) open_qty
                          FROM purchase_order po
                          LEFT JOIN purchase_order_detail d ON d.id_po=po.id
                          WHERE po.po_date BETWEEN ? AND ?", array($from, $to));

  $stock = $db->fetch("SELECT COALESCE(SUM(qty_sisa),0) onhand_qty,
                              SUM(qty_sisa < 0) negative_layers,
                              SUM(stock_type='QUALITY' AND qty_sisa>0) quality_layers,
                              SUM(stock_type='BLOCKED' AND qty_sisa>0) blocked_layers,
                              SUM((no_aju IS NULL OR no_aju='' OR no_dokpab IS NULL OR no_dokpab='') AND qty_sisa>0) missing_customs_layers
                       FROM stock_layer");

  $production = $db->fetch("SELECT COUNT(*) total_order,
                                   SUM(status IN ('CREATED','RELEASED','IN_PROCESS')) active_order,
                                   COALESCE(SUM(order_qty),0) order_qty,
                                   COALESCE(SUM(completed_qty),0) completed_qty,
                                   COALESCE(SUM(scrap_qty),0) scrap_qty
                            FROM production_order
                            WHERE COALESCE(start_date,DATE(created_at)) BETWEEN ? AND ?", array($from, $to));

  $finance = $db->fetch("SELECT COUNT(*) journal_count
                         FROM jurnal_header
                         WHERE posting_status='POSTED' AND tgl_jurnal BETWEEN ? AND ?", array($from, $to));

  $employees = $db->fetch("SELECT COUNT(*) total_employee,
                                  SUM(employment_status IN ('ACTIVE','PROBATION','CONTRACT')) active_employee
                           FROM erp_employee_master");

  return array(
    'period_from' => $from,
    'period_to' => $to,
    'sales_order' => (int)($sales ? $sales->total_order : 0),
    'sales_value' => (float)($sales ? $sales->sales_value : 0),
    'purchase_order' => (int)($purchase ? $purchase->total_po : 0),
    'purchase_value' => (float)($purchase ? $purchase->po_value : 0),
    'purchase_open_qty' => (float)($purchase ? $purchase->open_qty : 0),
    'stock_onhand_qty' => (float)($stock ? $stock->onhand_qty : 0),
    'negative_layers' => (int)($stock ? $stock->negative_layers : 0),
    'quality_layers' => (int)($stock ? $stock->quality_layers : 0),
    'blocked_layers' => (int)($stock ? $stock->blocked_layers : 0),
    'missing_customs_layers' => (int)($stock ? $stock->missing_customs_layers : 0),
    'production_order' => (int)($production ? $production->total_order : 0),
    'production_active' => (int)($production ? $production->active_order : 0),
    'production_order_qty' => (float)($production ? $production->order_qty : 0),
    'production_completed_qty' => (float)($production ? $production->completed_qty : 0),
    'production_scrap_qty' => (float)($production ? $production->scrap_qty : 0),
    'journal_count' => (int)($finance ? $finance->journal_count : 0),
    'total_employee' => (int)($employees ? $employees->total_employee : 0),
    'active_employee' => (int)($employees ? $employees->active_employee : 0)
  );
}

function md_critical_alerts($db)
{
  $alerts = array();

  $negativeLayers = (int)md_scalar($db, "SELECT COUNT(*) total FROM stock_layer WHERE qty_sisa < 0");
  $alerts[] = array(
    'severity' => $negativeLayers > 0 ? 'danger' : 'success',
    'title' => 'Negative Stock Layer',
    'value' => $negativeLayers,
    'description' => $negativeLayers > 0 ? 'Ada layer stok minus. Ini critical karena laporan mutasi dan stock overview bisa berbeda.' : 'Tidak ada stock layer minus.',
    'url' => 'stock-overview'
  );

  $unbalanced = (int)md_scalar($db, "SELECT COUNT(*) total FROM (
    SELECT h.id,ABS(COALESCE(SUM(d.debet),0)-COALESCE(SUM(d.kredit),0)) diff
    FROM jurnal_header h
    LEFT JOIN jurnal_detail d ON d.id_header=h.id
    WHERE h.posting_status='POSTED'
    GROUP BY h.id
    HAVING diff > 0.01
  ) x");
  $alerts[] = array(
    'severity' => $unbalanced > 0 ? 'danger' : 'success',
    'title' => 'Unbalanced Posted Journal',
    'value' => $unbalanced,
    'description' => $unbalanced > 0 ? 'Ada jurnal posted yang debit/kredit tidak balance. Ini wajib dibenahi sebelum closing.' : 'Semua jurnal posted balance.',
    'url' => 'jurnal-umum'
  );

  $missingJournal = (int)md_scalar($db, "SELECT COUNT(*) total FROM (
    SELECT 'SALES_INVOICE' src,id_sales id FROM sales_invoice WHERE billing_status='POSTED' AND (journal_header_id IS NULL OR journal_header_id=0)
    UNION ALL
    SELECT 'VENDOR_INVOICE',id FROM erp_vendor_invoice WHERE status='POSTED' AND (journal_header_id IS NULL OR journal_header_id=0)
    UNION ALL
    SELECT 'MANUAL_ADJ',a.id
    FROM erp_manual_stock_adjustment a
    WHERE a.status='POSTED'
      AND NOT EXISTS (
        SELECT 1 FROM jurnal_header j
        WHERE j.posting_status='POSTED'
          AND j.source_module='MANUAL_STOCK_ADJUSTMENT'
          AND j.source_document_no=a.adjustment_no
      )
  ) x");
  $alerts[] = array(
    'severity' => $missingJournal > 0 ? 'danger' : 'success',
    'title' => 'Posted Transaction Without Journal',
    'value' => $missingJournal,
    'description' => $missingJournal > 0 ? 'Ada transaksi posted yang belum punya jurnal otomatis.' : 'Transaksi posted utama sudah punya referensi jurnal.',
    'url' => 'jurnal-umum'
  );

  $missingCustoms = (int)md_scalar($db, "SELECT COUNT(*) total FROM stock_layer WHERE qty_sisa>0 AND (no_aju IS NULL OR no_aju='' OR no_dokpab IS NULL OR no_dokpab='')");
  $alerts[] = array(
    'severity' => $missingCustoms > 0 ? 'warning' : 'success',
    'title' => 'Open Stock Without Customs Doc',
    'value' => $missingCustoms,
    'description' => $missingCustoms > 0 ? 'Ada stok terbuka tanpa data aju/dokumen pabean lengkap.' : 'Stok terbuka sudah memiliki referensi dokumen pabean.',
    'url' => 'customs-stock-traceability'
  );

  $prPending = (int)md_scalar($db, "SELECT COUNT(*) total FROM purchase_requisition WHERE status='SUBMITTED'");
  $alerts[] = array(
    'severity' => $prPending > 0 ? 'warning' : 'success',
    'title' => 'PR Pending Approval',
    'value' => $prPending,
    'description' => $prPending > 0 ? 'Purchase requisition menunggu approval.' : 'Tidak ada PR pending approval.',
    'url' => 'approval-center'
  );

  $poOutstanding = (float)md_scalar($db, "SELECT COALESCE(SUM(GREATEST(COALESCE(qty,0)-COALESCE(received_qty,0),0)),0) total FROM purchase_order_detail");
  $alerts[] = array(
    'severity' => $poOutstanding > 0 ? 'info' : 'success',
    'title' => 'PO Outstanding Qty',
    'value' => $poOutstanding,
    'description' => $poOutstanding > 0 ? 'Masih ada quantity PO yang belum diterima.' : 'Tidak ada outstanding quantity PO.',
    'url' => 'purchase-order'
  );

  $mrpShortage = (int)md_scalar($db, "SELECT COUNT(*) total FROM erp_mrp_run_detail WHERE net_requirement > 0");
  $alerts[] = array(
    'severity' => $mrpShortage > 0 ? 'warning' : 'success',
    'title' => 'MRP Material Shortage',
    'value' => $mrpShortage,
    'description' => $mrpShortage > 0 ? 'Ada material shortage dari hasil MRP.' : 'Tidak ada shortage MRP terbuka.',
    'url' => 'mrp'
  );

  $activeMenuWithoutRole = (int)md_scalar($db, "SELECT COUNT(*) total
    FROM sys_menu m
    WHERE m.tampil='Y' AND m.type_menu='page'
      AND NOT EXISTS (SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id)");
  $alerts[] = array(
    'severity' => $activeMenuWithoutRole > 0 ? 'danger' : 'success',
    'title' => 'Menu Without Role',
    'value' => $activeMenuWithoutRole,
    'description' => $activeMenuWithoutRole > 0 ? 'Ada menu aktif tanpa role permission.' : 'Semua menu aktif sudah punya role.',
    'url' => 'group-permission'
  );

  return $alerts;
}

function md_module_status($db)
{
  return array(
    array('area'=>'Finance', 'metric'=>'Posted Journal MTD', 'value'=>md_scalar($db, "SELECT COUNT(*) total FROM jurnal_header WHERE posting_status='POSTED' AND tgl_jurnal BETWEEN ? AND ?", array(md_month_start(), md_today())), 'status'=>'Monitor'),
    array('area'=>'Finance', 'metric'=>'Draft Journal', 'value'=>md_scalar($db, "SELECT COUNT(*) total FROM jurnal_header WHERE posting_status='DRAFT'"), 'status'=>'Action if period close'),
    array('area'=>'Warehouse', 'metric'=>'Open Stock Layers', 'value'=>md_scalar($db, "SELECT COUNT(*) total FROM stock_layer WHERE qty_sisa>0"), 'status'=>'Monitor'),
    array('area'=>'Warehouse', 'metric'=>'Blocked / Quality Layers', 'value'=>md_scalar($db, "SELECT COUNT(*) total FROM stock_layer WHERE qty_sisa>0 AND stock_type IN ('BLOCKED','QUALITY')"), 'status'=>'Quality follow-up'),
    array('area'=>'Purchasing', 'metric'=>'Open PR', 'value'=>md_scalar($db, "SELECT COUNT(*) total FROM purchase_requisition WHERE status IN ('DRAFT','SUBMITTED','APPROVED','PARTIAL_PO')"), 'status'=>'Monitor'),
    array('area'=>'Purchasing', 'metric'=>'Outstanding PO Item', 'value'=>md_scalar($db, "SELECT COUNT(*) total FROM purchase_order_detail WHERE COALESCE(qty,0)>COALESCE(received_qty,0)"), 'status'=>'GR follow-up'),
    array('area'=>'Sales', 'metric'=>'Open Sales Order', 'value'=>md_scalar($db, "SELECT COUNT(*) total FROM sales_order WHERE COALESCE(status,'') NOT IN ('CLOSED','CANCELLED') AND approval_status NOT IN ('REJECTED','CANCELLED')"), 'status'=>'Delivery/Billing follow-up'),
    array('area'=>'Production', 'metric'=>'Active Production Order', 'value'=>md_scalar($db, "SELECT COUNT(*) total FROM production_order WHERE status IN ('CREATED','RELEASED','IN_PROCESS')"), 'status'=>'Shop floor monitor'),
    array('area'=>'HR', 'metric'=>'Active Employee', 'value'=>md_scalar($db, "SELECT COUNT(*) total FROM erp_employee_master WHERE employment_status IN ('ACTIVE','PROBATION','CONTRACT')"), 'status'=>'Monitor'),
    array('area'=>'System', 'metric'=>'Activity Log Today', 'value'=>md_scalar($db, "SELECT COUNT(*) total FROM log_aktifitas WHERE DATE(tgl)=CURDATE()"), 'status'=>'Audit trail')
  );
}

function md_trend_chart($db)
{
  $categories = array();
  $sales = array();
  $purchasing = array();
  $production = array();
  $journal = array();

  for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime('-'.$i.' day'));
    $categories[] = date('d M', strtotime($date));
    $sales[] = (int)md_scalar($db, "SELECT COUNT(*) total FROM sales_order WHERE so_date=?", array($date));
    $purchasing[] = (int)md_scalar($db, "SELECT COUNT(*) total FROM purchase_order WHERE po_date=?", array($date));
    $production[] = (int)md_scalar($db, "SELECT COUNT(*) total FROM production_order_confirmation WHERE posting_date=?", array($date));
    $journal[] = (int)md_scalar($db, "SELECT COUNT(*) total FROM jurnal_header WHERE posting_status='POSTED' AND tgl_jurnal=?", array($date));
  }

  return array(
    'categories' => $categories,
    'sales' => $sales,
    'purchasing' => $purchasing,
    'production' => $production,
    'journal' => $journal
  );
}

function md_alert_chart($alerts)
{
  $result = array('Critical'=>0, 'Warning'=>0, 'Info'=>0, 'OK'=>0);
  foreach ($alerts as $alert) {
    if ($alert['severity'] === 'danger') $result['Critical']++;
    elseif ($alert['severity'] === 'warning') $result['Warning']++;
    elseif ($alert['severity'] === 'info') $result['Info']++;
    else $result['OK']++;
  }
  $data = array();
  foreach ($result as $name => $value) {
    $data[] = array('name'=>$name, 'y'=>$value);
  }
  return $data;
}

function md_recent_activity($db)
{
  return md_rows($db, "SELECT user,deskripsi,tgl FROM log_aktifitas WHERE COALESCE(user,'')<>'' AND user<>'guest' ORDER BY tgl DESC LIMIT 8");
}
?>
