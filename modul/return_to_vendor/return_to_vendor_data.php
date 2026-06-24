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

$columns = array(
  'r.return_no',
  'r.posting_date',
  'r.vendor_code',
  'r.vendor_name',
  'r.source_no_bpb',
  'r.return_reason_code',
  'r.return_reason_text',
  'r.status',
  'r.created_by'
);

$datatable->set_numbering_status(1);
$datatable->set_order_by("r.created_at");
$datatable->set_order_type("desc");

$wh = "";
$params = array();
if (!empty($_POST['tgl_awal']) && !empty($_POST['tgl_akhir'])) {
  $wh .= " AND r.posting_date BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'];
  $params[] = $_POST['tgl_akhir'];
}
if (!empty($_POST['vendor'])) {
  $wh .= " AND r.vendor_code=? ";
  $params[] = $_POST['vendor'];
}
if (!empty($_POST['status'])) {
  $wh .= " AND r.status=? ";
  $params[] = $_POST['status'];
}
if (!empty($_POST['keyword'])) {
  $keyword = '%'.trim($_POST['keyword']).'%';
  $wh .= " AND (r.return_no LIKE ? OR r.source_no_bpb LIKE ? OR r.vendor_code LIKE ? OR r.vendor_name LIKE ? OR r.return_reason_text LIKE ?) ";
  for ($i=0; $i<5; $i++) $params[] = $keyword;
}

$query = $datatable->get_custom(
  "SELECT r.*,
          COALESCE(ds.item_count,0) AS item_count,
          COALESCE(ds.total_qty,0) AS total_qty
   FROM erp_vendor_return r
   LEFT JOIN (
     SELECT return_id,COUNT(*) AS item_count,SUM(qty) AS total_qty
     FROM erp_vendor_return_detail
     GROUP BY return_id
   ) ds ON ds.return_id=r.id
   WHERE 1=1 $wh",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $value) {
  $statusClass = $value->status === 'POSTED' ? 'success' : 'danger';
  $action = '<div class="rtv-action-buttons"><button type="button" class="btn btn-info btn-xs btn-detail-rtv" data-id="'.intval($value->id).'" title="'.wh_h(wh_t('common_detail', 'Detail')).'"><i class="fa fa-eye"></i></button></div>';
  $doc = '<strong>'.htmlspecialchars($value->return_no, ENT_QUOTES, 'UTF-8').'</strong><br><small class="text-muted">MvT '.htmlspecialchars($value->movement_type, ENT_QUOTES, 'UTF-8').'</small>';
  $vendor = '<strong>'.htmlspecialchars($value->vendor_code, ENT_QUOTES, 'UTF-8').'</strong><br><small class="text-muted">'.htmlspecialchars($value->vendor_name, ENT_QUOTES, 'UTF-8').'</small>';
  $reason = '<strong>'.htmlspecialchars($value->return_reason_code, ENT_QUOTES, 'UTF-8').'</strong><br><small class="text-muted">'.htmlspecialchars($value->return_reason_text, ENT_QUOTES, 'UTF-8').'</small>';

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = $action;
  $result[] = $doc;
  $result[] = htmlspecialchars($value->posting_date, ENT_QUOTES, 'UTF-8');
  $result[] = $vendor;
  $result[] = htmlspecialchars($value->source_no_bpb, ENT_QUOTES, 'UTF-8');
  $result[] = $reason;
  $result[] = number_format((float)$value->item_count, 0, ',', '.');
  $result[] = number_format((float)$value->total_qty, 5, ',', '.');
  $result[] = '<span class="label label-'.$statusClass.'">'.htmlspecialchars($value->status, ENT_QUOTES, 'UTF-8').'</span>';
  $result[] = htmlspecialchars($value->created_by, ENT_QUOTES, 'UTF-8');
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
