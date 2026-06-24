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

function pfr_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function pfr_label($s){
  $map=array('DRAFT'=>'default','SUBMITTED'=>'info','MANAGER_APPROVED'=>'primary','HR_REVIEW'=>'warning','APPROVED'=>'success','REJECTED'=>'danger','RETURNED'=>'warning','CANCELLED'=>'default','PENDING'=>'warning');
  $c=isset($map[$s])?$map[$s]:'default';
  return '<span class="label label-'.$c.'">'.pfr_h($s?:'-').'</span>';
}
function pfr_rating($r){
  $c=array('A'=>'success','B'=>'primary','C'=>'info','D'=>'warning','E'=>'danger');
  $cls=isset($c[$r])?$c[$r]:'default';
  return '<span class="label label-'.$cls.'" style="font-size:13px">'.pfr_h($r?:'-').'</span>';
}
function pfr_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-m-d'),
    'cycle_year'=>isset($_POST['cycle_year'])?trim($_POST['cycle_year']):'',
    'appraisal_period'=>isset($_POST['appraisal_period'])?trim($_POST['appraisal_period']):'',
    'appraisal_type'=>isset($_POST['appraisal_type'])?trim($_POST['appraisal_type']):'',
    'employee_id'=>isset($_POST['employee_id'])?trim($_POST['employee_id']):'',
    'appraiser_employee_id'=>isset($_POST['appraiser_employee_id'])?trim($_POST['appraiser_employee_id']):'',
    'department_code'=>isset($_POST['department_code'])?trim($_POST['department_code']):'',
    'job_title_id'=>isset($_POST['job_title_id'])?trim($_POST['job_title_id']):'',
    'final_rating'=>isset($_POST['final_rating'])?trim($_POST['final_rating']):'',
    'calibration_status'=>isset($_POST['calibration_status'])?trim($_POST['calibration_status']):'',
    'decision'=>isset($_POST['decision'])?trim($_POST['decision']):'',
    'improvement_required'=>isset($_POST['improvement_required'])?trim($_POST['improvement_required']):'',
    'impact_type'=>isset($_POST['impact_type'])?trim($_POST['impact_type']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function pfr_where($f,&$p){
  $w=" WHERE a.appraisal_date BETWEEN ? AND ? ";
  array_push($p,$f['from'],$f['to']);
  foreach(array('cycle_year','appraisal_period','appraisal_type','department_code','final_rating','calibration_status','decision','improvement_required') as $k){if($f[$k]!==''){$w.=" AND a.$k=? ";$p[]=$f[$k];}}
  foreach(array('employee_id','appraiser_employee_id','job_title_id') as $k){if($f[$k]!==''){$w.=" AND a.$k=? ";$p[]=(int)$f[$k];}}
  if($f['impact_type']==='HIGH_PERFORMER')$w.=" AND a.final_rating IN ('A','B') AND a.final_score>=85 ";
  elseif($f['impact_type']==='LOW_PERFORMER')$w.=" AND (a.final_rating IN ('D','E') OR a.final_score<70) ";
  elseif($f['impact_type']==='PIP')$w.=" AND a.improvement_required='Y' ";
  elseif($f['impact_type']==='PENDING_APPROVAL')$w.=" AND a.calibration_status IN ('DRAFT','SUBMITTED','MANAGER_APPROVED','HR_REVIEW') ";
  elseif($f['impact_type']==='APPROVED_RESULT')$w.=" AND a.calibration_status='APPROVED' ";
  if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (a.appraisal_no LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ? OR d.nm_dept LIKE ? OR j.job_title_name LIKE ? OR ap.full_name LIKE ? OR a.reward_recommendation LIKE ?) ";for($i=0;$i<7;$i++)$p[]=$kw;}
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$f=pfr_filters();$p=array();$w=pfr_where($f,$p);
$base=" FROM erp_appraisal_approval a JOIN erp_employee_master e ON e.id=a.employee_id LEFT JOIN dept d ON d.kd_dept=a.department_code LEFT JOIN erp_job_title j ON j.id=a.job_title_id LEFT JOIN erp_employee_master ap ON ap.id=a.appraiser_employee_id LEFT JOIN erp_employee_master hr ON hr.id=a.hr_reviewer_employee_id ";
$kpi=$db->fetch("SELECT COUNT(*) total,COUNT(DISTINCT a.employee_id) employees,ROUND(AVG(a.final_score),2) avg_score,SUM(a.final_rating='A') rating_a,SUM(a.final_rating IN ('A','B') AND a.final_score>=85) high_performer,SUM(a.final_rating IN ('D','E') OR a.final_score<70) low_performer,SUM(a.improvement_required='Y') pip,SUM(a.calibration_status='APPROVED') approved $base $w",$p);
$cnt=$db->fetch("SELECT COUNT(*) jml $base $w",$p);$total=$cnt?(int)$cnt->jml:0;
$orderMap=array(2=>'a.appraisal_no',3=>'a.cycle_year',4=>'e.employee_no',5=>'d.kd_dept',6=>'ap.employee_no',7=>'a.kpi_score',8=>'a.competency_score',9=>'a.behavior_score',10=>'a.final_score',11=>'a.final_rating',12=>'a.calibration_status',13=>'a.updated_at');
$orderBy='a.appraisal_date DESC,a.id DESC';
if(isset($_POST['order'][0]['column'])){$col=(int)$_POST['order'][0]['column'];$dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;}
$rows=$db->query("SELECT a.*,e.employee_no,e.full_name,e.employee_group,d.nm_dept,j.job_title_code,j.job_title_name,ap.employee_no appraiser_no,ap.full_name appraiser_name,hr.employee_no hr_no,hr.full_name hr_name $base $w ORDER BY $orderBy LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $employee='<strong>'.pfr_h($r->employee_no).'</strong><br><small>'.pfr_h($r->full_name).' / '.pfr_h($r->employee_group).'</small>';
  $org='<strong>'.pfr_h($r->department_code?:'-').'</strong><br><small>'.pfr_h($r->nm_dept?:'-').'</small><br><small>'.pfr_h($r->job_title_code?:'-').' '.pfr_h($r->job_title_name?:'').'</small>';
  $cycle='<strong>'.pfr_h($r->cycle_year.' '.$r->appraisal_period).'</strong><br><small>'.pfr_h($r->appraisal_type).' / '.pfr_h($r->appraisal_date).'</small>';
  $appraiser='<strong>'.pfr_h($r->appraiser_no?:'-').'</strong><br><small>'.pfr_h($r->appraiser_name?:'-').'</small><br><small>HR '.pfr_h($r->hr_no?:'-').'</small>';
  $score='<strong>'.number_format((float)$r->final_score,2).'</strong><br><small>KPI '.number_format((float)$r->kpi_score,2).' | Comp '.number_format((float)$r->competency_score,2).' | Beh '.number_format((float)$r->behavior_score,2).'</small>';
  $rec=pfr_h($r->reward_recommendation?:'-').'<br><small>PIP: '.pfr_h($r->improvement_required).'</small>';
  $data[]=array($no++,'<button class="btn btn-info btn-xs btn-pfr-detail" data-id="'.(int)$r->id.'"><i class="fa fa-eye"></i></button>','<strong>'.pfr_h($r->appraisal_no).'</strong><br><small>'.pfr_h($r->remarks?:'-').'</small>',$cycle,$employee,$org,$appraiser,number_format((float)$r->kpi_score,2),number_format((float)$r->competency_score,2),number_format((float)$r->behavior_score,2),$score,pfr_rating($r->final_rating),pfr_label($r->calibration_status).'<br><small>'.pfr_h($r->decision).'</small>',$rec,pfr_h($r->updated_by?:$r->created_by?:'-').'<br><small>'.pfr_h($r->updated_at?:$r->created_at).'</small>');
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'kpi'=>array('total'=>$kpi?(int)$kpi->total:0,'employees'=>$kpi?(int)$kpi->employees:0,'avg_score'=>$kpi&&$kpi->avg_score!==null?$kpi->avg_score:0,'rating_a'=>$kpi?(int)$kpi->rating_a:0,'high_performer'=>$kpi?(int)$kpi->high_performer:0,'low_performer'=>$kpi?(int)$kpi->low_performer:0,'pip'=>$kpi?(int)$kpi->pip:0,'approved'=>$kpi?(int)$kpi->approved:0)));
?>
