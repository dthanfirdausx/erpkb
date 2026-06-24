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
function ptr_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function ptr_num($v,$d=5){return number_format((float)$v,$d,',','.');}
function ptr_src($s){$cls=$s==='INHERITED'?'info':'success';return '<span class="label label-'.$cls.'">'.ptr_h($s).'</span>';}
$req=$_REQUEST;$draw=isset($req['draw'])?(int)$req['draw']:0;$start=isset($req['start'])?max(0,(int)$req['start']):0;$length=isset($req['length'])?(int)$req['length']:25;if($length<=0)$length=25;
$p=array();$w=" WHERE 1=1 ";
if(!empty($_POST['tgl_awal'])&&!empty($_POST['tgl_akhir'])){$w.=" AND h.posting_date BETWEEN ? AND ? ";$p[]=$_POST['tgl_awal'];$p[]=$_POST['tgl_akhir'];}
if(!empty($_POST['plant_id'])){$w.=" AND h.plant_id=? ";$p[]=(int)$_POST['plant_id'];}
if(!empty($_POST['stock_type'])){$w.=" AND h.stock_type=? ";$p[]=$_POST['stock_type'];}
if(!empty($_POST['trace_source'])){$w.=" AND tr.trace_source=? ";$p[]=$_POST['trace_source'];}
if(!empty($_POST['material_code'])){$w.=" AND (d.material_code=? OR tr.raw_material_code=? OR tr.source_material_code=?) ";$p[]=$_POST['material_code'];$p[]=$_POST['material_code'];$p[]=$_POST['material_code'];}
if(!empty($_POST['keyword'])){$kw='%'.trim($_POST['keyword']).'%';$w.=" AND (h.gr_no LIKE ? OR h.no_production_order LIKE ? OR h.confirmation_no LIKE ? OR d.material_code LIKE ? OR d.material_name LIKE ? OR tr.raw_material_code LIKE ? OR tr.raw_material_name LIKE ? OR tr.no_aju LIKE ? OR tr.no_dokpab LIKE ? OR tr.lot_no LIKE ? OR tr.no_bpb LIKE ?) ";for($i=0;$i<11;$i++)$p[]=$kw;}
$base=" FROM erp_gr_production_trace tr JOIN erp_gr_production h ON h.id=tr.gr_id JOIN erp_gr_production_detail d ON d.id=tr.gr_detail_id LEFT JOIN erp_plant ep ON ep.id=h.plant_id LEFT JOIN erp_storage_location es ON es.id=h.storage_location_id LEFT JOIN erp_storage_bin eb ON eb.id=h.storage_bin_id ".$w;
$cnt=$db->fetch("SELECT COUNT(*) jml ".$base,$p);
$rows=$db->query("SELECT tr.*,h.gr_no,h.no_production_order,h.confirmation_no,h.posting_date,h.stock_type,h.status,d.material_code output_material_code,d.material_name output_material_name,d.qty output_qty,d.uom output_uom,ep.plant_code,es.storage_code,eb.bin_code ".$base." ORDER BY h.posting_date DESC,h.id DESC,tr.raw_material_code,tr.id LIMIT ".$start.",".$length,$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<button class="btn btn-info btn-xs btn-ptr-detail" data-id="'.(int)$r->gr_id.'" data-output-layer="'.(int)$r->output_stock_layer_id.'" title="Genealogy"><i class="fa fa-sitemap"></i></button>';
  $out='<strong>'.ptr_h($r->gr_no).'</strong><br><small>'.ptr_h($r->no_production_order.' / '.$r->confirmation_no.' / '.$r->posting_date).'</small>';
  $fg='<strong>'.ptr_h($r->output_material_code).'</strong><br><small>'.ptr_h($r->output_material_name).'</small><br><small>Qty '.ptr_num($r->output_qty).' '.ptr_h($r->output_uom).'</small>';
  $src='<strong>'.ptr_h($r->source_material_code).'</strong><br><small>'.ptr_h($r->source_material_name).'</small>';
  $raw='<strong>'.ptr_h($r->raw_material_code).'</strong><br><small>'.ptr_h($r->raw_material_name).'</small>';
  $bc=ptr_h(trim(($r->jenis_dokpab?:'').' '.$r->no_aju.' / '.$r->no_dokpab));
  $loc=ptr_h(trim($r->plant_code.' / '.$r->storage_code.' / '.$r->bin_code,' /'));
  $data[]=array($no++,$act,$out,$fg,$src,$raw,ptr_num($r->qty),ptr_h($r->uom),$bc,ptr_h($r->lot_no?:'-'),ptr_h($r->no_bpb?:'-'),$loc,ptr_src($r->trace_source),ptr_h($r->status));
}
echo json_encode(array('draw'=>$draw,'recordsTotal'=>intval($cnt?$cnt->jml:0),'recordsFiltered'=>intval($cnt?$cnt->jml:0),'data'=>$data));
?>
