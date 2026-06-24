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
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
function mav_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function mav_num($v,$d=5){return number_format((float)$v,$d,',','.');}
function mav_status($short,$available,$remaining){if((float)$remaining<=0)return '<span class="label label-success">FULL ISSUED</span>';if((float)$short<=0)return '<span class="label label-success">AVAILABLE</span>';if((float)$available>0)return '<span class="label label-warning">PARTIAL</span>';return '<span class="label label-danger">SHORTAGE</span>';}
$req=$_REQUEST;$draw=isset($req['draw'])?(int)$req['draw']:0;$start=isset($req['start'])?max(0,(int)$req['start']):0;$length=isset($req['length'])?(int)$req['length']:25;if($length<=0)$length=25;
$plantId=isset($_POST['plant_id'])?(int)$_POST['plant_id']:0;$slocId=isset($_POST['storage_location_id'])?(int)$_POST['storage_location_id']:0;$binId=isset($_POST['storage_bin_id'])?(int)$_POST['storage_bin_id']:0;$stockType=isset($_POST['stock_type'])?trim($_POST['stock_type']):'UNRESTRICTED';
$stockWhere=" WHERE qty_sisa>0 AND lokasi='GUDANG' ";$stockParams=array();
if($stockType!==''){$stockWhere.=" AND COALESCE(stock_type,'UNRESTRICTED')=? ";$stockParams[]=$stockType;}
if($plantId>0){$stockWhere.=" AND plant_id=? ";$stockParams[]=$plantId;}
if($slocId>0){$stockWhere.=" AND storage_location_id=? ";$stockParams[]=$slocId;}
if($binId>0){$stockWhere.=" AND storage_bin_id=? ";$stockParams[]=$binId;}
$p=array();$w=" WHERE p.status IN ('CREATED','RELEASED','IN_PROCESS') ";
if(!empty($_POST['tgl_awal'])&&!empty($_POST['tgl_akhir'])){$w.=" AND p.start_date BETWEEN ? AND ? ";$p[]=$_POST['tgl_awal'];$p[]=$_POST['tgl_akhir'];}
if(!empty($_POST['po_status'])){$w.=" AND p.status=? ";$p[]=$_POST['po_status'];}
if(!empty($_POST['plant'])){$w.=" AND p.plant=? ";$p[]=$_POST['plant'];}
if(!empty($_POST['material_code'])){$w.=" AND m.material_code=? ";$p[]=$_POST['material_code'];}
if(!empty($_POST['availability_status'])){
  if($_POST['availability_status']==='SHORTAGE')$w.=" AND GREATEST(COALESCE(m.remaining_qty,m.required_qty-m.issued_qty)-COALESCE(st.available_qty,0),0)>0 ";
  if($_POST['availability_status']==='AVAILABLE')$w.=" AND COALESCE(m.remaining_qty,m.required_qty-m.issued_qty)>0 AND GREATEST(COALESCE(m.remaining_qty,m.required_qty-m.issued_qty)-COALESCE(st.available_qty,0),0)=0 ";
}
if(!empty($_POST['keyword'])){$kw='%'.trim($_POST['keyword']).'%';$w.=" AND (p.no_production_order LIKE ? OR p.material_code LIKE ? OR p.material_name LIKE ? OR m.material_code LIKE ? OR m.material_name LIKE ?) ";for($i=0;$i<5;$i++)$p[]=$kw;}
$stockSub="SELECT kode,COALESCE(SUM(qty_sisa),0) available_qty,COUNT(*) layer_count,GROUP_CONCAT(DISTINCT CONCAT(COALESCE(no_aju,''),' / ',COALESCE(no_dokpab,'')) ORDER BY tgl_masuk,id SEPARATOR '<br>') customs_refs FROM stock_layer ".$stockWhere." GROUP BY kode";
$base=" FROM production_order_material m JOIN production_order p ON p.id_production_order=m.id_production_order LEFT JOIN (".$stockSub.") st ON st.kode=m.material_code ".$w;
$params=array_merge($stockParams,$p);
$cnt=$db->fetch("SELECT COUNT(*) jml ".$base,$params);
$rows=$db->query("SELECT m.*,p.no_production_order,p.status po_status,p.start_date,p.finish_date,p.plant,p.storage_location,p.priority,p.material_code fg_code,p.material_name fg_name,COALESCE(st.available_qty,0) available_qty,COALESCE(st.layer_count,0) layer_count,st.customs_refs ".$base." ORDER BY p.start_date ASC,p.id_production_order DESC,m.id_material LIMIT ".$start.",".$length,$params);
$data=array();$no=$start+1;
foreach($rows as $r){
  $remaining=(float)($r->remaining_qty!==null?$r->remaining_qty:max((float)$r->required_qty-(float)$r->issued_qty,0));$available=(float)$r->available_qty;$short=max($remaining-$available,0);
  $act='<button class="btn btn-info btn-xs btn-mav-detail" data-material="'.mav_h($r->material_code).'" data-po="'.(int)$r->id_production_order.'" title="Stock Layers"><i class="fa fa-search"></i></button>';
  $po='<strong>'.mav_h($r->no_production_order).'</strong><br><small>'.mav_h($r->po_status.' / '.$r->priority.' / '.$r->start_date).'</small>';
  $fg='<strong>'.mav_h($r->fg_code).'</strong><br><small>'.mav_h($r->fg_name).'</small>';
  $mat='<strong>'.mav_h($r->material_code).'</strong><br><small>'.mav_h($r->material_name).'</small>';
  $req='Req '.mav_num($r->required_qty).'<br><small>Issued '.mav_num($r->issued_qty).' | Rem '.mav_num($remaining).'</small>';
  $stock='<strong>'.mav_num($available).'</strong><br><small>'.(int)$r->layer_count.' layer</small>';
  $bc=$r->customs_refs?'<small>'.$r->customs_refs.'</small>':'<span class="text-muted">-</span>';
  $data[]=array($no++,$act,$po,$fg,$mat,$req,$stock,mav_num($short),mav_h($r->uom),mav_h($r->plant.' / '.$r->storage_location),$bc,mav_status($short,$available,$remaining));
}
echo json_encode(array('draw'=>$draw,'recordsTotal'=>intval($cnt?$cnt->jml:0),'recordsFiltered'=>intval($cnt?$cnt->jml:0),'data'=>$data));
?>
