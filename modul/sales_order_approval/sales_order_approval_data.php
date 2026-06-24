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
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "sales_order_approval_lib.php";
session_check_json();

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$input=soa_filters();
$total=soa_count_rows($db,$input);
$rows=soa_load_rows($db,$input,$length,$start);
$data=array();$no=$start+1;

foreach($rows as $row){
  $canAct = soa_is_admin() || $row->approver==='' || $row->approver===null || $row->approver===soa_username() || $row->approver_group===soa_group_level();
  $actions = '<button type="button" class="btn btn-info btn-xs btn-soa-detail" data-id="'.intval($row->id_sales_order).'" title="'.sd_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> '
           . '<button type="button" class="btn btn-default btn-xs btn-soa-history" data-id="'.intval($row->id_sales_order).'" title="History"><i class="fa fa-history"></i></button> ';
  if($canAct && in_array($row->approval_status,array('PENDING','SUBMITTED'))){
    $actions .= '<button type="button" class="btn btn-success btn-xs btn-soa-act" data-id="'.intval($row->id_sales_order).'" data-act="approve" title="Approve"><i class="fa fa-check"></i></button> '
             . '<button type="button" class="btn btn-danger btn-xs btn-soa-act" data-id="'.intval($row->id_sales_order).'" data-act="reject" title="Reject"><i class="fa fa-times"></i></button>';
  }
  $data[]=array(
    $no++,
    $actions,
    '<strong>'.soa_h($row->no_sales_order).'</strong><br><small>Customer PO '.soa_h($row->no_po ?: '-').'</small>',
    soa_h($row->so_date),
    '<strong>'.soa_h($row->customer_name).'</strong><br><small>'.soa_h($row->kode_penerima).'</small>',
    soa_status_label($row->approval_status),
    '<small>Level '.intval($row->approval_level).' / '.soa_h($row->approver_group ?: $row->approver ?: 'Open approver').'</small><br>'.soa_h($row->approval_date ?: '-'),
    number_format((float)$row->item_count,0,',','.'),
    number_format((float)$row->total_qty,5,',','.'),
    number_format((float)$row->total_amount,2,',','.'),
    soa_h($row->currency),
    soa_h($row->sales_id ?: $row->user),
    '<small>'.soa_h($row->alasan ?: $row->catatan).'</small>'
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
