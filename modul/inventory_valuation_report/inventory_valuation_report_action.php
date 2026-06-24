<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "inventory_valuation_report_lib.php";

$act = isset($_GET['act']) ? $_GET['act'] : '';

if ($act === 'material_search') {
  session_check_json();
  $term = isset($_POST['term']) ? trim($_POST['term']) : '';
  $params = array();
  $where = " WHERE b.status=1 ";
  if ($term !== '') { $where .= " AND (b.kd_barang LIKE ? OR b.nm_barang LIKE ?) "; $params[]='%'.$term.'%'; $params[]='%'.$term.'%'; }
  $rows = $db->query("SELECT b.kd_barang,b.nm_barang,b.satuan,COALESCE(SUM(sl.qty_sisa),0) stock_qty FROM barang b LEFT JOIN stock_layer sl ON sl.kode=b.kd_barang AND sl.qty_sisa>0 $where GROUP BY b.kd_barang,b.nm_barang,b.satuan ORDER BY b.kd_barang LIMIT 30", $params);
  $results = array();
  foreach ($rows as $row) $results[] = array('id'=>$row->kd_barang,'text'=>$row->kd_barang.' - '.$row->nm_barang.' | Stock '.number_format((float)$row->stock_qty,5,',','.').' '.$row->satuan);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('results'=>$results));
  exit;
}

if ($act === 'layer_detail') {
  session_check_json();
  $input = ivr_input_array();
  $layers = ivr_load_layers($db, $input);
  $totalQty = 0; $totalValue = 0; $count = 0;
  ?>
  <div class="alert alert-info"><strong>Valuation Layer Detail.</strong> Nilai dihitung dari open layer FIFO: <code>qty_sisa x unit price</code>. Unit price diambil dari harga pemasukan/detail material document yang tersedia.</div>
  <div class="table-responsive"><table class="table table-bordered table-condensed ivr-layer-table">
    <thead><tr class="bg-gray"><th>Layer</th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th>Receipt / BPB</th><th><?=wh_h(wh_t('warehouse_customs', 'Customs'));?></th><th class="text-right">Qty Sisa</th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th class="text-right">Unit Price</th><th class="text-right">Value</th><th>Price Source</th></tr></thead><tbody>
    <?php foreach($layers as $row){ $count++; $totalQty+=(float)$row->qty_sisa; $totalValue+=(float)$row->stock_value; $location=trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code,' /'); $customs=trim((string)$row->jenis_dokpab.' '.(string)$row->no_dokpab.' / '.(string)$row->no_aju,' /'); $source=$row->purchase_price > 0 ? 'Pemasukan Detail' : ((float)$row->unit_price > 0 ? 'Material Document' : 'No Price'); ?>
      <tr><td>#<?=intval($row->id);?><br><small><?=ivr_h($row->ref_table.' '.$row->ref_id);?></small></td><td><strong><?=ivr_h($row->kode);?></strong><br><small><?=ivr_h($row->nm_barang);?></small></td><td><?=ivr_h($location ?: '-');?><br><small><?=ivr_h(ivr_stock_type_label($row->stock_type));?></small></td><td><?=ivr_h($row->no_bpb);?><br><small><?=ivr_h($row->tgl_masuk ?: substr((string)$row->created_at,0,10));?></small></td><td><small><?=ivr_h($customs ?: '-');?></small></td><td class="text-right"><?=number_format((float)$row->qty_sisa,5,',','.');?></td><td><?=ivr_h($row->satuan);?></td><td class="text-right"><?=number_format((float)$row->unit_price,5,',','.');?></td><td class="text-right"><strong><?=number_format((float)$row->stock_value,2,',','.');?></strong></td><td><?=ivr_h($source);?></td></tr>
    <?php } ?>
    <?php if($count===0){ ?><tr><td colspan="10" class="text-center text-muted">Tidak ada open layer untuk filter ini.</td></tr><?php } ?>
    </tbody><tfoot><tr class="bg-gray"><th colspan="5" class="text-right">Total</th><th class="text-right"><?=number_format($totalQty,5,',','.');?></th><th></th><th></th><th class="text-right"><?=number_format($totalValue,2,',','.');?></th><th></th></tr></tfoot>
  </table></div>
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

  $input = ivr_input_array();
  $groups = ivr_group_layers(ivr_load_layers($db, $input));
  $asOf = ivr_valid_date($input['as_of_date'], date('Y-m-d'));
  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Inventory Valuation'));
  $headers = array(erp_export_label("No"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Material Type"),erp_export_label("Material Group"),erp_export_label("Plant"),erp_export_label("Storage Location"),erp_export_label("Storage Bin"),erp_export_label("Stock Type"),erp_export_label("Qty"),erp_export_label("UOM"),erp_export_label("Avg Price"),erp_export_label("Total Value"),erp_export_label("Min Price"),erp_export_label("Max Price"),erp_export_label("Layers"),erp_export_label("Customs Docs"),erp_export_label("Zero Price Layers"));
  foreach($headers as $c=>$h) $sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5; $n=1; $totalQty=0; $totalValue=0;
  foreach($groups as $row){ $totalQty+=(float)$row->total_qty; $totalValue+=(float)$row->total_value; $values=array($n++,$row->material_code,$row->material_name,$row->material_type,$row->material_group,$row->plant_code,$row->storage_code,$row->bin_code,ivr_stock_type_label($row->stock_type),(float)$row->total_qty,$row->uom,(float)$row->avg_price,(float)$row->total_value,(float)$row->min_price,(float)$row->max_price,(int)$row->layer_count,(int)$row->customs_doc_count,(int)$row->zero_layers); foreach($values as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v); $r++; }
  $summaryRow=$r+1; $sheet->mergeCells('A'.$summaryRow.':I'.$summaryRow); $sheet->setCellValue('A'.$summaryRow, erp_export_label('TOTAL')); $sheet->setCellValue('J'.$summaryRow,$totalQty); $sheet->setCellValue('M'.$summaryRow,$totalValue);
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('INVENTORY VALUATION REPORT - SAP MM'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>18,'numeric_columns'=>array('J','L','N','O'),'money_columns'=>array('M'),'filters'=>array('As Of Date'=>$asOf,'Material'=>$input['material_code'] ?: erp_export_all_text(),'Stock Type'=>$input['stock_type'] ?: erp_export_all_text(),'Valuation'=>$input['valuation_status'] ?: erp_export_all_text(),'Keyword'=>$input['keyword']),'widths'=>array('A'=>6,'B'=>16,'C'=>36,'D'=>22,'E'=>24,'F'=>12,'G'=>18,'H'=>14,'I'=>18,'J'=>14,'K'=>10,'L'=>14,'M'=>18,'N'=>14,'O'=>14,'P'=>10,'Q'=>12,'R'=>16)));
  $sheet->getStyle('A'.$summaryRow.':R'.$summaryRow)->getFont()->setBold(true);
  $sheet->getStyle('A'.$summaryRow.':R'.$summaryRow)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5');
  $sheet->getStyle('A'.$summaryRow.':R'.$summaryRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $sheet->getStyle('J'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00000');
  $sheet->getStyle('M'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00');
  $tmp = erpkb_excel_temp_file('inventory_valuation_');
  PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);
  $size=@filesize($tmp); $signature=@file_get_contents($tmp,false,null,0,2);
  if(!$size || $signature!=='PK'){ @unlink($tmp); while(ob_get_level()>$initialOutputBufferLevel) ob_end_clean(); header('Content-Type:text/plain; charset=utf-8'); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit; }
  while(ob_get_level()>$initialOutputBufferLevel) ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="inventory_valuation_report_'.$asOf.'.xlsx"');
  header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public');
  readfile($tmp); @unlink($tmp); exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
