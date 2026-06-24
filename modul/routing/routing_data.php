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
function rt_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function rt_label($s){$m=array('DRAFT'=>'default','RELEASED'=>'success','INACTIVE'=>'warning','CANCELLED'=>'danger');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.rt_h($s).'</span>';}
function rt_filters(){return array('from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-m-01'),'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-m-t'),'status'=>isset($_POST['status'])?trim($_POST['status']):'','plant'=>isset($_POST['plant'])?trim($_POST['plant']):'','keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):'');}
function rt_where($f,&$p){$w=" WHERE r.valid_from BETWEEN ? AND ? ";$p[]=$f['from'];$p[]=$f['to'];if($f['status']!==''){$w.=" AND r.routing_status=? ";$p[]=$f['status'];}if($f['plant']!==''){$w.=" AND r.plant_code=? ";$p[]=$f['plant'];}if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (r.routing_no LIKE ? OR r.material_code LIKE ? OR r.material_name LIKE ? OR r.revision LIKE ? OR r.change_number LIKE ? OR EXISTS(SELECT 1 FROM erp_routing_operation o WHERE o.routing_id=r.id AND (o.operation_no LIKE ? OR o.operation_name LIKE ? OR o.work_center_code LIKE ? OR o.work_center_name LIKE ?))) ";for($i=0;$i<9;$i++)$p[]=$kw;}return $w;}
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$f=rt_filters();$p=array();$w=rt_where($f,$p);$c=$db->fetch("SELECT COUNT(*) jml FROM erp_routing r $w",$p);$total=$c?(int)$c->jml:0;
$rows=$db->query("SELECT r.*,COALESCE(x.ops,0) ops,COALESCE(x.total_time,0) total_time FROM erp_routing r LEFT JOIN(SELECT routing_id,COUNT(*) ops,SUM(COALESCE(setup_time,0)+COALESCE(machine_time,0)+COALESCE(labor_time,0)+COALESCE(queue_time,0)+COALESCE(move_time,0)) total_time FROM erp_routing_operation GROUP BY routing_id)x ON x.routing_id=r.id $w ORDER BY r.valid_from DESC,r.id DESC LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){$act='<div class="rt-action"><button class="btn btn-info btn-xs btn-rt-detail" data-id="'.(int)$r->id.'" title="Detail"><i class="fa fa-eye"></i></button> ';if($r->routing_status==='DRAFT'){$act.='<button class="btn btn-primary btn-xs btn-rt-edit" data-id="'.(int)$r->id.'" title="Edit"><i class="fa fa-pencil"></i></button> <button class="btn btn-success btn-xs btn-rt-release" data-id="'.(int)$r->id.'" data-no="'.rt_h($r->routing_no).'" title="Release"><i class="fa fa-check"></i></button> <button class="btn btn-danger btn-xs btn-rt-delete" data-id="'.(int)$r->id.'" data-no="'.rt_h($r->routing_no).'" title="Delete"><i class="fa fa-trash"></i></button>';}elseif($r->routing_status==='RELEASED'){$act.='<button class="btn btn-warning btn-xs btn-rt-inactive" data-id="'.(int)$r->id.'" data-no="'.rt_h($r->routing_no).'" title="Inactive"><i class="fa fa-ban"></i></button>';}$act.='</div>';
  $data[]=array($no++,$act,'<strong>'.rt_h($r->routing_no).'</strong><br><small>Rev '.rt_h($r->revision?:'-').' / Alt '.rt_h($r->alternative_routing).'</small>','<strong>'.rt_h($r->material_code).'</strong><br><small>'.rt_h($r->material_name).'</small>',rt_h($r->plant_code?:'All Plant'),rt_h($r->routing_usage),rt_h(($r->valid_from?:'-').' s/d '.($r->valid_to?:'Open')),(int)$r->ops,number_format((float)$r->total_time,2,',','.').' min',rt_label($r->routing_status),rt_h($r->updated_by?:$r->created_by));
}
header('Content-Type: application/json; charset=utf-8');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
