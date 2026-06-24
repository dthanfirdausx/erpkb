<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
function so_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function so_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}

function so_valid_date($date, $default) {
  $date = trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
}

function so_stock_type_label($stockType) {
  $labels = array('UNRESTRICTED'=>'Unrestricted','QUALITY'=>'Quality Inspection','BLOCKED'=>'Blocked');
  return isset($labels[$stockType]) ? $labels[$stockType] : $stockType;
}

function so_status_badge($status) {
  $status = strtoupper((string)$status);
  if ($status === 'POSTED') return '<span class="label label-success">Posted</span>';
  if ($status === 'COUNTED') return '<span class="label label-info">Counted</span>';
  if ($status === 'CANCELLED') return '<span class="label label-default">Cancelled</span>';
  return '<span class="label label-warning">Open</span>';
}

function so_doc_status_badge($docNo) {
  return $docNo ? '<span class="label label-warning">'.so_h($docNo).'</span>' : '<span class="label label-default">No Open Doc</span>';
}

function so_filter_sql($input, &$params) {
  $where = " WHERE sl.qty_sisa > 0 ";
  $asOf = so_valid_date(isset($input['as_of_date']) ? $input['as_of_date'] : '', date('Y-m-d'));
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

function so_load_groups($db, $input) {
  $params = array();
  $where = so_filter_sql($input, $params);
  $asOf = so_valid_date(isset($input['as_of_date']) ? $input['as_of_date'] : '', date('Y-m-d'));
  $rows = $db->query(
    "SELECT sl.kode AS material_code,b.nm_barang,b.satuan,
            sl.plant_id,ep.plant_code,ep.plant_name,
            sl.storage_location_id,es.storage_code,es.storage_name,
            sl.storage_bin_id,eb.bin_code,eb.bin_name,
            sl.stock_type,
            MIN(COALESCE(sl.tgl_masuk,DATE(sl.created_at))) AS oldest_receipt,
            COUNT(*) AS layer_count,
            COUNT(DISTINCT CONCAT(COALESCE(sl.jenis_dokpab,''),'|',COALESCE(sl.no_aju,''),'|',COALESCE(sl.no_dokpab,''))) AS customs_doc_count,
            COALESCE(SUM(sl.qty_sisa),0) AS system_qty,
            op.last_count_date,op.last_doc_no,op.open_doc_no
     FROM stock_layer sl
     LEFT JOIN barang b ON b.kd_barang=sl.kode
     LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
     LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
     LEFT JOIN (
       SELECT soi.material_code,soi.plant_id,soi.storage_location_id,soi.storage_bin_id,soi.stock_type,
              MAX(sod.opname_date) AS last_count_date,
              MAX(sod.doc_no) AS last_doc_no,
              MAX(CASE WHEN sod.status='OPEN' THEN sod.doc_no END) AS open_doc_no
       FROM stock_opname_document_items soi
       JOIN stock_opname_documents sod ON sod.id=soi.document_id
       WHERE sod.status<>'CANCELLED' AND sod.opname_date<=?
       GROUP BY soi.material_code,soi.plant_id,soi.storage_location_id,soi.storage_bin_id,soi.stock_type
     ) op ON op.material_code=sl.kode
       AND IFNULL(op.plant_id,0)=IFNULL(sl.plant_id,0)
       AND IFNULL(op.storage_location_id,0)=IFNULL(sl.storage_location_id,0)
       AND IFNULL(op.storage_bin_id,0)=IFNULL(sl.storage_bin_id,0)
       AND op.stock_type=sl.stock_type
     $where
     GROUP BY sl.kode,b.nm_barang,b.satuan,sl.plant_id,ep.plant_code,ep.plant_name,sl.storage_location_id,es.storage_code,es.storage_name,sl.storage_bin_id,eb.bin_code,eb.bin_name,sl.stock_type
     ORDER BY ep.plant_code,es.storage_code,eb.bin_code,sl.kode",
    array_merge(array($asOf), $params)
  );
  $result = array();
  foreach ($rows as $row) {
    if (!empty($input['doc_status'])) {
      if ($input['doc_status'] === 'OPEN' && !$row->open_doc_no) continue;
      if ($input['doc_status'] === 'NO_OPEN' && $row->open_doc_no) continue;
    }
    $result[] = $row;
  }
  return $result;
}

function so_next_doc_no($db) {
  $prefix = 'SO'.date('Ym');
  $row = $db->fetch("SELECT doc_no FROM stock_opname_documents WHERE doc_no LIKE ? ORDER BY doc_no DESC LIMIT 1", array($prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{4})$/', $row->doc_no, $m)) $next = ((int)$m[1]) + 1;
  return $prefix.sprintf('%04d', $next);
}
?>
