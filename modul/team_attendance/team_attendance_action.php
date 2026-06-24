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
session_check();

function taa_h($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function taa_valid_date($value, $fallback){ $value = trim((string)$value); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback; }
function taa_in_placeholders($count){ return implode(',', array_fill(0, max(1, (int)$count), '?')); }
function taa_manager_scope($db){
  $userId = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;
  $manager = $userId ? $db->fetch("SELECT * FROM erp_employee_master WHERE user_id=? LIMIT 1", array($userId)) : null;
  if (!$manager) return array(null, array());
  $stmt = $db->query("SELECT id FROM erp_employee_master WHERE manager_employee_id=? ORDER BY id", array((int)$manager->id));
  $ids = array(); if ($stmt) foreach ($stmt as $r) $ids[] = (int)$r->id;
  return array($manager, $ids);
}
function taa_filter_where($src, $subIds, &$params){
  $from = taa_valid_date(isset($src['tgl_awal']) ? $src['tgl_awal'] : '', date('Y-m-01'));
  $to = taa_valid_date(isset($src['tgl_akhir']) ? $src['tgl_akhir'] : '', date('Y-m-d'));
  if (strtotime($from) > strtotime($to)) $from = $to;
  $params = $subIds;
  $where = " WHERE a.employee_id IN (".taa_in_placeholders(count($subIds)).") AND a.attendance_date BETWEEN ? AND ? ";
  $params[] = $from; $params[] = $to;
  $employeeId = isset($src['employee_id']) ? (int)$src['employee_id'] : 0;
  if ($employeeId && in_array($employeeId, $subIds, true)) { $where .= " AND a.employee_id=? "; $params[] = $employeeId; }
  foreach (array('department_code','attendance_type','attendance_status') as $k) {
    if (isset($src[$k]) && trim($src[$k]) !== '') { $where .= " AND a.$k=? "; $params[] = trim($src[$k]); }
  }
  if (isset($src['keyword']) && trim($src['keyword']) !== '') {
    $kw = '%'.trim($src['keyword']).'%';
    $where .= " AND (a.attendance_no LIKE ? OR a.employee_no LIKE ? OR e.full_name LIKE ? OR a.remarks LIKE ? OR a.sap_reference LIKE ?) ";
    array_push($params, $kw, $kw, $kw, $kw, $kw);
  }
  return array($where, $from, $to);
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
if ($act !== 'export') { echo 'Action tidak dikenal.'; exit; }

list($manager, $subIds) = taa_manager_scope($db);
if (!$manager || empty($subIds)) { echo 'Data manager atau bawahan tidak ditemukan.'; exit; }

$initial = ob_get_level();
ob_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
require_once "../../inc/lib/PHPExcel.php";
require_once "../../inc/excel_style_helper.php";
PHPExcel_Shared_File::setUseUploadTempDirectory(true);

$params = array();
list($where, $from, $to) = taa_filter_where($_GET, $subIds, $params);
$rows = $db->query("SELECT a.*, e.full_name, e.employee_group, d.nm_dept, s.nama_shift, wl.location_code, wl.location_name
  FROM erp_attendance a
  JOIN erp_employee_master e ON e.id=a.employee_id
  LEFT JOIN dept d ON d.kd_dept=a.department_code
  LEFT JOIN erp_shift s ON s.id=a.shift_id
  LEFT JOIN erp_work_location wl ON wl.id=a.work_location_id
  $where
  ORDER BY a.attendance_date DESC, e.full_name, a.attendance_no DESC", $params);

$excel = new PHPExcel();
$sheet = $excel->setActiveSheetIndex(0);
$sheet->setTitle(erp_export_sheet_title('Team Attendance'));
$heads = array(erp_export_label("No"),erp_export_label("Attendance No"),erp_export_label("Date"),erp_export_label("Employee No"),erp_export_label("Employee Name"),erp_export_label("Department"),erp_export_label("Shift"),erp_export_label("Location"),erp_export_label("Planned Start"),erp_export_label("Planned End"),erp_export_label("Clock In"),erp_export_label("Clock Out"),erp_export_label("Planned Hours"),erp_export_label("Actual Hours"),erp_export_label("Late Min"),erp_export_label("Early Min"),erp_export_label("Overtime"),erp_export_label("Type"),erp_export_label("Source"),erp_export_label("Status"),erp_export_label("Absence Reason"),erp_export_label("Correction Reason"),erp_export_label("Remarks"),erp_export_label("SAP Ref"));
foreach ($heads as $i=>$h) $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4', $h);
$rn = 5; $no = 1;
foreach ($rows as $r) {
  $vals = array($no++, $r->attendance_no, $r->attendance_date, $r->employee_no, $r->full_name, ($r->department_code ?: '').' - '.($r->nm_dept ?: ''), ($r->shift_code ?: '').' - '.($r->nama_shift ?: ''), ($r->location_code ?: '').' - '.($r->location_name ?: ''), $r->planned_start, $r->planned_end, $r->actual_clock_in, $r->actual_clock_out, $r->planned_hours, $r->actual_hours, $r->late_minutes, $r->early_leave_minutes, $r->overtime_hours, $r->attendance_type, $r->attendance_source, $r->attendance_status, $r->absence_reason, $r->correction_reason, $r->remarks, $r->sap_reference);
  foreach ($vals as $i=>$v) $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn, $v);
  $rn++;
}
erpkb_excel_apply_standard_style($excel, array(
  'sheet'=>$sheet,
  'title'=>erp_export_title('TEAM ATTENDANCE'),
  'header_row'=>4,
  'first_data_row'=>5,
  'last_data_row'=>max(5, $rn-1),
  'column_count'=>count($heads),
  'decimal_columns'=>array('M','N','Q'),
  'numeric_columns'=>array('O','P'),
  'filters'=>array('Manager'=>$manager->employee_no.' - '.$manager->full_name, 'Date'=>$from.' s/d '.$to),
  'widths'=>array('B'=>20,'D'=>14,'E'=>28,'F'=>26,'G'=>24,'H'=>26,'U'=>24,'V'=>26,'W'=>36)
));
$tmp = erpkb_excel_temp_file('team_attendance_');
PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
$size = @filesize($tmp);
$sig = @file_get_contents($tmp, false, null, 0, 2);
if (!$size || $sig !== 'PK') {
  @unlink($tmp);
  while (ob_get_level() > $initial) ob_end_clean();
  echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');
  exit;
}
while (ob_get_level() > $initial) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="team_attendance_'.date('Ymd_His').'.xlsx"');
header('Content-Length: '.$size);
header('Cache-Control: max-age=0');
header('Pragma: public');
readfile($tmp);
@unlink($tmp);
exit;
?>
