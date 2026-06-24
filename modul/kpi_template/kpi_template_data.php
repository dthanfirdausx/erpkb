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
function ktd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function ktd_label($s){$m=array('DRAFT'=>'default','ACTIVE'=>'success','INACTIVE'=>'warning','ARCHIVED'=>'primary');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.ktd_h($s).'</span>';}
function ktd_filters(){return array('as_of'=>isset($_POST['as_of_date'])&&$_POST['as_of_date']!==''?$_POST['as_of_date']:date('Y-m-d'),'template_type'=>isset($_POST['template_type'])?trim($_POST['template_type']):'','cycle_type'=>isset($_POST['cycle_type'])?trim($_POST['cycle_type']):'','department_code'=>isset($_POST['department_code'])?trim($_POST['department_code']):'','job_title_id'=>isset($_POST['job_title_id'])?trim($_POST['job_title_id']):'','employee_group'=>isset($_POST['employee_group'])?trim($_POST['employee_group']):'','status'=>isset($_POST['status'])?trim($_POST['status']):'','weight_status'=>isset($_POST['weight_status'])?trim($_POST['weight_status']):'','keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):'');}
function ktd_where($f,&$p){$w=" WHERE t.effective_from<=? AND t.effective_to>=? ";array_push($p,$f['as_of'],$f['as_of']);foreach(array('template_type','cycle_type','department_code','employee_group','status') as $k){if($f[$k]!==''){$w.=" AND t.$k=? ";$p[]=$f[$k];}}if($f['job_title_id']!==''){$w.=" AND t.job_title_id=? ";$p[]=(int)$f['job_title_id'];}if($f['weight_status']==='COMPLETE')$w.=" AND ROUND(t.total_weight,2)=100.00 ";elseif($f['weight_status']==='INCOMPLETE')$w.=" AND ROUND(t.total_weight,2)<>100.00 ";if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (t.template_no LIKE ? OR t.template_name LIKE ? OR d.nm_dept LIKE ? OR jt.job_title_name LIKE ? OR t.sap_reference LIKE ?) ";for($i=0;$i<5;$i++)$p[]=$kw;}return $w;}
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$f=ktd_filters();$p=array();$w=ktd_where($f,$p);
$base=" FROM erp_kpi_template t LEFT JOIN dept d ON d.kd_dept=t.department_code LEFT JOIN erp_job_title jt ON jt.id=t.job_title_id LEFT JOIN erp_employee_master o ON o.id=t.owner_employee_id ";
$kpi=$db->fetch("SELECT COUNT(*) total,SUM(t.status='ACTIVE') active_count,SUM(ROUND(t.total_weight,2)=100.00) complete_weight,SUM(ROUND(t.total_weight,2)<>100.00) incomplete_weight,COUNT(DISTINCT t.department_code) dept_count $base $w",$p);
$cnt=$db->fetch("SELECT COUNT(*) jml $base $w",$p);$total=$cnt?(int)$cnt->jml:0;
$orderMap=array(2=>'t.template_no',3=>'t.template_name',4=>'t.template_type',5=>'d.kd_dept',6=>'jt.job_title_code',7=>'t.cycle_type',8=>'t.total_weight',9=>'line_count',10=>'t.status',11=>'t.updated_at');$orderBy='t.updated_at DESC,t.id DESC';
if(isset($_POST['order'][0]['column'])){$col=(int)$_POST['order'][0]['column'];$dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;}
$rows=$db->query("SELECT t.*,d.nm_dept,jt.job_title_code,jt.job_title_name,o.employee_no owner_no,o.full_name owner_name,(SELECT COUNT(*) FROM erp_kpi_template_detail x WHERE x.kpi_template_id=t.id) line_count $base $w ORDER BY $orderBy LIMIT $start,$length",$p);
$data=array();$no=$start+1;foreach($rows as $r){$act='<div class="kt-action"><button class="btn btn-info btn-xs btn-kt-detail" data-id="'.(int)$r->id.'"><i class="fa fa-eye"></i></button> <button class="btn btn-primary btn-xs btn-kt-edit" data-id="'.(int)$r->id.'"><i class="fa fa-pencil"></i></button> <button class="btn btn-danger btn-xs btn-kt-delete" data-id="'.(int)$r->id.'" data-no="'.ktd_h($r->template_no).'"><i class="fa fa-trash"></i></button></div>';
  $weight=(float)$r->total_weight;$weightLabel=$weight==100.0?'success':'warning';
  $data[]=array($no++,$act,'<strong>'.ktd_h($r->template_no).'</strong><br><small>'.ktd_h($r->sap_reference?:'-').'</small>','<strong>'.ktd_h($r->template_name).'</strong><br><small>'.ktd_h($r->remarks?:'-').'</small>',ktd_h($r->template_type).'<br><small>'.ktd_h($r->employee_group).'</small>','<strong>'.ktd_h($r->department_code?:'-').'</strong><br><small>'.ktd_h($r->nm_dept?:'-').'</small>','<strong>'.ktd_h($r->job_title_code?:'-').'</strong><br><small>'.ktd_h($r->job_title_name?:'-').'</small>',ktd_h($r->cycle_type).'<br><small>'.ktd_h($r->appraisal_period).'</small>','<span class="label label-'.$weightLabel.'">'.number_format($weight,2).'%</span>','<span class="badge bg-blue">'.(int)$r->line_count.'</span>',ktd_label($r->status).'<br><small>'.ktd_h($r->effective_from.' s/d '.$r->effective_to).'</small>',ktd_h($r->updated_by?:$r->created_by?:'-').'<br><small>'.ktd_h($r->updated_at?:$r->created_at).'</small>');
}
header('Content-Type: application/json; charset=utf-8');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'kpi'=>array('total'=>$kpi?(int)$kpi->total:0,'active'=>$kpi?(int)$kpi->active_count:0,'complete'=>$kpi?(int)$kpi->complete_weight:0,'incomplete'=>$kpi?(int)$kpi->incomplete_weight:0,'departments'=>$kpi?(int)$kpi->dept_count:0)));
?>
