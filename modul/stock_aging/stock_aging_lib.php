<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
function saging_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function saging_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}

function saging_valid_date($date, $default) {
  $date = trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
}

function saging_buckets() {
  return array(
    '0_30' => array('label' => '0-30 Days', 'min' => 0, 'max' => 30),
    '31_60' => array('label' => '31-60 Days', 'min' => 31, 'max' => 60),
    '61_90' => array('label' => '61-90 Days', 'min' => 61, 'max' => 90),
    '91_180' => array('label' => '91-180 Days', 'min' => 91, 'max' => 180),
    '181_365' => array('label' => '181-365 Days', 'min' => 181, 'max' => 365),
    '365_plus' => array('label' => '>365 Days', 'min' => 366, 'max' => null)
  );
}

function saging_bucket_for_days($days) {
  foreach (saging_buckets() as $key => $bucket) {
    if ($days >= $bucket['min'] && ($bucket['max'] === null || $days <= $bucket['max'])) return $key;
  }
  return '365_plus';
}

function saging_bucket_label($key) {
  $buckets = saging_buckets();
  return isset($buckets[$key]) ? $buckets[$key]['label'] : 'All Buckets';
}

function saging_stock_type_label($stockType) {
  $labels = array('UNRESTRICTED' => 'Unrestricted', 'QUALITY' => 'Quality Inspection', 'BLOCKED' => 'Blocked');
  return isset($labels[$stockType]) ? $labels[$stockType] : $stockType;
}

function saging_layer_where($input, &$params) {
  $where = " WHERE sl.qty_sisa > 0 ";
  $asOf = saging_valid_date(isset($input['as_of_date']) ? $input['as_of_date'] : '', date('Y-m-d'));
  $where .= " AND COALESCE(sl.tgl_masuk,DATE(sl.created_at)) <= ? ";
  $params[] = $asOf;
  if (!empty($input['material_code'])) { $where .= " AND sl.kode=? "; $params[] = $input['material_code']; }
  if (!empty($input['plant_id'])) { $where .= " AND sl.plant_id=? "; $params[] = (int)$input['plant_id']; }
  if (!empty($input['storage_location_id'])) { $where .= " AND sl.storage_location_id=? "; $params[] = (int)$input['storage_location_id']; }
  if (!empty($input['storage_bin_id'])) { $where .= " AND sl.storage_bin_id=? "; $params[] = (int)$input['storage_bin_id']; }
  if (!empty($input['stock_type'])) { $where .= " AND sl.stock_type=? "; $params[] = $input['stock_type']; }
  if (!empty($input['keyword'])) {
    $kw = '%'.trim($input['keyword']).'%';
    $where .= " AND (sl.kode LIKE ? OR b.nm_barang LIKE ? OR sl.no_bpb LIKE ? OR sl.no_aju LIKE ? OR sl.no_dokpab LIKE ? OR sl.jenis_dokpab LIKE ?) ";
    for ($i=0; $i<6; $i++) $params[] = $kw;
  }
  return $where;
}

function saging_load_layers($db, $input) {
  $params = array();
  $where = saging_layer_where($input, $params);
  $asOf = saging_valid_date(isset($input['as_of_date']) ? $input['as_of_date'] : '', date('Y-m-d'));
  $rows = $db->query(
    "SELECT sl.*,b.nm_barang,b.satuan,b.kategori,b.material_type_id,b.material_group_id,
            ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
            DATEDIFF(?,COALESCE(sl.tgl_masuk,DATE(sl.created_at))) AS aging_days
     FROM stock_layer sl
     LEFT JOIN barang b ON b.kd_barang=sl.kode
     LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
     LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
     $where
     ORDER BY sl.kode,sl.plant_id,sl.storage_location_id,sl.storage_bin_id,sl.stock_type,COALESCE(sl.tgl_masuk,DATE(sl.created_at)),sl.id",
    array_merge(array($asOf), $params)
  );
  $bucketFilter = isset($input['aging_bucket']) ? trim((string)$input['aging_bucket']) : '';
  $result = array();
  foreach ($rows as $row) {
    $row->aging_days = max(0, (int)$row->aging_days);
    $row->aging_bucket = saging_bucket_for_days($row->aging_days);
    if ($bucketFilter !== '' && $bucketFilter !== $row->aging_bucket) continue;
    $result[] = $row;
  }
  return $result;
}

function saging_group_key($row) {
  return implode('|', array((string)$row->kode,(string)$row->plant_id,(string)$row->storage_location_id,(string)$row->storage_bin_id,(string)$row->stock_type));
}

function saging_group_layers($layers) {
  $groups = array();
  foreach ($layers as $row) {
    $key = saging_group_key($row);
    if (!isset($groups[$key])) {
      $groups[$key] = (object)array(
        'material_code' => $row->kode,
        'material_name' => $row->nm_barang,
        'uom' => $row->satuan,
        'plant_id' => $row->plant_id,
        'plant_code' => $row->plant_code,
        'storage_location_id' => $row->storage_location_id,
        'storage_code' => $row->storage_code,
        'storage_name' => $row->storage_name,
        'storage_bin_id' => $row->storage_bin_id,
        'bin_code' => $row->bin_code,
        'stock_type' => $row->stock_type,
        'layer_count' => 0,
        'oldest_date' => $row->tgl_masuk ?: substr((string)$row->created_at, 0, 10),
        'max_age' => 0,
        'total_qty' => 0,
        'bucket_qty' => array('0_30'=>0,'31_60'=>0,'61_90'=>0,'91_180'=>0,'181_365'=>0,'365_plus'=>0)
      );
    }
    $g = $groups[$key];
    $g->layer_count++;
    $g->total_qty += (float)$row->qty_sisa;
    $g->bucket_qty[$row->aging_bucket] += (float)$row->qty_sisa;
    if ((int)$row->aging_days > (int)$g->max_age) $g->max_age = (int)$row->aging_days;
    $rowDate = $row->tgl_masuk ?: substr((string)$row->created_at, 0, 10);
    if ($rowDate && (!$g->oldest_date || $rowDate < $g->oldest_date)) $g->oldest_date = $rowDate;
  }
  return array_values($groups);
}
?>
