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

function jtd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function jtd_label($s){
  $map=array('DRAFT'=>'default','ACTIVE'=>'success','INACTIVE'=>'warning');
  $c=isset($map[$s])?$map[$s]:'default';
  return '<span class="label label-'.$c.'">'.jtd_h($s).'</span>';
}
function jtd_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:'9999-12-31',
    'job_family'=>isset($_POST['job_family'])?trim($_POST['job_family']):'',
    'job_level'=>isset($_POST['job_level'])?trim($_POST['job_level']):'',
    'department_code'=>isset($_POST['department_code'])?trim($_POST['department_code']):'',
    'status'=>isset($_POST['status'])?trim($_POST['status']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function jtd_where($f,&$p){
  $w=" WHERE jt.valid_from<=? AND jt.valid_to>=? ";
  $p[]=$f['to'];$p[]=$f['from'];
  if($f['job_family']!==''){$w.=" AND jt.job_family=? ";$p[]=$f['job_family'];}
  if($f['job_level']!==''){$w.=" AND jt.job_level=? ";$p[]=$f['job_level'];}
  if($f['department_code']!==''){$w.=" AND jt.department_code=? ";$p[]=$f['department_code'];}
  if($f['status']!==''){$w.=" AND jt.status=? ";$p[]=$f['status'];}
  if($f['keyword']!==''){
    $kw='%'.$f['keyword'].'%';
    $w.=" AND (jt.job_title_code LIKE ? OR jt.job_title_name LIKE ? OR jt.job_title_short_name LIKE ? OR jt.pay_grade LIKE ? OR jt.sap_reference LIKE ? OR d.nm_dept LIKE ? OR cc.cost_center_name LIKE ? OR pc.profit_center_name LIKE ?) ";
    for($i=0;$i<8;$i++)$p[]=$kw;
  }
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$f=jtd_filters();$p=array();$w=jtd_where($f,$p);

$kpi=$db->fetch("SELECT COUNT(*) total,SUM(status='ACTIVE') active,SUM(job_family IN ('MANAGEMENT','SUPERVISOR','EXECUTIVE')) leadership,SUM(headcount_plan) headcount_plan FROM erp_job_title");
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_job_title jt LEFT JOIN dept d ON d.kd_dept=jt.department_code LEFT JOIN erp_cost_center cc ON cc.cost_center_code=jt.cost_center_code LEFT JOIN erp_profit_center pc ON pc.profit_center_code=jt.profit_center_code $w",$p);
$total=$cnt?(int)$cnt->jml:0;

$orderMap=array(2=>'jt.job_title_code',3=>'jt.job_family',4=>'jt.job_level',5=>'d.kd_dept',6=>'rt.job_title_code',7=>'jt.cost_center_code',8=>'jt.headcount_plan',9=>'jt.status',10=>'jt.updated_at');
$orderBy="jt.job_level DESC, jt.job_family, jt.job_title_code";
if(isset($_POST['order'][0]['column'])){
  $col=(int)$_POST['order'][0]['column'];
  $dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';
  if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;
}

$rows=$db->query("SELECT jt.*,d.nm_dept,cs.structure_code,cs.structure_name,rt.job_title_code reports_to_code,rt.job_title_name reports_to_name,cc.cost_center_name,pc.profit_center_name
  FROM erp_job_title jt
  LEFT JOIN dept d ON d.kd_dept=jt.department_code
  LEFT JOIN erp_company_structure cs ON cs.id=jt.company_structure_id
  LEFT JOIN erp_job_title rt ON rt.id=jt.reports_to_job_title_id
  LEFT JOIN erp_cost_center cc ON cc.cost_center_code=jt.cost_center_code
  LEFT JOIN erp_profit_center pc ON pc.profit_center_code=jt.profit_center_code
  $w
  ORDER BY $orderBy
  LIMIT $start,$length",$p);

$data=array();$no=$start+1;
foreach($rows as $r){
  $nextStatus=$r->status==='ACTIVE'?'INACTIVE':'ACTIVE';
  $statusClass=$r->status==='ACTIVE'?'warning':'success';
  $statusIcon=$r->status==='ACTIVE'?'fa-ban':'fa-check';
  $act='<div class="jt-action"><button class="btn btn-info btn-xs btn-jt-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> ';
  $act.='<button class="btn btn-primary btn-xs btn-jt-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> ';
  $act.='<button class="btn btn-'.$statusClass.' btn-xs btn-jt-status" data-id="'.(int)$r->id.'" data-status="'.$nextStatus.'" title="'.$nextStatus.'"><i class="fa '.$statusIcon.'"></i></button> ';
  $act.='<button class="btn btn-danger btn-xs btn-jt-delete" data-id="'.(int)$r->id.'" data-no="'.jtd_h($r->job_title_code).'" title="'.hr_h('common_delete', 'Delete').'"><i class="fa fa-trash"></i></button></div>';
  $dept=$r->department_code?'<strong>'.jtd_h($r->department_code).'</strong><br><small>'.jtd_h($r->nm_dept).'</small>':'<span class="text-muted">Not assigned</span>';
  $reports=$r->reports_to_code?'<strong>'.jtd_h($r->reports_to_code).'</strong><br><small>'.jtd_h($r->reports_to_name).'</small>':'<span class="text-muted">Root</span>';
  $cost='<strong>'.jtd_h($r->cost_center_code?:'-').'</strong><br><small>'.jtd_h($r->cost_center_name?:'').'</small><br><small>Profit: '.jtd_h($r->profit_center_code?:'-').' '.jtd_h($r->profit_center_name?:'').'</small>';
  $data[]=array(
    $no++,
    $act,
    '<strong>'.jtd_h($r->job_title_code).'</strong><br><small>'.jtd_h($r->job_title_name).'</small><br><small class="text-muted">'.jtd_h($r->job_title_short_name?:'-').' | SAP: '.jtd_h($r->sap_reference?:'-').'</small>',
    '<span class="jt-pill">'.jtd_h($r->job_family).'</span><br><small>'.jtd_h($r->employee_group.' / '.$r->employee_subgroup).'</small>',
    '<strong>'.jtd_h($r->job_level).'</strong><br><small>Grade: '.jtd_h($r->pay_grade?:'-').'</small>',
    $dept.'<br><small class="text-muted">'.jtd_h($r->structure_code?:'-').'</small>',
    $reports,
    $cost,
    (int)$r->headcount_plan.'<br><small>'.jtd_h($r->work_location_type).'</small>',
    jtd_label($r->status),
    jtd_h(($r->updated_by?:$r->created_by?:'-')).'<br><small>'.jtd_h($r->updated_at?:$r->created_at).'</small>'
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
    'active'=>$kpi?(int)$kpi->active:0,
    'leadership'=>$kpi?(int)$kpi->leadership:0,
    'headcount_plan'=>$kpi?(int)$kpi->headcount_plan:0
  )
));
?>
