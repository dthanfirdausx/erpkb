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

function wld_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function wld_label($s){$m=array('DRAFT'=>'default','ACTIVE'=>'success','INACTIVE'=>'warning');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.wld_h($s).'</span>';}
function wld_filters(){return array('from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01'),'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:'9999-12-31','location_type'=>isset($_POST['location_type'])?trim($_POST['location_type']):'','status'=>isset($_POST['status'])?trim($_POST['status']):'','city'=>isset($_POST['city'])?trim($_POST['city']):'','keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):'');}
function wld_where($f,&$p){
  $w=" WHERE wl.valid_from<=? AND wl.valid_to>=? ";$p[]=$f['to'];$p[]=$f['from'];
  if($f['location_type']!==''){$w.=" AND wl.location_type=? ";$p[]=$f['location_type'];}
  if($f['status']!==''){$w.=" AND wl.status=? ";$p[]=$f['status'];}
  if($f['city']!==''){$w.=" AND wl.city LIKE ? ";$p[]='%'.$f['city'].'%';}
  if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (wl.location_code LIKE ? OR wl.location_name LIKE ? OR wl.address LIKE ? OR wl.sap_reference LIKE ? OR p.plant_name LIKE ? OR cs.structure_name LIKE ?) ";for($i=0;$i<6;$i++)$p[]=$kw;}
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$f=wld_filters();$p=array();$w=wld_where($f,$p);
$kpi=$db->fetch("SELECT COUNT(*) total,SUM(status='ACTIVE') active,SUM(location_type='PLANT') plant,SUM(location_type='WAREHOUSE') warehouse,SUM(capacity_headcount) capacity FROM erp_work_location");
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_work_location wl LEFT JOIN erp_company_structure cs ON cs.id=wl.company_structure_id LEFT JOIN erp_plant p ON p.id=wl.plant_id $w",$p);$total=$cnt?(int)$cnt->jml:0;
$orderMap=array(2=>'wl.location_code',3=>'wl.location_type',4=>'cs.structure_code',5=>'p.plant_code',6=>'wl.city',7=>'wl.attendance_required',8=>'wl.capacity_headcount',9=>'wl.status',10=>'wl.updated_at');$orderBy="wl.location_code ASC";
if(isset($_POST['order'][0]['column'])){$col=(int)$_POST['order'][0]['column'];$dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;}
$rows=$db->query("SELECT wl.*,cs.structure_code,cs.structure_name,cs.structure_type,p.plant_code,p.plant_name,sl.storage_code,sl.storage_name,cc.cost_center_name,pc.profit_center_name
  FROM erp_work_location wl
  LEFT JOIN erp_company_structure cs ON cs.id=wl.company_structure_id
  LEFT JOIN erp_plant p ON p.id=wl.plant_id
  LEFT JOIN erp_storage_location sl ON sl.id=wl.storage_location_id
  LEFT JOIN erp_cost_center cc ON cc.cost_center_code=wl.cost_center_code
  LEFT JOIN erp_profit_center pc ON pc.profit_center_code=wl.profit_center_code
  $w ORDER BY $orderBy LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<div class="wl-action"><button class="btn btn-info btn-xs btn-wl-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> <button class="btn btn-primary btn-xs btn-wl-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> ';
  if($r->status!=='ACTIVE')$act.='<button class="btn btn-danger btn-xs btn-wl-delete" data-id="'.(int)$r->id.'" data-no="'.wld_h($r->location_code).'" title="'.hr_h('common_delete', 'Delete').'"><i class="fa fa-trash"></i></button>';
  $act.='</div>';
  $org=$r->structure_code?'<strong>'.wld_h($r->structure_code).'</strong><br><small>'.wld_h($r->structure_name).' ['.wld_h($r->structure_type).']</small>':'<span class="text-muted">Not assigned</span>';
  $plant=$r->plant_code?'<strong>'.wld_h($r->plant_code).'</strong><br><small>'.wld_h($r->plant_name).'</small><br><small>SL: '.wld_h($r->storage_code?:'-').' '.wld_h($r->storage_name?:'').'</small>':'<span class="text-muted">Non warehouse location</span>';
  $loc=wld_h($r->city?:'-').'<br><small>'.wld_h($r->address?:'-').'</small>';
  $att='<strong>'.wld_h($r->attendance_required).'</strong><br><small>Radius: '.wld_h($r->geo_fence_radius_meter?:'-').' m</small><br><small>'.wld_h($r->timezone).'</small>';
  $cap=(int)$r->capacity_headcount.' HC<br><small>'.wld_h(($r->working_calendar_code?:'-').' / '.($r->default_shift_code?:'-')).'</small>';
  $data[]=array(
    $no++,$act,
    '<strong>'.wld_h($r->location_code).'</strong><br><small>'.wld_h($r->location_name).'</small><br><small class="text-muted">SAP: '.wld_h($r->sap_reference?:'-').'</small>',
    '<span class="wl-pill">'.wld_h($r->location_type).'</span><br><small>'.wld_h($r->work_location_category).'</small>',
    $org,$plant,$loc,$att,$cap,wld_label($r->status),
    wld_h(($r->updated_by?:$r->created_by?:'-')).'<br><small>'.wld_h($r->updated_at?:$r->created_at).'</small>'
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'kpi'=>array('total'=>$kpi?(int)$kpi->total:0,'active'=>$kpi?(int)$kpi->active:0,'plant'=>$kpi?(int)$kpi->plant:0,'warehouse'=>$kpi?(int)$kpi->warehouse:0,'capacity'=>$kpi?(int)$kpi->capacity:0)));
?>
