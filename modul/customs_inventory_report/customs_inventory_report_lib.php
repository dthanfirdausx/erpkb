<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
function cir_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cir_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}

function cir_valid_date($date, $default) {
  $date = trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
}

function cir_stock_type_label($stockType) {
  $labels = array('UNRESTRICTED'=>'Unrestricted','QUALITY'=>'Quality Inspection','BLOCKED'=>'Blocked');
  return isset($labels[$stockType]) ? $labels[$stockType] : $stockType;
}

function cir_doc_key($row) {
  return implode('|', array(
    (string)$row->jenis_dokpab,
    (string)$row->no_aju,
    (string)$row->no_dokpab,
    (string)$row->kode,
    (string)$row->plant_id,
    (string)$row->storage_location_id,
    (string)$row->storage_bin_id,
    (string)$row->stock_type
  ));
}

function cir_filter_sql($input, &$params) {
  $where = " WHERE 1=1 ";
  if (!empty($input['open_only']) && $input['open_only'] === 'Y') $where .= " AND sl.qty_sisa>0 ";
  if (!empty($input['material_code'])) { $where .= " AND sl.kode=? "; $params[] = $input['material_code']; }
  if (!empty($input['plant_id'])) { $where .= " AND sl.plant_id=? "; $params[] = (int)$input['plant_id']; }
  if (!empty($input['storage_location_id'])) { $where .= " AND sl.storage_location_id=? "; $params[] = (int)$input['storage_location_id']; }
  if (!empty($input['storage_bin_id'])) { $where .= " AND sl.storage_bin_id=? "; $params[] = (int)$input['storage_bin_id']; }
  if (!empty($input['stock_type'])) { $where .= " AND sl.stock_type=? "; $params[] = $input['stock_type']; }
  if (!empty($input['jenis_dokpab'])) { $where .= " AND COALESCE(sl.jenis_dokpab,'')=? "; $params[] = $input['jenis_dokpab']; }
  if (!empty($input['no_aju'])) { $where .= " AND sl.no_aju LIKE ? "; $params[] = '%'.$input['no_aju'].'%'; }
  if (!empty($input['no_dokpab'])) { $where .= " AND sl.no_dokpab LIKE ? "; $params[] = '%'.$input['no_dokpab'].'%'; }
  if (!empty($input['tgl_awal'])) { $where .= " AND COALESCE(sl.tgl_masuk,DATE(sl.created_at))>=? "; $params[] = cir_valid_date($input['tgl_awal'], date('Y-m-01')); }
  if (!empty($input['tgl_akhir'])) { $where .= " AND COALESCE(sl.tgl_masuk,DATE(sl.created_at))<=? "; $params[] = cir_valid_date($input['tgl_akhir'], date('Y-m-d')); }
  if (!empty($input['keyword'])) {
    $kw = '%'.trim($input['keyword']).'%';
    $where .= " AND (sl.kode LIKE ? OR b.nm_barang LIKE ? OR sl.no_bpb LIKE ? OR sl.no_aju LIKE ? OR sl.no_dokpab LIKE ? OR sl.jenis_dokpab LIKE ? OR sl.ref_table LIKE ?) ";
    for ($i=0; $i<7; $i++) $params[] = $kw;
  }
  return $where;
}

function cir_load_layers($db, $input) {
  $params = array();
  $where = cir_filter_sql($input, $params);
  return $db->query(
    "SELECT sl.*,b.nm_barang,b.satuan,b.kategori,
            ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
            COALESCE(sl.qty_masuk,0)-COALESCE(sl.qty_sisa,0) AS qty_used,
            DATEDIFF(CURDATE(),COALESCE(sl.tgl_masuk,DATE(sl.created_at))) AS aging_days
     FROM stock_layer sl
     LEFT JOIN barang b ON b.kd_barang=sl.kode
     LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
     LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
     $where
     ORDER BY COALESCE(sl.jenis_dokpab,''),sl.no_aju,sl.no_dokpab,sl.kode,COALESCE(sl.tgl_masuk,DATE(sl.created_at)),sl.id",
    $params
  );
}

function cir_group_layers($layers) {
  $groups = array();
  foreach ($layers as $row) {
    $key = cir_doc_key($row);
    if (!isset($groups[$key])) {
      $groups[$key] = (object)array(
        'jenis_dokpab'=>$row->jenis_dokpab,
        'no_aju'=>$row->no_aju,
        'no_dokpab'=>$row->no_dokpab,
        'material_code'=>$row->kode,
        'material_name'=>$row->nm_barang,
        'uom'=>$row->satuan,
        'plant_id'=>$row->plant_id,
        'plant_code'=>$row->plant_code,
        'storage_location_id'=>$row->storage_location_id,
        'storage_code'=>$row->storage_code,
        'storage_name'=>$row->storage_name,
        'storage_bin_id'=>$row->storage_bin_id,
        'bin_code'=>$row->bin_code,
        'stock_type'=>$row->stock_type,
        'layer_count'=>0,
        'oldest_date'=>$row->tgl_masuk ?: substr((string)$row->created_at,0,10),
        'max_age'=>0,
        'qty_masuk'=>0,
        'qty_used'=>0,
        'qty_sisa'=>0
      );
    }
    $g = $groups[$key];
    $g->layer_count++;
    $g->qty_masuk += (float)$row->qty_masuk;
    $g->qty_used += (float)$row->qty_used;
    $g->qty_sisa += (float)$row->qty_sisa;
    if ((int)$row->aging_days > (int)$g->max_age) $g->max_age = (int)$row->aging_days;
    $rowDate = $row->tgl_masuk ?: substr((string)$row->created_at,0,10);
    if ($rowDate && (!$g->oldest_date || $rowDate < $g->oldest_date)) $g->oldest_date = $rowDate;
  }
  return array_values($groups);
}
?>
