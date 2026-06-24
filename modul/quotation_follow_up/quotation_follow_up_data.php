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
include "quotation_follow_up_lib.php";
session_check_json();

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$input=qfu_filters();
$total=qfu_count_rows($db,$input);
$rows=qfu_load_rows($db,$input,$length,$start);
$data=array();$no=$start+1;
foreach($rows as $row){
  $due='';
  if($row->next_followup_date && strtotime($row->next_followup_date)<=strtotime(date('Y-m-d 23:59:59')) && !in_array($row->result_status,array('WON','LOST','CANCELLED'))){
    $due='<span class="label label-danger">DUE</span> ';
  }
  $data[]=array(
    $no++,
    '<button type="button" class="btn btn-primary btn-xs btn-qfu-add" data-id="'.intval($row->id_quotation).'" data-no="'.qfu_h($row->no_sales_quotation).'" title="Add Follow Up"><i class="fa fa-plus"></i></button> <button type="button" class="btn btn-info btn-xs btn-qfu-detail btn-qfu-history" data-id="'.intval($row->id_quotation).'" title="Detail / History"><i class="fa fa-eye"></i></button>',
    '<strong>'.qfu_h($row->no_sales_quotation).'</strong><br><small>'.qfu_h($row->tgl).' | Valid '.qfu_h($row->valid_date).'</small>',
    '<strong>'.qfu_h($row->customer_display).'</strong><br><small>'.qfu_h($row->kode_penerima).'</small>',
    qfu_h($row->subject),
    qfu_quote_status_label($row->quote_status),
    $row->followup_date?qfu_h($row->followup_date).'<br><small>'.qfu_h($row->contact_method.' / '.$row->activity_type).'</small>':'<span class="text-muted">Belum ada follow-up</span>',
    qfu_status_label($row->result_status ?: 'OPEN'),
    $due.qfu_h($row->next_followup_date ?: '-').'<br><small>'.qfu_h($row->next_action).'</small>',
    number_format((float)$row->probability_percent,2,',','.').'%',
    qfu_h($row->sales_person ?: $row->sales_id),
    '<small>'.qfu_h($row->discussion_summary).'</small>'
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
