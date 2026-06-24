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
include "production_reports_lib.php";
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
$draw=isset($_REQUEST['draw'])?(int)$_REQUEST['draw']:0;$start=isset($_REQUEST['start'])?max(0,(int)$_REQUEST['start']):0;$length=isset($_REQUEST['length'])?(int)$_REQUEST['length']:25;if($length<=0)$length=25;
$type=isset($_POST['report_type'])?$_POST['report_type']:'order_summary';
$params=array();$data=array();$total=0;
if($type==='material_consumption'){
  $where=prp_filters($params,'p');
  $base=" FROM production_order_material m JOIN production_order p ON p.id_production_order=m.id_production_order ".$where;
  $c=$db->fetch("SELECT COUNT(*) jml ".$base,$params);$total=$c?(int)$c->jml:0;
  $rows=$db->query("SELECT p.no_production_order,p.status,p.start_date,p.plant,m.material_code,m.material_name,m.required_qty,m.issued_qty,m.remaining_qty,m.uom ".$base." ORDER BY p.start_date,p.no_production_order,m.material_code LIMIT $start,$length",$params);
  $n=$start+1;foreach($rows as $r)$data[]=array($n++,prp_h($r->no_production_order),prp_h($r->status),prp_h($r->start_date),prp_h($r->plant),'<strong>'.prp_h($r->material_code).'</strong><br><small>'.prp_h($r->material_name).'</small>',prp_num($r->required_qty),prp_num($r->issued_qty),prp_num($r->remaining_qty),prp_h($r->uom),'<span class="label label-'.((float)$r->remaining_qty<=0?'success':'warning').'">'.(((float)$r->remaining_qty<=0)?'FULL':'OPEN').'</span>');
} elseif($type==='output_gr'){
  $params=array();$w=" WHERE 1=1 ";if(!empty($_POST['tgl_awal'])&&!empty($_POST['tgl_akhir'])){$w.=" AND h.posting_date BETWEEN ? AND ? ";$params[]=$_POST['tgl_awal'];$params[]=$_POST['tgl_akhir'];}if(!empty($_POST['plant_id'])){$w.=" AND h.plant_id=? ";$params[]=(int)$_POST['plant_id'];}if(!empty($_POST['keyword'])){$kw='%'.trim($_POST['keyword']).'%';$w.=" AND (h.gr_no LIKE ? OR h.no_production_order LIKE ? OR h.confirmation_no LIKE ? OR d.material_code LIKE ? OR d.material_name LIKE ?) ";for($i=0;$i<5;$i++)$params[]=$kw;}
  $base=" FROM erp_gr_production h JOIN erp_gr_production_detail d ON d.gr_id=h.id LEFT JOIN erp_plant ep ON ep.id=h.plant_id LEFT JOIN erp_storage_location es ON es.id=h.storage_location_id ".$w;
  $c=$db->fetch("SELECT COUNT(*) jml ".$base,$params);$total=$c?(int)$c->jml:0;
  $rows=$db->query("SELECT h.gr_no,h.no_production_order,h.confirmation_no,h.posting_date,h.stock_type,h.status,d.material_code,d.material_name,d.qty,d.uom,ep.plant_code,es.storage_code ".$base." ORDER BY h.posting_date DESC,h.id DESC LIMIT $start,$length",$params);
  $n=$start+1;foreach($rows as $r)$data[]=array($n++,prp_h($r->gr_no),prp_h($r->posting_date),prp_h($r->no_production_order),prp_h($r->confirmation_no),'<strong>'.prp_h($r->material_code).'</strong><br><small>'.prp_h($r->material_name).'</small>',prp_num($r->qty),prp_h($r->uom),prp_h(trim($r->plant_code.' / '.$r->storage_code,' /')),prp_h($r->stock_type),prp_h($r->status));
} elseif($type==='traceability'){
  $params=array();$w=" WHERE 1=1 ";if(!empty($_POST['tgl_awal'])&&!empty($_POST['tgl_akhir'])){$w.=" AND h.posting_date BETWEEN ? AND ? ";$params[]=$_POST['tgl_awal'];$params[]=$_POST['tgl_akhir'];}if(!empty($_POST['keyword'])){$kw='%'.trim($_POST['keyword']).'%';$w.=" AND (h.gr_no LIKE ? OR h.no_production_order LIKE ? OR tr.raw_material_code LIKE ? OR tr.raw_material_name LIKE ? OR tr.no_aju LIKE ? OR tr.no_dokpab LIKE ?) ";for($i=0;$i<6;$i++)$params[]=$kw;}
  $base=" FROM erp_gr_production_trace tr JOIN erp_gr_production h ON h.id=tr.gr_id JOIN erp_gr_production_detail d ON d.id=tr.gr_detail_id ".$w;
  $c=$db->fetch("SELECT COUNT(*) jml ".$base,$params);$total=$c?(int)$c->jml:0;
  $rows=$db->query("SELECT h.gr_no,h.no_production_order,h.posting_date,d.material_code output_code,d.material_name output_name,tr.raw_material_code,tr.raw_material_name,tr.qty,tr.uom,tr.jenis_dokpab,tr.no_aju,tr.no_dokpab,tr.trace_source ".$base." ORDER BY h.posting_date DESC,h.id DESC,tr.id LIMIT $start,$length",$params);
  $n=$start+1;foreach($rows as $r)$data[]=array($n++,prp_h($r->gr_no),prp_h($r->posting_date),prp_h($r->no_production_order),'<strong>'.prp_h($r->output_code).'</strong><br><small>'.prp_h($r->output_name).'</small>','<strong>'.prp_h($r->raw_material_code).'</strong><br><small>'.prp_h($r->raw_material_name).'</small>',prp_num($r->qty),prp_h($r->uom),prp_h(trim($r->jenis_dokpab.' '.$r->no_aju.' / '.$r->no_dokpab)),prp_h($r->trace_source));
} else {
  $where=prp_filters($params,'p');$sql=prp_order_sql($where);$c=$db->fetch("SELECT COUNT(*) jml FROM ($sql) x",$params);$total=$c?(int)$c->jml:0;
  $rows=$db->query($sql." ORDER BY start_date,no_production_order LIMIT $start,$length",$params);
  $n=$start+1;foreach($rows as $r)$data[]=array($n++,prp_h($r->no_production_order),prp_h($r->status),prp_h($r->order_strategy),prp_h($r->plant),'<strong>'.prp_h($r->material_code).'</strong><br><small>'.prp_h($r->material_name).'</small>',prp_num($r->order_qty),prp_num($r->issued_qty),prp_num($r->yield_qty),prp_num($r->gr_qty),prp_num($r->scrap_qty),prp_pct($r->yield_qty,$r->order_qty).'%',prp_h($r->start_date.' s/d '.$r->finish_date));
}
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
