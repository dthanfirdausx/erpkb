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
if (!defined('TEAM_PERFORMANCE_VIEW_INCLUDE')) include "../../inc/config.php";

function tpa_h($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function tpa_valid_date($value, $fallback){ $value = trim((string)$value); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback; }
function tpa_in_placeholders($count){ return implode(',', array_fill(0, max(1, (int)$count), '?')); }
function tpa_manager_scope($db)
{
  $userId = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;
  $manager = $userId ? $db->fetch("SELECT * FROM erp_employee_master WHERE user_id=? LIMIT 1", array($userId)) : null;
  if (!$manager) return array(null, array());
  $stmt = $db->query("SELECT id FROM erp_employee_master WHERE manager_employee_id=? ORDER BY id", array((int)$manager->id));
  $ids = array();
  if ($stmt) foreach ($stmt as $r) $ids[] = (int)$r->id;
  return array($manager, $ids);
}
function tpa_filters($src)
{
  $from = tpa_valid_date(isset($src['tgl_awal']) ? $src['tgl_awal'] : '', date('Y-m-01'));
  $to = tpa_valid_date(isset($src['tgl_akhir']) ? $src['tgl_akhir'] : '', date('Y-m-d'));
  if (strtotime($from) > strtotime($to)) $from = $to;
  return array(
    'from'=>$from,
    'to'=>$to,
    'employee_id'=>isset($src['employee_id']) ? (int)$src['employee_id'] : 0,
    'department_code'=>isset($src['department_code']) ? trim($src['department_code']) : '',
    'final_rating'=>isset($src['final_rating']) ? trim($src['final_rating']) : '',
    'appraisal_status'=>isset($src['appraisal_status']) ? trim($src['appraisal_status']) : '',
    'keyword'=>isset($src['keyword']) ? trim($src['keyword']) : ''
  );
}
function tpa_team_rows($db, $subIds, $f)
{
  if (empty($subIds)) return array();
  $params = array_merge(array($f['from'], $f['to'], $f['from'], $f['to'], $f['from'], $f['to']), $subIds);
  $where = " WHERE e.id IN (".tpa_in_placeholders(count($subIds)).") ";
  if ($f['employee_id'] && in_array($f['employee_id'], $subIds, true)) { $where .= " AND e.id=? "; $params[] = $f['employee_id']; }
  if ($f['department_code'] !== '') { $where .= " AND e.department_code=? "; $params[] = $f['department_code']; }
  if ($f['keyword'] !== '') {
    $kw = '%'.$f['keyword'].'%';
    $where .= " AND (e.employee_no LIKE ? OR e.full_name LIKE ? OR d.nm_dept LIKE ? OR jt.job_title_name LIKE ?) ";
    array_push($params, $kw, $kw, $kw, $kw);
  }
  if ($f['final_rating'] !== '') { $where .= " AND COALESCE(pa.final_rating,'')=? "; $params[] = $f['final_rating']; }
  if ($f['appraisal_status'] !== '') { $where .= " AND COALESCE(pa.appraisal_status,'')=? "; $params[] = $f['appraisal_status']; }

  $sql = "SELECT e.id employee_id, e.employee_no, e.full_name, e.employee_group, e.department_code, d.nm_dept, jt.job_title_name,
      COALESCE(att.total_records,0) attendance_records,
      COALESCE(att.present_days,0) present_days,
      COALESCE(att.absence_days,0) absence_days,
      COALESCE(att.leave_days,0) leave_days,
      COALESCE(att.sick_days,0) sick_days,
      COALESCE(att.late_count,0) late_count,
      COALESCE(att.late_minutes,0) late_minutes,
      COALESCE(att.actual_hours,0) actual_hours,
      COALESCE(att.overtime_hours,0) attendance_overtime_hours,
      COALESCE(ot.overtime_records,0) overtime_records,
      COALESCE(ot.requested_hours,0) requested_ot_hours,
      COALESCE(ot.approved_hours,0) approved_ot_hours,
      COALESCE(ot.payable_hours,0) payable_ot_hours,
      COALESCE(ot.estimated_amount,0) overtime_amount,
      COALESCE(lv.leave_records,0) leave_records,
      COALESCE(lv.leave_days_total,0) leave_days_total,
      ek.employee_kpi_no, ek.target_status, ek.approval_status kpi_approval_status, ek.total_weight,
      pa.appraisal_no, pa.manager_kpi_score, pa.competency_score, pa.behavior_score, pa.final_score, pa.final_rating,
      pa.appraisal_status, pa.approval_status appraisal_approval_status, pa.improvement_required, pa.reward_recommendation, pa.development_plan, pa.manager_comment
    FROM erp_employee_master e
    LEFT JOIN dept d ON d.kd_dept=e.department_code
    LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id
    LEFT JOIN (
      SELECT employee_id, COUNT(*) total_records,
        SUM(attendance_type IN ('REGULAR','OVERTIME','BUSINESS_TRIP','TRAINING','REMOTE')) present_days,
        SUM(attendance_type='ABSENT') absence_days,
        SUM(attendance_type='LEAVE') leave_days,
        SUM(attendance_type='SICK') sick_days,
        SUM(late_minutes>0) late_count,
        SUM(late_minutes) late_minutes,
        SUM(actual_hours) actual_hours,
        SUM(overtime_hours) overtime_hours
      FROM erp_attendance WHERE attendance_date BETWEEN ? AND ? GROUP BY employee_id
    ) att ON att.employee_id=e.id
    LEFT JOIN (
      SELECT employee_id, COUNT(*) overtime_records, SUM(requested_hours) requested_hours, SUM(approved_hours) approved_hours, SUM(payable_hours) payable_hours, SUM(estimated_amount) estimated_amount
      FROM erp_overtime WHERE overtime_date BETWEEN ? AND ? GROUP BY employee_id
    ) ot ON ot.employee_id=e.id
    LEFT JOIN (
      SELECT employee_id, COUNT(*) leave_records, SUM(total_days) leave_days_total
      FROM erp_leave_request WHERE start_date<=? AND end_date>=? AND workflow_status IN ('SUBMITTED','MANAGER_APPROVED','HR_APPROVED','APPROVED') GROUP BY employee_id
    ) lv ON lv.employee_id=e.id
    LEFT JOIN (
      SELECT x.* FROM erp_employee_kpi x
      JOIN (SELECT employee_id, MAX(id) id FROM erp_employee_kpi GROUP BY employee_id) y ON y.id=x.id
    ) ek ON ek.employee_id=e.id
    LEFT JOIN (
      SELECT x.* FROM erp_performance_appraisal x
      JOIN (SELECT employee_id, MAX(id) id FROM erp_performance_appraisal GROUP BY employee_id) y ON y.id=x.id
    ) pa ON pa.employee_id=e.id
    $where
    ORDER BY pa.final_score DESC, att.late_minutes ASC, e.full_name";
  $stmt = $db->query($sql, $params);
  return $stmt ? $stmt->fetchAll(PDO::FETCH_OBJ) : array();
}

$act = isset($_GET['act']) ? $_GET['act'] : '';

if ($act === 'detail') {
  session_check_json();
  list($manager, $subIds) = tpa_manager_scope($db);
  $employeeId = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
  if (!$manager || !in_array($employeeId, $subIds, true)) { echo '<div class="alert alert-warning">Employee bukan bawahan manager ini.</div>'; exit; }
  $f = tpa_filters($_POST);
  $f['employee_id'] = $employeeId;
  $rows = tpa_team_rows($db, $subIds, $f);
  if (!$rows) { echo '<div class="alert alert-warning">Data performance tidak ditemukan.</div>'; exit; }
  $r = $rows[0];
  echo '<h3 style="margin-top:0">'.tpa_h($r->employee_no).' <small>'.tpa_h($r->full_name).'</small></h3>';
  echo '<span class="label label-primary">'.tpa_h($r->department_code ?: '-').'</span> <span class="label label-info">Rating '.tpa_h($r->final_rating ?: '-').'</span><hr>';
  echo '<div class="row"><div class="col-sm-3"><b>'.hr_h('hr_attendance', 'Attendance').'</b><br>Present '.tpa_h($r->present_days).' / Late '.tpa_h($r->late_count).'<br><small>Late min '.tpa_h($r->late_minutes).'</small></div><div class="col-sm-3"><b>'.hr_h('hr_overtime', 'Overtime').'</b><br>Approved '.tpa_h($r->approved_ot_hours).' h<br><small>Payable '.tpa_h($r->payable_ot_hours).' h</small></div><div class="col-sm-3"><b>Leave</b><br>'.tpa_h($r->leave_days_total).' days</div><div class="col-sm-3"><b>KPI</b><br>'.tpa_h($r->employee_kpi_no ?: '-').'<br><small>'.tpa_h($r->target_status ?: '-').'</small></div></div><hr>';
  echo '<table class="table table-bordered"><thead><tr><th>KPI</th><th>Competency</th><th>Behavior</th><th>Final Score</th><th>'.hr_h('hr_rating', 'Rating').'</th><th>'.hr_h('common_status', 'Status').'</th></tr></thead><tbody><tr><td>'.number_format((float)$r->manager_kpi_score,2).'</td><td>'.number_format((float)$r->competency_score,2).'</td><td>'.number_format((float)$r->behavior_score,2).'</td><td><b>'.number_format((float)$r->final_score,2).'</b></td><td><b>'.tpa_h($r->final_rating ?: '-').'</b></td><td>'.tpa_h($r->appraisal_status ?: '-').'</td></tr></tbody></table>';
  echo '<div class="row"><div class="col-sm-4"><b>Reward Recommendation</b><p>'.tpa_h($r->reward_recommendation ?: '-').'</p></div><div class="col-sm-4"><b>Development Plan</b><p>'.tpa_h($r->development_plan ?: '-').'</p></div><div class="col-sm-4"><b>Manager Comment</b><p>'.tpa_h($r->manager_comment ?: '-').'</p></div></div>';
  exit;
}

if ($act === 'export') {
  session_check();
  list($manager, $subIds) = tpa_manager_scope($db);
  if (!$manager || empty($subIds)) { echo 'Data manager atau bawahan tidak ditemukan.'; exit; }
  $initial = ob_get_level(); ob_start();
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require_once "../../inc/lib/PHPExcel.php";
  require_once "../../inc/excel_style_helper.php";
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $f = tpa_filters($_GET);
  $rows = tpa_team_rows($db, $subIds, $f);
  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Team Performance'));
  $heads = array(erp_export_label("No"),erp_export_label("Employee No"),erp_export_label("Employee Name"),erp_export_label("Department"),erp_export_label("Job Title"),erp_export_label("Attendance Records"),erp_export_label("Present Days"),erp_export_label("Absence"),erp_export_label("Leave Days"),erp_export_label("Late Count"),erp_export_label("Late Minutes"),erp_export_label("Actual Hours"),erp_export_label("Attendance OT"),erp_export_label("OT Records"),erp_export_label("Requested OT"),erp_export_label("Approved OT"),erp_export_label("Payable OT"),erp_export_label("OT Amount"),erp_export_label("Leave Requests"),erp_export_label("Leave Days Total"),erp_export_label("KPI No"),erp_export_label("KPI Status"),erp_export_label("KPI Weight"),erp_export_label("Appraisal No"),erp_export_label("KPI Score"),erp_export_label("Competency"),erp_export_label("Behavior"),erp_export_label("Final Score"),erp_export_label("Rating"),erp_export_label("Appraisal Status"),erp_export_label("Approval Status"),erp_export_label("Improvement"),erp_export_label("Reward Recommendation"),erp_export_label("Development Plan"),erp_export_label("Manager Comment"));
  foreach ($heads as $i=>$h) $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4', $h);
  $rn = 5; $no = 1;
  foreach ($rows as $r) {
    $vals = array($no++, $r->employee_no, $r->full_name, ($r->department_code ?: '').' - '.($r->nm_dept ?: ''), $r->job_title_name, $r->attendance_records, $r->present_days, $r->absence_days, $r->leave_days, $r->late_count, $r->late_minutes, $r->actual_hours, $r->attendance_overtime_hours, $r->overtime_records, $r->requested_ot_hours, $r->approved_ot_hours, $r->payable_ot_hours, $r->overtime_amount, $r->leave_records, $r->leave_days_total, $r->employee_kpi_no, $r->target_status, $r->total_weight, $r->appraisal_no, $r->manager_kpi_score, $r->competency_score, $r->behavior_score, $r->final_score, $r->final_rating, $r->appraisal_status, $r->appraisal_approval_status, $r->improvement_required, $r->reward_recommendation, $r->development_plan, $r->manager_comment);
    foreach ($vals as $i=>$v) $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn, $v);
    $rn++;
  }
  erpkb_excel_apply_standard_style($excel, array(
    'sheet'=>$sheet,
    'title'=>erp_export_title('TEAM PERFORMANCE'),
    'header_row'=>4,
    'first_data_row'=>5,
    'last_data_row'=>max(5, $rn-1),
    'column_count'=>count($heads),
    'decimal_columns'=>array('L','M','O','P','Q','R','T','W','Y','Z','AA','AB'),
    'filters'=>array('Manager'=>$manager->employee_no.' - '.$manager->full_name, 'Period'=>$f['from'].' s/d '.$f['to'], 'Rating'=>$f['final_rating'] ?: erp_export_all_text()),
    'widths'=>array('C'=>28,'D'=>26,'E'=>26,'U'=>22,'X'=>22,'AC'=>12,'AD'=>20,'AG'=>30,'AH'=>34,'AI'=>34)
  ));
  $tmp = erpkb_excel_temp_file('team_performance_');
  PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
  $size = @filesize($tmp); $sig = @file_get_contents($tmp, false, null, 0, 2);
  if (!$size || $sig !== 'PK') { @unlink($tmp); while(ob_get_level()>$initial) ob_end_clean(); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit; }
  while(ob_get_level()>$initial) ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="team_performance_'.date('Ymd_His').'.xlsx"');
  header('Content-Length: '.$size);
  header('Cache-Control: max-age=0');
  header('Pragma: public');
  readfile($tmp); @unlink($tmp); exit;
}

if (defined('TEAM_PERFORMANCE_VIEW_INCLUDE')) return;
echo 'Action tidak dikenal.';
?>
