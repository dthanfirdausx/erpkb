<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "quality_dashboard_lib.php";

$act = isset($_GET['act']) ? $_GET['act'] : '';

if ($act === 'material_search') {
  session_check_json();
  $term = isset($_POST['term']) ? trim($_POST['term']) : '';
  $params = array();
  $where = " WHERE b.status=1 ";
  if ($term !== '') {
    $where .= " AND (b.kd_barang LIKE ? OR b.nm_barang LIKE ?) ";
    $params[] = '%'.$term.'%';
    $params[] = '%'.$term.'%';
  }
  $rows = $db->query(
    "SELECT b.kd_barang,b.nm_barang,b.satuan,
            COALESCE(SUM(CASE WHEN sl.stock_type='QUALITY' THEN sl.qty_sisa ELSE 0 END),0) AS quality_qty,
            COALESCE(SUM(CASE WHEN sl.stock_type='BLOCKED' THEN sl.qty_sisa ELSE 0 END),0) AS blocked_qty
     FROM barang b
     LEFT JOIN stock_layer sl ON sl.kode=b.kd_barang AND sl.qty_sisa>0
     $where
     GROUP BY b.kd_barang,b.nm_barang,b.satuan
     ORDER BY b.kd_barang LIMIT 30",
    $params
  );
  $results = array();
  foreach ($rows as $row) {
    $results[] = array(
      'id' => $row->kd_barang,
      'text' => $row->kd_barang.' - '.$row->nm_barang.' | QI '.qdash_num($row->quality_qty,2).' / Blocked '.qdash_num($row->blocked_qty,2).' '.$row->satuan
    );
  }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('results'=>$results));
  exit;
}

if ($act === 'detail') {
  session_check_json();
  $source = qdash_input('source');
  $id = (int)qdash_input('id', 0);
  if ($source === 'STOCK') {
    $row = $db->fetch(
      "SELECT sl.*,b.nm_barang,b.satuan,ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name
       FROM stock_layer sl
       LEFT JOIN barang b ON b.kd_barang=sl.kode
       LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
       LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
       LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
       WHERE sl.id=?",
      array($id)
    );
    if (!$row) { echo '<div class="alert alert-warning">Layer stok tidak ditemukan.</div>'; exit; }
    $location = qdash_location_text($row);
    ?>
    <div class="alert alert-info"><strong>Stock Quality Detail.</strong> Layer ini menjadi dasar trace material, lokasi, lot/dokumen BC, dan referensi penerimaan.</div>
    <div class="row">
      <div class="col-md-6"><table class="table table-bordered table-condensed qdash-detail-table"><tr><th>Layer</th><td>#<?=intval($row->id);?></td></tr><tr><th>Material</th><td><strong><?=qdash_h($row->kode);?></strong><br><?=qdash_h($row->nm_barang);?></td></tr><tr><th>Stock Type</th><td><?=qdash_status_badge($row->stock_type);?></td></tr><tr><th>Location</th><td><?=qdash_h($location ?: '-');?></td></tr><tr><th>Receipt Date</th><td><?=qdash_h($row->tgl_masuk ?: substr((string)$row->created_at,0,10));?></td></tr></table></div>
      <div class="col-md-6"><table class="table table-bordered table-condensed qdash-detail-table"><tr><th>Qty Masuk</th><td class="text-right"><?=qdash_num($row->qty_masuk,5).' '.qdash_h($row->satuan);?></td></tr><tr><th>Qty Sisa</th><td class="text-right"><strong><?=qdash_num($row->qty_sisa,5).' '.qdash_h($row->satuan);?></strong></td></tr><tr><th>No BPB</th><td><?=qdash_h($row->no_bpb);?></td></tr><tr><th>No Aju</th><td><?=qdash_h($row->no_aju);?></td></tr><tr><th>Dokumen BC</th><td><?=qdash_h(trim((string)$row->jenis_dokpab.' '.(string)$row->no_dokpab));?></td></tr></table></div>
    </div>
    <table class="table table-bordered table-condensed qdash-detail-table"><tr><th>Reference</th><td><?=qdash_h(trim((string)$row->ref_table.' #'.(string)$row->ref_id, ' #'));?></td></tr><tr><th>Legacy Location</th><td><?=qdash_h($row->lokasi);?></td></tr></table>
    <?php
    exit;
  }

  if ($source === 'NG') {
    $row = $db->fetch(
      "SELECT d.*,n.tgl_produksi AS header_date,n.user AS reporter,n.catatan,b.nm_barang,b.satuan
       FROM data_ng d
       LEFT JOIN ng n ON n.id=d.id_ng
       LEFT JOIN barang b ON b.kd_barang=d.kd_barang
       WHERE d.id=?",
      array($id)
    );
    if (!$row) { echo '<div class="alert alert-warning">Data NG tidak ditemukan.</div>'; exit; }
    ?>
    <div class="alert alert-warning"><strong>Defect / NG Detail.</strong> Gunakan data ini sebagai sumber NCR/CAPA bila defect perlu investigasi lanjutan.</div>
    <table class="table table-bordered table-condensed qdash-detail-table">
      <tr><th>NG Detail</th><td>#<?=intval($row->id);?> / Header #<?=intval($row->id_ng);?></td></tr>
      <tr><th>Tanggal Produksi</th><td><?=qdash_h($row->tgl_produksi);?></td></tr>
      <tr><th>Material</th><td><strong><?=qdash_h($row->kd_barang);?></strong><br><?=qdash_h($row->nm_barang);?></td></tr>
      <tr><th>Qty NG</th><td><?=qdash_num($row->jumlah,5).' '.qdash_h($row->satuan);?></td></tr>
      <tr><th>Keterangan</th><td><?=qdash_h($row->ket);?></td></tr>
      <tr><th>Catatan Header</th><td><?=qdash_h($row->catatan);?></td></tr>
      <tr><th>Reporter</th><td><?=qdash_h($row->reporter);?></td></tr>
    </table>
    <?php
    exit;
  }

  if ($source === 'SCRAP') {
    $row = $db->fetch(
      "SELECT c.*,po.no_production_order,po.material_code,po.material_name,po.uom,po.plant,po.order_qty
       FROM production_order_confirmation c
       LEFT JOIN production_order po ON po.id_production_order=c.id_production_order
       WHERE c.id_confirmation=?",
      array($id)
    );
    if (!$row) { echo '<div class="alert alert-warning">Confirmation tidak ditemukan.</div>'; exit; }
    ?>
    <div class="alert alert-warning"><strong>Production Scrap/Rework Detail.</strong> Data ini berasal dari production confirmation dan menjadi sinyal kualitas proses produksi.</div>
    <div class="row">
      <div class="col-md-6"><table class="table table-bordered table-condensed qdash-detail-table"><tr><th>Confirmation</th><td><?=qdash_h($row->confirmation_no);?></td></tr><tr><th>Production Order</th><td><?=qdash_h($row->no_production_order);?></td></tr><tr><th>Material</th><td><strong><?=qdash_h($row->material_code);?></strong><br><?=qdash_h($row->material_name);?></td></tr><tr><th>Operation</th><td><?=qdash_h(trim((string)$row->operation_no.' - '.(string)$row->operation_name, ' -'));?></td></tr></table></div>
      <div class="col-md-6"><table class="table table-bordered table-condensed qdash-detail-table"><tr><th>Posting Date</th><td><?=qdash_h($row->posting_date);?></td></tr><tr><th>Yield</th><td class="text-right"><?=qdash_num($row->yield_qty,5).' '.qdash_h($row->uom);?></td></tr><tr><th>Scrap</th><td class="text-right"><?=qdash_num($row->scrap_qty,5).' '.qdash_h($row->uom);?></td></tr><tr><th>Rework</th><td class="text-right"><?=qdash_num($row->rework_qty,5).' '.qdash_h($row->uom);?></td></tr></table></div>
    </div>
    <table class="table table-bordered table-condensed qdash-detail-table"><tr><th>Work Center / Shift</th><td><?=qdash_h(trim((string)$row->work_center.' / '.(string)$row->shift_code, ' /'));?></td></tr><tr><th>Operator</th><td><?=qdash_h($row->operator_name);?></td></tr><tr><th>Remarks</th><td><?=qdash_h($row->remarks);?></td></tr></table>
    <?php
    exit;
  }

  echo '<div class="alert alert-warning">Source detail tidak dikenal.</div>';
  exit;
}

if ($act === 'excel') {
  $initialOutputBufferLevel = ob_get_level();
  ob_start();
  ini_set('display_errors', '0');
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';
  require_once '../../inc/excel_style_helper.php';
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);

  $filters = qdash_filters();
  $rows = qdash_exception_rows($db, $filters);
  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Quality Dashboard'));
  $headers = array(erp_export_label("No"),erp_export_label("Source"),erp_export_label("Date"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Location / Work Center"),erp_export_label("Qty"),erp_export_label("UOM"),erp_export_label("Status"),erp_export_label("Reference"),erp_export_label("Dokumen BC"),erp_export_label("Remarks"));
  foreach ($headers as $c=>$header) $sheet->setCellValueByColumnAndRow($c,4,$header);
  $r = 5; $n = 1; $totalQty = 0;
  foreach ($rows as $row) {
    $totalQty += (float)$row->qty;
    $values = array($n++,$row->source_label,$row->doc_date,$row->material_code,$row->material_name,$row->location,(float)$row->qty,$row->uom,$row->status,$row->reference,$row->bc_document,$row->remarks);
    foreach ($values as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
    $r++;
  }
  $summaryRow = $r + 1;
  $sheet->mergeCells('A'.$summaryRow.':F'.$summaryRow);
  $sheet->setCellValue('A'.$summaryRow,'TOTAL QTY');
  $sheet->setCellValue('G'.$summaryRow,$totalQty);
  erpkb_excel_apply_standard_style($excel, array(
    'sheet'=>$sheet,'title'=>erp_export_title('QUALITY DASHBOARD - SAP QM COCKPIT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>12,
    'numeric_columns'=>array('G'),
    'filters'=>array('Periode'=>$filters['tgl_awal'].' s/d '.$filters['tgl_akhir'],'Source'=>$filters['source_type'],'Stock Type'=>$filters['stock_type'],'Material'=>$filters['material_code'],'Keyword'=>$filters['keyword']),
    'widths'=>array('A'=>6,'B'=>22,'C'=>13,'D'=>16,'E'=>34,'F'=>24,'G'=>14,'H'=>10,'I'=>16,'J'=>26,'K'=>24,'L'=>42)
  ));
  $sheet->getStyle('A'.$summaryRow.':L'.$summaryRow)->getFont()->setBold(true);
  $sheet->getStyle('A'.$summaryRow.':L'.$summaryRow)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5');
  $sheet->getStyle('A'.$summaryRow.':L'.$summaryRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $sheet->getStyle('G'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00000');

  $tmp = erpkb_excel_temp_file('quality_dashboard_');
  PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
  $size = @filesize($tmp);
  $signature = @file_get_contents($tmp, false, null, 0, 2);
  if (!$size || $signature !== 'PK') {
    @unlink($tmp);
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: text/plain; charset=utf-8');
    echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');
    exit;
  }
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="quality_dashboard_'.$filters['tgl_awal'].'_sd_'.$filters['tgl_akhir'].'.xlsx"');
  header('Content-Length: '.$size);
  header('Cache-Control: max-age=0');
  header('Pragma: public');
  readfile($tmp);
  @unlink($tmp);
  exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
