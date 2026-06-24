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
function idt_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function idt_num($v,$d=2){return number_format((float)$v,$d,',','.');}
function idt_status($s){$m=array('DRAFT'=>'default','POSTED'=>'success','CANCELLED'=>'danger');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.idt_h($s).'</span>';}
$req=$_REQUEST;$draw=isset($req['draw'])?(int)$req['draw']:0;$start=isset($req['start'])?max(0,(int)$req['start']):0;$length=isset($req['length'])?(int)$req['length']:25;if($length<=0)$length=25;
$p=array();$w=" WHERE 1=1 ";
if(!empty($_POST['tgl_awal'])&&!empty($_POST['tgl_akhir'])){$w.=" AND downtime_date BETWEEN ? AND ? ";$p[]=$_POST['tgl_awal'];$p[]=$_POST['tgl_akhir'];}
if(!empty($_POST['plant'])){$w.=" AND plant_code=? ";$p[]=$_POST['plant'];}
if(!empty($_POST['work_center'])){$w.=" AND work_center LIKE ? ";$p[]='%'.trim($_POST['work_center']).'%';}
if(!empty($_POST['category'])){$w.=" AND downtime_category=? ";$p[]=$_POST['category'];}
if(!empty($_POST['status'])){$w.=" AND approval_status=? ";$p[]=$_POST['status'];}
if(!empty($_POST['keyword'])){$kw='%'.trim($_POST['keyword']).'%';$w.=" AND (downtime_no LIKE ? OR no_production_order LIKE ? OR work_center LIKE ? OR operation_name LIKE ? OR reason_code LIKE ? OR reason_text LIKE ? OR action_taken LIKE ?) ";for($i=0;$i<7;$i++)$p[]=$kw;}
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_production_downtime ".$w,$p);
$rows=$db->query("SELECT * FROM erp_production_downtime ".$w." ORDER BY downtime_date DESC,start_time DESC,id DESC LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<button class="btn btn-info btn-xs btn-idt-detail" data-id="'.(int)$r->id.'" title="Detail"><i class="fa fa-eye"></i></button>';
  if($r->approval_status==='POSTED')$act.=' <button class="btn btn-danger btn-xs btn-idt-cancel" data-id="'.(int)$r->id.'" data-no="'.idt_h($r->downtime_no).'" title="Cancel"><i class="fa fa-ban"></i></button>';
  $doc='<strong>'.idt_h($r->downtime_no).'</strong><br><small>'.idt_h($r->downtime_date).'</small>';
  $po=$r->no_production_order?'<strong>'.idt_h($r->no_production_order).'</strong><br><small>'.idt_h($r->operation_no.' - '.$r->operation_name).'</small>':'<span class="text-muted">General downtime</span>';
  $wc='<strong>'.idt_h($r->work_center).'</strong><br><small>'.idt_h($r->plant_code.' / '.$r->shift_code).'</small>';
  $time=idt_h($r->start_time).'<br><small>s/d '.idt_h($r->end_time).'</small>';
  $reason='<strong>'.idt_h($r->downtime_category).'</strong><br><small>'.idt_h(($r->reason_code?:'-').' - '.$r->reason_text).'</small>';
  $data[]=array($no++,$act,$doc,$po,$wc,$time,idt_num($r->duration_minutes,0).' min',$reason,idt_h($r->impact_type),idt_h($r->responsibility?:'-'),idt_status($r->approval_status),idt_h($r->created_by));
}
echo json_encode(array('draw'=>$draw,'recordsTotal'=>intval($cnt?$cnt->jml:0),'recordsFiltered'=>intval($cnt?$cnt->jml:0),'data'=>$data));
?>
