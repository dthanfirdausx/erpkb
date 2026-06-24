<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
function sms_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sms_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}

function sms_valid_date($date, $default) {
  $date = trim((string)$date);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
}

function sms_stock_type_label($stockType) {
  $labels = array('UNRESTRICTED'=>'Unrestricted','QUALITY'=>'Quality Inspection','BLOCKED'=>'Blocked');
  return isset($labels[$stockType]) ? $labels[$stockType] : $stockType;
}

function sms_threshold($value) {
  $value = (int)$value;
  if ($value <= 0) return 90;
  if ($value > 9999) return 9999;
  return $value;
}

function sms_risk_label($idleDays, $threshold) {
  $idleDays = (int)$idleDays;
  $threshold = sms_threshold($threshold);
  if ($idleDays >= ($threshold * 2)) return 'Critical';
  if ($idleDays >= $threshold) return 'Slow Moving';
  return 'Normal';
}

function sms_risk_badge($risk) {
  if ($risk === 'Critical') return '<span class="label label-danger">Critical</span>';
  if ($risk === 'Slow Moving') return '<span class="label label-warning">Slow Moving</span>';
  return '<span class="label label-success">Normal</span>';
}

function sms_layer_where($input, &$params) {
  $where = " WHERE sl.qty_sisa > 0 ";
  $asOf = sms_valid_date(isset($input['as_of_date']) ? $input['as_of_date'] : '', date('Y-m-d'));
  $where .= " AND COALESCE(sl.tgl_masuk,DATE(sl.created_at)) <= ? ";
  $params[] = $asOf;
  if (!empty($input['material_code'])) { $where .= " AND sl.kode=? "; $params[] = $input['material_code']; }
  if (!empty($input['plant_id'])) { $where .= " AND sl.plant_id=? "; $params[] = (int)$input['plant_id']; }
  if (!empty($input['storage_location_id'])) { $where .= " AND sl.storage_location_id=? "; $params[] = (int)$input['storage_location_id']; }
  if (!empty($input['storage_bin_id'])) { $where .= " AND sl.storage_bin_id=? "; $params[] = (int)$input['storage_bin_id']; }
  if (!empty($input['stock_type'])) { $where .= " AND sl.stock_type=? "; $params[] = $input['stock_type']; }
  if (!empty($input['jenis_dokpab'])) { $where .= " AND COALESCE(sl.jenis_dokpab,'')=? "; $params[] = $input['jenis_dokpab']; }
  if (!empty($input['no_aju'])) { $where .= " AND sl.no_aju LIKE ? "; $params[] = '%'.$input['no_aju'].'%'; }
  if (!empty($input['no_dokpab'])) { $where .= " AND sl.no_dokpab LIKE ? "; $params[] = '%'.$input['no_dokpab'].'%'; }
  if (!empty($input['keyword'])) {
    $kw = '%'.trim($input['keyword']).'%';
    $where .= " AND (sl.kode LIKE ? OR b.nm_barang LIKE ? OR sl.no_bpb LIKE ? OR sl.no_aju LIKE ? OR sl.no_dokpab LIKE ? OR sl.jenis_dokpab LIKE ? OR sl.ref_table LIKE ?) ";
    for ($i=0; $i<7; $i++) $params[] = $kw;
  }
  return $where;
}

function sms_load_layers($db, $input) {
  $params = array();
  $where = sms_layer_where($input, $params);
  $asOf = sms_valid_date(isset($input['as_of_date']) ? $input['as_of_date'] : '', date('Y-m-d'));
  $threshold = sms_threshold(isset($input['threshold_days']) ? $input['threshold_days'] : 90);
  $slowOnly = !isset($input['slow_only']) || $input['slow_only'] === '' || $input['slow_only'] === 'Y';
  $queryParams = array($asOf, $asOf, $asOf.' 23:59:59');
  $rows = $db->query(
    "SELECT sl.*,b.nm_barang,b.satuan,b.kategori,b.material_type_id,b.material_group_id,
            ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
            mv.last_out_date,mv.last_move_date,
            COALESCE(mv.last_out_date,mv.last_move_date,sl.tgl_masuk,DATE(sl.created_at)) AS last_activity_date,
            DATEDIFF(?,COALESCE(mv.last_out_date,mv.last_move_date,sl.tgl_masuk,DATE(sl.created_at))) AS idle_days,
            DATEDIFF(?,COALESCE(sl.tgl_masuk,DATE(sl.created_at))) AS aging_days
     FROM stock_layer sl
     LEFT JOIN barang b ON b.kd_barang=sl.kode
     LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
     LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
     LEFT JOIN (
       SELECT kd_barang,plant_id,storage_location_id,storage_bin_id,COALESCE(stock_type,'UNRESTRICTED') stock_type,
              DATE(MAX(CASE WHEN direction='OUT' THEN COALESCE(posting_date,document_date,date_created) END)) AS last_out_date,
              DATE(MAX(COALESCE(posting_date,document_date,date_created))) AS last_move_date
       FROM detail_transaksi
       WHERE kd_barang IS NOT NULL AND COALESCE(posting_date,document_date,date_created) <= ?
       GROUP BY kd_barang,plant_id,storage_location_id,storage_bin_id,COALESCE(stock_type,'UNRESTRICTED')
     ) mv ON mv.kd_barang=sl.kode
          AND IFNULL(mv.plant_id,0)=IFNULL(sl.plant_id,0)
          AND IFNULL(mv.storage_location_id,0)=IFNULL(sl.storage_location_id,0)
          AND IFNULL(mv.storage_bin_id,0)=IFNULL(sl.storage_bin_id,0)
          AND mv.stock_type=sl.stock_type
     $where
     ORDER BY idle_days DESC,sl.kode,sl.plant_id,sl.storage_location_id,sl.storage_bin_id,sl.stock_type,sl.id",
    array_merge($queryParams, $params)
  );
  $result = array();
  foreach ($rows as $row) {
    $row->idle_days = max(0, (int)$row->idle_days);
    $row->aging_days = max(0, (int)$row->aging_days);
    $row->risk_label = sms_risk_label($row->idle_days, $threshold);
    if ($slowOnly && $row->idle_days < $threshold) continue;
    if (!empty($input['risk_label']) && $row->risk_label !== $input['risk_label']) continue;
    $result[] = $row;
  }
  return $result;
}

function sms_group_key($row) {
  return implode('|', array((string)$row->kode,(string)$row->plant_id,(string)$row->storage_location_id,(string)$row->storage_bin_id,(string)$row->stock_type));
}

function sms_group_layers($layers, $threshold) {
  $groups = array();
  foreach ($layers as $row) {
    $key = sms_group_key($row);
    if (!isset($groups[$key])) {
      $groups[$key] = (object)array(
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
        'oldest_receipt'=>$row->tgl_masuk ?: substr((string)$row->created_at,0,10),
        'last_out_date'=>$row->last_out_date,
        'last_move_date'=>$row->last_move_date,
        'max_idle_days'=>0,
        'max_aging_days'=>0,
        'qty_sisa'=>0,
        'qty_critical'=>0,
        'qty_slow'=>0,
        'doc_count'=>array(),
        'risk_label'=>'Normal'
      );
    }
    $g = $groups[$key];
    $g->layer_count++;
    $g->qty_sisa += (float)$row->qty_sisa;
    if ($row->risk_label === 'Critical') $g->qty_critical += (float)$row->qty_sisa;
    if ($row->risk_label === 'Slow Moving') $g->qty_slow += (float)$row->qty_sisa;
    if ((int)$row->idle_days > (int)$g->max_idle_days) $g->max_idle_days = (int)$row->idle_days;
    if ((int)$row->aging_days > (int)$g->max_aging_days) $g->max_aging_days = (int)$row->aging_days;
    $receiptDate = $row->tgl_masuk ?: substr((string)$row->created_at,0,10);
    if ($receiptDate && (!$g->oldest_receipt || $receiptDate < $g->oldest_receipt)) $g->oldest_receipt = $receiptDate;
    if ($row->last_out_date && (!$g->last_out_date || $row->last_out_date > $g->last_out_date)) $g->last_out_date = $row->last_out_date;
    if ($row->last_move_date && (!$g->last_move_date || $row->last_move_date > $g->last_move_date)) $g->last_move_date = $row->last_move_date;
    $docKey = trim((string)$row->jenis_dokpab.'|'.(string)$row->no_aju.'|'.(string)$row->no_dokpab);
    if ($docKey !== '||') $g->doc_count[$docKey] = true;
    $risk = sms_risk_label($g->max_idle_days, $threshold);
    $g->risk_label = $risk;
  }
  foreach ($groups as $g) $g->doc_total = count($g->doc_count);
  return array_values($groups);
}
?>
