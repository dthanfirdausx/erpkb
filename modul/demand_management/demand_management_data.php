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
function dm_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function dm_label($s){$m=array('DRAFT'=>'default','RELEASED'=>'success','CLOSED'=>'primary','CANCELLED'=>'danger');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.dm_h($s).'</span>';}
function dm_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-m-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-m-t'),
    'status'=>isset($_POST['status'])?trim($_POST['status']):'',
    'plant'=>isset($_POST['plant'])?trim($_POST['plant']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function dm_where($f,&$p){
  $w=" WHERE h.period_from<=? AND h.period_to>=? ";$p[]=$f['to'];$p[]=$f['from'];
  if($f['status']!==''){$w.=" AND h.status=? ";$p[]=$f['status'];}
  if($f['plant']!==''){$w.=" AND h.plant_code=? ";$p[]=$f['plant'];}
  if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (h.demand_no LIKE ? OR h.demand_version LIKE ? OR h.remarks LIKE ? OR EXISTS(SELECT 1 FROM erp_demand_plan_detail d WHERE d.demand_id=h.id AND (d.material_code LIKE ? OR d.material_name LIKE ? OR d.source_ref LIKE ? OR d.customer_name LIKE ?))) ";for($i=0;$i<7;$i++)$p[]=$kw;}
  return $w;
}
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$f=dm_filters();$p=array();$w=dm_where($f,$p);$c=$db->fetch("SELECT COUNT(*) jml FROM erp_demand_plan h $w",$p);$total=$c?(int)$c->jml:0;
$rows=$db->query("SELECT h.*,COALESCE(x.item_count,0)item_count,COALESCE(x.open_qty,0)open_qty FROM erp_demand_plan h LEFT JOIN(SELECT demand_id,COUNT(*)item_count,SUM(open_qty)open_qty FROM erp_demand_plan_detail GROUP BY demand_id)x ON x.demand_id=h.id $w ORDER BY h.period_from DESC,h.id DESC LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<div class="dm-action"><button class="btn btn-info btn-xs btn-dm-detail" data-id="'.(int)$r->id.'" title="Detail"><i class="fa fa-eye"></i></button>';
  if($r->status==='DRAFT')$act.=' <button class="btn btn-success btn-xs btn-dm-release" data-id="'.(int)$r->id.'" data-no="'.dm_h($r->demand_no).'" title="Release"><i class="fa fa-check"></i></button> <button class="btn btn-danger btn-xs btn-dm-cancel" data-id="'.(int)$r->id.'" data-no="'.dm_h($r->demand_no).'" title="Cancel"><i class="fa fa-ban"></i></button>';
  $act.='</div>';
  $data[]=array($no++,$act,'<strong>'.dm_h($r->demand_no).'</strong><br><small>'.dm_h($r->demand_type.' / '.$r->demand_version).'</small>',dm_h($r->period_from).' s/d '.dm_h($r->period_to),dm_h($r->plant_code?:'All Plant'),number_format((float)$r->item_count,0,',','.'),number_format((float)$r->total_qty,5,',','.'),number_format((float)$r->open_qty,5,',','.'),dm_label($r->status),dm_h($r->created_by?:'-'));
}
header('Content-Type: application/json; charset=utf-8');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
