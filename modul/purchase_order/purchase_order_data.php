<?php
include "../../inc/config.php";

if (!function_exists('po_data_t')) {
  function po_data_t($key, $fallback = '')
  {
    return lang_text($key, $fallback);
  }
}

$columns = array(
  'h.purchase_order_no',
  'h.po_date',
  'h.seller_name',
  'h.delivery_term',
  'h.payment_term',
  'h.status',
  'h.approval_status'
);

$datatable->set_numbering_status(1);
$datatable->set_order_by("h.id");
$datatable->set_order_type("desc");

$wh = "";
$params = array();

if (!empty($_POST['tgl_awal']) && !empty($_POST['tgl_akhir'])) {
  $wh .= " AND h.po_date BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'];
  $params[] = $_POST['tgl_akhir'];
}
if (isset($_POST['supplier']) && $_POST['supplier'] !== '' && $_POST['supplier'] !== 'all') {
  $wh .= " AND h.seller_code = ? ";
  $params[] = $_POST['supplier'];
}
if (isset($_POST['status_po']) && $_POST['status_po'] !== '' && $_POST['status_po'] !== 'all') {
  $wh .= " AND COALESCE(v.status_po,h.status) = ? ";
  $params[] = $_POST['status_po'];
}
if (isset($_POST['approval_status']) && $_POST['approval_status'] !== '' && $_POST['approval_status'] !== 'all') {
  $wh .= " AND h.approval_status = ? ";
  $params[] = $_POST['approval_status'];
}

$query = $datatable->get_custom(
  "SELECT h.id,h.purchase_order_no,h.po_date,h.seller_code,h.seller_name,h.seller_address,
          h.delivery_term,h.payment_term,h.currency,h.status,h.approval_status,
          COALESCE(ds.total_po,0) AS total_po,
          COALESCE(v.total_gr,0) AS total_gr,
          COALESCE(v.status_po,h.status,'OPEN') AS status_po,
          COALESCE(ds.item_count,0) AS item_count,
          COALESCE(ds.total_amount,0) AS total_amount
   FROM purchase_order h
   LEFT JOIN (
     SELECT id_po,po_no,COUNT(*) AS item_count,SUM(qty) AS total_po,SUM(COALESCE(amount,qty*harga)) AS total_amount
     FROM purchase_order_detail
     GROUP BY id_po,po_no
   ) ds ON ds.id_po=h.id OR ds.po_no=h.purchase_order_no
   LEFT JOIN v_purchase_order v ON v.id=h.id
   WHERE 1=1 $wh
   ",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $value) {
  $statusPo = $value->status_po ?: 'OPEN';
  $statusClass = 'default';
  if ($statusPo === 'OPEN' || $statusPo === 'Draft') $statusClass = 'warning';
  if ($statusPo === 'PARTIAL') $statusClass = 'info';
  if ($statusPo === 'CLOSED' || $statusPo === 'Closed') $statusClass = 'success';
  if ($statusPo === 'CANCELLED') $statusClass = 'danger';

  $approvalClass = 'default';
  if ($value->approval_status === 'Pending') $approvalClass = 'warning';
  if ($value->approval_status === 'Approved') $approvalClass = 'success';
  if ($value->approval_status === 'Rejected') $approvalClass = 'danger';

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = '<a href="#" class="po-detail-link" data-id="'.(int)$value->id.'" title="'.htmlspecialchars(po_data_t('purchase_order_view_detail','View PO detail'),ENT_QUOTES,'UTF-8').'"><strong>'.htmlspecialchars($value->purchase_order_no,ENT_QUOTES,'UTF-8').'</strong></a><br><small class="text-muted">'.htmlspecialchars($value->currency,ENT_QUOTES,'UTF-8').' '.erp_format_number((float)$value->total_amount,2).'</small>';
  $result[] = htmlspecialchars($value->po_date,ENT_QUOTES,'UTF-8');
  $result[] = '<strong>'.htmlspecialchars($value->seller_name,ENT_QUOTES,'UTF-8').'</strong><br><small class="text-muted">'.htmlspecialchars($value->seller_code,ENT_QUOTES,'UTF-8').'</small>';
  $result[] = htmlspecialchars((string)$value->delivery_term,ENT_QUOTES,'UTF-8');
  $result[] = htmlspecialchars((string)$value->payment_term,ENT_QUOTES,'UTF-8');
  $result[] = erp_format_number((float)$value->item_count,0);
  $result[] = erp_format_qty((float)$value->total_gr,2).' / '.erp_format_qty((float)$value->total_po,2);
  $result[] = '<span class="label label-'.$statusClass.'">'.htmlspecialchars($statusPo,ENT_QUOTES,'UTF-8').'</span>';
  $result[] = '<span class="label label-'.$approvalClass.'">'.htmlspecialchars($value->approval_status ?: 'Pending',ENT_QUOTES,'UTF-8').'</span>';
  $result[] = $value->id;
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
