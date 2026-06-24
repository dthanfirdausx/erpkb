<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "customs_stock_traceability_lib.php";
$act = isset($_GET['act']) ? $_GET['act'] : '';

if ($act === 'material_search') {
  session_check_json();
  $term = isset($_POST['term']) ? trim($_POST['term']) : '';
  $params = array(); $where = " WHERE b.status=1 ";
  if ($term !== '') { $where .= " AND (b.kd_barang LIKE ? OR b.nm_barang LIKE ?) "; $params[]='%'.$term.'%'; $params[]='%'.$term.'%'; }
  $rows = $db->query("SELECT b.kd_barang,b.nm_barang,b.satuan,COALESCE(SUM(sl.qty_sisa),0) stock_qty FROM barang b LEFT JOIN stock_layer sl ON sl.kode=b.kd_barang AND sl.qty_sisa>0 $where GROUP BY b.kd_barang,b.nm_barang,b.satuan ORDER BY b.kd_barang LIMIT 30", $params);
  $results = array();
  foreach ($rows as $row) $results[] = array('id'=>$row->kd_barang,'text'=>$row->kd_barang.' - '.$row->nm_barang.' | Stock '.number_format((float)$row->stock_qty,5,',','.').' '.$row->satuan);
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('results'=>$results)); exit;
}

function cst_detail_input() {
  return array(
    'jenis_dokpab'=>cst_input('jenis_dokpab'),
    'no_aju'=>cst_input('no_aju'),
    'no_dokpab'=>cst_input('no_dokpab'),
    'material_code'=>cst_input('material_code'),
    'plant_id'=>cst_input('plant_id'),
    'storage_location_id'=>cst_input('storage_location_id'),
    'storage_bin_id'=>cst_input('storage_bin_id'),
    'stock_type'=>cst_input('stock_type'),
    'open_only'=>'Y'
  );
}

if ($act === 'detail') {
  session_check_json();
  $layers = cst_load_layers($db, cst_detail_input());
  $totalMasuk=0; $totalUsed=0; $totalSisa=0; $rowCount=0;
  ?>
  <div class="alert alert-info"><strong>Detail Customs Stock.</strong> Menampilkan layer/lot pembentuk saldo dokumen pabean ini, termasuk BPB, No Aju, No Pendaftaran, lokasi, dan referensi transaksi. Jika batch fisik belum tersedia, Layer ID menjadi trace key stok.</div>
  <div class="table-responsive"><table class="table table-bordered table-condensed cst-detail-table">
    <thead><tr class="bg-gray"><th>Layer / Batch</th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th>Receipt Date</th><th>No BPB</th><th>No Aju</th><th>Dokumen BC</th><th class="text-right">Qty Masuk</th><th class="text-right">Qty Used</th><th class="text-right">Qty Sisa</th><th>Ref</th></tr></thead><tbody>
    <?php foreach($layers as $row){ $rowCount++; $totalMasuk+=(float)$row->qty_masuk; $totalUsed+=(float)$row->qty_used; $totalSisa+=(float)$row->qty_sisa; $location=trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code,' /'); $bc=trim((string)$row->jenis_dokpab.' '.(string)$row->no_dokpab); ?>
    <tr><td><strong>Layer #<?=intval($row->id);?></strong><br><small>Trace key</small></td><td><strong><?=cst_h($row->kode);?></strong><br><small><?=cst_h($row->nm_barang);?></small></td><td><?=cst_h($location ?: '-');?><br><small><?=cst_h(cst_stock_type_label($row->stock_type));?></small></td><td><?=cst_h($row->tgl_masuk ?: substr((string)$row->created_at,0,10));?></td><td><?=cst_h($row->no_bpb);?></td><td><?=cst_h($row->no_aju);?></td><td><?=cst_h($bc);?></td><td class="text-right"><?=number_format((float)$row->qty_masuk,5,',','.');?></td><td class="text-right"><?=number_format((float)$row->qty_used,5,',','.');?></td><td class="text-right"><strong><?=number_format((float)$row->qty_sisa,5,',','.');?></strong></td><td><small><?=cst_h(trim($row->ref_table.' #'.$row->ref_id,' #'));?></small></td></tr>
    <?php } ?>
    <?php if($rowCount===0){ ?><tr><td colspan="11" class="text-center text-muted">Tidak ada layer untuk filter ini.</td></tr><?php } ?>
    </tbody><tfoot><tr class="bg-gray"><th colspan="7" class="text-right">Total</th><th class="text-right"><?=number_format($totalMasuk,5,',','.');?></th><th class="text-right"><?=number_format($totalUsed,5,',','.');?></th><th class="text-right"><?=number_format($totalSisa,5,',','.');?></th><th></th></tr></tfoot>
  </table></div>
  <?php
  exit;
}

if ($act === 'excel') {
  $initialOutputBufferLevel = ob_get_level(); ob_start();
  ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php';
  $input = array('tgl_awal'=>cst_input('tgl_awal'),'tgl_akhir'=>cst_input('tgl_akhir'),'material_code'=>cst_input('material_code'),'plant_id'=>cst_input('plant_id'),'storage_location_id'=>cst_input('storage_location_id'),'storage_bin_id'=>cst_input('storage_bin_id'),'stock_type'=>cst_input('stock_type'),'jenis_dokpab'=>cst_input('jenis_dokpab'),'no_aju'=>cst_input('no_aju'),'no_dokpab'=>cst_input('no_dokpab'),'open_only'=>cst_input('open_only','Y'),'keyword'=>cst_input('keyword'));
  $groups = cst_group_layers(cst_load_layers($db,$input));
  $excel = new PHPExcel(); $sheet = $excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('Customs Stock Trace'));
  $headers = array(erp_export_label("No"),erp_export_label("Jenis BC"),erp_export_label("No Aju"),erp_export_label("No Pendaftaran"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Plant"),erp_export_label("Storage Location"),erp_export_label("Storage Bin"),erp_export_label("Stock Type"),erp_export_label("Oldest Receipt"),erp_export_label("Max Age"),erp_export_label("Layers"),erp_export_label("Qty Masuk"),erp_export_label("Qty Used"),erp_export_label("Qty Sisa"),erp_export_label("UOM"));
  foreach($headers as $c=>$h)$sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5;$n=1;$totIn=0;$totUsed=0;$totSisa=0;
  foreach($groups as $row){$totIn+=(float)$row->qty_masuk;$totUsed+=(float)$row->qty_used;$totSisa+=(float)$row->qty_sisa;$values=array($n++,$row->jenis_dokpab,$row->no_aju,$row->no_dokpab,$row->material_code,$row->material_name,$row->plant_code,$row->storage_code,$row->bin_code,cst_stock_type_label($row->stock_type),$row->oldest_date,(int)$row->max_age,(int)$row->layer_count,(float)$row->qty_masuk,(float)$row->qty_used,(float)$row->qty_sisa,$row->uom);foreach($values as $c=>$v)$sheet->setCellValueByColumnAndRow($c,$r,$v);$r++;}
  $summaryRow=$r+1;$sheet->mergeCells('A'.$summaryRow.':M'.$summaryRow);$sheet->setCellValue('A'.$summaryRow, erp_export_label('TOTAL'));$sheet->setCellValue('N'.$summaryRow,$totIn);$sheet->setCellValue('O'.$summaryRow,$totUsed);$sheet->setCellValue('P'.$summaryRow,$totSisa);
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('CUSTOMS STOCK TRACEABILITY - SAP MM'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>17,'numeric_columns'=>array('N','O','P'),'filters'=>array('Periode'=>($input['tgl_awal']?:erp_export_all_text()).' s/d '.($input['tgl_akhir']?:erp_export_all_text()),'Material'=>$input['material_code'],'Jenis BC'=>$input['jenis_dokpab'],'No Aju'=>$input['no_aju'],'No Pendaftaran'=>$input['no_dokpab'],'Open Only'=>$input['open_only'],'Keyword'=>$input['keyword']),'widths'=>array('A'=>6,'B'=>14,'C'=>30,'D'=>18,'E'=>16,'F'=>36,'G'=>12,'H'=>16,'I'=>14,'J'=>18,'K'=>14,'L'=>10,'M'=>10,'N'=>14,'O'=>14,'P'=>14,'Q'=>10)));
  $sheet->getStyle('A'.$summaryRow.':Q'.$summaryRow)->getFont()->setBold(true);$sheet->getStyle('A'.$summaryRow.':Q'.$summaryRow)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5');$sheet->getStyle('A'.$summaryRow.':Q'.$summaryRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);$sheet->getStyle('N'.$summaryRow.':P'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00000');
  $tmp=erpkb_excel_temp_file('customs_stock_trace_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$signature=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$signature!=='PK'){@unlink($tmp);while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
  while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="customs_stock_traceability_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
}
header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
