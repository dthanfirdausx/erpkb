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
function wcd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function wcd_num($v,$d=2){return number_format((float)$v,$d,',','.');}
function wcd_load($pct){$cls=$pct>=90?'danger':($pct>=70?'warning':'success');return '<div class="progress progress-xs" style="margin-bottom:4px"><div class="progress-bar progress-bar-'.$cls.'" style="width:'.min(100,$pct).'%"></div></div><small>'.$pct.'%</small>';}
$req=$_REQUEST;$draw=isset($req['draw'])?(int)$req['draw']:0;$start=isset($req['start'])?max(0,(int)$req['start']):0;$length=isset($req['length'])?(int)$req['length']:25;if($length<=0)$length=25;
$from=!empty($_POST['tgl_awal'])?$_POST['tgl_awal']:date('Y-m-d');$to=!empty($_POST['tgl_akhir'])?$_POST['tgl_akhir']:date('Y-m-d');$plant=isset($_POST['plant'])?trim($_POST['plant']):'';$status=isset($_POST['operation_status'])?trim($_POST['operation_status']):'';$keyword=isset($_POST['keyword'])?trim($_POST['keyword']):'';
$p=array($from.' 00:00:00',$to.' 23:59:59');
$w=" WHERE q.work_center<>'' AND q.planned_start BETWEEN ? AND ? ";
if($plant!==''){$w.=" AND q.plant_code=? ";$p[]=$plant;}
if($status!==''){$w.=" AND q.operation_status=? ";$p[]=$status;}
if($keyword!==''){$kw='%'.$keyword.'%';$w.=" AND (q.work_center LIKE ? OR q.work_center_name LIKE ? OR q.no_production_order LIKE ? OR q.material_code LIKE ? OR q.material_name LIKE ?) ";for($i=0;$i<5;$i++)$p[]=$kw;}
$union="SELECT d.work_center,COALESCE(d.work_center_name,d.work_center) work_center_name,d.operation_status,d.duration_minutes,d.scheduled_qty,d.planned_start,d.planned_finish,d.shift_code,h.no_production_order,h.plant_code,h.material_code,h.material_name,h.priority,h.dispatch_status
        FROM erp_production_schedule_detail d JOIN erp_production_schedule h ON h.id=d.schedule_id
        WHERE h.dispatch_status<>'CANCELLED'
        UNION ALL
        SELECT o.work_center,o.work_center AS work_center_name,o.status AS operation_status,(COALESCE(o.setup_time,0)+COALESCE(o.machine_time,0)+COALESCE(o.labor_time,0)) duration_minutes,p.order_qty AS scheduled_qty,CONCAT(p.start_date,' 08:00:00') planned_start,CONCAT(p.finish_date,' 17:00:00') planned_finish,NULL shift_code,p.no_production_order,p.plant AS plant_code,p.material_code,p.material_name,p.priority,p.status AS dispatch_status
        FROM production_order_operation o JOIN production_order p ON p.id_production_order=o.id_production_order
        WHERE p.status IN ('RELEASED','IN_PROCESS') AND NOT EXISTS (SELECT 1 FROM erp_production_schedule_detail sd WHERE sd.id_operation=o.id_operation)";
$base=" FROM ($union) q ".$w;
$cnt=$db->fetch("SELECT COUNT(DISTINCT q.work_center) jml ".$base,$p);$total=$cnt?(int)$cnt->jml:0;
$rows=$db->query("SELECT q.work_center,MAX(q.work_center_name) work_center_name,COUNT(*) queue_count,SUM(q.operation_status IN ('READY','OPEN')) ready_count,SUM(q.operation_status IN ('DISPATCHED','STARTED')) active_count,SUM(q.operation_status IN ('FINISHED')) finished_count,SUM(q.duration_minutes) total_minutes,SUM(q.scheduled_qty) scheduled_qty,MIN(q.planned_start) next_start,MAX(q.planned_finish) last_finish,GROUP_CONCAT(DISTINCT q.shift_code ORDER BY q.shift_code SEPARATOR ', ') shifts ".$base." GROUP BY q.work_center ORDER BY active_count DESC,ready_count DESC,q.work_center LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $capacity=8*60; $pct=$capacity>0?round(((float)$r->total_minutes/$capacity)*100,1):0;
  $act='<button class="btn btn-info btn-xs btn-wcd-detail" data-wc="'.wcd_h($r->work_center).'" title="Queue"><i class="fa fa-list"></i></button>';
  $wc='<strong>'.wcd_h($r->work_center).'</strong><br><small>'.wcd_h($r->work_center_name).'</small>';
  $queue='<span class="badge bg-aqua">'.(int)$r->queue_count.' ops</span><br><small>Ready '.(int)$r->ready_count.' | Active '.(int)$r->active_count.' | Finish '.(int)$r->finished_count.'</small>';
  $plan='Next '.wcd_h($r->next_start?:'-').'<br><small>Last '.wcd_h($r->last_finish?:'-').'</small>';
  $data[]=array($no++,$act,$wc,$queue,wcd_num($r->scheduled_qty,5),wcd_num($r->total_minutes,0).' min',wcd_load($pct),wcd_h($r->shifts?:'-'),$plan);
}
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
