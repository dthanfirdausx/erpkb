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
function oqa_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function oqa_num($v,$d=5){return number_format((float)$v,$d,',','.');}
function oqa_pct($a,$b){$b=(float)$b;return $b>0?max(0,min(100,round(((float)$a/$b)*100,1))):0;}
function oqa_base(){
  return "SELECT x.*,COALESCE(mat.material_progress,100) material_progress,COALESCE(conf.yield_qty,0) yield_qty,COALESCE(conf.scrap_qty,0) scrap_qty,COALESCE(dt.downtime_count,0) downtime_count,COALESCE(dt.downtime_minutes,0) downtime_minutes
          FROM (
            SELECT d.id queue_id,d.id_operation,h.id_production_order,h.no_production_order,h.plant_code,p.storage_location,h.material_code,h.material_name,h.order_qty,h.uom,h.priority,p.status po_status,d.operation_no,d.operation_name,d.work_center,d.work_center_name,d.shift_code,d.planned_start,d.planned_finish,d.duration_minutes,d.scheduled_qty,d.operation_status,h.dispatch_status,h.schedule_no,'SCHEDULE' source_type
            FROM erp_production_schedule_detail d JOIN erp_production_schedule h ON h.id=d.schedule_id JOIN production_order p ON p.id_production_order=h.id_production_order
            WHERE h.dispatch_status<>'CANCELLED' AND d.operation_status<>'CANCELLED'
            UNION ALL
            SELECT o.id_operation,o.id_operation,p.id_production_order,p.no_production_order,p.plant,p.storage_location,p.material_code,p.material_name,p.order_qty,p.uom,p.priority,p.status,o.operation_no,o.operation_name,o.work_center,o.work_center,NULL,CONCAT(p.start_date,' 08:00:00'),CONCAT(p.finish_date,' 17:00:00'),(COALESCE(o.setup_time,0)+COALESCE(o.machine_time,0)+COALESCE(o.labor_time,0)),p.order_qty,o.status,p.status,NULL,'ROUTING' source_type
            FROM production_order_operation o JOIN production_order p ON p.id_production_order=o.id_production_order
            WHERE p.status IN ('RELEASED','IN_PROCESS','CONFIRMED') AND NOT EXISTS (SELECT 1 FROM erp_production_schedule_detail sd WHERE sd.id_operation=o.id_operation AND sd.operation_status<>'CANCELLED')
          ) x
          LEFT JOIN (SELECT id_production_order,CASE WHEN SUM(required_qty)>0 THEN SUM(issued_qty)/SUM(required_qty)*100 ELSE 100 END material_progress FROM production_order_material GROUP BY id_production_order) mat ON mat.id_production_order=x.id_production_order
          LEFT JOIN (SELECT id_production_order,operation_no,SUM(CASE WHEN status='POSTED' THEN yield_qty ELSE 0 END) yield_qty,SUM(CASE WHEN status='POSTED' THEN scrap_qty ELSE 0 END) scrap_qty FROM production_order_confirmation GROUP BY id_production_order,operation_no) conf ON conf.id_production_order=x.id_production_order AND conf.operation_no=x.operation_no
          LEFT JOIN (SELECT id_production_order,operation_no,COUNT(*) downtime_count,SUM(CASE WHEN approval_status='POSTED' THEN duration_minutes ELSE 0 END) downtime_minutes FROM erp_production_downtime GROUP BY id_production_order,operation_no) dt ON dt.id_production_order=x.id_production_order AND dt.operation_no=x.operation_no";
}
$act=isset($_GET['act'])?$_GET['act']:'';
switch($act){
  case 'detail':
    $id=isset($_POST['id'])?(int)$_POST['id']:0;$source=isset($_POST['source_type'])?trim($_POST['source_type']):'ROUTING';
    $q=$db->fetch("SELECT q.* FROM (".oqa_base().") q WHERE q.queue_id=? AND q.source_type=? LIMIT 1",array($id,$source));if(!$q){echo '<div class="alert alert-warning">Operation tidak ditemukan.</div>';break;}
    $mats=$db->query("SELECT * FROM production_order_material WHERE id_production_order=? ORDER BY id_material",array($q->id_production_order));
    $confs=$db->query("SELECT * FROM production_order_confirmation WHERE id_production_order=? AND operation_no=? ORDER BY posting_date,id_confirmation",array($q->id_production_order,$q->operation_no));
    $dts=$db->query("SELECT * FROM erp_production_downtime WHERE id_production_order=? AND operation_no=? ORDER BY downtime_date,start_time",array($q->id_production_order,$q->operation_no));
    $acts=$db->query("SELECT * FROM erp_production_activity_log WHERE id_production_order=? AND operation_no=? ORDER BY activity_time DESC LIMIT 20",array($q->id_production_order,$q->operation_no));
    echo '<h3 style="margin-top:0">'.oqa_h($q->operation_no.' - '.$q->operation_name).' <small>'.oqa_h($q->operation_status.' / '.$q->source_type).'</small></h3><div class="row"><div class="col-sm-3"><strong>Production Order</strong><br>'.oqa_h($q->no_production_order).'<br><small>'.oqa_h($q->po_status.' / '.$q->priority).'</small></div><div class="col-sm-3"><strong>Material</strong><br>'.oqa_h($q->material_code).'<br><small>'.oqa_h($q->material_name).'</small></div><div class="col-sm-2"><strong>Work Center</strong><br>'.oqa_h($q->work_center).'<br><small>'.oqa_h($q->shift_code?:'-').'</small></div><div class="col-sm-2"><strong>Plan</strong><br>'.oqa_h($q->planned_start).'<br><small>'.oqa_h($q->planned_finish).'</small></div><div class="col-sm-2"><strong>Qty</strong><br>'.oqa_num($q->scheduled_qty?:$q->order_qty).' '.oqa_h($q->uom).'</div></div><hr>';
    echo '<h4>Material Readiness</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Component</th><th class="text-right">Required</th><th class="text-right">Issued</th><th class="text-right">Remaining</th><th>UOM</th><th>Status</th></tr></thead><tbody>';
    foreach($mats as $m){$pct=oqa_pct($m->issued_qty,$m->required_qty);$cls=$pct>=100?'success':'danger';echo '<tr><td><strong>'.oqa_h($m->material_code).'</strong><br><small>'.oqa_h($m->material_name).'</small></td><td class="text-right">'.oqa_num($m->required_qty).'</td><td class="text-right">'.oqa_num($m->issued_qty).'</td><td class="text-right">'.oqa_num($m->remaining_qty).'</td><td>'.oqa_h($m->uom).'</td><td><span class="label label-'.$cls.'">'.$pct.'%</span></td></tr>';}
    echo '</tbody></table><div class="row"><div class="col-md-6"><h4>Confirmations</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Confirmation</th><th>Operator</th><th class="text-right">Yield</th><th class="text-right">Scrap</th><th>Status</th></tr></thead><tbody>';
    foreach($confs as $c)echo '<tr><td>'.oqa_h($c->confirmation_no).'<br><small>'.oqa_h($c->posting_date).'</small></td><td>'.oqa_h($c->operator_name).'</td><td class="text-right">'.oqa_num($c->yield_qty).'</td><td class="text-right">'.oqa_num($c->scrap_qty).'</td><td>'.oqa_h($c->status).'</td></tr>';
    echo '</tbody></table></div><div class="col-md-6"><h4>Downtime</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>No</th><th>Reason</th><th class="text-right">Min</th><th>Status</th></tr></thead><tbody>';
    foreach($dts as $d)echo '<tr><td>'.oqa_h($d->downtime_no).'<br><small>'.oqa_h($d->start_time).'</small></td><td>'.oqa_h($d->downtime_category.' - '.$d->reason_text).'</td><td class="text-right">'.oqa_num($d->duration_minutes,0).'</td><td>'.oqa_h($d->approval_status).'</td></tr>';
    echo '</tbody></table></div></div><h4>Activity Log</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Time</th><th>Type</th><th>Activity</th><th>User</th></tr></thead><tbody>';
    foreach($acts as $a)echo '<tr><td>'.oqa_h($a->activity_time).'</td><td>'.oqa_h($a->activity_type.' / '.$a->severity).'</td><td>'.oqa_h($a->activity_text).'</td><td>'.oqa_h($a->created_by).'</td></tr>';
    echo '</tbody></table>';break;
  case 'excel':
    $initial=ob_get_level();ob_start();require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);$from=isset($_GET['tgl_awal'])?$_GET['tgl_awal']:date('Y-m-d');$to=isset($_GET['tgl_akhir'])?$_GET['tgl_akhir']:date('Y-m-d');$p=array($from.' 00:00:00',$to.' 23:59:59');$w=" WHERE q.planned_start BETWEEN ? AND ? ";if(!empty($_GET['plant'])){$w.=" AND q.plant_code=? ";$p[]=$_GET['plant'];}if(!empty($_GET['work_center'])){$w.=" AND q.work_center=? ";$p[]=$_GET['work_center'];}if(!empty($_GET['operation_status'])){$w.=" AND q.operation_status=? ";$p[]=$_GET['operation_status'];}
    $rows=$db->query("SELECT q.* FROM (".oqa_base().") q $w ORDER BY q.planned_start,q.work_center,q.no_production_order,q.operation_no",$p);$excel=new PHPExcel();$sh=$excel->setActiveSheetIndex(0);$sh->setTitle(erp_export_sheet_title('Operation Queue'));$heads=array(erp_export_label("No"),erp_export_label("Source"),erp_export_label("Production Order"),erp_export_label("PO Status"),erp_export_label("Priority"),erp_export_label("Plant"),erp_export_label("Material"),erp_export_label("Operation"),erp_export_label("Work Center"),erp_export_label("Shift"),erp_export_label("Planned Start"),erp_export_label("Planned Finish"),erp_export_label("Plan Qty"),erp_export_label("UOM"),erp_export_label("Material %"),erp_export_label("Yield"),erp_export_label("Scrap"),erp_export_label("Downtime Min"),erp_export_label("Operation Status"));foreach($heads as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$v);$r=5;$n=1;foreach($rows as $row){$vals=array($n++,$row->source_type,$row->no_production_order,$row->po_status,$row->priority,$row->plant_code,$row->material_code.' - '.$row->material_name,$row->operation_no.' - '.$row->operation_name,$row->work_center,$row->shift_code,$row->planned_start,$row->planned_finish,(float)($row->scheduled_qty?:$row->order_qty),$row->uom,(float)$row->material_progress,(float)$row->yield_qty,(float)$row->scrap_qty,(float)$row->downtime_minutes,$row->operation_status);foreach($vals as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r,$v);$r++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sh,'title'=>erp_export_title('OPERATION QUEUE'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>19,'numeric_columns'=>array('M','O','P','Q','R'),'filters'=>array('Plan Date'=>$from.' s/d '.$to,'Plant'=>isset($_GET['plant'])?$_GET['plant']:erp_export_all_text(),'Work Center'=>isset($_GET['work_center'])?$_GET['work_center']:erp_export_all_text())));$tmp=erpkb_excel_temp_file('operation_queue_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="operation_queue_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:header('Content-Type: application/json');echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
}
?>
