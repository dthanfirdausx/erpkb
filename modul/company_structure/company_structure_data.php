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

function csd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function csd_label($s){
  $map=array('DRAFT'=>'default','ACTIVE'=>'success','INACTIVE'=>'warning');
  $c=isset($map[$s])?$map[$s]:'default';
  return '<span class="label label-'.$c.'">'.csd_h($s).'</span>';
}
function csd_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-01-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:'9999-12-31',
    'type'=>isset($_POST['type'])?trim($_POST['type']):'',
    'status'=>isset($_POST['status'])?trim($_POST['status']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function csd_where($f,&$p){
  $w=" WHERE cs.valid_from<=? AND cs.valid_to>=? ";
  $p[]=$f['to'];$p[]=$f['from'];
  if($f['type']!==''){$w.=" AND cs.structure_type=? ";$p[]=$f['type'];}
  if($f['status']!==''){$w.=" AND cs.status=? ";$p[]=$f['status'];}
  if($f['keyword']!==''){
    $kw='%'.$f['keyword'].'%';
    $w.=" AND (cs.structure_code LIKE ? OR cs.structure_name LIKE ? OR cs.legal_entity_name LIKE ? OR cs.city LIKE ? OR cs.sap_reference LIKE ?) ";
    for($i=0;$i<5;$i++)$p[]=$kw;
  }
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$f=csd_filters();$p=array();$w=csd_where($f,$p);
$c=$db->fetch("SELECT COUNT(*) jml FROM erp_company_structure cs $w",$p);
$total=$c?(int)$c->jml:0;
$rows=$db->query("SELECT cs.*, p.structure_code parent_code, p.structure_name parent_name, p.structure_type parent_type,
    COALESCE(ch.child_count,0) child_count
  FROM erp_company_structure cs
  LEFT JOIN erp_company_structure p ON p.id=cs.parent_id
  LEFT JOIN (SELECT parent_id,COUNT(*) child_count FROM erp_company_structure GROUP BY parent_id) ch ON ch.parent_id=cs.id
  $w
  ORDER BY FIELD(cs.structure_type,'COMPANY','COMPANY_CODE','BUSINESS_AREA','PERSONNEL_AREA','PERSONNEL_SUBAREA','ORG_UNIT'), cs.structure_code
  LIMIT $start,$length",$p);

$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<div class="cs-action"><button class="btn btn-info btn-xs btn-cs-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> ';
  if($r->status==='DRAFT'){
    $act.='<button class="btn btn-primary btn-xs btn-cs-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> <button class="btn btn-success btn-xs btn-cs-activate" data-id="'.(int)$r->id.'" data-no="'.csd_h($r->structure_code).'" title="Activate"><i class="fa fa-check"></i></button> <button class="btn btn-danger btn-xs btn-cs-delete" data-id="'.(int)$r->id.'" data-no="'.csd_h($r->structure_code).'" title="'.hr_h('common_delete', 'Delete').'"><i class="fa fa-trash"></i></button>';
  }elseif($r->status==='ACTIVE'){
    $act.='<button class="btn btn-warning btn-xs btn-cs-inactive" data-id="'.(int)$r->id.'" data-no="'.csd_h($r->structure_code).'" title="'.hr_h('hr_inactive', 'Inactive').'"><i class="fa fa-ban"></i></button>';
  }
  $act.='</div>';
  $parent=$r->parent_code?'<strong>'.csd_h($r->parent_code).'</strong><br><small>'.csd_h($r->parent_name).' ['.csd_h($r->parent_type).']</small>':'<span class="text-muted">Root</span>';
  $legal=csd_h($r->legal_entity_name?:'-').'<br><small>Tax: '.csd_h($r->tax_id?:'-').' | '.csd_h($r->currency).'</small>';
  $location=csd_h($r->city?:'-').'<br><small>'.csd_h($r->country).' | '.csd_h($r->address?:'-').'</small>';
  $cost=csd_h($r->cost_center_code?:'-').'<br><small>Profit: '.csd_h($r->profit_center_code?:'-').'</small>';
  $data[]=array(
    $no++,
    $act,
    '<strong>'.csd_h($r->structure_code).'</strong><br><small>'.csd_h($r->structure_name).'</small><br><small class="text-muted">Child: '.(int)$r->child_count.' | SAP: '.csd_h($r->sap_reference?:'-').'</small>',
    '<span class="cs-type-pill">'.csd_h($r->structure_type).'</span>',
    $parent,
    $legal,
    $location,
    csd_h($r->valid_from.' s/d '.$r->valid_to),
    $cost,
    csd_label($r->status),
    csd_h(($r->updated_by?:$r->created_by).'<br>'.($r->updated_at?:$r->created_at))
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
