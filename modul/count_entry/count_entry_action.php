<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "count_entry_lib.php";
$act = isset($_GET['act']) ? $_GET['act'] : '';

if ($act === 'material_search') {
  session_check_json();
  $term = isset($_POST['term']) ? trim($_POST['term']) : '';
  $params = array(); $where = " WHERE b.status=1 ";
  if ($term !== '') { $where .= " AND (b.kd_barang LIKE ? OR b.nm_barang LIKE ?) "; $params[]='%'.$term.'%'; $params[]='%'.$term.'%'; }
  $rows = $db->query("SELECT b.kd_barang,b.nm_barang,b.satuan,COALESCE(SUM(sl.qty_sisa),0) stock_qty FROM barang b LEFT JOIN stock_layer sl ON sl.kode=b.kd_barang AND sl.qty_sisa>0 $where GROUP BY b.kd_barang,b.nm_barang,b.satuan ORDER BY b.kd_barang LIMIT 30", $params);
  $results = array();
  foreach($rows as $row) $results[] = array('id'=>$row->kd_barang,'text'=>$row->kd_barang.' - '.$row->nm_barang.' | Stock '.number_format((float)$row->stock_qty,5,',','.').' '.$row->satuan);
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('results'=>$results)); exit;
}

function ce_save_count_item($db, $docType, $itemId, $countedQtyRaw, $remarks) {
  $countedQtyRaw = str_replace(',', '.', trim((string)$countedQtyRaw));
  if (!in_array($docType, array('CYCLE_COUNT','STOCK_OPNAME')) || $itemId <= 0 || !is_numeric($countedQtyRaw)) {
    return array('status'=>'error','error_message'=>'Data count entry tidak valid.');
  }
  $item = ce_get_item($db, $docType, $itemId);
  if (!$item) return array('status'=>'error','error_message'=>'Item count entry tidak ditemukan.');
  if (in_array($item->document_status, array('POSTED','CANCELLED'))) return array('status'=>'error','error_message'=>'Dokumen '.$item->doc_no.' sudah '.$item->document_status.' dan tidak bisa diubah.');
  $table = $docType === 'CYCLE_COUNT' ? 'cycle_count_document_items' : 'stock_opname_document_items';
  $docTable = $docType === 'CYCLE_COUNT' ? 'cycle_count_documents' : 'stock_opname_documents';
  $countedQty = round((float)$countedQtyRaw, 5);
  $diff = round($countedQty - (float)$item->system_qty, 5);
  $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
  $ok = $db->update($table, array('counted_qty'=>$countedQty,'difference_qty'=>$diff,'status'=>'COUNTED','counted_by'=>$username,'counted_at'=>date('Y-m-d H:i:s'),'remarks'=>$remarks), 'id', $itemId);
  if (!$ok) return array('status'=>'error','error_message'=>$db->getErrorMessage());
  $open = $db->fetch("SELECT COUNT(*) total_open FROM $table WHERE document_id=? AND status='OPEN'", array((int)$item->document_id));
  $docStatus = ($open && (int)$open->total_open === 0) ? 'COUNTED' : 'OPEN';
  $db->update($docTable, array('status'=>$docStatus,'updated_at'=>date('Y-m-d H:i:s')), 'id', (int)$item->document_id);
  return array('status'=>'good','doc_no'=>$item->doc_no,'difference_qty'=>$diff,'difference_label'=>number_format($diff,5,',','.'),'item_status'=>'COUNTED','document_status'=>$docStatus);
}

if ($act === 'save_count') {
  session_check_json();
  $result = ce_save_count_item($db, ce_input('doc_type'), (int)ce_input('item_id'), ce_input('counted_qty'), ce_input('remarks'));
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($result);
  exit;
}

if ($act === 'save_all') {
  session_check_json();
  $items = isset($_POST['items']) && is_array($_POST['items']) ? $_POST['items'] : array();
  if (count($items) < 1) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('status'=>'error','error_message'=>'Tidak ada item count entry yang dikirim.'));
    exit;
  }
  $saved = 0;
  $errors = array();
  foreach ($items as $idx => $item) {
    $docType = isset($item['doc_type']) ? $item['doc_type'] : '';
    $itemId = isset($item['item_id']) ? (int)$item['item_id'] : 0;
    $countedQty = isset($item['counted_qty']) ? $item['counted_qty'] : '';
    $remarks = isset($item['remarks']) ? $item['remarks'] : '';
    $result = ce_save_count_item($db, $docType, $itemId, $countedQty, $remarks);
    if ($result['status'] === 'good') $saved++;
    else $errors[] = 'Baris '.($idx + 1).': '.$result['error_message'];
  }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('status'=>count($errors) ? 'partial' : 'good','saved'=>$saved,'failed'=>count($errors),'errors'=>$errors,'message'=>$saved.' item berhasil disimpan'.(count($errors) ? ', '.count($errors).' gagal.' : '.')));
  exit;
}

if ($act === 'excel') {
  $initialOutputBufferLevel = ob_get_level(); ob_start(); ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php'; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $input = array('tgl_awal'=>ce_input('tgl_awal', date('Y-m-01')),'tgl_akhir'=>ce_input('tgl_akhir', date('Y-m-d')),'doc_type'=>ce_input('doc_type'),'doc_no'=>ce_input('doc_no'),'material_code'=>ce_input('material_code'),'plant_id'=>ce_input('plant_id'),'storage_location_id'=>ce_input('storage_location_id'),'storage_bin_id'=>ce_input('storage_bin_id'),'stock_type'=>ce_input('stock_type'),'item_status'=>ce_input('item_status'),'keyword'=>ce_input('keyword'));
  $rows = ce_load_rows($db, $input);
  $excel = new PHPExcel(); $sheet = $excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('Count Entry'));
  $headers = array(erp_export_label("No"),erp_export_label("Doc Type"),erp_export_label("Doc No"),erp_export_label("Count Date"),erp_export_label("Doc Status"),erp_export_label("Item Status"),erp_export_label("Line"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Plant"),erp_export_label("Storage Location"),erp_export_label("Storage Bin"),erp_export_label("Stock Type"),erp_export_label("System Qty"),erp_export_label("Counted Qty"),erp_export_label("Difference"),erp_export_label("UOM"),erp_export_label("Counted By"),erp_export_label("Counted At"),erp_export_label("Remarks"));
  foreach($headers as $c=>$h) $sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5; $n=1; $totSystem=0; $totCounted=0; $totDiff=0;
  foreach($rows as $row){ $diff = $row->difference_qty === null ? null : (float)$row->difference_qty; $totSystem+=(float)$row->system_qty; $totCounted+=($row->counted_qty===null?0:(float)$row->counted_qty); $totDiff+=($diff===null?0:$diff); $values=array($n++,ce_doc_type_label($row->doc_type),$row->doc_no,$row->count_date,$row->document_status,$row->item_status,(int)$row->line_no,$row->material_code,$row->material_name,$row->plant_code,$row->storage_code,$row->bin_code,ce_stock_type_label($row->stock_type),(float)$row->system_qty,$row->counted_qty===null?'':(float)$row->counted_qty,$diff===null?'':$diff,$row->uom,$row->counted_by,$row->counted_at,$row->remarks); foreach($values as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v); $r++; }
  $summaryRow=$r+1; $sheet->mergeCells('A'.$summaryRow.':M'.$summaryRow); $sheet->setCellValue('A'.$summaryRow, erp_export_label('TOTAL')); $sheet->setCellValue('N'.$summaryRow,$totSystem); $sheet->setCellValue('O'.$summaryRow,$totCounted); $sheet->setCellValue('P'.$summaryRow,$totDiff);
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('COUNT ENTRY - SAP PHYSICAL INVENTORY'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>20,'numeric_columns'=>array('N','O','P'),'filters'=>array('Periode'=>ce_valid_date($input['tgl_awal'], date('Y-m-01')).' s/d '.ce_valid_date($input['tgl_akhir'], date('Y-m-d')),'Doc Type'=>$input['doc_type'],'Doc No'=>$input['doc_no'],'Material'=>$input['material_code'],'Status'=>$input['item_status'],'Keyword'=>$input['keyword']),'widths'=>array('A'=>6,'B'=>16,'C'=>18,'D'=>14,'E'=>14,'F'=>14,'G'=>8,'H'=>16,'I'=>36,'J'=>12,'K'=>16,'L'=>14,'M'=>18,'N'=>14,'O'=>14,'P'=>14,'Q'=>10,'R'=>16,'S'=>20,'T'=>30)));
  $sheet->getStyle('A'.$summaryRow.':T'.$summaryRow)->getFont()->setBold(true); $sheet->getStyle('A'.$summaryRow.':T'.$summaryRow)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5'); $sheet->getStyle('A'.$summaryRow.':T'.$summaryRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN); $sheet->getStyle('N'.$summaryRow.':P'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00000');
  $tmp=erpkb_excel_temp_file('count_entry_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp); $size=@filesize($tmp); $signature=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$signature!=='PK'){@unlink($tmp);while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
  while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="count_entry_'.date('Ymd_His').'.xlsx"'); header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;
}

header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
