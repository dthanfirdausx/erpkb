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
if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
include "../../inc/config.php";
include "goods_issue_delivery_lib.php";

$input = gid_filters();
$total = gid_count_rows($db, $input);
$start = isset($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;
$length = isset($_REQUEST['length']) ? (int)$_REQUEST['length'] : 25;
$rows = gid_load_rows($db, $input, $length, $start);
$data = array();
$no = $start + 1;
foreach ($rows as $row) {
  $data[] = array(
    $no++,
    '',
    gid_h($row->gi_no),
    gid_h($row->posting_date),
    gid_h($row->delivery_no).'<br><small>SO '.gid_h($row->no_sales_order).'</small>',
    gid_h($row->customer_code.' - '.$row->customer_name),
    gid_status_label($row->status),
    (int)$row->item_count,
    number_format((float)$row->posted_qty,5,',','.'),
    number_format((float)$row->posted_amount,2,',','.'),
    gid_h($row->shipping_point ?: '-'),
    gid_h(trim($row->vehicle_no.' / '.$row->driver_name, ' /') ?: '-'),
    $row->status,
    (int)$row->id
  );
}
echo json_encode(array(
  'draw' => isset($_REQUEST['draw']) ? (int)$_REQUEST['draw'] : 1,
  'recordsTotal' => $total,
  'recordsFiltered' => $total,
  'data' => $data
));
?>
