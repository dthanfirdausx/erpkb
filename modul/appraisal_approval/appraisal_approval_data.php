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

function aad_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function aad_label($s){
  $map=array('DRAFT'=>'default','SUBMITTED'=>'info','MANAGER_APPROVED'=>'primary','HR_REVIEW'=>'warning','APPROVED'=>'success','REJECTED'=>'danger','RETURNED'=>'warning','CANCELLED'=>'default');
  $c=isset($map[$s])?$map[$s]:'default';
  return '<span class="label label-'.$c.'">'.aad_h($s).'</span>';
}
function aad_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-m-d'),
    'cycle_year'=>isset($_POST['cycle_year'])?trim($_POST['cycle_year']):'',
    'status'=>isset($_POST['status'])?trim($_POST['status']):'',
    'rating'=>isset($_POST['rating'])?trim($_POST['rating']):'',
    'department_code'=>isset($_POST['department_code'])?trim($_POST['department_code']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function aad_where($f,&$p){
  $w=" WHERE a.appraisal_date BETWEEN ? AND ? ";
  $p[]=$f['from'];$p[]=$f['to'];
  if($f['cycle_year']!==''){$w.=" AND a.cycle_year=? ";$p[]=$f['cycle_year'];}
  if($f['status']!==''){$w.=" AND a.calibration_status=? ";$p[]=$f['status'];}
  if($f['rating']!==''){$w.=" AND a.final_rating=? ";$p[]=$f['rating'];}
  if($f['department_code']!==''){$w.=" AND a.department_code=? ";$p[]=$f['department_code'];}
  if($f['keyword']!==''){
    $kw='%'.$f['keyword'].'%';
    $w.=" AND (a.appraisal_no LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ? OR d.nm_dept LIKE ? OR j.job_title_name LIKE ? OR ap.full_name LIKE ?) ";
    for($i=0;$i<6;$i++)$p[]=$kw;
  }
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$f=aad_filters();$p=array();$w=aad_where($f,$p);

$kpi=$db->fetch("SELECT COUNT(*) total,SUM(calibration_status='SUBMITTED') submitted,SUM(calibration_status='HR_REVIEW') hr_review,SUM(calibration_status='APPROVED') approved,ROUND(AVG(final_score),2) avg_score FROM erp_appraisal_approval");
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_appraisal_approval a JOIN erp_employee_master e ON e.id=a.employee_id LEFT JOIN dept d ON d.kd_dept=a.department_code LEFT JOIN erp_job_title j ON j.id=a.job_title_id LEFT JOIN erp_employee_master ap ON ap.id=a.appraiser_employee_id $w",$p);
$total=$cnt?(int)$cnt->jml:0;

$orderMap=array(2=>'a.appraisal_no',3=>'a.cycle_year',4=>'e.full_name',5=>'d.nm_dept',6=>'ap.full_name',7=>'a.final_score',8=>'a.final_rating',9=>'a.calibration_status',10=>'a.updated_at');
$orderBy="a.appraisal_date DESC,a.id DESC";
if(isset($_POST['order'][0]['column'])){
  $col=(int)$_POST['order'][0]['column'];
  $dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';
  if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;
}

$rows=$db->query("SELECT a.*,e.employee_no,e.full_name,d.nm_dept,j.job_title_code,j.job_title_name,ap.employee_no appraiser_no,ap.full_name appraiser_name,hr.employee_no hr_no,hr.full_name hr_name
  FROM erp_appraisal_approval a
  JOIN erp_employee_master e ON e.id=a.employee_id
  LEFT JOIN dept d ON d.kd_dept=a.department_code
  LEFT JOIN erp_job_title j ON j.id=a.job_title_id
  LEFT JOIN erp_employee_master ap ON ap.id=a.appraiser_employee_id
  LEFT JOIN erp_employee_master hr ON hr.id=a.hr_reviewer_employee_id
  $w
  ORDER BY $orderBy
  LIMIT $start,$length",$p);

$data=array();$no=$start+1;
foreach($rows as $r){
  $disabled=$r->calibration_status==='APPROVED';
  $act='<div class="aa-action"><button class="btn btn-info btn-xs btn-aa-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> ';
  if(!$disabled){
    $act.='<button class="btn btn-primary btn-xs btn-aa-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> ';
    $act.='<button class="btn btn-success btn-xs btn-aa-decision" data-id="'.(int)$r->id.'" data-no="'.aad_h($r->appraisal_no).'" data-decision="APPROVE" title="'.hr_h('common_approve', 'Approve').'"><i class="fa fa-check"></i></button> ';
    $act.='<button class="btn btn-warning btn-xs btn-aa-decision" data-id="'.(int)$r->id.'" data-no="'.aad_h($r->appraisal_no).'" data-decision="RETURN" title="Return"><i class="fa fa-reply"></i></button> ';
    $act.='<button class="btn btn-danger btn-xs btn-aa-delete" data-id="'.(int)$r->id.'" data-no="'.aad_h($r->appraisal_no).'" title="'.hr_h('common_delete', 'Delete').'"><i class="fa fa-trash"></i></button>';
  }
  $act.='</div>';
  $score='<div class="aa-score"><b>'.aad_h($r->final_score).'</b><br><small>KPI '.aad_h($r->kpi_score).' | Comp '.aad_h($r->competency_score).' | Beh '.aad_h($r->behavior_score).'</small></div>';
  $data[]=array(
    $no++,
    $act,
    '<strong>'.aad_h($r->appraisal_no).'</strong><br><small>'.aad_h($r->appraisal_date).' | '.aad_h($r->appraisal_type).'</small>',
    '<strong>'.aad_h($r->cycle_year.' '.$r->appraisal_period).'</strong><br><small>Level: '.aad_h($r->approval_level).'</small>',
    '<strong>'.aad_h($r->employee_no).'</strong><br><small>'.aad_h($r->full_name).'</small>',
    '<strong>'.aad_h($r->department_code?:'-').'</strong><br><small>'.aad_h($r->nm_dept?:'-').'</small><br><small>'.aad_h($r->job_title_code?:'-').' '.aad_h($r->job_title_name?:'').'</small>',
    '<strong>'.aad_h($r->appraiser_no?:'-').'</strong><br><small>'.aad_h($r->appraiser_name?:'-').'</small><br><small>HR: '.aad_h($r->hr_no?:'-').'</small>',
    $score,
    '<span class="aa-rating aa-rating-'.$r->final_rating.'">'.aad_h($r->final_rating).'</span>',
    aad_label($r->calibration_status).'<br><small>'.aad_h($r->decision).'</small>',
    aad_h(($r->updated_by?:$r->created_by?:'-')).'<br><small>'.aad_h($r->updated_at?:$r->created_at).'</small>'
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
  'draw'=>$draw,
  'recordsTotal'=>$total,
  'recordsFiltered'=>$total,
  'data'=>$data,
  'kpi'=>array(
    'total'=>$kpi?(int)$kpi->total:0,
    'submitted'=>$kpi?(int)$kpi->submitted:0,
    'hr_review'=>$kpi?(int)$kpi->hr_review:0,
    'approved'=>$kpi?(int)$kpi->approved:0,
    'avg_score'=>$kpi&&$kpi->avg_score!==null?$kpi->avg_score:0
  )
));
?>
