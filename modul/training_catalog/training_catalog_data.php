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

function tcd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function tcd_label($s){
  $map=array('DRAFT'=>'default','ACTIVE'=>'success','INACTIVE'=>'warning','OBSOLETE'=>'danger');
  $c=isset($map[$s])?$map[$s]:'default';
  return '<span class="label label-'.$c.'">'.tcd_h($s).'</span>';
}
function tcd_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-m-d'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:'9999-12-31',
    'training_category'=>isset($_POST['training_category'])?trim($_POST['training_category']):'',
    'delivery_method'=>isset($_POST['delivery_method'])?trim($_POST['delivery_method']):'',
    'training_type'=>isset($_POST['training_type'])?trim($_POST['training_type']):'',
    'provider_type'=>isset($_POST['provider_type'])?trim($_POST['provider_type']):'',
    'owner_department_code'=>isset($_POST['owner_department_code'])?trim($_POST['owner_department_code']):'',
    'status'=>isset($_POST['status'])?trim($_POST['status']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function tcd_where($f,&$p){
  $w=" WHERE tc.valid_from<=? AND tc.valid_to>=? ";
  $p[]=$f['to'];$p[]=$f['from'];
  foreach(array('training_category','delivery_method','training_type','provider_type','owner_department_code','status') as $k){
    if($f[$k]!==''){$w.=" AND tc.$k=? ";$p[]=$f[$k];}
  }
  if($f['keyword']!==''){
    $kw='%'.$f['keyword'].'%';
    $w.=" AND (tc.training_code LIKE ? OR tc.training_name LIKE ? OR tc.provider_name LIKE ? OR tc.target_audience LIKE ? OR tc.competency_area LIKE ? OR tc.sap_reference LIKE ? OR d.nm_dept LIKE ? OR cc.cost_center_name LIKE ?) ";
    for($i=0;$i<8;$i++)$p[]=$kw;
  }
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$f=tcd_filters();$p=array();$w=tcd_where($f,$p);

$kpi=$db->fetch("SELECT COUNT(*) total,SUM(status='ACTIVE') active,SUM(training_type='MANDATORY') mandatory,SUM(training_type='CERTIFICATION') certification,ROUND(AVG(NULLIF(duration_hours,0)),2) avg_duration,SUM(cost_estimate) total_cost FROM erp_training_catalog");
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_training_catalog tc LEFT JOIN dept d ON d.kd_dept=tc.owner_department_code LEFT JOIN erp_cost_center cc ON cc.cost_center_code=tc.cost_center_code $w",$p);
$total=$cnt?(int)$cnt->jml:0;

$orderMap=array(2=>'tc.training_code',3=>'tc.training_category',4=>'tc.delivery_method',5=>'tc.provider_name',6=>'tc.duration_hours',7=>'tc.assessment_required',8=>'tc.valid_from',9=>'tc.status',10=>'tc.updated_at');
$orderBy="tc.status='ACTIVE' DESC, tc.training_category, tc.training_code";
if(isset($_POST['order'][0]['column'])){
  $col=(int)$_POST['order'][0]['column'];
  $dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';
  if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;
}

$rows=$db->query("SELECT tc.*,d.nm_dept,cc.cost_center_name
  FROM erp_training_catalog tc
  LEFT JOIN dept d ON d.kd_dept=tc.owner_department_code
  LEFT JOIN erp_cost_center cc ON cc.cost_center_code=tc.cost_center_code
  $w
  ORDER BY $orderBy
  LIMIT $start,$length",$p);

$data=array();$no=$start+1;
foreach($rows as $r){
  $nextStatus=$r->status==='ACTIVE'?'INACTIVE':'ACTIVE';
  $statusClass=$r->status==='ACTIVE'?'warning':'success';
  $statusIcon=$r->status==='ACTIVE'?'fa-ban':'fa-check';
  $act='<div class="tc-action"><button class="btn btn-info btn-xs btn-tc-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> ';
  $act.='<button class="btn btn-primary btn-xs btn-tc-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> ';
  $act.='<button class="btn btn-'.$statusClass.' btn-xs btn-tc-status" data-id="'.(int)$r->id.'" data-status="'.$nextStatus.'" title="'.$nextStatus.'"><i class="fa '.$statusIcon.'"></i></button> ';
  $act.='<button class="btn btn-danger btn-xs btn-tc-delete" data-id="'.(int)$r->id.'" data-no="'.tcd_h($r->training_code).'" title="'.hr_h('common_delete', 'Delete').'"><i class="fa fa-trash"></i></button></div>';
  $provider='<strong>'.tcd_h($r->provider_type).'</strong><br><small>'.tcd_h($r->provider_name?:'-').'</small>';
  $dur=number_format((float)$r->duration_hours,2).' jam<br><small>Max: '.(int)$r->max_participant.' peserta</small>';
  $assess='<span class="label label-'.($r->assessment_required==='Y'?'primary':'default').'">Assessment '.$r->assessment_required.'</span><br><small>Passing: '.tcd_h($r->passing_score!==null?$r->passing_score:'-').'</small><br><small>Certificate: '.tcd_h($r->certificate_required).'</small>';
  $valid=tcd_h($r->valid_from.' s/d '.$r->valid_to).'<br><small>Validity cert: '.(int)$r->validity_months.' bulan</small>';
  $dept=$r->owner_department_code?'<strong>'.tcd_h($r->owner_department_code).'</strong> - '.tcd_h($r->nm_dept):'<span class="text-muted">Not assigned</span>';
  $cost=number_format((float)$r->cost_estimate,2).' '.tcd_h($r->currency);
  $data[]=array(
    $no++,
    $act,
    '<strong>'.tcd_h($r->training_code).'</strong><br><small>'.tcd_h($r->training_name).'</small><br><small class="text-muted">SAP: '.tcd_h($r->sap_reference?:'-').'</small>',
    '<span class="tc-pill">'.tcd_h($r->training_category).'</span><br><small>'.tcd_h($r->training_type).'</small>',
    '<strong>'.tcd_h($r->delivery_method).'</strong><br><small>'.tcd_h($r->training_level).'</small>',
    $provider.'<br><small>'.$dept.'</small>',
    $dur.'<br><small>Cost: '.$cost.'</small>',
    $assess,
    $valid,
    tcd_label($r->status),
    tcd_h(($r->updated_by?:$r->created_by?:'-')).'<br><small>'.tcd_h($r->updated_at?:$r->created_at).'</small>'
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
    'mandatory'=>$kpi?(int)$kpi->mandatory:0,
    'certification'=>$kpi?(int)$kpi->certification:0,
    'avg_duration'=>$kpi?(float)$kpi->avg_duration:0,
    'total_cost'=>$kpi?(float)$kpi->total_cost:0
  )
));
?>
