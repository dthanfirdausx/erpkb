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

function mps_json($status, $message = '', $extra = array())
{
  header('Content-Type: application/json; charset=utf-8');
  $payload = array('status' => $status);
  if ($message !== '') $payload[$status === 'good' ? 'message' : 'error_message'] = $message;
  foreach ($extra as $k => $v) $payload[$k] = $v;
  echo json_encode($payload);
  exit;
}
function mps_h($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
function mps_g($k,$d=''){return isset($_GET[$k]) ? $_GET[$k] : $d;}
function mps_p($k,$d=''){return isset($_POST[$k]) ? $_POST[$k] : $d;}
function mps_valid_date($v){return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v);}
function mps_money($v){return number_format((float)$v, 2, '.', ',');}
function mps_current_employee()
{
  global $db;
  $userId = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;
  if ($userId <= 0) return null;
  return $db->fetch("SELECT e.*, u.username, d.nm_dept, jt.job_title_code, jt.job_title_name
    FROM erp_employee_master e
    LEFT JOIN sys_users u ON u.id=e.user_id
    LEFT JOIN dept d ON d.kd_dept=e.department_code
    LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id
    WHERE e.user_id=? LIMIT 1", array($userId));
}
function mps_rows($employeeId, $from, $to, $status, $area)
{
  global $db;
  $where = " WHERE ps.employee_id=? AND ps.period_from<=? AND ps.period_to>=? ";
  $params = array((int)$employeeId, $to, $from);
  if ($status !== '') {$where .= " AND ps.payslip_status=? "; $params[] = $status;}
  if ($area !== '') {$where .= " AND ps.payroll_area=? "; $params[] = $area;}
  return $db->query("SELECT ps.*, d.nm_dept
    FROM erp_payslip ps
    LEFT JOIN dept d ON d.kd_dept=ps.department_code
    $where
    ORDER BY ps.period_year DESC, ps.period_month DESC, ps.payslip_no DESC", $params);
}
function mps_row($id, $employeeId)
{
  global $db;
  return $db->fetch("SELECT ps.*, d.nm_dept
    FROM erp_payslip ps
    LEFT JOIN dept d ON d.kd_dept=ps.department_code
    WHERE ps.id=? AND ps.employee_id=? LIMIT 1", array((int)$id, (int)$employeeId));
}

$employee = mps_current_employee();
if (!$employee) mps_json('error', 'Data employee untuk user aktif belum terhubung.');
$act = isset($_GET['act']) ? $_GET['act'] : '';

switch ($act) {
  case 'detail':
    $r = mps_row((int)mps_p('id'), (int)$employee->id);
    if (!$r) {
      echo '<div class="alert alert-warning">Payslip tidak ditemukan atau bukan milik user aktif.</div>';
      exit;
    }
    $lines = $db->query("SELECT * FROM erp_payslip_detail WHERE payslip_id=? ORDER BY sequence_no,id", array((int)$r->id));
    $earnings = ''; $deductions = ''; $infos = '';
    foreach ($lines as $l) {
      $tr = '<tr><td><strong>'.mps_h($l->component_code).'</strong><br><small>'.mps_h($l->component_name).'</small></td><td class="text-right">'.mps_money($l->amount).'</td></tr>';
      if (in_array($l->component_type, array('EARNING','BENEFIT'), true)) $earnings .= $tr;
      elseif (in_array($l->component_type, array('DEDUCTION','TAX','EMPLOYER_CONTRIBUTION'), true)) $deductions .= $tr;
      else $infos .= $tr;
    }
    if ($earnings === '') $earnings = '<tr><td colspan="2" class="text-muted text-center">Tidak ada earning.</td></tr>';
    if ($deductions === '') $deductions = '<tr><td colspan="2" class="text-muted text-center">Tidak ada deduction/tax.</td></tr>';
    if ($infos === '') $infos = '<tr><td colspan="2" class="text-muted text-center">Tidak ada informasi tambahan.</td></tr>';
    echo '<div class="mps-slip-print">';
    echo '<div class="row"><div class="col-sm-7"><h3 style="margin-top:0">'.mps_h($r->payslip_no).'</h3><p><b>'.mps_h($r->full_name).'</b><br>'.mps_h($r->employee_no).' / '.mps_h($r->department_code.' - '.$r->nm_dept).'<br>'.mps_h($r->employee_group.' / '.$r->payroll_area).'</p></div><div class="col-sm-5 text-right"><h4>'.mps_h(date('F Y', mktime(0,0,0,(int)$r->period_month,1,(int)$r->period_year))).'</h4><span class="label label-primary">'.mps_h($r->payslip_status).'</span><br><small>Pay date: '.mps_h($r->pay_date).'</small></div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><b>Working Days</b><br>'.mps_money($r->working_days).'</div><div class="col-sm-3"><b>Paid Days</b><br>'.mps_money($r->paid_days).'</div><div class="col-sm-3"><b>Absence Days</b><br>'.mps_money($r->absence_days).'</div><div class="col-sm-3"><b>'.hr_h('hr_overtime_hours', 'Overtime Hours').'</b><br>'.mps_money($r->overtime_hours).'</div></div><hr>';
    echo '<div class="row"><div class="col-md-6"><h4>Earnings</h4><table class="table table-bordered table-condensed">'.$earnings.'</table></div><div class="col-md-6"><h4>Deductions & Tax</h4><table class="table table-bordered table-condensed">'.$deductions.'</table></div></div>';
    echo '<h4>Information</h4><table class="table table-bordered table-condensed">'.$infos.'</table>';
    echo '<hr><div class="row text-center"><div class="col-sm-3"><b>'.hr_h('hr_gross_pay', 'Gross Pay').'</b><br>'.mps_money($r->gross_pay).'</div><div class="col-sm-3"><b>Total Deduction</b><br>'.mps_money($r->total_deduction).'</div><div class="col-sm-3"><b>Tax</b><br>'.mps_money($r->tax_amount).'</div><div class="col-sm-3"><b>Take Home Pay</b><br><span style="font-size:20px;font-weight:800">'.mps_money($r->net_pay).'</span></div></div>';
    echo '</div>';
    exit;

  case 'export':
    $initial = ob_get_level(); ob_start();
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require_once "../../inc/lib/PHPExcel.php";
    require_once "../../inc/excel_style_helper.php";
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $from = mps_g('tgl_awal', date('Y-01-01')); if (!mps_valid_date($from)) $from = date('Y-01-01');
    $to = mps_g('tgl_akhir', date('Y-m-d')); if (!mps_valid_date($to)) $to = date('Y-m-d');
    $status = strtoupper(trim(mps_g('payslip_status')));
    $area = strtoupper(trim(mps_g('payroll_area')));
    $rows = mps_rows((int)$employee->id, $from, $to, $status, $area);
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('My Payslip'));
    $heads = array(erp_export_label("No"),erp_export_label("Payslip No"),erp_export_label("Payroll Run"),erp_export_label("Period"),erp_export_label("Pay Date"),erp_export_label("Status"),erp_export_label("Channel"),erp_export_label("Gross Pay"),erp_export_label("Total Earning"),erp_export_label("Total Deduction"),erp_export_label("Tax"),erp_export_label("Net Pay"),erp_export_label("Working Days"),erp_export_label("Paid Days"),erp_export_label("Absence Days"),erp_export_label("Overtime Hours"),erp_export_label("Released At"),erp_export_label("SAP Ref"),erp_export_label("Remarks"));
    foreach ($heads as $i => $h) $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4', $h);
    $rn = 5; $n = 1;
    foreach ($rows as $r) {
      $vals = array($n++,$r->payslip_no,$r->payroll_run_no,$r->period_from.' s/d '.$r->period_to,$r->pay_date,$r->payslip_status,$r->release_channel,(float)$r->gross_pay,(float)$r->total_earning,(float)$r->total_deduction,(float)$r->tax_amount,(float)$r->net_pay,(float)$r->working_days,(float)$r->paid_days,(float)$r->absence_days,(float)$r->overtime_hours,$r->released_at,$r->sap_reference,$r->remarks);
      foreach ($vals as $i => $v) $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn, $v);
      $rn++;
    }
    erpkb_excel_apply_standard_style($excel, array(
      'sheet' => $sheet,
      'title' => erp_export_title('MY PAYSLIP REPORT'),
      'header_row' => 4,
      'first_data_row' => 5,
      'last_data_row' => max(5, $rn - 1),
      'column_count' => count($heads),
      'decimal_columns' => array('H','I','J','K','L','M','N','O','P'),
      'filters' => array('Employee' => $employee->employee_no.' - '.$employee->full_name, 'Period' => $from.' s/d '.$to, 'Area' => $area ?: erp_export_all_text(), 'Status' => $status ?: erp_export_all_text()),
      'widths' => array('B'=>24,'C'=>24,'D'=>24,'R'=>24,'S'=>34)
    ));
    $tmp = erpkb_excel_temp_file('my_payslip_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = @filesize($tmp); $sig = @file_get_contents($tmp, false, null, 0, 2);
    if (!$size || $sig !== 'PK') {@unlink($tmp); while(ob_get_level()>$initial) ob_end_clean(); header('Content-Type:text/plain; charset=utf-8'); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit;}
    while(ob_get_level()>$initial) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="my_payslip_'.date('Ymd_His').'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp); @unlink($tmp); exit;

  default:
    mps_json('error', 'Aksi tidak dikenal.');
}
