<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
session_check_json();
function msa_d_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function msa_d_num($v,$d=5){return number_format((float)$v,$d,',','.');}
function msa_d_in($k,$d=''){return isset($_POST[$k])?trim((string)$_POST[$k]):$d;}
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$from=msa_d_in('tgl_awal',date('Y-m-01'));$to=msa_d_in('tgl_akhir',date('Y-m-d'));$type=msa_d_in('adjustment_type');$material=msa_d_in('material_code');$plant=msa_d_in('plant_id');$sloc=msa_d_in('storage_location_id');$bin=msa_d_in('storage_bin_id');$stockType=msa_d_in('stock_type');$keyword=msa_d_in('keyword');
$where=" WHERE h.posting_date BETWEEN ? AND ? ";$p=array($from,$to);
if($type!==''){$where.=" AND h.adjustment_type=? ";$p[]=$type;}
if($plant!==''){$where.=" AND h.plant_id=? ";$p[]=$plant;}
if($sloc!==''){$where.=" AND h.storage_location_id=? ";$p[]=$sloc;}
if($bin!==''){$where.=" AND h.storage_bin_id=? ";$p[]=$bin;}
if($stockType!==''){$where.=" AND h.stock_type=? ";$p[]=$stockType;}
if($material!==''){$where.=" AND EXISTS(SELECT 1 FROM erp_manual_stock_adjustment_detail d WHERE d.adjustment_id=h.id AND d.material_code=?) ";$p[]=$material;}
if($keyword!==''){$kw='%'.$keyword.'%';$where.=" AND (h.adjustment_no LIKE ? OR h.reason_code LIKE ? OR h.reason_text LIKE ? OR h.created_by LIKE ? OR EXISTS(SELECT 1 FROM erp_manual_stock_adjustment_detail d WHERE d.adjustment_id=h.id AND (d.material_code LIKE ? OR d.material_name LIKE ?))) ";array_push($p,$kw,$kw,$kw,$kw,$kw,$kw);}
$count=$db->fetch("SELECT COUNT(*) total FROM erp_manual_stock_adjustment h $where",$p);
$rows=$db->query("SELECT h.*,ep.plant_code,es.storage_code,eb.bin_code,COUNT(d.id) item_count FROM erp_manual_stock_adjustment h LEFT JOIN erp_manual_stock_adjustment_detail d ON d.adjustment_id=h.id LEFT JOIN erp_plant ep ON ep.id=h.plant_id LEFT JOIN erp_storage_location es ON es.id=h.storage_location_id LEFT JOIN erp_storage_bin eb ON eb.id=h.storage_bin_id $where GROUP BY h.id ORDER BY h.posting_date DESC,h.id DESC LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $label=$r->adjustment_type==='INCREASE'?'success':'danger';
  $data[]=array(
    $no++,
    '<button type="button" class="btn btn-info btn-xs btn-msa-detail" data-id="'.(int)$r->id.'"><i class="fa fa-search"></i></button>',
    '<strong>'.msa_d_h($r->adjustment_no).'</strong><br><small>'.msa_d_h($r->posting_date).' | MvT '.msa_d_h($r->movement_type).'</small>',
    '<span class="label label-'.$label.'">'.msa_d_h($r->adjustment_type).'</span><br><small>'.msa_d_h($r->reason_code).'</small>',
    msa_d_h(trim((string)$r->plant_code.' / '.(string)$r->storage_code.' / '.(string)$r->bin_code,' /')).'<br><small>'.msa_d_h($r->stock_type).'</small>',
    (int)$r->item_count,
    '<span class="text-right">'.msa_d_num($r->total_qty).'</span>',
    '<span class="text-right">'.number_format((float)$r->total_amount,2,',','.').'</span>',
    msa_d_h($r->created_by).'<br><small>'.msa_d_h($r->created_at).'</small>'
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$count?(int)$count->total:0,'recordsFiltered'=>$count?(int)$count->total:0,'data'=>$data));
?>
