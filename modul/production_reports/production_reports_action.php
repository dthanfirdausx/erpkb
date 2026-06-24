<?php
if (!function_exists('prod_t')) {
  function prod_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('prod_h')) {
  function prod_h($key, $fallback = '') { return htmlspecialchars((string) prod_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('prod_js')) {
  function prod_js($key, $fallback = '') { return json_encode(prod_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if(session_status()===PHP_SESSION_NONE)session_start();
include "../../inc/config.php";
session_check_json();
include "production_reports_lib.php";
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
$act=isset($_GET['act'])?$_GET['act']:'';
if($act!=='excel'){header('Content-Type: application/json');echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));exit;}
$initial=ob_get_level();ob_start();require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
$type=isset($_GET['report_type'])?$_GET['report_type']:'order_summary';$params=array();$title='PRODUCTION ORDER SUMMARY';$heads=array();$rows=array();
if($type==='material_consumption'){
  $title='PRODUCTION MATERIAL CONSUMPTION';$where=prp_filters($params,'p');$heads=array(erp_export_label("No"),erp_export_label("Production Order"),erp_export_label("Status"),erp_export_label("Start Date"),erp_export_label("Plant"),erp_export_label("Component"),erp_export_label("Required"),erp_export_label("Issued"),erp_export_label("Remaining"),erp_export_label("UOM"));
  $rows=$db->query("SELECT p.no_production_order,p.status,p.start_date,p.plant,m.material_code,m.material_name,m.required_qty,m.issued_qty,m.remaining_qty,m.uom FROM production_order_material m JOIN production_order p ON p.id_production_order=m.id_production_order $where ORDER BY p.start_date,p.no_production_order,m.material_code",$params);
} elseif($type==='output_gr'){
  $title='PRODUCTION OUTPUT GR REPORT';$params=array();$w=" WHERE 1=1 ";if(!empty($_GET['tgl_awal'])&&!empty($_GET['tgl_akhir'])){$w.=" AND h.posting_date BETWEEN ? AND ? ";$params[]=$_GET['tgl_awal'];$params[]=$_GET['tgl_akhir'];}if(!empty($_GET['plant_id'])){$w.=" AND h.plant_id=? ";$params[]=(int)$_GET['plant_id'];}
  $heads=array(erp_export_label("No"),erp_export_label("GR No"),erp_export_label("Posting Date"),erp_export_label("Production Order"),erp_export_label("Confirmation"),erp_export_label("Material"),erp_export_label("Qty"),erp_export_label("UOM"),erp_export_label("Plant/SLoc"),erp_export_label("Stock Type"),erp_export_label("Status"));
  $rows=$db->query("SELECT h.gr_no,h.posting_date,h.no_production_order,h.confirmation_no,d.material_code,d.material_name,d.qty,d.uom,ep.plant_code,es.storage_code,h.stock_type,h.status FROM erp_gr_production h JOIN erp_gr_production_detail d ON d.gr_id=h.id LEFT JOIN erp_plant ep ON ep.id=h.plant_id LEFT JOIN erp_storage_location es ON es.id=h.storage_location_id $w ORDER BY h.posting_date,h.id",$params);
} elseif($type==='traceability'){
  $title='PRODUCTION TRACEABILITY REPORT';$params=array();$w=" WHERE 1=1 ";if(!empty($_GET['tgl_awal'])&&!empty($_GET['tgl_akhir'])){$w.=" AND h.posting_date BETWEEN ? AND ? ";$params[]=$_GET['tgl_awal'];$params[]=$_GET['tgl_akhir'];}
  $heads=array(erp_export_label("No"),erp_export_label("GR No"),erp_export_label("Posting Date"),erp_export_label("Production Order"),erp_export_label("Output Material"),erp_export_label("Raw Material"),erp_export_label("Trace Qty"),erp_export_label("UOM"),erp_export_label("BC Document"),erp_export_label("Trace Source"));
  $rows=$db->query("SELECT h.gr_no,h.posting_date,h.no_production_order,d.material_code output_code,d.material_name output_name,tr.raw_material_code,tr.raw_material_name,tr.qty,tr.uom,tr.jenis_dokpab,tr.no_aju,tr.no_dokpab,tr.trace_source FROM erp_gr_production_trace tr JOIN erp_gr_production h ON h.id=tr.gr_id JOIN erp_gr_production_detail d ON d.id=tr.gr_detail_id $w ORDER BY h.posting_date,h.gr_no,tr.raw_material_code",$params);
} else {
  $where=prp_filters($params,'p');$heads=array(erp_export_label("No"),erp_export_label("Production Order"),erp_export_label("Status"),erp_export_label("Strategy"),erp_export_label("Plant"),erp_export_label("Material"),erp_export_label("Order Qty"),erp_export_label("Issued Material"),erp_export_label("Yield"),erp_export_label("GR Qty"),erp_export_label("Scrap"),erp_export_label("Yield %"),erp_export_label("Date"));
  $rows=$db->query(prp_order_sql($where)." ORDER BY start_date,no_production_order",$params);
}
$excel=new PHPExcel();$sh=$excel->setActiveSheetIndex(0);$sh->setTitle(substr(str_replace('PRODUCTION ','',$title),0,31));foreach($heads as $i=>$h)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);
$r=5;$n=1;foreach($rows as $row){if($type==='material_consumption')$vals=array($n++,$row->no_production_order,$row->status,$row->start_date,$row->plant,$row->material_code.' - '.$row->material_name,(float)$row->required_qty,(float)$row->issued_qty,(float)$row->remaining_qty,$row->uom);elseif($type==='output_gr')$vals=array($n++,$row->gr_no,$row->posting_date,$row->no_production_order,$row->confirmation_no,$row->material_code.' - '.$row->material_name,(float)$row->qty,$row->uom,trim($row->plant_code.' / '.$row->storage_code,' /'),$row->stock_type,$row->status);elseif($type==='traceability')$vals=array($n++,$row->gr_no,$row->posting_date,$row->no_production_order,$row->output_code.' - '.$row->output_name,$row->raw_material_code.' - '.$row->raw_material_name,(float)$row->qty,$row->uom,trim($row->jenis_dokpab.' '.$row->no_aju.' / '.$row->no_dokpab),$row->trace_source);else $vals=array($n++,$row->no_production_order,$row->status,$row->order_strategy,$row->plant,$row->material_code.' - '.$row->material_name,(float)$row->order_qty,(float)$row->issued_qty,(float)$row->yield_qty,(float)$row->gr_qty,(float)$row->scrap_qty,prp_pct($row->yield_qty,$row->order_qty),$row->start_date.' s/d '.$row->finish_date);foreach($vals as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r,$v);$r++;}
erpkb_excel_apply_standard_style($excel,array('sheet'=>$sh,'title'=>$title,'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>count($heads),'numeric_columns'=>array('G','H','I','J','K'),'decimal_columns'=>array('L'),'filters'=>array('Period'=>(isset($_GET['tgl_awal'])?$_GET['tgl_awal']:date('Y-m-01')).' s/d '.(isset($_GET['tgl_akhir'])?$_GET['tgl_akhir']:date('Y-m-d')),'Report Type'=>$type)));
$tmp=erpkb_excel_temp_file('production_reports_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="production_reports_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
?>
