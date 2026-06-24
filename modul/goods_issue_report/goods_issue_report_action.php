<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "goods_issue_report_lib.php";

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
  $rows = $db->query("SELECT b.kd_barang,b.nm_barang,b.satuan,COALESCE(SUM(sl.qty_sisa),0) AS stock_qty
                      FROM barang b
                      LEFT JOIN stock_layer sl ON sl.kode=b.kd_barang AND sl.qty_sisa>0
                      $where
                      GROUP BY b.kd_barang,b.nm_barang,b.satuan
                      ORDER BY b.kd_barang LIMIT 30", $params);
  $results = array();
  foreach ($rows as $row) $results[] = array('id'=>$row->kd_barang,'text'=>$row->kd_barang.' - '.$row->nm_barang.' | Stock '.number_format((float)$row->stock_qty,5,',','.').' '.$row->satuan);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('results'=>$results));
  exit;
}

if ($act === 'detail') {
  session_check_json();
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  if ($id <= 0) { echo '<div class="alert alert-danger">Movement tidak valid.</div>'; exit; }
  $row = $db->fetch("SELECT dt.*,b.nm_barang,b.satuan AS material_uom,
                           ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
                           p.no_aju AS header_no_aju,p.no_dokpab AS header_no_dokpab,p.jenis_dokpab,p.tgl_dokpab,p.kantor_pabean,p.negara_asal,
                           po.purchase_order_no,v.nama AS vendor_name,
                           ref.no_ref AS original_doc,ref.move_code AS original_mvt,ref.posting_date AS original_posting_date
                    FROM detail_transaksi dt
                    LEFT JOIN barang b ON b.kd_barang=COALESCE(NULLIF(dt.destination_material_code,''),NULLIF(dt.kd_barang,''))
                    LEFT JOIN erp_storage_location es ON es.id=COALESCE(dt.storage_location_id,dt.destination_storage_location_id)
                    LEFT JOIN erp_plant ep ON ep.id=COALESCE(dt.plant_id,es.plant_id)
                    LEFT JOIN erp_storage_bin eb ON eb.id=COALESCE(dt.storage_bin_id,dt.destination_storage_bin_id)
                    LEFT JOIN pemasukan p ON p.no_bpb=COALESCE(NULLIF(dt.no_bpb,''),NULLIF(dt.no_ref,''),NULLIF(dt.ref_pengganti,''))
                    LEFT JOIN pemasok v ON v.kode_pemasok=p.pemasok
                    LEFT JOIN purchase_order po ON po.id=dt.id_po OR po.id=dt.ref_id
                    LEFT JOIN detail_transaksi ref ON ref.id_detail=dt.ref_detail_id
                    WHERE dt.id_detail=? LIMIT 1", array($id));
  if (!$row) { echo '<div class="alert alert-danger">Movement tidak ditemukan.</div>'; exit; }
  $direction = ($row->direction === 'OUT' || (!$row->direction && (float)$row->qty < 0)) ? 'OUT' : 'IN';
  $stockType = $row->stock_type ?: ($row->destination_stock_type ?: 'UNRESTRICTED');
  $doc = $row->no_ref ?: ($row->no_bpb ?: $row->ref_pengganti);
  ?>
  <style>.gir-detail-table th{width:190px;background:#f8fafc}.gir-detail-table th,.gir-detail-table td{font-size:12px}.gir-detail-head{border:1px solid #e5e7eb;background:#f8fafc;border-radius:12px;padding:14px;margin-bottom:14px}</style>
  <div class="gir-detail-head">
    <div class="row">
      <div class="col-sm-8"><h3 style="margin-top:0"><?=gir_h($doc ?: '-');?> <small>Item <?=intval($row->id_detail);?></small></h3><p>Movement Type <?=gir_h($row->move_code);?> - <?=gir_h(gir_movement_label($row->move_code,$row->ref_type,$direction));?> | <span class="label label-<?=$direction==='OUT'?'danger':'success';?>"><?=gir_h($direction);?></span></p></div>
      <div class="col-sm-4 text-right"><h3 style="margin-top:0"><?=number_format(abs((float)$row->qty),5,',','.');?> <?=gir_h($row->uom ?: $row->material_uom);?></h3><p>Amount <?=number_format((float)$row->amount,2,',','.');?></p></div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6"><h4><?=wh_h(wh_t('warehouse_document', 'Document'));?></h4><table class="table table-bordered table-condensed gir-detail-table">
      <tr><th>Material Document</th><td><?=gir_h($doc);?></td></tr>
      <tr><th><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></th><td><?=gir_h($row->posting_date);?></td></tr>
      <tr><th>Document Date</th><td><?=gir_h($row->document_date);?></td></tr>
      <tr><th><?=wh_h(wh_t('warehouse_reference_type', 'Reference Type'));?></th><td><?=gir_h($row->ref_type);?></td></tr>
      <tr><th>Created By</th><td><?=gir_h($row->created_by ?: $row->user);?></td></tr>
      <tr><th>Remark / Reason</th><td><?=gir_h(trim((string)$row->remark.' '.$row->reason));?></td></tr>
    </table></div>
    <div class="col-md-6"><h4>Material & Quantity</h4><table class="table table-bordered table-condensed gir-detail-table">
      <tr><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><td><?=gir_h(trim((string)($row->destination_material_code ?: $row->kd_barang).' - '.(string)$row->nm_barang,' -'));?></td></tr>
      <tr><th><?=wh_h(wh_t('warehouse_movement', 'Movement'));?></th><td><?=gir_h($row->move_code.' - '.gir_movement_label($row->move_code,$row->ref_type,$direction));?></td></tr>
      <tr><th><?=wh_h(wh_t('warehouse_direction', 'Direction'));?></th><td><?=gir_h($direction);?></td></tr>
      <tr><th><?=wh_h(wh_t('warehouse_qty', 'Qty'));?></th><td><?=number_format(abs((float)$row->qty),5,',','.');?> <?=gir_h($row->uom ?: $row->material_uom);?></td></tr>
      <tr><th>Price</th><td><?=number_format((float)$row->price,2,',','.');?></td></tr>
      <tr><th><?=wh_h(wh_t('warehouse_amount', 'Amount'));?></th><td><?=number_format((float)$row->amount,2,',','.');?></td></tr>
    </table></div>
  </div>
  <div class="row">
    <div class="col-md-6"><h4><?=wh_h(wh_t('warehouse_location', 'Location'));?></h4><table class="table table-bordered table-condensed gir-detail-table">
      <tr><th><?=wh_h(wh_t('common_plant', 'Plant'));?></th><td><?=gir_h(trim((string)$row->plant_code.' - '.(string)$row->plant_name,' -'));?></td></tr>
      <tr><th><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></th><td><?=gir_h(trim((string)$row->storage_code.' - '.(string)$row->storage_name,' -'));?></td></tr>
      <tr><th><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></th><td><?=gir_h(trim((string)$row->bin_code.' - '.(string)$row->bin_name,' -'));?></td></tr>
      <tr><th><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></th><td><?=gir_h(gir_stock_type_label($stockType));?></td></tr>
      <tr><th>Legacy Location</th><td><?=gir_h($row->lokasi);?></td></tr>
    </table></div>
    <div class="col-md-6"><h4>Customs & Reference</h4><table class="table table-bordered table-condensed gir-detail-table">
      <tr><th>No Aju</th><td><?=gir_h($row->no_aju ?: $row->header_no_aju);?></td></tr>
      <tr><th>Dokumen BC</th><td><?=gir_h(trim((string)$row->jenis_dokpab.' '.(string)($row->no_dokpab ?: $row->header_no_dokpab)));?></td></tr>
      <tr><th>Tgl Dokumen BC</th><td><?=gir_h($row->tgl_dokpab);?></td></tr>
      <tr><th>PO</th><td><?=gir_h($row->purchase_order_no);?></td></tr>
      <tr><th>Vendor</th><td><?=gir_h($row->vendor_name);?></td></tr>
      <tr><th>Reversal Ref</th><td><?=gir_h(trim((string)$row->original_doc.' '.(string)$row->original_mvt.' '.(string)$row->original_posting_date));?></td></tr>
    </table></div>
  </div>
  <?php
  exit;
}

if ($act === 'excel') {
  $initialOutputBufferLevel = ob_get_level();
  ob_start();
  ini_set('display_errors','0');
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';
  require_once '../../inc/excel_style_helper.php';
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);

  $input = gir_input_array();
  $rows = gir_load_rows($db, $input);
  $summary = gir_summary($db, $input);
  $from = gir_valid_date($input['tgl_awal'], date('Y-m-01'));
  $to = gir_valid_date($input['tgl_akhir'], date('Y-m-d'));

  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Goods Issue'));
  $headers = array(erp_export_label("No"),erp_export_label("Material Document"),erp_export_label("Item"),erp_export_label("Posting Date"),erp_export_label("Document Date"),erp_export_label("Movement Type"),erp_export_label("Movement Text"),erp_export_label("Direction"),erp_export_label("Reference Type"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Plant"),erp_export_label("Storage Location"),erp_export_label("Storage Bin"),erp_export_label("Stock Type"),erp_export_label("Return/Gain Qty"),erp_export_label("Issued Qty"),erp_export_label("UOM"),erp_export_label("Price"),erp_export_label("Amount"),erp_export_label("No Aju"),erp_export_label("Dokumen BC"),erp_export_label("PO"),erp_export_label("Vendor"),erp_export_label("User"),erp_export_label("Remark"));
  foreach ($headers as $c=>$h) $sheet->setCellValueByColumnAndRow($c,4,$h);
  $r = 5; $n = 1;
  foreach ($rows as $row) {
    $direction = $row->movement_direction;
    $doc = $row->no_ref ?: ($row->no_bpb ?: $row->ref_pengganti);
    $qtyIn = $direction === 'IN' ? abs((float)$row->qty) : 0;
    $qtyOut = $direction === 'OUT' ? abs((float)$row->qty) : 0;
    $values = array($n++,$doc,$row->id_detail,$row->posting_date,$row->document_date,$row->move_code,gir_movement_label($row->move_code,$row->ref_type,$direction),$direction,$row->ref_type,$row->material_code,$row->nm_barang,$row->plant_code,$row->storage_code,$row->bin_code,gir_stock_type_label($row->stock_type_label),$qtyIn,$qtyOut,$row->uom ?: $row->satuan,(float)$row->price,(float)$row->amount,$row->no_aju ?: $row->header_no_aju,$row->no_dokpab ?: $row->header_no_dokpab,$row->purchase_order_no,$row->vendor_name,$row->username,$row->remark ?: $row->reason);
    foreach ($values as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
    $r++;
  }
  $summaryRow = $r + 1;
  $sheet->mergeCells('A'.$summaryRow.':O'.$summaryRow);
  $sheet->setCellValue('A'.$summaryRow, erp_export_label('TOTAL'));
  $sheet->setCellValue('P'.$summaryRow,(float)$summary->qty_in);
  $sheet->setCellValue('Q'.$summaryRow,(float)$summary->qty_out);
  $sheet->setCellValue('T'.$summaryRow,(float)$summary->total_amount);

  erpkb_excel_apply_standard_style($excel,array(
    'sheet'=>$sheet,
    'title'=>erp_export_title('GOODS ISSUE REPORT - SAP MM'),
    'header_row'=>4,
    'first_data_row'=>5,
    'last_data_row'=>max(5,$r-1),
    'column_count'=>26,
    'numeric_columns'=>array('P','Q'),
    'money_columns'=>array('S','T'),
    'filters'=>array('Periode'=>$from.' s/d '.$to,'Material'=>$input['material_code'] ?: erp_export_all_text(),'Movement'=>$input['move_code'] ?: erp_export_all_text(),'Direction'=>$input['direction'] ?: erp_export_all_text(),'Stock Type'=>$input['stock_type'] ?: erp_export_all_text(),'Keyword'=>$input['keyword']),
    'widths'=>array('A'=>6,'B'=>22,'C'=>10,'D'=>20,'E'=>20,'F'=>12,'G'=>28,'H'=>12,'I'=>18,'J'=>16,'K'=>34,'L'=>12,'M'=>18,'N'=>14,'O'=>18,'P'=>14,'Q'=>14,'R'=>10,'S'=>14,'T'=>16,'U'=>28,'V'=>20,'W'=>20,'X'=>26,'Y'=>16,'Z'=>34)
  ));
  $sheet->getStyle('A'.$summaryRow.':Z'.$summaryRow)->getFont()->setBold(true);
  $sheet->getStyle('A'.$summaryRow.':Z'.$summaryRow)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5');
  $sheet->getStyle('A'.$summaryRow.':Z'.$summaryRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $sheet->getStyle('P'.$summaryRow.':Q'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00000');
  $sheet->getStyle('S'.$summaryRow.':T'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00');

  $tmp = erpkb_excel_temp_file('goods_issue_');
  PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);
  $size = @filesize($tmp); $signature = @file_get_contents($tmp,false,null,0,2);
  if(!$size || $signature !== 'PK'){ @unlink($tmp); while(ob_get_level()>$initialOutputBufferLevel) ob_end_clean(); header('Content-Type:text/plain; charset=utf-8'); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit; }
  while(ob_get_level()>$initialOutputBufferLevel) ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="goods_issue_report_'.$from.'_sd_'.$to.'.xlsx"');
  header('Content-Length: '.$size);
  header('Cache-Control: max-age=0');
  header('Pragma: public');
  readfile($tmp); @unlink($tmp); exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
