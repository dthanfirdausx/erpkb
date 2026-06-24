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

function tsd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function tsd_label($s){
  $map=array('DRAFT'=>'default','PASSED'=>'success','FAILED'=>'danger','INCOMPLETE'=>'warning','NOT_EVALUATED'=>'default','NOT_STARTED'=>'default','IN_PROGRESS'=>'info','COMPLETED'=>'primary','CANCELLED'=>'danger','Y'=>'success','N'=>'default','PARTIAL'=>'warning');
  $c=isset($map[$s])?$map[$s]:'default';
  return '<span class="label label-'.$c.'">'.tsd_h($s).'</span>';
}
function tsd_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-12-31'),
    'training_plan_id'=>isset($_POST['training_plan_id'])?trim($_POST['training_plan_id']):'',
    'department_code'=>isset($_POST['department_code'])?trim($_POST['department_code']):'',
    'evaluation_method'=>isset($_POST['evaluation_method'])?trim($_POST['evaluation_method']):'',
    'result_status'=>isset($_POST['result_status'])?trim($_POST['result_status']):'',
    'completion_status'=>isset($_POST['completion_status'])?trim($_POST['completion_status']):'',
    'certificate_issued'=>isset($_POST['certificate_issued'])?trim($_POST['certificate_issued']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function tsd_where($f,&$p){
  $w=" WHERE trr.result_date BETWEEN ? AND ? ";
  $p[]=$f['from'];$p[]=$f['to'];
  foreach(array('evaluation_method','result_status','completion_status','certificate_issued') as $k){
    if($f[$k]!==''){$w.=" AND trr.$k=? ";$p[]=$f[$k];}
  }
  if($f['training_plan_id']!==''){$w.=" AND reg.training_plan_id=? ";$p[]=$f['training_plan_id'];}
  if($f['department_code']!==''){$w.=" AND e.department_code=? ";$p[]=$f['department_code'];}
  if($f['keyword']!==''){
    $kw='%'.$f['keyword'].'%';
    $w.=" AND (trr.result_no LIKE ? OR reg.registration_no LIKE ? OR tp.plan_code LIKE ? OR tp.plan_name LIKE ? OR tc.training_code LIKE ? OR tc.training_name LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ? OR trr.certificate_no LIKE ?) ";
    for($i=0;$i<9;$i++)$p[]=$kw;
  }
  return $w;
}
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$f=tsd_filters();$p=array();$w=tsd_where($f,$p);
$join=" FROM erp_training_result trr JOIN erp_training_registration reg ON reg.id=trr.training_registration_id JOIN erp_training_plan tp ON tp.id=reg.training_plan_id JOIN erp_training_catalog tc ON tc.id=tp.training_catalog_id JOIN erp_employee_master e ON e.id=reg.employee_id LEFT JOIN dept d ON d.kd_dept=e.department_code LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id ";
$kpi=$db->fetch("SELECT COUNT(*) total,SUM(CASE WHEN trr.result_status='PASSED' THEN 1 ELSE 0 END) passed,SUM(CASE WHEN trr.result_status='FAILED' THEN 1 ELSE 0 END) failed,SUM(CASE WHEN trr.completion_status='COMPLETED' THEN 1 ELSE 0 END) completed,SUM(CASE WHEN trr.certificate_issued='Y' THEN 1 ELSE 0 END) certified,ROUND(AVG(NULLIF(trr.final_score,0)),2) avg_score $join");
$cnt=$db->fetch("SELECT COUNT(*) jml $join $w",$p);
$total=$cnt?(int)$cnt->jml:0;
$orderMap=array(2=>'trr.result_no',3=>'tp.plan_code',4=>'e.employee_no',5=>'e.department_code',6=>'trr.result_date',7=>'trr.final_score',8=>'trr.result_status',9=>'trr.certificate_issued',10=>'trr.updated_at');
$orderBy="trr.result_date DESC,trr.result_no DESC";
if(isset($_POST['order'][0]['column'])){
  $col=(int)$_POST['order'][0]['column'];$dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';
  if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;
}
$rows=$db->query("SELECT trr.*,reg.registration_no,tp.plan_code,tp.plan_name,tc.training_code,tc.training_name,e.employee_no,e.full_name,e.department_code,d.nm_dept,jt.job_title_code,jt.job_title_name $join $w ORDER BY $orderBy LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<div class="ts-action"><button class="btn btn-info btn-xs btn-ts-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> ';
  $act.='<button class="btn btn-primary btn-xs btn-ts-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> ';
  $act.='<button class="btn btn-success btn-xs btn-ts-pass" data-id="'.(int)$r->id.'" title="Mark Passed"><i class="fa fa-check"></i></button> ';
  $act.='<button class="btn btn-danger btn-xs btn-ts-delete" data-id="'.(int)$r->id.'" data-no="'.tsd_h($r->result_no).'" title="'.hr_h('common_delete', 'Delete').'"><i class="fa fa-trash"></i></button></div>';
  $employee='<strong>'.tsd_h($r->employee_no).'</strong><br><small>'.tsd_h($r->full_name).'</small>';
  $dept='<strong>'.tsd_h($r->department_code).'</strong> - '.tsd_h($r->nm_dept).'<br><small>'.tsd_h($r->job_title_code.' - '.$r->job_title_name).'</small>';
  $score='Final: <strong>'.tsd_h($r->final_score!==null?number_format((float)$r->final_score,2):'-').'</strong><br><small>Pre/Post: '.tsd_h($r->pre_test_score!==null?$r->pre_test_score:'-').' / '.tsd_h($r->post_test_score!==null?$r->post_test_score:'-').'</small>';
  $cert=tsd_label($r->certificate_issued).'<br><small>'.tsd_h($r->certificate_no?:'-').'</small>';
  $data[]=array($no++,$act,'<strong>'.tsd_h($r->result_no).'</strong><br><small>'.tsd_h($r->registration_no).'</small>','<strong>'.tsd_h($r->plan_code).'</strong><br><small>'.tsd_h($r->training_code.' - '.$r->training_name).'</small>',$employee,$dept,tsd_h($r->result_date).'<br><small>'.tsd_h($r->evaluation_method).'</small>',$score,tsd_label($r->result_status).'<br>'.tsd_label($r->completion_status),$cert,tsd_h(($r->updated_by?:$r->created_by?:'-')).'<br><small>'.tsd_h($r->updated_at?:$r->created_at).'</small>');
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'kpi'=>array('total'=>$kpi?(int)$kpi->total:0,'passed'=>$kpi?(int)$kpi->passed:0,'failed'=>$kpi?(int)$kpi->failed:0,'completed'=>$kpi?(int)$kpi->completed:0,'certified'=>$kpi?(int)$kpi->certified:0,'avg_score'=>$kpi?(float)$kpi->avg_score:0)));
?>
