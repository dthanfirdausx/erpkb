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
if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
session_check_json();

function grpo_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function grpo_status($status) {
  return '<span class="label label-'.($status === 'POSTED' ? 'success' : 'danger').'">'.grpo_h($status).'</span>';
}

$columns = array(
  'h.gr_no','h.posting_date','h.no_production_order','h.confirmation_no','d.material_code','d.material_name',
  'd.qty','d.uom','ep.plant_code','es.storage_code','h.stock_type','h.status','h.created_by'
);

$where = "";
$params = array();
if (!empty($_POST['tgl_awal']) && !empty($_POST['tgl_akhir'])) {
  $where .= " AND h.posting_date BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'];
  $params[] = $_POST['tgl_akhir'];
}
if (!empty($_POST['plant_id'])) {
  $where .= " AND h.plant_id=? ";
  $params[] = $_POST['plant_id'];
}
if (!empty($_POST['storage_location_id'])) {
  $where .= " AND h.storage_location_id=? ";
  $params[] = $_POST['storage_location_id'];
}
if (!empty($_POST['status'])) {
  $where .= " AND h.status=? ";
  $params[] = $_POST['status'];
}
if (!empty($_POST['keyword'])) {
  $keyword = '%'.trim($_POST['keyword']).'%';
  $where .= " AND (h.gr_no LIKE ? OR h.no_production_order LIKE ? OR h.confirmation_no LIKE ? OR d.material_code LIKE ? OR d.material_name LIKE ? OR h.remarks LIKE ? OR EXISTS (SELECT 1 FROM erp_gr_production_trace tr WHERE tr.gr_id=h.id AND (tr.raw_material_code LIKE ? OR tr.raw_material_name LIKE ? OR tr.no_aju LIKE ? OR tr.no_dokpab LIKE ? OR tr.lot_no LIKE ?))) ";
  for ($i=0; $i<11; $i++) $params[] = $keyword;
}

$datatable->set_numbering_status(1);
$datatable->set_order_by("h.posting_date");
$datatable->set_order_type("desc");

$query = $datatable->get_custom(
  "SELECT h.*,d.material_code,d.material_name,d.qty,d.uom,d.stock_layer_id,
          ep.plant_code,es.storage_code,eb.bin_code,
          COALESCE(tr.trace_count,0) AS trace_count,
          COALESCE(tr.raw_count,0) AS raw_count
   FROM erp_gr_production h
   JOIN erp_gr_production_detail d ON d.gr_id=h.id
   LEFT JOIN erp_plant ep ON ep.id=h.plant_id
   LEFT JOIN erp_storage_location es ON es.id=h.storage_location_id
   LEFT JOIN erp_storage_bin eb ON eb.id=h.storage_bin_id
   LEFT JOIN (
     SELECT gr_id,COUNT(*) AS trace_count,COUNT(DISTINCT raw_material_code) AS raw_count
     FROM erp_gr_production_trace
     GROUP BY gr_id
   ) tr ON tr.gr_id=h.id
   WHERE 1=1 $where",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $row) {
  $actions = '<div class="grpo-action-buttons"><button type="button" class="btn btn-info btn-xs btn-detail-grpo" data-id="'.intval($row->id).'" title="'.wh_h(wh_t('common_detail', 'Detail')).'"><i class="fa fa-eye"></i></button>';
  if ($row->status === 'POSTED') {
    $actions .= ' <button type="button" class="btn btn-danger btn-xs btn-reverse-grpo" data-id="'.intval($row->id).'" data-no="'.grpo_h($row->gr_no).'" title="Reverse"><i class="fa fa-undo"></i></button>';
  }
  $actions .= '</div>';

  $doc = '<strong>'.grpo_h($row->gr_no).'</strong><br><small>'.grpo_h($row->posting_date).' | MvT 101</small>';
  $prod = '<strong>'.grpo_h($row->no_production_order).'</strong><br><small>Confirmation '.grpo_h($row->confirmation_no).'</small>';
  $mat = '<strong>'.grpo_h($row->material_code).'</strong><br><small>'.grpo_h($row->material_name).'</small>';
  $loc = grpo_h(trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /'));
  $trace = intval($row->trace_count).' trace<br><small>'.intval($row->raw_count).' raw material</small>';

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = $actions;
  $result[] = $doc;
  $result[] = $prod;
  $result[] = $mat;
  $result[] = number_format((float)$row->qty,5,',','.');
  $result[] = grpo_h($row->uom);
  $result[] = $loc ?: '-';
  $result[] = grpo_h($row->stock_type);
  $result[] = $trace;
  $result[] = grpo_status($row->status);
  $result[] = grpo_h($row->created_by);
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
