<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "stock_opname_lib.php";
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

function so_request_input() {
  return array('as_of_date'=>so_input('as_of_date', date('Y-m-d')),'material_code'=>so_input('material_code'),'plant_id'=>so_input('plant_id'),'storage_location_id'=>so_input('storage_location_id'),'storage_bin_id'=>so_input('storage_bin_id'),'stock_type'=>so_input('stock_type'),'doc_status'=>so_input('doc_status'),'keyword'=>so_input('keyword'));
}

if ($act === 'detail') {
  session_check_json();
  $input = so_request_input();
  $params = array();
  $where = so_filter_sql($input, $params);
  $rows = $db->query("SELECT sl.*,b.nm_barang,b.satuan,ep.plant_code,es.storage_code,eb.bin_code FROM stock_layer sl LEFT JOIN barang b ON b.kd_barang=sl.kode LEFT JOIN erp_plant ep ON ep.id=sl.plant_id LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id $where ORDER BY COALESCE(sl.tgl_masuk,DATE(sl.created_at)),sl.id", $params);
  $totalMasuk=0; $totalSisa=0; $rowCount=0;
  ?>
  <div class="alert alert-info"><strong>Stock Opname Layer Detail.</strong> Menampilkan layer/lot pembentuk saldo sistem, termasuk BPB, No Aju, dokumen BC, dan lokasi fisik.</div>
  <div class="table-responsive"><table class="table table-bordered table-condensed so-detail-table">
    <thead><tr class="bg-gray"><th>Layer / Batch</th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th>Receipt Date</th><th>No BPB</th><th>No Aju</th><th>Dokumen BC</th><th class="text-right">Qty Masuk</th><th class="text-right">Qty Sisa</th><th>Ref</th></tr></thead><tbody>
    <?php foreach($rows as $row){ $rowCount++; $totalMasuk+=(float)$row->qty_masuk; $totalSisa+=(float)$row->qty_sisa; $location=trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code,' /'); $bc=trim((string)$row->jenis_dokpab.' '.(string)$row->no_dokpab); ?>
      <tr><td><strong>Layer #<?=intval($row->id);?></strong><br><small>Trace key</small></td><td><strong><?=so_h($row->kode);?></strong><br><small><?=so_h($row->nm_barang);?></small></td><td><?=so_h($location ?: '-');?><br><small><?=so_h(so_stock_type_label($row->stock_type));?></small></td><td><?=so_h($row->tgl_masuk ?: substr((string)$row->created_at,0,10));?></td><td><?=so_h($row->no_bpb);?></td><td><?=so_h($row->no_aju);?></td><td><?=so_h($bc);?></td><td class="text-right"><?=number_format((float)$row->qty_masuk,5,',','.');?></td><td class="text-right"><strong><?=number_format((float)$row->qty_sisa,5,',','.');?></strong></td><td><small><?=so_h(trim((string)$row->ref_table.' #'.(string)$row->ref_id,' #'));?></small></td></tr>
    <?php } ?>
    <?php if($rowCount===0){ ?><tr><td colspan="10" class="text-center text-muted">Tidak ada layer untuk filter ini.</td></tr><?php } ?>
    </tbody><tfoot><tr class="bg-gray"><th colspan="7" class="text-right">Total</th><th class="text-right"><?=number_format($totalMasuk,5,',','.');?></th><th class="text-right"><?=number_format($totalSisa,5,',','.');?></th><th></th></tr></tfoot>
  </table></div>
  <?php exit;
}

if ($act === 'create_doc') {
  session_check_json();
  $input = so_request_input();
  $input['doc_status'] = 'NO_OPEN';
  $groups = so_load_groups($db, $input);
  if (count($groups) < 1) {
    $checkInput = $input;
    $checkInput['doc_status'] = 'OPEN';
    $openGroups = so_load_groups($db, $checkInput);
    $openDocs = array();
    foreach ($openGroups as $openRow) {
      if (!empty($openRow->open_doc_no)) $openDocs[$openRow->open_doc_no] = true;
      if (count($openDocs) >= 5) break;
    }
    $message = 'Tidak ada saldo stok tanpa dokumen open untuk dibuat stock opname.';
    if (!empty($openDocs)) $message .= ' Selesaikan atau cancel dokumen open terlebih dahulu: '.implode(', ', array_keys($openDocs)).'.';
    header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('status'=>'error','error_message'=>$message)); exit;
  }
  $docNo = so_next_doc_no($db);
  $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
  $first = $groups[0];
  $ok = $db->insert('stock_opname_documents', array('doc_no'=>$docNo,'opname_date'=>so_valid_date($input['as_of_date'], date('Y-m-d')),'status'=>'OPEN','plant_id'=>$input['plant_id'] !== '' ? $input['plant_id'] : $first->plant_id,'storage_location_id'=>$input['storage_location_id'] !== '' ? $input['storage_location_id'] : null,'storage_bin_id'=>$input['storage_bin_id'] !== '' ? $input['storage_bin_id'] : null,'stock_type'=>$input['stock_type'],'created_by'=>$username,'remarks'=>'Stock opname document created from ERPKB workbench'));
  if (!$ok) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('status'=>'error','error_message'=>$db->getErrorMessage())); exit; }
  $docId = $db->last_insert_id(); $line=1; $inserted=0;
  foreach ($groups as $row) {
    if ($db->insert('stock_opname_document_items', array('document_id'=>$docId,'line_no'=>$line++,'material_code'=>$row->material_code,'material_name'=>$row->nm_barang,'plant_id'=>$row->plant_id,'storage_location_id'=>$row->storage_location_id,'storage_bin_id'=>$row->storage_bin_id,'stock_type'=>$row->stock_type,'system_qty'=>$row->system_qty,'uom'=>$row->satuan,'layer_count'=>$row->layer_count,'customs_doc_count'=>$row->customs_doc_count,'status'=>'OPEN'))) $inserted++;
  }
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('status'=>'good','doc_no'=>$docNo,'message'=>'Stock opname document '.$docNo.' berhasil dibuat dengan '.$inserted.' item.')); exit;
}

if ($act === 'excel') {
  $initialOutputBufferLevel = ob_get_level(); ob_start(); ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php'; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $input = so_request_input(); $rows = so_load_groups($db, $input);
  $excel = new PHPExcel(); $sheet = $excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('Stock Opname'));
  $headers = array(erp_export_label("No"),erp_export_label("Open Doc"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Plant"),erp_export_label("Storage Location"),erp_export_label("Storage Bin"),erp_export_label("Stock Type"),erp_export_label("Last Opname"),erp_export_label("Last Doc"),erp_export_label("Oldest Receipt"),erp_export_label("Layers"),erp_export_label("Customs Docs"),erp_export_label("System Qty"),erp_export_label("UOM"));
  foreach($headers as $c=>$h) $sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5; $n=1; $totalQty=0;
  foreach($rows as $row){ $totalQty+=(float)$row->system_qty; $values=array($n++,$row->open_doc_no,$row->material_code,$row->nm_barang,$row->plant_code,$row->storage_code,$row->bin_code,so_stock_type_label($row->stock_type),$row->last_count_date,$row->last_doc_no,$row->oldest_receipt,(int)$row->layer_count,(int)$row->customs_doc_count,(float)$row->system_qty,$row->satuan); foreach($values as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v); $r++; }
  $summaryRow=$r+1; $sheet->mergeCells('A'.$summaryRow.':M'.$summaryRow); $sheet->setCellValue('A'.$summaryRow, erp_export_label('TOTAL')); $sheet->setCellValue('N'.$summaryRow,$totalQty);
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('STOCK OPNAME - SAP PHYSICAL INVENTORY'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>15,'numeric_columns'=>array('N'),'filters'=>array('As Of Date'=>so_valid_date($input['as_of_date'], date('Y-m-d')),'Material'=>$input['material_code'],'Stock Type'=>$input['stock_type'],'Doc Status'=>$input['doc_status'],'Keyword'=>$input['keyword']),'widths'=>array('A'=>6,'B'=>18,'C'=>16,'D'=>36,'E'=>12,'F'=>16,'G'=>14,'H'=>18,'I'=>14,'J'=>18,'K'=>14,'L'=>10,'M'=>12,'N'=>14,'O'=>10)));
  $sheet->getStyle('A'.$summaryRow.':O'.$summaryRow)->getFont()->setBold(true); $sheet->getStyle('A'.$summaryRow.':O'.$summaryRow)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5'); $sheet->getStyle('A'.$summaryRow.':O'.$summaryRow)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN); $sheet->getStyle('N'.$summaryRow)->getNumberFormat()->setFormatCode('#,##0.00000');
  $tmp=erpkb_excel_temp_file('stock_opname_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp); $size=@filesize($tmp); $signature=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$signature!=='PK'){@unlink($tmp);while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
  while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="stock_opname_'.date('Ymd_His').'.xlsx"'); header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;
}

header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
