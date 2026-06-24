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

function gipd_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function gipd_status($status) {
  if ($status === 'POSTED') return '<span class="label label-success">POSTED</span>';
  if ($status === 'REVERSED') return '<span class="label label-danger">REVERSED</span>';
  return '<span class="label label-default">'.gipd_h($status).'</span>';
}

$columns = array(
  'h.issue_no',
  'h.posting_date',
  'h.production_no',
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
if (!empty($_POST['production_no'])) {
  $where .= " AND h.production_no LIKE ? ";
  $params[] = '%'.trim($_POST['production_no']).'%';
}
if (!empty($_POST['keyword'])) {
  $keyword = '%'.trim($_POST['keyword']).'%';
  $where .= " AND (h.issue_no LIKE ? OR h.production_no LIKE ? OR h.reference_no LIKE ? OR h.reason_text LIKE ? OR EXISTS (SELECT 1 FROM erp_issue_production_detail d WHERE d.issue_id=h.id AND (d.material_code LIKE ? OR d.material_name LIKE ?)) OR EXISTS (SELECT 1 FROM erp_issue_production_trace t WHERE t.issue_id=h.id AND (t.no_aju LIKE ? OR t.no_dokpab LIKE ? OR t.lot_no LIKE ? OR t.no_bpb LIKE ?))) ";
  for ($i=0; $i<10; $i++) $params[] = $keyword;
}

$datatable->set_numbering_status(1);
$datatable->set_order_by("h.created_at");
$datatable->set_order_type("desc");

$query = $datatable->get_custom(
  "SELECT h.*,ep.plant_code,es.storage_code,eb.bin_code,
          COALESCE(ds.item_count,0) AS item_count,
          COALESCE(ds.total_qty,0) AS total_qty,
          COALESCE(ts.trace_count,0) AS trace_count,
          ts.customs_refs
   FROM erp_issue_production h
   LEFT JOIN erp_plant ep ON ep.id=h.plant_id
   LEFT JOIN erp_storage_location es ON es.id=h.storage_location_id
   LEFT JOIN erp_storage_bin eb ON eb.id=h.storage_bin_id
   LEFT JOIN (
     SELECT issue_id,COUNT(*) AS item_count,SUM(issued_qty) AS total_qty
     FROM erp_issue_production_detail
     GROUP BY issue_id
   ) ds ON ds.issue_id=h.id
   LEFT JOIN (
     SELECT issue_id,COUNT(*) AS trace_count,GROUP_CONCAT(DISTINCT CONCAT(COALESCE(no_aju,''),' / ',COALESCE(no_dokpab,'')) ORDER BY no_aju,no_dokpab SEPARATOR '<br>') AS customs_refs
     FROM erp_issue_production_trace
     GROUP BY issue_id
   ) ts ON ts.issue_id=h.id
   WHERE 1=1 $where",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $row) {
  $actions = '<div class="gip-action-buttons"><button type="button" class="btn btn-info btn-xs btn-detail-gip" data-id="'.intval($row->id).'" title="'.wh_h(wh_t('warehouse_detail_trace', 'Detail Trace')).'"><i class="fa fa-eye"></i></button>';
  if ($row->status === 'POSTED') {
    $actions .= ' <button type="button" class="btn btn-warning btn-xs btn-reversal-gip" data-id="'.intval($row->id).'" data-no="'.gipd_h($row->issue_no).'" title="'.wh_h(wh_t('warehouse_reversal', 'Reversal')).' 262"><i class="fa fa-undo"></i></button>';
  }
  $actions .= '</div>';
  $doc = '<strong>'.gipd_h($row->issue_no).'</strong><br><small class="text-muted">MvT '.gipd_h($row->movement_type).'</small>';
  $production = '<strong>'.gipd_h($row->production_no).'</strong><br><small class="text-muted">'.gipd_h($row->reference_no).'</small>';
  $location = gipd_h(trim($row->plant_code.' / '.$row->storage_code.' / '.$row->bin_code, ' /'));
  $reason = '<strong>'.gipd_h($row->reason_code).'</strong><br><small class="text-muted">'.gipd_h($row->reason_text).'</small>';
  $customsRefs = '';
  if (!empty($row->customs_refs)) {
    $refs = explode('<br>', $row->customs_refs);
    foreach ($refs as $ref) {
      $customsRefs .= gipd_h($ref).'<br>';
    }
  }

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = $actions;
  $result[] = $doc;
  $result[] = gipd_h($row->posting_date);
  $result[] = $production;
  $result[] = $location;
  $result[] = $reason;
  $result[] = number_format((float)$row->item_count, 0, ',', '.');
  $result[] = number_format((float)$row->total_qty, 5, ',', '.');
  $result[] = '<span class="badge bg-aqua">'.intval($row->trace_count).' trace</span><br><small>'.$customsRefs.'</small>';
  $result[] = gipd_status($row->status);
  $result[] = gipd_h($row->created_by);
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
