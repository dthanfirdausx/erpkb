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
session_check_json();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
include "sales_order_report_lib.php";
$draw=isset($_REQUEST['draw'])?(int)$_REQUEST['draw']:0;
$start=isset($_REQUEST['start'])?max(0,(int)$_REQUEST['start']):0;
$length=isset($_REQUEST['length'])?(int)$_REQUEST['length']:25;if($length<=0)$length=25;
$filters=sor_filters();
$total=sor_count_rows($db,$filters);
$rows=sor_load_rows($db,$filters,$length,$start);
$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<button class="btn btn-info btn-xs btn-sor-detail" data-id="'.(int)$r->id_sales_order.'" title="'.sd_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> <a class="btn btn-warning btn-xs" href="'.base_index().'sales-order/detail/'.(int)$r->id_sales_order.'" title="Open Sales Order"><i class="fa fa-external-link"></i></a>';
  $doc='<strong>'.sor_h($r->no_sales_order).'</strong><br><small>'.sor_h($r->so_date).' / '.sor_h($r->currency?:'-').'</small>';
  $cust='<strong>'.sor_h($r->customer_name?:$r->kode_penerima).'</strong><br><small>PO: '.sor_h($r->no_po?:'-').'</small>';
  $delivery=sor_h($r->delivery_date?:'-').'<br>'.sor_fulfillment_badge($r);
  $order='<span class="badge bg-aqua">'.(int)$r->item_count.' item</span><br><small>Qty '.sor_num($r->qty_so,4).' | Amt '.sor_num($r->total_amount).'</small>';
  $prod='<div class="progress progress-xs"><div class="progress-bar progress-bar-primary" style="width:'.min(100,(float)$r->production_percent).'%"></div></div><small>'.sor_num($r->qty_produksi,4).' / '.sor_num($r->qty_so,4).' ('.sor_num($r->production_percent).'%)</small>';
  $deliv='<div class="progress progress-xs"><div class="progress-bar progress-bar-success" style="width:'.min(100,(float)$r->delivery_percent).'%"></div></div><small>'.sor_num($r->qty_kirim,4).' / '.sor_num($r->qty_so,4).' ('.sor_num($r->delivery_percent).'%)</small>';
  $bill='<div class="progress progress-xs"><div class="progress-bar progress-bar-warning" style="width:'.min(100,(float)$r->billing_percent).'%"></div></div><small>'.(int)$r->invoice_count.' inv | Qty '.sor_num($r->invoice_qty,4).' ('.sor_num($r->billing_percent).'%)</small>';
  $data[]=array($no++,$act,$doc,$cust,sor_approval_badge($r->approval_status),sor_status_badge($r->status_so),$delivery,$order,$prod,$deliv,$bill,sor_h($r->sales_id?:'-'));
}
header('Content-Type: application/json');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
