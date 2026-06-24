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
function efd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function efd_label($s){$c=$s==='ACTIVE'?'success':'warning';return '<span class="label label-'.$c.'">'.efd_h($s).'</span>';}
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$from=isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01');$to=isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:'9999-12-31';$rel=isset($_POST['relationship_type'])?trim($_POST['relationship_type']):'';$dep=isset($_POST['is_dependent'])?trim($_POST['is_dependent']):'';$emer=isset($_POST['emergency_contact'])?trim($_POST['emergency_contact']):'';$status=isset($_POST['status'])?trim($_POST['status']):'';$kw=isset($_POST['keyword'])?trim($_POST['keyword']):'';
$w=" WHERE f.effective_from<=? AND f.effective_to>=? ";$p=array($to,$from);if($rel!==''){$w.=" AND f.relationship_type=? ";$p[]=$rel;}if($dep!==''){$w.=" AND f.is_dependent=? ";$p[]=$dep;}if($emer!==''){$w.=" AND f.emergency_contact=? ";$p[]=$emer;}if($status!==''){$w.=" AND f.status=? ";$p[]=$status;}if($kw!==''){$like='%'.$kw.'%';$w.=" AND (f.family_no LIKE ? OR f.family_name LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ? OR f.identity_no LIKE ?) ";array_push($p,$like,$like,$like,$like,$like);}
$kpi=$db->fetch("SELECT COUNT(*) total,SUM(status='ACTIVE') active,SUM(is_dependent='Y') dependent,SUM(emergency_contact='Y') emergency,SUM(benefit_eligible='Y') benefit FROM erp_employee_family_data");
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_employee_family_data f JOIN erp_employee_master e ON e.id=f.employee_id $w",$p);$total=$cnt?(int)$cnt->jml:0;
$orderMap=array(2=>'f.family_no',3=>'e.employee_no',4=>'f.relationship_type',5=>'f.family_name',6=>'f.birth_date',7=>'f.is_dependent',8=>'f.emergency_contact',9=>'f.status',10=>'f.updated_at');$orderBy='e.employee_no,f.relationship_type,f.family_name';if(isset($_POST['order'][0]['column'])){$col=(int)$_POST['order'][0]['column'];$dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;}
$rows=$db->query("SELECT f.*,e.employee_no,e.full_name,e.department_code,d.nm_dept,j.job_title_code,j.job_title_name FROM erp_employee_family_data f JOIN erp_employee_master e ON e.id=f.employee_id LEFT JOIN dept d ON d.kd_dept=e.department_code LEFT JOIN erp_job_title j ON j.id=e.job_title_id $w ORDER BY $orderBy LIMIT $start,$length",$p);
$data=array();$no=$start+1;foreach($rows as $r){$next=$r->status==='ACTIVE'?'INACTIVE':'ACTIVE';$btn=$r->status==='ACTIVE'?'warning':'success';$icon=$r->status==='ACTIVE'?'fa-ban':'fa-check';$act='<div class="ef-action"><button class="btn btn-info btn-xs btn-ef-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> <button class="btn btn-primary btn-xs btn-ef-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> <button class="btn btn-'.$btn.' btn-xs btn-ef-status" data-id="'.(int)$r->id.'" data-status="'.$next.'" title="'.$next.'"><i class="fa '.$icon.'"></i></button> <button class="btn btn-danger btn-xs btn-ef-delete" data-id="'.(int)$r->id.'" data-no="'.efd_h($r->family_no).'" title="'.hr_h('common_delete', 'Delete').'"><i class="fa fa-trash"></i></button></div>';
  $flags='Dependent: '.efd_h($r->is_dependent).' | Tax: '.efd_h($r->tax_dependent).' | BPJS: '.efd_h($r->bpjs_dependent);
  $data[]=array($no++,$act,'<strong>'.efd_h($r->family_no).'</strong><br><small>SAP: '.efd_h($r->sap_reference?:'-').'</small>','<strong>'.efd_h($r->employee_no).'</strong><br><small>'.efd_h($r->full_name).'</small><br><small>'.efd_h($r->department_code?:'-').' '.efd_h($r->nm_dept?:'').'</small>',efd_h($r->relationship_type),'<strong>'.efd_h($r->family_name).'</strong><br><small>'.efd_h($r->gender.' / '.$r->marital_status).'</small>',efd_h(($r->birth_place?:'-').', '.($r->birth_date?:'-')).'<br><small>'.efd_h($r->identity_type.' '.$r->identity_no).'</small>',efd_h($flags),efd_h('Emergency: '.$r->emergency_contact.' | Benefit: '.$r->benefit_eligible),efd_label($r->status),efd_h($r->updated_by?:$r->created_by?:'-').'<br><small>'.efd_h($r->updated_at?:$r->created_at).'</small>');}
header('Content-Type: application/json; charset=utf-8');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'kpi'=>array('total'=>$kpi?(int)$kpi->total:0,'active'=>$kpi?(int)$kpi->active:0,'dependent'=>$kpi?(int)$kpi->dependent:0,'emergency'=>$kpi?(int)$kpi->emergency:0,'benefit'=>$kpi?(int)$kpi->benefit:0)));
?>
