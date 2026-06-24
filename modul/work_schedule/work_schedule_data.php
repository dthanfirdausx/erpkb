<?php
if (!function_exists('hr_t')) {
  function hr_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('hr_h')) {
  function hr_h($key, $fallback = '') { return htmlspecialchars((string) hr_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('hr_js')) {
  function hr_js($key, $fallback = '') { return json_encode(hr_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
session_check_json();

function wsd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function wsd_t($key,$fallback=''){return lang_text($key,$fallback);}
function wsd_label($s){$map=array('DRAFT'=>'warning','ACTIVE'=>'success','INACTIVE'=>'default');$c=isset($map[$s])?$map[$s]:'default';return '<span class="label label-'.$c.'">'.wsd_h($s).'</span>';}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$from=isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01');
$to=isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:'9999-12-31';
$type=isset($_POST['schedule_type'])?trim($_POST['schedule_type']):'';
$cat=isset($_POST['schedule_category'])?trim($_POST['schedule_category']):'';
$dept=isset($_POST['department_code'])?trim($_POST['department_code']):'';
$status=isset($_POST['schedule_status'])?trim($_POST['schedule_status']):'';
$kw=isset($_POST['keyword'])?trim($_POST['keyword']):'';

$w=" WHERE ws.valid_from<=? AND ws.valid_to>=? ";
$p=array($to,$from);
if($type!==''){$w.=" AND ws.schedule_type=? ";$p[]=$type;}
if($cat!==''){$w.=" AND ws.schedule_category=? ";$p[]=$cat;}
if($dept!==''){$w.=" AND ws.department_code=? ";$p[]=$dept;}
if($status!==''){$w.=" AND ws.schedule_status=? ";$p[]=$status;}
if($kw!==''){$like='%'.$kw.'%';$w.=" AND (ws.schedule_code LIKE ? OR ws.schedule_name LIKE ? OR ws.sap_reference LIKE ? OR ws.remarks LIKE ? OR wl.location_code LIKE ? OR wl.location_name LIKE ? OR d.nm_dept LIKE ?) ";array_push($p,$like,$like,$like,$like,$like,$like,$like);}

$kpi=$db->fetch("SELECT COUNT(*) total,SUM(schedule_status='ACTIVE') active,SUM(schedule_type='SHIFT') shift_schedule,SUM(schedule_category='PRODUCTION') production,ROUND(AVG(working_hours_per_week),2) avg_weekly_hours FROM erp_work_schedule");
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_work_schedule ws LEFT JOIN erp_work_location wl ON wl.id=ws.work_location_id LEFT JOIN dept d ON d.kd_dept=ws.department_code $w",$p);
$total=$cnt?(int)$cnt->jml:0;

$orderMap=array(2=>'ws.schedule_code',3=>'ws.schedule_type',4=>'fc.calendar_code',5=>'wl.location_code',6=>'d.kd_dept',7=>'ws.monday',8=>'ws.working_hours_per_week',9=>'ws.schedule_status',10=>'ws.updated_at');
$orderBy='ws.schedule_category,ws.schedule_code';
if(isset($_POST['order'][0]['column'])){
  $col=(int)$_POST['order'][0]['column'];
  $dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';
  if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;
}

$rows=$db->query("SELECT ws.*,s.kode_shift,s.nama_shift,s.jam_mulai,s.jam_selesai,fc.calendar_name,fc.plant_code,wl.location_code,wl.location_name,wl.location_type,d.nm_dept,d.dept_type FROM erp_work_schedule ws LEFT JOIN erp_shift s ON s.id=ws.default_shift_id LEFT JOIN erp_factory_calendar_header fc ON fc.id=ws.calendar_id LEFT JOIN erp_work_location wl ON wl.id=ws.work_location_id LEFT JOIN dept d ON d.kd_dept=ws.department_code $w ORDER BY $orderBy LIMIT $start,$length",$p);

$data=array();$no=$start+1;
foreach($rows as $r){
  $next=$r->schedule_status==='ACTIVE'?'INACTIVE':'ACTIVE';
  $btn=$r->schedule_status==='ACTIVE'?'warning':'success';
  $icon=$r->schedule_status==='ACTIVE'?'fa-ban':'fa-check';
  $act='<div class="ws-action"><button class="btn btn-info btn-xs btn-ws-detail" data-id="'.(int)$r->id.'" title="'.wsd_h(wsd_t('common_detail','Detail')).'"><i class="fa fa-eye"></i></button> <button class="btn btn-primary btn-xs btn-ws-edit" data-id="'.(int)$r->id.'" title="'.wsd_h(wsd_t('edit','Edit')).'"><i class="fa fa-pencil"></i></button> <button class="btn btn-'.$btn.' btn-xs btn-ws-status" data-id="'.(int)$r->id.'" data-status="'.$next.'" title="'.$next.'"><i class="fa '.$icon.'"></i></button> <button class="btn btn-danger btn-xs btn-ws-delete" data-id="'.(int)$r->id.'" data-no="'.wsd_h($r->schedule_code).'" title="'.wsd_h(wsd_t('common_delete','Delete')).'"><i class="fa fa-trash"></i></button></div>';
  $days='<span class="label label-'.($r->monday==='Y'?'success':'default').'">M</span> <span class="label label-'.($r->tuesday==='Y'?'success':'default').'">T</span> <span class="label label-'.($r->wednesday==='Y'?'success':'default').'">W</span> <span class="label label-'.($r->thursday==='Y'?'success':'default').'">T</span> <span class="label label-'.($r->friday==='Y'?'success':'default').'">F</span> <span class="label label-'.($r->saturday==='Y'?'success':'default').'">S</span> <span class="label label-'.($r->sunday==='Y'?'success':'default').'">S</span>';
  $data[]=array(
    $no++,
    $act,
    '<strong>'.wsd_h($r->schedule_code).'</strong><br><small>'.wsd_h($r->schedule_name).'</small><br><small>'.wsd_h(wsd_t('work_schedule_sap_reference','SAP Reference')).': '.wsd_h($r->sap_reference?:'-').'</small>',
    '<strong>'.wsd_h($r->schedule_type).'</strong><br><small>'.wsd_h($r->schedule_category).'</small>',
    '<strong>'.wsd_h($r->calendar_code?:'-').'</strong><br><small>'.wsd_h($r->calendar_name?:'-').'</small><br><small>'.wsd_h(wsd_t('work_schedule_default_shift','Default Shift')).': '.wsd_h(($r->default_shift_code?:'-').' '.($r->nama_shift?:'')).'</small>',
    '<strong>'.wsd_h($r->location_code?:'-').'</strong><br><small>'.wsd_h($r->location_name?:'-').'</small><br><small>'.wsd_h($r->location_type?:'-').'</small>',
    '<strong>'.wsd_h($r->department_code?:'-').'</strong><br><small>'.wsd_h($r->nm_dept?:'-').'</small><br><small>'.wsd_h($r->employee_group).'</small>',
    $days,
    '<strong>'.wsd_h(($r->planned_start?substr($r->planned_start,0,5):'-').' - '.($r->planned_end?substr($r->planned_end,0,5):'-')).'</strong><br><small>'.wsd_h($r->working_hours_per_day).' '.wsd_h(wsd_t('work_schedule_hours_day','Hours / Day')).' | '.wsd_h($r->working_hours_per_week).' '.wsd_h(wsd_t('work_schedule_hours_week','Hours / Week')).'</small><br><small>'.wsd_h(wsd_t('work_schedule_grace_in','Grace In Min')).' '.wsd_h($r->grace_in_minutes).'/'.wsd_h($r->grace_out_minutes).'</small>',
    wsd_label($r->schedule_status).'<br><small>'.wsd_h(wsd_t('work_schedule_overtime_eligible','Overtime Eligible')).' '.wsd_h($r->overtime_eligible).' | '.wsd_h(wsd_t('work_schedule_attendance_required','Attendance Required')).' '.wsd_h($r->attendance_required).'</small>',
    wsd_h($r->updated_by?:$r->created_by?:'-').'<br><small>'.wsd_h($r->updated_at?:$r->created_at).'</small>'
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'kpi'=>array('total'=>$kpi?(int)$kpi->total:0,'active'=>$kpi?(int)$kpi->active:0,'shift_schedule'=>$kpi?(int)$kpi->shift_schedule:0,'production'=>$kpi?(int)$kpi->production:0,'avg_weekly_hours'=>$kpi?$kpi->avg_weekly_hours:0)));
?>
