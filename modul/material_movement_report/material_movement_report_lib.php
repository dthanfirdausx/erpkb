<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
function mmr_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function mmr_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}

function mmr_valid_date($date, $default) {
  $date = trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
}

function mmr_material_expr() {
  return "COALESCE(NULLIF(dt.destination_material_code,''),NULLIF(dt.kd_barang,''))";
}

function mmr_sloc_expr() {
  return "COALESCE(dt.storage_location_id,dt.destination_storage_location_id,slocfb.storage_location_id)";
}

function mmr_bin_expr() {
  return "COALESCE(dt.storage_bin_id,dt.destination_storage_bin_id,slocfb.storage_bin_id)";
}

function mmr_stock_type_expr() {
  return "COALESCE(NULLIF(dt.stock_type,''),NULLIF(dt.destination_stock_type,''),NULLIF(slocfb.stock_type,''),'UNRESTRICTED')";
}

function mmr_direction_expr() {
  return "CASE WHEN dt.direction='OUT' OR (dt.direction IS NULL AND dt.qty<0) THEN 'OUT' ELSE 'IN' END";
}

function mmr_signed_qty_expr() {
  return "CASE WHEN dt.direction='OUT' OR (dt.direction IS NULL AND dt.qty<0) THEN -ABS(COALESCE(dt.qty,0)) ELSE ABS(COALESCE(dt.qty,0)) END";
}

function mmr_base_from_sql() {
  $material = mmr_material_expr();
  $sloc = mmr_sloc_expr();
  $bin = mmr_bin_expr();
  return " FROM detail_transaksi dt
           LEFT JOIN (
             SELECT no_bpb,kode,
                    MIN(plant_id) AS plant_id,
                    MIN(storage_location_id) AS storage_location_id,
                    MIN(storage_bin_id) AS storage_bin_id,
                    MIN(stock_type) AS stock_type
             FROM stock_layer
             GROUP BY no_bpb,kode
           ) slocfb ON slocfb.no_bpb=COALESCE(NULLIF(dt.no_bpb,''),NULLIF(dt.no_ref,''),NULLIF(dt.ref_pengganti,'')) AND slocfb.kode=$material
           LEFT JOIN erp_storage_location es ON es.id=$sloc
           LEFT JOIN erp_plant ep ON ep.id=COALESCE(dt.plant_id,es.plant_id,slocfb.plant_id)
           LEFT JOIN erp_storage_bin eb ON eb.id=$bin
           LEFT JOIN barang b ON b.kd_barang=$material
           LEFT JOIN pemasukan p ON p.no_bpb=COALESCE(NULLIF(dt.no_bpb,''),NULLIF(dt.no_ref,''),NULLIF(dt.ref_pengganti,''))
           LEFT JOIN pemasok v ON v.kode_pemasok=p.pemasok
           LEFT JOIN purchase_order po ON po.id=dt.id_po OR po.id=dt.ref_id ";
}

function mmr_filter_sql($input, &$params) {
  $where = " WHERE ".mmr_material_expr()." IS NOT NULL ";
  $from = mmr_valid_date(isset($input['tgl_awal']) ? $input['tgl_awal'] : '', date('Y-m-01'));
  $to = mmr_valid_date(isset($input['tgl_akhir']) ? $input['tgl_akhir'] : '', date('Y-m-d'));
  $where .= " AND dt.posting_date BETWEEN ? AND ? ";
  $params[] = $from.' 00:00:00';
  $params[] = $to.' 23:59:59';

  if (!empty($input['material_code'])) { $where .= " AND ".mmr_material_expr()."=? "; $params[] = $input['material_code']; }
  if (!empty($input['plant_id'])) { $where .= " AND COALESCE(dt.plant_id,es.plant_id,slocfb.plant_id)=? "; $params[] = (int)$input['plant_id']; }
  if (!empty($input['storage_location_id'])) { $where .= " AND ".mmr_sloc_expr()."=? "; $params[] = (int)$input['storage_location_id']; }
  if (!empty($input['storage_bin_id'])) { $where .= " AND ".mmr_bin_expr()."=? "; $params[] = (int)$input['storage_bin_id']; }
  if (!empty($input['stock_type'])) { $where .= " AND ".mmr_stock_type_expr()."=? "; $params[] = $input['stock_type']; }
  if (!empty($input['move_code'])) { $where .= " AND dt.move_code=? "; $params[] = $input['move_code']; }
  if (!empty($input['ref_type'])) { $where .= " AND dt.ref_type=? "; $params[] = $input['ref_type']; }
  if (!empty($input['direction'])) {
    if ($input['direction'] === 'IN') $where .= " AND ".mmr_direction_expr()."='IN' ";
    if ($input['direction'] === 'OUT') $where .= " AND ".mmr_direction_expr()."='OUT' ";
  }
  if (!empty($input['user'])) { $where .= " AND COALESCE(NULLIF(dt.created_by,''),NULLIF(dt.user,''))=? "; $params[] = $input['user']; }
  if (!empty($input['keyword'])) {
    $kw = '%'.trim($input['keyword']).'%';
    $where .= " AND (dt.no_ref LIKE ? OR dt.no_bpb LIKE ? OR dt.ref_pengganti LIKE ? OR dt.no_aju LIKE ? OR dt.no_dokpab LIKE ? OR dt.kd_barang LIKE ? OR dt.destination_material_code LIKE ? OR b.nm_barang LIKE ? OR p.no_aju LIKE ? OR p.no_dokpab LIKE ? OR p.no_bpb LIKE ? OR po.purchase_order_no LIKE ? OR v.nama LIKE ? OR dt.remark LIKE ? OR dt.reason LIKE ?) ";
    for ($i = 0; $i < 15; $i++) $params[] = $kw;
  }
  return $where;
}

function mmr_input_array() {
  return array(
    'tgl_awal' => mmr_input('tgl_awal', date('Y-m-01')),
    'tgl_akhir' => mmr_input('tgl_akhir', date('Y-m-d')),
    'material_code' => mmr_input('material_code'),
    'plant_id' => mmr_input('plant_id'),
    'storage_location_id' => mmr_input('storage_location_id'),
    'storage_bin_id' => mmr_input('storage_bin_id'),
    'stock_type' => mmr_input('stock_type'),
    'move_code' => mmr_input('move_code'),
    'ref_type' => mmr_input('ref_type'),
    'direction' => mmr_input('direction'),
    'user' => mmr_input('user'),
    'keyword' => mmr_input('keyword')
  );
}

function mmr_load_rows($db, $input, $limit = 0, $offset = 0) {
  $params = array();
  $where = mmr_filter_sql($input, $params);
  $sql = "SELECT dt.id_detail,dt.no_ref,dt.ref_pengganti,dt.no_bpb,dt.no_aju,dt.no_dokpab,
                 dt.move_code,dt.ref_type,dt.document_date,dt.posting_date,dt.qty,dt.uom,dt.price,dt.amount,
                 dt.reason,dt.remark,dt.is_reversal,dt.ref_detail_id,
                 COALESCE(NULLIF(dt.created_by,''),NULLIF(dt.user,'')) AS username,
                 ".mmr_material_expr()." AS material_code,
                 b.nm_barang,b.satuan,
                 COALESCE(dt.plant_id,es.plant_id,slocfb.plant_id) AS plant_id,
                 ep.plant_code,ep.plant_name,
                 ".mmr_sloc_expr()." AS storage_location_id,
                 es.storage_code,es.storage_name,
                 ".mmr_bin_expr()." AS storage_bin_id,
                 eb.bin_code,eb.bin_name,
                 ".mmr_stock_type_expr()." AS stock_type_label,
                 ".mmr_direction_expr()." AS movement_direction,
                 ".mmr_signed_qty_expr()." AS signed_qty,
                 p.no_aju AS header_no_aju,p.no_dokpab AS header_no_dokpab,p.jenis_dokpab,p.tgl_dokpab,
                 po.purchase_order_no,
                 v.nama AS vendor_name
          ".mmr_base_from_sql()."
          $where
          ORDER BY dt.posting_date DESC,dt.id_detail DESC";
  if ($limit > 0) $sql .= " LIMIT ".(int)$offset.",".(int)$limit;
  return $db->query($sql, $params);
}

function mmr_count_rows($db, $input) {
  $params = array();
  $where = mmr_filter_sql($input, $params);
  $row = $db->fetch("SELECT COUNT(*) AS total ".mmr_base_from_sql()." $where", $params);
  return $row ? (int)$row->total : 0;
}

function mmr_summary($db, $input) {
  $params = array();
  $where = mmr_filter_sql($input, $params);
  return $db->fetch("SELECT COUNT(*) AS total_lines,
                            COUNT(DISTINCT COALESCE(NULLIF(dt.no_ref,''),NULLIF(dt.no_bpb,''),dt.id_detail)) AS total_docs,
                            COALESCE(SUM(CASE WHEN ".mmr_direction_expr()."='IN' THEN ABS(COALESCE(dt.qty,0)) ELSE 0 END),0) AS qty_in,
                            COALESCE(SUM(CASE WHEN ".mmr_direction_expr()."='OUT' THEN ABS(COALESCE(dt.qty,0)) ELSE 0 END),0) AS qty_out,
                            COALESCE(SUM(COALESCE(dt.amount,0)),0) AS total_amount
                     ".mmr_base_from_sql()."
                     $where", $params);
}

function mmr_stock_type_label($stockType) {
  $labels = array('UNRESTRICTED'=>'Unrestricted','QUALITY'=>'Quality Inspection','BLOCKED'=>'Blocked');
  return isset($labels[$stockType]) ? $labels[$stockType] : $stockType;
}

function mmr_movement_label($moveCode, $refType, $direction) {
  $labels = array(
    '101'=>'Goods Receipt',
    '102'=>'Goods Receipt Reversal',
    '103'=>'GR Blocked Stock',
    '104'=>'GR Blocked Reversal',
    '105'=>'Release GR Blocked Stock',
    '122'=>'Return to Vendor',
    '201'=>'Issue to Cost Center',
    '241'=>'Issue to Asset',
    '261'=>'Issue to Production',
    '301'=>'Plant Transfer',
    '309'=>'Material to Material Transfer',
    '311'=>'Storage Location Transfer',
    '321'=>'Quality to Unrestricted',
    '343'=>'Blocked to Unrestricted',
    '501'=>'GR without Purchase Order',
    '551'=>'Scrap Issue',
    '555'=>'Sample Issue',
    '561'=>'Initial Stock',
    '701'=>'Physical Inventory Gain',
    '702'=>'Physical Inventory Loss'
  );
  if (isset($labels[$moveCode])) return $labels[$moveCode];
  if ($refType !== '') return $refType;
  return $direction === 'OUT' ? 'Goods Issue' : 'Goods Receipt';
}
?>
