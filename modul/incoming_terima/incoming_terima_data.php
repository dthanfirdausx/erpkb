<?php
include "../../inc/config.php";

$columns = array(
  't.no_transfer',
  't.no_terima',
  't.tgl_transfer',
  't.no_ro',
  'bd.nm_bagian',
  'bk.nm_bagian',
  'dp.nm_dept',
  't.user',
  't.tgl_terima',
  't.user_terima',
  't.status',
  't.ket'
);

$datatable->set_numbering_status(1);
$datatable->set_order_by("t.tgl_transfer");
$datatable->set_order_type("desc");

$wh = " AND t.ke='1' ";
$params = array();

if (isset($_POST['status']) && $_POST['status'] !== '') {
  $wh .= " AND COALESCE(t.status,'0')=? ";
  $params[] = $_POST['status'];
}
if (!empty($_POST['tgl_awal']) && !empty($_POST['tgl_akhir'])) {
  $wh .= " AND DATE(t.tgl_transfer) BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'];
  $params[] = $_POST['tgl_akhir'];
}
if (!empty($_POST['source'])) {
  $keyword = '%'.trim($_POST['source']).'%';
  $wh .= " AND (t.no_transfer LIKE ? OR t.no_terima LIKE ? OR t.no_ro LIKE ? OR bd.nm_bagian LIKE ? OR dp.nm_dept LIKE ? OR t.ket LIKE ?) ";
  for ($i=0; $i<6; $i++) $params[] = $keyword;
}
if (!empty($_POST['plant'])) {
  $wh .= " AND EXISTS (
    SELECT 1
    FROM transfer_detail td_plant
    JOIN barang b_plant ON b_plant.id=td_plant.id_barang
    JOIN erp_plant ep_plant ON ep_plant.id=b_plant.plant_id
    WHERE td_plant.id_transfer=t.id_transfer
      AND ep_plant.plant_code=?
  ) ";
  $params[] = $_POST['plant'];
}

$query = $datatable->get_custom(
  "SELECT t.*,
          bd.nm_bagian AS source_name,
          bk.nm_bagian AS destination_name,
          dp.nm_dept,
          COALESCE(ds.item_count,0) AS item_count,
          COALESCE(ds.total_qty,0) AS total_qty
   FROM transfer t
   LEFT JOIN bagian bd ON bd.id_bagian=t.dari
   LEFT JOIN bagian bk ON bk.id_bagian=t.ke
   LEFT JOIN dept dp ON dp.kd_dept=t.kd_dept
   LEFT JOIN (
     SELECT id_transfer,COUNT(DISTINCT id_barang) AS item_count,SUM(jml) AS total_qty
     FROM transfer_detail
     GROUP BY id_transfer
   ) ds ON ds.id_transfer=t.id_transfer
   WHERE 1=1 $wh",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $value) {
  $posted = (string)$value->status === '1';
  $statusLabel = $posted ? '<span class="label label-success">POSTED</span>' : '<span class="label label-warning">OUTSTANDING</span>';
  $action = '<div class="grprod-action-buttons">
    <button type="button" class="btn btn-info btn-xs btn-detail-grprod" data-id="'.intval($value->id_transfer).'" title="Detail"><i class="fa fa-eye"></i></button>';
  if (!$posted) {
    $action .= ' <button type="button" class="btn btn-success btn-xs btn-receive-grprod" data-id="'.intval($value->id_transfer).'" data-no="'.htmlspecialchars($value->no_transfer, ENT_QUOTES, 'UTF-8').'" title="Post GR"><i class="fa fa-check"></i></button>';
  }
  $action .= '</div>';

  $doc = '<strong>'.htmlspecialchars($value->no_transfer, ENT_QUOTES, 'UTF-8').'</strong>';
  if ($value->no_terima) $doc .= '<br><small class="text-muted">Mat. Doc: '.htmlspecialchars($value->no_terima, ENT_QUOTES, 'UTF-8').'</small>';
  $source = htmlspecialchars($value->source_name ?: $value->dari, ENT_QUOTES, 'UTF-8');
  $dest = htmlspecialchars($value->destination_name ?: $value->ke, ENT_QUOTES, 'UTF-8');
  $prodRef = htmlspecialchars($value->no_ro ?: '-', ENT_QUOTES, 'UTF-8').'<br><small class="text-muted">'.htmlspecialchars($value->nm_dept ?: '-', ENT_QUOTES, 'UTF-8').'</small>';

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = $action;
  $result[] = $doc;
  $result[] = htmlspecialchars($value->tgl_transfer, ENT_QUOTES, 'UTF-8');
  $result[] = $prodRef;
  $result[] = $source;
  $result[] = $dest;
  $result[] = number_format((float)$value->item_count, 0, ',', '.');
  $result[] = number_format((float)$value->total_qty, 5, ',', '.');
  $result[] = htmlspecialchars($value->user_terima ?: '-', ENT_QUOTES, 'UTF-8');
  $result[] = htmlspecialchars($value->tgl_terima ?: '-', ENT_QUOTES, 'UTF-8');
  $result[] = $statusLabel;
  $result[] = $value->id_transfer;
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
