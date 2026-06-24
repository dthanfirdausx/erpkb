<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
function ivr_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ivr_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}

function ivr_valid_date($date, $default) {
  $date = trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
}

function ivr_stock_type_label($stockType) {
  $labels = array('UNRESTRICTED'=>'Unrestricted','QUALITY'=>'Quality Inspection','BLOCKED'=>'Blocked');
  return isset($labels[$stockType]) ? $labels[$stockType] : $stockType;
}

function ivr_price_expr() {
  return "COALESCE(NULLIF(pd.harga,0),NULLIF(dtpd.price,0),NULLIF(dtref.price,0),CASE WHEN ABS(COALESCE(dtref.qty,0))>0 THEN ABS(COALESCE(dtref.amount,0))/ABS(dtref.qty) ELSE NULL END,0)";
}

function ivr_layer_where($input, &$params) {
  $where = " WHERE sl.qty_sisa > 0 ";
  $asOf = ivr_valid_date(isset($input['as_of_date']) ? $input['as_of_date'] : '', date('Y-m-d'));
  $where .= " AND COALESCE(sl.tgl_masuk,DATE(sl.created_at)) <= ? ";
  $params[] = $asOf;
  if (!empty($input['material_code'])) { $where .= " AND sl.kode=? "; $params[] = $input['material_code']; }
  if (!empty($input['plant_id'])) { $where .= " AND sl.plant_id=? "; $params[] = (int)$input['plant_id']; }
  if (!empty($input['storage_location_id'])) { $where .= " AND sl.storage_location_id=? "; $params[] = (int)$input['storage_location_id']; }
  if (!empty($input['storage_bin_id'])) { $where .= " AND sl.storage_bin_id=? "; $params[] = (int)$input['storage_bin_id']; }
  if (!empty($input['stock_type'])) { $where .= " AND sl.stock_type=? "; $params[] = $input['stock_type']; }
  if (!empty($input['material_type_id'])) { $where .= " AND b.material_type_id=? "; $params[] = (int)$input['material_type_id']; }
  if (!empty($input['material_group_id'])) { $where .= " AND b.material_group_id=? "; $params[] = (int)$input['material_group_id']; }
  if (!empty($input['valuation_status']) && $input['valuation_status'] === 'VALUED') $where .= " AND ".ivr_price_expr()." > 0 ";
  if (!empty($input['valuation_status']) && $input['valuation_status'] === 'ZERO') $where .= " AND ".ivr_price_expr()." <= 0 ";
  if (!empty($input['keyword'])) {
    $kw = '%'.trim($input['keyword']).'%';
    $where .= " AND (sl.kode LIKE ? OR b.nm_barang LIKE ? OR sl.no_bpb LIKE ? OR sl.no_aju LIKE ? OR sl.no_dokpab LIKE ? OR sl.jenis_dokpab LIKE ?) ";
    for ($i=0; $i<6; $i++) $params[] = $kw;
  }
  return $where;
}

function ivr_base_sql() {
  return " FROM stock_layer sl
           LEFT JOIN barang b ON b.kd_barang=sl.kode
           LEFT JOIN erp_material_type mt ON mt.id=b.material_type_id
           LEFT JOIN erp_material_group mg ON mg.id=b.material_group_id
           LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
           LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
           LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
           LEFT JOIN pemasukan_detail pd ON (sl.ref_table='pemasukan_detail' AND pd.id=sl.ref_id)
           LEFT JOIN detail_transaksi dtpd ON dtpd.id_incoming_detail=pd.id AND dtpd.kd_barang=sl.kode AND dtpd.direction='IN'
           LEFT JOIN detail_transaksi dtref ON dtref.no_bpb=sl.no_bpb AND dtref.kd_barang=sl.kode AND dtref.direction='IN' ";
}

function ivr_load_layers($db, $input) {
  $params = array();
  $where = ivr_layer_where($input, $params);
  $price = ivr_price_expr();
  return $db->query(
    "SELECT sl.*,b.nm_barang,b.satuan,b.kategori,b.material_type_id,b.material_group_id,
            mt.type_code,mt.type_name,mg.group_code,mg.group_name,
            ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
            pd.harga AS purchase_price,pd.valuta AS currency,pd.unit AS purchase_uom,
            $price AS unit_price,
            (sl.qty_sisa * $price) AS stock_value,
            CASE WHEN $price > 0 THEN 'VALUED' ELSE 'ZERO' END AS valuation_status
     ".ivr_base_sql()."
     $where
     GROUP BY sl.id
     ORDER BY sl.kode,sl.plant_id,sl.storage_location_id,sl.storage_bin_id,sl.stock_type,sl.id",
    $params
  );
}

function ivr_group_key($row) {
  return implode('|', array((string)$row->kode,(string)$row->plant_id,(string)$row->storage_location_id,(string)$row->storage_bin_id,(string)$row->stock_type));
}

function ivr_group_layers($layers) {
  $groups = array();
  foreach ($layers as $row) {
    $key = ivr_group_key($row);
    if (!isset($groups[$key])) {
      $groups[$key] = (object)array(
        'material_code'=>$row->kode,
        'material_name'=>$row->nm_barang,
        'uom'=>$row->satuan,
        'material_type'=>$row->type_code ? $row->type_code.' - '.$row->type_name : '',
        'material_group'=>$row->group_code ? $row->group_code.' - '.$row->group_name : '',
        'plant_id'=>$row->plant_id,
        'plant_code'=>$row->plant_code,
        'storage_location_id'=>$row->storage_location_id,
        'storage_code'=>$row->storage_code,
        'storage_name'=>$row->storage_name,
        'storage_bin_id'=>$row->storage_bin_id,
        'bin_code'=>$row->bin_code,
        'stock_type'=>$row->stock_type,
        'layer_count'=>0,
        'total_qty'=>0,
        'total_value'=>0,
        'min_price'=>null,
        'max_price'=>null,
        'zero_layers'=>0,
        'customs_doc_count'=>0,
        '_customs'=>array()
      );
    }
    $g = $groups[$key];
    $price = (float)$row->unit_price;
    $value = (float)$row->stock_value;
    $g->layer_count++;
    $g->total_qty += (float)$row->qty_sisa;
    $g->total_value += $value;
    if ($price <= 0) $g->zero_layers++;
    if ($g->min_price === null || $price < $g->min_price) $g->min_price = $price;
    if ($g->max_price === null || $price > $g->max_price) $g->max_price = $price;
    $customsKey = trim((string)$row->jenis_dokpab.'|'.(string)$row->no_aju.'|'.(string)$row->no_dokpab);
    if ($customsKey !== '||') $g->_customs[$customsKey] = true;
  }
  foreach ($groups as $g) {
    $g->avg_price = $g->total_qty > 0 ? $g->total_value / $g->total_qty : 0;
    $g->customs_doc_count = count($g->_customs);
    unset($g->_customs);
  }
  return array_values($groups);
}

function ivr_input_array() {
  return array(
    'as_of_date'=>ivr_input('as_of_date', date('Y-m-d')),
    'material_code'=>ivr_input('material_code'),
    'plant_id'=>ivr_input('plant_id'),
    'storage_location_id'=>ivr_input('storage_location_id'),
    'storage_bin_id'=>ivr_input('storage_bin_id'),
    'stock_type'=>ivr_input('stock_type'),
    'material_type_id'=>ivr_input('material_type_id'),
    'material_group_id'=>ivr_input('material_group_id'),
    'valuation_status'=>ivr_input('valuation_status'),
    'keyword'=>ivr_input('keyword')
  );
}
?>
