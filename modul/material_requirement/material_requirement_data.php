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

function mrq_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function mrq_label($s){
  $m=array('DRAFT'=>'default','SUBMITTED'=>'info','APPROVED'=>'success','STAGED'=>'warning','ISSUED'=>'primary','CLOSED'=>'primary','CANCELLED'=>'danger');
  $c=isset($m[$s])?$m[$s]:'default';
  return '<span class="label label-'.$c.'">'.mrq_h($s).'</span>';
}
function mrq_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-m-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-m-t'),
    'status'=>isset($_POST['status'])?trim($_POST['status']):'',
    'type'=>isset($_POST['type'])?trim($_POST['type']):'',
    'plant'=>isset($_POST['plant'])?trim($_POST['plant']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function mrq_where($f,&$p){
  $w=" WHERE h.requirement_date BETWEEN ? AND ? ";$p[]=$f['from'];$p[]=$f['to'];
  if($f['status']!==''){$w.=" AND h.status=? ";$p[]=$f['status'];}
  if($f['type']!==''){$w.=" AND h.requirement_type=? ";$p[]=$f['type'];}
  if($f['plant']!==''){$w.=" AND h.plant_code=? ";$p[]=$f['plant'];}
  if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (h.requirement_no LIKE ? OR h.source_mrp_no LIKE ? OR h.source_production_order_no LIKE ? OR h.source_ref LIKE ? OR h.requestor LIKE ? OR h.remarks LIKE ? OR EXISTS(SELECT 1 FROM erp_material_requirement_detail d WHERE d.requirement_id=h.id AND (d.material_code LIKE ? OR d.material_name LIKE ? OR d.source_ref LIKE ? OR d.parent_material_code LIKE ?))) ";for($i=0;$i<10;$i++)$p[]=$kw;}
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$f=mrq_filters();$p=array();$w=mrq_where($f,$p);
$c=$db->fetch("SELECT COUNT(*) jml FROM erp_material_requirement h $w",$p);$total=$c?(int)$c->jml:0;
$rows=$db->query("SELECT h.*,COALESCE(x.item_count,0)item_count FROM erp_material_requirement h LEFT JOIN(SELECT requirement_id,COUNT(*) item_count FROM erp_material_requirement_detail GROUP BY requirement_id)x ON x.requirement_id=h.id $w ORDER BY h.requirement_date DESC,h.id DESC LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<div class="mrq-action"><button class="btn btn-info btn-xs btn-mrq-detail" data-id="'.(int)$r->id.'" title="Detail"><i class="fa fa-eye"></i></button> ';
  if($r->status==='DRAFT'){
    $act.='<button class="btn btn-primary btn-xs btn-mrq-edit" data-id="'.(int)$r->id.'" title="Edit"><i class="fa fa-pencil"></i></button> ';
    $act.='<button class="btn btn-success btn-xs btn-mrq-submit" data-id="'.(int)$r->id.'" data-no="'.mrq_h($r->requirement_no).'" title="Submit"><i class="fa fa-paper-plane"></i></button> ';
    $act.='<button class="btn btn-danger btn-xs btn-mrq-delete" data-id="'.(int)$r->id.'" data-no="'.mrq_h($r->requirement_no).'" title="Delete"><i class="fa fa-trash"></i></button>';
  } elseif($r->status==='SUBMITTED') {
    $act.='<button class="btn btn-success btn-xs btn-mrq-approve" data-id="'.(int)$r->id.'" data-no="'.mrq_h($r->requirement_no).'" title="Approve"><i class="fa fa-check"></i></button> ';
    $act.='<button class="btn btn-warning btn-xs btn-mrq-cancel" data-id="'.(int)$r->id.'" data-no="'.mrq_h($r->requirement_no).'" title="Cancel"><i class="fa fa-ban"></i></button>';
  } elseif(in_array($r->status,array('APPROVED','STAGED'),true)) {
    $act.='<button class="btn btn-warning btn-xs btn-mrq-cancel" data-id="'.(int)$r->id.'" data-no="'.mrq_h($r->requirement_no).'" title="Cancel"><i class="fa fa-ban"></i></button>';
  }
  $act.='</div>';
  $source=$r->source_mrp_no?:($r->source_production_order_no?:$r->source_ref);
  $data[]=array(
    $no++,
    $act,
    '<strong>'.mrq_h($r->requirement_no).'</strong><br><small>'.mrq_h($r->requirement_type.' / '.$r->priority).'</small>',
    mrq_h($r->requirement_date),
    mrq_h($r->plant_code?:'All Plant'),
    mrq_h($source?:'-'),
    number_format((float)$r->item_count,0,',','.'),
    number_format((float)$r->total_required_qty,5,',','.'),
    number_format((float)$r->total_open_qty,5,',','.'),
    mrq_label($r->status),
    mrq_h($r->requestor?:$r->created_by)
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
