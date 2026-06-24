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

function toa_json($status, $message = '', $extra = array())
{
  header('Content-Type: application/json; charset=utf-8');
  $payload = array('status' => $status);
  if ($message !== '') $payload[$status === 'good' ? 'message' : 'error_message'] = $message;
  foreach ($extra as $k => $v) $payload[$k] = $v;
  echo json_encode($payload);
  exit;
}
function toa_h($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function toa_user(){ return isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'admin'; }
function toa_valid_date($value, $fallback){ $value = trim((string)$value); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback; }
function toa_code($value){ return strtoupper(trim((string)$value)); }
function toa_in_placeholders($count){ return implode(',', array_fill(0, max(1, (int)$count), '?')); }
function toa_manager_scope($db)
{
  $userId = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;
  $manager = $userId ? $db->fetch("SELECT * FROM erp_employee_master WHERE user_id=? LIMIT 1", array($userId)) : null;
  if (!$manager) return array(null, array());
  $stmt = $db->query("SELECT id FROM erp_employee_master WHERE manager_employee_id=? ORDER BY id", array((int)$manager->id));
  $ids = array();
  if ($stmt) foreach ($stmt as $r) $ids[] = (int)$r->id;
  return array($manager, $ids);
}
function toa_row($db, $id, $subIds)
{
  if (empty($subIds)) return null;
  $params = $subIds;
  array_unshift($params, (int)$id);
  return $db->fetch("SELECT ot.*, e.full_name, e.employee_group, d.nm_dept, cc.cost_center_name,
      a.attendance_date, a.planned_hours, a.actual_hours, a.late_minutes, a.early_leave_minutes, a.overtime_hours attendance_ot
    FROM erp_overtime ot
    JOIN erp_employee_master e ON e.id=ot.employee_id
    LEFT JOIN dept d ON d.kd_dept=ot.department_code
    LEFT JOIN erp_cost_center cc ON cc.cost_center_code=ot.cost_center_code
    LEFT JOIN erp_attendance a ON a.id=ot.attendance_id
    WHERE ot.id=? AND ot.employee_id IN (".toa_in_placeholders(count($subIds)).")
    LIMIT 1", $params);
}
function toa_filter_where($src, $subIds, &$params)
{
  $from = toa_valid_date(isset($src['tgl_awal']) ? $src['tgl_awal'] : '', date('Y-m-01'));
  $to = toa_valid_date(isset($src['tgl_akhir']) ? $src['tgl_akhir'] : '', date('Y-m-d'));
  if (strtotime($from) > strtotime($to)) $from = $to;
  $params = $subIds;
  $where = " WHERE ot.employee_id IN (".toa_in_placeholders(count($subIds)).") AND ot.overtime_date BETWEEN ? AND ? ";
  $params[] = $from; $params[] = $to;
  foreach (array('department_code','overtime_type','overtime_status','request_source') as $key) {
    if (isset($src[$key]) && trim($src[$key]) !== '') { $where .= " AND ot.$key=? "; $params[] = trim($src[$key]); }
  }
  $employeeId = isset($src['employee_id']) ? (int)$src['employee_id'] : 0;
  if ($employeeId && in_array($employeeId, $subIds, true)) { $where .= " AND ot.employee_id=? "; $params[] = $employeeId; }
  if (isset($src['keyword']) && trim($src['keyword']) !== '') {
    $kw = '%'.trim($src['keyword']).'%';
    $where .= " AND (ot.overtime_no LIKE ? OR ot.employee_no LIKE ? OR e.full_name LIKE ? OR ot.overtime_reason LIKE ? OR ot.sap_reference LIKE ?) ";
    array_push($params, $kw, $kw, $kw, $kw, $kw);
  }
  return array($where, $from, $to);
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

if ($act === 'decision') {
  session_check_json();
  list($manager, $subIds) = toa_manager_scope($db);
  if (!$manager || empty($subIds)) toa_json('error', 'Data manager atau bawahan tidak ditemukan.');
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $decision = toa_code(isset($_POST['decision']) ? $_POST['decision'] : '');
  $note = trim(isset($_POST['note']) ? $_POST['note'] : '');
  $row = toa_row($db, $id, $subIds);
  if (!$row) toa_json('error', 'Overtime tidak ditemukan atau bukan bawahan manager ini.');
  if ($row->overtime_status !== 'REQUESTED') toa_json('error', 'Hanya overtime REQUESTED yang bisa diproses dari Team Overtime Approval.');
  if (!in_array($decision, array('APPROVE','REJECT'), true)) toa_json('error', 'Decision tidak valid.');
  if ($decision === 'REJECT' && $note === '') toa_json('error', 'Reject reason wajib diisi.');
  $username = toa_user();
  $approvedHours = $row->requested_hours > 0 ? (float)$row->requested_hours : 0;
  $payableHours = $approvedHours;
  $amount = round($payableHours * (float)$row->rate_multiplier * (float)$row->hourly_rate, 2);
  $data = array(
    'overtime_status' => $decision === 'APPROVE' ? 'APPROVED' : 'REJECTED',
    'approved_by' => $username,
    'approved_at' => date('Y-m-d H:i:s'),
    'updated_by' => $username,
    'updated_at' => date('Y-m-d H:i:s')
  );
  if ($decision === 'APPROVE') {
    $data['approved_hours'] = $approvedHours;
    $data['payable_hours'] = $payableHours;
    $data['estimated_amount'] = $amount;
    if ($note !== '') $data['remarks'] = trim(($row->remarks ? $row->remarks."\n" : '').'Manager note: '.$note);
  } else {
    $data['reject_reason'] = $note;
    $data['approved_hours'] = 0;
    $data['payable_hours'] = 0;
    $data['estimated_amount'] = 0;
  }
  $ok = $db->update('erp_overtime', $data, 'id', (int)$row->id);
  if (!$ok || $db->getErrorMessage() !== '') toa_json('error', $db->getErrorMessage() ?: hr_t('hr_overtime_process_failed', 'Overtime failed to process.'));
  if (function_exists('simpan_log')) simpan_log('User '.$username.' '.($decision === 'APPROVE' ? 'APPROVED' : 'REJECTED').' overtime '.$row->overtime_no.' untuk '.$row->employee_no.' pada '.date('Y-m-d H:i:s'), $username);
  toa_json('good', 'Overtime '.$row->overtime_no.' berhasil diproses.');
}

if ($act === 'detail') {
  session_check_json();
  list($manager, $subIds) = toa_manager_scope($db);
  $row = toa_row($db, isset($_POST['id']) ? (int)$_POST['id'] : 0, $subIds);
  if (!$row) { echo '<div class="alert alert-warning">Overtime tidak ditemukan.</div>'; exit; }
  echo '<h3 style="margin-top:0">'.toa_h($row->overtime_no).' <small>'.toa_h($row->employee_no.' - '.$row->full_name).'</small></h3>';
  echo '<span class="label label-info">'.toa_h($row->overtime_type).'</span> <span class="label label-primary">'.toa_h($row->request_source).'</span> <span class="label label-success">'.toa_h($row->overtime_status).'</span><hr>';
  echo '<div class="row"><div class="col-sm-3"><b>'.hr_h('hr_date', 'Date').'</b><br>'.toa_h($row->overtime_date).'</div><div class="col-sm-3"><b>'.hr_h('hr_department', 'Department').'</b><br>'.toa_h(($row->department_code ?: '-').' - '.($row->nm_dept ?: '-')).'</div><div class="col-sm-3"><b>Cost Center</b><br>'.toa_h(($row->cost_center_code ?: '-').' - '.($row->cost_center_name ?: '-')).'</div><div class="col-sm-3"><b>Attendance Ref</b><br>'.toa_h($row->attendance_no ?: '-').'</div></div><hr>';
  echo '<div class="row"><div class="col-sm-3"><b>Planned</b><br>'.toa_h(($row->planned_start ?: '-').' s/d '.($row->planned_end ?: '-')).'</div><div class="col-sm-3"><b>Actual</b><br>'.toa_h(($row->actual_start ?: '-').' s/d '.($row->actual_end ?: '-')).'</div><div class="col-sm-3"><b>Hours</b><br>Req '.toa_h($row->requested_hours).' | App '.toa_h($row->approved_hours).'<br><small>Pay '.toa_h($row->payable_hours).' x '.toa_h($row->rate_multiplier).'</small></div><div class="col-sm-3"><b>Amount</b><br>'.number_format((float)$row->estimated_amount,2).'</div></div><hr>';
  echo '<b>Reason</b><p>'.nl2br(toa_h($row->overtime_reason)).'</p>';
  echo '<b>Reject / Remarks</b><p>'.nl2br(toa_h(trim(($row->reject_reason ?: '')."\n".($row->remarks ?: '')) ?: '-')).'</p>';
  exit;
}

if ($act === 'export') {
  session_check();
  list($manager, $subIds) = toa_manager_scope($db);
  if (!$manager || empty($subIds)) { echo 'Data manager atau bawahan tidak ditemukan.'; exit; }
  $initial = ob_get_level(); ob_start();
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require_once "../../inc/lib/PHPExcel.php";
  require_once "../../inc/excel_style_helper.php";
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $params = array();
  list($where, $from, $to) = toa_filter_where($_GET, $subIds, $params);
  $rows = $db->query("SELECT ot.*, e.full_name, d.nm_dept, cc.cost_center_name
    FROM erp_overtime ot
    JOIN erp_employee_master e ON e.id=ot.employee_id
    LEFT JOIN dept d ON d.kd_dept=ot.department_code
    LEFT JOIN erp_cost_center cc ON cc.cost_center_code=ot.cost_center_code
    $where ORDER BY ot.overtime_date DESC, ot.overtime_no", $params);
  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Team Overtime'));
  $heads = array(erp_export_label("No"),erp_export_label("Overtime No"),erp_export_label("Date"),erp_export_label("Employee No"),erp_export_label("Employee Name"),erp_export_label("Department"),erp_export_label("Cost Center"),erp_export_label("Attendance"),erp_export_label("Planned Start"),erp_export_label("Planned End"),erp_export_label("Actual Start"),erp_export_label("Actual End"),erp_export_label("Requested"),erp_export_label("Approved"),erp_export_label("Payable"),erp_export_label("Multiplier"),erp_export_label("Hourly Rate"),erp_export_label("Amount"),erp_export_label("Type"),erp_export_label("Source"),erp_export_label("Status"),erp_export_label("Reason"),erp_export_label("Approved By"),erp_export_label("Approved At"),erp_export_label("Reject Reason"),erp_export_label("SAP Ref"),erp_export_label("Remarks"));
  foreach ($heads as $i=>$h) $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4', $h);
  $rn = 5; $no = 1;
  foreach ($rows as $r) {
    $vals = array($no++, $r->overtime_no, $r->overtime_date, $r->employee_no, $r->full_name, ($r->department_code ?: '').' - '.($r->nm_dept ?: ''), ($r->cost_center_code ?: '').' - '.($r->cost_center_name ?: ''), $r->attendance_no, $r->planned_start, $r->planned_end, $r->actual_start, $r->actual_end, $r->requested_hours, $r->approved_hours, $r->payable_hours, $r->rate_multiplier, $r->hourly_rate, $r->estimated_amount, $r->overtime_type, $r->request_source, $r->overtime_status, $r->overtime_reason, $r->approved_by, $r->approved_at, $r->reject_reason, $r->sap_reference, $r->remarks);
    foreach ($vals as $i=>$v) $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn, $v);
    $rn++;
  }
  erpkb_excel_apply_standard_style($excel, array(
    'sheet'=>$sheet,
    'title'=>erp_export_title('TEAM OVERTIME APPROVAL'),
    'header_row'=>4,
    'first_data_row'=>5,
    'last_data_row'=>max(5, $rn-1),
    'column_count'=>count($heads),
    'decimal_columns'=>array('M','N','O','P'),
    'money_columns'=>array('Q','R'),
    'filters'=>array('Manager'=>$manager->employee_no.' - '.$manager->full_name, 'Date'=>$from.' s/d '.$to, 'Status'=>isset($_GET['overtime_status']) && $_GET['overtime_status'] !== '' ? $_GET['overtime_status'] : erp_export_all_text()),
    'widths'=>array('B'=>20,'E'=>28,'F'=>26,'G'=>28,'V'=>38,'Y'=>32,'AA'=>34)
  ));
  $tmp = erpkb_excel_temp_file('team_overtime_');
  PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
  $size = @filesize($tmp); $sig = @file_get_contents($tmp, false, null, 0, 2);
  if (!$size || $sig !== 'PK') { @unlink($tmp); while(ob_get_level()>$initial) ob_end_clean(); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit; }
  while(ob_get_level()>$initial) ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="team_overtime_approval_'.date('Ymd_His').'.xlsx"');
  header('Content-Length: '.$size);
  header('Cache-Control: max-age=0');
  header('Pragma: public');
  readfile($tmp); @unlink($tmp); exit;
}

echo 'Action tidak dikenal.';
?>
