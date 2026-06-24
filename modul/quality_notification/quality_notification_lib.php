<?php
require_once dirname(__DIR__)."/inspection_lot/inspection_lot_lib.php";

function qn_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}
function qn_valid_date($date, $default) {
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date) ? $date : $default;
}
function qn_num($value, $decimals = 5) { return number_format((float)$value, $decimals, ',', '.'); }
function qn_qty($value) { return (float)str_replace(',', '.', trim((string)$value)); }
function qn_filters() {
  return array(
    'tgl_awal' => qn_valid_date(qn_input('tgl_awal', date('Y-m-01')), date('Y-m-01')),
    'tgl_akhir' => qn_valid_date(qn_input('tgl_akhir', date('Y-m-d')), date('Y-m-d')),
    'material_code' => qn_input('material_code'),
    'status' => qn_input('status'),
    'severity' => qn_input('severity'),
    'source_type' => qn_input('source_type'),
    'responsible_user' => qn_input('responsible_user'),
    'keyword' => qn_input('keyword')
  );
}
function qn_next_number($date = '') {
  global $db;
  $prefix = 'NCR'.date('Ym', strtotime($date ?: date('Y-m-d')));
  $row = $db->fetch("SELECT notification_no FROM erp_quality_notification WHERE notification_no LIKE ? ORDER BY notification_no DESC LIMIT 1", array($prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{5})$/', $row->notification_no, $m)) $next = ((int)$m[1]) + 1;
  return $prefix.sprintf('%05d', $next);
}
function qn_status_badge($status) {
  $map = array('OPEN'=>'danger','IN_REVIEW'=>'warning','CONTAINED'=>'info','CAPA_REQUIRED'=>'primary','CAPA_IN_PROGRESS'=>'primary','CLOSED'=>'success','CANCELLED'=>'default');
  $class = isset($map[$status]) ? $map[$status] : 'default';
  return '<span class="label label-'.$class.'">'.ilot_h($status ?: '-').'</span>';
}
function qn_severity_badge($severity) {
  $map = array('LOW'=>'success','MEDIUM'=>'info','HIGH'=>'warning','CRITICAL'=>'danger');
  $class = isset($map[$severity]) ? $map[$severity] : 'default';
  return '<span class="label label-'.$class.'">'.ilot_h($severity ?: '-').'</span>';
}
function qn_where($filters, &$params) {
  $where = " WHERE DATE(qn.created_at) BETWEEN ? AND ? ";
  $params[] = $filters['tgl_awal'];
  $params[] = $filters['tgl_akhir'];
  if ($filters['material_code'] !== '') { $where .= " AND qn.material_code=? "; $params[] = $filters['material_code']; }
  if ($filters['status'] !== '') { $where .= " AND qn.status=? "; $params[] = $filters['status']; }
  if ($filters['severity'] !== '') { $where .= " AND qn.severity=? "; $params[] = $filters['severity']; }
  if ($filters['source_type'] !== '') { $where .= " AND qn.source_type=? "; $params[] = $filters['source_type']; }
  if ($filters['responsible_user'] !== '') { $where .= " AND qn.responsible_user=? "; $params[] = $filters['responsible_user']; }
  if ($filters['keyword'] !== '') {
    $kw = '%'.$filters['keyword'].'%';
    $where .= " AND (qn.notification_no LIKE ? OR qn.material_code LIKE ? OR qn.material_name LIKE ? OR qn.source_ref_no LIKE ? OR qn.defect_description LIKE ? OR qn.defect_code LIKE ? OR qn.no_aju LIKE ? OR qn.no_dokpab LIKE ?) ";
    for ($i=0; $i<8; $i++) $params[] = $kw;
  }
  return $where;
}
function qn_select_sql() {
  return "SELECT qn.*,il.lot_no,il.inspection_origin,il.inspection_type,ep.plant_code,ep.plant_name,es.storage_code,es.storage_name,eb.bin_code,eb.bin_name,
                 COALESCE(qa.action_count,0) AS action_count
          FROM erp_quality_notification qn
          LEFT JOIN erp_inspection_lot il ON il.id=qn.inspection_lot_id
          LEFT JOIN erp_plant ep ON ep.id=qn.plant_id
          LEFT JOIN erp_storage_location es ON es.id=qn.storage_location_id
          LEFT JOIN erp_storage_bin eb ON eb.id=qn.storage_bin_id
          LEFT JOIN (SELECT notification_id,COUNT(*) action_count FROM erp_quality_notification_action GROUP BY notification_id) qa ON qa.notification_id=qn.id";
}
function qn_load_rows($db, $filters) {
  $params = array();
  $where = qn_where($filters, $params);
  $rows = $db->query(qn_select_sql()." $where ORDER BY qn.created_at DESC, qn.id DESC", $params);
  return $rows ? iterator_to_array($rows, false) : array();
}
function qn_fetch($db, $id) {
  return $db->fetch(qn_select_sql()." WHERE qn.id=? LIMIT 1", array((int)$id));
}
function qn_kpi($db) {
  $row = $db->fetch("SELECT COUNT(*) total,SUM(status IN ('OPEN','IN_REVIEW','CONTAINED','CAPA_REQUIRED','CAPA_IN_PROGRESS')) open_count,SUM(severity='CRITICAL') critical_count,SUM(status='CLOSED') closed_count,SUM(due_date IS NOT NULL AND due_date<CURDATE() AND status<>'CLOSED') overdue_count FROM erp_quality_notification");
  return $row ?: (object)array('total'=>0,'open_count'=>0,'critical_count'=>0,'closed_count'=>0,'overdue_count'=>0);
}
function qn_responsible_users($db) {
  return $db->query("SELECT DISTINCT username FROM sys_users WHERE username IS NOT NULL AND username<>'' ORDER BY username");
}
function qn_material_search($db, $term) {
  $params = array();
  $where = " WHERE b.status=1 ";
  if ($term !== '') { $where .= " AND (b.kd_barang LIKE ? OR b.nm_barang LIKE ?) "; $params[]='%'.$term.'%'; $params[]='%'.$term.'%'; }
  return $db->query("SELECT b.kd_barang,b.nm_barang,b.satuan FROM barang b $where ORDER BY b.kd_barang LIMIT 30", $params);
}
function qn_failed_inspection_candidates($db, $term = '') {
  $params = array();
  $where = " WHERE r.result_status='FAIL' ";
  if ($term !== '') {
    $kw = '%'.$term.'%';
    $where .= " AND (il.lot_no LIKE ? OR il.material_code LIKE ? OR il.material_name LIKE ? OR r.characteristic_name LIKE ? OR r.defect_code LIKE ?) ";
    for ($i=0; $i<5; $i++) $params[] = $kw;
  }
  return $db->query(
    "SELECT r.id AS result_id,r.inspection_lot_id,r.characteristic_name,r.defect_code,r.defect_qty,r.remarks,
            il.lot_no,il.material_code,il.material_name,il.uom,il.plant_id,il.storage_location_id,il.storage_bin_id,il.no_aju,il.jenis_dokpab,il.no_dokpab,il.no_bpb
     FROM erp_inspection_lot_result r
     JOIN erp_inspection_lot il ON il.id=r.inspection_lot_id
     LEFT JOIN erp_quality_notification qn ON qn.source_type='INSPECTION_LOT' AND qn.source_ref_id=r.id
     $where AND qn.id IS NULL
     ORDER BY r.recorded_at DESC,r.id DESC LIMIT 40",
    $params
  );
}
function qn_ng_candidates($db, $term = '') {
  $params = array();
  $where = " WHERE 1=1 ";
  if ($term !== '') {
    $kw = '%'.$term.'%';
    $where .= " AND (d.kd_barang LIKE ? OR b.nm_barang LIKE ? OR d.ket LIKE ? OR n.catatan LIKE ?) ";
    for ($i=0; $i<4; $i++) $params[] = $kw;
  }
  return $db->query(
    "SELECT d.*,n.user AS reporter,n.catatan,b.nm_barang,b.satuan
     FROM data_ng d
     LEFT JOIN ng n ON n.id=d.id_ng
     LEFT JOIN barang b ON b.kd_barang=d.kd_barang
     LEFT JOIN erp_quality_notification qn ON qn.source_type='NG' AND qn.source_ref_id=d.id
     $where AND qn.id IS NULL
     ORDER BY d.tgl_produksi DESC,d.id DESC LIMIT 40",
    $params
  );
}
function qn_insert_action($db, $id, $type, $text, $username) {
  return $db->insert('erp_quality_notification_action', array('notification_id'=>$id,'action_type'=>$type,'action_text'=>$text,'action_by'=>$username));
}
?>
