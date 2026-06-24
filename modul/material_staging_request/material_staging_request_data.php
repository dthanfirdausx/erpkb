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
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);

function msrd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function msrd_num($v,$d=2){return number_format((float)$v,$d,',','.');}
function msrd_status($s){
  $map=array('DRAFT'=>'default','REQUESTED'=>'primary','PICKING'=>'warning','STAGED'=>'success','PARTIAL_ISSUE'=>'info','ISSUED'=>'success','CANCELLED'=>'danger');
  $cls=isset($map[$s])?$map[$s]:'default';
  return '<span class="label label-'.$cls.'">'.msrd_h($s).'</span>';
}

$request=$_REQUEST;
$draw=isset($request['draw'])?(int)$request['draw']:0;
$start=isset($request['start'])?max(0,(int)$request['start']):0;
$length=isset($request['length'])?(int)$request['length']:25;
if($length<=0)$length=25;

$params=array();
$where=" WHERE 1=1 ";
if(!empty($_POST['tgl_awal'])&&!empty($_POST['tgl_akhir'])){
  $where.=" AND h.request_date BETWEEN ? AND ? ";
  $params[]=$_POST['tgl_awal'];$params[]=$_POST['tgl_akhir'];
}
if(!empty($_POST['status'])){$where.=" AND h.staging_status=? ";$params[]=$_POST['status'];}
if(!empty($_POST['plant_id'])){$where.=" AND h.plant_id=? ";$params[]=(int)$_POST['plant_id'];}
if(!empty($_POST['storage_location_id'])){$where.=" AND h.source_storage_location_id=? ";$params[]=(int)$_POST['storage_location_id'];}
if(!empty($_POST['keyword'])){
  $kw='%'.trim($_POST['keyword']).'%';
  $where.=" AND (h.staging_no LIKE ? OR h.no_production_order LIKE ? OR h.reference_no LIKE ? OR h.created_by LIKE ? OR ds.material_text LIKE ?) ";
  for($i=0;$i<5;$i++)$params[]=$kw;
}

$base=" FROM erp_material_staging_request h
        LEFT JOIN (
          SELECT staging_id,COUNT(*) item_count,SUM(requested_qty) requested_qty,SUM(staged_qty) staged_qty,SUM(shortage_qty) shortage_qty,
                 GROUP_CONCAT(DISTINCT CONCAT(material_code,' ',COALESCE(material_name,'')) SEPARATOR ' ') material_text
          FROM erp_material_staging_request_detail GROUP BY staging_id
        ) ds ON ds.staging_id=h.id ".$where;
$total=$db->fetch("SELECT COUNT(*) AS jml ".$base,$params);
$order=" ORDER BY h.id DESC ";
$rows=$db->query("SELECT h.*,COALESCE(ds.item_count,0) item_count,COALESCE(ds.requested_qty,0) requested_qty,COALESCE(ds.staged_qty,0) staged_qty,COALESCE(ds.shortage_qty,0) shortage_qty ".$base.$order." LIMIT ".$start.",".$length,$params);

$data=array();$no=$start+1;
foreach($rows as $row){
  $actions='<div class="msr-actions">'
    .'<button type="button" class="btn btn-info btn-xs btn-detail-msr" data-id="'.intval($row->id).'" title="Detail"><i class="fa fa-eye"></i></button> ';
  if($row->staging_status==='DRAFT'){
    $actions.='<button type="button" class="btn btn-primary btn-xs btn-status-msr" data-id="'.intval($row->id).'" data-act="submit" title="Submit"><i class="fa fa-paper-plane"></i></button> ';
  }
  if($row->staging_status==='REQUESTED'){
    $actions.='<button type="button" class="btn btn-warning btn-xs btn-status-msr" data-id="'.intval($row->id).'" data-act="start_picking" title="Start Picking"><i class="fa fa-hand-paper-o"></i></button> ';
  }
  if(in_array($row->staging_status,array('REQUESTED','PICKING'),true)){
    $actions.='<button type="button" class="btn btn-success btn-xs btn-status-msr" data-id="'.intval($row->id).'" data-act="confirm_staged" title="Confirm Staged"><i class="fa fa-check"></i></button> ';
    $actions.='<button type="button" class="btn btn-danger btn-xs btn-cancel-msr" data-id="'.intval($row->id).'" title="Cancel"><i class="fa fa-ban"></i></button>';
  }
  if($row->staging_status==='DRAFT'){
    $actions.='<button type="button" class="btn btn-danger btn-xs btn-cancel-msr" data-id="'.intval($row->id).'" title="Cancel"><i class="fa fa-trash"></i></button>';
  }
  $actions.='</div>';
  $doc='<strong>'.msrd_h($row->staging_no).'</strong><br><small>Ref: '.msrd_h($row->reference_no?:'-').'</small>';
  $po='<strong>'.msrd_h($row->no_production_order).'</strong><br><small>'.msrd_h($row->request_type).'</small>';
  $date='<strong>Request</strong> '.msrd_h($row->request_date).'<br><small>Required: '.msrd_h($row->required_date?:'-').'</small>';
  $loc='<strong>Source</strong> '.msrd_h(trim(($row->plant_code?:'').' / '.($row->source_storage_location?:''),' /')).'<br><small>Bin: '.msrd_h($row->source_storage_bin?:'-').' | Dest: '.msrd_h($row->destination_area?:$row->destination_storage_location?:'-').'</small>';
  $qty='<strong>Req</strong> '.msrd_num($row->requested_qty,5).'<br><small>Staged '.msrd_num($row->staged_qty,5).'</small>';
  $short=((float)$row->shortage_qty>0)?'<span class="label label-danger">'.msrd_num($row->shortage_qty,5).'</span>':'<span class="label label-success">OK</span>';
  $data[]=array($no++,$actions,$doc,$po,$date,$loc,'<span class="badge bg-aqua">'.intval($row->item_count).' item</span>',$qty,$short,msrd_h($row->priority),msrd_status($row->staging_status),msrd_h($row->created_by));
}

echo json_encode(array('draw'=>$draw,'recordsTotal'=>intval($total?$total->jml:0),'recordsFiltered'=>intval($total?$total->jml:0),'data'=>$data));
?>
