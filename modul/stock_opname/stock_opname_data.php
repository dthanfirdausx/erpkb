<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "stock_opname_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;
$input = array('as_of_date'=>so_input('as_of_date', date('Y-m-d')),'material_code'=>so_input('material_code'),'plant_id'=>so_input('plant_id'),'storage_location_id'=>so_input('storage_location_id'),'storage_bin_id'=>so_input('storage_bin_id'),'stock_type'=>so_input('stock_type'),'doc_status'=>so_input('doc_status'),'keyword'=>so_input('keyword'));
$rows = so_load_groups($db, $input);
$pageRows = array_slice($rows, $start, $length);
$data = array();
$no = $start + 1;
foreach ($pageRows as $row) {
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $attrs = ' data-material="'.so_h($row->material_code).'" data-plant-id="'.intval($row->plant_id).'" data-storage-location-id="'.intval($row->storage_location_id).'" data-storage-bin-id="'.intval($row->storage_bin_id).'" data-stock-type="'.so_h($row->stock_type).'" data-as-of-date="'.so_h(so_valid_date($input['as_of_date'], date('Y-m-d'))).'"';
  $data[] = array(
    $no++,
    '<button type="button" class="btn btn-info btn-xs btn-so-detail" '.$attrs.' title="'.wh_h(wh_t('warehouse_detail_layer', 'Detail Layer')).'"><i class="fa fa-sitemap"></i></button>',
    so_doc_status_badge($row->open_doc_no),
    '<strong>'.so_h($row->material_code).'</strong><br><small class="text-muted">'.so_h($row->nm_barang).'</small>',
    so_h($location ?: '-').'<br><small>'.so_h(so_stock_type_label($row->stock_type)).'</small>',
    so_h($row->last_count_date ?: '-').'<br><small>'.so_h($row->last_doc_no ?: 'Belum pernah opname').'</small>',
    so_h($row->oldest_receipt).'<br><small>'.$row->layer_count.' layer | '.$row->customs_doc_count.' dokumen BC</small>',
    number_format((float)$row->system_qty,5,',','.'),
    so_h($row->satuan)
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>count($rows),'recordsFiltered'=>count($rows),'data'=>$data));
?>
