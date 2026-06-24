<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../inc/config.php';
while (ob_get_level() > 0) ob_end_clean();
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
header('Content-Type: application/json; charset=utf-8');

function ams_json($status, $payload = array(), $code = 200) {
  http_response_code($code);
  echo json_encode(array_merge(array('status' => $status), $payload));
  exit;
}

function ams_input() {
  $raw = file_get_contents('php://input');
  $json = json_decode($raw, true);
  return array($raw, is_array($json) ? $json : array());
}

function ams_header($name) {
  $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
  return isset($_SERVER[$key]) ? trim($_SERVER[$key]) : '';
}

function ams_value($json, $key, $default = '') {
  if (isset($json[$key])) return $json[$key];
  if (isset($_POST[$key])) return $_POST[$key];
  if (isset($_GET[$key])) return $_GET[$key];
  return $default;
}

function ams_batch_no() {
  return 'AMS-' . date('Ymd-His') . '-' . substr(strtoupper(md5(uniqid('', true))), 0, 6);
}

function ams_attendance_no($date) {
  global $db;
  $ym = preg_replace('/[^0-9]/', '', substr((string)$date, 0, 7));
  if (strlen($ym) !== 6) $ym = date('Ym');
  $prefix = 'ATTM-' . $ym . '-';
  $r = $db->fetch("SELECT attendance_no FROM erp_attendance WHERE attendance_no LIKE ? ORDER BY attendance_no DESC LIMIT 1", array($prefix.'%'));
  $n = 1;
  if ($r && preg_match('/-(\d+)$/', $r->attendance_no, $m)) $n = (int)$m[1] + 1;
  return $prefix . str_pad($n, 4, '0', STR_PAD_LEFT);
}

function ams_punch_type($v) {
  $v = strtoupper(trim((string)$v));
  $map = array('0'=>'IN','1'=>'OUT','2'=>'BREAK_OUT','3'=>'BREAK_IN','4'=>'IN','5'=>'OUT','IN'=>'IN','OUT'=>'OUT','CHECKIN'=>'IN','CHECKOUT'=>'OUT','CHECK_IN'=>'IN','CHECK_OUT'=>'OUT','BREAKIN'=>'BREAK_IN','BREAKOUT'=>'BREAK_OUT','BREAK_IN'=>'BREAK_IN','BREAK_OUT'=>'BREAK_OUT');
  return isset($map[$v]) ? $map[$v] : 'UNKNOWN';
}

function ams_dt($v) {
  $v = trim((string)$v);
  if ($v === '') return '';
  if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/', $v)) {
    return str_replace('T', ' ', strlen($v) === 16 ? $v.':00' : $v);
  }
  $ts = strtotime($v);
  return $ts ? date('Y-m-d H:i:s', $ts) : '';
}

function ams_logs_from_adms($raw, $serial) {
  $logs = array();
  foreach (preg_split('/\r\n|\r|\n/', trim((string)$raw)) as $line) {
    $line = trim($line);
    if ($line === '') continue;
    $parts = preg_split('/\t+|\s{2,}/', $line);
    if (count($parts) < 2) continue;
    $logs[] = array(
      'machine_user_id' => $parts[0],
      'punch_time' => $parts[1] . (isset($parts[2]) && preg_match('/^\d{2}:\d{2}/', $parts[2]) ? ' '.$parts[2] : ''),
      'punch_type' => isset($parts[3]) ? $parts[3] : (isset($parts[2]) ? $parts[2] : 'UNKNOWN'),
      'verify_type' => isset($parts[4]) ? $parts[4] : '',
      'work_code' => isset($parts[5]) ? $parts[5] : '',
      'serial_no' => $serial,
      'raw_line' => $line
    );
  }
  return $logs;
}

function ams_resolve_employee($deviceId, $machineUserId, $employeeNo, $date) {
  global $db;
  if ($employeeNo !== '') {
    $e = $db->fetch("SELECT * FROM erp_employee_master WHERE employee_no=? LIMIT 1", array($employeeNo));
    if ($e) return $e;
  }
  if ($machineUserId !== '') {
    $m = $db->fetch("SELECT e.* FROM erp_attendance_device_user_map m JOIN erp_employee_master e ON e.id=m.employee_id WHERE m.device_id=? AND m.machine_user_id=? AND ? BETWEEN m.valid_from AND m.valid_to AND m.map_status='ACTIVE' LIMIT 1", array((int)$deviceId, $machineUserId, $date));
    if ($m) return $m;
    $e = $db->fetch("SELECT * FROM erp_employee_master WHERE employee_no=? OR personnel_no=? LIMIT 1", array($machineUserId, $machineUserId));
    if ($e) return $e;
  }
  return false;
}

function ams_shift_plan($employeeId, $date) {
  global $db;
  $ss = $db->fetch("SELECT ss.*,s.kode_shift,s.nama_shift FROM erp_shift_schedule ss LEFT JOIN erp_shift s ON s.id=ss.shift_id WHERE ss.employee_id=? AND ? BETWEEN ss.schedule_from AND ss.schedule_to AND ss.assignment_status IN ('RELEASED','PLANNED') ORDER BY ss.id DESC LIMIT 1", array((int)$employeeId, $date));
  if ($ss) {
    $start = $ss->planned_start ? $date.' '.substr($ss->planned_start,0,8) : null;
    $end = $ss->planned_end ? $date.' '.substr($ss->planned_end,0,8) : null;
    if ($start && $end && strtotime($end) <= strtotime($start)) $end = date('Y-m-d H:i:s', strtotime($end.' +1 day'));
    return array($ss->id, $ss->assignment_no, $ss->shift_id, $ss->shift_code ?: $ss->kode_shift, $ss->work_location_id, $start, $end, (int)$ss->break_minutes, (float)$ss->planned_hours_per_day);
  }
  return array(null, null, null, null, null, null, null, 60, 8);
}

function ams_minutes($a, $b) {
  if (!$a || !$b) return 0;
  return max(0, (strtotime($b) - strtotime($a)) / 60);
}

function ams_rebuild_attendance($employee, $date) {
  global $db;
  $logs = $db->query("SELECT * FROM erp_attendance_machine_log WHERE employee_id=? AND punch_date=? AND process_status IN ('PENDING','PROCESSED') ORDER BY punch_time ASC", array((int)$employee->id, $date));
  $first = null; $last = null; $count = 0;
  foreach ($logs as $l) {
    $count++;
    if (!$first && in_array($l->punch_type, array('IN','UNKNOWN','BREAK_IN'), true)) $first = $l->punch_time;
    if (in_array($l->punch_type, array('OUT','UNKNOWN','BREAK_OUT'), true)) $last = $l->punch_time;
  }
  if (!$first && $count > 0) {
    $r = $db->fetch("SELECT MIN(punch_time) first_time, MAX(punch_time) last_time, COUNT(*) jml FROM erp_attendance_machine_log WHERE employee_id=? AND punch_date=? AND process_status IN ('PENDING','PROCESSED')", array((int)$employee->id, $date));
    $first = $r ? $r->first_time : null;
    $last = ($r && (int)$r->jml > 1) ? $r->last_time : null;
  }
  if (!$first) return array(false, 'Tidak ada punch valid untuk direkap.');

  list($ssId,$ssNo,$shiftId,$shiftCode,$locId,$plannedStart,$plannedEnd,$break,$plannedHours) = ams_shift_plan($employee->id, $date);
  if ($last && strtotime($last) <= strtotime($first)) $last = date('Y-m-d H:i:s', strtotime($last.' +1 day'));
  $actual = ($first && $last) ? max(0, round((ams_minutes($first, $last) - $break) / 60, 2)) : 0;
  $late = ($plannedStart && $first && strtotime($first) > strtotime($plannedStart)) ? (int)round((strtotime($first)-strtotime($plannedStart))/60) : 0;
  $early = ($plannedEnd && $last && strtotime($last) < strtotime($plannedEnd)) ? (int)round((strtotime($plannedEnd)-strtotime($last))/60) : 0;
  $ot = max(0, round($actual - $plannedHours, 2));
  $existing = $db->fetch("SELECT * FROM erp_attendance WHERE employee_id=? AND attendance_date=? LIMIT 1", array((int)$employee->id, $date));
  if ($existing && $existing->attendance_status === 'POSTED') return array(false, 'Attendance sudah POSTED, tidak diubah.');

  $data = array(
    'employee_id'=>(int)$employee->id,'employee_no'=>$employee->employee_no,'department_code'=>$employee->department_code,
    'shift_schedule_id'=>$ssId,'assignment_no'=>$ssNo,'shift_id'=>$shiftId,'shift_code'=>$shiftCode,'work_location_id'=>$locId,
    'attendance_date'=>$date,'planned_start'=>$plannedStart,'planned_end'=>$plannedEnd,'actual_clock_in'=>$first,'actual_clock_out'=>$last,
    'break_minutes'=>$break,'planned_hours'=>$plannedHours,'actual_hours'=>$actual,'late_minutes'=>$late,'early_leave_minutes'=>$early,'overtime_hours'=>$ot,
    'attendance_type'=>'REGULAR','attendance_source'=>'MACHINE','attendance_status'=>'RECORDED','remarks'=>'Auto sync from attendance machine','updated_by'=>'machine_sync','updated_at'=>date('Y-m-d H:i:s')
  );
  if ($existing) {
    $db->update('erp_attendance', $data, 'id', (int)$existing->id);
    return array((int)$existing->id, 'Attendance updated.');
  }
  $data['attendance_no'] = ams_attendance_no($date);
  $data['created_by'] = 'machine_sync';
  $ok = $db->insert('erp_attendance', $data);
  return $ok ? array((int)$db->last_insert_id(), 'Attendance created.') : array(false, $db->getErrorMessage());
}

list($raw, $json) = ams_input();
$deviceCode = trim((string)ams_value($json, 'device_code', ams_value($json, 'SN', ams_value($json, 'sn', ''))));
$serial = trim((string)ams_value($json, 'serial_no', ams_value($json, 'SN', ams_value($json, 'sn', ''))));
if ($deviceCode === '' && $serial !== '') $deviceCode = $serial;
$apiKey = trim((string)ams_value($json, 'api_key', ams_header('X-API-Key')));
if ($deviceCode === '') ams_json('error', array('message'=>'device_code atau SN wajib dikirim.'), 400);

$device = $db->fetch("SELECT * FROM erp_attendance_device WHERE (device_code=? OR serial_no=?) AND device_status='ACTIVE' LIMIT 1", array($deviceCode, $serial));
if (!$device) ams_json('error', array('message'=>'Device tidak ditemukan/aktif.'), 404);
if ($device->api_key_hash && hash('sha256', $apiKey) !== $device->api_key_hash) ams_json('error', array('message'=>'API key tidak valid.'), 401);

$logs = array();
$payloadType = 'UNKNOWN';
if (isset($json['logs']) && is_array($json['logs'])) {
  $logs = $json['logs'];
  $payloadType = 'JSON';
} elseif (isset($json['punch_time'])) {
  $logs = array($json);
  $payloadType = 'JSON';
} elseif (isset($_POST['logs']) && is_array($_POST['logs'])) {
  $logs = $_POST['logs'];
  $payloadType = 'FORM';
} elseif (trim($raw) !== '') {
  $logs = ams_logs_from_adms($raw, $serial);
  $payloadType = 'ADMS_ATTLOG';
}
if (!count($logs)) ams_json('error', array('message'=>'Tidak ada log attendance yang dikirim.'), 400);

$batchNo = ams_batch_no();
$db->insert('erp_attendance_machine_batch', array('batch_no'=>$batchNo,'device_id'=>(int)$device->id,'device_code'=>$device->device_code,'serial_no'=>$serial ?: $device->serial_no,'request_source'=>isset($_GET['act']) ? $_GET['act'] : 'sync','request_ip'=>isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'CLI','payload_type'=>$payloadType,'total_logs'=>count($logs),'raw_payload'=>substr($raw,0,16777210),'sync_status'=>'RECEIVED'));
$batchId = (int)$db->last_insert_id();

$accepted=0; $duplicate=0; $errors=0; $messages=array();
foreach ($logs as $idx => $log) {
  $machineUser = trim((string)(isset($log['machine_user_id']) ? $log['machine_user_id'] : (isset($log['pin']) ? $log['pin'] : (isset($log['uid']) ? $log['uid'] : ''))));
  $employeeNo = strtoupper(trim((string)(isset($log['employee_no']) ? $log['employee_no'] : '')));
  $punchTime = ams_dt(isset($log['punch_time']) ? $log['punch_time'] : (isset($log['time']) ? $log['time'] : ''));
  $punchType = ams_punch_type(isset($log['punch_type']) ? $log['punch_type'] : (isset($log['status']) ? $log['status'] : 'UNKNOWN'));
  if ($punchTime === '' || ($machineUser === '' && $employeeNo === '')) {
    $errors++; $messages[]='Log '.($idx+1).': punch_time dan machine_user_id/employee_no wajib valid.'; continue;
  }
  $date = substr($punchTime,0,10);
  $employee = ams_resolve_employee((int)$device->id, $machineUser, $employeeNo, $date);
  $exists = $db->fetch("SELECT id FROM erp_attendance_machine_log WHERE device_id=? AND machine_user_id=? AND punch_time=? AND punch_type=? LIMIT 1", array((int)$device->id, $machineUser ?: $employeeNo, $punchTime, $punchType));
  if ($exists) { $duplicate++; continue; }
  $rawPayload = json_encode($log);
  $status = $employee ? 'PENDING' : 'ERROR';
  $msg = $employee ? '' : 'Employee tidak ditemukan dari mapping mesin.';
  $ok = $db->insert('erp_attendance_machine_log', array(
    'batch_id'=>$batchId,'device_id'=>(int)$device->id,'device_code'=>$device->device_code,'serial_no'=>$serial ?: $device->serial_no,
    'employee_id'=>$employee ? (int)$employee->id : null,'employee_no'=>$employee ? $employee->employee_no : ($employeeNo ?: null),'machine_user_id'=>$machineUser ?: $employeeNo,
    'punch_time'=>$punchTime,'punch_date'=>$date,'punch_type'=>$punchType,'verify_type'=>isset($log['verify_type']) ? $log['verify_type'] : null,'work_code'=>isset($log['work_code']) ? $log['work_code'] : null,'raw_status'=>isset($log['status']) ? $log['status'] : null,
    'raw_payload'=>$rawPayload,'process_status'=>$status,'process_message'=>$msg
  ));
  if (!$ok) { $errors++; $messages[]='Log '.($idx+1).': '.$db->getErrorMessage(); continue; }
  $logId = (int)$db->last_insert_id();
  if (!$employee) { $errors++; continue; }
  list($attendanceId,$processMessage) = ams_rebuild_attendance($employee, $date);
  if ($attendanceId) {
    $db->update('erp_attendance_machine_log', array('process_status'=>'PROCESSED','process_message'=>$processMessage,'attendance_id'=>$attendanceId,'processed_at'=>date('Y-m-d H:i:s')), 'id', $logId);
    $accepted++;
  } else {
    $db->update('erp_attendance_machine_log', array('process_status'=>'ERROR','process_message'=>$processMessage,'processed_at'=>date('Y-m-d H:i:s')), 'id', $logId);
    $errors++;
    $messages[]='Log '.($idx+1).': '.$processMessage;
  }
}

$syncStatus = $errors > 0 ? ($accepted > 0 ? 'PARTIAL' : 'ERROR') : 'SUCCESS';
$response = array('batch_no'=>$batchNo,'total'=>count($logs),'accepted'=>$accepted,'duplicate'=>$duplicate,'errors'=>$errors,'messages'=>array_slice($messages,0,20));
$db->update('erp_attendance_machine_batch', array('accepted_logs'=>$accepted,'duplicate_logs'=>$duplicate,'error_logs'=>$errors,'sync_status'=>$syncStatus,'response_payload'=>json_encode($response),'processed_at'=>date('Y-m-d H:i:s')), 'id', $batchId);
$db->update('erp_attendance_device', array('last_sync_at'=>date('Y-m-d H:i:s'),'updated_by'=>'machine_sync','updated_at'=>date('Y-m-d H:i:s')), 'id', (int)$device->id);

ams_json($syncStatus === 'ERROR' ? 'error' : 'good', $response, $syncStatus === 'ERROR' ? 422 : 200);
?>
