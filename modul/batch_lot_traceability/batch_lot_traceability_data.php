<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "batch_lot_traceability_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;
$input = array(
  'tgl_awal'=>blt_input('tgl_awal'),
  'tgl_akhir'=>blt_input('tgl_akhir'),
  'material_code'=>blt_input('material_code'),
  'plant_id'=>blt_input('plant_id'),
  'storage_location_id'=>blt_input('storage_location_id'),
  'storage_bin_id'=>blt_input('storage_bin_id'),
  'stock_type'=>blt_input('stock_type'),
  'jenis_dokpab'=>blt_input('jenis_dokpab'),
  'open_only'=>blt_input('open_only','Y'),
  'keyword'=>blt_input('keyword')
);
$rows = blt_load_layers($db, $input);
$all = array();
foreach ($rows as $r) $all[] = $r;
$pageRows = array_slice($all, $start, $length);
$data = array();
$no = $start + 1;
foreach ($pageRows as $row) {
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $bc = trim((string)$row->jenis_dokpab.' '.(string)$row->no_dokpab);
  $traceBadge = ((int)$row->source_trace_count > 0) ? '<span class="label label-primary">Production Source</span>' : '<span class="label label-default">Direct Receipt</span>';
  $usageBadge = ((int)$row->usage_trace_count > 0) ? '<span class="label label-info">'.intval($row->usage_trace_count).' Usage</span>' : '<span class="label label-default">No Usage</span>';
  $data[] = array(
    $no++,
    '<button type="button" class="btn btn-info btn-xs btn-blt-detail" data-id="'.intval($row->id).'" title="Trace Detail"><i class="fa fa-sitemap"></i></button>',
    '<strong>#'.intval($row->id).'</strong><br><small>'.blt_h($row->ref_table.' #'.$row->ref_id).'</small>',
    '<strong>'.blt_h($row->kode).'</strong><br><small class="text-muted">'.blt_h($row->nm_barang).'</small>',
    blt_h($location ?: '-').'<br><small>'.blt_h(blt_stock_type_label($row->stock_type)).'</small>',
    blt_h($row->tgl_masuk ?: substr((string)$row->created_at,0,10)).'<br><small>'.$row->aging_days.' hari</small>',
    '<strong>'.blt_h($row->no_bpb ?: '-').'</strong><br><small>No Aju: '.blt_h($row->no_aju ?: '-').'</small>',
    blt_h($bc ?: '-'),
    number_format((float)$row->qty_masuk,5,',','.'),
    number_format((float)$row->qty_used,5,',','.'),
    '<a href="javascript:void(0)" class="blt-stock-link" data-id="'.intval($row->id).'"><strong>'.number_format((float)$row->qty_sisa,5,',','.').'</strong></a>',
    blt_h($row->satuan),
    $traceBadge.' '.$usageBadge
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>count($all),'recordsFiltered'=>count($all),'data'=>$data));
?>
