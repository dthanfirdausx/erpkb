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

function mrp_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function mrp_label($s){$m=array('DRAFT'=>'default','RELEASED'=>'success','CANCELLED'=>'danger');$c=isset($m[$s])?$m[$s]:'default';return '<span class="label label-'.$c.'">'.mrp_h($s).'</span>';}
function mrp_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-m-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-m-t'),
    'status'=>isset($_POST['status'])?trim($_POST['status']):'',
    'plant'=>isset($_POST['plant'])?trim($_POST['plant']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function mrp_where($f,&$p){
  $w=" WHERE h.period_from<=? AND h.period_to>=? ";$p[]=$f['to'];$p[]=$f['from'];
  if($f['status']!==''){$w.=" AND h.status=? ";$p[]=$f['status'];}
  if($f['plant']!==''){$w.=" AND h.plant_code=? ";$p[]=$f['plant'];}
  if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (h.mrp_no LIKE ? OR h.remarks LIKE ? OR EXISTS(SELECT 1 FROM erp_mrp_run_detail d WHERE d.mrp_id=h.id AND (d.material_code LIKE ? OR d.material_name LIKE ? OR d.source_ref LIKE ? OR d.parent_material_code LIKE ?))) ";for($i=0;$i<6;$i++)$p[]=$kw;}
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$f=mrp_filters();$p=array();$w=mrp_where($f,$p);
$c=$db->fetch("SELECT COUNT(*) jml FROM erp_mrp_run h $w",$p);$total=$c?(int)$c->jml:0;
$rows=$db->query("SELECT h.*,COALESCE(x.item_count,0)item_count,COALESCE(x.shortage_items,0)shortage_items FROM erp_mrp_run h LEFT JOIN(SELECT mrp_id,COUNT(*)item_count,SUM(CASE WHEN net_requirement>0 THEN 1 ELSE 0 END)shortage_items FROM erp_mrp_run_detail GROUP BY mrp_id)x ON x.mrp_id=h.id $w ORDER BY h.period_from DESC,h.id DESC LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<div class="mrp-action"><button class="btn btn-info btn-xs btn-mrp-detail" data-id="'.(int)$r->id.'" title="Detail"><i class="fa fa-eye"></i></button> ';
  if($r->status==='DRAFT'){
    $act.='<button class="btn btn-primary btn-xs btn-mrp-edit" data-id="'.(int)$r->id.'" title="Edit"><i class="fa fa-pencil"></i></button> ';
    $act.='<button class="btn btn-success btn-xs btn-mrp-release" data-id="'.(int)$r->id.'" data-no="'.mrp_h($r->mrp_no).'" title="Release"><i class="fa fa-check"></i></button> ';
    $act.='<button class="btn btn-danger btn-xs btn-mrp-delete" data-id="'.(int)$r->id.'" data-no="'.mrp_h($r->mrp_no).'" title="Delete"><i class="fa fa-trash"></i></button>';
  } elseif($r->status==='RELEASED') {
    $act.='<button class="btn btn-warning btn-xs btn-mrp-cancel" data-id="'.(int)$r->id.'" data-no="'.mrp_h($r->mrp_no).'" title="Cancel"><i class="fa fa-ban"></i></button>';
  }
  $act.='</div>';
  $data[]=array(
    $no++,
    $act,
    '<strong>'.mrp_h($r->mrp_no).'</strong><br><small>'.mrp_h($r->mrp_type.' / '.$r->planning_scope).'</small>',
    mrp_h($r->period_from).' s/d '.mrp_h($r->period_to),
    mrp_h($r->plant_code?:'All Plant'),
    number_format((float)$r->item_count,0,',','.'),
    number_format((float)$r->total_gross_req,5,',','.'),
    number_format((float)$r->total_shortage,5,',','.'),
    '<span class="badge bg-orange">'.number_format((float)$r->shortage_items,0,',','.').'</span>',
    mrp_label($r->status),
    mrp_h($r->created_by?:'-')
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
