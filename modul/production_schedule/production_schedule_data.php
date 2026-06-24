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
function psd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function psd_num($v,$d=2){return number_format((float)$v,$d,',','.');}
function psd_status($s){$m=array('DRAFT'=>'default','SCHEDULED'=>'primary','DISPATCHED'=>'warning','IN_PROCESS'=>'info','COMPLETED'=>'success','CANCELLED'=>'danger');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.psd_h($s).'</span>';}
$req=$_REQUEST;$draw=isset($req['draw'])?(int)$req['draw']:0;$start=isset($req['start'])?max(0,(int)$req['start']):0;$length=isset($req['length'])?(int)$req['length']:25;if($length<=0)$length=25;
$p=array();$w=" WHERE 1=1 ";
if(!empty($_POST['tgl_awal'])&&!empty($_POST['tgl_akhir'])){$w.=" AND h.schedule_date BETWEEN ? AND ? ";$p[]=$_POST['tgl_awal'];$p[]=$_POST['tgl_akhir'];}
if(!empty($_POST['status'])){$w.=" AND h.dispatch_status=? ";$p[]=$_POST['status'];}
if(!empty($_POST['plant'])){$w.=" AND h.plant_code=? ";$p[]=$_POST['plant'];}
if(!empty($_POST['work_center'])){$w.=" AND ds.work_center_text LIKE ? ";$p[]='%'.trim($_POST['work_center']).'%';}
if(!empty($_POST['keyword'])){$kw='%'.trim($_POST['keyword']).'%';$w.=" AND (h.schedule_no LIKE ? OR h.no_production_order LIKE ? OR h.material_code LIKE ? OR h.material_name LIKE ? OR h.scheduler LIKE ? OR ds.work_center_text LIKE ?) ";for($i=0;$i<6;$i++)$p[]=$kw;}
$base=" FROM erp_production_schedule h LEFT JOIN (SELECT schedule_id,COUNT(*) op_count,SUM(duration_minutes) total_minutes,MIN(planned_start) first_start,MAX(planned_finish) last_finish,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',work_center,work_center_name,operation_name) SEPARATOR ' ') work_center_text FROM erp_production_schedule_detail GROUP BY schedule_id) ds ON ds.schedule_id=h.id ".$w;
$cnt=$db->fetch("SELECT COUNT(*) jml ".$base,$p);
$rows=$db->query("SELECT h.*,COALESCE(ds.op_count,0) op_count,COALESCE(ds.total_minutes,0) total_minutes,ds.first_start,ds.last_finish ".$base." ORDER BY h.schedule_date DESC,h.id DESC LIMIT ".$start.",".$length,$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<div class="ps-action"><button class="btn btn-info btn-xs btn-ps-detail" data-id="'.(int)$r->id.'" title="Detail"><i class="fa fa-eye"></i></button> ';
  if($r->dispatch_status==='DRAFT')$act.='<button class="btn btn-primary btn-xs btn-ps-status" data-act="schedule" data-id="'.(int)$r->id.'" title="Schedule"><i class="fa fa-calendar-check-o"></i></button> ';
  if($r->dispatch_status==='SCHEDULED')$act.='<button class="btn btn-warning btn-xs btn-ps-status" data-act="dispatch" data-id="'.(int)$r->id.'" title="Dispatch"><i class="fa fa-send"></i></button> ';
  if(in_array($r->dispatch_status,array('DRAFT','SCHEDULED'),true))$act.='<button class="btn btn-danger btn-xs btn-ps-cancel" data-id="'.(int)$r->id.'" title="Cancel"><i class="fa fa-ban"></i></button>';
  $act.='</div>';
  $doc='<strong>'.psd_h($r->schedule_no).'</strong><br><small>Scheduler: '.psd_h($r->scheduler?:'-').'</small>';
  $po='<strong>'.psd_h($r->no_production_order).'</strong><br><small>'.psd_h($r->priority).'</small>';
  $mat='<strong>'.psd_h($r->material_code).'</strong><br><small>'.psd_h($r->material_name).'</small>';
  $date='<strong>'.psd_h($r->schedule_date).'</strong><br><small>'.psd_h(($r->planned_start?:'-').' s/d '.($r->planned_finish?:'-')).'</small>';
  $ops='<span class="badge bg-aqua">'.(int)$r->op_count.' op</span><br><small>'.psd_num($r->total_minutes,0).' min</small>';
  $data[]=array($no++,$act,$doc,$po,$mat,psd_num($r->order_qty,5).' '.psd_h($r->uom),psd_h($r->plant_code),$date,$ops,psd_status($r->dispatch_status),psd_h($r->created_by));
}
echo json_encode(array('draw'=>$draw,'recordsTotal'=>intval($cnt?$cnt->jml:0),'recordsFiltered'=>intval($cnt?$cnt->jml:0),'data'=>$data));
?>
