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
include "billing_report_lib.php";
$draw=isset($_REQUEST['draw'])?(int)$_REQUEST['draw']:0;$start=isset($_REQUEST['start'])?max(0,(int)$_REQUEST['start']):0;$length=isset($_REQUEST['length'])?(int)$_REQUEST['length']:25;if($length<=0)$length=25;
$f=br_filters();$total=br_count_rows($db,$f);$rows=br_load_rows($db,$f,$length,$start);$data=array();$no=$start+1;
foreach($rows as $r){
  $act='<button class="btn btn-info btn-xs btn-br-detail" data-id="'.(int)$r->id_sales.'" title="'.sd_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> <a target="_blank" class="btn btn-success btn-xs" href="'.base_url().'modul/sales_invoice/print.php?id='.(int)$r->id_sales.'" title="'.sd_h('common_print', 'Print').'"><i class="fa fa-print"></i></a>';
  $doc='<strong>'.br_h($r->no_sales_invoice?:$r->invoice_no).'</strong><br><small>'.br_h($r->invoice_date).' / '.br_h($r->valuta?:'-').'</small>';
  $cust='<strong>'.br_h($r->bill_name).'</strong><br><small>Ship: '.br_h($r->ship_name).'</small>';
  $ref='SO '.br_h($r->no_sales_order?:'-').'<br><small>SJ/DO '.br_h($r->surat_jalan_no?:'-').' | PO '.br_h($r->nopo?:'-').'</small>';
  $due=br_h($r->due_date?:'-').'<br><small>'.((int)$r->overdue_days>0?((int)$r->overdue_days.' hari overdue'):'Term '.br_h($r->term?:'-')).'</small>';
  $items='<span class="badge bg-aqua">'.(int)$r->item_count.' item</span><br><small>Qty '.br_num($r->total_qty,4).'</small>';
  $amount='DPP '.br_num($r->subtotal).'<br><small>PPN '.br_num($r->tax_amount).' | Total '.br_num($r->grand_total).'</small>';
  $data[]=array($no++,$act,$doc,$cust,$ref,$items,$amount,br_tax_badge($r->tax),$due,br_due_badge($r),br_h($r->sales_id?:'-'),br_h($r->catatan?:'-'));
}
header('Content-Type: application/json');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
