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

function deptd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function deptd_label($status){
  $class=$status==='ACTIVE'?'success':'warning';
  return '<span class="label label-'.$class.'">'.deptd_h($status).'</span>';
}
function deptd_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:'9999-12-31',
    'dept_type'=>isset($_POST['dept_type'])?trim($_POST['dept_type']):'',
    'status'=>isset($_POST['status'])?trim($_POST['status']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function deptd_where($f,&$p){
  $w=" WHERE d.valid_from<=? AND d.valid_to>=? ";
  $p[]=$f['to'];$p[]=$f['from'];
  if($f['dept_type']!==''){$w.=" AND d.dept_type=? ";$p[]=$f['dept_type'];}
  if($f['status']!==''){$w.=" AND d.status=? ";$p[]=$f['status'];}
  if($f['keyword']!==''){
    $kw='%'.$f['keyword'].'%';
    $w.=" AND (d.kd_dept LIKE ? OR d.nm_dept LIKE ? OR d.dept_short_name LIKE ? OR d.cost_center_code LIKE ? OR d.profit_center_code LIKE ? OR d.sap_reference LIKE ? OR cc.cost_center_name LIKE ? OR pc.profit_center_name LIKE ? OR u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?) ";
    for($i=0;$i<11;$i++)$p[]=$kw;
  }
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$f=deptd_filters();$p=array();$w=deptd_where($f,$p);

$kpi=$db->fetch("SELECT COUNT(*) total,SUM(status='ACTIVE') active,SUM(dept_type IN ('PRODUCTION','WAREHOUSE','QUALITY','OPERATIONAL')) ops,SUM(COALESCE(cost_center_code,'')<>'') with_cost_center FROM dept");
$cnt=$db->fetch("SELECT COUNT(*) jml FROM dept d LEFT JOIN erp_cost_center cc ON cc.cost_center_code=d.cost_center_code LEFT JOIN erp_profit_center pc ON pc.profit_center_code=d.profit_center_code LEFT JOIN sys_users u ON u.id=d.manager_user_id $w",$p);
$total=$cnt?(int)$cnt->jml:0;

$orderMap=array(
  2=>'d.kd_dept',
  3=>'d.dept_type',
  4=>'pd.kd_dept',
  5=>'cs.structure_code',
  6=>'d.cost_center_code',
  7=>'u.username',
  8=>'d.valid_from',
  9=>'d.status',
  10=>'d.updated_at'
);
$orderBy="FIELD(d.dept_type,'FINANCE','HR','SUPPORT','WAREHOUSE','QUALITY','PRODUCTION','SALES','OPERATIONAL','FUNCTIONAL'), d.kd_dept";
if(isset($_POST['order'][0]['column'])){
  $col=(int)$_POST['order'][0]['column'];
  $dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';
  if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;
}

$rows=$db->query("SELECT d.*,pd.nm_dept parent_name,cs.structure_code,cs.structure_name,cs.structure_type,cc.cost_center_name,pc.profit_center_name,u.username manager_username,TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) manager_name,
    COALESCE(ch.child_count,0) child_count
  FROM dept d
  LEFT JOIN dept pd ON pd.kd_dept=d.parent_dept_code
  LEFT JOIN erp_company_structure cs ON cs.id=d.company_structure_id
  LEFT JOIN erp_cost_center cc ON cc.cost_center_code=d.cost_center_code
  LEFT JOIN erp_profit_center pc ON pc.profit_center_code=d.profit_center_code
  LEFT JOIN sys_users u ON u.id=d.manager_user_id
  LEFT JOIN (SELECT parent_dept_code,COUNT(*) child_count FROM dept WHERE parent_dept_code IS NOT NULL GROUP BY parent_dept_code) ch ON ch.parent_dept_code=d.kd_dept
  $w
  ORDER BY $orderBy
  LIMIT $start,$length",$p);

$data=array();$no=$start+1;
foreach($rows as $r){
  $toggle=$r->status==='ACTIVE'?'INACTIVE':'ACTIVE';
  $toggleIcon=$r->status==='ACTIVE'?'fa-ban':'fa-check';
  $toggleClass=$r->status==='ACTIVE'?'warning':'success';
  $act='<div class="dept-action"><button class="btn btn-info btn-xs btn-dept-detail" data-id="'.deptd_h($r->kd_dept).'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> ';
  $act.='<button class="btn btn-primary btn-xs btn-dept-edit" data-id="'.deptd_h($r->kd_dept).'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> ';
  $act.='<button class="btn btn-'.$toggleClass.' btn-xs btn-dept-status" data-id="'.deptd_h($r->kd_dept).'" data-status="'.$toggle.'" title="'.$toggle.'"><i class="fa '.$toggleIcon.'"></i></button> ';
  $act.='<button class="btn btn-danger btn-xs btn-dept-delete" data-id="'.deptd_h($r->kd_dept).'" data-no="'.deptd_h($r->kd_dept).'" title="'.hr_h('common_delete', 'Delete').'"><i class="fa fa-trash"></i></button></div>';
  $parent=$r->parent_dept_code?'<strong>'.deptd_h($r->parent_dept_code).'</strong><br><small>'.deptd_h($r->parent_name).'</small>':'<span class="text-muted">Root</span>';
  $org=$r->structure_code?'<strong>'.deptd_h($r->structure_code).'</strong><br><small>'.deptd_h($r->structure_name).' ['.deptd_h($r->structure_type).']</small>':'<span class="text-muted">Not assigned</span>';
  $cost='<strong>'.deptd_h($r->cost_center_code?:'-').'</strong><br><small>'.deptd_h($r->cost_center_name?:'').'</small><br><small>Profit: '.deptd_h($r->profit_center_code?:'-').' '.deptd_h($r->profit_center_name?:'').'</small>';
  $managerName=trim($r->manager_name)!==''?$r->manager_name:$r->manager_username;
  $manager=$r->manager_username?deptd_h($r->manager_username).'<br><small>'.deptd_h($managerName).'</small>':'<span class="text-muted">Not assigned</span>';
  $data[]=array(
    $no++,
    $act,
    '<strong>'.deptd_h($r->kd_dept).'</strong><br><small>'.deptd_h($r->nm_dept).'</small><br><small class="text-muted">'.deptd_h($r->dept_short_name?:'-').' | Child: '.(int)$r->child_count.' | SAP: '.deptd_h($r->sap_reference?:'-').'</small>',
    '<span class="dept-pill">'.deptd_h($r->dept_type).'</span><br><small>'.deptd_h($r->functional_area?:'-').'</small>',
    $parent,
    $org,
    $cost,
    $manager,
    deptd_h($r->valid_from.' s/d '.$r->valid_to),
    deptd_label($r->status),
    deptd_h(($r->updated_by?:$r->created_by?:'-')).'<br><small>'.deptd_h($r->updated_at?:$r->created_at).'</small>'
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
    'ops'=>$kpi?(int)$kpi->ops:0,
    'with_cost_center'=>$kpi?(int)$kpi->with_cost_center:0
  )
));
?>
