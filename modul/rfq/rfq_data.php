<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();

function rfq_data_t($key, $fallback = '') { return lang_text($key, $fallback); }
function rfq_data_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

$columns = array(
  'r.rfq_no',
  'r.rfq_date',
  'r.quotation_deadline',
  'r.subject',
  'r.plant',
  'r.currency',
  'r.status',
  'r.created_by'
);

$datatable->set_numbering_status(1);
$datatable->set_order_by("r.id");
$datatable->set_order_type("desc");

$wh = "";
$params = array();

if (isset($_POST['status']) && $_POST['status'] !== '') {
  $wh .= " AND r.status=? ";
  $params[] = $_POST['status'];
}
if (isset($_POST['tgl_awal']) && $_POST['tgl_awal'] !== '' && (!isset($_POST['tgl_akhir']) || $_POST['tgl_akhir'] === '')) {
  $wh .= " AND r.rfq_date BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'];
  $params[] = date('Y-m-d');
} else if (isset($_POST['tgl_awal'], $_POST['tgl_akhir']) && $_POST['tgl_awal'] !== '' && $_POST['tgl_akhir'] !== '') {
  $wh .= " AND r.rfq_date BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'];
  $params[] = $_POST['tgl_akhir'];
}
if (isset($_POST['vendor']) && $_POST['vendor'] !== '') {
  $wh .= " AND EXISTS (SELECT 1 FROM erp_rfq_vendor v WHERE v.rfq_id=r.id AND v.vendor_code=?) ";
  $params[] = $_POST['vendor'];
}
if (isset($_POST['reference']) && trim($_POST['reference']) !== '') {
  $keyword = '%'.trim($_POST['reference']).'%';
  $wh .= " AND (
    r.rfq_no LIKE ? OR r.subject LIKE ? OR r.note LIKE ?
    OR EXISTS (SELECT 1 FROM erp_rfq_item i WHERE i.rfq_id=r.id AND (i.material_code LIKE ? OR i.material_name LIKE ?))
    OR EXISTS (SELECT 1 FROM erp_rfq_vendor v WHERE v.rfq_id=r.id AND (v.vendor_code LIKE ? OR v.vendor_name LIKE ?))
  ) ";
  for ($i = 0; $i < 7; $i++) $params[] = $keyword;
}

$query = $datatable->get_custom(
  "SELECT r.*,ep.plant_name,
          COALESCE(ds.item_count,0) AS item_count,
          COALESCE(ds.total_qty,0) AS total_qty,
          COALESCE(vs.vendor_count,0) AS vendor_count,
          COALESCE(qs.quote_count,0) AS quote_count,
          qs.best_value
   FROM erp_rfq r
   LEFT JOIN erp_plant ep ON ep.plant_code=r.plant
   LEFT JOIN (
     SELECT rfq_id,COUNT(*) AS item_count,SUM(qty) AS total_qty
     FROM erp_rfq_item
     GROUP BY rfq_id
   ) ds ON ds.rfq_id=r.id
   LEFT JOIN (
     SELECT rfq_id,COUNT(*) AS vendor_count
     FROM erp_rfq_vendor
     GROUP BY rfq_id
   ) vs ON vs.rfq_id=r.id
   LEFT JOIN (
     SELECT rfq_id,COUNT(*) AS quote_count,
            MIN(price * qty * (1 - (discount_percent/100)) * (1 + (tax_percent/100))) AS best_value
     FROM erp_rfq_quotation
     GROUP BY rfq_id
   ) qs ON qs.rfq_id=r.id
   WHERE 1=1 $wh",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $value) {
  $statusClass = 'default';
  if ($value->status === 'SENT') $statusClass = 'warning';
  if ($value->status === 'QUOTED') $statusClass = 'info';
  if ($value->status === 'AWARDED') $statusClass = 'success';
  if ($value->status === 'CLOSED') $statusClass = 'primary';
  if ($value->status === 'CANCELLED') $statusClass = 'danger';

  $action = '<div class="rfq-action-buttons">
    <button type="button" class="btn btn-info btn-xs btn-detail-rfq" data-id="'.intval($value->id).'" title="'.rfq_data_h(rfq_data_t('common_detail','Detail')).'"><i class="fa fa-eye"></i></button>';
  if ($value->status === 'DRAFT') {
    $action .= ' <button type="button" class="btn btn-primary btn-xs btn-send-rfq" data-id="'.intval($value->id).'" data-no="'.rfq_data_h($value->rfq_no).'" title="'.rfq_data_h(rfq_data_t('rfq_send','Send RFQ')).'"><i class="fa fa-paper-plane"></i></button>';
  }
  if (!in_array($value->status, array('CLOSED','CANCELLED'))) {
    $action .= ' <button type="button" class="btn btn-success btn-xs btn-open-quote" data-id="'.intval($value->id).'" data-no="'.rfq_data_h($value->rfq_no).'" title="'.rfq_data_h(rfq_data_t('rfq_quote_form','Input Quotation')).'"><i class="fa fa-money"></i></button>
    <button type="button" class="btn btn-danger btn-xs btn-cancel-rfq" data-id="'.intval($value->id).'" data-no="'.rfq_data_h($value->rfq_no).'" title="'.rfq_data_h(rfq_data_t('rfq_cancel','Cancel')).'"><i class="fa fa-ban"></i></button>';
  }
  $action .= '</div>';

  $doc = '<strong>'.rfq_data_h($value->rfq_no).'</strong><br><small class="text-muted">'.rfq_data_h($value->subject).'</small>';
  $plant = rfq_data_h(trim($value->plant.' - '.$value->plant_name));
  $bestValue = $value->best_value !== null ? $value->currency.' '.number_format((float) $value->best_value, 2, ',', '.') : '-';

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = $action;
  $result[] = $doc;
  $result[] = rfq_data_h($value->rfq_date);
  $result[] = rfq_data_h($value->quotation_deadline);
  $result[] = $plant;
  $result[] = number_format((float) $value->item_count, 0, ',', '.');
  $result[] = number_format((float) $value->vendor_count, 0, ',', '.');
  $result[] = number_format((float) $value->quote_count, 0, ',', '.');
  $result[] = rfq_data_h($bestValue);
  $result[] = '<span class="label label-'.$statusClass.'">'.rfq_data_h($value->status).'</span>';
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
