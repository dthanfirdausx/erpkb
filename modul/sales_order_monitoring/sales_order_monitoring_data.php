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
include "sales_order_monitoring_lib.php";
session_check_json();

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$input=som_filters(); 
$total=som_count_rows($db,$input);
$rows=som_load_rows($db,$input,$length,$start);
$data=array();$no=$start+1;
foreach($rows as $row){
  $overdue=$row->is_overdue?'<span class="label label-danger">OVERDUE</span> ':'';
  $prodBar='<div class="progress progress-xs" style="margin-bottom:3px"><div class="progress-bar progress-bar-primary" style="width:'.min(100,(float)$row->production_percent).'%"></div></div><small>'.number_format((float)$row->production_percent,2,',','.').'%</small>';
  $delBar='<div class="progress progress-xs" style="margin-bottom:3px"><div class="progress-bar progress-bar-success" style="width:'.min(100,(float)$row->delivery_percent).'%"></div></div><small>'.number_format((float)$row->delivery_percent,2,',','.').'%</small>';
  $data[]=array(
    $no++,
    '<button type="button" class="btn btn-info btn-xs btn-som-detail" data-id="'.intval($row->id_sales_order).'" title="'.sd_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> <a class="btn btn-default btn-xs" target="_blank" href="'.base_url().'index.php/sales-order/detail/'.intval($row->id_sales_order).'" title="Open SO"><i class="fa fa-external-link"></i></a>',
    '<strong>'.som_h($row->no_sales_order).'</strong><br><small>PO '.som_h($row->no_po ?: '-').'</small>',
    som_h($row->so_date),
    '<strong>'.som_h($row->nama).'</strong><br><small>'.som_h($row->kode_penerima).'</small>',
    $overdue.som_h($row->delivery_date ?: '-'),
    som_approval_label($row->approval_status),
    som_status_label($row->status_so),
    number_format((float)$row->qty_so,5,',','.'),
    number_format((float)$row->qty_produksi,5,',','.').$prodBar,
    number_format((float)$row->qty_kirim,5,',','.').$delBar,
    number_format((float)$row->total_amount,2,',','.'),
    som_h($row->currency),
    som_h($row->sales_id ?: $row->user)
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
