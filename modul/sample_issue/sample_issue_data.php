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

function smpd_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function smpd_status($status) {
  if ($status === 'POSTED') return '<span class="label label-success">POSTED</span>';
  if ($status === 'REVERSED') return '<span class="label label-danger">REVERSED</span>';
  return '<span class="label label-default">'.smpd_h($status).'</span>';
}

$columns = array(
  'h.issue_no',
  'h.posting_date',
  'h.reason_code',
  'h.reason_text',
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
if (!empty($_POST['reason_code'])) {
  $where .= " AND h.reason_code=? ";
  $params[] = $_POST['reason_code'];
}
if (!empty($_POST['keyword'])) {
  $keyword = '%'.trim($_POST['keyword']).'%';
  $where .= " AND (
    h.issue_no LIKE ? OR h.reference_no LIKE ? OR h.reason_code LIKE ? OR h.reason_text LIKE ? OR h.sample_type LIKE ?
    OR h.recipient_type LIKE ? OR h.recipient_name LIKE ?
    OR EXISTS (SELECT 1 FROM erp_sample_issue_detail d WHERE d.issue_id=h.id AND (d.material_code LIKE ? OR d.material_name LIKE ?))
    OR EXISTS (SELECT 1 FROM erp_sample_issue_trace t WHERE t.issue_id=h.id AND (t.no_aju LIKE ? OR t.no_dokpab LIKE ? OR t.no_bpb LIKE ? OR t.lot_no LIKE ?))
  ) ";
  for ($i=0; $i<13; $i++) $params[] = $keyword;
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
   FROM erp_sample_issue h
   LEFT JOIN erp_plant ep ON ep.id=h.plant_id
   LEFT JOIN erp_storage_location es ON es.id=h.storage_location_id
   LEFT JOIN erp_storage_bin eb ON eb.id=h.storage_bin_id
   LEFT JOIN (
     SELECT issue_id,COUNT(*) AS item_count,SUM(qty) AS total_qty,SUM(amount) AS total_amount
     FROM erp_sample_issue_detail
     GROUP BY issue_id
   ) ds ON ds.issue_id=h.id
   LEFT JOIN (
     SELECT issue_id,COUNT(*) AS trace_count,
            GROUP_CONCAT(DISTINCT CONCAT(COALESCE(no_aju,''),' / ',COALESCE(no_dokpab,''),' / ',COALESCE(no_bpb,'')) ORDER BY no_aju,no_dokpab,no_bpb SEPARATOR '<br>') AS customs_refs
     FROM erp_sample_issue_trace
     GROUP BY issue_id
   ) ts ON ts.issue_id=h.id
   WHERE 1=1 $where",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $row) {
  $actions = '<div class="smp-action-buttons">';
  $actions .= '<button type="button" class="btn btn-primary btn-xs btn-detail-smp" data-id="'.intval($row->id).'" title="'.wh_h(wh_t('warehouse_detail_trace', 'Detail Trace')).'"><i class="fa fa-plus"></i> <span class="badge">'.number_format((float)$row->item_count, 0, ',', '.').'</span></button>';
  if ($row->status === 'POSTED') {
    $actions .= ' <button type="button" class="btn btn-warning btn-xs btn-reversal-smp" data-id="'.intval($row->id).'" data-no="'.smpd_h($row->issue_no).'" title="'.wh_h(wh_t('warehouse_reversal', 'Reversal')).' 334"><i class="fa fa-undo"></i></button>';
  }
  $actions .= '</div>';

  $doc = '<strong>'.smpd_h($row->issue_no).'</strong><br><small class="text-muted">MvT '.smpd_h($row->movement_type).'</small>';
  $sample = '<strong>'.smpd_h($row->sample_type ?: '-').'</strong><br><small class="text-muted">'.smpd_h(trim($row->recipient_type.' '.$row->recipient_name) ?: '-').'</small>';
  $location = smpd_h(trim($row->plant_code.' / '.$row->storage_code.' / '.$row->bin_code, ' /'));
  $reason = '<strong>'.smpd_h($row->reason_code).'</strong><br><small class="text-muted">'.smpd_h($row->reason_text).'</small>';
  $customsRefs = '';
  if (!empty($row->customs_refs)) {
    foreach (explode('<br>', $row->customs_refs) as $ref) {
      if (trim($ref) !== '') $customsRefs .= smpd_h($ref).'<br>';
    }
  }

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = $actions;
  $result[] = $doc;
  $result[] = smpd_h($row->posting_date);
  $result[] = $sample;
  $result[] = $location;
  $result[] = $reason;
  $result[] = number_format((float)$row->item_count, 0, ',', '.');
  $result[] = number_format((float)$row->total_qty, 5, ',', '.');
  $result[] = number_format((float)$row->total_amount, 2, ',', '.');
  $result[] = '<span class="badge bg-aqua">'.intval($row->trace_count).' trace</span><br><small>'.$customsRefs.'</small>';
  $result[] = smpd_status($row->status);
  $result[] = smpd_h($row->created_by);
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
