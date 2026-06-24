<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
function cc_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cc_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}

function cc_valid_date($date, $default) {
  $date = trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
}

function cc_stock_type_label($stockType) {
  $labels = array('UNRESTRICTED'=>'Unrestricted','QUALITY'=>'Quality Inspection','BLOCKED'=>'Blocked');
  return isset($labels[$stockType]) ? $labels[$stockType] : $stockType;
}

function cc_cycle_class($qty, $layerCount) {
  $qty = (float)$qty;
  $layerCount = (int)$layerCount;
  if ($qty >= 1000 || $layerCount >= 10) return 'A';
  if ($qty >= 100 || $layerCount >= 3) return 'B';
  return 'C';
}

function cc_cycle_interval_days($class) {
  if ($class === 'A') return 30;
  if ($class === 'B') return 90;
  return 180;
}

function cc_due_status($lastCountDate, $asOfDate, $class) {
  $asOf = strtotime($asOfDate);
  $interval = cc_cycle_interval_days($class);
  if (!$lastCountDate) return 'Due';
  $days = floor(($asOf - strtotime($lastCountDate)) / 86400);
  if ($days >= $interval) return 'Due';
  if ($days >= max(1, $interval - 7)) return 'Upcoming';
  return 'Not Due';
}

function cc_due_badge($status) {
  if ($status === 'Due') return '<span class="label label-danger">Due</span>';
  if ($status === 'Upcoming') return '<span class="label label-warning">Upcoming</span>';
  return '<span class="label label-success">Not Due</span>';
}

function cc_doc_status_badge($status) {
  $status = strtoupper((string)$status);
  if ($status === 'POSTED') return '<span class="label label-success">Posted</span>';
  if ($status === 'COUNTED') return '<span class="label label-info">Counted</span>';
  if ($status === 'CANCELLED') return '<span class="label label-default">Cancelled</span>';
  return '<span class="label label-warning">Open</span>';
}

function cc_layer_where($input, &$params) {
  $where = " WHERE sl.qty_sisa > 0 ";
  $asOf = cc_valid_date(isset($input['as_of_date']) ? $input['as_of_date'] : '', date('Y-m-d'));
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

function cc_load_groups($db, $input) {
  $params = array();
  $where = cc_layer_where($input, $params);
  $asOf = cc_valid_date(isset($input['as_of_date']) ? $input['as_of_date'] : '', date('Y-m-d'));
  $rows = $db->query(
    "SELECT sl.kode AS material_code,b.nm_barang,b.satuan,
            sl.plant_id,ep.plant_code,ep.plant_name,
            sl.storage_location_id,es.storage_code,es.storage_name,
            sl.storage_bin_id,eb.bin_code,eb.bin_name,
            sl.stock_type,
            MIN(COALESCE(sl.tgl_masuk,DATE(sl.created_at))) AS oldest_receipt,
            MAX(DATEDIFF(?,COALESCE(sl.tgl_masuk,DATE(sl.created_at)))) AS max_age_days,
            COUNT(*) AS layer_count,
            COUNT(DISTINCT CONCAT(COALESCE(sl.jenis_dokpab,''),'|',COALESCE(sl.no_aju,''),'|',COALESCE(sl.no_dokpab,''))) AS customs_doc_count,
            COALESCE(SUM(sl.qty_sisa),0) AS system_qty,
            cc.last_count_date,
            cc.last_doc_no,
            cc.open_doc_no
     FROM stock_layer sl
     LEFT JOIN barang b ON b.kd_barang=sl.kode
     LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
     LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
     LEFT JOIN (
       SELECT cci.material_code,cci.plant_id,cci.storage_location_id,cci.storage_bin_id,cci.stock_type,
              MAX(ccd.count_date) AS last_count_date,
              MAX(ccd.doc_no) AS last_doc_no,
              MAX(CASE WHEN ccd.status='OPEN' THEN ccd.doc_no END) AS open_doc_no
       FROM cycle_count_document_items cci
       JOIN cycle_count_documents ccd ON ccd.id=cci.document_id
       WHERE ccd.status<>'CANCELLED' AND ccd.count_date<=?
       GROUP BY cci.material_code,cci.plant_id,cci.storage_location_id,cci.storage_bin_id,cci.stock_type
     ) cc ON cc.material_code=sl.kode
       AND IFNULL(cc.plant_id,0)=IFNULL(sl.plant_id,0)
       AND IFNULL(cc.storage_location_id,0)=IFNULL(sl.storage_location_id,0)
       AND IFNULL(cc.storage_bin_id,0)=IFNULL(sl.storage_bin_id,0)
       AND cc.stock_type=sl.stock_type
     $where
     GROUP BY sl.kode,b.nm_barang,b.satuan,sl.plant_id,ep.plant_code,ep.plant_name,sl.storage_location_id,es.storage_code,es.storage_name,sl.storage_bin_id,eb.bin_code,eb.bin_name,sl.stock_type
     ORDER BY system_qty DESC,sl.kode",
    array_merge(array($asOf, $asOf), $params)
  );
  $result = array();
  foreach ($rows as $row) {
    $row->cycle_class = cc_cycle_class($row->system_qty, $row->layer_count);
    $row->cycle_interval_days = cc_cycle_interval_days($row->cycle_class);
    $row->due_status = cc_due_status($row->last_count_date, $asOf, $row->cycle_class);
    if (!empty($input['cycle_class']) && $row->cycle_class !== $input['cycle_class']) continue;
    if (!empty($input['due_status']) && $row->due_status !== $input['due_status']) continue;
    $result[] = $row;
  }
  return $result;
}

function cc_next_doc_no($db) {
  $prefix = 'CC'.date('Ym');
  $row = $db->fetch("SELECT doc_no FROM cycle_count_documents WHERE doc_no LIKE ? ORDER BY doc_no DESC LIMIT 1", array($prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{4})$/', $row->doc_no, $m)) $next = ((int)$m[1]) + 1;
  return $prefix.sprintf('%04d', $next);
}
?>
