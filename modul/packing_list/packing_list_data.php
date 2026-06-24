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
include "packing_list_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;
$input = pl_filters();
$total = pl_count_rows($db, $input);
$rows = pl_load_rows($db, $input, $length, $start);
$data = array();
$no = $start + 1;
foreach ($rows as $row) {
  $data[] = array(
    $no++,
    '',
    '<strong>'.pl_h($row->no_packing_list).'</strong><br><small>Delivery '.pl_h($row->delivery_no ?: '-').'</small>',
    pl_h($row->tgl_sj ?: substr((string)$row->date_created, 0, 10)),
    '<strong>'.pl_h($row->customer_name ?: $row->penerima).'</strong><br><small>'.pl_h($row->penerima).'</small>',
    pl_h($row->picking_no ?: '-'),
    pl_h($row->no_sj ?: '-'),
    pl_h($row->no_invoice ?: '-'),
    pl_h($row->no_po ?: '-'),
    pl_status_label($row->status),
    number_format((float)$row->item_count, 0, ',', '.'),
    number_format((float)$row->packed_qty, 5, ',', '.'),
    pl_h($row->vehicle_no ?: '-'),
    $row->status,
    (int)$row->id
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
