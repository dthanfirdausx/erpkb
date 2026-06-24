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
function lad_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function lad_label($s){$m=array('SUBMITTED'=>'info','MANAGER_APPROVED'=>'primary','HR_APPROVED'=>'primary','APPROVED'=>'success','REJECTED'=>'danger','RETURNED'=>'warning','CANCELLED'=>'default','DRAFT'=>'default');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.lad_h($s).'</span>';}
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$len=isset($_POST['length'])?(int)$_POST['length']:25;if($len<=0||$len>500)$len=25;
$from=isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01');$to=isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-m-d');$status=isset($_POST['status'])?trim($_POST['status']):'';$dept=isset($_POST['department_code'])?trim($_POST['department_code']):'';$kw=isset($_POST['keyword'])?trim($_POST['keyword']):'';
$p=array($to,$from);$w=" WHERE l.start_date<=? AND l.end_date>=? ";if($status!==''){$w.=" AND l.workflow_status=? ";$p[]=$status;}if($dept!==''){$w.=" AND l.department_code=? ";$p[]=$dept;}if($kw!==''){$like='%'.$kw.'%';$w.=" AND (l.leave_no LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ? OR d.nm_dept LIKE ?) ";array_push($p,$like,$like,$like,$like);}
$kpi=$db->fetch("SELECT COUNT(*) total,SUM(workflow_status='SUBMITTED') pending_manager,SUM(workflow_status='MANAGER_APPROVED') pending_hr,SUM(workflow_status='APPROVED') approved,SUM(workflow_status IN ('REJECTED','RETURNED')) exception_count FROM erp_leave_request");
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_leave_request l JOIN erp_employee_master e ON e.id=l.employee_id LEFT JOIN dept d ON d.kd_dept=l.department_code $w",$p);$total=$cnt?(int)$cnt->jml:0;
$orderMap=array(2=>'l.leave_no',3=>'e.full_name',4=>'l.leave_type',5=>'l.start_date',6=>'l.total_days',7=>'l.workflow_status',8=>'la.decision_date');$orderBy="l.start_date DESC,l.id DESC";if(isset($_POST['order'][0]['column'])){$col=(int)$_POST['order'][0]['column'];$dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;}
$rows=$db->query("SELECT l.*,e.employee_no,e.full_name,d.nm_dept,a.employee_no approver_no,a.full_name approver_name,hr.employee_no hr_no,hr.full_name hr_name,la.approval_no last_approval_no,la.decision last_decision,la.decision_date last_decision_date FROM erp_leave_request l JOIN erp_employee_master e ON e.id=l.employee_id LEFT JOIN dept d ON d.kd_dept=l.department_code LEFT JOIN erp_employee_master a ON a.id=l.approver_employee_id LEFT JOIN erp_employee_master hr ON hr.id=l.hr_reviewer_employee_id LEFT JOIN (SELECT x.* FROM erp_leave_approval x JOIN (SELECT leave_request_id,MAX(id) id FROM erp_leave_approval GROUP BY leave_request_id) y ON y.id=x.id) la ON la.leave_request_id=l.id $w ORDER BY $orderBy LIMIT $start,$len",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $closed=in_array($r->workflow_status,array('APPROVED','REJECTED','CANCELLED'),true);
  $act='<div class="la-action"><button class="btn btn-info btn-xs btn-la-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> ';
  $step=$r->workflow_status==='SUBMITTED'?'MANAGER':($r->workflow_status==='MANAGER_APPROVED'?'HR':'FINAL');
  if(!$closed){$act.='<button class="btn btn-success btn-xs btn-la-decision" data-id="'.(int)$r->id.'" data-no="'.lad_h($r->leave_no).'" data-step="'.$step.'" data-decision="APPROVE" title="'.hr_h('common_approve', 'Approve').'"><i class="fa fa-check"></i></button> <button class="btn btn-warning btn-xs btn-la-decision" data-id="'.(int)$r->id.'" data-no="'.lad_h($r->leave_no).'" data-step="'.$step.'" data-decision="RETURN" title="Return"><i class="fa fa-reply"></i></button> <button class="btn btn-danger btn-xs btn-la-decision" data-id="'.(int)$r->id.'" data-no="'.lad_h($r->leave_no).'" data-step="'.$step.'" data-decision="REJECT" title="'.hr_h('common_reject', 'Reject').'"><i class="fa fa-times"></i></button>';}
  $act.='</div>';
  $data[]=array($no++,$act,'<strong>'.lad_h($r->leave_no).'</strong><br><small>'.lad_h($r->request_date).' | Step '.$step.'</small>','<strong>'.lad_h($r->employee_no).'</strong><br><small>'.lad_h($r->full_name).'</small><br><small>'.lad_h($r->department_code.' '.$r->nm_dept).'</small>','<span class="la-pill">'.lad_h($r->leave_type).'</span>','<strong>'.lad_h($r->start_date).'</strong><br><small>s/d '.lad_h($r->end_date).'</small>',lad_h($r->total_days).'<br><small>Quota '.$r->leave_quota_before.' -> '.$r->leave_quota_after.'</small>',lad_label($r->workflow_status).'<br><small>'.lad_h($r->decision).'</small>',lad_h($r->approver_no?($r->approver_no.' - '.$r->approver_name):'-').'<br><small>HR: '.lad_h($r->hr_no?:'-').'</small>',lad_h($r->last_approval_no?:'-').'<br><small>'.lad_h($r->last_decision?:'-').' '.lad_h($r->last_decision_date?:'').'</small>');
}
header('Content-Type: application/json; charset=utf-8');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'kpi'=>array('total'=>$kpi?(int)$kpi->total:0,'pending_manager'=>$kpi?(int)$kpi->pending_manager:0,'pending_hr'=>$kpi?(int)$kpi->pending_hr:0,'approved'=>$kpi?(int)$kpi->approved:0,'exception_count'=>$kpi?(int)$kpi->exception_count:0)));
?>
