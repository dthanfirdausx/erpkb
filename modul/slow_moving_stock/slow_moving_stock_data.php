<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "slow_moving_stock_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;
$input = array(
  'as_of_date'=>sms_input('as_of_date', date('Y-m-d')),
  'threshold_days'=>sms_input('threshold_days', 90),
  'material_code'=>sms_input('material_code'),
  'plant_id'=>sms_input('plant_id'),
  'storage_location_id'=>sms_input('storage_location_id'),
  'storage_bin_id'=>sms_input('storage_bin_id'),
  'stock_type'=>sms_input('stock_type'),
  'risk_label'=>sms_input('risk_label'),
  'jenis_dokpab'=>sms_input('jenis_dokpab'),
  'no_aju'=>sms_input('no_aju'),
  'no_dokpab'=>sms_input('no_dokpab'),
  'slow_only'=>sms_input('slow_only','Y'),
  'keyword'=>sms_input('keyword')
);
$threshold = sms_threshold($input['threshold_days']);
$groups = sms_group_layers(sms_load_layers($db, $input), $threshold);
$pageRows = array_slice($groups, $start, $length);
$data = array();
$no = $start + 1;
foreach ($pageRows as $row) {
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $attrs = ' data-material="'.sms_h($row->material_code).'" data-plant-id="'.intval($row->plant_id).'" data-storage-location-id="'.intval($row->storage_location_id).'" data-storage-bin-id="'.intval($row->storage_bin_id).'" data-stock-type="'.sms_h($row->stock_type).'" data-as-of-date="'.sms_h(sms_valid_date($input['as_of_date'], date('Y-m-d'))).'" data-threshold-days="'.intval($threshold).'" data-risk-label="'.sms_h($input['risk_label']).'" data-slow-only="'.sms_h($input['slow_only']).'"';
  $data[] = array(
    $no++,
    '<button type="button" class="btn btn-info btn-xs btn-sms-detail" '.$attrs.' title="Detail Slow Moving"><i class="fa fa-sitemap"></i></button>',
    sms_risk_badge($row->risk_label),
    '<strong>'.sms_h($row->material_code).'</strong><br><small class="text-muted">'.sms_h($row->material_name).'</small>',
    sms_h($location ?: '-').'<br><small>'.sms_h(sms_stock_type_label($row->stock_type)).'</small>',
    sms_h($row->last_out_date ?: '-').'<br><small>Last move: '.sms_h($row->last_move_date ?: '-').'</small>',
    sms_h($row->oldest_receipt).'<br><small>'.$row->layer_count.' layer | '.$row->doc_total.' dokumen BC</small>',
    '<strong>'.number_format((int)$row->max_idle_days,0,',','.').'</strong><br><small>threshold '.$threshold.' hari</small>',
    number_format((float)$row->qty_critical,5,',','.'),
    number_format((float)$row->qty_slow,5,',','.'),
    '<a href="javascript:void(0)" class="sms-stock-link" '.$attrs.'><strong>'.number_format((float)$row->qty_sisa,5,',','.').'</strong></a>',
    sms_h($row->uom)
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>count($groups),'recordsFiltered'=>count($groups),'data'=>$data));
?>
