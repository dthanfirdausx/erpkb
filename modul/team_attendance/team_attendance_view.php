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

function tav_h($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function tav_num($value, $dec = 0){ return number_format((float)$value, $dec, '.', ','); }
function tav_date($value){ return ($value && $value !== '0000-00-00') ? date('d M Y', strtotime($value)) : '-'; }
function tav_time($value){ return ($value && $value !== '0000-00-00 00:00:00') ? date('H:i', strtotime($value)) : '-'; }
function tav_valid_date($value, $fallback){ $value = trim((string)$value); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback; }
function tav_type_class($type){
  $map = array('REGULAR'=>'success','OVERTIME'=>'primary','BUSINESS_TRIP'=>'info','TRAINING'=>'info','REMOTE'=>'default','LEAVE'=>'warning','SICK'=>'warning','ABSENT'=>'danger');
  return isset($map[$type]) ? $map[$type] : 'default';
}
function tav_status_class($status){
  $map = array('DRAFT'=>'warning','RECORDED'=>'info','APPROVED'=>'success','POSTED'=>'primary','REJECTED'=>'danger','CANCELLED'=>'default');
  return isset($map[$status]) ? $map[$status] : 'default';
}
function tav_workdays($from, $to){
  $start = strtotime($from); $end = strtotime($to); if (!$start || !$end || $end < $start) return 0;
  $days = 0; for ($t = $start; $t <= $end; $t = strtotime('+1 day', $t)) if ((int)date('N', $t) <= 5) $days++;
  return $days;
}
function tav_in_placeholders($count){ return implode(',', array_fill(0, max(1, (int)$count), '?')); }

$today = date('Y-m-d');
$defaultFrom = date('Y-m-01');
$from = tav_valid_date(isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '', $defaultFrom);
$to = tav_valid_date(isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '', $today);
if (strtotime($from) > strtotime($to)) $from = $to;
$filterEmployee = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$filterDept = isset($_GET['department_code']) ? trim($_GET['department_code']) : '';
$filterType = isset($_GET['attendance_type']) ? trim($_GET['attendance_type']) : '';
$filterStatus = isset($_GET['attendance_status']) ? trim($_GET['attendance_status']) : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

$currentUserId = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;
$manager = null; $subordinates = array(); $subIds = array(); $rows = array(); $memberSummary = array();
$summary = array('total'=>0,'present_days'=>0,'absence_days'=>0,'leave_days'=>0,'sick_days'=>0,'late_count'=>0,'late_minutes'=>0,'early_count'=>0,'early_minutes'=>0,'planned_hours'=>0,'actual_hours'=>0,'overtime_hours'=>0);
$chartCategories = array(); $chartActual = array(); $chartPlanned = array(); $chartLate = array(); $typeChart = array(); $employeeLateChart = array();

if ($currentUserId > 0) {
  $manager = $db->fetch("SELECT e.*, u.username, u.foto_user, d.nm_dept, jt.job_title_name
    FROM erp_employee_master e
    LEFT JOIN sys_users u ON u.id=e.user_id
    LEFT JOIN dept d ON d.kd_dept=e.department_code
    LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id
    WHERE e.user_id=? LIMIT 1", array($currentUserId));
}

if ($manager) {
  $stmtSub = $db->query("SELECT e.*, d.nm_dept, jt.job_title_name
    FROM erp_employee_master e
    LEFT JOIN dept d ON d.kd_dept=e.department_code
    LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id
    WHERE e.manager_employee_id=?
    ORDER BY e.department_code, e.full_name", array((int)$manager->id));
  $subordinates = $stmtSub ? $stmtSub->fetchAll(PDO::FETCH_OBJ) : array();
  foreach ($subordinates as $s) $subIds[] = (int)$s->id;
}

if (!empty($subIds)) {
  $params = $subIds;
  $where = " WHERE a.employee_id IN (".tav_in_placeholders(count($subIds)).") AND a.attendance_date BETWEEN ? AND ? ";
  $params[] = $from; $params[] = $to;
  if ($filterEmployee && in_array($filterEmployee, $subIds, true)) { $where .= " AND a.employee_id=? "; $params[] = $filterEmployee; }
  if ($filterDept !== '') { $where .= " AND a.department_code=? "; $params[] = $filterDept; }
  if ($filterType !== '') { $where .= " AND a.attendance_type=? "; $params[] = $filterType; }
  if ($filterStatus !== '') { $where .= " AND a.attendance_status=? "; $params[] = $filterStatus; }
  if ($keyword !== '') {
    $kw = '%'.$keyword.'%';
    $where .= " AND (a.attendance_no LIKE ? OR a.employee_no LIKE ? OR e.full_name LIKE ? OR a.remarks LIKE ? OR a.sap_reference LIKE ?) ";
    array_push($params, $kw, $kw, $kw, $kw, $kw);
  }

  $sumRow = $db->fetch("SELECT COUNT(*) total,
      SUM(a.attendance_type IN ('REGULAR','OVERTIME','BUSINESS_TRIP','TRAINING','REMOTE')) present_days,
      SUM(a.attendance_type='ABSENT') absence_days,
      SUM(a.attendance_type='LEAVE') leave_days,
      SUM(a.attendance_type='SICK') sick_days,
      SUM(a.late_minutes>0) late_count,
      COALESCE(SUM(a.late_minutes),0) late_minutes,
      SUM(a.early_leave_minutes>0) early_count,
      COALESCE(SUM(a.early_leave_minutes),0) early_minutes,
      COALESCE(SUM(a.planned_hours),0) planned_hours,
      COALESCE(SUM(a.actual_hours),0) actual_hours,
      COALESCE(SUM(a.overtime_hours),0) overtime_hours
    FROM erp_attendance a JOIN erp_employee_master e ON e.id=a.employee_id $where", $params);
  if ($sumRow) foreach ($summary as $k=>$v) $summary[$k] = isset($sumRow->$k) ? (float)$sumRow->$k : 0;

  $stmtRows = $db->query("SELECT a.*, e.full_name, e.employee_group, d.nm_dept, s.nama_shift, wl.location_code, wl.location_name
    FROM erp_attendance a
    JOIN erp_employee_master e ON e.id=a.employee_id
    LEFT JOIN dept d ON d.kd_dept=a.department_code
    LEFT JOIN erp_shift s ON s.id=a.shift_id
    LEFT JOIN erp_work_location wl ON wl.id=a.work_location_id
    $where
    ORDER BY a.attendance_date DESC, e.full_name, a.attendance_no DESC
    LIMIT 300", $params);
  $rows = $stmtRows ? $stmtRows->fetchAll(PDO::FETCH_OBJ) : array();

  $stmtMember = $db->query("SELECT e.id, e.employee_no, e.full_name, e.department_code, d.nm_dept, jt.job_title_name,
      COUNT(a.id) total,
      COALESCE(SUM(a.attendance_type IN ('REGULAR','OVERTIME','BUSINESS_TRIP','TRAINING','REMOTE')),0) present_days,
      COALESCE(SUM(a.attendance_type IN ('ABSENT','SICK','LEAVE')),0) exception_days,
      COALESCE(SUM(a.late_minutes>0),0) late_count,
      COALESCE(SUM(a.late_minutes),0) late_minutes,
      COALESCE(SUM(a.early_leave_minutes>0),0) early_count,
      COALESCE(SUM(a.overtime_hours),0) overtime_hours,
      COALESCE(AVG(NULLIF(a.actual_hours,0)),0) avg_hours
    FROM erp_employee_master e
    LEFT JOIN dept d ON d.kd_dept=e.department_code
    LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id
    LEFT JOIN erp_attendance a ON a.employee_id=e.id AND a.attendance_date BETWEEN ? AND ?
    WHERE e.id IN (".tav_in_placeholders(count($subIds)).")
    GROUP BY e.id
    ORDER BY late_minutes DESC, e.full_name", array_merge(array($from, $to), $subIds));
  $memberSummary = $stmtMember ? $stmtMember->fetchAll(PDO::FETCH_OBJ) : array();

  $stmtTrend = $db->query("SELECT a.attendance_date, SUM(a.actual_hours) actual_hours, SUM(a.planned_hours) planned_hours, SUM(a.late_minutes) late_minutes
    FROM erp_attendance a JOIN erp_employee_master e ON e.id=a.employee_id $where
    GROUP BY a.attendance_date ORDER BY a.attendance_date", $params);
  $trend = array(); if ($stmtTrend) foreach ($stmtTrend as $r) $trend[$r->attendance_date] = $r;
  for ($t = strtotime($from); $t <= strtotime($to); $t = strtotime('+1 day', $t)) {
    $d = date('Y-m-d', $t); $chartCategories[] = date('d M', $t);
    $chartActual[] = isset($trend[$d]) ? (float)$trend[$d]->actual_hours : 0;
    $chartPlanned[] = isset($trend[$d]) ? (float)$trend[$d]->planned_hours : 0;
    $chartLate[] = isset($trend[$d]) ? (int)$trend[$d]->late_minutes : 0;
  }

  $stmtType = $db->query("SELECT a.attendance_type label, COUNT(*) total FROM erp_attendance a JOIN erp_employee_master e ON e.id=a.employee_id $where GROUP BY a.attendance_type ORDER BY total DESC", $params);
  if ($stmtType) foreach ($stmtType as $r) $typeChart[] = array($r->label ?: 'UNKNOWN', (int)$r->total);
  foreach (array_slice($memberSummary, 0, 8) as $m) $employeeLateChart[] = array($m->employee_no.' - '.$m->full_name, (int)$m->late_minutes);
}

$teamSize = count($subordinates);
$expectedSlots = tav_workdays($from, $to) * max(1, $teamSize);
$presenceRate = $expectedSlots > 0 ? min(100, round(($summary['present_days'] / $expectedSlots) * 100, 1)) : 0;
$punctualRate = $summary['total'] > 0 ? max(0, round((($summary['total'] - $summary['late_count']) / $summary['total']) * 100, 1)) : 0;
$avgHours = $summary['total'] > 0 ? round($summary['actual_hours'] / $summary['total'], 2) : 0;
$photoUrl = $manager ? erpkb_user_photo_url($manager->foto_user, 'back_profil_foto') : base_admin().'assets/dist/img/default-user-neutral.svg';
?>
<style>
.ta-hero{position:relative;overflow:hidden;border-radius:20px;background:linear-gradient(135deg,#0f172a,#0f766e 55%,#2563eb);color:#fff;padding:24px;margin-bottom:18px;box-shadow:0 18px 42px rgba(15,23,42,.18)}
.ta-hero:after{content:"";position:absolute;right:-95px;top:-120px;width:330px;height:330px;border-radius:50%;background:rgba(255,255,255,.12)}
.ta-profile{position:relative;z-index:1;display:flex;gap:16px;align-items:center;flex-wrap:wrap}.ta-photo{width:72px;height:72px;border-radius:22px;object-fit:cover;background:#fff;border:3px solid rgba(255,255,255,.7)}
.ta-hero h1{margin:0 0 6px;font-weight:800;letter-spacing:-.02em}.ta-hero p{margin:0;color:rgba(255,255,255,.86)}.ta-hero .btn{border-radius:999px;font-weight:700}
.ta-card,.ta-filter{border:1px solid #e5edf5;border-radius:18px;background:#fff;box-shadow:0 10px 24px rgba(15,23,42,.055);margin-bottom:16px}.ta-filter .box-body,.ta-card .box-body{padding:18px 20px}.ta-card .box-header{padding:16px 20px 4px;border-bottom:0}.ta-card .box-title{font-weight:800;color:#0f172a}
.ta-filter label{font-size:12px;color:#475569;text-transform:uppercase;letter-spacing:.03em}.ta-filter .form-control{border-radius:10px}.select2-container{width:100%!important}
.ta-kpi{border:1px solid #e5edf5;border-radius:18px;background:#fff;padding:16px;min-height:118px;margin-bottom:16px;box-shadow:0 8px 22px rgba(15,23,42,.045)}.ta-kpi i{width:38px;height:38px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;color:#fff;background:#0f766e}.ta-kpi span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.ta-kpi strong{display:block;color:#0f172a;font-size:25px;line-height:1.25}.ta-kpi small{color:#64748b}
.ta-chart{height:305px}.ta-chart-sm{height:270px}.ta-table>thead>tr>th{background:#f8fafc;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:.03em}.ta-table>tbody>tr>td{vertical-align:middle;font-size:12px}.ta-timebox{display:flex;gap:6px;flex-wrap:wrap}.ta-chip{padding:5px 8px;border-radius:999px;background:#f8fafc;border:1px solid #e5edf5;color:#334155;font-weight:700}
.ta-progress{height:8px;margin-top:7px;border-radius:999px;background:#e5e7eb;overflow:hidden}.ta-progress span{display:block;height:100%;background:linear-gradient(90deg,#0f766e,#22c55e)}
.ta-empty{padding:24px;border:1px dashed #cbd5e1;border-radius:18px;background:#f8fafc;color:#475569}.ta-action{white-space:nowrap}
@media(max-width:767px){.ta-hero h1{font-size:23px}.ta-chart,.ta-chart-sm{height:250px}}
</style>

<section class="content-header">
  <h1><?=hr_h('hr_team_attendance', 'Team Attendance');?> <small>Manager Self Service</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li><li>Manager Self Service</li><li class="active"><?=hr_h('hr_team_attendance', 'Team Attendance');?></li></ol>
</section>
<section class="content">
  <div class="ta-hero">
    <div class="row">
      <div class="col-md-8">
        <div class="ta-profile">
          <img class="ta-photo" src="<?=tav_h($photoUrl);?>" alt="Manager">
          <div>
            <h1>Team Attendance Control Room</h1>
            <p><?= $manager ? tav_h($manager->employee_no.' - '.$manager->full_name) : 'Manager belum terhubung ke employee master'; ?><?= $manager && $manager->nm_dept ? ' | '.tav_h($manager->nm_dept) : ''; ?></p>
            <p>Monitor kehadiran bawahan langsung: disiplin masuk, jam aktual, overtime, absence, dan exception yang perlu ditindaklanjuti.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 text-right" style="position:relative;z-index:1">
        <a class="btn btn-success" href="<?=base_admin();?>modul/team_attendance/team_attendance_action.php?act=export&<?=http_build_query($_GET);?>"><i class="fa fa-file-excel-o"></i> <?=hr_h('common_export_excel', 'Export Excel');?></a>
      </div>
    </div>
  </div>

  <?php if (!$manager) { ?>
    <div class="ta-empty"><b>Employee profile manager belum ditemukan.</b><br>User login ini belum tersambung ke `erp_employee_master.user_id`, jadi sistem belum bisa membaca bawahan.</div>
  <?php } elseif (empty($subordinates)) { ?>
    <div class="ta-empty"><b>Belum ada bawahan langsung.</b><br>Isi `manager_employee_id` di Employee Master agar data team attendance muncul untuk manager ini.</div>
  <?php } else { ?>
  <div class="box ta-filter">
    <div class="box-body">
      <form method="get" class="form-horizontal">
        <div class="form-group">
          <label class="col-lg-1 control-label"><?=hr_h('hr_date', 'Date');?></label>
          <div class="col-lg-2"><input name="tgl_awal" class="form-control ta-date" value="<?=tav_h($from);?>"></div>
          <div class="col-lg-2"><input name="tgl_akhir" class="form-control ta-date" value="<?=tav_h($to);?>"></div>
          <label class="col-lg-1 control-label"><?=hr_h('hr_employee', 'Employee');?></label>
          <div class="col-lg-3">
            <select name="employee_id" class="form-control select2-basic">
              <option value="">All Team Member</option>
              <?php foreach ($subordinates as $s) { ?><option value="<?=$s->id;?>" <?=$filterEmployee===(int)$s->id?'selected':'';?>><?=tav_h($s->employee_no.' - '.$s->full_name);?></option><?php } ?>
            </select>
          </div>
          <label class="col-lg-1 control-label"><?=hr_h('common_status', 'Status');?></label>
          <div class="col-lg-2">
            <select name="attendance_status" class="form-control select2-basic"><option value="">All</option><?php foreach(array('DRAFT','RECORDED','APPROVED','POSTED','REJECTED','CANCELLED') as $v){ ?><option <?=$filterStatus===$v?'selected':'';?>><?=$v;?></option><?php } ?></select>
          </div>
        </div>
        <div class="form-group">
          <label class="col-lg-1 control-label">Dept</label>
          <div class="col-lg-2">
            <select name="department_code" class="form-control select2-basic"><option value="">All Department</option><?php $deptSeen=array(); foreach($subordinates as $s){ if($s->department_code && !isset($deptSeen[$s->department_code])){ $deptSeen[$s->department_code]=1; ?><option value="<?=tav_h($s->department_code);?>" <?=$filterDept===$s->department_code?'selected':'';?>><?=tav_h($s->department_code.' - '.$s->nm_dept);?></option><?php }} ?></select>
          </div>
          <label class="col-lg-1 control-label">Type</label>
          <div class="col-lg-2">
            <select name="attendance_type" class="form-control select2-basic"><option value="">All</option><?php foreach(array('REGULAR','OVERTIME','BUSINESS_TRIP','TRAINING','REMOTE','LEAVE','SICK','ABSENT') as $v){ ?><option <?=$filterType===$v?'selected':'';?>><?=$v;?></option><?php } ?></select>
          </div>
          <label class="col-lg-1 control-label"><?=hr_h('common_search', 'Search');?></label>
          <div class="col-lg-3"><input name="keyword" class="form-control" value="<?=tav_h($keyword);?>" placeholder="Attendance no, employee, remarks"></div>
          <div class="col-lg-2"><button class="btn btn-primary"><i class="fa fa-filter"></i> <?=hr_h('common_filter', 'Filter');?></button> <a class="btn btn-default" href="<?=base_index();?>team-attendance"><i class="fa fa-refresh"></i></a></div>
        </div>
      </form>
    </div>
  </div>

  <div class="row">
    <div class="col-md-2 col-sm-6"><div class="ta-kpi"><i class="fa fa-users"></i><span>Team Members</span><strong><?=tav_num($teamSize);?></strong><small>Bawahan langsung</small></div></div>
    <div class="col-md-2 col-sm-6"><div class="ta-kpi"><i class="fa fa-calendar-check-o"></i><span>Presence Rate</span><strong><?=tav_num($presenceRate,1);?>%</strong><div class="ta-progress"><span style="width:<?=$presenceRate;?>%"></span></div></div></div>
    <div class="col-md-2 col-sm-6"><div class="ta-kpi"><i class="fa fa-clock-o"></i><span>Punctual Rate</span><strong><?=tav_num($punctualRate,1);?>%</strong><small><?=tav_num($summary['late_count']);?> late records</small></div></div>
    <div class="col-md-2 col-sm-6"><div class="ta-kpi"><i class="fa fa-hourglass-half"></i><span>Actual Hours</span><strong><?=tav_num($summary['actual_hours'],1);?></strong><small>Avg <?=tav_num($avgHours,2);?> jam/record</small></div></div>
    <div class="col-md-2 col-sm-6"><div class="ta-kpi"><i class="fa fa-exclamation-triangle"></i><span>Late Minutes</span><strong><?=tav_num($summary['late_minutes']);?></strong><small>Early <?=tav_num($summary['early_minutes']);?> min</small></div></div>
    <div class="col-md-2 col-sm-6"><div class="ta-kpi"><i class="fa fa-moon-o"></i><span><?=hr_h('hr_overtime', 'Overtime');?></span><strong><?=tav_num($summary['overtime_hours'],1);?></strong><small>Jam OT team</small></div></div>
  </div>

  <div class="row">
    <div class="col-md-8"><div class="box ta-card"><div class="box-header"><h3 class="box-title">Actual vs Planned Hours</h3></div><div class="box-body"><div id="ta_hours_chart" class="ta-chart"></div></div></div></div>
    <div class="col-md-4"><div class="box ta-card"><div class="box-header"><h3 class="box-title">Attendance Type</h3></div><div class="box-body"><div id="ta_type_chart" class="ta-chart-sm"></div></div></div></div>
  </div>
  <div class="row">
    <div class="col-md-7"><div class="box ta-card"><div class="box-header"><h3 class="box-title">Late Minutes Trend</h3></div><div class="box-body"><div id="ta_late_chart" class="ta-chart-sm"></div></div></div></div>
    <div class="col-md-5"><div class="box ta-card"><div class="box-header"><h3 class="box-title">Top Late by Employee</h3></div><div class="box-body"><div id="ta_employee_late_chart" class="ta-chart-sm"></div></div></div></div>
  </div>

  <div class="box ta-card">
    <div class="box-header"><h3 class="box-title"><i class="fa fa-user-check"></i> Team Member Summary</h3></div>
    <div class="box-body table-responsive">
      <table id="ta_member_table" class="table table-bordered table-striped ta-table">
        <thead><tr><th><?=hr_h('hr_employee', 'Employee');?></th><th><?=hr_h('hr_department', 'Department');?></th><th><?=hr_h('hr_position', 'Position');?></th><th class="text-right">Records</th><th class="text-right">Present</th><th class="text-right">Late</th><th class="text-right">Late Min</th><th class="text-right">Early</th><th class="text-right">OT Hours</th><th class="text-right">Avg Hours</th></tr></thead>
        <tbody>
          <?php foreach($memberSummary as $m){ ?>
            <tr>
              <td><b><?=tav_h($m->employee_no);?></b><br><?=tav_h($m->full_name);?></td>
              <td><?=tav_h(($m->department_code ?: '-').' - '.($m->nm_dept ?: '-'));?></td>
              <td><?=tav_h($m->job_title_name ?: '-');?></td>
              <td class="text-right"><?=tav_num($m->total);?></td>
              <td class="text-right"><?=tav_num($m->present_days);?></td>
              <td class="text-right"><?=tav_num($m->late_count);?></td>
              <td class="text-right"><?=tav_num($m->late_minutes);?></td>
              <td class="text-right"><?=tav_num($m->early_count);?></td>
              <td class="text-right"><?=tav_num($m->overtime_hours,1);?></td>
              <td class="text-right"><?=tav_num($m->avg_hours,2);?></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="box ta-card">
    <div class="box-header"><h3 class="box-title"><i class="fa fa-list"></i> Attendance Detail</h3></div>
    <div class="box-body table-responsive">
      <table id="ta_detail_table" class="table table-bordered table-striped ta-table" style="width:100%">
        <thead><tr><th><?=hr_h('hr_date', 'Date');?></th><th><?=hr_h('hr_employee', 'Employee');?></th><th>Shift / Location</th><th>Plan vs Actual</th><th>Hours</th><th>Exception</th><th>Type</th><th><?=hr_h('common_status', 'Status');?></th><th><?=hr_h('common_remarks', 'Remarks');?></th><th><?=hr_h('common_action', 'Action');?></th></tr></thead>
        <tbody>
          <?php foreach($rows as $r){ ?>
            <tr>
              <td data-order="<?=tav_h($r->attendance_date);?>"><b><?=tav_date($r->attendance_date);?></b><br><small><?=tav_h($r->attendance_no);?></small></td>
              <td><b><?=tav_h($r->employee_no);?></b><br><?=tav_h($r->full_name);?></td>
              <td><?=tav_h(($r->shift_code ?: '-').' - '.($r->nama_shift ?: '-'));?><br><small><?=tav_h(($r->location_code ?: '-').' - '.($r->location_name ?: '-'));?></small></td>
              <td><div class="ta-timebox"><span class="ta-chip">Plan <?=tav_time($r->planned_start);?>-<?=tav_time($r->planned_end);?></span><span class="ta-chip">Actual <?=tav_time($r->actual_clock_in);?>-<?=tav_time($r->actual_clock_out);?></span></div></td>
              <td class="text-right">Plan <?=tav_num($r->planned_hours,2);?><br>Actual <b><?=tav_num($r->actual_hours,2);?></b><br>OT <?=tav_num($r->overtime_hours,2);?></td>
              <td>Late <b><?=tav_num($r->late_minutes);?></b> min<br>Early <b><?=tav_num($r->early_leave_minutes);?></b> min</td>
              <td><span class="label label-<?=tav_type_class($r->attendance_type);?>"><?=tav_h($r->attendance_type);?></span></td>
              <td><span class="label label-<?=tav_status_class($r->attendance_status);?>"><?=tav_h($r->attendance_status);?></span></td>
              <td><?=tav_h($r->remarks ?: $r->absence_reason ?: $r->correction_reason ?: '-');?></td>
              <td class="text-center">
                <button type="button" class="btn btn-info btn-xs btn-team-attendance-detail"
                  data-no="<?=tav_h($r->attendance_no);?>"
                  data-date="<?=tav_h(tav_date($r->attendance_date));?>"
                  data-employee="<?=tav_h($r->employee_no.' - '.$r->full_name);?>"
                  data-shift="<?=tav_h(($r->shift_code ?: '-').' - '.($r->nama_shift ?: '-'));?>"
                  data-location="<?=tav_h(($r->location_code ?: '-').' - '.($r->location_name ?: '-'));?>"
                  data-plan="<?=tav_h(tav_time($r->planned_start).' - '.tav_time($r->planned_end));?>"
                  data-actual="<?=tav_h(tav_time($r->actual_clock_in).' - '.tav_time($r->actual_clock_out));?>"
                  data-hours="<?=tav_h('Plan '.tav_num($r->planned_hours,2).' / Actual '.tav_num($r->actual_hours,2).' / OT '.tav_num($r->overtime_hours,2));?>"
                  data-exception="<?=tav_h('Late '.tav_num($r->late_minutes).' min, Early '.tav_num($r->early_leave_minutes).' min');?>"
                  data-status="<?=tav_h($r->attendance_type.' / '.$r->attendance_status);?>"
                  data-remarks="<?=tav_h($r->remarks ?: $r->absence_reason ?: $r->correction_reason ?: '-');?>">
                  <i class="fa fa-eye"></i>
                </button>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php } ?>
</section>
<div class="modal fade" id="modal_team_attendance_detail">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-eye"></i> Team Attendance Detail</h4></div>
      <div class="modal-body" id="team_attendance_detail_body"></div>
    </div>
  </div>
</div>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="<?=base_admin();?>assets/js/highcharts.js"></script>
<script>
$(function(){
  if($.fn.datepicker){$('.ta-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('.select2-basic').select2({width:'100%',allowClear:true});}
  if($.fn.DataTable){
    $('#ta_member_table').DataTable({pageLength:10,order:[[6,'desc']],dom:"<'row'<'col-sm-6'l><'col-sm-6'f>>tr<'row'<'col-sm-5'i><'col-sm-7'p>>"});
    $('#ta_detail_table').DataTable({pageLength:25,order:[[0,'desc']],dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>tr<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=hr_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','print']}],columnDefs:[{targets:[9],orderable:false,searchable:false}]});
  }
  $(document).on('click','.btn-team-attendance-detail',function(){
    var b=$(this);
    $('#team_attendance_detail_body').html('<div class="table-responsive"><table class="table table-bordered table-striped">'+
      '<tr><th style="width:190px">Attendance No</th><td>'+b.data('no')+'</td></tr>'+
      '<tr><th><?=hr_h('hr_date', 'Date');?></th><td>'+b.data('date')+'</td></tr>'+
      '<tr><th><?=hr_h('hr_employee', 'Employee');?></th><td>'+b.data('employee')+'</td></tr>'+
      '<tr><th><?=hr_h('hr_shift', 'Shift');?></th><td>'+b.data('shift')+'</td></tr>'+
      '<tr><th>Location</th><td>'+b.data('location')+'</td></tr>'+
      '<tr><th>Plan / Actual</th><td>'+b.data('plan')+' / '+b.data('actual')+'</td></tr>'+
      '<tr><th>Hours</th><td>'+b.data('hours')+'</td></tr>'+
      '<tr><th>Exception</th><td>'+b.data('exception')+'</td></tr>'+
      '<tr><th>Type / Status</th><td>'+b.data('status')+'</td></tr>'+
      '<tr><th><?=hr_h('common_remarks', 'Remarks');?></th><td>'+b.data('remarks')+'</td></tr>'+
      '</table></div>');
    $('#modal_team_attendance_detail').modal('show');
  });
  if(typeof Highcharts !== 'undefined' && document.getElementById('ta_hours_chart')){
    Highcharts.chart('ta_hours_chart',{chart:{type:'spline'},title:{text:null},xAxis:{categories:<?=json_encode($chartCategories);?>},yAxis:{title:{text:'Hours'}},tooltip:{shared:true},credits:{enabled:false},series:[{name:'Actual Hours',data:<?=json_encode($chartActual);?>,color:'#0f766e'},{name:'Planned Hours',data:<?=json_encode($chartPlanned);?>,color:'#2563eb'}]});
    Highcharts.chart('ta_type_chart',{chart:{type:'pie'},title:{text:null},tooltip:{pointFormat:'<b>{point.y}</b> records'},credits:{enabled:false},plotOptions:{pie:{innerSize:'58%',dataLabels:{enabled:true,format:'{point.name}<br>{point.y}'}}},series:[{name:'Type',data:<?=json_encode($typeChart);?>}]});
    Highcharts.chart('ta_late_chart',{chart:{type:'column'},title:{text:null},xAxis:{categories:<?=json_encode($chartCategories);?>},yAxis:{title:{text:'Minutes'}},credits:{enabled:false},series:[{name:'Late Minutes',data:<?=json_encode($chartLate);?>,color:'#f97316'}]});
    Highcharts.chart('ta_employee_late_chart',{chart:{type:'bar'},title:{text:null},xAxis:{type:'category'},yAxis:{title:{text:'Minutes'}},legend:{enabled:false},credits:{enabled:false},series:[{name:'Late Minutes',data:<?=json_encode($employeeLateChart);?>,color:'#ef4444'}]});
  }
});
</script>
