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
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
session_check_json();

function fc_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function fc_label($s){$m=array('DRAFT'=>'default','RELEASED'=>'success','CLOSED'=>'primary','CANCELLED'=>'danger');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.fc_h($s).'</span>';}
function fc_filters(){
  $f=array();
  $f['from']=isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-m-01');
  $f['to']=isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-m-t');
  $f['status']=isset($_POST['status'])?trim($_POST['status']):'';
  $f['plant']=isset($_POST['plant'])?trim($_POST['plant']):'';
  $f['keyword']=isset($_POST['keyword'])?trim($_POST['keyword']):'';
  return $f;
}
function fc_where($f,&$params){
  $w=" WHERE f.period_from<=? AND f.period_to>=? "; $params[]=$f['to']; $params[]=$f['from'];
  if($f['status']!==''){ $w.=" AND f.status=? "; $params[]=$f['status']; }
  if($f['plant']!==''){ $w.=" AND f.plant_code=? "; $params[]=$f['plant']; }
  if($f['keyword']!==''){ $kw='%'.$f['keyword'].'%'; $w.=" AND (f.forecast_no LIKE ? OR f.forecast_version LIKE ? OR f.customer_name LIKE ? OR f.remarks LIKE ? OR EXISTS(SELECT 1 FROM erp_forecast_detail d WHERE d.forecast_id=f.id AND (d.material_code LIKE ? OR d.material_name LIKE ?))) "; for($i=0;$i<6;$i++)$params[]=$kw; }
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$f=fc_filters();$params=array();$where=fc_where($f,$params);
$count=$db->fetch("SELECT COUNT(*) jml FROM erp_forecast f $where",$params);$total=$count?(int)$count->jml:0;
$rows=$db->query("SELECT f.*,COALESCE(d.item_count,0) item_count FROM erp_forecast f LEFT JOIN (SELECT forecast_id,COUNT(*) item_count FROM erp_forecast_detail GROUP BY forecast_id)d ON d.forecast_id=f.id $where ORDER BY f.period_from DESC,f.id DESC LIMIT $start,$length",$params);
$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<div class="btn-group"><button class="btn btn-info btn-xs btn-fc-detail" data-id="'.(int)$r->id.'" title="Detail"><i class="fa fa-eye"></i></button>';
  if($r->status==='DRAFT')$act.=' <button class="btn btn-success btn-xs btn-fc-release" data-id="'.(int)$r->id.'" data-no="'.fc_h($r->forecast_no).'" title="Release"><i class="fa fa-check"></i></button> <button class="btn btn-danger btn-xs btn-fc-cancel" data-id="'.(int)$r->id.'" data-no="'.fc_h($r->forecast_no).'" title="Cancel"><i class="fa fa-ban"></i></button>';
  $act.='</div>';
  $data[]=array(
    $no++,$act,
    '<strong>'.fc_h($r->forecast_no).'</strong><br><small>'.fc_h($r->forecast_type.' / '.$r->forecast_version).'</small>',
    fc_h($r->period_from).' s/d '.fc_h($r->period_to),
    '<strong>'.fc_h($r->plant_code ?: '-').'</strong>',
    '<strong>'.fc_h($r->customer_name ?: 'All Customer').'</strong><br><small>'.fc_h($r->customer_code ?: '-').'</small>',
    number_format((float)$r->item_count,0,',','.'),
    number_format((float)$r->total_qty,5,',','.'),
    fc_label($r->status),
    fc_h($r->created_by ?: '-')
  );
}
header('Content-Type: application/json; charset=utf-8');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
