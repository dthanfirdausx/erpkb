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
function pom_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function pom_num($v,$d=2){return number_format((float)$v,$d,',','.');}
function pom_badge($s){$m=array('CREATED'=>'default','RELEASED'=>'primary','IN_PROCESS'=>'info','CONFIRMED'=>'success','TECO'=>'warning','CLOSED'=>'success','CANCELLED'=>'danger');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.pom_h($s).'</span>';}
function pom_pct($done,$total){$total=(float)$total;if($total<=0)return 0;return max(0,min(100,round(((float)$done/$total)*100,1)));}
function pom_bar($pct,$cls){return '<div class="progress progress-xs" style="margin-bottom:4px"><div class="progress-bar progress-bar-'.$cls.'" style="width:'.$pct.'%"></div></div><small>'.$pct.'%</small>';}
$req=$_REQUEST;$draw=isset($req['draw'])?(int)$req['draw']:0;$start=isset($req['start'])?max(0,(int)$req['start']):0;$length=isset($req['length'])?(int)$req['length']:25;if($length<=0)$length=25;
$p=array();$w=" WHERE 1=1 ";
if(!empty($_POST['tgl_awal'])&&!empty($_POST['tgl_akhir'])){$w.=" AND p.start_date BETWEEN ? AND ? ";$p[]=$_POST['tgl_awal'];$p[]=$_POST['tgl_akhir'];}
if(!empty($_POST['status'])){$w.=" AND p.status=? ";$p[]=$_POST['status'];}
if(!empty($_POST['plant'])){$w.=" AND p.plant=? ";$p[]=$_POST['plant'];}
if(!empty($_POST['exception'])){if($_POST['exception']==='SHORTAGE')$w.=" AND COALESCE(mat.material_progress,0)<100 ";if($_POST['exception']==='NO_CONFIRMATION')$w.=" AND COALESCE(conf.yield_qty,0)=0 ";if($_POST['exception']==='NO_GR')$w.=" AND COALESCE(gr.gr_qty,0)=0 ";}
if(!empty($_POST['keyword'])){$kw='%'.trim($_POST['keyword']).'%';$w.=" AND (p.no_production_order LIKE ? OR p.no_sales_order LIKE ? OR p.customer_po LIKE ? OR p.material_code LIKE ? OR p.material_name LIKE ? OR p.production_version_no LIKE ? OR p.bom_no LIKE ? OR p.routing_no LIKE ?) ";for($i=0;$i<8;$i++)$p[]=$kw;}
$joins=" LEFT JOIN (
    SELECT id_production_order,COUNT(*) item_count,SUM(required_qty) required_qty,SUM(issued_qty) issued_qty,
           CASE WHEN SUM(required_qty)>0 THEN SUM(issued_qty)/SUM(required_qty)*100 ELSE 0 END material_progress
    FROM production_order_material GROUP BY id_production_order
  ) mat ON mat.id_production_order=p.id_production_order
  LEFT JOIN (
    SELECT production_id id_production_order,COUNT(*) issue_count,SUM(CASE WHEN status='POSTED' THEN 1 ELSE 0 END) posted_issue_count
    FROM erp_issue_production GROUP BY production_id
  ) gi ON gi.id_production_order=p.id_production_order
  LEFT JOIN (
    SELECT id_production_order,COUNT(*) conf_count,SUM(CASE WHEN status='POSTED' THEN yield_qty ELSE 0 END) yield_qty,SUM(CASE WHEN status='POSTED' THEN scrap_qty ELSE 0 END) scrap_qty,MAX(posting_date) last_confirmation
    FROM production_order_confirmation GROUP BY id_production_order
  ) conf ON conf.id_production_order=p.id_production_order
  LEFT JOIN (
    SELECT id_production_order,COUNT(*) gr_count,SUM(CASE WHEN status='POSTED' THEN d.qty ELSE 0 END) gr_qty,MAX(posting_date) last_gr
    FROM erp_gr_production h JOIN erp_gr_production_detail d ON d.gr_id=h.id GROUP BY id_production_order
  ) gr ON gr.id_production_order=p.id_production_order
  LEFT JOIN (
    SELECT id_production_order,COUNT(*) sched_count,MAX(dispatch_status) schedule_status,MIN(planned_start) first_start,MAX(planned_finish) last_finish
    FROM erp_production_schedule GROUP BY id_production_order
  ) sch ON sch.id_production_order=p.id_production_order ";
$cnt=$db->fetch("SELECT COUNT(*) jml FROM production_order p $joins $w",$p);
$rows=$db->query("SELECT p.*,COALESCE(mat.item_count,0)item_count,COALESCE(mat.required_qty,0) required_qty,COALESCE(mat.issued_qty,0) issued_qty,COALESCE(mat.material_progress,0) material_progress,COALESCE(gi.posted_issue_count,0) posted_issue_count,COALESCE(conf.conf_count,0) conf_count,COALESCE(conf.yield_qty,0) yield_qty,COALESCE(conf.scrap_qty,0) conf_scrap_qty,conf.last_confirmation,COALESCE(gr.gr_count,0) gr_count,COALESCE(gr.gr_qty,0) gr_qty,gr.last_gr,COALESCE(sch.sched_count,0) sched_count,sch.schedule_status,sch.first_start,sch.last_finish FROM production_order p $joins $w ORDER BY FIELD(p.status,'IN_PROCESS','RELEASED','CONFIRMED','CREATED','TECO','CLOSED','CANCELLED'),p.start_date ASC,p.id_production_order DESC LIMIT ".$start.",".$length,$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $matPct=pom_pct($r->issued_qty,$r->required_qty);$confPct=pom_pct($r->yield_qty,$r->order_qty);$grPct=pom_pct($r->gr_qty,$r->order_qty);
  $exceptions=array();if($matPct<100&&in_array($r->status,array('RELEASED','IN_PROCESS'),true))$exceptions[]='<span class="label label-danger">Material</span>';if((float)$r->yield_qty<(float)$r->order_qty&&in_array($r->status,array('IN_PROCESS','CONFIRMED'),true))$exceptions[]='<span class="label label-warning">Confirm</span>';if((float)$r->gr_qty<(float)$r->yield_qty&&$r->yield_qty>0)$exceptions[]='<span class="label label-info">GR</span>';if(!$exceptions)$exceptions[]='<span class="label label-success">OK</span>';
  $act='<button class="btn btn-info btn-xs btn-pom-detail" data-id="'.(int)$r->id_production_order.'" title="Detail"><i class="fa fa-eye"></i></button>';
  $doc='<strong>'.pom_h($r->no_production_order).'</strong><br><small>'.pom_h($r->order_strategy.' / '.$r->priority).'</small>';
  $mat='<strong>'.pom_h($r->material_code).'</strong><br><small>'.pom_h($r->material_name).'</small>';
  $qty='Order '.pom_num($r->order_qty,5).' '.pom_h($r->uom).'<br><small>Done '.pom_num($r->completed_qty,5).' | Scrap '.pom_num($r->scrap_qty,5).'</small>';
  $schedule=$r->sched_count?('<strong>'.pom_h($r->schedule_status).'</strong><br><small>'.pom_h(($r->first_start?:'-').' s/d '.($r->last_finish?:'-')).'</small>'):'<span class="text-muted">Belum dijadwalkan</span>';
  $progress='<strong>Material</strong> '.pom_bar($matPct,$matPct>=100?'success':'danger').'<strong>Confirm</strong> '.pom_bar($confPct,$confPct>=100?'success':'warning').'<strong>GR</strong> '.pom_bar($grPct,$grPct>=100?'success':'info');
  $docs='<span class="badge bg-aqua">'.(int)$r->posted_issue_count.' GI</span> <span class="badge bg-green">'.(int)$r->conf_count.' CNF</span> <span class="badge bg-purple">'.(int)$r->gr_count.' GR</span>';
  $data[]=array($no++,$act,$doc,$mat,$qty,pom_h($r->plant.' / '.$r->storage_location),pom_h($r->start_date.' s/d '.$r->finish_date),$schedule,$progress,$docs,implode(' ',$exceptions),pom_badge($r->status));
}
echo json_encode(array('draw'=>$draw,'recordsTotal'=>intval($cnt?$cnt->jml:0),'recordsFiltered'=>intval($cnt?$cnt->jml:0),'data'=>$data));
?>
