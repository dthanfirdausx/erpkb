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
include "../../inc/config.php";session_check_json();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
include "delivery_report_lib.php";
$draw=isset($_REQUEST['draw'])?(int)$_REQUEST['draw']:0;$start=isset($_REQUEST['start'])?max(0,(int)$_REQUEST['start']):0;$length=isset($_REQUEST['length'])?(int)$_REQUEST['length']:25;if($length<=0)$length=25;
$f=dr_filters();$total=dr_count_rows($db,$f);$rows=dr_load_rows($db,$f,$length,$start);$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<button class="btn btn-info btn-xs btn-dr-detail" data-id="'.(int)$r->id.'" title="'.sd_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> <a class="btn btn-primary btn-xs" target="_blank" href="'.base_url().'modul/surat_jalan/surat_jalan_print.php?id='.(int)$r->id.'" title="Print SJ"><i class="fa fa-print"></i></a>';
  $doc='<strong>'.dr_h($r->no_surat_jalan).'</strong><br><small>SO '.dr_h($r->no_sales_order?:'-').' / MT '.dr_h($r->movement_type?:'601').'</small>';
  $date='Doc '.dr_h($r->document_date?:$r->tgl_surat_jalan).'<br><small>Post '.dr_h($r->posting_date?:'-').'</small>';
  $cust='<strong>'.dr_h($r->customer_name?:$r->kode_penerima).'</strong><br><small>PO '.dr_h($r->no_po?:'-').'</small>';
  $flow='PL: '.dr_h($r->packing_docs?:'-').'<br><small>GI: '.dr_h($r->gi_no?:'-').'</small>';
  $qty='Qty '.dr_num($r->shipped_qty,4).'<br><small>Item '.(int)$r->item_count.' | GI '.dr_num($r->gi_qty,4).' ('.dr_num($r->gi_percent).'%)</small>';
  $customs=dr_h($r->outbound_bc_type?:$r->bc_types?:'-').'<br><small>'.dr_h($r->outbound_no_daftar?:$r->bc_docs?:'-').'</small>';
  $ship=dr_h($r->shipping_point?:'-').'<br><small>'.dr_h(trim(($r->no_kendaraan?:$r->no_polisi).' / '.$r->sopir,' /')?:'-').'</small>';
  $data[]=array($no++,$act,$doc,$date,$cust,dr_status_badge($r->status),dr_gi_badge($r->gi_status),$flow,$qty,$customs,dr_num($r->gross_weight,5),$ship);
}
header('Content-Type: application/json');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
