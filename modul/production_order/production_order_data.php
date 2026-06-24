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

function pod_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function pod_status($status) {
  $class = 'default';
  if ($status === 'RELEASED') $class = 'success';
  if ($status === 'IN_PROCESS') $class = 'info';
  if ($status === 'CONFIRMED' || $status === 'CLOSED') $class = 'primary';
  if ($status === 'TECO') $class = 'warning';
  if ($status === 'CANCELLED') $class = 'danger';
  return '<span class="label label-'.$class.'">'.pod_h($status).'</span>';
}

function pod_strategy($strategy) {
  $strategy = $strategy === 'MTO' ? 'MTO' : 'MTS';
  $class = $strategy === 'MTO' ? 'info' : 'default';
  $label = $strategy === 'MTO' ? 'Make to Order' : 'Make to Stock';
  return '<span class="label label-'.$class.'">'.pod_h($label).'</span>';
}

$columns = array(
  'p.no_production_order',
  'p.start_date',
  'p.finish_date',
  'p.material_code',
  'p.material_name',
  'p.plant',
  'p.priority',
  'p.status',
  'p.created_by'
);

$where = "";
$params = array();
if (!empty($_POST['tgl_awal']) && !empty($_POST['tgl_akhir'])) {
  $where .= " AND p.start_date BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'];
  $params[] = $_POST['tgl_akhir'];
}
if (!empty($_POST['status'])) {
  $where .= " AND p.status=? ";
  $params[] = $_POST['status'];
}
if (!empty($_POST['plant'])) {
  $where .= " AND p.plant=? ";
  $params[] = $_POST['plant'];
}
if (!empty($_POST['keyword'])) {
  $keyword = '%'.trim($_POST['keyword']).'%';
  $where .= " AND (p.no_production_order LIKE ? OR p.no_sales_order LIKE ? OR p.customer_po LIKE ? OR p.material_code LIKE ? OR p.material_name LIKE ? OR p.remarks LIKE ?) ";
  for ($i=0; $i<6; $i++) $params[] = $keyword;
}

$datatable->set_numbering_status(1);
$datatable->set_order_by("p.created_at");
$datatable->set_order_type("desc");

$query = $datatable->get_custom(
  "SELECT p.*,
          COALESCE(m.item_count,0) AS item_count,
          COALESCE(m.required_qty,0) AS required_qty,
          COALESCE(m.issued_qty,0) AS issued_qty
   FROM production_order p
   LEFT JOIN (
     SELECT id_production_order,COUNT(*) AS item_count,SUM(required_qty) AS required_qty,SUM(issued_qty) AS issued_qty
     FROM production_order_material
     GROUP BY id_production_order
   ) m ON m.id_production_order=p.id_production_order
   WHERE 1=1 $where",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $row) {
  $actions = '<div class="po-action-buttons"><button type="button" class="btn btn-info btn-xs btn-detail-po" data-id="'.intval($row->id_production_order).'" title="Detail"><i class="fa fa-eye"></i></button>';
  if ($row->status === 'CREATED') {
    $actions .= ' <button type="button" class="btn btn-success btn-xs btn-release-po" data-id="'.intval($row->id_production_order).'" data-no="'.pod_h($row->no_production_order).'" title="Release"><i class="fa fa-play"></i></button>';
    $actions .= ' <button type="button" class="btn btn-danger btn-xs btn-cancel-po" data-id="'.intval($row->id_production_order).'" data-no="'.pod_h($row->no_production_order).'" title="Cancel"><i class="fa fa-ban"></i></button>';
  }
  if ($row->status === 'RELEASED') {
    $actions .= ' <button type="button" class="btn btn-danger btn-xs btn-cancel-po" data-id="'.intval($row->id_production_order).'" data-no="'.pod_h($row->no_production_order).'" title="Cancel"><i class="fa fa-ban"></i></button>';
  }
  $actions .= '</div>';
  $soRef = $row->no_sales_order ? '<br><small>SO '.pod_h($row->no_sales_order).' / PO '.pod_h($row->customer_po ?: '-').'</small>' : '';
  $doc = '<strong>'.pod_h($row->no_production_order).'</strong><br>'.pod_strategy($row->order_strategy).'<br><small>'.pod_h($row->order_type.' / '.$row->priority).'</small>'.$soRef;
  $material = '<strong>'.pod_h($row->material_code).'</strong><br><small>'.pod_h($row->material_name).'</small>';
  $qty = number_format((float)$row->order_qty, 5, ',', '.').' '.pod_h($row->uom).'<br><small>Done '.number_format((float)$row->completed_qty, 5, ',', '.').'</small>';
  $components = intval($row->item_count).' item<br><small>Req '.number_format((float)$row->required_qty, 5, ',', '.').' / Iss '.number_format((float)$row->issued_qty, 5, ',', '.').'</small>';

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = $actions;
  $result[] = $doc;
  $result[] = pod_h($row->start_date).'<br><small>Finish '.pod_h($row->finish_date).'</small>';
  $result[] = $material;
  $result[] = $qty;
  $result[] = pod_h($row->plant.' / '.$row->storage_location);
  $result[] = $components;
  $result[] = pod_status($row->status);
  $result[] = pod_h($row->created_by);
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
