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

function trd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function trd_label($s){
  $map=array('REGISTERED'=>'success','WAITLIST'=>'warning','CANCELLED'=>'danger','ATTENDED'=>'info','NO_SHOW'=>'danger','COMPLETED'=>'primary','DRAFT'=>'default','SUBMITTED'=>'info','APPROVED'=>'success','REJECTED'=>'danger','NOT_MARKED'=>'default','PRESENT'=>'success','ABSENT'=>'danger','PARTIAL'=>'warning');
  $c=isset($map[$s])?$map[$s]:'default';
  return '<span class="label label-'.$c.'">'.trd_h($s).'</span>';
}
function trd_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-12-31'),
    'training_plan_id'=>isset($_POST['training_plan_id'])?trim($_POST['training_plan_id']):'',
    'department_code'=>isset($_POST['department_code'])?trim($_POST['department_code']):'',
    'registration_status'=>isset($_POST['registration_status'])?trim($_POST['registration_status']):'',
    'approval_status'=>isset($_POST['approval_status'])?trim($_POST['approval_status']):'',
    'attendance_status'=>isset($_POST['attendance_status'])?trim($_POST['attendance_status']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function trd_where($f,&$p){
  $w=" WHERE tr.registration_date BETWEEN ? AND ? ";
  $p[]=$f['from'];$p[]=$f['to'];
  foreach(array('training_plan_id','registration_status','approval_status','attendance_status') as $k){
    if($f[$k]!==''){$w.=" AND tr.$k=? ";$p[]=$f[$k];}
  }
  if($f['department_code']!==''){$w.=" AND e.department_code=? ";$p[]=$f['department_code'];}
  if($f['keyword']!==''){
    $kw='%'.$f['keyword'].'%';
    $w.=" AND (tr.registration_no LIKE ? OR tp.plan_code LIKE ? OR tp.plan_name LIKE ? OR tc.training_code LIKE ? OR tc.training_name LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ?) ";
    for($i=0;$i<7;$i++)$p[]=$kw;
  }
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$f=trd_filters();$p=array();$w=trd_where($f,$p);
$join=" FROM erp_training_registration tr JOIN erp_training_plan tp ON tp.id=tr.training_plan_id JOIN erp_training_catalog tc ON tc.id=tp.training_catalog_id JOIN erp_employee_master e ON e.id=tr.employee_id LEFT JOIN dept d ON d.kd_dept=e.department_code LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id ";

$kpi=$db->fetch("SELECT COUNT(*) total,SUM(CASE WHEN tr.registration_status='REGISTERED' THEN 1 ELSE 0 END) registered,SUM(CASE WHEN tr.registration_status='WAITLIST' THEN 1 ELSE 0 END) waitlist,SUM(CASE WHEN tr.attendance_status='PRESENT' THEN 1 ELSE 0 END) present,SUM(CASE WHEN tr.registration_status='COMPLETED' THEN 1 ELSE 0 END) completed,ROUND(AVG(NULLIF(tr.score,0)),2) avg_score $join");
$cnt=$db->fetch("SELECT COUNT(*) jml $join $w",$p);
$total=$cnt?(int)$cnt->jml:0;

$orderMap=array(2=>'tr.registration_no',3=>'tp.plan_code',4=>'e.employee_no',5=>'e.department_code',6=>'tr.registration_date',7=>'tr.registration_status',8=>'tr.attendance_status',9=>'tr.score',10=>'tr.updated_at');
$orderBy="tr.registration_date DESC,tr.registration_no DESC";
if(isset($_POST['order'][0]['column'])){
  $col=(int)$_POST['order'][0]['column'];
  $dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';
  if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;
}

$rows=$db->query("SELECT tr.*,tp.plan_code,tp.plan_name,tp.planned_start_date,tp.planned_end_date,tc.training_code,tc.training_name,e.employee_no,e.full_name,e.department_code,d.nm_dept,jt.job_title_code,jt.job_title_name $join $w ORDER BY $orderBy LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<div class="tr-action"><button class="btn btn-info btn-xs btn-tr-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> ';
  $act.='<button class="btn btn-primary btn-xs btn-tr-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> ';
  $act.='<button class="btn btn-success btn-xs btn-tr-attend" data-id="'.(int)$r->id.'" title="Mark Present"><i class="fa fa-check"></i></button> ';
  $act.='<button class="btn btn-danger btn-xs btn-tr-delete" data-id="'.(int)$r->id.'" data-no="'.trd_h($r->registration_no).'" title="'.hr_h('common_delete', 'Delete').'"><i class="fa fa-trash"></i></button></div>';
  $employee='<strong>'.trd_h($r->employee_no).'</strong><br><small>'.trd_h($r->full_name).'</small>';
  $dept='<strong>'.trd_h($r->department_code).'</strong> - '.trd_h($r->nm_dept).'<br><small>'.trd_h($r->job_title_code.' - '.$r->job_title_name).'</small>';
  $schedule=trd_h($r->planned_start_date.' s/d '.$r->planned_end_date).'<br><small>Reg: '.trd_h($r->registration_date).'</small>';
  $score=($r->score!==null?number_format((float)$r->score,2):'-').'<br><small>Hours: '.number_format((float)$r->learning_hours,2).'</small>';
  $data[]=array(
    $no++,
    $act,
    '<strong>'.trd_h($r->registration_no).'</strong><br><small>'.trd_h($r->registration_source).'</small>',
    '<strong>'.trd_h($r->plan_code).'</strong><br><small>'.trd_h($r->plan_name).'</small><br><small>'.trd_h($r->training_code.' - '.$r->training_name).'</small>',
    $employee,
    $dept,
    $schedule,
    trd_label($r->registration_status).'<br>'.trd_label($r->approval_status),
    trd_label($r->attendance_status),
    $score,
    trd_h(($r->updated_by?:$r->created_by?:'-')).'<br><small>'.trd_h($r->updated_at?:$r->created_at).'</small>'
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'kpi'=>array(
  'total'=>$kpi?(int)$kpi->total:0,
  'registered'=>$kpi?(int)$kpi->registered:0,
  'waitlist'=>$kpi?(int)$kpi->waitlist:0,
  'present'=>$kpi?(int)$kpi->present:0,
  'completed'=>$kpi?(int)$kpi->completed:0,
  'avg_score'=>$kpi?(float)$kpi->avg_score:0
)));
?>
