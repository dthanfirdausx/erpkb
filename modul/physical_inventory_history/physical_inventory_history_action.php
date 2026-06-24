<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if(session_status()===PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "physical_inventory_history_lib.php";
$act=isset($_GET['act'])?$_GET['act']:'';

if($act==='material_search'){
  session_check_json(); $term=isset($_POST['term'])?trim($_POST['term']):''; $params=array(); $where=" WHERE b.status=1 ";
  if($term!==''){ $where.=" AND (b.kd_barang LIKE ? OR b.nm_barang LIKE ?) "; $params[]='%'.$term.'%'; $params[]='%'.$term.'%'; }
  $rows=$db->query("SELECT b.kd_barang,b.nm_barang,b.satuan,COALESCE(SUM(sl.qty_sisa),0) stock_qty FROM barang b LEFT JOIN stock_layer sl ON sl.kode=b.kd_barang AND sl.qty_sisa>0 $where GROUP BY b.kd_barang,b.nm_barang,b.satuan ORDER BY b.kd_barang LIMIT 30",$params);
  $results=array(); foreach($rows as $row)$results[]=array('id'=>$row->kd_barang,'text'=>$row->kd_barang.' - '.$row->nm_barang.' | Stock '.number_format((float)$row->stock_qty,5,',','.').' '.$row->satuan);
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('results'=>$results)); exit;
}

if($act==='excel'){
  $initialOutputBufferLevel=ob_get_level(); ob_start(); ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php'; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $input=array('tgl_awal'=>pih_input('tgl_awal',date('Y-m-01')),'tgl_akhir'=>pih_input('tgl_akhir',date('Y-m-d')),'doc_type'=>pih_input('doc_type'),'doc_no'=>pih_input('doc_no'),'material_code'=>pih_input('material_code'),'plant_id'=>pih_input('plant_id'),'storage_location_id'=>pih_input('storage_location_id'),'storage_bin_id'=>pih_input('storage_bin_id'),'stock_type'=>pih_input('stock_type'),'history_status'=>pih_input('history_status'),'keyword'=>pih_input('keyword'));
  $rows=pih_load_rows($db,$input); $excel=new PHPExcel(); $sheet=$excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('PI History'));
  $headers=array(erp_export_label("No"),erp_export_label("Doc Type"),erp_export_label("Doc No"),erp_export_label("Count Date"),erp_export_label("Doc Status"),erp_export_label("Item Status"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Plant"),erp_export_label("Storage Location"),erp_export_label("Storage Bin"),erp_export_label("Stock Type"),erp_export_label("System Qty"),erp_export_label("Counted Qty"),erp_export_label("Difference"),erp_export_label("UOM"),erp_export_label("Counted By"),erp_export_label("Counted At"),erp_export_label("Posting No"),erp_export_label("Movement"),erp_export_label("Posted By"),erp_export_label("Posted At"),erp_export_label("Material Doc"));
  foreach($headers as $c=>$h)$sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5;$n=1;$totSys=0;$totCnt=0;$totDiff=0;
  foreach($rows as $row){ $diff=$row->difference_qty===null?null:(float)$row->difference_qty; $totSys+=(float)$row->system_qty; $totCnt+=($row->counted_qty===null?0:(float)$row->counted_qty); $totDiff+=($diff===null?0:$diff); $values=array($n++,pih_doc_type_label($row->doc_type),$row->doc_no,$row->count_date,$row->document_status,$row->item_status,$row->material_code,$row->material_name,$row->plant_code,$row->storage_code,$row->bin_code,pih_stock_type_label($row->stock_type),(float)$row->system_qty,$row->counted_qty===null?'':(float)$row->counted_qty,$diff===null?'':$diff,$row->uom,$row->counted_by,$row->counted_at,$row->posting_no,$row->movement_type,$row->posted_by,$row->posted_at,$row->material_doc_no); foreach($values as $c=>$v)$sheet->setCellValueByColumnAndRow($c,$r,$v); $r++; }
  $summaryRow=$r+1; $sheet->mergeCells('A'.$summaryRow.':L'.$summaryRow); $sheet->setCellValue('A'.$summaryRow, erp_export_label('TOTAL')); $sheet->setCellValue('M'.$summaryRow,$totSys); $sheet->setCellValue('N'.$summaryRow,$totCnt); $sheet->setCellValue('O'.$summaryRow,$totDiff);
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('PHYSICAL INVENTORY HISTORY - SAP IM'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>23,'numeric_columns'=>array('M','N','O'),'filters'=>array('Periode'=>pih_valid_date($input['tgl_awal'],date('Y-m-01')).' s/d '.pih_valid_date($input['tgl_akhir'],date('Y-m-d')),'Doc Type'=>$input['doc_type'],'Doc No'=>$input['doc_no'],'Status'=>$input['history_status'],'Keyword'=>$input['keyword']),'widths'=>array('A'=>6,'B'=>16,'C'=>18,'D'=>14,'E'=>14,'F'=>14,'G'=>16,'H'=>36,'I'=>12,'J'=>16,'K'=>14,'L'=>18,'M'=>14,'N'=>14,'O'=>14,'P'=>10,'Q'=>16,'R'=>20,'S'=>18,'T'=>10,'U'=>16,'V'=>20,'W'=>18)));
  $sheet->getStyle('A'.$summaryRow.':W'.$summaryRow)->getFont()->setBold(true); $sheet->getStyle('A'.$summaryRow.':W'.$summaryRow)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5'); $sheet->getStyle('A'.$summaryRow.':W'.$summaryRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN); $sheet->getStyle('M'.$summaryRow.':O'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00000');
  $tmp=erpkb_excel_temp_file('physical_inventory_history_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp); $size=@filesize($tmp); $signature=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$signature!=='PK'){@unlink($tmp);while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
  while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="physical_inventory_history_'.date('Ymd_His').'.xlsx"'); header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;
}
header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
