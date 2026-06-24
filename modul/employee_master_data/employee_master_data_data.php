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
function emd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function emd_label($s){$map=array('ACTIVE'=>'success','PROBATION'=>'info','CONTRACT'=>'primary','INACTIVE'=>'warning','TERMINATED'=>'danger');$c=isset($map[$s])?$map[$s]:'default';return '<span class="label label-'.$c.'">'.emd_h($s).'</span>';}
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$from=isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01');$to=isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:'9999-12-31';
$status=isset($_POST['employment_status'])?trim($_POST['employment_status']):'';$dept=isset($_POST['department_code'])?trim($_POST['department_code']):'';$group=isset($_POST['employee_group'])?trim($_POST['employee_group']):'';$keyword=isset($_POST['keyword'])?trim($_POST['keyword']):'';
$p=array($to,$from);$w=" WHERE e.valid_from<=? AND e.valid_to>=? ";
if($status!==''){$w.=" AND e.employment_status=? ";$p[]=$status;} if($dept!==''){$w.=" AND e.department_code=? ";$p[]=$dept;} if($group!==''){$w.=" AND e.employee_group=? ";$p[]=$group;}
if($keyword!==''){$kw='%'.$keyword.'%';$w.=" AND (e.employee_no LIKE ? OR e.full_name LIKE ? OR e.email LIKE ? OR e.phone LIKE ? OR d.nm_dept LIKE ? OR j.job_title_name LIKE ? OR e.identity_no LIKE ?) ";for($i=0;$i<7;$i++)$p[]=$kw;}
$kpi=$db->fetch("SELECT COUNT(*) total,SUM(employment_status IN ('ACTIVE','PROBATION','CONTRACT')) active,SUM(employee_group='OPERATOR') operators,SUM(employee_group IN ('MANAGER','DIRECTOR')) leaders FROM erp_employee_master");
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_employee_master e LEFT JOIN dept d ON d.kd_dept=e.department_code LEFT JOIN erp_job_title j ON j.id=e.job_title_id $w",$p);$total=$cnt?(int)$cnt->jml:0;
$orderMap=array(2=>'e.employee_no',3=>'e.full_name',4=>'j.job_title_code',5=>'d.kd_dept',6=>'m.employee_no',7=>'e.employee_group',8=>'e.hire_date',9=>'e.employment_status',10=>'e.updated_at');
$orderBy='e.employee_no';if(isset($_POST['order'][0]['column'])){$col=(int)$_POST['order'][0]['column'];$dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;}
$rows=$db->query("SELECT e.*,d.nm_dept,j.job_title_code,j.job_title_name,j.job_level,cs.structure_code,cc.cost_center_name,pc.profit_center_name,m.employee_no manager_no,m.full_name manager_name,u.username
  FROM erp_employee_master e
  LEFT JOIN dept d ON d.kd_dept=e.department_code
  LEFT JOIN erp_job_title j ON j.id=e.job_title_id
  LEFT JOIN erp_company_structure cs ON cs.id=e.company_structure_id
  LEFT JOIN erp_cost_center cc ON cc.cost_center_code=e.cost_center_code
  LEFT JOIN erp_profit_center pc ON pc.profit_center_code=e.profit_center_code
  LEFT JOIN erp_employee_master m ON m.id=e.manager_employee_id
  LEFT JOIN sys_users u ON u.id=e.user_id
  $w ORDER BY $orderBy LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<div class="em-action"><button class="btn btn-info btn-xs btn-em-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> <button class="btn btn-primary btn-xs btn-em-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> <button class="btn btn-danger btn-xs btn-em-delete" data-id="'.(int)$r->id.'" data-no="'.emd_h($r->employee_no).'" title="'.hr_h('common_delete', 'Delete').'"><i class="fa fa-trash"></i></button></div>';
  $data[]=array($no++,$act,'<strong>'.emd_h($r->employee_no).'</strong><br><small>Personnel: '.emd_h($r->personnel_no?:'-').' | SAP: '.emd_h($r->sap_reference?:'-').'</small>','<strong>'.emd_h($r->full_name).'</strong><br><small>'.emd_h($r->gender).' | '.emd_h($r->phone?:'-').'</small>','<strong>'.emd_h($r->job_title_code?:'-').'</strong><br><small>'.emd_h($r->job_title_name?:'-').' ['.emd_h($r->job_level?:'-').']</small>','<strong>'.emd_h($r->department_code?:'-').'</strong><br><small>'.emd_h($r->nm_dept?:'-').' | '.emd_h($r->structure_code?:'-').'</small>',emd_h($r->manager_no?($r->manager_no.' - '.$r->manager_name):'-'),emd_h($r->employee_group).'<br><small>'.emd_h($r->employee_subgroup?:'-').' / Grade '.emd_h($r->pay_grade?:'-').'</small>',emd_h($r->hire_date).'<br><small>Valid '.emd_h($r->valid_from.' s/d '.$r->valid_to).'</small>',emd_label($r->employment_status),emd_h(($r->updated_by?:$r->created_by?:'-')).'<br><small>'.emd_h($r->updated_at?:$r->created_at).'</small>');
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'kpi'=>array('total'=>$kpi?(int)$kpi->total:0,'active'=>$kpi?(int)$kpi->active:0,'operators'=>$kpi?(int)$kpi->operators:0,'leaders'=>$kpi?(int)$kpi->leaders:0)));
?>
