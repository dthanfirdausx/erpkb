<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();

function tp_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function tp_status_badge($status) {
  if ($status === '1') return '<span class="tp-badge tp-ok">Received</span>';
  if ($status === '9') return '<span class="tp-badge tp-rev">Reversed</span>';
  return '<span class="tp-badge tp-open">Open</span>';
}

$columns = array(
  't.no_transfer',
  't.tgl_transfer',
  'src.nm_bagian',
  'dsl.storage_code',
  't.no_ro',
  't.user',
  't.ket',
  't.status'
);

$where = " AND t.dari='1' ";
$params = array();
if (!empty($_POST['tgl_awal']) && !empty($_POST['tgl_akhir'])) {
  $where .= " AND DATE(t.tgl_transfer) BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'];
  $params[] = $_POST['tgl_akhir'];
}
if (!empty($_POST['destination_storage_location_id'])) {
  $where .= " AND t.destination_storage_location_id=? ";
  $params[] = $_POST['destination_storage_location_id'];
} elseif (!empty($_POST['tujuan'])) {
  $where .= " AND t.ke=? ";
  $params[] = $_POST['tujuan'];
}
if (isset($_POST['status']) && $_POST['status'] !== '') {
  $where .= " AND t.status=? ";
  $params[] = $_POST['status'];
}
if (!empty($_POST['keyword'])) {
  $keyword = '%'.trim($_POST['keyword']).'%';
  $where .= " AND (t.no_transfer LIKE ? OR t.no_ro LIKE ? OR t.user LIKE ? OR t.ket LIKE ? OR EXISTS (SELECT 1 FROM transfer_detail td JOIN barang b ON b.id=td.id_barang WHERE td.id_transfer=t.id_transfer AND (b.kd_barang LIKE ? OR b.nm_barang LIKE ?))) ";
  for ($i=0; $i<6; $i++) $params[] = $keyword;
}

$datatable->set_numbering_status(1);
$datatable->set_order_by("t.date_created");
$datatable->set_order_type("desc");

$query = $datatable->get_custom(
  "SELECT t.*,src.nm_bagian AS source_name,dst.nm_bagian AS destination_name,
          dsl.storage_code AS destination_storage_code,
          dsl.storage_name AS destination_storage_name,
          dsb.bin_code AS destination_bin_code,
          dsb.bin_name AS destination_bin_name,
          COALESCE(ds.item_count,0) AS item_count,
          COALESCE(ds.total_qty,0) AS total_qty
   FROM transfer t
   LEFT JOIN bagian src ON src.id_bagian=t.dari
   LEFT JOIN bagian dst ON dst.id_bagian=t.ke
   LEFT JOIN erp_storage_location dsl ON dsl.id=t.destination_storage_location_id
   LEFT JOIN erp_storage_bin dsb ON dsb.id=t.destination_storage_bin_id
   LEFT JOIN (
     SELECT id_transfer,COUNT(DISTINCT no) AS item_count,SUM(jml) AS total_qty
     FROM transfer_detail
     GROUP BY id_transfer
   ) ds ON ds.id_transfer=t.id_transfer
   WHERE 1=1 $where",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $value) {
  $actions = '<div class="tp-action-buttons"><button type="button" class="btn btn-info btn-xs" onclick="show_detail(\''.tp_h($value->no_transfer).'\')" title="Detail"><i class="fa fa-eye"></i></button>';
  if ($value->status !== '9') {
    $actions .= ' <button type="button" class="btn btn-warning btn-xs" onclick="reversal(\''.tp_h($value->no_transfer).'\')" title="Reversal 312"><i class="fa fa-undo"></i></button>';
  }
  $actions .= '</div>';

  $doc = '<strong>'.tp_h($value->no_transfer).'</strong><br><small class="text-muted">Created '.tp_h($value->date_created).'</small>';
  $movement = $value->status === '9' ? '<strong>312</strong><br><small>Transfer Reversal</small>' : '<strong>311</strong><br><small>Transfer Posting</small>';
  $reference = '<strong>'.tp_h($value->no_ro).'</strong><br><small class="text-muted">'.tp_h($value->tgl_ro).'</small>';
  $destinationLabel = trim($value->destination_storage_code.' - '.$value->destination_storage_name.' / '.$value->destination_bin_code.' - '.$value->destination_bin_name, ' -/');
  if ($destinationLabel === '') $destinationLabel = $value->destination_name;

  $row = array();
  $row[] = $datatable->number($i);
  $row[] = $actions;
  $row[] = $doc;
  $row[] = tp_h($value->tgl_transfer);
  $row[] = $movement;
  $row[] = tp_h($value->source_name ?: 'Gudang');
  $row[] = tp_h($destinationLabel);
  $row[] = $reference;
  $row[] = number_format((float)$value->item_count, 0, ',', '.');
  $row[] = number_format((float)$value->total_qty, 5, ',', '.');
  $row[] = tp_status_badge((string)$value->status);
  $row[] = tp_h($value->user);
  $data[] = $row;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
