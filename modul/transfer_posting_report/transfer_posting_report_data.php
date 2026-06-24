<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "transfer_posting_report_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;

$input = tpr_input_array();
$total = tpr_count_rows($db, $input);
$rows = tpr_load_rows($db, $input, $length, $start);
$data = array();
$no = $start + 1;

foreach ($rows as $row) {
  $direction = $row->movement_direction;
  $dirClass = $direction === 'OUT' ? 'label-danger' : 'label-success';
  $doc = $row->no_ref ?: ($row->no_bpb ?: $row->ref_pengganti);
  $customs = trim((string)($row->no_aju ?: $row->header_no_aju).' / '.(string)($row->no_dokpab ?: $row->header_no_dokpab), ' /');
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $qtyIn = $direction === 'IN' ? abs((float)$row->qty) : 0;
  $qtyOut = $direction === 'OUT' ? abs((float)$row->qty) : 0;

  $data[] = array(
    $no++,
    '<button type="button" class="btn btn-info btn-xs btn-tpr-detail" data-id="'.intval($row->id_detail).'" title="'.wh_h(wh_t('common_detail', 'Detail')).'"><i class="fa fa-eye"></i></button>',
    '<strong>'.tpr_h($doc ?: '-').'</strong><br><small class="text-muted">Item '.tpr_h($row->id_detail).'</small>',
    tpr_h($row->posting_date),
    '<span class="label '.$dirClass.'">'.tpr_h($direction).'</span><br><small>MvT '.tpr_h($row->move_code).' - '.tpr_h(tpr_movement_label($row->move_code, $row->ref_type, $direction)).'</small>',
    '<strong>'.tpr_h($row->material_code).'</strong><br><small class="text-muted">'.tpr_h($row->nm_barang).'</small>',
    tpr_h($location ?: '-').'<br><small class="text-muted">'.tpr_h(tpr_stock_type_label($row->stock_type_label)).'</small>',
    '<small>'.tpr_h($customs ?: '-').'</small>',
    number_format($qtyIn, 5, ',', '.'),
    number_format($qtyOut, 5, ',', '.'),
    number_format((float)$row->amount, 2, ',', '.'),
    tpr_h($row->uom ?: $row->satuan),
    tpr_h($row->purchase_order_no ?: $row->ref_type),
    tpr_h($row->username)
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
