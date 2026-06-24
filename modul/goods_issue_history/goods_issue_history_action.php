<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();

function gih_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function gih_row($label, $value) {
  return '<tr><th>'.gih_h($label).'</th><td>'.($value === '' || $value === null ? '-' : gih_h($value)).'</td></tr>';
}

function gih_valid_date($date) {
  $dt = DateTime::createFromFormat('Y-m-d', $date);
  return $dt && $dt->format('Y-m-d') === $date;
}

function gih_move_codes_sql() {
  return "'201','202','241','242','261','262','291','292','333','334','551','552','122','601'";
}

function gih_movement_desc($moveCode) {
  $labels = array(
    '201' => 'Issue to Cost Center',
    '202' => 'Reversal Cost Center',
    '241' => 'Issue to Asset',
    '242' => 'Reversal Asset',
    '261' => 'Issue to Production Order',
    '262' => 'Reversal Issue to Production',
    '291' => 'Other Goods Issue',
    '292' => 'Reversal Other Goods Issue',
    '333' => 'Sample Issue',
    '334' => 'Reversal Sample Issue',
    '551' => 'Scrap Issue',
    '552' => 'Reversal Scrap Issue',
    '122' => 'Return to Vendor',
    '601' => 'Goods Issue to Delivery'
  );
  return isset($labels[$moveCode]) ? $labels[$moveCode] : 'Goods Issue';
}

function gih_location_join_sql() {
  return "
     LEFT JOIN (
       SELECT no_bpb,kode,MIN(plant_id) AS plant_id,MIN(storage_location_id) AS storage_location_id,MIN(storage_bin_id) AS storage_bin_id
       FROM stock_layer
       GROUP BY no_bpb,kode
     ) slloc ON slloc.no_bpb=COALESCE(NULLIF(dt.no_bpb,''),NULLIF(dt.ref_pengganti,''),NULLIF(dt.no_ref,'')) AND slloc.kode=dt.kd_barang
     LEFT JOIN (
       SELECT material_doc_id,MIN(plant_id) AS plant_id,MIN(storage_location_id) AS storage_location_id,MIN(storage_bin_id) AS storage_bin_id
       FROM (
         SELECT material_doc_id,plant_id,storage_location_id,storage_bin_id FROM erp_issue_cost_center_trace
         UNION ALL SELECT material_doc_id,plant_id,storage_location_id,storage_bin_id FROM erp_issue_asset_trace
         UNION ALL SELECT material_doc_id,plant_id,storage_location_id,storage_bin_id FROM erp_scrap_issue_trace
         UNION ALL SELECT material_doc_id,plant_id,storage_location_id,storage_bin_id FROM erp_sample_issue_trace
         UNION ALL SELECT material_doc_id,plant_id,storage_location_id,storage_bin_id FROM erp_other_goods_issue_trace
         UNION ALL SELECT material_doc_id,plant_id,storage_location_id,storage_bin_id FROM erp_issue_production_trace
       ) alltrace
       GROUP BY material_doc_id
     ) loc ON loc.material_doc_id=dt.id_detail
     LEFT JOIN erp_plant ep ON ep.id=COALESCE(loc.plant_id,slloc.plant_id)
     LEFT JOIN erp_storage_location es ON es.id=COALESCE(loc.storage_location_id,slloc.storage_location_id)
     LEFT JOIN erp_storage_bin eb ON eb.id=COALESCE(loc.storage_bin_id,slloc.storage_bin_id)
  ";
}

function gih_filter_sql(&$params, $source) {
  $where = " WHERE dt.move_code IN (".gih_move_codes_sql().") ";
  $tglAwal = isset($source['tgl_awal']) ? $source['tgl_awal'] : date('Y-m-01');
  $tglAkhir = isset($source['tgl_akhir']) ? $source['tgl_akhir'] : date('Y-m-d');
  if (gih_valid_date($tglAwal) && gih_valid_date($tglAkhir)) {
    $where .= " AND dt.posting_date BETWEEN ? AND ? ";
    $params[] = $tglAwal.' 00:00:00';
    $params[] = $tglAkhir.' 23:59:59';
  }
  if (!empty($source['move_code'])) {
    $where .= " AND dt.move_code=? ";
    $params[] = $source['move_code'];
  }
  if (!empty($source['direction'])) {
    if ($source['direction'] === 'IN') {
      $where .= " AND (dt.direction='IN' OR (dt.direction IS NULL AND dt.qty>=0)) ";
    } elseif ($source['direction'] === 'OUT') {
      $where .= " AND (dt.direction='OUT' OR (dt.direction IS NULL AND dt.qty<0)) ";
    }
  }
  if (!empty($source['plant_id'])) {
    $where .= " AND COALESCE(loc.plant_id,slloc.plant_id)=? ";
    $params[] = $source['plant_id'];
  }
  if (!empty($source['storage_location_id'])) {
    $where .= " AND COALESCE(loc.storage_location_id,slloc.storage_location_id)=? ";
    $params[] = $source['storage_location_id'];
  }
  if (!empty($source['user'])) {
    $where .= " AND COALESCE(NULLIF(dt.created_by,''),NULLIF(dt.user,''))=? ";
    $params[] = $source['user'];
  }
  if (!empty($source['keyword'])) {
    $kw = '%'.trim($source['keyword']).'%';
    $where .= " AND (
      dt.no_ref LIKE ? OR dt.no_bpb LIKE ? OR dt.ref_pengganti LIKE ? OR dt.kd_barang LIKE ? OR b.nm_barang LIKE ?
      OR dt.no_aju LIKE ? OR dt.no_dokpab LIKE ? OR dt.ref_type LIKE ? OR dt.remark LIKE ? OR dt.reason LIKE ?
      OR p.nopo LIKE ? OR po.purchase_order_no LIKE ? OR pemasok.nama LIKE ?
    ) ";
    for ($i=0; $i<13; $i++) $params[] = $kw;
  }
  return $where;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

if ($act === 'detail') {
  $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
  if ($id <= 0) {
    echo '<div class="alert alert-danger">Goods Issue document tidak valid.</div>';
    exit;
  }

  $row = $db->fetch(
    "SELECT dt.*,
            b.nm_barang,b.satuan AS material_uom,b.type AS material_type,b.kd_kategori,
            p.no_bpb AS gr_no_bpb,p.tgl_bpb,p.nopo,p.pemasok,p.no_invoice,p.no_do,p.no_aju AS header_no_aju,p.no_dokpab AS header_no_dokpab,
            p.jenis_dokpab,p.tgl_dokpab,p.kantor_pabean,p.negara_asal,p.stock_type AS header_stock_type,
            pemasok.nama AS vendor_name,
            po.purchase_order_no,po.po_type,po.payment_term,po.currency,
            ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
            ref.no_ref AS original_doc_no,ref.move_code AS original_move_code,ref.posting_date AS original_posting_date
     FROM detail_transaksi dt
     LEFT JOIN barang b ON b.kd_barang=dt.kd_barang
     LEFT JOIN pemasukan p ON p.no_bpb=COALESCE(NULLIF(dt.no_bpb,''),NULLIF(dt.ref_pengganti,''),NULLIF(dt.no_ref,''))
     LEFT JOIN pemasok ON pemasok.kode_pemasok=p.pemasok
     LEFT JOIN purchase_order po ON po.id=dt.id_po OR po.id=dt.id_po_detail OR po.id=dt.ref_id
     ".gih_location_join_sql()."
     LEFT JOIN detail_transaksi ref ON ref.id_detail=dt.ref_detail_id
     WHERE dt.id_detail=? AND dt.move_code IN (".gih_move_codes_sql().")
     LIMIT 1",
    array('id' => $id)
  );

  if (!$row) {
    echo '<div class="alert alert-danger">Goods Issue document tidak ditemukan.</div>';
    exit;
  }

  $direction = $row->direction ?: ((float)$row->qty < 0 ? 'OUT' : 'IN');
  $directionClass = $direction === 'OUT' ? 'danger' : 'success';
  $docNo = $row->no_ref ?: $row->no_bpb;
  $docYear = $row->posting_date ? date('Y', strtotime($row->posting_date)) : '';
  ?>
  <style>
    .gih-detail-head{border-radius:12px;background:#f8fafc;border:1px solid #e5e7eb;padding:14px 16px;margin-bottom:14px}
    .gih-detail-head h3{margin:0 0 6px;font-size:20px}.gih-detail-head p{margin:0;color:#64748b}
    .gih-detail-table th{width:190px;background:#f8fafc}.gih-detail-table td,.gih-detail-table th{font-size:12px}
  </style>
  <div class="gih-detail-head">
    <div class="row">
      <div class="col-sm-8">
        <h3><?=gih_h($docNo);?> <small>Year <?=gih_h($docYear);?> / Item <?=gih_h($row->no_urut ?: $row->id_detail);?></small></h3>
        <p><?=gih_h(gih_movement_desc($row->move_code));?> | Movement Type <?=gih_h($row->move_code);?> | <span class="label label-<?=$directionClass;?>"><?=gih_h($direction);?></span></p>
      </div>
      <div class="col-sm-4 text-right">
        <h3><?=number_format(abs((float)$row->qty),5,',','.');?> <?=gih_h($row->uom);?></h3>
        <p>Amount <?=number_format((float)$row->amount,2,',','.');?></p>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6">
      <h4>Document Header</h4>
      <table class="table table-bordered table-condensed gih-detail-table">
        <?=gih_row('Goods Issue No', $docNo);?>
        <?=gih_row('Posting Date', $row->posting_date);?>
        <?=gih_row('Document Date', $row->document_date);?>
        <?=gih_row('Created By', $row->created_by ?: $row->user);?>
        <?=gih_row('Reference Type', $row->ref_type);?>
        <?=gih_row('Reference Replacement', $row->ref_pengganti);?>
        <?=gih_row('Reason', $row->reason);?>
        <?=gih_row('Remark', $row->remark);?>
      </table>
    </div>
    <div class="col-md-6">
      <h4>Material & Quantity</h4>
      <table class="table table-bordered table-condensed gih-detail-table">
        <?=gih_row('Material', trim($row->kd_barang.' - '.$row->nm_barang, ' -'));?>
        <?=gih_row('Material Type', $row->material_type);?>
        <?=gih_row('Movement Type', $row->move_code.' - '.gih_movement_desc($row->move_code));?>
        <?=gih_row('Direction', $direction);?>
        <?=gih_row('Quantity', number_format(abs((float)$row->qty),5,',','.'));?>
        <?=gih_row('UOM', $row->uom ?: $row->material_uom);?>
        <?=gih_row('Price', number_format((float)$row->price,2,',','.'));?>
        <?=gih_row('Amount', number_format((float)$row->amount,2,',','.'));?>
      </table>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6">
      <h4>Organization & Location</h4>
      <table class="table table-bordered table-condensed gih-detail-table">
        <?=gih_row('Plant', trim($row->plant_code.' - '.$row->plant_name, ' -'));?>
        <?=gih_row('Storage Location', trim($row->storage_code.' - '.$row->storage_name, ' -'));?>
        <?=gih_row('Storage Bin', trim($row->bin_code.' - '.$row->bin_name, ' -'));?>
        <?=gih_row('Legacy Location', $row->lokasi);?>
        <?=gih_row('Position', $row->posisi);?>
        <?=gih_row('Stock Type', $row->header_stock_type);?>
      </table>
    </div>
    <div class="col-md-6">
      <h4><?=wh_h(wh_t('warehouse_reference', 'Reference'));?></h4>
      <table class="table table-bordered table-condensed gih-detail-table">
        <?=gih_row('Source GR / BPB', $row->gr_no_bpb ?: $row->no_bpb);?>
        <?=gih_row('Purchase Order', $row->purchase_order_no ?: $row->nopo);?>
        <?=gih_row('Vendor', trim($row->pemasok.' - '.$row->vendor_name, ' -'));?>
        <?=gih_row('Is Reversal', ((int)$row->is_reversal === 1 ? 'Yes' : 'No'));?>
        <?=gih_row('Original Document', $row->original_doc_no);?>
        <?=gih_row('Original Movement', $row->original_move_code);?>
      </table>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6">
      <h4>Customs Information</h4>
      <table class="table table-bordered table-condensed gih-detail-table">
        <?=gih_row('No Aju', $row->header_no_aju ?: $row->no_aju);?>
        <?=gih_row('Jenis Dokumen Pabean', $row->jenis_dokpab);?>
        <?=gih_row('No Dokumen Pabean', $row->header_no_dokpab ?: $row->no_dokpab);?>
        <?=gih_row('Tanggal Dokumen Pabean', $row->tgl_dokpab);?>
        <?=gih_row('Kantor Pabean', $row->kantor_pabean);?>
        <?=gih_row('Negara Asal', $row->negara_asal);?>
      </table>
    </div>
    <div class="col-md-6">
      <h4>System Reference</h4>
      <table class="table table-bordered table-condensed gih-detail-table">
        <?=gih_row('Detail ID', $row->id_detail);?>
        <?=gih_row('Reference ID', $row->ref_id);?>
        <?=gih_row('Reference Detail ID', $row->ref_detail_id);?>
        <?=gih_row('Incoming Detail ID', $row->id_incoming_detail);?>
        <?=gih_row('Date Created', $row->date_created);?>
        <?=gih_row('Updated At', $row->updated_at);?>
      </table>
    </div>
  </div>
  <?php
  exit;
}

if ($act === 'excel') {
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  ini_set('display_errors', '0');
  $excelInitialOutputBufferLevel = ob_get_level();
  ob_start();
  require '../../inc/lib/PHPExcel.php';
  require_once '../../inc/excel_style_helper.php';
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);

  $params = array();
  $where = gih_filter_sql($params, $_GET);
  $rows = $db->query(
    "SELECT dt.id_detail,dt.no_ref,dt.posting_date,dt.document_date,dt.move_code,dt.direction,dt.ref_type,dt.ref_pengganti,
            dt.kd_barang,b.nm_barang,dt.qty,dt.uom,dt.price,dt.amount,dt.no_bpb,dt.no_aju,dt.no_dokpab,dt.reason,dt.remark,
            COALESCE(NULLIF(dt.created_by,''),NULLIF(dt.user,'')) AS username,
            p.jenis_dokpab,p.no_aju AS header_no_aju,p.no_dokpab AS header_no_dokpab,p.nopo,pemasok.nama AS vendor_name,
            po.purchase_order_no,ep.plant_code,es.storage_code,eb.bin_code
     FROM detail_transaksi dt
     LEFT JOIN barang b ON b.kd_barang=dt.kd_barang
     LEFT JOIN pemasukan p ON p.no_bpb=COALESCE(NULLIF(dt.no_bpb,''),NULLIF(dt.ref_pengganti,''),NULLIF(dt.no_ref,''))
     LEFT JOIN pemasok ON pemasok.kode_pemasok=p.pemasok
     LEFT JOIN purchase_order po ON po.id=dt.id_po OR po.id=dt.id_po_detail OR po.id=dt.ref_id
     ".gih_location_join_sql()."
     $where
     ORDER BY dt.posting_date DESC,dt.id_detail DESC",
    $params
  );

  $tglAwal = isset($_GET['tgl_awal']) && gih_valid_date($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
  $tglAkhir = isset($_GET['tgl_akhir']) && gih_valid_date($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('GI History'));
  $sheet->mergeCells('A1:X1');
  $sheet->mergeCells('A2:X2');
  $sheet->setCellValue('A1', namaPT);
  $sheet->setCellValue('A2', 'GOODS ISSUE HISTORY '.$tglAwal.' SD '.$tglAkhir);
  $headers = array(erp_export_label("No"),erp_export_label("Detail ID"),erp_export_label("GI No"),erp_export_label("Posting Date"),erp_export_label("Document Date"),erp_export_label("Movement"),erp_export_label("Movement Text"),erp_export_label("Direction"),erp_export_label("Ref Type"),erp_export_label("Reference"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Qty"),erp_export_label("UOM"),erp_export_label("Price"),erp_export_label("Amount"),erp_export_label("Plant"),erp_export_label("SLoc"),erp_export_label("Bin"),erp_export_label("No BPB"),erp_export_label("No Aju"),erp_export_label("No Dok Pabean"),erp_export_label("Reason / Remark"),erp_export_label("User"));
  foreach ($headers as $c => $header) $sheet->setCellValueByColumnAndRow($c, 4, $header);
  $r = 5;
  $n = 1;
  foreach ($rows as $row) {
    $direction = $row->direction ?: ((float)$row->qty < 0 ? 'OUT' : 'IN');
    $values = array(
      $n++,
      $row->id_detail,
      $row->no_ref,
      $row->posting_date,
      $row->document_date,
      $row->move_code,
      gih_movement_desc($row->move_code),
      $direction,
      $row->ref_type,
      $row->purchase_order_no ?: $row->nopo ?: $row->ref_pengganti,
      $row->kd_barang,
      $row->nm_barang,
      abs((float)$row->qty),
      $row->uom,
      (float)$row->price,
      (float)$row->amount,
      $row->plant_code,
      $row->storage_code,
      $row->bin_code,
      $row->no_bpb,
      $row->header_no_aju ?: $row->no_aju,
      trim($row->jenis_dokpab.' '.($row->header_no_dokpab ?: $row->no_dokpab)),
      trim($row->reason.' '.$row->remark),
      $row->username
    );
    foreach ($values as $c => $value) $sheet->setCellValueByColumnAndRow($c, $r, $value);
    $r++;
  }
  $sheet->getStyle('A1:X2')->getFont()->setBold(true);
  $sheet->getStyle('A1:X2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle('A4:X4')->getFont()->setBold(true);
  $sheet->getStyle('A4:X'.max(4, $r-1))->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $sheet->getStyle('M5:M'.max(5, $r-1))->getNumberFormat()->setFormatCode('#,##0.00000');
  $sheet->getStyle('O5:P'.max(5, $r-1))->getNumberFormat()->setFormatCode('#,##0.00');
  erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet,'title'=>erp_export_title('GOODS ISSUE HISTORY'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>24,'numeric_columns'=>array('M'),'money_columns'=>array('O','P'),'filters'=>array('Periode'=>$tglAwal.' s/d '.$tglAkhir,'Movement'=>isset($_GET['move_code'])?$_GET['move_code']:'','Keyword'=>isset($_GET['keyword'])?$_GET['keyword']:'')));
  $tmp = tempnam(sys_get_temp_dir(), 'gih_');
  PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
  $size = filesize($tmp);
  while (ob_get_level() > $excelInitialOutputBufferLevel) ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="goods_issue_history_'.$tglAwal.'_sd_'.$tglAkhir.'.xlsx"');
  header('Content-Length: '.$size);
  readfile($tmp);
  @unlink($tmp);
  exit;
}

echo '<div class="alert alert-danger">Aksi tidak dikenal.</div>';
?>
