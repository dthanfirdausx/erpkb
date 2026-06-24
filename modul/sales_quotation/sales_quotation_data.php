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
include "sales_quotation_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;

$input = sq_filters();
$total = sq_count_rows($db, $input);
$rows = sq_load_rows($db, $input, $length, $start);
$data = array();
$no = $start + 1;

foreach ($rows as $row) {
  $actions = '<div class="btn-group">'
           . '<a class="btn btn-info btn-xs" href="'.base_index().'sales-quotation/detail/'.intval($row->id_quotation).'" title="'.sd_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></a>'
           . '<a class="btn btn-warning btn-xs" href="'.base_index().'sales-quotation/edit/'.intval($row->id_quotation).'" title="'.sd_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></a>'
           . '<button type="button" class="btn btn-success btn-xs btn-sq-status" data-id="'.intval($row->id_quotation).'" data-status="SENT" title="Mark Sent"><i class="fa fa-send"></i></button>'
           . '<button type="button" class="btn btn-danger btn-xs btn-sq-status" data-id="'.intval($row->id_quotation).'" data-status="CANCELLED" title="'.sd_h('common_cancel', 'Cancel').'"><i class="fa fa-ban"></i></button>'
           . '</div>';
  $data[] = array(
    $no++,
    $actions,
    '<strong>'.sq_h($row->no_sales_quotation).'</strong><br><small class="text-muted">Inquiry '.sq_h($row->inquiry_id ?: '-').'</small>',
    sq_h($row->tgl),
    '<strong>'.sq_h($row->customer_display ?: $row->customer_name).'</strong><br><small>'.sq_h(trim((string)$row->contact_person.' '.(string)$row->kode_penerima)).'</small>',
    sq_h($row->subject ?: $row->catatan),
    sq_status_label($row->status),
    sq_h($row->valid_date),
    number_format((float)$row->item_count, 0, ',', '.'),
    number_format((float)$row->total_amount, 2, ',', '.'),
    sq_h($row->currency),
    sq_h($row->sales_id ?: $row->user)
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
