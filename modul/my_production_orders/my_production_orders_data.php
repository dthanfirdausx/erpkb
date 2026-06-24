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
function mpo_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function mpo_num($v,$d=2){return number_format((float)$v,$d,',','.');}
function mpo_pct($a,$b){$b=(float)$b;return $b>0?max(0,min(100,round(((float)$a/$b)*100,1))):0;}
function mpo_bar($pct,$cls){return '<div class="progress progress-xs" style="margin-bottom:4px"><div class="progress-bar progress-bar-'.$cls.'" style="width:'.$pct.'%"></div></div><small>'.$pct.'%</small>';}
function mpo_badge($s){$m=array('CREATED'=>'default','RELEASED'=>'primary','IN_PROCESS'=>'info','CONFIRMED'=>'success','TECO'=>'warning','CLOSED'=>'success','CANCELLED'=>'danger');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.mpo_h($s).'</span>';}
$req=$_REQUEST;$draw=isset($req['draw'])?(int)$req['draw']:0;$start=isset($req['start'])?max(0,(int)$req['start']):0;$length=isset($req['length'])?(int)$req['length']:25;if($length<=0)$length=25;
$p=array();$w=" WHERE p.status IN ('RELEASED','IN_PROCESS','CONFIRMED') ";
if(!empty($_POST['tgl_awal'])&&!empty($_POST['tgl_akhir'])){$w.=" AND p.start_date BETWEEN ? AND ? ";$p[]=$_POST['tgl_awal'];$p[]=$_POST['tgl_akhir'];}
if(!empty($_POST['status'])){$w.=" AND p.status=? ";$p[]=$_POST['status'];}
if(!empty($_POST['plant'])){$w.=" AND p.plant=? ";$p[]=$_POST['plant'];}
if(!empty($_POST['work_center'])){$w.=" AND EXISTS(SELECT 1 FROM production_order_operation ox WHERE ox.id_production_order=p.id_production_order AND ox.work_center LIKE ?) ";$p[]='%'.trim($_POST['work_center']).'%';}
if(!empty($_POST['readiness'])){if($_POST['readiness']==='READY')$w.=" AND COALESCE(mat.material_progress,0)>=100 ";if($_POST['readiness']==='SHORTAGE')$w.=" AND COALESCE(mat.material_progress,0)<100 ";if($_POST['readiness']==='TO_CONFIRM')$w.=" AND COALESCE(conf.yield_qty,0)<p.order_qty ";if($_POST['readiness']==='TO_GR')$w.=" AND COALESCE(gr.gr_qty,0)<COALESCE(conf.yield_qty,0) AND COALESCE(conf.yield_qty,0)>0 ";}
if(!empty($_POST['keyword'])){$kw='%'.trim($_POST['keyword']).'%';$w.=" AND (p.no_production_order LIKE ? OR p.material_code LIKE ? OR p.material_name LIKE ? OR p.no_sales_order LIKE ? OR p.customer_po LIKE ?) ";for($i=0;$i<5;$i++)$p[]=$kw;}
$joins=" LEFT JOIN (
    SELECT id_production_order,COUNT(*) component_count,SUM(required_qty) required_qty,SUM(issued_qty) issued_qty,
           CASE WHEN SUM(required_qty)>0 THEN SUM(issued_qty)/SUM(required_qty)*100 ELSE 100 END material_progress
    FROM production_order_material GROUP BY id_production_order
  ) mat ON mat.id_production_order=p.id_production_order
  LEFT JOIN (
    SELECT id_production_order,COUNT(*) operation_count,SUM(status='OPEN') open_ops,SUM(status='STARTED') started_ops,SUM(status='FINISHED') finished_ops,
           GROUP_CONCAT(DISTINCT work_center ORDER BY operation_no SEPARATOR ', ') work_centers
    FROM production_order_operation GROUP BY id_production_order
  ) ops ON ops.id_production_order=p.id_production_order
  LEFT JOIN (
    SELECT id_production_order,COUNT(*) conf_count,SUM(CASE WHEN status='POSTED' THEN yield_qty ELSE 0 END) yield_qty,SUM(CASE WHEN status='POSTED' THEN scrap_qty ELSE 0 END) scrap_qty,MAX(posting_date) last_confirmation
    FROM production_order_confirmation GROUP BY id_production_order
  ) conf ON conf.id_production_order=p.id_production_order
  LEFT JOIN (
    SELECT id_production_order,COUNT(*) sched_count,MAX(dispatch_status) schedule_status,MIN(planned_start) first_start,MAX(planned_finish) last_finish
    FROM erp_production_schedule GROUP BY id_production_order
  ) sch ON sch.id_production_order=p.id_production_order
  LEFT JOIN (
    SELECT id_production_order,COUNT(*) downtime_count,SUM(CASE WHEN approval_status='POSTED' THEN duration_minutes ELSE 0 END) downtime_minutes
    FROM erp_production_downtime GROUP BY id_production_order
  ) dt ON dt.id_production_order=p.id_production_order
  LEFT JOIN (
    SELECT h.id_production_order,SUM(CASE WHEN h.status='POSTED' THEN d.qty ELSE 0 END) gr_qty
    FROM erp_gr_production h JOIN erp_gr_production_detail d ON d.gr_id=h.id GROUP BY h.id_production_order
  ) gr ON gr.id_production_order=p.id_production_order ";
$cnt=$db->fetch("SELECT COUNT(*) jml FROM production_order p $joins $w",$p);
$rows=$db->query("SELECT p.*,COALESCE(mat.component_count,0) component_count,COALESCE(mat.required_qty,0) required_qty,COALESCE(mat.issued_qty,0) issued_qty,COALESCE(mat.material_progress,100) material_progress,COALESCE(ops.operation_count,0) operation_count,COALESCE(ops.open_ops,0) open_ops,COALESCE(ops.started_ops,0) started_ops,COALESCE(ops.finished_ops,0) finished_ops,ops.work_centers,COALESCE(conf.conf_count,0) conf_count,COALESCE(conf.yield_qty,0) yield_qty,COALESCE(conf.scrap_qty,0) conf_scrap_qty,conf.last_confirmation,COALESCE(sch.sched_count,0) sched_count,sch.schedule_status,sch.first_start,sch.last_finish,COALESCE(dt.downtime_count,0) downtime_count,COALESCE(dt.downtime_minutes,0) downtime_minutes,COALESCE(gr.gr_qty,0) gr_qty FROM production_order p $joins $w ORDER BY FIELD(p.status,'IN_PROCESS','RELEASED','CONFIRMED'),FIELD(p.priority,'URGENT','HIGH','NORMAL','LOW'),p.start_date ASC,p.id_production_order DESC LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $matPct=round((float)$r->material_progress,1);$confPct=mpo_pct($r->yield_qty,$r->order_qty);$grPct=mpo_pct($r->gr_qty,$r->order_qty);
  $ready=($matPct>=100)?'<span class="label label-success">READY</span>':'<span class="label label-danger">SHORTAGE</span>';
  $exec=array();if($r->started_ops>0)$exec[]='<span class="label label-info">STARTED '.$r->started_ops.'</span>';if($r->open_ops>0)$exec[]='<span class="label label-default">OPEN '.$r->open_ops.'</span>';if($r->finished_ops>0)$exec[]='<span class="label label-success">FINISH '.$r->finished_ops.'</span>';if(!$exec)$exec[]='<span class="label label-default">NO OPS</span>';
  $act='<button class="btn btn-info btn-xs btn-mpo-detail" data-id="'.(int)$r->id_production_order.'" title="Detail"><i class="fa fa-eye"></i></button> <a class="btn btn-success btn-xs" href="'.base_index().'production-confirmation" title="Confirmation"><i class="fa fa-check-square-o"></i></a> <a class="btn btn-warning btn-xs" href="'.base_index().'input-downtime" title="Downtime"><i class="fa fa-clock-o"></i></a>';
  $doc='<strong>'.mpo_h($r->no_production_order).'</strong><br><small>'.mpo_h($r->order_strategy.' / '.$r->priority).'</small>';
  $mat='<strong>'.mpo_h($r->material_code).'</strong><br><small>'.mpo_h($r->material_name).'</small>';
  $qty='Order '.mpo_num($r->order_qty,5).' '.mpo_h($r->uom).'<br><small>Yield '.mpo_num($r->yield_qty,5).' | Scrap '.mpo_num($r->conf_scrap_qty,5).'</small>';
  $ops=implode(' ',$exec).'<br><small>'.mpo_h($r->work_centers?:'-').'</small>';
  $plan=$r->sched_count?('<strong>'.mpo_h($r->schedule_status).'</strong><br><small>'.mpo_h(($r->first_start?:'-').' s/d '.($r->last_finish?:'-')).'</small>'):'<span class="text-muted">Not scheduled</span>';
  $progress='<strong>Material</strong> '.mpo_bar($matPct,$matPct>=100?'success':'danger').'<strong>Confirm</strong> '.mpo_bar($confPct,$confPct>=100?'success':'warning').'<strong>GR</strong> '.mpo_bar($grPct,$grPct>=100?'success':'info');
  $loss='<span class="badge bg-red">'.(int)$r->downtime_count.' DT</span><br><small>'.mpo_num($r->downtime_minutes,0).' min</small>';
  $data[]=array($no++,$act,$doc,$mat,$qty,mpo_h($r->plant.' / '.$r->storage_location),$ops,$ready,$plan,$progress,$loss,mpo_badge($r->status));
}
echo json_encode(array('draw'=>$draw,'recordsTotal'=>intval($cnt?$cnt->jml:0),'recordsFiltered'=>intval($cnt?$cnt->jml:0),'data'=>$data));
?>
