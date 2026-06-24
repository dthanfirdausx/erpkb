<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
require_once "../../inc/accounting_journal.php";
session_check_json();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);

function dpi_json($status,$message='',$extra=array()){
  echo json_encode(array(array_merge(array('status'=>$status,'error_message'=>$message),$extra)));
  exit;
}
function dpi_h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function dpi_num($v,$d=2){ return number_format((float)$v,$d,',','.'); }
function dpi_user(){ return isset($_SESSION['username']) ? $_SESSION['username'] : 'system'; }
function dpi_number($v){ if(function_exists('formatNumber')) return formatNumber($v); return (float)str_replace(array(',',' '),array('',''),(string)$v); }
function dpi_term_days($term){ return preg_match('/(\d+)/',(string)$term,$m) ? (int)$m[1] : 0; }
function dpi_due_date($date,$term){ $d=dpi_term_days($term); return ($date && $d>0) ? date('Y-m-d',strtotime($date.' +'.$d.' days')) : null; }
function dpi_invoice_no(){
  global $db;
  $prefix = 'DP/'.date('Y').'/'.date('m').'/';
  $row = $db->fetch("SELECT MAX(no_sales_invoice) max_no FROM sales_invoice WHERE no_sales_invoice LIKE ?", array($prefix.'%'));
  $next = 1;
  if ($row && $row->max_no) {
    $parts = explode('/',$row->max_no);
    $next = ((int)end($parts)) + 1;
  }
  return $prefix.str_pad($next,4,'0',STR_PAD_LEFT);
}
function dpi_so($id){
  global $db;
  return $db->fetch("
    SELECT so.*,p.nama customer_name,p.alamat customer_address,COALESCE(SUM(sod.nilai),0) so_amount
    FROM sales_order so
    LEFT JOIN sales_order_detail sod ON sod.id_sales_order=so.id_sales_order
    LEFT JOIN penerima p ON p.kode_penerima=so.kode_penerima
    WHERE so.id_sales_order=?
    GROUP BY so.id_sales_order
    LIMIT 1
  ", array($id));
}
function dpi_post_journal($invoiceId){
  global $db;
  if (!function_exists('finance_post_journal')) return 'Helper finance_post_journal belum tersedia.';
  $h = $db->fetch("SELECT * FROM sales_invoice WHERE id_sales=? LIMIT 1", array($invoiceId));
  if (!$h) return 'DP invoice tidak ditemukan.';
  $noBukti = $h->no_sales_invoice;
  $valuta = $h->valuta ?: 'IDR';
  $lines = array(
    array('no_rek'=>'12199','debet'=>$h->gross_amount,'kredit'=>0,'line_text'=>'Down Payment receivable '.$noBukti,'expected_category'=>'aset','valuta'=>$valuta,'kurs'=>1),
    array('no_rek'=>'21401','debet'=>0,'kredit'=>$h->net_amount,'line_text'=>'Customer down payment liability '.$noBukti,'expected_category'=>'kewajiban','valuta'=>$valuta,'kurs'=>1)
  );
  if ((float)$h->tax_amount > 0) {
    $lines[] = array('no_rek'=>'21807','debet'=>0,'kredit'=>$h->tax_amount,'line_text'=>'Output VAT down payment '.$noBukti,'expected_category'=>'kewajiban','valuta'=>$valuta,'kurs'=>1);
  }
  $res = finance_post_journal(array(
    'document_type'=>'DR',
    'posting_status'=>'POSTED',
    'tgl_jurnal'=>$h->posting_date ?: $h->invoice_date,
    'ket'=>'AUTO: Down Payment Invoice '.$noBukti,
    'no_bukti'=>$noBukti,
    'source_module'=>'DOWN_PAYMENT_INVOICE',
    'source_document_no'=>$noBukti,
    'valuta'=>$valuta,
    'kurs'=>1,
    'lines'=>$lines
  ));
  return $res === true ? 'OK' : $res;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
switch ($act) {
  case 'get_no':
    header('Content-Type: application/json');
    echo json_encode(array('no'=>dpi_invoice_no()));
    break;

  case 'get_so':
    header('Content-Type: application/json');
    $so = dpi_so((int)$_GET['id']);
    if (!$so) { echo json_encode(array('status'=>'error','error_message'=>'Sales Order tidak ditemukan.')); break; }
    $tax = (strtolower((string)$so->tax)==='include' || (string)$so->tax==='1') ? '1' : '0';
    $items = $db->query("SELECT sod.*,b.nm_barang,b.satuan FROM sales_order_detail sod LEFT JOIN barang b ON b.kd_barang=sod.kd_barang WHERE sod.id_sales_order=? ORDER BY sod.id_detail", array($so->id_sales_order));
    $html = '<div class="row"><div class="col-sm-3"><strong>'.sd_h('sales_customer', 'Customer').'</strong><br>'.dpi_h($so->customer_name ?: $so->kode_penerima).'</div><div class="col-sm-3"><strong>SO Date</strong><br>'.dpi_h($so->so_date).'</div><div class="col-sm-3"><strong>Currency / Term</strong><br>'.dpi_h($so->currency ?: 'IDR').' / '.dpi_h($so->term).'</div><div class="col-sm-3"><strong>SO Amount</strong><br>'.dpi_num($so->so_amount).'</div></div><hr><div class="table-responsive"><table class="table table-condensed table-bordered"><thead><tr class="bg-gray"><th>'.sd_h('sales_material', 'Material').'</th><th class="text-right">'.sd_h('sales_qty', 'Qty').'</th><th class="text-right">'.sd_h('sales_price', 'Price').'</th><th class="text-right">'.sd_h('sales_amount', 'Amount').'</th></tr></thead><tbody>';
    foreach ($items as $it) $html .= '<tr><td><strong>'.dpi_h($it->kd_barang).'</strong><br><small>'.dpi_h($it->nm_barang ?: $it->ket).'</small></td><td class="text-right">'.dpi_num($it->qty,4).'</td><td class="text-right">'.dpi_num($it->price).'</td><td class="text-right">'.dpi_num($it->nilai).'</td></tr>';
    $html .= '</tbody></table></div>';
    echo json_encode(array('status'=>'good','customer_code'=>$so->kode_penerima,'no_sales_order'=>$so->no_sales_order,'no_po'=>$so->no_po,'currency'=>$so->currency ?: 'IDR','term'=>$so->term,'tax'=>$tax,'so_amount'=>(float)$so->so_amount,'html'=>$html));
    break;

  case 'in':
    $so = dpi_so((int)$_POST['sales_order_id']);
    if (!$so) dpi_json('error','Sales Order tidak ditemukan.');
    $invoiceNo = trim($_POST['no_sales_invoice']);
    if ($invoiceNo === '') $invoiceNo = dpi_invoice_no();
    if ($db->fetch("SELECT id_sales FROM sales_invoice WHERE no_sales_invoice=? LIMIT 1", array($invoiceNo))) dpi_json('error','Nomor DP invoice sudah dipakai.');
    $invoiceDate = $_POST['invoice_date'] ?: date('Y-m-d');
    $net = round(dpi_number($_POST['dp_base_amount']),2);
    if ($net <= 0) dpi_json('error','DP base amount wajib lebih dari nol.');
    $soAmount = (float)$so->so_amount;
    if ($soAmount > 0 && $net > $soAmount) dpi_json('error','DP amount tidak boleh lebih besar dari nilai Sales Order.');
    $tax = isset($_POST['tax']) ? $_POST['tax'] : '0';
    $taxRate = $tax === '1' ? 11 : 0;
    $taxAmount = round($net * ($taxRate/100),2);
    $gross = $net + $taxAmount;
    $dpPercent = $soAmount > 0 ? round(($net / $soAmount) * 100,4) : dpi_number($_POST['dp_percent']);
    $data = array(
      'billing_type'=>'DP',
      'reference_type'=>'SO',
      'reference_no'=>$so->no_sales_order,
      'bill_to'=>$so->kode_penerima,
      'ship_to'=>$so->kode_penerima,
      'invoice_date'=>$invoiceDate,
      'posting_date'=>$invoiceDate,
      'invoice_no'=>'',
      'no_sales_order'=>$so->no_sales_order,
      'no_sales_invoice'=>$invoiceNo,
      'nopo'=>$so->no_po,
      'ttd'=>$_POST['ttd'],
      'term'=>$_POST['term'] ?: $so->term,
      'due_date'=>dpi_due_date($invoiceDate, $_POST['term'] ?: $so->term),
      'valuta'=>$_POST['valuta'] ?: ($so->currency ?: 'IDR'),
      'ship_date'=>null,
      'catatan'=>$_POST['catatan'],
      'no_do'=>null,
      'bank_detail'=>function_exists('infokb') ? infokb()->bank : '',
      'tax'=>$tax,
      'tax_code'=>$tax==='1' ? 'PPN11' : 'NON_TAX',
      'tax_rate'=>$taxRate,
      'net_amount'=>$net,
      'tax_amount'=>$taxAmount,
      'gross_amount'=>$gross,
      'dp_percent'=>$dpPercent,
      'dp_base_amount'=>$soAmount,
      'dp_applied_amount'=>0,
      'dp_open_amount'=>$gross,
      'billing_status'=>'POSTED',
      'created_by'=>dpi_user(),
      'posted_by'=>dpi_user(),
      'posted_at'=>date('Y-m-d H:i:s')
    );
    if (!$db->insert('sales_invoice',$data)) dpi_json('error',$db->getErrorMessage() ?: sd_t('sales_dp_invoice_save_failed', 'DP invoice failed to save.'));
    $id = $db->last_insert_id();
    $db->insert('sales_invoice_detail', array(
      'id_sales'=>$id,
      'sales_order_detail_id'=>null,
      'surat_jalan_detail_id'=>null,
      'line_no'=>10,
      'billing_item_type'=>'DOWN_PAYMENT',
      'kd_barang'=>'DOWN_PAYMENT',
      'nm_barang'=>'Down Payment Invoice '.$so->no_sales_order,
      'material_number'=>'DP',
      'material_description'=>'Down Payment / Uang Muka',
      'qty'=>1,
      'harga'=>$net,
      'unit'=>'LS',
      'nilai'=>$net,
      'tax_code'=>$tax==='1' ? 'PPN11' : 'NON_TAX',
      'tax_rate'=>$taxRate,
      'tax_amount'=>$taxAmount,
      'gross_amount'=>$gross
    ));
    $jr = dpi_post_journal($id);
    if ($jr !== 'OK') dpi_json('error','DP tersimpan tetapi jurnal gagal: '.$jr);
    if (function_exists('simpan_log')) simpan_log('User '.dpi_user().' posting Down Payment Invoice '.$invoiceNo.' dari Sales Order '.$so->no_sales_order.' pada '.date('Y-m-d H:i:s'), dpi_user());
    dpi_json('good','',array('id'=>$id,'invoice_no'=>$invoiceNo));
    break;

  case 'cancel':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $h = $db->fetch("SELECT * FROM sales_invoice WHERE id_sales=? AND billing_type='DP' LIMIT 1", array($id));
    if (!$h) dpi_json('error','DP invoice tidak ditemukan.');
    if ($h->billing_status !== 'POSTED') dpi_json('error','Hanya DP invoice POSTED yang bisa dicancel.');
    if ($reason === '') dpi_json('error','Alasan cancel wajib diisi.');
    $db->update('sales_invoice', array('billing_status'=>'CANCELLED','cancelled_by'=>dpi_user(),'cancelled_at'=>date('Y-m-d H:i:s'),'cancel_reason'=>$reason,'dp_open_amount'=>0), 'id_sales', $id);
    if (function_exists('accounting_reverse_auto_journal')) accounting_reverse_auto_journal($h->no_sales_invoice, $h->no_sales_invoice.'-C', array('tgl_jurnal'=>date('Y-m-d'),'ket'=>'Cancel Down Payment Invoice '.$h->no_sales_invoice));
    if (function_exists('simpan_log')) simpan_log('User '.dpi_user().' cancel Down Payment Invoice '.$h->no_sales_invoice.' alasan '.$reason.' pada '.date('Y-m-d H:i:s'), dpi_user());
    dpi_json('good','');
    break;

  case 'detail':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $h = $db->fetch("SELECT si.*,p.nama customer_name,p.alamat customer_address FROM sales_invoice si LEFT JOIN penerima p ON p.kode_penerima=si.bill_to WHERE si.id_sales=? AND si.billing_type='DP' LIMIT 1", array($id));
    if (!$h) { echo '<div class="alert alert-warning">DP invoice tidak ditemukan.</div>'; break; }
    echo '<h3 style="margin-top:0">'.dpi_h($h->no_sales_invoice).' <small>'.dpi_h($h->billing_status).'</small></h3>';
    echo '<div class="row"><div class="col-sm-3"><strong>'.sd_h('sales_customer', 'Customer').'</strong><br>'.dpi_h($h->customer_name ?: $h->bill_to).'<br><small>'.dpi_h($h->customer_address).'</small></div><div class="col-sm-3"><strong>'.sd_h('sales_order', 'Sales Order').'</strong><br>'.dpi_h($h->no_sales_order).'<br><small>PO '.dpi_h($h->nopo ?: '-').'</small></div><div class="col-sm-3"><strong>Date / Due</strong><br>'.dpi_h($h->invoice_date).'<br><small>Due '.dpi_h($h->due_date ?: '-').'</small></div><div class="col-sm-3"><strong>Currency / Tax</strong><br>'.dpi_h($h->valuta).' / '.dpi_h($h->tax_code).'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><div class="well well-sm"><strong>DPP</strong><br>'.dpi_num($h->net_amount).'</div></div><div class="col-sm-3"><div class="well well-sm"><strong>PPN</strong><br>'.dpi_num($h->tax_amount).'</div></div><div class="col-sm-3"><div class="well well-sm"><strong>Grand Total</strong><br>'.dpi_num($h->gross_amount).'</div></div><div class="col-sm-3"><div class="well well-sm"><strong>Open DP</strong><br>'.dpi_num($h->dp_open_amount).'</div></div></div>';
    echo '<table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Item Type</th><th>Description</th><th class="text-right">'.sd_h('sales_qty', 'Qty').'</th><th class="text-right">'.sd_h('sales_amount', 'Amount').'</th></tr></thead><tbody><tr><td>DOWN_PAYMENT</td><td>Down Payment / Uang Muka untuk SO '.dpi_h($h->no_sales_order).'</td><td class="text-right">1.0000</td><td class="text-right">'.dpi_num($h->net_amount).'</td></tr></tbody></table>';
    if ($h->cancel_reason) echo '<div class="alert alert-warning"><strong>Cancel Reason:</strong> '.dpi_h($h->cancel_reason).'</div>';
    break;

  case 'excel':
    $initial = ob_get_level(); ob_start();
    require_once "../../inc/lib/PHPExcel.php"; require_once "../../inc/excel_style_helper.php"; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $from = !empty($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01'); $to = !empty($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
    $p = array($from,$to); $w = " WHERE si.billing_type='DP' AND si.invoice_date BETWEEN ? AND ? ";
    if (!empty($_GET['customer'])) { $w .= " AND si.bill_to=? "; $p[]=$_GET['customer']; }
    if (!empty($_GET['status'])) { $w .= " AND si.billing_status=? "; $p[]=$_GET['status']; }
    $rows = $db->query("SELECT si.*,p.nama customer_name FROM sales_invoice si LEFT JOIN penerima p ON p.kode_penerima=si.bill_to $w ORDER BY si.invoice_date DESC,si.id_sales DESC", $p);
    $excel = new PHPExcel(); $sh = $excel->setActiveSheetIndex(0); $sh->setTitle(erp_export_sheet_title('DP Invoice'));
    $heads = array(erp_export_label("No"),erp_export_label("DP Invoice"),erp_export_label("Invoice Date"),erp_export_label("Customer"),erp_export_label("Sales Order"),erp_export_label("PO No"),erp_export_label("Currency"),erp_export_label("DP %"),erp_export_label("DPP"),erp_export_label("PPN"),erp_export_label("Gross"),erp_export_label("Applied"),erp_export_label("Open"),erp_export_label("Status"),erp_export_label("Created By"),erp_export_label("Notes"));
    foreach ($heads as $i=>$v) $sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$v);
    $r=5; $n=1; foreach($rows as $row){ $vals=array($n++,$row->no_sales_invoice,$row->invoice_date,$row->customer_name?:$row->bill_to,$row->no_sales_order,$row->nopo,$row->valuta,(float)$row->dp_percent,(float)$row->net_amount,(float)$row->tax_amount,(float)$row->gross_amount,(float)$row->dp_applied_amount,(float)$row->dp_open_amount,$row->billing_status,$row->created_by,$row->catatan); foreach($vals as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r,$v); $r++; }
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sh,'title'=>erp_export_title('DOWN PAYMENT INVOICE'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>16,'numeric_columns'=>array('H','I','J','K','L','M'),'filters'=>array('Invoice Date'=>$from.' s/d '.$to,'Customer'=>isset($_GET['customer'])?$_GET['customer']:erp_export_all_text(),'Status'=>isset($_GET['status'])?$_GET['status']:erp_export_all_text())));
    $tmp = erpkb_excel_temp_file('down_payment_invoice_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp); $size=@filesize($tmp); $sig=@file_get_contents($tmp,false,null,0,2);
    if (!$size || $sig !== 'PK') { @unlink($tmp); while(ob_get_level()>$initial) ob_end_clean(); header('Content-Type:text/plain; charset=utf-8'); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit; }
    while(ob_get_level()>$initial) ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="down_payment_invoice_'.date('Ymd_His').'.xlsx"'); header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;

  default:
    header('Content-Type: application/json'); echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
}
?>
