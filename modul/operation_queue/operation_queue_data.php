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
function oq_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function oq_num($v,$d=2){return number_format((float)$v,$d,',','.');}
function oq_pct($a,$b){$b=(float)$b;return $b>0?max(0,min(100,round(((float)$a/$b)*100,1))):0;}
function oq_bar($pct,$cls){return '<div class="progress progress-xs" style="margin-bottom:4px"><div class="progress-bar progress-bar-'.$cls.'" style="width:'.$pct.'%"></div></div><small>'.$pct.'%</small>';}
function oq_badge($s){$m=array('OPEN'=>'default','READY'=>'primary','DISPATCHED'=>'info','STARTED'=>'warning','FINISHED'=>'success','CANCELLED'=>'danger','RELEASED'=>'primary','IN_PROCESS'=>'info','CONFIRMED'=>'success','TECO'=>'warning');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.oq_h($s).'</span>';}
function oq_base(){
  return "SELECT x.*,COALESCE(mat.material_progress,100) material_progress,COALESCE(conf.yield_qty,0) yield_qty,COALESCE(conf.scrap_qty,0) scrap_qty,COALESCE(dt.downtime_count,0) downtime_count,COALESCE(dt.downtime_minutes,0) downtime_minutes
          FROM (
            SELECT d.id queue_id,d.id_operation,h.id_production_order,h.no_production_order,h.plant_code,p.storage_location,h.material_code,h.material_name,h.order_qty,h.uom,h.priority,p.status po_status,d.operation_no,d.operation_name,d.work_center,d.work_center_name,d.shift_code,d.planned_start,d.planned_finish,d.duration_minutes,d.scheduled_qty,d.operation_status,h.dispatch_status,h.schedule_no,'SCHEDULE' source_type
            FROM erp_production_schedule_detail d JOIN erp_production_schedule h ON h.id=d.schedule_id JOIN production_order p ON p.id_production_order=h.id_production_order
            WHERE h.dispatch_status<>'CANCELLED' AND d.operation_status<>'CANCELLED'
            UNION ALL
            SELECT o.id_operation,o.id_operation,p.id_production_order,p.no_production_order,p.plant,p.storage_location,p.material_code,p.material_name,p.order_qty,p.uom,p.priority,p.status,o.operation_no,o.operation_name,o.work_center,o.work_center,NULL,CONCAT(p.start_date,' 08:00:00'),CONCAT(p.finish_date,' 17:00:00'),(COALESCE(o.setup_time,0)+COALESCE(o.machine_time,0)+COALESCE(o.labor_time,0)),p.order_qty,o.status,p.status,NULL,'ROUTING' source_type
            FROM production_order_operation o JOIN production_order p ON p.id_production_order=o.id_production_order
            WHERE p.status IN ('RELEASED','IN_PROCESS','CONFIRMED') AND NOT EXISTS (SELECT 1 FROM erp_production_schedule_detail sd WHERE sd.id_operation=o.id_operation AND sd.operation_status<>'CANCELLED')
          ) x
          LEFT JOIN (
            SELECT id_production_order,CASE WHEN SUM(required_qty)>0 THEN SUM(issued_qty)/SUM(required_qty)*100 ELSE 100 END material_progress
            FROM production_order_material GROUP BY id_production_order
          ) mat ON mat.id_production_order=x.id_production_order
          LEFT JOIN (
            SELECT id_production_order,operation_no,SUM(CASE WHEN status='POSTED' THEN yield_qty ELSE 0 END) yield_qty,SUM(CASE WHEN status='POSTED' THEN scrap_qty ELSE 0 END) scrap_qty
            FROM production_order_confirmation GROUP BY id_production_order,operation_no
          ) conf ON conf.id_production_order=x.id_production_order AND conf.operation_no=x.operation_no
          LEFT JOIN (
            SELECT id_production_order,operation_no,COUNT(*) downtime_count,SUM(CASE WHEN approval_status='POSTED' THEN duration_minutes ELSE 0 END) downtime_minutes
            FROM erp_production_downtime GROUP BY id_production_order,operation_no
          ) dt ON dt.id_production_order=x.id_production_order AND dt.operation_no=x.operation_no";
}
$req=$_REQUEST;$draw=isset($req['draw'])?(int)$req['draw']:0;$start=isset($req['start'])?max(0,(int)$req['start']):0;$length=isset($req['length'])?(int)$req['length']:25;if($length<=0)$length=25;
$from=!empty($_POST['tgl_awal'])?$_POST['tgl_awal']:date('Y-m-d');$to=!empty($_POST['tgl_akhir'])?$_POST['tgl_akhir']:date('Y-m-d');
$p=array($from.' 00:00:00',$to.' 23:59:59');$w=" WHERE q.planned_start BETWEEN ? AND ? ";
if(!empty($_POST['plant'])){$w.=" AND q.plant_code=? ";$p[]=$_POST['plant'];}
if(!empty($_POST['work_center'])){$w.=" AND q.work_center=? ";$p[]=$_POST['work_center'];}
if(!empty($_POST['shift'])){$w.=" AND q.shift_code=? ";$p[]=$_POST['shift'];}
if(!empty($_POST['operation_status'])){$w.=" AND q.operation_status=? ";$p[]=$_POST['operation_status'];}
if(!empty($_POST['readiness'])){if($_POST['readiness']==='READY')$w.=" AND q.material_progress>=100 ";if($_POST['readiness']==='SHORTAGE')$w.=" AND q.material_progress<100 ";if($_POST['readiness']==='DOWNTIME')$w.=" AND q.downtime_count>0 ";}
if(!empty($_POST['keyword'])){$kw='%'.trim($_POST['keyword']).'%';$w.=" AND (q.no_production_order LIKE ? OR q.material_code LIKE ? OR q.material_name LIKE ? OR q.operation_no LIKE ? OR q.operation_name LIKE ? OR q.work_center LIKE ?) ";for($i=0;$i<6;$i++)$p[]=$kw;}
$base=" FROM (".oq_base().") q ".$w;
$cnt=$db->fetch("SELECT COUNT(*) jml ".$base,$p);$total=$cnt?(int)$cnt->jml:0;
$rows=$db->query("SELECT q.* ".$base." ORDER BY FIELD(q.operation_status,'STARTED','DISPATCHED','READY','OPEN','FINISHED'),FIELD(q.priority,'URGENT','HIGH','NORMAL','LOW'),q.planned_start ASC,q.no_production_order,q.operation_no LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $matPct=round((float)$r->material_progress,1);$confPct=oq_pct($r->yield_qty,$r->scheduled_qty?:$r->order_qty);
  $act='<button class="btn btn-info btn-xs btn-oq-detail" data-source="'.oq_h($r->source_type).'" data-id="'.(int)$r->queue_id.'" title="Detail"><i class="fa fa-eye"></i></button> <a class="btn btn-success btn-xs" href="'.base_index().'production-confirmation" title="Confirmation"><i class="fa fa-check-square-o"></i></a> <a class="btn btn-warning btn-xs" href="'.base_index().'input-downtime" title="Downtime"><i class="fa fa-clock-o"></i></a>';
  $doc='<strong>'.oq_h($r->no_production_order).'</strong><br><small>'.oq_h($r->source_type.' / '.$r->priority).'</small>';
  $mat='<strong>'.oq_h($r->material_code).'</strong><br><small>'.oq_h($r->material_name).'</small>';
  $op='<strong>'.oq_h($r->operation_no.' - '.$r->operation_name).'</strong><br><small>'.oq_h($r->work_center.' / '.($r->shift_code?:'-')).'</small>';
  $plan=oq_h($r->planned_start).'<br><small>s/d '.oq_h($r->planned_finish).'</small>';
  $qty='Plan '.oq_num($r->scheduled_qty?:$r->order_qty,5).' '.oq_h($r->uom).'<br><small>Yield '.oq_num($r->yield_qty,5).' | Scrap '.oq_num($r->scrap_qty,5).'</small>';
  $readiness=($matPct>=100?'<span class="label label-success">READY</span>':'<span class="label label-danger">SHORTAGE</span>').'<br><small>Material '.$matPct.'%</small>';
  $progress='<strong>Material</strong> '.oq_bar($matPct,$matPct>=100?'success':'danger').'<strong>Confirm</strong> '.oq_bar($confPct,$confPct>=100?'success':'warning');
  $loss='<span class="badge bg-red">'.(int)$r->downtime_count.' DT</span><br><small>'.oq_num($r->downtime_minutes,0).' min</small>';
  $data[]=array($no++,$act,$doc,$mat,$op,oq_h($r->plant_code.' / '.$r->storage_location),$plan,$qty,$readiness,$progress,$loss,oq_badge($r->operation_status),oq_badge($r->po_status));
}
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
