<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "customs_stock_traceability_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;
$input = array(
  'tgl_awal'=>cst_input('tgl_awal'),
  'tgl_akhir'=>cst_input('tgl_akhir'),
  'material_code'=>cst_input('material_code'),
  'plant_id'=>cst_input('plant_id'),
  'storage_location_id'=>cst_input('storage_location_id'),
  'storage_bin_id'=>cst_input('storage_bin_id'),
  'stock_type'=>cst_input('stock_type'),
  'jenis_dokpab'=>cst_input('jenis_dokpab'),
  'no_aju'=>cst_input('no_aju'),
  'no_dokpab'=>cst_input('no_dokpab'),
  'open_only'=>cst_input('open_only','Y'),
  'keyword'=>cst_input('keyword')
);
$groups = cst_group_layers(cst_load_layers($db, $input));
$pageRows = array_slice($groups, $start, $length);
$data = array();
$no = $start + 1;
foreach ($pageRows as $row) {
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $attrs = ' data-jenis="'.cst_h($row->jenis_dokpab).'" data-aju="'.cst_h($row->no_aju).'" data-dok="'.cst_h($row->no_dokpab).'" data-material="'.cst_h($row->material_code).'" data-plant-id="'.intval($row->plant_id).'" data-storage-location-id="'.intval($row->storage_location_id).'" data-storage-bin-id="'.intval($row->storage_bin_id).'" data-stock-type="'.cst_h($row->stock_type).'"';
  $data[] = array(
    $no++,
    '<button type="button" class="btn btn-info btn-xs btn-cst-detail" '.$attrs.' title="Detail Customs Stock"><i class="fa fa-sitemap"></i></button>',
    '<strong>'.cst_h($row->jenis_dokpab ?: 'Non BC').'</strong><br><small>No Daftar: '.cst_h($row->no_dokpab ?: '-').'</small>',
    '<strong>'.cst_h($row->no_aju ?: '-').'</strong>',
    '<strong>'.cst_h($row->material_code).'</strong><br><small class="text-muted">'.cst_h($row->material_name).'</small>',
    cst_h($location ?: '-').'<br><small>'.cst_h(cst_stock_type_label($row->stock_type)).'</small>',
    cst_h($row->oldest_date).'<br><small>Max '.$row->max_age.' hari | '.$row->layer_count.' layer</small>',
    number_format((float)$row->qty_masuk,5,',','.'),
    number_format((float)$row->qty_used,5,',','.'),
    '<a href="javascript:void(0)" class="cst-stock-link" '.$attrs.'><strong>'.number_format((float)$row->qty_sisa,5,',','.').'</strong></a>',
    cst_h($row->uom)
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>count($groups),'recordsFiltered'=>count($groups),'data'=>$data));
?>
