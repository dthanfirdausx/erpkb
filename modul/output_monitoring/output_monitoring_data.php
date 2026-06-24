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
function om_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function om_num($v,$d=2){return number_format((float)$v,$d,',','.');}
function om_pct($a,$b){$b=(float)$b;return $b>0?max(0,min(999,round(((float)$a/$b)*100,1))):0;}
function om_bar($pct,$cls){$w=min(100,$pct);return '<div class="progress progress-xs" style="margin-bottom:4px"><div class="progress-bar progress-bar-'.$cls.'" style="width:'.$w.'%"></div></div><small>'.$pct.'%</small>';}
function om_status_text($s){$m=array('NO_CONFIRMATION'=>'production_no_confirmation','WAITING_GR'=>'production_waiting_gr','PARTIAL_GR'=>'production_partial_gr','COMPLETED'=>'production_completed','OVER_GR'=>'production_over_gr');return prod_t(isset($m[$s])?$m[$s]:'',$s);}
function om_status($s){$m=array('NO_CONFIRMATION'=>'default','WAITING_GR'=>'warning','PARTIAL_GR'=>'info','COMPLETED'=>'success','OVER_GR'=>'danger');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.om_h(om_status_text($s)).'</span>';}
function om_po_badge($s){$m=array('RELEASED'=>'primary','IN_PROCESS'=>'info','CONFIRMED'=>'success','TECO'=>'warning','CLOSED'=>'success','CANCELLED'=>'danger');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.om_h($s).'</span>';}
$req=$_REQUEST;$draw=isset($req['draw'])?(int)$req['draw']:0;$start=isset($req['start'])?max(0,(int)$req['start']):0;$length=isset($req['length'])?(int)$req['length']:25;if($length<=0)$length=25;
$from=!empty($_POST['tgl_awal'])?$_POST['tgl_awal']:date('Y-m-01');$to=!empty($_POST['tgl_akhir'])?$_POST['tgl_akhir']:date('Y-m-d');
$p=array($from,$to);$w=" WHERE p.status IN ('RELEASED','IN_PROCESS','CONFIRMED','TECO','CLOSED') AND p.start_date BETWEEN ? AND ? ";
if(!empty($_POST['plant'])){$w.=" AND p.plant=? ";$p[]=$_POST['plant'];}
if(!empty($_POST['po_status'])){$w.=" AND p.status=? ";$p[]=$_POST['po_status'];}
if(!empty($_POST['output_status'])){if($_POST['output_status']==='NO_CONFIRMATION')$w.=" AND COALESCE(conf.yield_qty,0)=0 ";if($_POST['output_status']==='WAITING_GR')$w.=" AND COALESCE(conf.yield_qty,0)>0 AND COALESCE(gr.gr_qty,0)=0 ";if($_POST['output_status']==='PARTIAL_GR')$w.=" AND COALESCE(gr.gr_qty,0)>0 AND COALESCE(gr.gr_qty,0)<COALESCE(conf.yield_qty,0) ";if($_POST['output_status']==='COMPLETED')$w.=" AND COALESCE(conf.yield_qty,0)>0 AND COALESCE(gr.gr_qty,0)=COALESCE(conf.yield_qty,0) ";if($_POST['output_status']==='OVER_GR')$w.=" AND COALESCE(gr.gr_qty,0)>COALESCE(conf.yield_qty,0) ";}
if(!empty($_POST['keyword'])){$kw='%'.trim($_POST['keyword']).'%';$w.=" AND (p.no_production_order LIKE ? OR p.material_code LIKE ? OR p.material_name LIKE ? OR p.no_sales_order LIKE ? OR p.customer_po LIKE ?) ";for($i=0;$i<5;$i++)$p[]=$kw;}
$joins=" LEFT JOIN (
    SELECT id_production_order,COUNT(*) conf_count,SUM(CASE WHEN status='POSTED' THEN yield_qty ELSE 0 END) yield_qty,SUM(CASE WHEN status='POSTED' THEN scrap_qty ELSE 0 END) scrap_qty,SUM(CASE WHEN status='POSTED' THEN rework_qty ELSE 0 END) rework_qty,MAX(posting_date) last_confirmation
    FROM production_order_confirmation GROUP BY id_production_order
  ) conf ON conf.id_production_order=p.id_production_order
  LEFT JOIN (
    SELECT h.id_production_order,COUNT(DISTINCT h.id) gr_count,SUM(CASE WHEN h.status='POSTED' THEN d.qty ELSE 0 END) gr_qty,MAX(h.posting_date) last_gr
    FROM erp_gr_production h JOIN erp_gr_production_detail d ON d.gr_id=h.id GROUP BY h.id_production_order
  ) gr ON gr.id_production_order=p.id_production_order
  LEFT JOIN (
    SELECT id_production_order,COUNT(*) downtime_count,SUM(CASE WHEN approval_status='POSTED' THEN duration_minutes ELSE 0 END) downtime_minutes
    FROM erp_production_downtime GROUP BY id_production_order
  ) dt ON dt.id_production_order=p.id_production_order ";
$cnt=$db->fetch("SELECT COUNT(*) jml FROM production_order p $joins $w",$p);
$rows=$db->query("SELECT p.*,COALESCE(conf.conf_count,0) conf_count,COALESCE(conf.yield_qty,0) yield_qty,COALESCE(conf.scrap_qty,0) scrap_qty,COALESCE(conf.rework_qty,0) rework_qty,conf.last_confirmation,COALESCE(gr.gr_count,0) gr_count,COALESCE(gr.gr_qty,0) gr_qty,gr.last_gr,COALESCE(dt.downtime_count,0) downtime_count,COALESCE(dt.downtime_minutes,0) downtime_minutes FROM production_order p $joins $w ORDER BY FIELD(p.status,'IN_PROCESS','RELEASED','CONFIRMED','TECO','CLOSED'),p.start_date DESC,p.id_production_order DESC LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $outstanding=(float)$r->yield_qty-(float)$r->gr_qty;$status='NO_CONFIRMATION';if($r->yield_qty>0&&$r->gr_qty==0)$status='WAITING_GR';if($r->gr_qty>0&&$r->gr_qty<$r->yield_qty)$status='PARTIAL_GR';if($r->yield_qty>0&&abs((float)$r->gr_qty-(float)$r->yield_qty)<0.00001)$status='COMPLETED';if($r->gr_qty>$r->yield_qty)$status='OVER_GR';
  $yieldPct=om_pct($r->yield_qty,$r->order_qty);$grPct=om_pct($r->gr_qty,$r->yield_qty);
  $act='<button class="btn btn-info btn-xs btn-om-detail" data-id="'.(int)$r->id_production_order.'" title="'.prod_h('common_detail','Detail').'"><i class="fa fa-eye"></i></button> <a class="btn btn-success btn-xs" href="'.base_index().'production-confirmation" title="'.prod_h('production_confirmation_short','Confirmation').'"><i class="fa fa-check-square-o"></i></a> <a class="btn btn-primary btn-xs" href="'.base_index().'gr-from-production-order" title="'.prod_h('production_gr_production','GR Production').'"><i class="fa fa-arrow-down"></i></a>';
  $doc='<strong>'.om_h($r->no_production_order).'</strong><br><small>'.om_h($r->order_strategy.' / '.$r->priority).'</small>';
  $mat='<strong>'.om_h($r->material_code).'</strong><br><small>'.om_h($r->material_name).'</small>';
  $qty=prod_h('production_order_qty','Order Qty').' '.om_num($r->order_qty,5).' '.om_h($r->uom).'<br><small>'.prod_h('production_completed_qty','Completed').' '.om_num($r->completed_qty,5).'</small>';
  $confirm=prod_h('production_yield','Yield').' '.om_num($r->yield_qty,5).'<br><small>'.prod_h('production_scrap','Scrap').' '.om_num($r->scrap_qty,5).' | '.prod_h('production_rework','Rework').' '.om_num($r->rework_qty,5).'</small><br>'.om_bar($yieldPct,$yieldPct>=100?'success':'warning');
  $gr=prod_h('production_gr_short','GR').' '.om_num($r->gr_qty,5).'<br><small>'.prod_h('production_outstanding','Outstanding').' '.om_num($outstanding,5).'</small><br>'.om_bar($grPct,$grPct>=100?'success':'info');
  $dates=prod_h('production_confirm_label','Confirm').' '.om_h($r->last_confirmation?:'-').'<br><small>'.prod_h('production_gr_short','GR').' '.om_h($r->last_gr?:'-').'</small>';
  $loss='<span class="badge bg-red">'.(int)$r->downtime_count.' DT</span><br><small>'.om_num($r->downtime_minutes,0).' '.prod_h('production_minutes_short','min').'</small>';
  $docs='<span class="badge bg-green">'.(int)$r->conf_count.' CNF</span> <span class="badge bg-purple">'.(int)$r->gr_count.' GR</span>';
  $data[]=array($no++,$act,$doc,$mat,$qty,om_h($r->plant.' / '.$r->storage_location),$confirm,$gr,$dates,$loss,$docs,om_status($status),om_po_badge($r->status));
}
echo json_encode(array('draw'=>$draw,'recordsTotal'=>intval($cnt?$cnt->jml:0),'recordsFiltered'=>intval($cnt?$cnt->jml:0),'data'=>$data));
?>
