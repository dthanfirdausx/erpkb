<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
function blt_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function blt_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}

function blt_valid_date($date, $default) {
  $date = trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
}

function blt_stock_type_label($stockType) {
  $labels = array('UNRESTRICTED'=>'Unrestricted','QUALITY'=>'Quality Inspection','BLOCKED'=>'Blocked');
  return isset($labels[$stockType]) ? $labels[$stockType] : $stockType;
}

function blt_filter_sql($input, &$params) {
  $where = " WHERE 1=1 ";
  if (!empty($input['open_only']) && $input['open_only'] === 'Y') $where .= " AND sl.qty_sisa>0 ";
  if (!empty($input['material_code'])) { $where .= " AND sl.kode=? "; $params[] = $input['material_code']; }
  if (!empty($input['plant_id'])) { $where .= " AND sl.plant_id=? "; $params[] = (int)$input['plant_id']; }
  if (!empty($input['storage_location_id'])) { $where .= " AND sl.storage_location_id=? "; $params[] = (int)$input['storage_location_id']; }
  if (!empty($input['storage_bin_id'])) { $where .= " AND sl.storage_bin_id=? "; $params[] = (int)$input['storage_bin_id']; }
  if (!empty($input['stock_type'])) { $where .= " AND sl.stock_type=? "; $params[] = $input['stock_type']; }
  if (!empty($input['jenis_dokpab'])) { $where .= " AND sl.jenis_dokpab=? "; $params[] = $input['jenis_dokpab']; }
  if (!empty($input['tgl_awal'])) { $where .= " AND COALESCE(sl.tgl_masuk,DATE(sl.created_at))>=? "; $params[] = blt_valid_date($input['tgl_awal'], date('Y-m-01')); }
  if (!empty($input['tgl_akhir'])) { $where .= " AND COALESCE(sl.tgl_masuk,DATE(sl.created_at))<=? "; $params[] = blt_valid_date($input['tgl_akhir'], date('Y-m-d')); }
  if (!empty($input['keyword'])) {
    $kw = '%'.trim($input['keyword']).'%';
    $where .= " AND (sl.kode LIKE ? OR b.nm_barang LIKE ? OR sl.no_bpb LIKE ? OR sl.no_aju LIKE ? OR sl.no_dokpab LIKE ? OR sl.jenis_dokpab LIKE ? OR sl.ref_table LIKE ?) ";
    for ($i=0; $i<7; $i++) $params[] = $kw;
  }
  return $where;
}

function blt_load_layers($db, $input) {
  $params = array();
  $where = blt_filter_sql($input, $params);
  return $db->query(
    "SELECT sl.*,b.nm_barang,b.satuan,b.kategori,
            ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
            COALESCE(sl.qty_masuk,0)-COALESCE(sl.qty_sisa,0) AS qty_used,
            DATEDIFF(CURDATE(),COALESCE(sl.tgl_masuk,DATE(sl.created_at))) AS aging_days,
            (SELECT COUNT(*) FROM erp_gr_production_trace gt WHERE gt.output_stock_layer_id=sl.id) AS source_trace_count,
            (SELECT COUNT(*) FROM erp_issue_production_trace it WHERE it.stock_layer_id=sl.id) +
            (SELECT COUNT(*) FROM erp_issue_cost_center_trace ct WHERE ct.stock_layer_id=sl.id) +
            (SELECT COUNT(*) FROM erp_issue_asset_trace at WHERE at.stock_layer_id=sl.id) +
            (SELECT COUNT(*) FROM erp_scrap_issue_trace st WHERE st.stock_layer_id=sl.id) +
            (SELECT COUNT(*) FROM erp_sample_issue_trace sat WHERE sat.stock_layer_id=sl.id) +
            (SELECT COUNT(*) FROM erp_other_goods_issue_trace ot WHERE ot.stock_layer_id=sl.id) AS usage_trace_count
     FROM stock_layer sl
     LEFT JOIN barang b ON b.kd_barang=sl.kode
     LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
     LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
     $where
     ORDER BY COALESCE(sl.tgl_masuk,DATE(sl.created_at)) DESC,sl.id DESC",
    $params
  );
}

function blt_layer($db, $id) {
  return $db->fetch(
    "SELECT sl.*,b.nm_barang,b.satuan,ep.plant_code,es.storage_code,eb.bin_code,
            COALESCE(sl.qty_masuk,0)-COALESCE(sl.qty_sisa,0) AS qty_used
     FROM stock_layer sl
     LEFT JOIN barang b ON b.kd_barang=sl.kode
     LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
     LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
     WHERE sl.id=?",
    array($id)
  );
}
?>
