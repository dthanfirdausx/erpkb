<?php
if (!function_exists('prod_t')) {
  function prod_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('prod_h')) {
  function prod_h($key, $fallback = '') { return htmlspecialchars((string) prod_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('prod_js')) {
  function prod_js($key, $fallback = '') { return json_encode(prod_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if(session_status()===PHP_SESSION_NONE)session_start();
include "../../inc/config.php";
session_check_json();
function pv_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function pv_label($s){$m=array('DRAFT'=>'default','RELEASED'=>'success','INACTIVE'=>'warning','LOCKED'=>'danger');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.pv_h($s).'</span>';}
function pv_filters(){return array('from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-m-01'),'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-m-t'),'status'=>isset($_POST['status'])?trim($_POST['status']):'','plant'=>isset($_POST['plant'])?trim($_POST['plant']):'','keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):'');}
function pv_where($f,&$p){$w=" WHERE pv.valid_from BETWEEN ? AND ? ";$p[]=$f['from'];$p[]=$f['to'];if($f['status']!==''){$w.=" AND pv.version_status=? ";$p[]=$f['status'];}if($f['plant']!==''){$w.=" AND pv.plant_code=? ";$p[]=$f['plant'];}if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (pv.production_version_no LIKE ? OR pv.material_code LIKE ? OR pv.material_name LIKE ? OR pv.bom_no LIKE ? OR pv.routing_no LIKE ? OR pv.version_description LIKE ?) ";for($i=0;$i<6;$i++)$p[]=$kw;}return $w;}
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$f=pv_filters();$p=array();$w=pv_where($f,$p);$c=$db->fetch("SELECT COUNT(*) jml FROM erp_production_version pv $w",$p);$total=$c?(int)$c->jml:0;
$rows=$db->query("SELECT pv.* FROM erp_production_version pv $w ORDER BY pv.valid_from DESC,pv.id DESC LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){$act='<div class="pv-action"><button class="btn btn-info btn-xs btn-pv-detail" data-id="'.(int)$r->id.'" title="Detail"><i class="fa fa-eye"></i></button> ';if($r->version_status==='DRAFT'){$act.='<button class="btn btn-primary btn-xs btn-pv-edit" data-id="'.(int)$r->id.'" title="Edit"><i class="fa fa-pencil"></i></button> <button class="btn btn-success btn-xs btn-pv-release" data-id="'.(int)$r->id.'" data-no="'.pv_h($r->production_version_no).'" title="Release"><i class="fa fa-check"></i></button> <button class="btn btn-danger btn-xs btn-pv-delete" data-id="'.(int)$r->id.'" data-no="'.pv_h($r->production_version_no).'" title="Delete"><i class="fa fa-trash"></i></button>';}elseif($r->version_status==='RELEASED'){$act.='<button class="btn btn-warning btn-xs btn-pv-inactive" data-id="'.(int)$r->id.'" data-no="'.pv_h($r->production_version_no).'" title="Inactive"><i class="fa fa-ban"></i></button>';}$act.='</div>';
  $data[]=array($no++,$act,'<strong>'.pv_h($r->production_version_no).'</strong><br><small>Version '.pv_h($r->version_code).' / Default '.pv_h($r->is_default).'</small>','<strong>'.pv_h($r->material_code).'</strong><br><small>'.pv_h($r->material_name).'</small>',pv_h($r->plant_code),'<strong>'.pv_h($r->bom_no).'</strong><br><small>BOM ID '.(int)$r->bom_id.'</small>','<strong>'.pv_h($r->routing_no).'</strong><br><small>Routing ID '.(int)$r->routing_id.'</small>',pv_h(($r->valid_from?:'-').' s/d '.($r->valid_to?:'Open')),pv_h(($r->lot_size_from!==null?$r->lot_size_from:'0').' - '.($r->lot_size_to!==null&&$r->lot_size_to>0?$r->lot_size_to:'Open')),pv_label($r->version_status),pv_h($r->updated_by?:$r->created_by));
}
header('Content-Type: application/json; charset=utf-8');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
