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
function wip_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function wip_num($v,$d=5){return number_format((float)$v,$d,',','.');}
function wip_pct($a,$b){$b=(float)$b;return $b>0?max(0,min(100,round(((float)$a/$b)*100,1))):0;}
function wip_status($r){
  if($r->status==='CANCELLED')return '<span class="label label-danger">CANCELLED</span>';
  if((float)$r->gr_qty >= (float)$r->order_qty && (float)$r->order_qty>0)return '<span class="label label-success">OUTPUT RECEIVED</span>';
  if((float)$r->yield_qty > (float)$r->gr_qty)return '<span class="label label-warning">AWAITING GR</span>';
  if((float)$r->yield_qty > 0)return '<span class="label label-info">CONFIRMED WIP</span>';
  if((float)$r->issued_qty > 0)return '<span class="label label-primary">MATERIAL ISSUED</span>';
  return '<span class="label label-default">NOT STARTED</span>';
}
function wip_bar($pct,$cls){return '<div class="progress progress-xs" style="margin-bottom:4px"><div class="progress-bar progress-bar-'.$cls.'" style="width:'.$pct.'%"></div></div><small>'.$pct.'%</small>';}
$req=$_REQUEST;$draw=isset($req['draw'])?(int)$req['draw']:0;$start=isset($req['start'])?max(0,(int)$req['start']):0;$length=isset($req['length'])?(int)$req['length']:25;if($length<=0)$length=25;
$p=array();$w=" WHERE p.status IN ('RELEASED','IN_PROCESS','CONFIRMED','TECO') ";
if(!empty($_POST['tgl_awal'])&&!empty($_POST['tgl_akhir'])){$w.=" AND p.start_date BETWEEN ? AND ? ";$p[]=$_POST['tgl_awal'];$p[]=$_POST['tgl_akhir'];}
if(!empty($_POST['status'])){$w.=" AND p.status=? ";$p[]=$_POST['status'];}
if(!empty($_POST['plant'])){$w.=" AND p.plant=? ";$p[]=$_POST['plant'];}
if(!empty($_POST['wip_state'])){
  if($_POST['wip_state']==='MATERIAL_ISSUED')$w.=" AND COALESCE(mat.issued_qty,0)>0 AND COALESCE(conf.yield_qty,0)=0 ";
  if($_POST['wip_state']==='AWAITING_GR')$w.=" AND COALESCE(conf.yield_qty,0)>COALESCE(gr.gr_qty,0) ";
  if($_POST['wip_state']==='SHORT_CONFIRMATION')$w.=" AND COALESCE(conf.yield_qty,0)<p.order_qty ";
}
if(!empty($_POST['keyword'])){$kw='%'.trim($_POST['keyword']).'%';$w.=" AND (p.no_production_order LIKE ? OR p.no_sales_order LIKE ? OR p.customer_po LIKE ? OR p.material_code LIKE ? OR p.material_name LIKE ?) ";for($i=0;$i<5;$i++)$p[]=$kw;}
$joins=" LEFT JOIN (
    SELECT id_production_order,SUM(required_qty) required_qty,SUM(issued_qty) issued_qty,SUM(remaining_qty) remaining_qty,COUNT(*) component_count
    FROM production_order_material GROUP BY id_production_order
  ) mat ON mat.id_production_order=p.id_production_order
  LEFT JOIN (
    SELECT production_id id_production_order,COUNT(*) gi_count,MAX(posting_date) last_gi
    FROM erp_issue_production WHERE status='POSTED' GROUP BY production_id
  ) gi ON gi.id_production_order=p.id_production_order
  LEFT JOIN (
    SELECT id_production_order,COUNT(*) conf_count,SUM(CASE WHEN status='POSTED' THEN yield_qty ELSE 0 END) yield_qty,SUM(CASE WHEN status='POSTED' THEN scrap_qty ELSE 0 END) scrap_qty,SUM(CASE WHEN status='POSTED' THEN rework_qty ELSE 0 END) rework_qty,MAX(posting_date) last_confirmation
    FROM production_order_confirmation GROUP BY id_production_order
  ) conf ON conf.id_production_order=p.id_production_order
  LEFT JOIN (
    SELECT h.id_production_order,COUNT(DISTINCT h.id) gr_count,SUM(CASE WHEN h.status='POSTED' THEN d.qty ELSE 0 END) gr_qty,MAX(h.posting_date) last_gr
    FROM erp_gr_production h JOIN erp_gr_production_detail d ON d.gr_id=h.id GROUP BY h.id_production_order
  ) gr ON gr.id_production_order=p.id_production_order ";
$cnt=$db->fetch("SELECT COUNT(*) jml FROM production_order p $joins $w",$p);
$rows=$db->query("SELECT p.*,COALESCE(mat.required_qty,0) required_qty,COALESCE(mat.issued_qty,0) issued_qty,COALESCE(mat.remaining_qty,0) material_remaining_qty,COALESCE(mat.component_count,0) component_count,COALESCE(gi.gi_count,0) gi_count,gi.last_gi,COALESCE(conf.conf_count,0) conf_count,COALESCE(conf.yield_qty,0) yield_qty,COALESCE(conf.scrap_qty,0) conf_scrap_qty,COALESCE(conf.rework_qty,0) rework_qty,conf.last_confirmation,COALESCE(gr.gr_count,0) gr_count,COALESCE(gr.gr_qty,0) gr_qty,gr.last_gr FROM production_order p $joins $w ORDER BY p.start_date ASC,p.id_production_order DESC LIMIT ".$start.",".$length,$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $matPct=wip_pct($r->issued_qty,$r->required_qty);$confPct=wip_pct($r->yield_qty,$r->order_qty);$grPct=wip_pct($r->gr_qty,$r->order_qty);
  $outConf=max((float)$r->order_qty-(float)$r->yield_qty,0);$outGr=max((float)$r->yield_qty-(float)$r->gr_qty,0);
  $age=$r->start_date?max(0,floor((strtotime(date('Y-m-d'))-strtotime($r->start_date))/86400)):0;
  $act='<button class="btn btn-info btn-xs btn-wip-detail" data-id="'.(int)$r->id_production_order.'" title="Detail"><i class="fa fa-eye"></i></button>';
  $doc='<strong>'.wip_h($r->no_production_order).'</strong><br><small>'.wip_h($r->order_strategy.' / '.$r->priority).'</small>';
  $mat='<strong>'.wip_h($r->material_code).'</strong><br><small>'.wip_h($r->material_name).'</small>';
  $qty='Order '.wip_num($r->order_qty).' '.wip_h($r->uom).'<br><small>Yield '.wip_num($r->yield_qty).' | GR '.wip_num($r->gr_qty).'</small>';
  $progress='<strong>Issue</strong> '.wip_bar($matPct,$matPct>=100?'success':'warning').'<strong>Confirm</strong> '.wip_bar($confPct,$confPct>=100?'success':'info').'<strong>GR</strong> '.wip_bar($grPct,$grPct>=100?'success':'danger');
  $outstanding='Confirm '.wip_num($outConf).'<br><small>GR '.wip_num($outGr).' | Scrap '.wip_num($r->conf_scrap_qty).'</small>';
  $dates='Start '.wip_h($r->start_date).'<br><small>Age '.$age.' hari | Last GI '.wip_h($r->last_gi?:'-').' | Last CNF '.wip_h($r->last_confirmation?:'-').' | Last GR '.wip_h($r->last_gr?:'-').'</small>';
  $docs='<span class="badge bg-aqua">'.(int)$r->gi_count.' GI</span> <span class="badge bg-green">'.(int)$r->conf_count.' CNF</span> <span class="badge bg-purple">'.(int)$r->gr_count.' GR</span>';
  $data[]=array($no++,$act,$doc,$mat,$qty,wip_h($r->plant.' / '.$r->storage_location),$dates,$progress,$outstanding,$docs,wip_status($r),'<span class="label label-default">'.wip_h($r->status).'</span>');
}
echo json_encode(array('draw'=>$draw,'recordsTotal'=>intval($cnt?$cnt->jml:0),'recordsFiltered'=>intval($cnt?$cnt->jml:0),'data'=>$data));
?>
