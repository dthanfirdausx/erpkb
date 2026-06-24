<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
session_check_json();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);

function sltd_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function sltd_status($status) {
  if ($status === 'POSTED') return '<span class="label label-success">POSTED</span>';
  if ($status === 'REVERSED') return '<span class="label label-danger">REVERSED</span>';
  return '<span class="label label-default">'.sltd_h($status).'</span>';
}

$columns = array(
  'h.transfer_no',
  'h.posting_date',
  'src.storage_code',
  'dst.storage_code',
  'h.reason_code',
  'h.status',
  'h.created_by'
);

$where = "";
$params = array();
if (!empty($_POST['tgl_awal']) && !empty($_POST['tgl_akhir'])) {
  $where .= " AND h.posting_date BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'];
  $params[] = $_POST['tgl_akhir'];
}
if (!empty($_POST['status'])) {
  $where .= " AND h.status=? ";
  $params[] = $_POST['status'];
}
if (!empty($_POST['source_storage_location_id'])) {
  $where .= " AND h.source_storage_location_id=? ";
  $params[] = (int) $_POST['source_storage_location_id'];
}
if (!empty($_POST['destination_storage_location_id'])) {
  $where .= " AND h.destination_storage_location_id=? ";
  $params[] = (int) $_POST['destination_storage_location_id'];
}
if (!empty($_POST['keyword'])) {
  $kw = '%'.trim($_POST['keyword']).'%';
  $where .= " AND (
    h.transfer_no LIKE ? OR h.reference_no LIKE ? OR h.reason_code LIKE ? OR h.reason_text LIKE ?
    OR EXISTS (SELECT 1 FROM erp_storage_location_transfer_detail d WHERE d.transfer_id=h.id AND (d.material_code LIKE ? OR d.material_name LIKE ?))
    OR EXISTS (SELECT 1 FROM erp_storage_location_transfer_trace t WHERE t.transfer_id=h.id AND (t.no_aju LIKE ? OR t.no_dokpab LIKE ? OR t.no_bpb LIKE ?))
  ) ";
  for ($i=0; $i<9; $i++) $params[] = $kw;
}

$datatable->set_numbering_status(1);
$datatable->set_order_by("h.posting_date");
$datatable->set_order_type("desc");

$query = $datatable->get_custom(
  "SELECT h.*,
          sp.plant_code AS source_plant_code,
          dp.plant_code AS destination_plant_code,
          src.storage_code AS source_storage_code,src.storage_name AS source_storage_name,
          dst.storage_code AS destination_storage_code,dst.storage_name AS destination_storage_name,
          sb.bin_code AS source_bin_code,dbin.bin_code AS destination_bin_code,
          COALESCE(ds.item_count,0) AS item_count,
          COALESCE(ds.total_qty,0) AS total_qty,
          COALESCE(ds.total_amount,0) AS total_amount,
          COALESCE(ts.trace_count,0) AS trace_count
   FROM erp_storage_location_transfer h
   LEFT JOIN erp_plant sp ON sp.id=h.source_plant_id
   LEFT JOIN erp_plant dp ON dp.id=h.destination_plant_id
   LEFT JOIN erp_storage_location src ON src.id=h.source_storage_location_id
   LEFT JOIN erp_storage_location dst ON dst.id=h.destination_storage_location_id
   LEFT JOIN erp_storage_bin sb ON sb.id=h.source_storage_bin_id
   LEFT JOIN erp_storage_bin dbin ON dbin.id=h.destination_storage_bin_id
   LEFT JOIN (
     SELECT transfer_id,COUNT(*) AS item_count,SUM(qty) AS total_qty,SUM(amount) AS total_amount
     FROM erp_storage_location_transfer_detail
     GROUP BY transfer_id
   ) ds ON ds.transfer_id=h.id
   LEFT JOIN (
     SELECT transfer_id,COUNT(*) AS trace_count
     FROM erp_storage_location_transfer_trace
     GROUP BY transfer_id
   ) ts ON ts.transfer_id=h.id
   WHERE 1=1 $where",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $row) {
  $actions = '<div class="slt-action-buttons">';
  $actions .= '<button type="button" class="btn btn-info btn-xs btn-detail-slt" data-id="'.intval($row->id).'" title="'.wh_h(wh_t('common_detail', 'Detail')).'"><i class="fa fa-eye"></i> <span class="badge">'.intval($row->item_count).'</span></button>';
  if ($row->status === 'POSTED') {
    $actions .= ' <button type="button" class="btn btn-warning btn-xs btn-reversal-slt" data-id="'.intval($row->id).'" data-no="'.sltd_h($row->transfer_no).'" title="'.wh_h(wh_t('warehouse_reversal', 'Reversal')).' 312"><i class="fa fa-undo"></i></button>';
  }
  $actions .= '</div>';
  $doc = '<strong>'.sltd_h($row->transfer_no).'</strong><br><small>MvT '.sltd_h($row->movement_type).'</small>';
  $source = '<strong>'.sltd_h(trim($row->source_plant_code.' / '.$row->source_storage_code, ' /')).'</strong><br><small>'.sltd_h(trim($row->source_storage_name.' / '.$row->source_bin_code, ' /')).'</small>';
  $dest = '<strong>'.sltd_h(trim($row->destination_plant_code.' / '.$row->destination_storage_code, ' /')).'</strong><br><small>'.sltd_h(trim($row->destination_storage_name.' / '.$row->destination_bin_code, ' /')).'</small>';
  $reason = '<strong>'.sltd_h($row->reason_code ?: '-').'</strong><br><small>'.sltd_h($row->reason_text ?: '-').'</small>';

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = $actions;
  $result[] = $doc;
  $result[] = sltd_h($row->posting_date);
  $result[] = $source;
  $result[] = $dest;
  $result[] = $reason;
  $result[] = number_format((float)$row->item_count,0,',','.');
  $result[] = number_format((float)$row->total_qty,5,',','.');
  $result[] = number_format((float)$row->total_amount,2,',','.');
  $result[] = '<span class="badge bg-aqua">'.intval($row->trace_count).' trace</span>';
  $result[] = sltd_status($row->status);
  $result[] = sltd_h($row->created_by);
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
