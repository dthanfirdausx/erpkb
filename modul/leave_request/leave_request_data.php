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
function lrd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function lrd_label($s){$m=array('DRAFT'=>'default','SUBMITTED'=>'info','MANAGER_APPROVED'=>'primary','HR_APPROVED'=>'primary','APPROVED'=>'success','REJECTED'=>'danger','RETURNED'=>'warning','CANCELLED'=>'default');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.lrd_h($s).'</span>';}
function lrd_filters(){return array('from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01'),'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-m-d'),'leave_type'=>isset($_POST['leave_type'])?trim($_POST['leave_type']):'','status'=>isset($_POST['status'])?trim($_POST['status']):'','department_code'=>isset($_POST['department_code'])?trim($_POST['department_code']):'','keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):'');}
function lrd_where($f,&$p){$w=" WHERE l.start_date<=? AND l.end_date>=? ";$p[]=$f['to'];$p[]=$f['from'];if($f['leave_type']!==''){$w.=" AND l.leave_type=? ";$p[]=$f['leave_type'];}if($f['status']!==''){$w.=" AND l.workflow_status=? ";$p[]=$f['status'];}if($f['department_code']!==''){$w.=" AND l.department_code=? ";$p[]=$f['department_code'];}if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (l.leave_no LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ? OR d.nm_dept LIKE ? OR l.reason LIKE ?) ";for($i=0;$i<5;$i++)$p[]=$kw;}return $w;}
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$len=isset($_POST['length'])?(int)$_POST['length']:25;if($len<=0||$len>500)$len=25;$f=lrd_filters();$p=array();$w=lrd_where($f,$p);
$kpi=$db->fetch("SELECT COUNT(*) total,SUM(workflow_status='SUBMITTED') submitted,SUM(workflow_status='APPROVED') approved,SUM(workflow_status IN ('REJECTED','RETURNED')) exception_count,SUM(total_days) total_days FROM erp_leave_request");
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_leave_request l JOIN erp_employee_master e ON e.id=l.employee_id LEFT JOIN dept d ON d.kd_dept=l.department_code $w",$p);$total=$cnt?(int)$cnt->jml:0;
$orderMap=array(2=>'l.leave_no',3=>'e.full_name',4=>'l.leave_type',5=>'l.start_date',6=>'l.total_days',7=>'a.full_name',8=>'l.workflow_status',9=>'l.updated_at');$orderBy="l.start_date DESC,l.id DESC";if(isset($_POST['order'][0]['column'])){$col=(int)$_POST['order'][0]['column'];$dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;}
$rows=$db->query("SELECT l.*,e.employee_no,e.full_name,d.nm_dept,j.job_title_code,j.job_title_name,a.employee_no approver_no,a.full_name approver_name,h.employee_no handover_no,h.full_name handover_name FROM erp_leave_request l JOIN erp_employee_master e ON e.id=l.employee_id LEFT JOIN dept d ON d.kd_dept=l.department_code LEFT JOIN erp_job_title j ON j.id=l.job_title_id LEFT JOIN erp_employee_master a ON a.id=l.approver_employee_id LEFT JOIN erp_employee_master h ON h.id=l.handover_to_employee_id $w ORDER BY $orderBy LIMIT $start,$len",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $final=$r->workflow_status==='APPROVED';
  $act='<div class="lr-action"><button class="btn btn-info btn-xs btn-lr-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> ';
  if(!$final){$act.='<button class="btn btn-primary btn-xs btn-lr-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> <button class="btn btn-success btn-xs btn-lr-decision" data-id="'.(int)$r->id.'" data-no="'.lrd_h($r->leave_no).'" data-decision="APPROVE" title="'.hr_h('common_approve', 'Approve').'"><i class="fa fa-check"></i></button> <button class="btn btn-warning btn-xs btn-lr-decision" data-id="'.(int)$r->id.'" data-no="'.lrd_h($r->leave_no).'" data-decision="RETURN" title="Return"><i class="fa fa-reply"></i></button> <button class="btn btn-danger btn-xs btn-lr-delete" data-id="'.(int)$r->id.'" data-no="'.lrd_h($r->leave_no).'" title="'.hr_h('common_delete', 'Delete').'"><i class="fa fa-trash"></i></button>';}
  $act.='</div>';
  $data[]=array($no++,$act,'<strong>'.lrd_h($r->leave_no).'</strong><br><small>'.lrd_h($r->request_date).' | SAP: '.lrd_h($r->sap_reference?:'-').'</small>','<strong>'.lrd_h($r->employee_no).'</strong><br><small>'.lrd_h($r->full_name).'</small><br><small>'.lrd_h($r->department_code?:'-').' '.lrd_h($r->nm_dept?:'').'</small>','<span class="lr-pill">'.lrd_h($r->leave_type).'</span><br><small>'.lrd_h($r->start_half_day.' / '.$r->end_half_day).'</small>','<strong>'.lrd_h($r->start_date).'</strong><br><small>s/d '.lrd_h($r->end_date).'</small>','<strong>'.lrd_h($r->total_days).'</strong><br><small>Quota: '.lrd_h($r->leave_quota_before).' -> '.lrd_h($r->leave_quota_after).'</small>',lrd_h($r->approver_no?($r->approver_no.' - '.$r->approver_name):'-').'<br><small>Handover: '.lrd_h($r->handover_no?:'-').'</small>',lrd_label($r->workflow_status).'<br><small>'.lrd_h($r->decision).'</small>',lrd_h(($r->updated_by?:$r->created_by?:'-')).'<br><small>'.lrd_h($r->updated_at?:$r->created_at).'</small>');
}
header('Content-Type: application/json; charset=utf-8');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'kpi'=>array('total'=>$kpi?(int)$kpi->total:0,'submitted'=>$kpi?(int)$kpi->submitted:0,'approved'=>$kpi?(int)$kpi->approved:0,'exception_count'=>$kpi?(int)$kpi->exception_count:0,'total_days'=>$kpi&&$kpi->total_days!==null?$kpi->total_days:0)));
?>
