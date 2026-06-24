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

function pd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function pd_label($s,$type='status'){
  $m=$type==='vacancy'?array('VACANT'=>'warning','OCCUPIED'=>'success','PARTIAL'=>'info','OVERSTAFFED'=>'danger','FROZEN'=>'default'):array('PLANNED'=>'default','APPROVED'=>'primary','ACTIVE'=>'success','INACTIVE'=>'warning','OBSOLETE'=>'danger');
  $c=isset($m[$s])?$m[$s]:'default';
  return '<span class="label label-'.$c.'">'.pd_h($s).'</span>';
}
function pd_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:'9999-12-31',
    'department_code'=>isset($_POST['department_code'])?trim($_POST['department_code']):'',
    'position_status'=>isset($_POST['position_status'])?trim($_POST['position_status']):'',
    'vacancy_status'=>isset($_POST['vacancy_status'])?trim($_POST['vacancy_status']):'',
    'position_type'=>isset($_POST['position_type'])?trim($_POST['position_type']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function pd_where($f,&$p){
  $w=" WHERE p.valid_from<=? AND p.valid_to>=? ";$p[]=$f['to'];$p[]=$f['from'];
  if($f['department_code']!==''){$w.=" AND p.department_code=? ";$p[]=$f['department_code'];}
  if($f['position_status']!==''){$w.=" AND p.position_status=? ";$p[]=$f['position_status'];}
  if($f['vacancy_status']!==''){$w.=" AND p.vacancy_status=? ";$p[]=$f['vacancy_status'];}
  if($f['position_type']!==''){$w.=" AND p.position_type=? ";$p[]=$f['position_type'];}
  if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (p.position_code LIKE ? OR p.position_name LIKE ? OR jt.job_title_code LIKE ? OR jt.job_title_name LIKE ? OR d.nm_dept LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ?) ";for($i=0;$i<7;$i++)$p[]=$kw;}
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$f=pd_filters();$p=array();$w=pd_where($f,$p);

$kpi=$db->fetch("SELECT COUNT(*) total,SUM(position_status='ACTIVE') active,SUM(vacancy_status='VACANT') vacant,SUM(vacancy_status='OCCUPIED') occupied,SUM(headcount_plan) headcount_plan,SUM(planned_fte) planned_fte,SUM(occupied_fte) occupied_fte FROM erp_position");
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_position p LEFT JOIN erp_job_title jt ON jt.id=p.job_title_id LEFT JOIN dept d ON d.kd_dept=p.department_code LEFT JOIN erp_employee_master e ON e.id=p.holder_employee_id $w",$p);
$total=$cnt?(int)$cnt->jml:0;

$orderMap=array(2=>'p.position_code',3=>'jt.job_title_code',4=>'d.kd_dept',5=>'rp.position_code',6=>'e.employee_no',7=>'p.vacancy_status',8=>'p.position_status',9=>'p.planned_fte',10=>'p.updated_at');
$orderBy="p.position_code";
if(isset($_POST['order'][0]['column'])){
  $col=(int)$_POST['order'][0]['column'];
  $dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';
  if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;
}

$rows=$db->query("SELECT p.*,jt.job_title_code,jt.job_title_name,jt.job_level,d.nm_dept,cs.structure_code,cs.structure_name,rp.position_code reports_to_code,rp.position_name reports_to_name,e.employee_no holder_no,e.full_name holder_name,cc.cost_center_name,pc.profit_center_name,wl.location_code,wl.location_name
  FROM erp_position p
  LEFT JOIN erp_job_title jt ON jt.id=p.job_title_id
  LEFT JOIN dept d ON d.kd_dept=p.department_code
  LEFT JOIN erp_company_structure cs ON cs.id=p.company_structure_id
  LEFT JOIN erp_position rp ON rp.id=p.reports_to_position_id
  LEFT JOIN erp_employee_master e ON e.id=p.holder_employee_id
  LEFT JOIN erp_cost_center cc ON cc.cost_center_code=p.cost_center_code
  LEFT JOIN erp_profit_center pc ON pc.profit_center_code=p.profit_center_code
  LEFT JOIN erp_work_location wl ON wl.id=p.work_location_id
  $w ORDER BY $orderBy LIMIT $start,$length",$p);

$data=array();$no=$start+1;
foreach($rows as $r){
  $next=$r->position_status==='ACTIVE'?'INACTIVE':'ACTIVE';
  $btnClass=$r->position_status==='ACTIVE'?'warning':'success';
  $btnIcon=$r->position_status==='ACTIVE'?'fa-ban':'fa-check';
  $act='<div class="pos-action"><button class="btn btn-info btn-xs btn-pos-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> ';
  $act.='<button class="btn btn-primary btn-xs btn-pos-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> ';
  $act.='<button class="btn btn-'.$btnClass.' btn-xs btn-pos-status" data-id="'.(int)$r->id.'" data-status="'.$next.'" title="'.$next.'"><i class="fa '.$btnIcon.'"></i></button> ';
  $act.='<button class="btn btn-danger btn-xs btn-pos-delete" data-id="'.(int)$r->id.'" data-no="'.pd_h($r->position_code).'" title="'.hr_h('common_delete', 'Delete').'"><i class="fa fa-trash"></i></button></div>';
  $data[]=array(
    $no++,
    $act,
    '<strong>'.pd_h($r->position_code).'</strong><br><small>'.pd_h($r->position_name).'</small><br><small class="text-muted">'.pd_h($r->position_type.' / '.$r->position_category).'</small>',
    '<strong>'.pd_h($r->job_title_code?:'-').'</strong><br><small>'.pd_h($r->job_title_name?:'').'</small><br><small>Level: '.pd_h($r->job_level?:'-').' | Grade: '.pd_h($r->pay_grade?:'-').'</small>',
    '<strong>'.pd_h($r->department_code?:'-').'</strong><br><small>'.pd_h($r->nm_dept?:'').'</small><br><small class="text-muted">'.pd_h($r->structure_code?:'-').'</small>',
    $r->reports_to_code?'<strong>'.pd_h($r->reports_to_code).'</strong><br><small>'.pd_h($r->reports_to_name).'</small>':'<span class="text-muted">Root</span>',
    $r->holder_no?'<strong>'.pd_h($r->holder_no).'</strong><br><small>'.pd_h($r->holder_name).'</small>':'<span class="text-muted">Vacant</span>',
    pd_label($r->vacancy_status,'vacancy'),
    pd_label($r->position_status),
    '<strong>'.pd_h($r->occupied_fte.' / '.$r->planned_fte).'</strong><br><small>HC: '.(int)$r->headcount_plan.'</small>',
    '<strong>'.pd_h($r->cost_center_code?:'-').'</strong><br><small>'.pd_h($r->cost_center_name?:'').'</small><br><small>Profit: '.pd_h($r->profit_center_code?:'-').'</small>',
    pd_h($r->updated_by?:$r->created_by?:'-').'<br><small>'.pd_h($r->updated_at?:$r->created_at).'</small>'
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
    'vacant'=>$kpi?(int)$kpi->vacant:0,
    'occupied'=>$kpi?(int)$kpi->occupied:0,
    'headcount_plan'=>$kpi?(int)$kpi->headcount_plan:0,
    'planned_fte'=>$kpi?number_format((float)$kpi->planned_fte,2):'0.00',
    'occupied_fte'=>$kpi?number_format((float)$kpi->occupied_fte,2):'0.00'
  )
));
?>
