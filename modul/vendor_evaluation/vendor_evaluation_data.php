<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();

function ve_data_t($key, $fallback = '')
{
  return lang_text($key, $fallback);
}

function ve_data_h($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$columns = array(
  'e.evaluation_no',
  'e.period_from',
  'e.period_to',
  'e.vendor_code',
  'e.vendor_name',
  'e.po_count',
  'e.gr_count',
  'e.on_time_delivery_pct',
  'e.quality_score',
  'e.total_score',
  'e.rating',
  'e.status',
  'e.evaluator'
);

$datatable->set_numbering_status(1);
$datatable->set_order_by("e.created_at");
$datatable->set_order_type("desc");

$wh = "";
$params = array();

if (!empty($_POST['period_from']) && !empty($_POST['period_to'])) {
  $wh .= " AND e.period_from>=? AND e.period_to<=? ";
  $params[] = $_POST['period_from'];
  $params[] = $_POST['period_to'];
}
if (!empty($_POST['vendor'])) {
  $wh .= " AND e.vendor_code=? ";
  $params[] = $_POST['vendor'];
}
if (!empty($_POST['status'])) {
  $wh .= " AND e.status=? ";
  $params[] = $_POST['status'];
}
if (!empty($_POST['rating'])) {
  $wh .= " AND e.rating=? ";
  $params[] = $_POST['rating'];
}
if (!empty($_POST['keyword'])) {
  $keyword = '%'.trim($_POST['keyword']).'%';
  $wh .= " AND (e.evaluation_no LIKE ? OR e.vendor_code LIKE ? OR e.vendor_name LIKE ? OR e.evaluator LIKE ?) ";
  $params[] = $keyword;
  $params[] = $keyword;
  $params[] = $keyword;
  $params[] = $keyword;
}

$query = $datatable->get_custom(
  "SELECT e.*
   FROM erp_vendor_evaluation e
   WHERE 1=1 $wh",
  $columns,
  $params
);

function ve_rating_label_data($rating)
{
  $class = $rating === 'A' ? 'success' : ($rating === 'B' ? 'primary' : ($rating === 'C' ? 'warning' : 'danger'));
  return '<span class="label label-'.$class.'">'.ve_data_h($rating).'</span>';
}

function ve_status_label_data($status)
{
  $class = $status === 'FINALIZED' ? 'success' : ($status === 'CANCELLED' ? 'danger' : 'warning');
  return '<span class="label label-'.$class.'">'.ve_data_h($status).'</span>';
}

$data = array();
$i = 1;
foreach ($query as $value) {
  $action = '<div class="ve-action-buttons">
    <button type="button" class="btn btn-info btn-xs btn-detail-ve" data-id="'.intval($value->id).'" title="'.ve_data_h(ve_data_t('common_detail', 'Detail')).'"><i class="fa fa-eye"></i></button>';
  if ($value->status === 'DRAFT') {
    $action .= ' <button type="button" class="btn btn-primary btn-xs btn-score-ve" data-id="'.intval($value->id).'" title="'.ve_data_h(ve_data_t('vendor_evaluation_manual_score', 'Manual Score')).'"><i class="fa fa-sliders"></i></button>
      <button type="button" class="btn btn-success btn-xs btn-finalize-ve" data-id="'.intval($value->id).'" data-no="'.ve_data_h($value->evaluation_no).'" title="'.ve_data_h(ve_data_t('vendor_evaluation_finalize', 'Finalize')).'"><i class="fa fa-lock"></i></button>
      <button type="button" class="btn btn-danger btn-xs btn-cancel-ve" data-id="'.intval($value->id).'" data-no="'.ve_data_h($value->evaluation_no).'" title="'.ve_data_h(ve_data_t('vendor_evaluation_cancel', 'Cancel')).'"><i class="fa fa-ban"></i></button>';
  }
  $action .= '</div>';

  $evaluation = '<strong>'.ve_data_h($value->evaluation_no).'</strong><br><small class="text-muted">'.ve_data_h($value->purchasing_org ?: ve_data_t('vendor_evaluation_all_org', 'All Org')).' / '.ve_data_h($value->plant ?: ve_data_t('vendor_evaluation_all_plant', 'All Plant')).'</small>';
  $period = ve_data_h($value->period_from.' s/d '.$value->period_to);
  $vendor = '<strong>'.ve_data_h($value->vendor_code).'</strong><br><small class="text-muted">'.ve_data_h($value->vendor_name).'</small>';

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = $action;
  $result[] = $evaluation;
  $result[] = $period;
  $result[] = $vendor;
  $result[] = number_format((float)$value->po_count, 0, ',', '.');
  $result[] = number_format((float)$value->gr_count, 0, ',', '.');
  $result[] = number_format((float)$value->on_time_delivery_pct, 2, ',', '.').'%';
  $result[] = number_format((float)$value->quality_score, 2, ',', '.');
  $result[] = '<strong>'.number_format((float)$value->total_score, 2, ',', '.').'</strong>';
  $result[] = ve_rating_label_data($value->rating);
  $result[] = ve_status_label_data($value->status);
  $result[] = ve_data_h($value->evaluator);
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
