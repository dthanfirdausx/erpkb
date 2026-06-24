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
session_check_json();

function iccd_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function iccd_status($status) {
  if ($status === 'POSTED') return '<span class="label label-success">POSTED</span>';
  if ($status === 'REVERSED') return '<span class="label label-danger">REVERSED</span>';
  return '<span class="label label-default">'.iccd_h($status).'</span>';
}

$columns = array(
  'h.issue_no',
  'h.posting_date',
  'h.cost_center_code',
  'h.cost_center_name',
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
if (!empty($_POST['cost_center_id'])) {
  $where .= " AND h.cost_center_id=? ";
  $params[] = (int)$_POST['cost_center_id'];
}
if (!empty($_POST['keyword'])) {
  $keyword = '%'.trim($_POST['keyword']).'%';
  $where .= " AND (
    h.issue_no LIKE ? OR h.reference_no LIKE ? OR h.cost_center_code LIKE ? OR h.cost_center_name LIKE ? OR h.reason_text LIKE ?
    OR EXISTS (SELECT 1 FROM erp_issue_cost_center_detail d WHERE d.issue_id=h.id AND (d.material_code LIKE ? OR d.material_name LIKE ?))
    OR EXISTS (SELECT 1 FROM erp_issue_cost_center_trace t WHERE t.issue_id=h.id AND (t.no_aju LIKE ? OR t.no_dokpab LIKE ? OR t.no_bpb LIKE ? OR t.lot_no LIKE ?))
  ) ";
  for ($i=0; $i<11; $i++) $params[] = $keyword;
}

$datatable->set_numbering_status(1);
$datatable->set_order_by("h.created_at");
$datatable->set_order_type("desc");

$query = $datatable->get_custom(
  "SELECT h.*,ep.plant_code,es.storage_code,eb.bin_code,
          COALESCE(ds.item_count,0) AS item_count,
          COALESCE(ds.total_qty,0) AS total_qty,
          COALESCE(ds.total_amount,0) AS total_amount,
          COALESCE(ts.trace_count,0) AS trace_count,
          ts.customs_refs
   FROM erp_issue_cost_center h
   LEFT JOIN erp_plant ep ON ep.id=h.plant_id
   LEFT JOIN erp_storage_location es ON es.id=h.storage_location_id
   LEFT JOIN erp_storage_bin eb ON eb.id=h.storage_bin_id
   LEFT JOIN (
     SELECT issue_id,COUNT(*) AS item_count,SUM(qty) AS total_qty,SUM(amount) AS total_amount
     FROM erp_issue_cost_center_detail
     GROUP BY issue_id
   ) ds ON ds.issue_id=h.id
   LEFT JOIN (
     SELECT issue_id,COUNT(*) AS trace_count,
            GROUP_CONCAT(DISTINCT CONCAT(COALESCE(no_aju,''),' / ',COALESCE(no_dokpab,''),' / ',COALESCE(no_bpb,'')) ORDER BY no_aju,no_dokpab,no_bpb SEPARATOR '<br>') AS customs_refs
     FROM erp_issue_cost_center_trace
     GROUP BY issue_id
   ) ts ON ts.issue_id=h.id
   WHERE 1=1 $where",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $row) {
  $actions = '<div class="icc-action-buttons">';
  $actions .= '<button type="button" class="btn btn-primary btn-xs btn-detail-icc" data-id="'.intval($row->id).'" title="'.wh_h(wh_t('warehouse_detail_trace', 'Detail Trace')).'"><i class="fa fa-plus"></i> <span class="badge">'.number_format((float)$row->item_count, 0, ',', '.').'</span></button>';
  if ($row->status === 'POSTED') {
    $actions .= ' <button type="button" class="btn btn-warning btn-xs btn-reversal-icc" data-id="'.intval($row->id).'" data-no="'.iccd_h($row->issue_no).'" title="'.wh_h(wh_t('warehouse_reversal', 'Reversal')).' 202"><i class="fa fa-undo"></i></button>';
  }
  $actions .= '</div>';

  $doc = '<strong>'.iccd_h($row->issue_no).'</strong><br><small class="text-muted">MvT '.iccd_h($row->movement_type).'</small>';
  $cc = '<strong>'.iccd_h($row->cost_center_code).'</strong><br><small class="text-muted">'.iccd_h($row->cost_center_name).'</small>';
  $location = iccd_h(trim($row->plant_code.' / '.$row->storage_code.' / '.$row->bin_code, ' /'));
  $reason = '<strong>'.iccd_h($row->reason_code).'</strong><br><small class="text-muted">'.iccd_h($row->reason_text).'</small>';
  $customsRefs = '';
  if (!empty($row->customs_refs)) {
    foreach (explode('<br>', $row->customs_refs) as $ref) {
      if (trim($ref) !== '') $customsRefs .= iccd_h($ref).'<br>';
    }
  }

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = $actions;
  $result[] = $doc;
  $result[] = iccd_h($row->posting_date);
  $result[] = $cc;
  $result[] = $location;
  $result[] = $reason;
  $result[] = number_format((float)$row->item_count, 0, ',', '.');
  $result[] = number_format((float)$row->total_qty, 5, ',', '.');
  $result[] = number_format((float)$row->total_amount, 2, ',', '.');
  $result[] = '<span class="badge bg-aqua">'.intval($row->trace_count).' trace</span><br><small>'.$customsRefs.'</small>';
  $result[] = iccd_status($row->status);
  $result[] = iccd_h($row->created_by);
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
