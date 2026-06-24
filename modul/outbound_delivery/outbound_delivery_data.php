<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if(session_status()===PHP_SESSION_NONE)session_start();
include "../../inc/config.php";
include "outbound_delivery_lib.php";
session_check_json();
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$input=obd_filters();$total=obd_count_rows($db,$input);$rows=obd_load_rows($db,$input,$length,$start);$data=array();$no=$start+1;
foreach($rows as $row){
  $giBar='<div class="progress progress-xs" style="margin-bottom:3px"><div class="progress-bar progress-bar-success" style="width:'.min(100,(float)$row->gi_percent).'%"></div></div><small>'.number_format((float)$row->gi_percent,2,',','.').'%</small>';
  $data[]=array($no++,
    '<button class="btn btn-info btn-xs btn-obd-detail" data-id="'.intval($row->id).'" title="'.sd_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button>',
    '<strong>'.obd_h($row->delivery_no).'</strong><br><small>SO '.obd_h($row->no_sales_order).'</small>',
    obd_h($row->delivery_date).'<br><small>GI '.obd_h($row->planned_gi_date ?: '-').'</small>',
    '<strong>'.obd_h($row->customer_name).'</strong><br><small>'.obd_h($row->customer_code).'</small>',
    obd_status_label($row->status),
    obd_small_label($row->picking_status).' '.obd_small_label($row->packing_status).' '.obd_small_label($row->gi_status),
    number_format((float)$row->item_count,0,',','.'),
    number_format((float)$row->delivery_qty,5,',','.'),
    number_format((float)$row->gi_qty,5,',','.').$giBar,
    number_format((float)$row->total_amount,2,',','.'),
    obd_h($row->shipping_point ?: '-'),
    obd_h(trim((string)$row->vehicle_no.' / '.(string)$row->driver_name,' / '))
  );
}
header('Content-Type: application/json; charset=utf-8');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
