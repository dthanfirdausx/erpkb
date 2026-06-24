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

function mpd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function mpd_label($s){
  $m=array('DRAFT'=>'default','SUBMITTED'=>'warning','APPROVED'=>'success','REJECTED'=>'danger','CLOSED'=>'primary');
  $c=isset($m[$s])?$m[$s]:'default';
  return '<span class="label label-'.$c.'">'.mpd_h($s).'</span>';
}
function mpd_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-12-31'),
    'type'=>isset($_POST['planning_type'])?trim($_POST['planning_type']):'',
    'status'=>isset($_POST['planning_status'])?trim($_POST['planning_status']):'',
    'department_code'=>isset($_POST['department_code'])?trim($_POST['department_code']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function mpd_where($f,&$p){
  $w=" WHERE p.period_from<=? AND p.period_to>=? ";$p[]=$f['to'];$p[]=$f['from'];
  if($f['type']!==''){$w.=" AND p.planning_type=? ";$p[]=$f['type'];}
  if($f['status']!==''){$w.=" AND p.planning_status=? ";$p[]=$f['status'];}
  if($f['department_code']!==''){$w.=" AND (p.department_code=? OR EXISTS(SELECT 1 FROM erp_manpower_plan_detail x WHERE x.plan_id=p.id AND x.department_code=?)) ";$p[]=$f['department_code'];$p[]=$f['department_code'];}
  if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (p.plan_no LIKE ? OR p.plan_name LIKE ? OR p.sap_reference LIKE ? OR p.remarks LIKE ?) ";array_push($p,$kw,$kw,$kw,$kw);}
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$f=mpd_filters();$p=array();$w=mpd_where($f,$p);

$kpi=$db->fetch("SELECT COUNT(*) total,SUM(planning_status='DRAFT') draft_count,SUM(planning_status='SUBMITTED') submitted_count,SUM(planning_status='APPROVED') approved_count,SUM(total_requested_headcount) requested_hc,SUM(total_gap_headcount) gap_hc,SUM(total_budget_amount) budget FROM erp_manpower_plan p $w",$p);
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_manpower_plan p $w",$p);
$total=$cnt?(int)$cnt->jml:0;

$orderMap=array(2=>'p.plan_no',3=>'p.plan_name',4=>'p.planning_type',5=>'p.period_from',6=>'p.total_requested_headcount',7=>'p.total_gap_headcount',8=>'p.total_budget_amount',9=>'p.planning_status',10=>'p.updated_at');
$orderBy='p.period_from DESC,p.plan_no DESC';
if(isset($_POST['order'][0]['column'])){
  $col=(int)$_POST['order'][0]['column'];
  $dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';
  if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;
}

$rows=$db->query("SELECT p.*,d.nm_dept,cs.structure_code,cs.structure_name,cc.cost_center_name,pc.profit_center_name,e.employee_no,e.full_name
  FROM erp_manpower_plan p
  LEFT JOIN dept d ON d.kd_dept=p.department_code
  LEFT JOIN erp_company_structure cs ON cs.id=p.company_structure_id
  LEFT JOIN erp_cost_center cc ON cc.cost_center_code=p.cost_center_code
  LEFT JOIN erp_profit_center pc ON pc.profit_center_code=p.profit_center_code
  LEFT JOIN erp_employee_master e ON e.id=p.approved_by_employee_id
  $w ORDER BY $orderBy LIMIT $start,$length",$p);

$data=array();$no=$start+1;
foreach($rows as $r){
  $canDelete=$r->planning_status==='DRAFT';
  $act='<div class="mp-action"><button class="btn btn-info btn-xs btn-mp-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> ';
  $act.='<button class="btn btn-primary btn-xs btn-mp-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> ';
  $act.='<button class="btn btn-warning btn-xs btn-mp-status" data-id="'.(int)$r->id.'" data-status="SUBMITTED" title="'.hr_h('common_submit', 'Submit').'"><i class="fa fa-send"></i></button> ';
  $act.='<button class="btn btn-success btn-xs btn-mp-status" data-id="'.(int)$r->id.'" data-status="APPROVED" title="'.hr_h('common_approve', 'Approve').'"><i class="fa fa-check"></i></button> ';
  $act.='<button class="btn btn-danger btn-xs btn-mp-delete" data-id="'.(int)$r->id.'" data-no="'.mpd_h($r->plan_no).'" title="'.hr_h('common_delete', 'Delete').'"'.($canDelete?'':' disabled').'><i class="fa fa-trash"></i></button></div>';
  $org=($r->structure_code?'<strong>'.mpd_h($r->structure_code).'</strong><br><small>'.mpd_h($r->structure_name).'</small>':'<span class="text-muted">All Org</span>');
  $dept=$r->department_code?'<strong>'.mpd_h($r->department_code).'</strong><br><small>'.mpd_h($r->nm_dept).'</small>':'<span class="text-muted">All Dept</span>';
  $data[]=array(
    $no++,
    $act,
    '<strong>'.mpd_h($r->plan_no).'</strong><br><small>'.mpd_h($r->sap_reference?:'-').'</small>',
    '<strong>'.mpd_h($r->plan_name).'</strong><br><small>'.mpd_h('Version '.$r->plan_version.' / '.$r->plan_year).'</small>',
    '<span class="label label-info">'.mpd_h($r->planning_type).'</span><br><small>'.mpd_h($r->period_from.' s/d '.$r->period_to).'</small>',
    $org.'<br>'.$dept,
    '<strong>'.number_format((float)$r->total_current_headcount,2).'</strong> / '.number_format((float)$r->total_planned_headcount,2).'<br><small>Current / Planned</small>',
    '<strong>'.number_format((float)$r->total_requested_headcount,2).'</strong><br><small>Gap '.number_format((float)$r->total_gap_headcount,2).'</small>',
    '<strong>'.mpd_h($r->budget_currency).'</strong> '.number_format((float)$r->total_budget_amount,2).'<br><small>'.mpd_h($r->cost_center_code?:'-').' / '.mpd_h($r->profit_center_code?:'-').'</small>',
    mpd_label($r->planning_status).'<br><small>'.mpd_h($r->employee_no?($r->employee_no.' - '.$r->full_name):'-').'</small>',
    mpd_h($r->updated_by?:$r->created_by?:'-').'<br><small>'.mpd_h($r->updated_at?:$r->created_at).'</small>'
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
  'draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,
  'kpi'=>array(
    'total'=>$kpi?(int)$kpi->total:0,
    'draft'=>$kpi?(int)$kpi->draft_count:0,
    'submitted'=>$kpi?(int)$kpi->submitted_count:0,
    'approved'=>$kpi?(int)$kpi->approved_count:0,
    'requested_hc'=>$kpi?number_format((float)$kpi->requested_hc,2):'0.00',
    'gap_hc'=>$kpi?number_format((float)$kpi->gap_hc,2):'0.00',
    'budget'=>$kpi?number_format((float)$kpi->budget,0):'0'
  )
));
?>
