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

function tpd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function tpd_label($s,$type='approval'){
  $map=array('DRAFT'=>'default','SUBMITTED'=>'info','APPROVED'=>'success','REJECTED'=>'danger','CANCELLED'=>'warning','COMPLETED'=>'primary','NOT_STARTED'=>'default','SCHEDULED'=>'info','IN_PROGRESS'=>'warning');
  $c=isset($map[$s])?$map[$s]:'default';
  return '<span class="label label-'.$c.'">'.tpd_h($s).'</span>';
}
function tpd_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-12-31'),
    'plan_year'=>isset($_POST['plan_year'])?trim($_POST['plan_year']):'',
    'training_catalog_id'=>isset($_POST['training_catalog_id'])?trim($_POST['training_catalog_id']):'',
    'target_department_code'=>isset($_POST['target_department_code'])?trim($_POST['target_department_code']):'',
    'priority'=>isset($_POST['priority'])?trim($_POST['priority']):'',
    'approval_status'=>isset($_POST['approval_status'])?trim($_POST['approval_status']):'',
    'execution_status'=>isset($_POST['execution_status'])?trim($_POST['execution_status']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function tpd_where($f,&$p){
  $w=" WHERE tp.planned_start_date<=? AND tp.planned_end_date>=? ";
  $p[]=$f['to'];$p[]=$f['from'];
  foreach(array('plan_year','training_catalog_id','target_department_code','priority','approval_status','execution_status') as $k){
    if($f[$k]!==''){$w.=" AND tp.$k=? ";$p[]=$f[$k];}
  }
  if($f['keyword']!==''){
    $kw='%'.$f['keyword'].'%';
    $w.=" AND (tp.plan_code LIKE ? OR tp.plan_name LIKE ? OR tc.training_code LIKE ? OR tc.training_name LIKE ? OR d.nm_dept LIKE ? OR jt.job_title_name LIKE ? OR tp.plan_owner LIKE ?) ";
    for($i=0;$i<7;$i++)$p[]=$kw;
  }
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$f=tpd_filters();$p=array();$w=tpd_where($f,$p);

$kpi=$db->fetch("SELECT COUNT(*) total,SUM(CASE WHEN approval_status='APPROVED' THEN 1 ELSE 0 END) approved,SUM(CASE WHEN execution_status IN ('SCHEDULED','IN_PROGRESS') THEN 1 ELSE 0 END) scheduled,SUM(CASE WHEN priority IN ('HIGH','CRITICAL') THEN 1 ELSE 0 END) high_prio,SUM(planned_participant) planned_participant,SUM(budget_amount) budget FROM erp_training_plan");
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_training_plan tp LEFT JOIN erp_training_catalog tc ON tc.id=tp.training_catalog_id LEFT JOIN dept d ON d.kd_dept=tp.target_department_code LEFT JOIN erp_job_title jt ON jt.id=tp.target_job_title_id $w",$p);
$total=$cnt?(int)$cnt->jml:0;

$orderMap=array(2=>'tp.plan_code',3=>'tc.training_name',4=>'tp.planned_start_date',5=>'tp.target_department_code',6=>'tp.priority',7=>'tp.budget_amount',8=>'participant_count',9=>'tp.approval_status',10=>'tp.updated_at');
$orderBy="tp.planned_start_date DESC, tp.plan_code DESC";
if(isset($_POST['order'][0]['column'])){
  $col=(int)$_POST['order'][0]['column'];
  $dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';
  if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;
}

$rows=$db->query("SELECT tp.*,tc.training_code,tc.training_name,tc.training_category,tc.delivery_method,d.nm_dept,jt.job_title_code,jt.job_title_name,COUNT(tpp.id) participant_count
  FROM erp_training_plan tp
  LEFT JOIN erp_training_catalog tc ON tc.id=tp.training_catalog_id
  LEFT JOIN dept d ON d.kd_dept=tp.target_department_code
  LEFT JOIN erp_job_title jt ON jt.id=tp.target_job_title_id
  LEFT JOIN erp_training_plan_participant tpp ON tpp.training_plan_id=tp.id
  $w
  GROUP BY tp.id
  ORDER BY $orderBy
  LIMIT $start,$length",$p);

$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<div class="tp-action"><button class="btn btn-info btn-xs btn-tp-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> ';
  $act.='<button class="btn btn-primary btn-xs btn-tp-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> ';
  $act.='<button class="btn btn-success btn-xs btn-tp-status" data-id="'.(int)$r->id.'" data-status="APPROVED" title="'.hr_h('common_approve', 'Approve').'"><i class="fa fa-check"></i></button> ';
  $act.='<button class="btn btn-danger btn-xs btn-tp-delete" data-id="'.(int)$r->id.'" data-no="'.tpd_h($r->plan_code).'" title="'.hr_h('common_delete', 'Delete').'"><i class="fa fa-trash"></i></button></div>';
  $target=$r->target_department_code?'<strong>'.tpd_h($r->target_department_code).'</strong> - '.tpd_h($r->nm_dept):'<span class="text-muted">All Department</span>';
  if($r->job_title_code)$target.='<br><small>Job: '.tpd_h($r->job_title_code.' - '.$r->job_title_name).'</small>';
  if($r->target_employee_group)$target.='<br><small>Group: '.tpd_h($r->target_employee_group).'</small>';
  $data[]=array(
    $no++,
    $act,
    '<strong>'.tpd_h($r->plan_code).'</strong><br><small>'.tpd_h($r->plan_name).'</small><br><small class="text-muted">'.tpd_h($r->plan_year.' / '.$r->plan_period).'</small>',
    '<strong>'.tpd_h($r->training_code).'</strong><br><small>'.tpd_h($r->training_name).'</small><br><span class="tp-pill">'.tpd_h($r->training_category).'</span> <small>'.tpd_h($r->delivery_method).'</small>',
    tpd_h($r->planned_start_date.' s/d '.$r->planned_end_date).'<br><small>'.tpd_h($r->source_type).'</small>',
    $target,
    '<strong>'.tpd_h($r->priority).'</strong><br><small>Owner: '.tpd_h($r->plan_owner?:'-').'</small>',
    tpd_h($r->currency).' '.number_format((float)$r->budget_amount,2).'<br><small>Planned: '.(int)$r->planned_participant.'</small>',
    '<span class="badge bg-blue">'.(int)$r->participant_count.'</span> peserta',
    tpd_label($r->approval_status).'<br>'.tpd_label($r->execution_status,'exec'),
    tpd_h(($r->updated_by?:$r->created_by?:'-')).'<br><small>'.tpd_h($r->updated_at?:$r->created_at).'</small>'
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
    'approved'=>$kpi?(int)$kpi->approved:0,
    'scheduled'=>$kpi?(int)$kpi->scheduled:0,
    'high_priority'=>$kpi?(int)$kpi->high_prio:0,
    'planned_participant'=>$kpi?(int)$kpi->planned_participant:0,
    'budget'=>$kpi?(float)$kpi->budget:0
  )
));
?>
