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
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
function wca_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function wca_num($v,$d=5){return number_format((float)$v,$d,',','.');}
function wca_union(){
  return "SELECT d.work_center,COALESCE(d.work_center_name,d.work_center) work_center_name,d.operation_no,d.operation_name,d.operation_status,d.duration_minutes,d.scheduled_qty,d.planned_start,d.planned_finish,d.shift_code,h.schedule_no,h.no_production_order,h.plant_code,h.material_code,h.material_name,h.priority,h.dispatch_status
          FROM erp_production_schedule_detail d JOIN erp_production_schedule h ON h.id=d.schedule_id
          WHERE h.dispatch_status<>'CANCELLED'
          UNION ALL
          SELECT o.work_center,o.work_center,o.operation_no,o.operation_name,o.status,(COALESCE(o.setup_time,0)+COALESCE(o.machine_time,0)+COALESCE(o.labor_time,0)),p.order_qty,CONCAT(p.start_date,' 08:00:00'),CONCAT(p.finish_date,' 17:00:00'),NULL,NULL,p.no_production_order,p.plant,p.material_code,p.material_name,p.priority,p.status
          FROM production_order_operation o JOIN production_order p ON p.id_production_order=o.id_production_order
          WHERE p.status IN ('RELEASED','IN_PROCESS') AND NOT EXISTS (SELECT 1 FROM erp_production_schedule_detail sd WHERE sd.id_operation=o.id_operation)";
}
$act=isset($_GET['act'])?$_GET['act']:'';
switch($act){
  case 'detail':
    $wc=isset($_POST['work_center'])?trim($_POST['work_center']):'';$from=!empty($_POST['tgl_awal'])?$_POST['tgl_awal']:date('Y-m-d');$to=!empty($_POST['tgl_akhir'])?$_POST['tgl_akhir']:date('Y-m-d');
    if($wc===''){echo '<div class="alert alert-warning">Work center tidak valid.</div>';break;}
    $rows=$db->query("SELECT q.* FROM (".wca_union().") q WHERE q.work_center=? AND q.planned_start BETWEEN ? AND ? ORDER BY q.planned_start,q.operation_no",array($wc,$from.' 00:00:00',$to.' 23:59:59'));
    echo '<h3 style="margin-top:0">Work Center '.wca_h($wc).'</h3><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Plan</th><th>Production Order</th><th>Material</th><th>Operation</th><th>Shift</th><th class="text-right">Qty</th><th class="text-right">Min</th><th>Status</th></tr></thead><tbody>';
    foreach($rows as $r)echo '<tr><td>'.wca_h($r->planned_start).'<br><small>'.wca_h($r->planned_finish).'</small></td><td><strong>'.wca_h($r->no_production_order).'</strong><br><small>'.wca_h($r->schedule_no?:'-').' / '.wca_h($r->priority).'</small></td><td><strong>'.wca_h($r->material_code).'</strong><br><small>'.wca_h($r->material_name).'</small></td><td><strong>'.wca_h($r->operation_no).'</strong><br><small>'.wca_h($r->operation_name).'</small></td><td>'.wca_h($r->shift_code?:'-').'</td><td class="text-right">'.wca_num($r->scheduled_qty).'</td><td class="text-right">'.wca_num($r->duration_minutes,0).'</td><td>'.wca_h($r->operation_status).'</td></tr>';
    echo '</tbody></table></div>';break;
  case 'excel':
    $initial=ob_get_level();ob_start();require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $from=isset($_GET['tgl_awal'])?$_GET['tgl_awal']:date('Y-m-d');$to=isset($_GET['tgl_akhir'])?$_GET['tgl_akhir']:date('Y-m-d');$p=array($from.' 00:00:00',$to.' 23:59:59');$w=" WHERE q.planned_start BETWEEN ? AND ? ";if(!empty($_GET['plant'])){$w.=" AND q.plant_code=? ";$p[]=$_GET['plant'];}
    $rows=$db->query("SELECT q.* FROM (".wca_union().") q $w ORDER BY q.work_center,q.planned_start,q.operation_no",$p);
    $excel=new PHPExcel();$sh=$excel->setActiveSheetIndex(0);$sh->setTitle(erp_export_sheet_title('WC Dashboard'));$heads=array(erp_export_label("No"),erp_export_label("Work Center"),erp_export_label("Work Center Name"),erp_export_label("Planned Start"),erp_export_label("Planned Finish"),erp_export_label("Production Order"),erp_export_label("Material"),erp_export_label("Operation"),erp_export_label("Shift"),erp_export_label("Qty"),erp_export_label("Duration Min"),erp_export_label("Operation Status"),erp_export_label("Schedule Status"));foreach($heads as $i=>$h)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);$r=5;$n=1;foreach($rows as $row){$vals=array($n++,$row->work_center,$row->work_center_name,$row->planned_start,$row->planned_finish,$row->no_production_order,$row->material_code.' - '.$row->material_name,$row->operation_no.' - '.$row->operation_name,$row->shift_code,(float)$row->scheduled_qty,(float)$row->duration_minutes,$row->operation_status,$row->dispatch_status);foreach($vals as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r,$v);$r++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sh,'title'=>erp_export_title('WORK CENTER DASHBOARD'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>13,'numeric_columns'=>array('J'),'decimal_columns'=>array('K'),'filters'=>array('Plan Date'=>$from.' s/d '.$to,'Plant'=>isset($_GET['plant'])?$_GET['plant']:erp_export_all_text())));$tmp=erpkb_excel_temp_file('work_center_dashboard_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="work_center_dashboard_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:header('Content-Type: application/json');echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
}
?>
