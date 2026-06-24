<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "process_inspection_lib.php";

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

if ($act === 'material_search') {
  session_check_json();
  $term = pins_input('term');
  $params = array();
  $where = " WHERE 1=1 ";
  if ($term !== '') { $where .= " AND (material_code LIKE ? OR material_name LIKE ?) "; $params[]='%'.$term.'%'; $params[]='%'.$term.'%'; }
  $rows = $db->query("SELECT DISTINCT material_code,material_name,uom FROM production_order $where ORDER BY material_code LIMIT 30", $params);
  $results = array();
  foreach ($rows as $row) $results[] = array('id'=>$row->material_code,'text'=>$row->material_code.' - '.$row->material_name.' | '.$row->uom);
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('results'=>$results)); exit;
}

if ($act === 'create_lot') {
  session_check_json();
  $res = pins_create_lot_from_confirmation($db, (int)pins_input('confirmation_id',0), $username);
  if ($res['status'] === 'good') ilot_json('good','',array('id'=>$res['id'],'lot_no'=>$res['lot_no'],'existing'=>$res['existing']));
  ilot_json('error',$res['message']);
}

if ($act === 'source_detail') {
  session_check_json();
  $row = pins_candidate($db, (int)pins_input('confirmation_id',0));
  if (!$row) { echo '<div class="alert alert-warning">Production confirmation tidak ditemukan.</div>'; exit; }
  ?>
  <div class="alert alert-info"><strong>In-Process Source Detail.</strong> Data ini berasal dari production confirmation dan menjadi kandidat inspection lot tipe 03.</div>
  <div class="row">
    <div class="col-md-6"><table class="table table-bordered table-condensed"><tr><th style="width:180px;background:#f8fafc">Confirmation</th><td><?=ilot_h($row->confirmation_no ?: '-');?></td></tr><tr><th style="background:#f8fafc">Production Order</th><td><?=ilot_h($row->no_production_order);?></td></tr><tr><th style="background:#f8fafc">Posting Date</th><td><?=ilot_h($row->posting_date);?></td></tr><tr><th style="background:#f8fafc">Operation</th><td><?=ilot_h(trim((string)$row->operation_no.' / '.(string)$row->operation_name, ' /'));?></td></tr><tr><th style="background:#f8fafc">Work Center</th><td><?=ilot_h($row->work_center ?: '-');?></td></tr></table></div>
    <div class="col-md-6"><table class="table table-bordered table-condensed"><tr><th style="width:180px;background:#f8fafc">Material</th><td><strong><?=ilot_h($row->material_code);?></strong><br><?=ilot_h($row->material_name);?></td></tr><tr><th style="background:#f8fafc">Yield Qty</th><td class="text-right"><?=ilot_num($row->yield_qty).' '.ilot_h($row->uom);?></td></tr><tr><th style="background:#f8fafc">Scrap Qty</th><td class="text-right"><?=ilot_num($row->scrap_qty).' '.ilot_h($row->uom);?></td></tr><tr><th style="background:#f8fafc">Rework Qty</th><td class="text-right"><?=ilot_num($row->rework_qty).' '.ilot_h($row->uom);?></td></tr><tr><th style="background:#f8fafc">Operator / Shift</th><td><?=ilot_h(trim((string)$row->operator_name.' / '.(string)$row->shift_code, ' /') ?: '-');?></td></tr></table></div>
  </div>
  <?php if (!empty($row->inspection_lot_id)) { ?>
    <div class="alert alert-success">Inspection lot sudah dibuat: <strong><?=ilot_h($row->lot_no);?></strong> <?=pins_status_badge($row->lot_status);?></div>
  <?php } else { ?>
    <div class="alert alert-warning">Belum ada inspection lot. Klik tombol <strong>+ Lot</strong> pada worklist untuk membuat lot in-process.</div>
  <?php } ?>
  <?php
  exit;
}

if ($act === 'excel') {
  $initial = ob_get_level(); ob_start();
  ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php'; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $filters = pins_filters(); $rows = pins_candidates($db, $filters);
  $excel = new PHPExcel(); $sheet = $excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('In-Process Insp'));
  $heads = array(erp_export_label("No"),erp_export_label("Confirmation"),erp_export_label("Posting Date"),erp_export_label("Production Order"),erp_export_label("PO Status"),erp_export_label("Plant"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Operation"),erp_export_label("Work Center"),erp_export_label("Yield Qty"),erp_export_label("Scrap Qty"),erp_export_label("Rework Qty"),erp_export_label("UOM"),erp_export_label("Inspection Lot"),erp_export_label("Inspection Status"),erp_export_label("Result Count"),erp_export_label("Fail Count"),erp_export_label("UD"),erp_export_label("Operator"),erp_export_label("Shift"),erp_export_label("Remarks"));
  foreach($heads as $c=>$h) $sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5; $n=1;
  foreach($rows as $row){
    $vals=array($n++,$row->confirmation_no,$row->posting_date,$row->no_production_order,$row->po_status,$row->plant,$row->material_code,$row->material_name,$row->operation_no.' - '.$row->operation_name,$row->work_center,(float)$row->yield_qty,(float)$row->scrap_qty,(float)$row->rework_qty,$row->uom,$row->lot_no,$row->lot_status ?: 'PENDING_LOT',(int)$row->result_count,(int)$row->fail_count,$row->ud_text,$row->operator_name,$row->shift_code,$row->remarks);
    foreach($vals as $c=>$v) $sheet->setCellValueByColumnAndRow($c,$r,$v);
    $r++;
  }
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('IN-PROCESS INSPECTION - SAP QM'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>22,'numeric_columns'=>array('K','L','M'),'filters'=>array('Periode'=>$filters['tgl_awal'].' s/d '.$filters['tgl_akhir'],'Status'=>$filters['inspection_status'],'Plant'=>$filters['plant'],'Work Center'=>$filters['work_center'],'Material'=>$filters['material_code'],'Keyword'=>$filters['keyword'])));
  $tmp=erpkb_excel_temp_file('process_inspection_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp); $size=@filesize($tmp); $sig=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$sig!=='PK'){@unlink($tmp); while(ob_get_level()>$initial)ob_end_clean(); header('Content-Type:text/plain; charset=utf-8'); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit;}
  while(ob_get_level()>$initial)ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="process_inspection_'.$filters['tgl_awal'].'_sd_'.$filters['tgl_akhir'].'.xlsx"'); header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;
}

ilot_json('error','Action tidak dikenal.');
?>
