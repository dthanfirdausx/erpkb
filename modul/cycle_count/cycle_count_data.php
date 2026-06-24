<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "cycle_count_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;
$input = array(
  'as_of_date'=>cc_input('as_of_date', date('Y-m-d')),
  'material_code'=>cc_input('material_code'),
  'plant_id'=>cc_input('plant_id'),
  'storage_location_id'=>cc_input('storage_location_id'),
  'storage_bin_id'=>cc_input('storage_bin_id'),
  'stock_type'=>cc_input('stock_type'),
  'cycle_class'=>cc_input('cycle_class'),
  'due_status'=>cc_input('due_status'),
  'keyword'=>cc_input('keyword')
);
$rows = cc_load_groups($db, $input);
$pageRows = array_slice($rows, $start, $length);
$data = array();
$no = $start + 1;
foreach ($pageRows as $row) {
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $attrs = ' data-material="'.cc_h($row->material_code).'" data-plant-id="'.intval($row->plant_id).'" data-storage-location-id="'.intval($row->storage_location_id).'" data-storage-bin-id="'.intval($row->storage_bin_id).'" data-stock-type="'.cc_h($row->stock_type).'" data-as-of-date="'.cc_h(cc_valid_date($input['as_of_date'], date('Y-m-d'))).'"';
  $createBtn = $row->open_doc_no ? '<button type="button" class="btn btn-default btn-xs" disabled title="Sudah ada dokumen open">'.cc_h($row->open_doc_no).'</button>' : '<button type="button" class="btn btn-success btn-xs btn-cc-create" '.$attrs.' title="'.wh_h(wh_t('warehouse_create_count_document', 'Create Count Document')).'"><i class="fa fa-plus"></i></button>';
  $data[] = array(
    $no++,
    '<div class="btn-group"><button type="button" class="btn btn-info btn-xs btn-cc-detail" '.$attrs.' title="'.wh_h(wh_t('warehouse_detail_layer', 'Detail Layer')).'"><i class="fa fa-sitemap"></i></button> '.$createBtn.'</div>',
    cc_due_badge($row->due_status),
    '<span class="label label-primary">'.cc_h($row->cycle_class).'</span><br><small>'.$row->cycle_interval_days.' hari</small>',
    '<strong>'.cc_h($row->material_code).'</strong><br><small class="text-muted">'.cc_h($row->nm_barang).'</small>',
    cc_h($location ?: '-').'<br><small>'.cc_h(cc_stock_type_label($row->stock_type)).'</small>',
    cc_h($row->last_count_date ?: '-').'<br><small>'.cc_h($row->last_doc_no ?: 'Belum pernah count').'</small>',
    cc_h($row->oldest_receipt).'<br><small>'.$row->layer_count.' layer | '.$row->customs_doc_count.' dokumen BC</small>',
    number_format((float)$row->system_qty,5,',','.'),
    cc_h($row->satuan)
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>count($rows),'recordsFiltered'=>count($rows),'data'=>$data));
?>
