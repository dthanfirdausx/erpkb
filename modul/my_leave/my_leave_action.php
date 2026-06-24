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
session_check_json();

function ml_json($status, $message = '', $extra = array())
{
  header('Content-Type: application/json; charset=utf-8');
  $payload = array('status' => $status);
  if ($message !== '') $payload[$status === 'good' ? 'message' : 'error_message'] = $message;
  foreach ($extra as $k => $v) $payload[$k] = $v;
  echo json_encode($payload);
  exit;
}

function ml_h($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
function ml_p($k,$d=''){return isset($_POST[$k]) ? $_POST[$k] : $d;}
function ml_g($k,$d=''){return isset($_GET[$k]) ? $_GET[$k] : $d;}
function ml_user(){return isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'employee';}
function ml_current_employee()
{
  global $db;
  $userId = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;
  if ($userId <= 0) return null;
  return $db->fetch("SELECT e.*, u.username, d.nm_dept, jt.job_title_code, jt.job_title_name, m.employee_no manager_no, m.full_name manager_name
    FROM erp_employee_master e
    LEFT JOIN sys_users u ON u.id=e.user_id
    LEFT JOIN dept d ON d.kd_dept=e.department_code
    LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id
    LEFT JOIN erp_employee_master m ON m.id=e.manager_employee_id
    WHERE e.user_id=? LIMIT 1", array($userId));
}
function ml_valid_date($v){return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v);}
function ml_code($v){return strtoupper(trim((string)$v));}
function ml_next_no()
{
  global $db;
  $prefix = 'LVR'.date('Ym');
  $r = $db->fetch("SELECT leave_no FROM erp_leave_request WHERE leave_no LIKE ? ORDER BY leave_no DESC LIMIT 1", array($prefix.'%'));
  $n = $r ? ((int)substr($r->leave_no, -4) + 1) : 1;
  return $prefix.sprintf('%04d', $n);
}
function ml_days($from, $to, $startHalf, $endHalf)
{
  $a = strtotime($from); $b = strtotime($to);
  if (!$a || !$b || $b < $a) return 0;
  $days = (($b - $a) / 86400) + 1;
  if ($startHalf === 'PM') $days -= .5;
  if ($endHalf === 'AM') $days -= .5;
  return max(.5, $days);
}
function ml_leave_row($id, $employeeId)
{
  global $db;
  return $db->fetch("SELECT l.*, h.employee_no handover_no, h.full_name handover_name, a.employee_no approver_no, a.full_name approver_name, hr.employee_no hr_no, hr.full_name hr_name
    FROM erp_leave_request l
    LEFT JOIN erp_employee_master h ON h.id=l.handover_to_employee_id
    LEFT JOIN erp_employee_master a ON a.id=l.approver_employee_id
    LEFT JOIN erp_employee_master hr ON hr.id=l.hr_reviewer_employee_id
    WHERE l.id=? AND l.employee_id=? LIMIT 1", array((int)$id, (int)$employeeId));
}
function ml_select2($rows, $id, $cb)
{
  $out = array();
  foreach ($rows as $r) $out[] = array('id' => $r->$id, 'text' => $cb($r));
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('results' => $out));
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$employee = ml_current_employee();
if (!$employee) ml_json('error', 'Data employee untuk user aktif belum terhubung.');
$username = ml_user();

switch ($act) {
  case 'handover_search':
    $term = trim(ml_p('term')); $like = '%'.$term.'%';
    $rows = $db->query("SELECT id, employee_no, full_name, department_code
      FROM erp_employee_master
      WHERE id<>? AND employment_status IN ('ACTIVE','PROBATION','CONTRACT')
        AND (?='' OR employee_no LIKE ? OR full_name LIKE ? OR department_code LIKE ?)
      ORDER BY employee_no LIMIT 30", array((int)$employee->id, $term, $like, $like, $like));
    ml_select2($rows, 'id', function($r){return $r->employee_no.' - '.$r->full_name.' ['.$r->department_code.']';});
    break;

  case 'get':
    $row = ml_leave_row((int)ml_p('id'), (int)$employee->id);
    if (!$row) ml_json('error', 'Leave request tidak ditemukan.');
    $data = (array)$row;
    $data['handover_text'] = $row->handover_no ? $row->handover_no.' - '.$row->handover_name : '';
    ml_json('good', '', array('data' => $data));
    break;

  case 'save':
    $id = (int)ml_p('id');
    $row = $id > 0 ? ml_leave_row($id, (int)$employee->id) : null;
    if ($id > 0 && !$row) ml_json('error', 'Leave request tidak ditemukan.');
    if ($row && !in_array($row->workflow_status, array('DRAFT','RETURNED'), true)) {
      ml_json('error', 'Hanya status DRAFT atau RETURNED yang bisa diedit dari ESS.');
    }

    $type = ml_code(ml_p('leave_type', 'ANNUAL_LEAVE'));
    $requestDate = ml_p('request_date', date('Y-m-d'));
    $startDate = ml_p('start_date');
    $endDate = ml_p('end_date');
    $startHalf = ml_code(ml_p('start_half_day', 'FULL_DAY'));
    $endHalf = ml_code(ml_p('end_half_day', 'FULL_DAY'));
    $submit = ml_p('submit_status') === 'SUBMITTED';
    $validTypes = array('ANNUAL_LEAVE','SICK_LEAVE','SPECIAL_LEAVE','MATERNITY_LEAVE','PATERNITY_LEAVE','MARRIAGE_LEAVE','BEREAVEMENT_LEAVE','UNPAID_LEAVE','PERMISSION','OTHER');
    if (!in_array($type, $validTypes, true)) ml_json('error', 'Leave Type tidak valid.');
    if (!ml_valid_date($requestDate) || !ml_valid_date($startDate) || !ml_valid_date($endDate)) ml_json('error', 'Tanggal request/start/end wajib valid.');
    if (strtotime($endDate) < strtotime($startDate)) ml_json('error', 'End Date tidak boleh sebelum Start Date.');
    if (!in_array($startHalf, array('FULL_DAY','AM','PM'), true) || !in_array($endHalf, array('FULL_DAY','AM','PM'), true)) ml_json('error', 'Half day tidak valid.');

    $handover = ml_p('handover_to_employee_id') !== '' ? (int)ml_p('handover_to_employee_id') : null;
    if ($handover && !$db->fetch("SELECT id FROM erp_employee_master WHERE id=? AND id<>? LIMIT 1", array($handover, (int)$employee->id))) {
      ml_json('error', 'Handover employee tidak valid.');
    }

    $days = ml_days($startDate, $endDate, $startHalf, $endHalf);
    $quotaBefore = ml_p('leave_quota_before') !== '' ? (float)ml_p('leave_quota_before') : 12;
    $quotaDeduct = in_array($type, array('ANNUAL_LEAVE','SPECIAL_LEAVE','PERMISSION'), true) ? $days : 0;
    $quotaAfter = max(0, $quotaBefore - $quotaDeduct);
    $status = $submit ? 'SUBMITTED' : 'DRAFT';
    $approvalLevel = $submit ? 'MANAGER' : 'EMPLOYEE';
    $reason = trim(ml_p('reason'));
    if ($submit && $reason === '') ml_json('error', 'Reason wajib diisi sebelum submit.');

    $data = array(
      'leave_no' => $row ? $row->leave_no : ml_next_no(),
      'employee_id' => (int)$employee->id,
      'department_code' => $employee->department_code,
      'job_title_id' => $employee->job_title_id,
      'leave_type' => $type,
      'request_date' => $requestDate,
      'start_date' => $startDate,
      'end_date' => $endDate,
      'start_half_day' => $startHalf,
      'end_half_day' => $endHalf,
      'total_days' => $days,
      'leave_quota_before' => $quotaBefore,
      'leave_quota_after' => $quotaAfter,
      'reason' => $reason,
      'attachment_ref' => trim(ml_p('attachment_ref')),
      'handover_to_employee_id' => $handover,
      'approver_employee_id' => $employee->manager_employee_id,
      'hr_reviewer_employee_id' => null,
      'workflow_status' => $status,
      'approval_level' => $approvalLevel,
      'decision' => 'PENDING',
      'remarks' => trim(ml_p('remarks')),
      'updated_by' => $username,
      'updated_at' => date('Y-m-d H:i:s')
    );
    if ($id > 0) {
      $ok = $db->update('erp_leave_request', $data, 'id', $id);
    } else {
      $data['created_by'] = $username;
      $ok = $db->insert('erp_leave_request', $data);
      $id = $db->last_insert_id();
    }
    if (!$ok || $db->getErrorMessage() !== '') ml_json('error', $db->getErrorMessage() ?: 'Leave request gagal disimpan.');
    simpan_log('User '.$username.' menyimpan leave request '.$data['leave_no'].' untuk '.$employee->employee_no.' pada '.date('Y-m-d H:i:s'), $username);
    ml_json('good', 'Leave request berhasil disimpan.', array('id' => $id, 'leave_no' => $data['leave_no'], 'total_days' => $days));
    break;

  case 'cancel':
    $row = ml_leave_row((int)ml_p('id'), (int)$employee->id);
    if (!$row) ml_json('error', 'Leave request tidak ditemukan.');
    if (in_array($row->workflow_status, array('APPROVED','REJECTED','CANCELLED'), true)) ml_json('error', 'Status final tidak bisa dibatalkan dari ESS.');
    $reason = trim(ml_p('reason'));
    $db->update('erp_leave_request', array(
      'workflow_status' => 'CANCELLED',
      'approval_level' => 'FINAL',
      'decision' => 'CANCELLED',
      'decision_by' => $username,
      'decision_at' => date('Y-m-d H:i:s'),
      'cancellation_reason' => $reason,
      'updated_by' => $username,
      'updated_at' => date('Y-m-d H:i:s')
    ), 'id', (int)$row->id);
    if ($db->getErrorMessage() !== '') ml_json('error', $db->getErrorMessage());
    simpan_log('User '.$username.' membatalkan leave request '.$row->leave_no.' pada '.date('Y-m-d H:i:s'), $username);
    ml_json('good', 'Leave request berhasil dibatalkan.');
    break;

  case 'export':
    $initial = ob_get_level(); ob_start();
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require_once "../../inc/lib/PHPExcel.php";
    require_once "../../inc/excel_style_helper.php";
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);

    $from = ml_g('tgl_awal', date('Y-01-01'));
    $to = ml_g('tgl_akhir', date('Y-m-d'));
    $type = ml_code(ml_g('leave_type'));
    $status = ml_code(ml_g('status'));
    if (!ml_valid_date($from)) $from = date('Y-01-01');
    if (!ml_valid_date($to)) $to = date('Y-m-d');
    $where = " WHERE l.employee_id=? AND l.start_date<=? AND l.end_date>=? ";
    $params = array((int)$employee->id, $to, $from);
    if ($type !== '') {$where .= " AND l.leave_type=? "; $params[] = $type;}
    if ($status !== '') {$where .= " AND l.workflow_status=? "; $params[] = $status;}
    $rows = $db->query("SELECT l.*, h.employee_no handover_no, h.full_name handover_name, a.employee_no approver_no, a.full_name approver_name
      FROM erp_leave_request l
      LEFT JOIN erp_employee_master h ON h.id=l.handover_to_employee_id
      LEFT JOIN erp_employee_master a ON a.id=l.approver_employee_id
      $where ORDER BY l.start_date DESC, l.leave_no DESC", $params);
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('My Leave'));
    $heads = array(erp_export_label("No"),erp_export_label("Leave No"),erp_export_label("Leave Type"),erp_export_label("Request Date"),erp_export_label("Start Date"),erp_export_label("End Date"),erp_export_label("Half Day"),erp_export_label("Total Days"),erp_export_label("Quota Before"),erp_export_label("Quota After"),erp_export_label("Handover"),erp_export_label("Approver"),erp_export_label("Status"),erp_export_label("Decision"),erp_export_label("Decision By"),erp_export_label("Decision At"),erp_export_label("Reason"),erp_export_label("Attachment"),erp_export_label("Approver Note"),erp_export_label("Remarks"));
    foreach ($heads as $i => $h) $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4', $h);
    $rn = 5; $n = 1;
    foreach ($rows as $r) {
      $vals = array($n++,$r->leave_no,$r->leave_type,$r->request_date,$r->start_date,$r->end_date,$r->start_half_day.' / '.$r->end_half_day,(float)$r->total_days,(float)$r->leave_quota_before,(float)$r->leave_quota_after,trim(($r->handover_no?:'').' - '.($r->handover_name?:'')),trim(($r->approver_no?:'').' - '.($r->approver_name?:'')),$r->workflow_status,$r->decision,$r->decision_by,$r->decision_at,$r->reason,$r->attachment_ref,$r->approver_note,$r->remarks);
      foreach ($vals as $i => $v) $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn, $v);
      $rn++;
    }
    erpkb_excel_apply_standard_style($excel, array(
      'sheet' => $sheet,
      'title' => erp_export_title('MY LEAVE REPORT'),
      'header_row' => 4,
      'first_data_row' => 5,
      'last_data_row' => max(5, $rn - 1),
      'column_count' => count($heads),
      'decimal_columns' => array('H','I','J'),
      'filters' => array('Employee' => $employee->employee_no.' - '.$employee->full_name, 'Period' => $from.' s/d '.$to, 'Type' => $type ?: erp_export_all_text(), 'Status' => $status ?: erp_export_all_text()),
      'widths' => array('B'=>18,'C'=>20,'K'=>30,'L'=>30,'Q'=>42,'S'=>36,'T'=>34)
    ));
    $tmp = erpkb_excel_temp_file('my_leave_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = @filesize($tmp); $sig = @file_get_contents($tmp, false, null, 0, 2);
    if (!$size || $sig !== 'PK') {@unlink($tmp); while(ob_get_level()>$initial) ob_end_clean(); header('Content-Type:text/plain; charset=utf-8'); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit;}
    while(ob_get_level()>$initial) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="my_leave_'.date('Ymd_His').'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp); @unlink($tmp); exit;

  default:
    ml_json('error', 'Aksi tidak dikenal.');
}
