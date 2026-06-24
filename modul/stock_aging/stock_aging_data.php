<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "stock_aging_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;

$input = array(
  'as_of_date' => saging_input('as_of_date', date('Y-m-d')),
  'material_code' => saging_input('material_code'),
  'plant_id' => saging_input('plant_id'),
  'storage_location_id' => saging_input('storage_location_id'),
  'storage_bin_id' => saging_input('storage_bin_id'),
  'stock_type' => saging_input('stock_type'),
  'aging_bucket' => saging_input('aging_bucket'),
  'keyword' => saging_input('keyword')
);

$groups = saging_group_layers(saging_load_layers($db, $input));
$pageRows = array_slice($groups, $start, $length);
$data = array();
$no = $start + 1;
$buckets = saging_buckets();

foreach ($pageRows as $row) {
  $attrsBase = ' data-material="'.saging_h($row->material_code).'" data-plant-id="'.intval($row->plant_id).'" data-storage-location-id="'.intval($row->storage_location_id).'" data-storage-bin-id="'.intval($row->storage_bin_id).'" data-stock-type="'.saging_h($row->stock_type).'" data-as-of-date="'.saging_h(saging_valid_date($input['as_of_date'], date('Y-m-d'))).'"';
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $line = array();
  $line[] = $no++;
  $line[] = '<button type="button" class="btn btn-info btn-xs btn-aging-detail" '.$attrsBase.' data-bucket="" title="Detail Aging"><i class="fa fa-sitemap"></i></button>';
  $line[] = '<strong>'.saging_h($row->material_code).'</strong><br><small class="text-muted">'.saging_h($row->material_name).'</small>';
  $line[] = saging_h($location ?: '-').'<br><small class="text-muted">'.saging_h(saging_stock_type_label($row->stock_type)).'</small>';
  $line[] = saging_h($row->oldest_date).'<br><small class="text-muted">Max '.$row->max_age.' hari | '.$row->layer_count.' layer</small>';
  foreach ($buckets as $bucketKey => $bucket) {
    $qty = (float)$row->bucket_qty[$bucketKey];
    $line[] = $qty > 0 ? '<a href="javascript:void(0)" class="aging-bucket-link" '.$attrsBase.' data-bucket="'.saging_h($bucketKey).'"><strong>'.number_format($qty,5,',','.').'</strong></a>' : '-';
  }
  $line[] = '<a href="javascript:void(0)" class="aging-bucket-link" '.$attrsBase.' data-bucket=""><strong>'.number_format((float)$row->total_qty,5,',','.').'</strong></a>';
  $line[] = saging_h($row->uom);
  $data[] = $line;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
  'draw' => $draw,
  'recordsTotal' => count($groups),
  'recordsFiltered' => count($groups),
  'data' => $data
));
?>
