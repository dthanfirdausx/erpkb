<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "inventory_valuation_report_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;

$input = ivr_input_array();
$groups = ivr_group_layers(ivr_load_layers($db, $input));
$pageRows = array_slice($groups, $start, $length);
$data = array();
$no = $start + 1;

foreach ($pageRows as $row) {
  $attrs = ' data-material="'.ivr_h($row->material_code).'" data-plant-id="'.intval($row->plant_id).'" data-storage-location-id="'.intval($row->storage_location_id).'" data-storage-bin-id="'.intval($row->storage_bin_id).'" data-stock-type="'.ivr_h($row->stock_type).'" data-as-of-date="'.ivr_h(ivr_valid_date($input['as_of_date'], date('Y-m-d'))).'"';
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $status = $row->zero_layers > 0 ? '<span class="label label-warning">Partial/Zero Price</span>' : '<span class="label label-success">Valued</span>';
  $data[] = array(
    $no++,
    '<button type="button" class="btn btn-info btn-xs btn-ivr-detail" '.$attrs.' title="Detail Valuation Layer"><i class="fa fa-sitemap"></i></button>',
    '<strong>'.ivr_h($row->material_code).'</strong><br><small class="text-muted">'.ivr_h($row->material_name).'</small>',
    ivr_h($row->material_type ?: '-').'<br><small>'.ivr_h($row->material_group ?: '-').'</small>',
    ivr_h($location ?: '-').'<br><small>'.ivr_h(ivr_stock_type_label($row->stock_type)).'</small>',
    number_format((float)$row->total_qty,5,',','.'),
    ivr_h($row->uom),
    number_format((float)$row->avg_price,5,',','.'),
    number_format((float)$row->total_value,2,',','.'),
    '<small>Min '.number_format((float)$row->min_price,5,',','.').'<br>Max '.number_format((float)$row->max_price,5,',','.').'</small>',
    intval($row->layer_count),
    intval($row->customs_doc_count),
    $status
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>count($groups),'recordsFiltered'=>count($groups),'data'=>$data));
?>
