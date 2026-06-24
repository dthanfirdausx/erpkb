<?php
if (!function_exists('hr_t')) {
  function hr_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('hr_h')) {
  function hr_h($key, $fallback = '') { return htmlspecialchars((string) hr_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('hr_js')) {
  function hr_js($key, $fallback = '') { return json_encode(hr_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";

function tra_json($status, $message = '', $extra = array())
{
  header('Content-Type: application/json; charset=utf-8');
  $payload = array('status' => $status);
  if ($message !== '') $payload[$status === 'good' ? 'message' : 'error_message'] = $message;
  foreach ($extra as $k => $v) $payload[$k] = $v;
  echo json_encode($payload);
  exit;
}
function tra_h($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function tra_user(){ return isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'admin'; }
function tra_valid_date($value, $fallback){ $value = trim((string)$value); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback; }
function tra_code($value){ return strtoupper(trim((string)$value)); }
function tra_in_placeholders($count){ return implode(',', array_fill(0, max(1, (int)$count), '?')); }
function tra_manager_scope($db)
{
  $userId = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;
  $manager = $userId ? $db->fetch("SELECT * FROM erp_employee_master WHERE user_id=? LIMIT 1", array($userId)) : null;
  if (!$manager) return array(null, array());
  $stmt = $db->query("SELECT id FROM erp_employee_master WHERE manager_employee_id=? ORDER BY id", array((int)$manager->id));
  $ids = array();
  if ($stmt) foreach ($stmt as $r) $ids[] = (int)$r->id;
  return array($manager, $ids);
}
function tra_history($db, $id, $old, $new, $note, $user)
{
  $db->insert('erp_employee_request_history', array(
    'request_id'=>(int)$id,
    'previous_status'=>$old,
    'new_status'=>$new,
    'action_note'=>$note,
    'action_by'=>$user,
    'action_at'=>date('Y-m-d H:i:s')
  ));
}
function tra_request_row($db, $id, $subIds)
{
  if (empty($subIds)) return null;
  $params = $subIds;
  array_unshift($params, (int)$id);
  return $db->fetch("SELECT r.*, e.full_name, e.employee_group, d.nm_dept, jt.job_title_name, hr.employee_no hr_no, hr.full_name hr_name
    FROM erp_employee_request r
    JOIN erp_employee_master e ON e.id=r.employee_id
    LEFT JOIN dept d ON d.kd_dept=r.department_code
    LEFT JOIN erp_job_title jt ON jt.id=r.job_title_id
    LEFT JOIN erp_employee_master hr ON hr.id=r.hr_reviewer_employee_id
    WHERE r.id=? AND r.employee_id IN (".tra_in_placeholders(count($subIds)).")
    LIMIT 1", $params);
}
function tra_filter_where($src, $subIds, &$params)
{
  $from = tra_valid_date(isset($src['tgl_awal']) ? $src['tgl_awal'] : '', date('Y-01-01'));
  $to = tra_valid_date(isset($src['tgl_akhir']) ? $src['tgl_akhir'] : '', date('Y-m-d'));
  if (strtotime($from) > strtotime($to)) $from = $to;
  $params = $subIds;
  $where = " WHERE r.employee_id IN (".tra_in_placeholders(count($subIds)).") AND r.request_date BETWEEN ? AND ? ";
  $params[] = $from; $params[] = $to;
  foreach (array('department_code','request_category','workflow_status','priority') as $key) {
    if (isset($src[$key]) && trim($src[$key]) !== '') { $where .= " AND r.$key=? "; $params[] = trim($src[$key]); }
  }
  $employeeId = isset($src['employee_id']) ? (int)$src['employee_id'] : 0;
  if ($employeeId && in_array($employeeId, $subIds, true)) { $where .= " AND r.employee_id=? "; $params[] = $employeeId; }
  if (isset($src['keyword']) && trim($src['keyword']) !== '') {
    $kw = '%'.trim($src['keyword']).'%';
    $where .= " AND (r.request_no LIKE ? OR r.employee_no LIKE ? OR e.full_name LIKE ? OR r.subject LIKE ? OR r.request_type LIKE ? OR r.description LIKE ?) ";
    array_push($params, $kw, $kw, $kw, $kw, $kw, $kw);
  }
  return array($where, $from, $to);
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

if ($act === 'decision') {
  session_check_json();
  list($manager, $subIds) = tra_manager_scope($db);
  if (!$manager || empty($subIds)) tra_json('error', 'Data manager atau bawahan tidak ditemukan.');
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $decision = tra_code(isset($_POST['decision']) ? $_POST['decision'] : '');
  $note = trim(isset($_POST['note']) ? $_POST['note'] : '');
  $row = tra_request_row($db, $id, $subIds);
  if (!$row) tra_json('error', 'Request tidak ditemukan atau bukan bawahan manager ini.');
  if ($row->workflow_status !== 'SUBMITTED' || $row->approval_level !== 'MANAGER') tra_json('error', 'Hanya request SUBMITTED step MANAGER yang bisa diproses.');
  if (!in_array($decision, array('APPROVE','REJECT','RETURN'), true)) tra_json('error', 'Decision tidak valid.');
  if ($decision !== 'APPROVE' && $note === '') tra_json('error', 'Catatan wajib diisi untuk Return atau Reject.');
  $username = tra_user();
  $newStatus = $decision === 'APPROVE' ? 'MANAGER_APPROVED' : ($decision === 'REJECT' ? 'REJECTED' : 'RETURNED');
  $newDecision = $decision === 'APPROVE' ? 'APPROVED' : ($decision === 'REJECT' ? 'REJECTED' : 'RETURNED');
  $newLevel = $decision === 'APPROVE' ? 'HR' : 'FINAL';
  $ok = $db->update('erp_employee_request', array(
    'workflow_status'=>$newStatus,
    'approval_level'=>$newLevel,
    'decision'=>$newDecision,
    'decision_by'=>$username,
    'decision_at'=>date('Y-m-d H:i:s'),
    'approver_employee_id'=>(int)$manager->id,
    'manager_note'=>$note,
    'updated_by'=>$username,
    'updated_at'=>date('Y-m-d H:i:s')
  ), 'id', (int)$row->id);
  if (!$ok || $db->getErrorMessage() !== '') tra_json('error', $db->getErrorMessage() ?: 'Request gagal diproses.');
  tra_history($db, $row->id, $row->workflow_status, $newStatus, $note ?: 'Manager '.$newDecision, $username);
  if (function_exists('simpan_log')) simpan_log('User '.$username.' '.$newDecision.' employee request '.$row->request_no.' untuk '.$row->employee_no.' pada '.date('Y-m-d H:i:s'), $username);
  tra_json('good', 'Request '.$row->request_no.' berhasil diproses.');
}

if ($act === 'detail') {
  session_check_json();
  list($manager, $subIds) = tra_manager_scope($db);
  $row = tra_request_row($db, isset($_POST['id']) ? (int)$_POST['id'] : 0, $subIds);
  if (!$row) { echo '<div class="alert alert-warning">Request tidak ditemukan.</div>'; exit; }
  $hist = $db->query("SELECT * FROM erp_employee_request_history WHERE request_id=? ORDER BY action_at,id", array((int)$row->id));
  echo '<h3 style="margin-top:0">'.tra_h($row->request_no).' <small>'.tra_h($row->employee_no.' - '.$row->full_name).'</small></h3>';
  echo '<span class="label label-info">'.tra_h($row->request_category).'</span> <span class="label label-primary">'.tra_h($row->priority).'</span> <span class="label label-success">'.tra_h($row->workflow_status).'</span><hr>';
  echo '<div class="row"><div class="col-sm-3"><b>Request Date</b><br>'.tra_h($row->request_date).'</div><div class="col-sm-3"><b>Required Date</b><br>'.tra_h($row->required_date ?: '-').'</div><div class="col-sm-3"><b>'.hr_h('hr_department', 'Department').'</b><br>'.tra_h(($row->department_code ?: '-').' - '.($row->nm_dept ?: '-')).'</div><div class="col-sm-3"><b>Decision</b><br>'.tra_h($row->decision).'</div></div><hr>';
  echo '<b>Subject</b><p>'.tra_h($row->subject).'</p><b>'.hr_h('hr_description', 'Description').'</b><p>'.nl2br(tra_h($row->description ?: '-')).'</p><b>Manager / HR / Resolution Note</b><p>'.nl2br(tra_h($row->manager_note ?: $row->hr_note ?: $row->resolution_note ?: '-')).'</p>';
  echo '<h4>History</h4><div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>From</th><th>To</th><th>By</th><th>At</th><th>Note</th></tr></thead><tbody>';
  $n = 0;
  foreach ($hist as $h) { $n++; echo '<tr><td>'.tra_h($h->previous_status ?: '-').'</td><td>'.tra_h($h->new_status).'</td><td>'.tra_h($h->action_by).'</td><td>'.tra_h($h->action_at).'</td><td>'.tra_h($h->action_note).'</td></tr>'; }
  if (!$n) echo '<tr><td colspan="5" class="text-muted text-center">Belum ada history.</td></tr>';
  echo '</tbody></table></div>';
  exit;
}

if ($act === 'export') {
  session_check();
  list($manager, $subIds) = tra_manager_scope($db);
  if (!$manager || empty($subIds)) { echo 'Data manager atau bawahan tidak ditemukan.'; exit; }
  $initial = ob_get_level(); ob_start();
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require_once "../../inc/lib/PHPExcel.php";
  require_once "../../inc/excel_style_helper.php";
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $params = array();
  list($where, $from, $to) = tra_filter_where($_GET, $subIds, $params);
  $rows = $db->query("SELECT r.*, e.full_name, e.employee_group, d.nm_dept, jt.job_title_name
    FROM erp_employee_request r
    JOIN erp_employee_master e ON e.id=r.employee_id
    LEFT JOIN dept d ON d.kd_dept=r.department_code
    LEFT JOIN erp_job_title jt ON jt.id=r.job_title_id
    $where ORDER BY r.request_date DESC, r.request_no DESC", $params);
  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Team Request Approval'));
  $heads = array(erp_export_label("No"),erp_export_label("Request No"),erp_export_label("Date"),erp_export_label("Required Date"),erp_export_label("Employee No"),erp_export_label("Employee Name"),erp_export_label("Department"),erp_export_label("Job Title"),erp_export_label("Category"),erp_export_label("Type"),erp_export_label("Priority"),erp_export_label("Subject"),erp_export_label("Status"),erp_export_label("Approval Level"),erp_export_label("Decision"),erp_export_label("Decision By"),erp_export_label("Decision At"),erp_export_label("Attachment"),erp_export_label("Description"),erp_export_label("Manager Note"),erp_export_label("HR Note"),erp_export_label("Resolution"),erp_export_label("SAP Ref"),erp_export_label("Remarks"),erp_export_label("Updated By"));
  foreach ($heads as $i=>$h) $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4', $h);
  $rn = 5; $no = 1;
  foreach ($rows as $r) {
    $vals = array($no++, $r->request_no, $r->request_date, $r->required_date, $r->employee_no, $r->full_name, ($r->department_code ?: '').' - '.($r->nm_dept ?: ''), $r->job_title_name, $r->request_category, $r->request_type, $r->priority, $r->subject, $r->workflow_status, $r->approval_level, $r->decision, $r->decision_by, $r->decision_at, $r->attachment_ref, $r->description, $r->manager_note, $r->hr_note, $r->resolution_note, $r->sap_reference, $r->remarks, $r->updated_by ?: $r->created_by);
    foreach ($vals as $i=>$v) $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn, $v);
    $rn++;
  }
  erpkb_excel_apply_standard_style($excel, array(
    'sheet'=>$sheet,
    'title'=>erp_export_title('TEAM REQUEST APPROVAL'),
    'header_row'=>4,
    'first_data_row'=>5,
    'last_data_row'=>max(5, $rn-1),
    'column_count'=>count($heads),
    'filters'=>array('Manager'=>$manager->employee_no.' - '.$manager->full_name, 'Period'=>$from.' s/d '.$to, 'Status'=>isset($_GET['workflow_status']) && $_GET['workflow_status'] !== '' ? $_GET['workflow_status'] : erp_export_all_text()),
    'widths'=>array('B'=>20,'F'=>28,'G'=>26,'H'=>24,'I'=>24,'J'=>24,'L'=>36,'S'=>44,'T'=>34,'U'=>34,'V'=>34,'X'=>32)
  ));
  $tmp = erpkb_excel_temp_file('team_request_approval_');
  PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
  $size = @filesize($tmp); $sig = @file_get_contents($tmp, false, null, 0, 2);
  if (!$size || $sig !== 'PK') { @unlink($tmp); while(ob_get_level()>$initial) ob_end_clean(); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit; }
  while(ob_get_level()>$initial) ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="team_request_approval_'.date('Ymd_His').'.xlsx"');
  header('Content-Length: '.$size);
  header('Cache-Control: max-age=0');
  header('Pragma: public');
  readfile($tmp); @unlink($tmp); exit;
}

echo 'Action tidak dikenal.';
?>
