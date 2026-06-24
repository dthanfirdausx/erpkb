<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
function ce_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ce_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}

function ce_valid_date($date, $default) {
  $date = trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
}

function ce_stock_type_label($stockType) {
  $labels = array('UNRESTRICTED'=>'Unrestricted','QUALITY'=>'Quality Inspection','BLOCKED'=>'Blocked');
  return isset($labels[$stockType]) ? $labels[$stockType] : $stockType;
}

function ce_status_badge($status) {
  $status = strtoupper((string)$status);
  if ($status === 'POSTED') return '<span class="label label-success">Posted</span>';
  if ($status === 'COUNTED') return '<span class="label label-info">Counted</span>';
  if ($status === 'CANCELLED') return '<span class="label label-default">Cancelled</span>';
  return '<span class="label label-warning">Open</span>';
}

function ce_doc_type_label($type) {
  return $type === 'CYCLE_COUNT' ? 'Cycle Count' : 'Stock Opname';
}

function ce_base_union_sql() {
  return "
    SELECT 'CYCLE_COUNT' AS doc_type,d.id AS document_id,d.doc_no,d.count_date AS count_date,d.status AS document_status,
           i.id AS item_id,i.line_no,i.material_code,i.material_name,i.plant_id,i.storage_location_id,i.storage_bin_id,i.stock_type,
           i.system_qty,i.counted_qty,i.difference_qty,i.uom,i.layer_count,i.customs_doc_count,i.status AS item_status,
           i.counted_by,i.counted_at,i.remarks,ep.plant_code,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name
    FROM cycle_count_document_items i
    JOIN cycle_count_documents d ON d.id=i.document_id
    LEFT JOIN erp_plant ep ON ep.id=i.plant_id
    LEFT JOIN erp_storage_location es ON es.id=i.storage_location_id
    LEFT JOIN erp_storage_bin eb ON eb.id=i.storage_bin_id
    UNION ALL
    SELECT 'STOCK_OPNAME' AS doc_type,d.id AS document_id,d.doc_no,d.opname_date AS count_date,d.status AS document_status,
           i.id AS item_id,i.line_no,i.material_code,i.material_name,i.plant_id,i.storage_location_id,i.storage_bin_id,i.stock_type,
           i.system_qty,i.counted_qty,i.difference_qty,i.uom,i.layer_count,i.customs_doc_count,i.status AS item_status,
           i.counted_by,i.counted_at,i.remarks,ep.plant_code,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name
    FROM stock_opname_document_items i
    JOIN stock_opname_documents d ON d.id=i.document_id
    LEFT JOIN erp_plant ep ON ep.id=i.plant_id
    LEFT JOIN erp_storage_location es ON es.id=i.storage_location_id
    LEFT JOIN erp_storage_bin eb ON eb.id=i.storage_bin_id
  ";
}

function ce_filter_where($input, &$params) {
  $where = " WHERE 1=1 ";
  $from = ce_valid_date(isset($input['tgl_awal']) ? $input['tgl_awal'] : '', date('Y-m-01'));
  $to = ce_valid_date(isset($input['tgl_akhir']) ? $input['tgl_akhir'] : '', date('Y-m-d'));
  $where .= " AND x.count_date BETWEEN ? AND ? ";
  $params[] = $from;
  $params[] = $to;
  if (!empty($input['doc_type'])) { $where .= " AND x.doc_type=? "; $params[] = $input['doc_type']; }
  if (!empty($input['doc_no'])) { $where .= " AND x.doc_no LIKE ? "; $params[] = '%'.$input['doc_no'].'%'; }
  if (!empty($input['material_code'])) { $where .= " AND x.material_code=? "; $params[] = $input['material_code']; }
  if (!empty($input['plant_id'])) { $where .= " AND x.plant_id=? "; $params[] = (int)$input['plant_id']; }
  if (!empty($input['storage_location_id'])) { $where .= " AND x.storage_location_id=? "; $params[] = (int)$input['storage_location_id']; }
  if (!empty($input['storage_bin_id'])) { $where .= " AND x.storage_bin_id=? "; $params[] = (int)$input['storage_bin_id']; }
  if (!empty($input['stock_type'])) { $where .= " AND x.stock_type=? "; $params[] = $input['stock_type']; }
  if (!empty($input['item_status'])) { $where .= " AND x.item_status=? "; $params[] = $input['item_status']; }
  if (!empty($input['keyword'])) {
    $kw = '%'.trim($input['keyword']).'%';
    $where .= " AND (x.doc_no LIKE ? OR x.material_code LIKE ? OR x.material_name LIKE ? OR x.remarks LIKE ?) ";
    for ($i=0; $i<4; $i++) $params[] = $kw;
  }
  return $where;
}

function ce_load_rows($db, $input) {
  $params = array();
  $where = ce_filter_where($input, $params);
  return $db->query("SELECT x.* FROM (".ce_base_union_sql().") x $where ORDER BY CASE x.item_status WHEN 'OPEN' THEN 1 WHEN 'COUNTED' THEN 2 WHEN 'POSTED' THEN 3 WHEN 'CANCELLED' THEN 4 ELSE 5 END ASC,x.count_date DESC,x.doc_no DESC,x.line_no ASC", $params);
}

function ce_get_item($db, $docType, $itemId) {
  $table = $docType === 'CYCLE_COUNT' ? 'cycle_count_document_items' : 'stock_opname_document_items';
  $docTable = $docType === 'CYCLE_COUNT' ? 'cycle_count_documents' : 'stock_opname_documents';
  $dateCol = $docType === 'CYCLE_COUNT' ? 'count_date' : 'opname_date';
  return $db->fetch(
    "SELECT i.*,d.doc_no,d.$dateCol AS count_date,d.status AS document_status
     FROM $table i JOIN $docTable d ON d.id=i.document_id
     WHERE i.id=?",
    array((int)$itemId)
  );
}
?>
