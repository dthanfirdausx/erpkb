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

function tla_json($status, $message = '', $extra = array())
{
  header('Content-Type: application/json; charset=utf-8');
  $payload = array('status' => $status);
  if ($message !== '') $payload[$status === 'good' ? 'message' : 'error_message'] = $message;
  foreach ($extra as $k => $v) $payload[$k] = $v;
  echo json_encode($payload);
  exit;
}
function tla_h($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function tla_user(){ return isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'admin'; }
function tla_valid_date($value, $fallback){ $value = trim((string)$value); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback; }
function tla_code($value){ return strtoupper(trim((string)$value)); }
function tla_in_placeholders($count){ return implode(',', array_fill(0, max(1, (int)$count), '?')); }
function tla_manager_scope($db)
{
  $userId = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;
  $manager = $userId ? $db->fetch("SELECT * FROM erp_employee_master WHERE user_id=? LIMIT 1", array($userId)) : null;
  if (!$manager) return array(null, array());
  $stmt = $db->query("SELECT id FROM erp_employee_master WHERE manager_employee_id=? ORDER BY id", array((int)$manager->id));
  $ids = array();
  if ($stmt) foreach ($stmt as $r) $ids[] = (int)$r->id;
  return array($manager, $ids);
}
function tla_next_no($db)
{
  $prefix = 'LVA'.date('Ym');
  $row = $db->fetch("SELECT approval_no FROM erp_leave_approval WHERE approval_no LIKE ? ORDER BY approval_no DESC LIMIT 1", array($prefix.'%'));
  $next = $row ? ((int)substr($row->approval_no, -4) + 1) : 1;
  return $prefix.sprintf('%04d', $next);
}
function tla_request_row($db, $id, $subIds)
{
  if (empty($subIds)) return null;
  $params = $subIds;
  array_unshift($params, (int)$id);
  return $db->fetch("SELECT l.*, e.employee_no, e.full_name, d.nm_dept, j.job_title_name, h.employee_no handover_no, h.full_name handover_name
    FROM erp_leave_request l
    JOIN erp_employee_master e ON e.id=l.employee_id
    LEFT JOIN dept d ON d.kd_dept=l.department_code
    LEFT JOIN erp_job_title j ON j.id=l.job_title_id
    LEFT JOIN erp_employee_master h ON h.id=l.handover_to_employee_id
    WHERE l.id=? AND l.employee_id IN (".tla_in_placeholders(count($subIds)).")
    LIMIT 1", $params);
}
function tla_filter_where($src, $subIds, &$params)
{
  $from = tla_valid_date(isset($src['tgl_awal']) ? $src['tgl_awal'] : '', date('Y-01-01'));
  $to = tla_valid_date(isset($src['tgl_akhir']) ? $src['tgl_akhir'] : '', date('Y-m-d'));
  if (strtotime($from) > strtotime($to)) $from = $to;
  $params = $subIds;
  $where = " WHERE l.employee_id IN (".tla_in_placeholders(count($subIds)).") AND l.start_date<=? AND l.end_date>=? ";
  $params[] = $to; $params[] = $from;
  foreach (array('workflow_status','leave_type','department_code') as $key) {
    if (isset($src[$key]) && trim($src[$key]) !== '') { $where .= " AND l.$key=? "; $params[] = trim($src[$key]); }
  }
  $employeeId = isset($src['employee_id']) ? (int)$src['employee_id'] : 0;
  if ($employeeId && in_array($employeeId, $subIds, true)) { $where .= " AND l.employee_id=? "; $params[] = $employeeId; }
  if (isset($src['keyword']) && trim($src['keyword']) !== '') {
    $kw = '%'.trim($src['keyword']).'%';
    $where .= " AND (l.leave_no LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ? OR l.reason LIKE ? OR l.remarks LIKE ?) ";
    array_push($params, $kw, $kw, $kw, $kw, $kw);
  }
  return array($where, $from, $to);
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

if ($act === 'decision') {
  session_check_json();
  list($manager, $subIds) = tla_manager_scope($db);
  if (!$manager || empty($subIds)) tla_json('error', 'Data manager atau bawahan tidak ditemukan.');
  $id = isset($_POST['leave_request_id']) ? (int)$_POST['leave_request_id'] : 0;
  $decision = tla_code(isset($_POST['decision']) ? $_POST['decision'] : '');
  $note = trim(isset($_POST['approval_note']) ? $_POST['approval_note'] : '');
  $row = tla_request_row($db, $id, $subIds);
  if (!$row) tla_json('error', 'Leave request tidak ditemukan atau bukan bawahan manager ini.');
  if ($row->workflow_status !== 'SUBMITTED' || $row->approval_level !== 'MANAGER') tla_json('error', 'Hanya leave status SUBMITTED step MANAGER yang bisa diproses dari Team Leave Approval.');
  if (!in_array($decision, array('APPROVE','REJECT','RETURN'), true)) tla_json('error', 'Decision tidak valid.');
  if ($decision !== 'APPROVE' && $note === '') tla_json('error', 'Catatan wajib diisi untuk Return atau Reject.');

  $newStatus = $decision === 'APPROVE' ? 'MANAGER_APPROVED' : ($decision === 'REJECT' ? 'REJECTED' : 'RETURNED');
  $newDecision = $decision === 'APPROVE' ? 'APPROVED' : ($decision === 'REJECT' ? 'REJECTED' : 'RETURNED');
  $newLevel = $decision === 'APPROVE' ? 'HR' : 'FINAL';
  $username = tla_user();
  $approvalNo = tla_next_no($db);
  $ok = $db->insert('erp_leave_approval', array(
    'approval_no' => $approvalNo,
    'leave_request_id' => (int)$row->id,
    'approval_step' => 'MANAGER',
    'approver_employee_id' => (int)$manager->id,
    'decision' => $newDecision,
    'decision_date' => date('Y-m-d H:i:s'),
    'approval_note' => $note,
    'previous_status' => $row->workflow_status,
    'new_status' => $newStatus,
    'created_by' => $username,
    'updated_by' => $username,
    'updated_at' => date('Y-m-d H:i:s')
  ));
  if (!$ok || $db->getErrorMessage() !== '') tla_json('error', $db->getErrorMessage() ?: 'Approval gagal disimpan.');

  $db->update('erp_leave_request', array(
    'workflow_status' => $newStatus,
    'approval_level' => $newLevel,
    'decision' => $newDecision,
    'decision_by' => $username,
    'decision_at' => date('Y-m-d H:i:s'),
    'approver_employee_id' => (int)$manager->id,
    'approver_note' => $note,
    'updated_by' => $username,
    'updated_at' => date('Y-m-d H:i:s')
  ), 'id', (int)$row->id);
  if ($db->getErrorMessage() !== '') tla_json('error', $db->getErrorMessage());
  if (function_exists('simpan_log')) simpan_log('User '.$username.' '.$newDecision.' leave request '.$row->leave_no.' untuk '.$row->employee_no.' pada '.date('Y-m-d H:i:s'), $username);
  tla_json('good', 'Leave request '.$row->leave_no.' berhasil diproses.', array('approval_no' => $approvalNo));
}

if ($act === 'detail') {
  session_check_json();
  list($manager, $subIds) = tla_manager_scope($db);
  $row = tla_request_row($db, isset($_POST['id']) ? (int)$_POST['id'] : 0, $subIds);
  if (!$row) { echo '<div class="alert alert-warning">Leave request tidak ditemukan.</div>'; exit; }
  $logs = $db->query("SELECT a.*, e.employee_no, e.full_name
    FROM erp_leave_approval a
    LEFT JOIN erp_employee_master e ON e.id=a.approver_employee_id
    WHERE a.leave_request_id=?
    ORDER BY a.id", array((int)$row->id));
  echo '<h3 style="margin-top:0">'.tla_h($row->leave_no).' <small>'.tla_h($row->employee_no.' - '.$row->full_name).'</small></h3>';
  echo '<span class="label label-info">'.tla_h($row->leave_type).'</span> <span class="label label-primary">'.tla_h($row->workflow_status).'</span><hr>';
  echo '<div class="row"><div class="col-sm-3"><b>'.hr_h('hr_period', 'Period').'</b><br>'.tla_h($row->start_date.' s/d '.$row->end_date).'</div><div class="col-sm-3"><b>Total Days</b><br>'.tla_h($row->total_days).'</div><div class="col-sm-3"><b>'.hr_h('hr_department', 'Department').'</b><br>'.tla_h(($row->department_code ?: '-').' - '.($row->nm_dept ?: '-')).'</div><div class="col-sm-3"><b>Handover</b><br>'.tla_h($row->handover_no ? $row->handover_no.' - '.$row->handover_name : '-').'</div></div><hr>';
  echo '<b>Reason</b><p>'.nl2br(tla_h($row->reason ?: '-')).'</p>';
  echo '<b>Manager Note</b><p>'.nl2br(tla_h($row->approver_note ?: '-')).'</p>';
  echo '<h4>Approval History</h4><div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>'.hr_h('common_no', 'No').'</th><th>Step</th><th>Decision</th><th>'.hr_h('common_status', 'Status').'</th><th>Approver</th><th>'.hr_h('hr_date', 'Date').'</th><th>Note</th></tr></thead><tbody>';
  $no = 1;
  foreach ($logs as $l) {
    echo '<tr><td>'.$no++.'</td><td>'.tla_h($l->approval_step).'</td><td>'.tla_h($l->decision).'</td><td>'.tla_h(($l->previous_status ?: '-').' -> '.($l->new_status ?: '-')).'</td><td>'.tla_h(trim(($l->employee_no ?: '').' '.($l->full_name ?: '')) ?: '-').'</td><td>'.tla_h($l->decision_date ?: '-').'</td><td>'.tla_h($l->approval_note ?: '-').'</td></tr>';
  }
  if ($no === 1) echo '<tr><td colspan="7" class="text-muted text-center">Belum ada approval history.</td></tr>';
  echo '</tbody></table></div>';
  exit;
}

if ($act === 'export') {
  session_check();
  list($manager, $subIds) = tla_manager_scope($db);
  if (!$manager || empty($subIds)) { echo 'Data manager atau bawahan tidak ditemukan.'; exit; }
  $initial = ob_get_level(); ob_start();
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require_once "../../inc/lib/PHPExcel.php";
  require_once "../../inc/excel_style_helper.php";
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $params = array();
  list($where, $from, $to) = tla_filter_where($_GET, $subIds, $params);
  $rows = $db->query("SELECT l.*, e.employee_no, e.full_name, d.nm_dept, j.job_title_name, h.employee_no handover_no, h.full_name handover_name,
      la.approval_no last_approval_no, la.decision last_decision, la.decision_date last_decision_date, la.approval_note last_approval_note
    FROM erp_leave_request l
    JOIN erp_employee_master e ON e.id=l.employee_id
    LEFT JOIN dept d ON d.kd_dept=l.department_code
    LEFT JOIN erp_job_title j ON j.id=l.job_title_id
    LEFT JOIN erp_employee_master h ON h.id=l.handover_to_employee_id
    LEFT JOIN (SELECT x.* FROM erp_leave_approval x JOIN (SELECT leave_request_id, MAX(id) id FROM erp_leave_approval GROUP BY leave_request_id) y ON y.id=x.id) la ON la.leave_request_id=l.id
    $where ORDER BY l.start_date DESC, l.leave_no DESC", $params);
  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Team Leave Approval'));
  $heads = array(erp_export_label("No"),erp_export_label("Leave No"),erp_export_label("Employee No"),erp_export_label("Employee Name"),erp_export_label("Department"),erp_export_label("Job Title"),erp_export_label("Leave Type"),erp_export_label("Request Date"),erp_export_label("Start Date"),erp_export_label("End Date"),erp_export_label("Half Day"),erp_export_label("Total Days"),erp_export_label("Quota Before"),erp_export_label("Quota After"),erp_export_label("Handover"),erp_export_label("Workflow Status"),erp_export_label("Approval Level"),erp_export_label("Decision"),erp_export_label("Decision By"),erp_export_label("Decision At"),erp_export_label("Reason"),erp_export_label("Attachment"),erp_export_label("Manager Note"),erp_export_label("Last Approval"),erp_export_label("Last Decision"),erp_export_label("Last Decision Date"),erp_export_label("Last Note"),erp_export_label("Remarks"));
  foreach ($heads as $i=>$h) $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4', $h);
  $rn = 5; $no = 1;
  foreach ($rows as $r) {
    $vals = array($no++, $r->leave_no, $r->employee_no, $r->full_name, ($r->department_code ?: '').' - '.($r->nm_dept ?: ''), $r->job_title_name, $r->leave_type, $r->request_date, $r->start_date, $r->end_date, $r->start_half_day.' / '.$r->end_half_day, $r->total_days, $r->leave_quota_before, $r->leave_quota_after, trim(($r->handover_no ?: '').' - '.($r->handover_name ?: '')), $r->workflow_status, $r->approval_level, $r->decision, $r->decision_by, $r->decision_at, $r->reason, $r->attachment_ref, $r->approver_note, $r->last_approval_no, $r->last_decision, $r->last_decision_date, $r->last_approval_note, $r->remarks);
    foreach ($vals as $i=>$v) $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn, $v);
    $rn++;
  }
  erpkb_excel_apply_standard_style($excel, array(
    'sheet'=>$sheet,
    'title'=>erp_export_title('TEAM LEAVE APPROVAL'),
    'header_row'=>4,
    'first_data_row'=>5,
    'last_data_row'=>max(5, $rn-1),
    'column_count'=>count($heads),
    'decimal_columns'=>array('L','M','N'),
    'filters'=>array('Manager'=>$manager->employee_no.' - '.$manager->full_name, 'Period'=>$from.' s/d '.$to, 'Status'=>isset($_GET['workflow_status']) && $_GET['workflow_status'] !== '' ? $_GET['workflow_status'] : erp_export_all_text()),
    'widths'=>array('B'=>18,'D'=>28,'E'=>26,'F'=>24,'G'=>20,'O'=>28,'U'=>42,'W'=>34,'X'=>20,'AA'=>34,'AB'=>34)
  ));
  $tmp = erpkb_excel_temp_file('team_leave_approval_');
  PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
  $size = @filesize($tmp); $sig = @file_get_contents($tmp, false, null, 0, 2);
  if (!$size || $sig !== 'PK') { @unlink($tmp); while(ob_get_level()>$initial) ob_end_clean(); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit; }
  while(ob_get_level()>$initial) ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="team_leave_approval_'.date('Ymd_His').'.xlsx"');
  header('Content-Length: '.$size);
  header('Cache-Control: max-age=0');
  header('Pragma: public');
  readfile($tmp); @unlink($tmp); exit;
}

echo 'Action tidak dikenal.';
?>
