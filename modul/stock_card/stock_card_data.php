<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
include "stock_card_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;

$input = array(
  'tgl_awal' => scard_post('tgl_awal', date('Y-m-01')),
  'tgl_akhir' => scard_post('tgl_akhir', date('Y-m-d')),
  'material_code' => scard_post('material_code'),
  'plant_id' => scard_post('plant_id'),
  'storage_location_id' => scard_post('storage_location_id'),
  'storage_bin_id' => scard_post('storage_bin_id'),
  'stock_type' => scard_post('stock_type'),
  'move_code' => scard_post('move_code'),
  'direction' => scard_post('direction'),
  'keyword' => scard_post('keyword')
);

$rows = scard_load_card_rows($db, $input);
$pageRows = array_slice($rows, $start, $length);
$data = array();
$no = $start + 1;

foreach ($pageRows as $row) {
  $direction = (float)$row->signed_qty < 0 ? 'OUT' : 'IN';
  $badgeClass = $direction === 'OUT' ? 'label-danger' : 'label-success';
  $movement = scard_movement_label($row->move_code, $row->ref_type, $direction);
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $customs = trim((string)($row->no_aju ?: $row->header_no_aju).' / '.(string)($row->no_dokpab ?: $row->header_no_dokpab), ' /');
  $doc = $row->no_ref ?: ($row->no_bpb ?: $row->ref_pengganti);
  $balanceAttr = ' data-material="'.scard_h($row->material_code).'" data-plant-id="'.intval($row->plant_id).'" data-storage-location-id="'.intval($row->storage_location_id).'" data-storage-bin-id="'.intval($row->storage_bin_id).'" data-stock-type="'.scard_h($row->line_stock_type).'" data-posting-date="'.scard_h(substr((string)$row->posting_date,0,10)).'"';

  $data[] = array(
    $no++,
    '<div class="scard-actions"><button type="button" class="btn btn-info btn-xs btn-layer-scard" '.$balanceAttr.' title="'.wh_h(wh_t('warehouse_detail_lot_batch_bc', 'Detail Lot/Batch/BC')).'"><i class="fa fa-sitemap"></i></button></div>',
    '<strong>'.scard_h($row->material_code).'</strong><br><small class="text-muted">'.scard_h($row->nm_barang).'</small>',
    scard_h($row->posting_date),
    '<span class="label '.$badgeClass.'">'.scard_h($direction).'</span><br><small>MvT '.scard_h($row->move_code).' - '.scard_h($movement).'</small>',
    '<strong>'.scard_h($doc ?: '-').'</strong><br><small class="text-muted">'.scard_h($row->purchase_order_no ?: $row->ref_type).'</small>',
    scard_h($location ?: '-').'<br><small class="text-muted">'.scard_h(scard_stock_type_label($row->line_stock_type)).'</small>',
    '<small>'.scard_h($customs ?: '-').'</small>',
    number_format((float)$row->qty_in, 5, ',', '.'),
    number_format((float)$row->qty_out, 5, ',', '.'),
    '<a href="javascript:void(0)" class="scard-balance-link" '.$balanceAttr.'><strong>'.number_format((float)$row->running_balance, 5, ',', '.').'</strong></a>',
    scard_h($row->uom ?: $row->satuan),
    scard_h($row->username)
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
  'draw' => $draw,
  'recordsTotal' => count($rows),
  'recordsFiltered' => count($rows),
  'data' => $data
));
?>
