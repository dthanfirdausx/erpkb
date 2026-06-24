<?php
require_once dirname(__DIR__)."/quality_notification/quality_notification_lib.php";

function capa_input($key, $default = '') {
  if (isset($_POST[$key])) return trim((string)$_POST[$key]);
  if (isset($_GET[$key])) return trim((string)$_GET[$key]);
  return $default;
}
function capa_valid_date($date, $default = null) {
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date) ? $date : $default;
}
function capa_qty($value) { return (float)str_replace(',', '.', trim((string)$value)); }
function capa_h($value) { return ilot_h($value); }
function capa_num($value, $decimals = 0) { return number_format((float)$value, $decimals, ',', '.'); }
function capa_next_number($date = '') {
  global $db;
  $prefix = 'CAPA'.date('Ym', strtotime($date ?: date('Y-m-d')));
  $row = $db->fetch("SELECT capa_no FROM erp_capa WHERE capa_no LIKE ? ORDER BY capa_no DESC LIMIT 1", array($prefix.'%'));
  $next = 1;
  if ($row && preg_match('/(\d{5})$/', $row->capa_no, $m)) $next = ((int)$m[1]) + 1;
  return $prefix.sprintf('%05d', $next);
}
function capa_status_badge($status) {
  $map = array('DRAFT'=>'default','OPEN'=>'danger','IN_PROGRESS'=>'warning','WAITING_VERIFICATION'=>'info','EFFECTIVE'=>'success','INEFFECTIVE'=>'danger','CLOSED'=>'success','CANCELLED'=>'default');
  $class = isset($map[$status]) ? $map[$status] : 'default';
  return '<span class="label label-'.$class.'">'.capa_h($status ?: '-').'</span>';
}
function capa_risk_badge($risk) {
  $map = array('LOW'=>'success','MEDIUM'=>'info','HIGH'=>'warning','CRITICAL'=>'danger');
  $class = isset($map[$risk]) ? $map[$risk] : 'default';
  return '<span class="label label-'.$class.'">'.capa_h($risk ?: '-').'</span>';
}
function capa_filters() {
  return array(
    'tgl_awal' => capa_valid_date(capa_input('tgl_awal', date('Y-m-01')), date('Y-m-01')),
    'tgl_akhir' => capa_valid_date(capa_input('tgl_akhir', date('Y-m-d')), date('Y-m-d')),
    'status' => capa_input('status'),
    'risk_level' => capa_input('risk_level'),
    'owner_user' => capa_input('owner_user'),
    'source_type' => capa_input('source_type'),
    'keyword' => capa_input('keyword')
  );
}
function capa_select_sql() {
  return "SELECT c.*, qn.severity AS notification_severity, qn.source_ref_no, qn.no_aju, qn.jenis_dokpab, qn.no_dokpab,
                 COALESCE(a.action_count,0) AS action_count
          FROM erp_capa c
          LEFT JOIN erp_quality_notification qn ON qn.id=c.notification_id
          LEFT JOIN (SELECT capa_id,COUNT(*) action_count FROM erp_capa_action GROUP BY capa_id) a ON a.capa_id=c.id";
}
function capa_where($filters, &$params) {
  $where = " WHERE DATE(c.created_at) BETWEEN ? AND ? ";
  $params[] = $filters['tgl_awal'];
  $params[] = $filters['tgl_akhir'];
  if ($filters['status'] !== '') { $where .= " AND c.status=? "; $params[] = $filters['status']; }
  if ($filters['risk_level'] !== '') { $where .= " AND c.risk_level=? "; $params[] = $filters['risk_level']; }
  if ($filters['owner_user'] !== '') { $where .= " AND c.owner_user=? "; $params[] = $filters['owner_user']; }
  if ($filters['source_type'] !== '') { $where .= " AND c.source_type=? "; $params[] = $filters['source_type']; }
  if ($filters['keyword'] !== '') {
    $kw = '%'.$filters['keyword'].'%';
    $where .= " AND (c.capa_no LIKE ? OR c.notification_no LIKE ? OR c.material_code LIKE ? OR c.material_name LIKE ? OR c.problem_statement LIKE ? OR c.root_cause LIKE ? OR c.corrective_action LIKE ? OR c.preventive_action LIKE ?) ";
    for ($i=0; $i<8; $i++) $params[] = $kw;
  }
  return $where;
}
function capa_load_rows($db, $filters) {
  $params = array();
  $where = capa_where($filters, $params);
  $rows = $db->query(capa_select_sql()." $where ORDER BY c.created_at DESC,c.id DESC", $params);
  return $rows ? iterator_to_array($rows, false) : array();
}
function capa_fetch($db, $id) {
  return $db->fetch(capa_select_sql()." WHERE c.id=? LIMIT 1", array((int)$id));
}
function capa_kpi($db) {
  $row = $db->fetch("SELECT COUNT(*) total,
    SUM(status IN ('OPEN','IN_PROGRESS','WAITING_VERIFICATION')) open_count,
    SUM(status='WAITING_VERIFICATION') verification_count,
    SUM(risk_level IN ('HIGH','CRITICAL') AND status NOT IN ('CLOSED','CANCELLED')) high_risk_count,
    SUM(due_date IS NOT NULL AND due_date<CURDATE() AND status NOT IN ('CLOSED','CANCELLED')) overdue_count
    FROM erp_capa");
  return $row ?: (object)array('total'=>0,'open_count'=>0,'verification_count'=>0,'high_risk_count'=>0,'overdue_count'=>0);
}
function capa_users($db) {
  $rows = $db->query("SELECT DISTINCT username FROM sys_users WHERE username IS NOT NULL AND username<>'' ORDER BY username");
  return $rows ? iterator_to_array($rows, false) : array();
}
function capa_qn_candidates($db, $term = '') {
  $params = array();
  $where = " WHERE qn.status IN ('CAPA_REQUIRED','CAPA_IN_PROGRESS','CONTAINED','IN_REVIEW','OPEN') ";
  if ($term !== '') {
    $kw = '%'.$term.'%';
    $where .= " AND (qn.notification_no LIKE ? OR qn.material_code LIKE ? OR qn.material_name LIKE ? OR qn.defect_description LIKE ? OR qn.defect_code LIKE ?) ";
    for ($i=0; $i<5; $i++) $params[] = $kw;
  }
  return $db->query(
    "SELECT qn.*
     FROM erp_quality_notification qn
     LEFT JOIN erp_capa c ON c.notification_id=qn.id AND c.status<>'CANCELLED'
     $where AND c.id IS NULL
     ORDER BY qn.severity='CRITICAL' DESC, qn.created_at DESC, qn.id DESC LIMIT 40",
    $params
  );
}
function capa_insert_action($db, $id, $type, $text, $username) {
  return $db->insert('erp_capa_action', array('capa_id'=>$id,'action_type'=>$type,'action_text'=>$text,'action_by'=>$username));
}
function capa_sync_notification_status($db, $notificationId, $status, $username) {
  $notificationId = (int)$notificationId;
  if ($notificationId <= 0) return true;
  $qnStatus = null;
  if (in_array($status, array('OPEN','IN_PROGRESS','WAITING_VERIFICATION','INEFFECTIVE'), true)) $qnStatus = 'CAPA_IN_PROGRESS';
  if (in_array($status, array('EFFECTIVE','CLOSED'), true)) $qnStatus = 'CLOSED';
  if ($status === 'CANCELLED') $qnStatus = 'CAPA_REQUIRED';
  if (!$qnStatus) return true;
  return $db->update('erp_quality_notification', array('status'=>$qnStatus,'updated_by'=>$username), 'id', $notificationId);
}
?>
