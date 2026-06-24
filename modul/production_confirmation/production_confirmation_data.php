<?php
if (!function_exists('prod_t')) {
  function prod_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('prod_h')) {
  function prod_h($key, $fallback = '') { return htmlspecialchars((string) prod_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('prod_js')) {
  function prod_js($key, $fallback = '') { return json_encode(prod_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();

function pc_data_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function pc_data_status($status) {
  $class = 'default';
  if ($status === 'POSTED') $class = 'success';
  if ($status === 'REVERSED') $class = 'danger';
  return '<span class="label label-'.$class.'">'.pc_data_h($status).'</span>';
}

$columns = array(
  'c.confirmation_no',
  'c.posting_date',
  'p.no_production_order',
  'p.material_code',
  'p.material_name',
  'c.operation_no',
  'c.work_center',
  'c.yield_qty',
  'c.scrap_qty',
  'c.rework_qty',
  'c.operator_name',
  'c.status',
  'c.created_by'
);

$where = "";
$params = array();
if (!empty($_POST['tgl_awal']) && !empty($_POST['tgl_akhir'])) {
  $where .= " AND c.posting_date BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'];
  $params[] = $_POST['tgl_akhir'];
}
if (!empty($_POST['plant'])) {
  $where .= " AND p.plant=? ";
  $params[] = $_POST['plant'];
}
if (!empty($_POST['status'])) {
  $where .= " AND c.status=? ";
  $params[] = $_POST['status'];
}
if (!empty($_POST['operator_name'])) {
  $where .= " AND c.operator_name=? ";
  $params[] = $_POST['operator_name'];
}
if (!empty($_POST['keyword'])) {
  $keyword = '%'.trim($_POST['keyword']).'%';
  $where .= " AND (c.confirmation_no LIKE ? OR p.no_production_order LIKE ? OR p.material_code LIKE ? OR p.material_name LIKE ? OR c.work_center LIKE ? OR c.remarks LIKE ?) ";
  for ($i=0; $i<6; $i++) $params[] = $keyword;
}

$datatable->set_numbering_status(1);
$datatable->set_order_by("c.confirmation_date");
$datatable->set_order_type("desc");

$query = $datatable->get_custom(
  "SELECT c.*,p.no_production_order,p.plant,p.storage_location,p.material_code,p.material_name,p.order_qty,p.completed_qty,p.scrap_qty AS order_scrap_qty,p.uom,p.status AS order_status
   FROM production_order_confirmation c
   JOIN production_order p ON p.id_production_order=c.id_production_order
   WHERE 1=1 $where",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $row) {
  $actions = '<div class="pc-action-buttons">';
  $actions .= '<button type="button" class="btn btn-info btn-xs btn-detail-pc" data-id="'.intval($row->id_confirmation).'" title="'.prod_h('common_detail','Detail').'"><i class="fa fa-eye"></i></button>';
  if ($row->status === 'POSTED') {
    $actions .= ' <button type="button" class="btn btn-danger btn-xs btn-reverse-pc" data-id="'.intval($row->id_confirmation).'" data-no="'.pc_data_h($row->confirmation_no).'" title="'.prod_h('production_reverse','Reverse').'"><i class="fa fa-undo"></i></button>';
  }
  $actions .= '</div>';

  $confirmation = '<strong>'.pc_data_h($row->confirmation_no).'</strong><br><small>'.pc_data_h($row->posting_date).'</small>';
  $order = '<strong>'.pc_data_h($row->no_production_order).'</strong><br><small>'.pc_data_h($row->plant.' / '.$row->storage_location).'</small>';
  $material = '<strong>'.pc_data_h($row->material_code).'</strong><br><small>'.pc_data_h($row->material_name).'</small>';
  $operation = '<strong>'.pc_data_h($row->operation_no ?: '-').'</strong><br><small>'.pc_data_h(trim($row->work_center.' - '.$row->operation_name, ' -')).'</small>';
  $qty = prod_h('production_yield','Yield').' <strong>'.number_format((float)$row->yield_qty,5,',','.').'</strong> '.pc_data_h($row->uom).'<br><small>'.prod_h('production_scrap','Scrap').' '.number_format((float)$row->scrap_qty,5,',','.').' | '.prod_h('production_rework','Rework').' '.number_format((float)$row->rework_qty,5,',','.').'</small>';

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = $actions;
  $result[] = $confirmation;
  $result[] = $order;
  $result[] = $material;
  $result[] = $operation;
  $result[] = $qty;
  $result[] = pc_data_h($row->operator_name ?: '-').'<br><small>'.pc_data_h($row->shift_code ?: '').'</small>';
  $result[] = pc_data_h($row->final_confirmation);
  $result[] = pc_data_status($row->status);
  $result[] = pc_data_h($row->created_by);
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
