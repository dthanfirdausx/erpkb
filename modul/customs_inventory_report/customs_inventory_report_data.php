<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "customs_inventory_report_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;
$input = array(
  'tgl_awal'=>cir_input('tgl_awal'),
  'tgl_akhir'=>cir_input('tgl_akhir'),
  'material_code'=>cir_input('material_code'),
  'plant_id'=>cir_input('plant_id'),
  'storage_location_id'=>cir_input('storage_location_id'),
  'storage_bin_id'=>cir_input('storage_bin_id'),
  'stock_type'=>cir_input('stock_type'),
  'jenis_dokpab'=>cir_input('jenis_dokpab'),
  'no_aju'=>cir_input('no_aju'),
  'no_dokpab'=>cir_input('no_dokpab'),
  'open_only'=>cir_input('open_only','Y'),
  'keyword'=>cir_input('keyword')
);
$groups = cir_group_layers(cir_load_layers($db, $input));
$pageRows = array_slice($groups, $start, $length);
$data = array();
$no = $start + 1;
foreach ($pageRows as $row) {
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $attrs = ' data-jenis="'.cir_h($row->jenis_dokpab).'" data-aju="'.cir_h($row->no_aju).'" data-dok="'.cir_h($row->no_dokpab).'" data-material="'.cir_h($row->material_code).'" data-plant-id="'.intval($row->plant_id).'" data-storage-location-id="'.intval($row->storage_location_id).'" data-storage-bin-id="'.intval($row->storage_bin_id).'" data-stock-type="'.cir_h($row->stock_type).'"';
  $data[] = array(
    $no++,
    '<button type="button" class="btn btn-info btn-xs btn-cir-detail" '.$attrs.' title="Detail Customs Inventory"><i class="fa fa-sitemap"></i></button>',
    '<strong>'.cir_h($row->jenis_dokpab ?: 'Non BC').'</strong><br><small>No Daftar: '.cir_h($row->no_dokpab ?: '-').'</small>',
    '<strong>'.cir_h($row->no_aju ?: '-').'</strong>',
    '<strong>'.cir_h($row->material_code).'</strong><br><small class="text-muted">'.cir_h($row->material_name).'</small>',
    cir_h($location ?: '-').'<br><small>'.cir_h(cir_stock_type_label($row->stock_type)).'</small>',
    cir_h($row->oldest_date).'<br><small>Max '.$row->max_age.' hari | '.$row->layer_count.' layer</small>',
    number_format((float)$row->qty_masuk,5,',','.'),
    number_format((float)$row->qty_used,5,',','.'),
    '<a href="javascript:void(0)" class="cir-stock-link" '.$attrs.'><strong>'.number_format((float)$row->qty_sisa,5,',','.').'</strong></a>',
    cir_h($row->uom)
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>count($groups),'recordsFiltered'=>count($groups),'data'=>$data));
?>
