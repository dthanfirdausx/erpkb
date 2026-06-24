<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "count_entry_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;
$input = array('tgl_awal'=>ce_input('tgl_awal', date('Y-m-01')),'tgl_akhir'=>ce_input('tgl_akhir', date('Y-m-d')),'doc_type'=>ce_input('doc_type'),'doc_no'=>ce_input('doc_no'),'material_code'=>ce_input('material_code'),'plant_id'=>ce_input('plant_id'),'storage_location_id'=>ce_input('storage_location_id'),'storage_bin_id'=>ce_input('storage_bin_id'),'stock_type'=>ce_input('stock_type'),'item_status'=>ce_input('item_status'),'keyword'=>ce_input('keyword'));
$rows = iterator_to_array(ce_load_rows($db, $input));
$pageRows = array_slice($rows, $start, $length);
$data = array();
$no = $start + 1;
foreach ($pageRows as $row) {
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $hasCount = $row->counted_qty !== null;
  $countedValue = $hasCount ? (float)$row->counted_qty : (float)$row->system_qty;
  $diff = $row->difference_qty === null ? ($countedValue - (float)$row->system_qty) : (float)$row->difference_qty;
  $attrs = ' data-doc-type="'.ce_h($row->doc_type).'" data-item-id="'.intval($row->item_id).'"';
  $inputDisabled = in_array($row->document_status, array('POSTED','CANCELLED')) ? ' disabled' : '';
  $detailAttrs = ' data-doc="'.ce_h($row->doc_no).'" data-doc-type="'.ce_h(ce_doc_type_label($row->doc_type)).'" data-count-date="'.ce_h($row->count_date).'" data-status="'.ce_h($row->item_status).'" data-material="'.ce_h($row->material_code.' - '.$row->material_name).'" data-location="'.ce_h($location ?: '-').'" data-stock-type="'.ce_h(ce_stock_type_label($row->stock_type)).'" data-system-qty="'.ce_h(number_format((float)$row->system_qty,5,',','.')).'" data-counted-qty="'.ce_h(number_format($countedValue,5,',','.')).'" data-difference="'.ce_h(number_format($diff,5,',','.')).'" data-uom="'.ce_h($row->uom).'"';
  $data[] = array(
    $no++,
    '<button type="button" class="btn btn-info btn-xs btn-ce-detail" '.$detailAttrs.' title="'.wh_h(wh_t('common_detail', 'Detail')).'"><i class="fa fa-eye"></i></button> <button type="button" class="btn btn-primary btn-xs btn-ce-save" '.$attrs.$inputDisabled.' title="Save Count"><i class="fa fa-save"></i></button>',
    '<strong>'.ce_h($row->doc_no).'</strong><br><small>'.ce_h(ce_doc_type_label($row->doc_type)).' | '.ce_h($row->count_date).'</small>',
    ce_status_badge($row->item_status).'<br><small>Doc: '.strip_tags(ce_status_badge($row->document_status)).'</small>',
    '<strong>'.ce_h($row->material_code).'</strong><br><small class="text-muted">'.ce_h($row->material_name).'</small>',
    ce_h($location ?: '-').'<br><small>'.ce_h(ce_stock_type_label($row->stock_type)).'</small>',
    number_format((float)$row->system_qty,5,',','.'),
    '<input type="number" step="0.00001" class="form-control input-sm ce-counted-qty" value="'.ce_h(number_format($countedValue,5,'.','')).'"'.$inputDisabled.'>',
    '<span class="ce-diff '.($diff < 0 ? 'text-danger' : ($diff > 0 ? 'text-success' : '')).'">'.number_format($diff,5,',','.').'</span>',
    ce_h($row->uom),
    '<input type="text" class="form-control input-sm ce-remarks" value="'.ce_h($row->remarks).'"'.$inputDisabled.'>'
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>count($rows),'recordsFiltered'=>count($rows),'data'=>$data));
?>
