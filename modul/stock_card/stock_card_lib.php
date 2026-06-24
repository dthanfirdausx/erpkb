<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
function scard_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function scard_post($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}

function scard_valid_date($date, $default) {
  $date = trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
}

function scard_material_expr() {
  return "COALESCE(NULLIF(dt.destination_material_code,''),NULLIF(dt.kd_barang,''))";
}

function scard_storage_expr() {
  return "COALESCE(dt.storage_location_id,dt.destination_storage_location_id,slocfb.storage_location_id)";
}

function scard_bin_expr() {
  return "COALESCE(dt.storage_bin_id,dt.destination_storage_bin_id,slocfb.storage_bin_id)";
}

function scard_stock_type_expr() {
  return "COALESCE(NULLIF(dt.stock_type,''),NULLIF(dt.destination_stock_type,''),NULLIF(slocfb.stock_type,''),'UNRESTRICTED')";
}

function scard_signed_expr() {
  return "CASE WHEN dt.direction='OUT' OR (dt.direction IS NULL AND dt.qty<0) THEN -ABS(COALESCE(dt.qty,0)) ELSE ABS(COALESCE(dt.qty,0)) END";
}

function scard_filter_sql($input, &$params, $mode = 'rows') {
  $where = " WHERE ".scard_material_expr()." IS NOT NULL ";
  $tglAwal = scard_valid_date(isset($input['tgl_awal']) ? $input['tgl_awal'] : '', date('Y-m-01'));
  $tglAkhir = scard_valid_date(isset($input['tgl_akhir']) ? $input['tgl_akhir'] : '', date('Y-m-d'));

  if ($mode === 'opening') {
    $where .= " AND dt.posting_date < ? ";
    $params[] = $tglAwal.' 00:00:00';
  } else {
    $where .= " AND dt.posting_date BETWEEN ? AND ? ";
    $params[] = $tglAwal.' 00:00:00';
    $params[] = $tglAkhir.' 23:59:59';
  }

  if (!empty($input['material_code'])) {
    $where .= " AND ".scard_material_expr()." = ? ";
    $params[] = trim($input['material_code']);
  }
  if (!empty($input['plant_id'])) {
    $where .= " AND COALESCE(dt.plant_id,es.plant_id,slocfb.plant_id) = ? ";
    $params[] = (int)$input['plant_id'];
  }
  if (!empty($input['storage_location_id'])) {
    $where .= " AND ".scard_storage_expr()." = ? ";
    $params[] = (int)$input['storage_location_id'];
  }
  if (!empty($input['storage_bin_id'])) {
    $where .= " AND ".scard_bin_expr()." = ? ";
    $params[] = (int)$input['storage_bin_id'];
  }
  if (!empty($input['stock_type'])) {
    $where .= " AND ".scard_stock_type_expr()." = ? ";
    $params[] = trim($input['stock_type']);
  }
  if (!empty($input['move_code'])) {
    $where .= " AND dt.move_code = ? ";
    $params[] = trim($input['move_code']);
  }
  if (!empty($input['direction'])) {
    if ($input['direction'] === 'IN') {
      $where .= " AND (dt.direction='IN' OR (dt.direction IS NULL AND dt.qty>=0)) ";
    } elseif ($input['direction'] === 'OUT') {
      $where .= " AND (dt.direction='OUT' OR (dt.direction IS NULL AND dt.qty<0)) ";
    }
  }
  if (!empty($input['keyword'])) {
    $kw = '%'.trim($input['keyword']).'%';
    $where .= " AND (dt.no_ref LIKE ? OR dt.no_bpb LIKE ? OR dt.ref_pengganti LIKE ? OR dt.no_aju LIKE ? OR dt.no_dokpab LIKE ? OR dt.kd_barang LIKE ? OR dt.destination_material_code LIKE ? OR b.nm_barang LIKE ? OR dt.remark LIKE ? OR dt.reason LIKE ?) ";
    for ($i = 0; $i < 10; $i++) $params[] = $kw;
  }
  return $where;
}

function scard_partition_key($row) {
  return implode('|', array(
    (string)$row->material_code,
    (string)$row->plant_id,
    (string)$row->storage_location_id,
    (string)$row->storage_bin_id,
    (string)$row->line_stock_type
  ));
}

function scard_base_from_sql() {
  $material = scard_material_expr();
  $sloc = scard_storage_expr();
  $bin = scard_bin_expr();
  $stockType = scard_stock_type_expr();
  $signed = scard_signed_expr();
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
           LEFT JOIN barang b ON b.kd_barang=$material
           LEFT JOIN erp_plant ep ON ep.id=COALESCE(dt.plant_id,es.plant_id,slocfb.plant_id)
           LEFT JOIN erp_storage_bin eb ON eb.id=$bin
           LEFT JOIN pemasukan p ON p.no_bpb=COALESCE(NULLIF(dt.no_bpb,''),NULLIF(dt.no_ref,''),NULLIF(dt.ref_pengganti,''))
           LEFT JOIN purchase_order po ON po.id=dt.id_po OR po.id=dt.ref_id ";
}

function scard_load_card_rows($db, $input) {
  $openingParams = array();
  $openingWhere = scard_filter_sql($input, $openingParams, 'opening');
  $openSql = "SELECT ".scard_material_expr()." AS material_code,
                     COALESCE(dt.plant_id,es.plant_id,slocfb.plant_id) AS plant_id,
                     ".scard_storage_expr()." AS storage_location_id,
                     ".scard_bin_expr()." AS storage_bin_id,
                     ".scard_stock_type_expr()." AS line_stock_type,
                     COALESCE(SUM(".scard_signed_expr()."),0) AS opening_qty
              ".scard_base_from_sql()."
              $openingWhere
              GROUP BY material_code,plant_id,storage_location_id,storage_bin_id,line_stock_type";
  $opening = array();
  foreach ($db->query($openSql, $openingParams) as $row) {
    $opening[scard_partition_key($row)] = (float)$row->opening_qty;
  }

  $params = array();
  $where = scard_filter_sql($input, $params, 'rows');
  $sql = "SELECT dt.id_detail,dt.no_ref,dt.ref_pengganti,dt.no_bpb,dt.no_aju,dt.no_dokpab,dt.move_code,
                 dt.document_date,dt.posting_date,dt.direction,dt.qty,dt.uom,dt.amount,dt.ref_type,
                 dt.reason,dt.remark,COALESCE(NULLIF(dt.created_by,''),NULLIF(dt.user,'')) AS username,
                 ".scard_material_expr()." AS material_code,
                 b.nm_barang,b.satuan,
                 COALESCE(dt.plant_id,es.plant_id,slocfb.plant_id) AS plant_id,
                 ep.plant_code,ep.plant_name,
                 ".scard_storage_expr()." AS storage_location_id,
                 es.storage_code,es.storage_name,
                 ".scard_bin_expr()." AS storage_bin_id,
                 eb.bin_code,eb.bin_name,
                 ".scard_stock_type_expr()." AS line_stock_type,
                 ".scard_signed_expr()." AS signed_qty,
                 p.no_aju AS header_no_aju,p.no_dokpab AS header_no_dokpab,
                 po.purchase_order_no
          ".scard_base_from_sql()."
          $where
          ORDER BY material_code,plant_id,storage_location_id,storage_bin_id,line_stock_type,dt.posting_date,dt.id_detail";

  $rows = array();
  $running = $opening;
  foreach ($db->query($sql, $params) as $row) {
    $key = scard_partition_key($row);
    if (!isset($running[$key])) $running[$key] = 0;
    $row->opening_balance = $running[$key];
    $running[$key] += (float)$row->signed_qty;
    $row->running_balance = $running[$key];
    $row->qty_in = (float)$row->signed_qty > 0 ? (float)$row->signed_qty : 0;
    $row->qty_out = (float)$row->signed_qty < 0 ? abs((float)$row->signed_qty) : 0;
    $rows[] = $row;
  }
  return $rows;
}

function scard_stock_type_label($stockType) {
  $labels = array(
    'UNRESTRICTED' => 'Unrestricted',
    'QUALITY' => 'Quality Inspection',
    'BLOCKED' => 'Blocked'
  );
  return isset($labels[$stockType]) ? $labels[$stockType] : $stockType;
}

function scard_movement_label($moveCode, $refType, $direction) {
  $labels = array(
    '101' => 'GR for PO / Production',
    '102' => 'GR Reversal',
    '103' => 'GR Blocked Stock',
    '104' => 'Blocked Reversal',
    '105' => 'Release Blocked Stock',
    '122' => 'Return to Vendor',
    '201' => 'Issue to Cost Center',
    '241' => 'Issue to Asset',
    '261' => 'Issue to Production',
    '551' => 'Scrap Issue',
    '555' => 'Sample Issue',
    '501' => 'GR without PO'
  );
  if (isset($labels[$moveCode])) return $labels[$moveCode];
  if ($refType) return $refType;
  return $direction === 'OUT' ? 'Goods Issue' : 'Goods Receipt';
}
?>
