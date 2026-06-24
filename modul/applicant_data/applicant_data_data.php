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

function apd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function apd_label($s){
  $m=array('NEW'=>'default','SCREENING'=>'info','SHORTLISTED'=>'primary','INTERVIEW'=>'warning','OFFER'=>'success','HIRED'=>'success','REJECTED'=>'danger','WITHDRAWN'=>'default','BLACKLISTED'=>'danger');
  $c=isset($m[$s])?$m[$s]:'default';
  return '<span class="label label-'.$c.'">'.apd_h($s).'</span>';
}
function apd_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-12-31'),
    'status'=>isset($_POST['applicant_status'])?trim($_POST['applicant_status']):'',
    'vacancy_id'=>isset($_POST['vacancy_id'])?trim($_POST['vacancy_id']):'',
    'source_channel'=>isset($_POST['source_channel'])?trim($_POST['source_channel']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function apd_where($f,&$p){
  $w=" WHERE a.application_date BETWEEN ? AND ? ";array_push($p,$f['from'],$f['to']);
  if($f['status']!==''){$w.=" AND a.applicant_status=? ";$p[]=$f['status'];}
  if($f['vacancy_id']!==''){$w.=" AND a.vacancy_id=? ";$p[]=(int)$f['vacancy_id'];}
  if($f['source_channel']!==''){$w.=" AND a.source_channel=? ";$p[]=$f['source_channel'];}
  if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (a.applicant_no LIKE ? OR a.applicant_name LIKE ? OR a.email LIKE ? OR a.phone LIKE ? OR v.vacancy_no LIKE ? OR v.vacancy_title LIKE ?) ";array_push($p,$kw,$kw,$kw,$kw,$kw,$kw);}
  return $w;
}
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$f=apd_filters();$p=array();$w=apd_where($f,$p);
$kpi=$db->fetch("SELECT COUNT(*) total,SUM(a.applicant_status='NEW') new_count,SUM(a.applicant_status IN ('SCREENING','SHORTLISTED','INTERVIEW')) process_count,SUM(a.applicant_status='OFFER') offer_count,SUM(a.applicant_status='HIRED') hired_count,SUM(a.applicant_status='REJECTED') rejected_count,AVG(NULLIF(a.final_score,0)) avg_score FROM erp_applicant_data a LEFT JOIN erp_job_vacancy v ON v.id=a.vacancy_id $w",$p);
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_applicant_data a LEFT JOIN erp_job_vacancy v ON v.id=a.vacancy_id $w",$p);$total=$cnt?(int)$cnt->jml:0;
$orderMap=array(2=>'a.applicant_no',3=>'a.applicant_name',4=>'v.vacancy_no',5=>'a.education_level',6=>'a.years_experience',7=>'a.final_score',8=>'a.application_date',9=>'a.applicant_status',10=>'a.updated_at');$orderBy='a.application_date DESC,a.applicant_no DESC';
if(isset($_POST['order'][0]['column'])){$col=(int)$_POST['order'][0]['column'];$dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;}
$rows=$db->query("SELECT a.*,v.vacancy_no,v.vacancy_title,v.vacancy_status,p.position_code,jt.job_title_name,d.nm_dept,rec.employee_no recruiter_no,rec.full_name recruiter_name
  FROM erp_applicant_data a
  LEFT JOIN erp_job_vacancy v ON v.id=a.vacancy_id
  LEFT JOIN erp_position p ON p.id=v.position_id
  LEFT JOIN erp_job_title jt ON jt.id=v.job_title_id
  LEFT JOIN dept d ON d.kd_dept=v.department_code
  LEFT JOIN erp_employee_master rec ON rec.id=a.recruiter_employee_id
  $w ORDER BY $orderBy LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $canDelete=in_array($r->applicant_status,array('NEW','REJECTED','WITHDRAWN'),true);
  $act='<div class="ap-action"><button class="btn btn-info btn-xs btn-ap-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> <button class="btn btn-primary btn-xs btn-ap-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> <button class="btn btn-success btn-xs btn-ap-status" data-id="'.(int)$r->id.'" data-status="SHORTLISTED" title="Shortlist"><i class="fa fa-star"></i></button> <button class="btn btn-warning btn-xs btn-ap-status" data-id="'.(int)$r->id.'" data-status="INTERVIEW" title="'.hr_h('hr_interview', 'Interview').'"><i class="fa fa-comments"></i></button> <button class="btn btn-danger btn-xs btn-ap-delete" data-id="'.(int)$r->id.'" data-no="'.apd_h($r->applicant_no).'" title="'.hr_h('common_delete', 'Delete').'"'.($canDelete?'':' disabled').'><i class="fa fa-trash"></i></button></div>';
  $data[]=array(
    $no++,$act,
    '<strong>'.apd_h($r->applicant_no).'</strong><br><small>'.apd_h($r->sap_reference?:'-').'</small>',
    '<strong>'.apd_h($r->applicant_name).'</strong><br><small>'.apd_h(($r->email?:'-').' / '.($r->phone?:'-')).'</small>',
    '<strong>'.apd_h($r->vacancy_no?:'-').'</strong><br><small>'.apd_h($r->vacancy_title?:'-').'</small><br><small>'.apd_h($r->position_code?:'-').' / '.apd_h($r->job_title_name?:'-').'</small>',
    '<strong>'.apd_h($r->education_level).'</strong><br><small>'.apd_h(($r->major?:'-').' / '.($r->university?:'-')).'</small>',
    '<strong>'.number_format((float)$r->years_experience,2).' yrs</strong><br><small>'.apd_h($r->current_position?:'-').'</small>',
    '<strong>'.number_format((float)$r->final_score,2).'</strong><br><small>Screen '.number_format((float)$r->screening_score,2).' / Int '.number_format((float)$r->interview_score,2).'</small>',
    '<strong>'.apd_h($r->application_date).'</strong><br><small>'.apd_h($r->source_channel?:'-').'</small>',
    apd_label($r->applicant_status).'<br><small>'.apd_h($r->recruiter_no?($r->recruiter_no.' - '.$r->recruiter_name):'-').'</small>',
    apd_h($r->updated_by?:$r->created_by?:'-').'<br><small>'.apd_h($r->updated_at?:$r->created_at).'</small>'
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'kpi'=>array('total'=>$kpi?(int)$kpi->total:0,'new'=>$kpi?(int)$kpi->new_count:0,'process'=>$kpi?(int)$kpi->process_count:0,'offer'=>$kpi?(int)$kpi->offer_count:0,'hired'=>$kpi?(int)$kpi->hired_count:0,'rejected'=>$kpi?(int)$kpi->rejected_count:0,'avg_score'=>$kpi?number_format((float)$kpi->avg_score,2):'0.00')));
?>
