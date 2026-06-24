<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include "../../inc/config.php";
session_check_json();

function mdoc_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function mdoc_movement_label($moveCode, $refType, $direction) {
  $labels = array(
    '101' => 'GR Goods Receipt',
    '102' => 'GR Reversal',
    '103' => 'GR Blocked Stock',
    '104' => 'Blocked Reversal',
    '105' => 'Release Blocked',
    '122' => 'Return to Vendor',
    '501' => 'GR Without PO'
  );
  if (isset($labels[$moveCode])) return $labels[$moveCode];
  if ($refType !== '') return $refType;
  return $direction === 'OUT' ? 'Goods Issue' : 'Material Movement';
}

$columns = array(
  'dt.no_ref',
  'dt.posting_date',
  'dt.move_code',
  'dt.ref_type',
  'dt.kd_barang',
  'b.nm_barang',
  'ep.plant_code',
  'es.storage_code',
  'dt.qty',
  'dt.uom',
  'dt.amount',
  'COALESCE(NULLIF(dt.created_by,\'\'),NULLIF(dt.user,\'\'))'
);

$where = "";
$params = array();

if (!empty($_POST['tgl_awal']) && !empty($_POST['tgl_akhir'])) {
  $where .= " AND dt.posting_date BETWEEN ? AND ? ";
  $params[] = $_POST['tgl_awal'].' 00:00:00';
  $params[] = $_POST['tgl_akhir'].' 23:59:59';
}
if (!empty($_POST['move_code'])) {
  $where .= " AND dt.move_code=? ";
  $params[] = $_POST['move_code'];
}
if (!empty($_POST['direction'])) {
  if ($_POST['direction'] === 'IN') {
    $where .= " AND (dt.direction='IN' OR (dt.direction IS NULL AND dt.qty>=0)) ";
  } elseif ($_POST['direction'] === 'OUT') {
    $where .= " AND (dt.direction='OUT' OR (dt.direction IS NULL AND dt.qty<0)) ";
  }
}
if (!empty($_POST['plant_id'])) {
  $where .= " AND loc.plant_id=? ";
  $params[] = $_POST['plant_id'];
}
if (!empty($_POST['storage_location_id'])) {
  $where .= " AND loc.storage_location_id=? ";
  $params[] = $_POST['storage_location_id'];
}
if (!empty($_POST['user'])) {
  $where .= " AND COALESCE(NULLIF(dt.created_by,''),NULLIF(dt.user,''))=? ";
  $params[] = $_POST['user'];
}
if (!empty($_POST['keyword'])) {
  $keyword = '%'.trim($_POST['keyword']).'%';
  $where .= " AND (dt.no_ref LIKE ? OR dt.no_bpb LIKE ? OR dt.ref_pengganti LIKE ? OR dt.kd_barang LIKE ? OR b.nm_barang LIKE ? OR p.nopo LIKE ? OR po.purchase_order_no LIKE ? OR p.no_aju LIKE ? OR p.no_dokpab LIKE ? OR pemasok.nama LIKE ? OR dt.remark LIKE ? OR dt.reason LIKE ?) ";
  for ($i=0; $i<12; $i++) $params[] = $keyword;
}

$datatable->set_numbering_status(1);
$datatable->set_order_by("dt.posting_date");
$datatable->set_order_type("desc");

$query = $datatable->get_custom(
  "SELECT dt.*,
          b.nm_barang,
          p.no_bpb AS gr_no_bpb,
          p.nopo,
          p.no_aju AS header_no_aju,
          p.no_dokpab AS header_no_dokpab,
          COALESCE(pemasok.nama,p.pemasok,'') AS vendor_name,
          po.purchase_order_no,
          loc.plant_id,
          loc.storage_location_id,
          loc.storage_bin_id,
          ep.plant_code,
          es.storage_code,
          es.storage_name,
          eb.bin_code,
          eb.bin_name
   FROM detail_transaksi dt
   LEFT JOIN barang b ON b.kd_barang=dt.kd_barang
   LEFT JOIN pemasukan p ON p.no_bpb=COALESCE(NULLIF(dt.no_bpb,''),NULLIF(dt.no_ref,''),NULLIF(dt.ref_pengganti,''))
   LEFT JOIN pemasok ON pemasok.kode_pemasok=p.pemasok
   LEFT JOIN purchase_order po ON po.id=dt.id_po OR po.id=dt.ref_id
   LEFT JOIN (
     SELECT no_bpb,kode,MIN(plant_id) AS plant_id,MIN(storage_location_id) AS storage_location_id,MIN(storage_bin_id) AS storage_bin_id
     FROM stock_layer
     GROUP BY no_bpb,kode
   ) loc ON loc.no_bpb=COALESCE(NULLIF(dt.no_bpb,''),NULLIF(dt.no_ref,''),NULLIF(dt.ref_pengganti,'')) AND loc.kode=dt.kd_barang
   LEFT JOIN erp_plant ep ON ep.id=loc.plant_id
   LEFT JOIN erp_storage_location es ON es.id=loc.storage_location_id
   LEFT JOIN erp_storage_bin eb ON eb.id=loc.storage_bin_id
   WHERE 1=1 $where",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $value) {
  $direction = $value->direction ?: ((float)$value->qty < 0 ? 'OUT' : 'IN');
  $dirClass = $direction === 'OUT' ? 'mdoc-out' : ($direction === 'IN' ? 'mdoc-in' : 'mdoc-neutral');
  $docYear = $value->posting_date ? date('Y', strtotime($value->posting_date)) : '';
  $lineNo = $value->no_urut ? $value->no_urut : $value->id_detail;
  $source = mdoc_movement_label((string)$value->move_code, (string)$value->ref_type, (string)$direction);
  $doc = '<div class="mdoc-doc"><strong>'.mdoc_h($value->no_ref ?: $value->no_bpb).'</strong><br><small class="text-muted">Year '.$docYear.' | Item '.$lineNo.'</small></div>';
  $movement = '<span class="mdoc-badge '.$dirClass.'">'.mdoc_h($direction).'</span><br><small>MvT '.mdoc_h($value->move_code).'</small>';
  $material = '<strong>'.mdoc_h($value->kd_barang).'</strong><br><small class="text-muted">'.mdoc_h($value->nm_barang).'</small>';
  $location = trim((string)$value->storage_code.' - '.(string)$value->storage_name, ' -');
  $plant = $value->plant_code ? $value->plant_code : '-';
  $sourceText = '<strong>'.mdoc_h($source).'</strong><br><small class="text-muted">'.mdoc_h($value->purchase_order_no ?: $value->nopo ?: $value->ref_type).'</small>';
  $user = $value->created_by ?: $value->user;

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = '<div class="mdoc-action-buttons"><button type="button" class="btn btn-info btn-xs btn-detail-mdoc" data-id="'.intval($value->id_detail).'" title="Detail"><i class="fa fa-eye"></i></button></div>';
  $result[] = $doc;
  $result[] = mdoc_h($value->posting_date);
  $result[] = $movement;
  $result[] = $sourceText;
  $result[] = $material;
  $result[] = mdoc_h($plant);
  $result[] = mdoc_h($location ?: '-');
  $result[] = number_format((float)$value->qty, 5, ',', '.');
  $result[] = mdoc_h($value->uom);
  $result[] = number_format((float)$value->amount, 2, ',', '.');
  $result[] = mdoc_h($user);
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
