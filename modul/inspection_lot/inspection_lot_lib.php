<?php
function ilot_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function ilot_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}
function ilot_valid_date($date, $default) {
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date) ? $date : $default;
}
function ilot_num($value, $decimals = 5) { return number_format((float)$value, $decimals, ',', '.'); }
function ilot_qty($value) { return (float)str_replace(',', '.', trim((string)$value)); }
function ilot_json($status, $message = '', $extra = array()) {
  header('Content-Type: application/json; charset=utf-8');
  $payload = array('status'=>$status);
  if ($message !== '') $payload['error_message'] = $message;
  foreach ($extra as $k=>$v) $payload[$k] = $v;
  echo json_encode($payload);
  exit;
}
function ilot_filters() {
  return array(
    'tgl_awal' => ilot_valid_date(ilot_input('tgl_awal', date('Y-m-01')), date('Y-m-01')),
    'tgl_akhir' => ilot_valid_date(ilot_input('tgl_akhir', date('Y-m-d')), date('Y-m-d')),
    'material_code' => ilot_input('material_code'),
    'plant_id' => ilot_input('plant_id'),
    'storage_location_id' => ilot_input('storage_location_id'),
    'storage_bin_id' => ilot_input('storage_bin_id'),
    'lot_status' => ilot_input('lot_status'),
    'inspection_origin' => ilot_input('inspection_origin'),
    'keyword' => ilot_input('keyword')
  );
}
function ilot_next_number($date = '') {
  global $db;
  $prefix = 'IL'.date('Ym', strtotime($date ?: date('Y-m-d')));
  $row = $db->fetch("SELECT lot_no FROM erp_inspection_lot WHERE lot_no LIKE ? ORDER BY lot_no DESC LIMIT 1", array($prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{5})$/', $row->lot_no, $m)) $next = ((int)$m[1]) + 1;
  return $prefix.sprintf('%05d', $next);
}
function ilot_status_badge($status) {
  $map = array(
    'CREATED'=>'default','IN_INSPECTION'=>'info','RESULT_RECORDED'=>'warning',
    'UD_ACCEPTED'=>'success','UD_REJECTED'=>'danger','UD_PARTIAL'=>'primary','CANCELLED'=>'default'
  );
  $class = isset($map[$status]) ? $map[$status] : 'default';
  return '<span class="label label-'.$class.'">'.ilot_h($status ?: '-').'</span>';
}
function ilot_origin_label($origin) {
  $labels = array('GOODS_RECEIPT'=>'Goods Receipt','PRODUCTION'=>'Production','MANUAL'=>'Manual','TRANSFER'=>'Transfer','RETURN'=>'Return');
  return isset($labels[$origin]) ? $labels[$origin] : $origin;
}
function ilot_location_text($row) {
  $parts = array();
  if (!empty($row->plant_code)) $parts[] = $row->plant_code;
  if (!empty($row->storage_code)) $parts[] = $row->storage_code;
  if (!empty($row->bin_code)) $parts[] = $row->bin_code;
  return implode(' / ', $parts);
}
function ilot_where($filters, &$params) {
  $where = " WHERE DATE(il.created_at) BETWEEN ? AND ? ";
  $params[] = $filters['tgl_awal'];
  $params[] = $filters['tgl_akhir'];
  if ($filters['material_code'] !== '') { $where .= " AND il.material_code=? "; $params[] = $filters['material_code']; }
  if ($filters['plant_id'] !== '') { $where .= " AND il.plant_id=? "; $params[] = (int)$filters['plant_id']; }
  if ($filters['storage_location_id'] !== '') { $where .= " AND il.storage_location_id=? "; $params[] = (int)$filters['storage_location_id']; }
  if ($filters['storage_bin_id'] !== '') { $where .= " AND il.storage_bin_id=? "; $params[] = (int)$filters['storage_bin_id']; }
  if ($filters['lot_status'] !== '') { $where .= " AND il.lot_status=? "; $params[] = $filters['lot_status']; }
  if ($filters['inspection_origin'] !== '') { $where .= " AND il.inspection_origin=? "; $params[] = $filters['inspection_origin']; }
  if ($filters['keyword'] !== '') {
    $kw = '%'.$filters['keyword'].'%';
    $where .= " AND (il.lot_no LIKE ? OR il.material_code LIKE ? OR il.material_name LIKE ? OR il.source_ref_no LIKE ? OR il.no_aju LIKE ? OR il.no_dokpab LIKE ? OR il.notes LIKE ?) ";
    for ($i=0; $i<7; $i++) $params[] = $kw;
  }
  return $where;
}
function ilot_select_sql() {
  return "SELECT il.*,ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
                 COALESCE(rr.result_count,0) AS result_count,
                 COALESCE(rr.fail_count,0) AS fail_count
          FROM erp_inspection_lot il
          LEFT JOIN erp_plant ep ON ep.id=il.plant_id
          LEFT JOIN erp_storage_location es ON es.id=il.storage_location_id
          LEFT JOIN erp_storage_bin eb ON eb.id=il.storage_bin_id
          LEFT JOIN (
            SELECT inspection_lot_id,COUNT(*) AS result_count,SUM(result_status='FAIL') AS fail_count
            FROM erp_inspection_lot_result GROUP BY inspection_lot_id
          ) rr ON rr.inspection_lot_id=il.id";
}
function ilot_load_rows($db, $filters) {
  $params = array();
  $where = ilot_where($filters, $params);
  $rows = $db->query(ilot_select_sql()." $where ORDER BY il.created_at DESC, il.id DESC", $params);
  return $rows ? iterator_to_array($rows, false) : array();
}
function ilot_fetch($db, $id) {
  return $db->fetch(ilot_select_sql()." WHERE il.id=? LIMIT 1", array((int)$id));
}
function ilot_plants($db) {
  return $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
}
function ilot_storage_locations($db) {
  return $db->query("SELECT s.id,s.plant_id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
}
function ilot_storage_bins($db) {
  return $db->query("SELECT b.id,b.storage_location_id,b.bin_code,b.bin_name,s.storage_code FROM erp_storage_bin b LEFT JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code");
}
function ilot_result_defaults() {
  return array(
    array('characteristic_no'=>'0010','characteristic_name'=>'Visual Inspection','specification'=>'No visual defect','result_status'=>'INFO'),
    array('characteristic_no'=>'0020','characteristic_name'=>'Quantity Check','specification'=>'Qty sesuai dokumen','result_status'=>'INFO'),
    array('characteristic_no'=>'0030','characteristic_name'=>'Packaging / Label','specification'=>'Kemasan dan label sesuai','result_status'=>'INFO'),
    array('characteristic_no'=>'0040','characteristic_name'=>'Customs Document Trace','specification'=>'No Aju / BC / BPB lengkap','result_status'=>'INFO')
  );
}
function ilot_create_default_results($db, $lotId, $sampleQty, $username) {
  foreach (ilot_result_defaults() as $row) {
    $db->insert('erp_inspection_lot_result', array(
      'inspection_lot_id'=>$lotId,
      'characteristic_no'=>$row['characteristic_no'],
      'characteristic_name'=>$row['characteristic_name'],
      'specification'=>$row['specification'],
      'sample_qty'=>$sampleQty,
      'result_status'=>$row['result_status'],
      'recorded_by'=>$username
    ));
  }
}
function ilot_stock_layers($db, $term = '') {
  $params = array();
  $where = " WHERE sl.qty_sisa>0 AND sl.stock_type IN ('QUALITY','BLOCKED') ";
  if ($term !== '') {
    $kw = '%'.$term.'%';
    $where .= " AND (sl.kode LIKE ? OR b.nm_barang LIKE ? OR sl.no_bpb LIKE ? OR sl.no_aju LIKE ? OR sl.no_dokpab LIKE ?) ";
    for ($i=0; $i<5; $i++) $params[] = $kw;
  }
  return $db->query(
    "SELECT sl.*,b.nm_barang,b.satuan,ep.plant_code,es.storage_code,eb.bin_code
     FROM stock_layer sl
     LEFT JOIN barang b ON b.kd_barang=sl.kode
     LEFT JOIN erp_plant ep ON ep.id=sl.plant_id
     LEFT JOIN erp_storage_location es ON es.id=sl.storage_location_id
     LEFT JOIN erp_storage_bin eb ON eb.id=sl.storage_bin_id
     $where
     ORDER BY COALESCE(sl.tgl_masuk,DATE(sl.created_at)) DESC,sl.id DESC
     LIMIT 40",
    $params
  );
}
function ilot_kpi($db) {
  $row = $db->fetch("SELECT COUNT(*) total_lot,SUM(lot_status IN ('CREATED','IN_INSPECTION','RESULT_RECORDED')) open_lot,SUM(lot_status='UD_ACCEPTED') accepted_lot,SUM(lot_status IN ('UD_REJECTED','UD_PARTIAL')) exception_lot,COALESCE(SUM(lot_qty),0) total_qty FROM erp_inspection_lot");
  return $row ?: (object)array('total_lot'=>0,'open_lot'=>0,'accepted_lot'=>0,'exception_lot'=>0,'total_qty'=>0);
}
?>
