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

function jvd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function jvd_label($s){
  $m=array('DRAFT'=>'default','OPEN'=>'success','SCREENING'=>'info','INTERVIEW'=>'primary','OFFER'=>'warning','HIRED'=>'success','CANCELLED'=>'danger','CLOSED'=>'default');
  $c=isset($m[$s])?$m[$s]:'default';
  return '<span class="label label-'.$c.'">'.jvd_h($s).'</span>';
}
function jvd_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-12-31'),
    'status'=>isset($_POST['vacancy_status'])?trim($_POST['vacancy_status']):'',
    'type'=>isset($_POST['vacancy_type'])?trim($_POST['vacancy_type']):'',
    'department_code'=>isset($_POST['department_code'])?trim($_POST['department_code']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function jvd_where($f,&$p){
  $w=" WHERE COALESCE(v.posting_date,v.created_at)<=? AND COALESCE(v.closing_date,'9999-12-31')>=? ";$p[]=$f['to'];$p[]=$f['from'];
  if($f['status']!==''){$w.=" AND v.vacancy_status=? ";$p[]=$f['status'];}
  if($f['type']!==''){$w.=" AND v.vacancy_type=? ";$p[]=$f['type'];}
  if($f['department_code']!==''){$w.=" AND v.department_code=? ";$p[]=$f['department_code'];}
  if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (v.vacancy_no LIKE ? OR v.vacancy_title LIKE ? OR p.position_code LIKE ? OR jt.job_title_name LIKE ? OR v.sap_reference LIKE ?) ";array_push($p,$kw,$kw,$kw,$kw,$kw);}
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$f=jvd_filters();$p=array();$w=jvd_where($f,$p);
$kpi=$db->fetch("SELECT COUNT(*) total,SUM(v.vacancy_status='OPEN') open_count,SUM(v.vacancy_status IN ('SCREENING','INTERVIEW','OFFER')) process_count,SUM(v.vacancy_status='HIRED') hired_status,SUM(v.headcount_requested) requested_hc,SUM(v.headcount_filled) filled_hc,SUM(v.applicant_count) applicants FROM erp_job_vacancy v LEFT JOIN erp_position p ON p.id=v.position_id LEFT JOIN erp_job_title jt ON jt.id=v.job_title_id $w",$p);
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_job_vacancy v LEFT JOIN erp_position p ON p.id=v.position_id LEFT JOIN erp_job_title jt ON jt.id=v.job_title_id $w",$p);$total=$cnt?(int)$cnt->jml:0;
$orderMap=array(2=>'v.vacancy_no',3=>'v.vacancy_title',4=>'d.kd_dept',5=>'v.vacancy_type',6=>'v.headcount_requested',7=>'v.applicant_count',8=>'v.posting_date',9=>'v.vacancy_status',10=>'v.updated_at');$orderBy='v.created_at DESC,v.vacancy_no DESC';
if(isset($_POST['order'][0]['column'])){$col=(int)$_POST['order'][0]['column'];$dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;}
$rows=$db->query("SELECT v.*,p.position_code,p.position_name,jt.job_title_code,jt.job_title_name,d.nm_dept,cs.structure_code,cs.structure_name,rec.employee_no recruiter_no,rec.full_name recruiter_name,hm.employee_no manager_no,hm.full_name manager_name
  FROM erp_job_vacancy v
  LEFT JOIN erp_position p ON p.id=v.position_id
  LEFT JOIN erp_job_title jt ON jt.id=v.job_title_id
  LEFT JOIN dept d ON d.kd_dept=v.department_code
  LEFT JOIN erp_company_structure cs ON cs.id=v.company_structure_id
  LEFT JOIN erp_employee_master rec ON rec.id=v.recruiter_employee_id
  LEFT JOIN erp_employee_master hm ON hm.id=v.hiring_manager_employee_id
  $w ORDER BY $orderBy LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $canDelete=in_array($r->vacancy_status,array('DRAFT','CANCELLED'),true);
  $act='<div class="jv-action"><button class="btn btn-info btn-xs btn-jv-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> <button class="btn btn-primary btn-xs btn-jv-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> <button class="btn btn-success btn-xs btn-jv-status" data-id="'.(int)$r->id.'" data-status="OPEN" title="Open"><i class="fa fa-bullhorn"></i></button> <button class="btn btn-warning btn-xs btn-jv-status" data-id="'.(int)$r->id.'" data-status="CLOSED" title="'.hr_h('common_close', 'Close').'"><i class="fa fa-lock"></i></button> <button class="btn btn-danger btn-xs btn-jv-delete" data-id="'.(int)$r->id.'" data-no="'.jvd_h($r->vacancy_no).'" title="'.hr_h('common_delete', 'Delete').'"'.($canDelete?'':' disabled').'><i class="fa fa-trash"></i></button></div>';
  $data[]=array(
    $no++,$act,
    '<strong>'.jvd_h($r->vacancy_no).'</strong><br><small>'.jvd_h($r->sap_reference?:'-').'</small>',
    '<strong>'.jvd_h($r->vacancy_title).'</strong><br><small>'.jvd_h(($r->position_code?:'-').' / '.($r->job_title_name?:'-')).'</small>',
    '<strong>'.jvd_h($r->department_code?:'-').'</strong><br><small>'.jvd_h($r->nm_dept?:'').'</small><br><small>'.jvd_h($r->structure_code?:'-').'</small>',
    '<span class="label label-info">'.jvd_h($r->vacancy_type).'</span><br><small>'.jvd_h($r->employment_type.' / '.$r->employee_group).'</small>',
    '<strong>'.number_format((float)$r->headcount_filled,2).' / '.number_format((float)$r->headcount_requested,2).'</strong><br><small>Approved '.number_format((float)$r->headcount_approved,2).'</small>',
    '<strong>'.(int)$r->applicant_count.'</strong> applicant<br><small>'.(int)$r->shortlisted_count.' shortlist / '.(int)$r->interview_count.' interview / '.(int)$r->offer_count.' offer</small>',
    '<strong>'.jvd_h($r->posting_date?:'-').'</strong><br><small>Close '.jvd_h($r->closing_date?:'-').'</small>',
    jvd_label($r->vacancy_status).'<br><small>'.jvd_h($r->priority).'</small>',
    jvd_h($r->updated_by?:$r->created_by?:'-').'<br><small>'.jvd_h($r->updated_at?:$r->created_at).'</small>'
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'kpi'=>array('total'=>$kpi?(int)$kpi->total:0,'open'=>$kpi?(int)$kpi->open_count:0,'process'=>$kpi?(int)$kpi->process_count:0,'hired'=>$kpi?(int)$kpi->hired_status:0,'requested_hc'=>$kpi?number_format((float)$kpi->requested_hc,2):'0.00','filled_hc'=>$kpi?number_format((float)$kpi->filled_hc,2):'0.00','applicants'=>$kpi?(int)$kpi->applicants:0)));
?>
