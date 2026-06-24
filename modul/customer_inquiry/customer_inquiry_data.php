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
include "customer_inquiry_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;

$input = ciq_filters();
$total = ciq_count_rows($db, $input);
$rows = ciq_load_rows($db, $input, $length, $start);
$data = array();
$no = $start + 1;

foreach ($rows as $row) {
  $actions = '<div class="btn-group">'
           . '<a class="btn btn-info btn-xs" href="'.base_index().'customer-inquiry/detail/'.intval($row->id).'" title="'.sd_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></a>'
           . '<a class="btn btn-warning btn-xs" href="'.base_index().'customer-inquiry/edit/'.intval($row->id).'" title="'.sd_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></a>';
  if ($row->status !== 'CANCELLED') {
    $actions .= '<button type="button" class="btn btn-danger btn-xs btn-ciq-cancel" data-id="'.intval($row->id).'" title="'.sd_h('common_cancel', 'Cancel').'"><i class="fa fa-ban"></i></button>';
  }
  $actions .= '</div>';

  $data[] = array(
    $no++,
    $actions,
    '<strong>'.ciq_h($row->inquiry_no).'</strong><br><small class="text-muted">Valid until '.ciq_h($row->valid_until ?: '-').'</small>',
    ciq_h($row->inquiry_date),
    '<strong>'.ciq_h($row->customer_display ?: $row->customer_name).'</strong><br><small>'.ciq_h(trim((string)$row->contact_person.' '.$row->phone)).'</small>',
    ciq_h($row->subject),
    ciq_priority_label($row->priority),
    ciq_status_label($row->status),
    ciq_h($row->requested_delivery_date),
    number_format((float)$row->item_count, 0, ',', '.'),
    number_format((float)$row->total_amount, 2, ',', '.'),
    ciq_h($row->sales_person ?: $row->created_by)
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
