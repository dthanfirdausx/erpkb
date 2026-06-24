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
include "picking_lib.php";
session_check_json();
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$in=pick_filters();$total=pick_count($db,$in);$rows=pick_load($db,$in,$length,$start);$data=array();$no=$start+1;
foreach($rows as $r){$bar='<div class="progress progress-xs" style="margin-bottom:3px"><div class="progress-bar progress-bar-success" style="width:'.min(100,(float)$r->picked_percent).'%"></div></div><small>'.number_format((float)$r->picked_percent,2,',','.').'%</small>';$data[]=array($no++,'<button class="btn btn-info btn-xs btn-pick-detail" data-id="'.intval($r->id).'"><i class="fa fa-eye"></i></button>','<strong>'.pick_h($r->picking_no).'</strong><br><small>Delivery '.pick_h($r->delivery_no).'</small>',pick_h($r->picking_date),'<strong>'.pick_h($r->customer_name).'</strong><br><small>'.pick_h($r->customer_code).'</small>',pick_status_label($r->status),number_format((float)$r->item_count,0,',','.'),number_format((float)$r->delivery_qty,5,',','.'),number_format((float)$r->picked_qty,5,',','.').$bar,pick_h($r->warehouse ?: '-'),pick_h($r->picker ?: '-'),pick_h($r->no_sales_order));}
header('Content-Type: application/json; charset=utf-8');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
