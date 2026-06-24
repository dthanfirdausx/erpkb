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

function gih_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function gih_move_codes_sql() {
  return "'201','202','241','242','261','262','291','292','333','334','551','552','122','601'";
}

function gih_movement_label($moveCode, $refType) {
  $labels = array(
    '201' => 'Issue to Cost Center',
    '202' => 'Reversal Cost Center',
    '241' => 'Issue to Asset',
    '242' => 'Reversal Asset',
    '261' => 'Issue to Production',
    '262' => 'Reversal Production',
    '291' => 'Other Goods Issue',
    '292' => 'Reversal Other GI',
    '333' => 'Sample Issue',
    '334' => 'Reversal Sample',
    '551' => 'Scrap Issue',
    '552' => 'Reversal Scrap',
    '122' => 'Return to Vendor',
    '601' => 'GI to Delivery'
  );
  if (isset($labels[$moveCode])) return $labels[$moveCode];
  return $refType !== '' ? $refType : 'Goods Issue';
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

$where = " AND dt.move_code IN (".gih_move_codes_sql().") ";
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
  $where .= " AND COALESCE(loc.plant_id,slloc.plant_id)=? ";
  $params[] = $_POST['plant_id'];
}
if (!empty($_POST['storage_location_id'])) {
  $where .= " AND COALESCE(loc.storage_location_id,slloc.storage_location_id)=? ";
  $params[] = $_POST['storage_location_id'];
}
if (!empty($_POST['user'])) {
  $where .= " AND COALESCE(NULLIF(dt.created_by,''),NULLIF(dt.user,''))=? ";
  $params[] = $_POST['user'];
}
if (!empty($_POST['keyword'])) {
  $keyword = '%'.trim($_POST['keyword']).'%';
  $where .= " AND (
    dt.no_ref LIKE ? OR dt.no_bpb LIKE ? OR dt.ref_pengganti LIKE ? OR dt.kd_barang LIKE ? OR b.nm_barang LIKE ?
    OR dt.no_aju LIKE ? OR dt.no_dokpab LIKE ? OR dt.ref_type LIKE ? OR dt.remark LIKE ? OR dt.reason LIKE ?
    OR p.nopo LIKE ? OR po.purchase_order_no LIKE ? OR pemasok.nama LIKE ?
  ) ";
  for ($i=0; $i<13; $i++) $params[] = $keyword;
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
   LEFT JOIN pemasukan p ON p.no_bpb=COALESCE(NULLIF(dt.no_bpb,''),NULLIF(dt.ref_pengganti,''),NULLIF(dt.no_ref,''))
   LEFT JOIN pemasok ON pemasok.kode_pemasok=p.pemasok
   LEFT JOIN purchase_order po ON po.id=dt.id_po OR po.id=dt.id_po_detail OR po.id=dt.ref_id
   LEFT JOIN (
     SELECT no_bpb,kode,MIN(plant_id) AS plant_id,MIN(storage_location_id) AS storage_location_id,MIN(storage_bin_id) AS storage_bin_id
     FROM stock_layer
     GROUP BY no_bpb,kode
   ) slloc ON slloc.no_bpb=COALESCE(NULLIF(dt.no_bpb,''),NULLIF(dt.ref_pengganti,''),NULLIF(dt.no_ref,'')) AND slloc.kode=dt.kd_barang
   LEFT JOIN (
     SELECT material_doc_id,MIN(plant_id) AS plant_id,MIN(storage_location_id) AS storage_location_id,MIN(storage_bin_id) AS storage_bin_id
     FROM (
       SELECT material_doc_id,plant_id,storage_location_id,storage_bin_id FROM erp_issue_cost_center_trace
       UNION ALL SELECT material_doc_id,plant_id,storage_location_id,storage_bin_id FROM erp_issue_asset_trace
       UNION ALL SELECT material_doc_id,plant_id,storage_location_id,storage_bin_id FROM erp_scrap_issue_trace
       UNION ALL SELECT material_doc_id,plant_id,storage_location_id,storage_bin_id FROM erp_sample_issue_trace
       UNION ALL SELECT material_doc_id,plant_id,storage_location_id,storage_bin_id FROM erp_other_goods_issue_trace
       UNION ALL SELECT material_doc_id,plant_id,storage_location_id,storage_bin_id FROM erp_issue_production_trace
     ) alltrace
     GROUP BY material_doc_id
   ) loc ON loc.material_doc_id=dt.id_detail
   LEFT JOIN erp_plant ep ON ep.id=COALESCE(loc.plant_id,slloc.plant_id)
   LEFT JOIN erp_storage_location es ON es.id=COALESCE(loc.storage_location_id,slloc.storage_location_id)
   LEFT JOIN erp_storage_bin eb ON eb.id=COALESCE(loc.storage_bin_id,slloc.storage_bin_id)
   WHERE 1=1 $where",
  $columns,
  $params
);

$data = array();
$i = 1;
foreach ($query as $value) {
  $direction = $value->direction ?: ((float)$value->qty < 0 ? 'OUT' : 'IN');
  $dirClass = $direction === 'OUT' ? 'gih-out' : ($direction === 'IN' ? 'gih-in' : 'gih-neutral');
  $docYear = $value->posting_date ? date('Y', strtotime($value->posting_date)) : '';
  $lineNo = $value->no_urut ? $value->no_urut : $value->id_detail;
  $source = gih_movement_label((string)$value->move_code, (string)$value->ref_type);
  $doc = '<div class="gih-doc"><strong>'.gih_h($value->no_ref ?: $value->no_bpb).'</strong><br><small class="text-muted">Year '.$docYear.' | Item '.$lineNo.'</small></div>';
  $movement = '<span class="gih-badge '.$dirClass.'">'.gih_h($direction).'</span><br><small>MvT '.gih_h($value->move_code).'</small>';
  $material = '<strong>'.gih_h($value->kd_barang).'</strong><br><small class="text-muted">'.gih_h($value->nm_barang).'</small>';
  $location = trim((string)$value->storage_code.' - '.(string)$value->storage_name, ' -');
  $plant = $value->plant_code ? $value->plant_code : '-';
  $sourceText = '<strong>'.gih_h($source).'</strong><br><small class="text-muted">'.gih_h($value->purchase_order_no ?: $value->nopo ?: $value->ref_type ?: $value->reason).'</small>';
  $user = $value->created_by ?: $value->user;

  $result = array();
  $result[] = $datatable->number($i);
  $result[] = '<div class="gih-action-buttons"><button type="button" class="btn btn-info btn-xs btn-detail-gih" data-id="'.intval($value->id_detail).'" title="'.wh_h(wh_t('common_detail', 'Detail')).'"><i class="fa fa-eye"></i></button></div>';
  $result[] = $doc;
  $result[] = gih_h($value->posting_date);
  $result[] = $movement;
  $result[] = $sourceText;
  $result[] = $material;
  $result[] = gih_h($plant);
  $result[] = gih_h($location ?: '-');
  $result[] = number_format(abs((float)$value->qty), 5, ',', '.');
  $result[] = gih_h($value->uom);
  $result[] = number_format((float)$value->amount, 2, ',', '.');
  $result[] = gih_h($user);
  $data[] = $result;
  $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
