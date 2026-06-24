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
function pal_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function pal_num($v,$d=2){return number_format((float)$v,$d,',','.');}
function pal_badge($v,$map){$c=isset($map[$v])?$map[$v]:'default';return '<span class="label label-'.$c.'">'.pal_h($v).'</span>';}
function pal_union(){
  return "SELECT a.id,'MANUAL' source_type,a.activity_no doc_no,a.activity_date,a.activity_time,a.id_production_order,a.no_production_order,a.operation_no,a.operation_name,a.work_center,a.work_center_name,a.plant_code,a.shift_code,a.operator_name,a.activity_type,a.severity,a.activity_text,a.action_taken,a.reference_type,a.reference_id,a.status,a.remarks,a.created_by,a.created_at
          FROM erp_production_activity_log a
          UNION ALL
          SELECT c.id_confirmation,'CONFIRMATION',c.confirmation_no,COALESCE(c.posting_date,DATE(c.confirmation_date)),c.confirmation_date,c.id_production_order,p.no_production_order,c.operation_no,c.operation_name,c.work_center,c.work_center,p.plant,c.shift_code,c.operator_name,'CONFIRMATION','INFO',CONCAT('Confirmation yield ',CAST(c.yield_qty AS CHAR),' ',p.uom,', scrap ',CAST(c.scrap_qty AS CHAR)),c.remarks,'CONFIRMATION',c.id_confirmation,c.status,c.remarks,c.created_by,c.created_at
          FROM production_order_confirmation c JOIN production_order p ON p.id_production_order=c.id_production_order
          UNION ALL
          SELECT d.id,'DOWNTIME',d.downtime_no,d.downtime_date,d.start_time,d.id_production_order,d.no_production_order,d.operation_no,d.operation_name,d.work_center,d.work_center_name,d.plant_code,d.shift_code,d.created_by,'DOWNTIME',CASE WHEN d.impact_type='UNPLANNED' THEN 'WARNING' ELSE 'INFO' END,CONCAT(d.downtime_category,' downtime: ',d.reason_text,' (',CAST(d.duration_minutes AS CHAR),' min)'),d.action_taken,'DOWNTIME',d.id,d.approval_status,d.remarks,d.created_by,d.created_at
          FROM erp_production_downtime d";
}
$req=$_REQUEST;$draw=isset($req['draw'])?(int)$req['draw']:0;$start=isset($req['start'])?max(0,(int)$req['start']):0;$length=isset($req['length'])?(int)$req['length']:25;if($length<=0)$length=25;
$from=!empty($_POST['tgl_awal'])?$_POST['tgl_awal']:date('Y-m-d');$to=!empty($_POST['tgl_akhir'])?$_POST['tgl_akhir']:date('Y-m-d');
$p=array($from,$to);$w=" WHERE q.activity_date BETWEEN ? AND ? ";
if(!empty($_POST['plant'])){$w.=" AND q.plant_code=? ";$p[]=$_POST['plant'];}
if(!empty($_POST['work_center'])){$w.=" AND q.work_center LIKE ? ";$p[]='%'.trim($_POST['work_center']).'%';}
if(!empty($_POST['shift'])){$w.=" AND q.shift_code=? ";$p[]=$_POST['shift'];}
if(!empty($_POST['activity_type'])){$w.=" AND q.activity_type=? ";$p[]=$_POST['activity_type'];}
if(!empty($_POST['severity'])){$w.=" AND q.severity=? ";$p[]=$_POST['severity'];}
if(!empty($_POST['source_type'])){$w.=" AND q.source_type=? ";$p[]=$_POST['source_type'];}
if(!empty($_POST['keyword'])){$kw='%'.trim($_POST['keyword']).'%';$w.=" AND (q.doc_no LIKE ? OR q.no_production_order LIKE ? OR q.work_center LIKE ? OR q.operator_name LIKE ? OR q.activity_text LIKE ? OR q.action_taken LIKE ? OR q.remarks LIKE ?) ";for($i=0;$i<7;$i++)$p[]=$kw;}
$base=" FROM (".pal_union().") q ".$w;
$cnt=$db->fetch("SELECT COUNT(*) jml ".$base,$p);$total=$cnt?(int)$cnt->jml:0;
$rows=$db->query("SELECT q.* ".$base." ORDER BY q.activity_time DESC,q.created_at DESC LIMIT $start,$length",$p);
$typeMap=array('ORDER_START'=>'primary','OPERATION_START'=>'info','OPERATION_FINISH'=>'success','MATERIAL_ISSUE'=>'primary','CONFIRMATION'=>'success','DOWNTIME'=>'warning','QUALITY_CHECK'=>'info','CLEANING'=>'default','HANDOVER'=>'primary','NOTE'=>'default','STOP'=>'danger','OTHER'=>'default');
$sevMap=array('INFO'=>'info','WARNING'=>'warning','CRITICAL'=>'danger');
$statusMap=array('POSTED'=>'success','CANCELLED'=>'danger','REVERSED'=>'danger');
$data=array();$no=$start+1;
foreach($rows as $r){
  $canCancel=($r->source_type==='MANUAL'&&$r->status==='POSTED');
  $act='<button class="btn btn-info btn-xs btn-pal-detail" data-source="'.pal_h($r->source_type).'" data-id="'.(int)$r->id.'" title="Detail"><i class="fa fa-eye"></i></button>';
  if($canCancel)$act.=' <button class="btn btn-danger btn-xs btn-pal-cancel" data-id="'.(int)$r->id.'" data-no="'.pal_h($r->doc_no).'" title="Cancel"><i class="fa fa-ban"></i></button>';
  $doc='<strong>'.pal_h($r->doc_no).'</strong><br><small>'.pal_h($r->source_type).' / '.pal_h($r->activity_time).'</small>';
  $prod=$r->no_production_order?'<strong>'.pal_h($r->no_production_order).'</strong><br><small>'.pal_h(trim($r->operation_no.' - '.$r->operation_name,' -')).'</small>':'<span class="text-muted">General shop floor activity</span>';
  $wc='<strong>'.pal_h($r->work_center).'</strong><br><small>'.pal_h(trim($r->plant_code.' / '.$r->shift_code,' /')).'</small>';
  $event=pal_badge($r->activity_type,$typeMap).' '.pal_badge($r->severity,$sevMap).'<br><small>'.pal_h($r->operator_name?:$r->created_by).'</small>';
  $text='<strong>'.pal_h($r->activity_text).'</strong>';
  if($r->action_taken)$text.='<br><small>Action: '.pal_h($r->action_taken).'</small>';
  $data[]=array($no++,$act,$doc,$prod,$wc,$event,$text,pal_badge($r->status,$statusMap),pal_h($r->created_by));
}
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
