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

function ma_h($value)
{
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function ma_date($value)
{
  if (!$value || $value === '0000-00-00') return '-';
  return date('d M Y', strtotime($value));
}

function ma_time($value)
{
  if (!$value || $value === '0000-00-00 00:00:00') return '-';
  return date('H:i', strtotime($value));
}

function ma_num($value, $decimal = 0)
{
  return number_format((float) $value, $decimal, '.', ',');
}

function ma_status_class($status)
{
  $map = array(
    'DRAFT' => 'warning',
    'RECORDED' => 'info',
    'APPROVED' => 'success',
    'POSTED' => 'primary',
    'REJECTED' => 'danger',
    'CANCELLED' => 'default'
  );
  return isset($map[$status]) ? $map[$status] : 'default';
}

function ma_type_class($type)
{
  $map = array(
    'REGULAR' => 'success',
    'OVERTIME' => 'primary',
    'BUSINESS_TRIP' => 'info',
    'TRAINING' => 'info',
    'REMOTE' => 'default',
    'LEAVE' => 'warning',
    'SICK' => 'warning',
    'ABSENT' => 'danger'
  );
  return isset($map[$type]) ? $map[$type] : 'default';
}

function ma_valid_date($value, $fallback)
{
  $value = trim((string) $value);
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;
  return $fallback;
}

function ma_workdays($from, $to)
{
  $start = strtotime($from);
  $end = strtotime($to);
  if (!$start || !$end || $end < $start) return 0;
  $days = 0;
  for ($t = $start; $t <= $end; $t = strtotime('+1 day', $t)) {
    $dow = (int) date('N', $t);
    if ($dow <= 5) $days++;
  }
  return $days;
}

$today = date('Y-m-d');
$defaultFrom = date('Y-m-01');
$from = ma_valid_date(isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '', $defaultFrom);
$to = ma_valid_date(isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '', $today);
if (strtotime($to) > strtotime($today)) $to = $today;
if (strtotime($from) > strtotime($to)) $from = $to;
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$filterType = isset($_GET['type']) ? trim($_GET['type']) : '';

$currentUserId = isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0;
$employee = null;
$rows = array();
$summary = array(
  'total' => 0,
  'posted' => 0,
  'present_days' => 0,
  'absence_days' => 0,
  'leave_days' => 0,
  'sick_days' => 0,
  'late_count' => 0,
  'late_minutes' => 0,
  'early_count' => 0,
  'early_minutes' => 0,
  'planned_hours' => 0,
  'actual_hours' => 0,
  'overtime_hours' => 0
);
$chartCategories = array();
$chartActual = array();
$chartPlanned = array();
$chartLate = array();
$typeChart = array();
$statusChart = array();

if ($currentUserId > 0) {
  $employee = $db->fetch("SELECT e.*, u.username, u.foto_user, d.nm_dept, jt.job_title_name, jt.job_level
    FROM erp_employee_master e
    LEFT JOIN sys_users u ON u.id=e.user_id
    LEFT JOIN dept d ON d.kd_dept=e.department_code
    LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id
    WHERE e.user_id=? LIMIT 1", array($currentUserId));
}

if ($employee) {
  $where = " WHERE a.employee_id=? AND a.attendance_date BETWEEN ? AND ? ";
  $params = array((int) $employee->id, $from, $to);
  if ($filterStatus !== '') {
    $where .= " AND a.attendance_status=? ";
    $params[] = $filterStatus;
  }
  if ($filterType !== '') {
    $where .= " AND a.attendance_type=? ";
    $params[] = $filterType;
  }

  $sumRow = $db->fetch("SELECT COUNT(*) total,
      SUM(a.attendance_status='POSTED') posted,
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
    FROM erp_attendance a $where", $params);
  if ($sumRow) {
    foreach ($summary as $key => $value) {
      $summary[$key] = isset($sumRow->$key) ? (float) $sumRow->$key : 0;
    }
  }

  $stmt = $db->query("SELECT a.*, d.nm_dept, wl.location_code, wl.location_name, s.nama_shift
    FROM erp_attendance a
    LEFT JOIN dept d ON d.kd_dept=a.department_code
    LEFT JOIN erp_work_location wl ON wl.id=a.work_location_id
    LEFT JOIN erp_shift s ON s.id=a.shift_id
    $where
    ORDER BY a.attendance_date DESC, a.attendance_no DESC
    LIMIT 120", $params);
  $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_OBJ) : array();

  $trendStmt = $db->query("SELECT a.attendance_date, SUM(a.actual_hours) actual_hours, SUM(a.planned_hours) planned_hours, SUM(a.late_minutes) late_minutes
    FROM erp_attendance a $where
    GROUP BY a.attendance_date
    ORDER BY a.attendance_date", $params);
  $trendMap = array();
  if ($trendStmt) {
    foreach ($trendStmt as $r) {
      $trendMap[$r->attendance_date] = $r;
    }
  }

  for ($t = strtotime($from); $t <= strtotime($to); $t = strtotime('+1 day', $t)) {
    $d = date('Y-m-d', $t);
    $chartCategories[] = date('d M', $t);
    $chartActual[] = isset($trendMap[$d]) ? (float) $trendMap[$d]->actual_hours : 0;
    $chartPlanned[] = isset($trendMap[$d]) ? (float) $trendMap[$d]->planned_hours : 0;
    $chartLate[] = isset($trendMap[$d]) ? (int) $trendMap[$d]->late_minutes : 0;
  }

  $typeStmt = $db->query("SELECT a.attendance_type label, COUNT(*) total FROM erp_attendance a $where GROUP BY a.attendance_type ORDER BY total DESC", $params);
  if ($typeStmt) {
    foreach ($typeStmt as $r) {
      $typeChart[] = array($r->label, (int) $r->total);
    }
  }
  $statusStmt = $db->query("SELECT a.attendance_status label, COUNT(*) total FROM erp_attendance a $where GROUP BY a.attendance_status ORDER BY total DESC", $params);
  if ($statusStmt) {
    foreach ($statusStmt as $r) {
      $statusChart[] = array($r->label, (int) $r->total);
    }
  }
}

$expectedDays = ma_workdays($from, $to);
$presenceRate = $expectedDays > 0 ? min(100, round(($summary['present_days'] / $expectedDays) * 100, 1)) : 0;
$punctualRate = $summary['total'] > 0 ? max(0, round((($summary['total'] - $summary['late_count']) / $summary['total']) * 100, 1)) : 0;
$avgHours = $summary['total'] > 0 ? round($summary['actual_hours'] / $summary['total'], 2) : 0;
$photoUrl = $employee ? erpkb_user_photo_url($employee->foto_user, 'back_profil_foto') : base_admin().'assets/dist/img/default-user-neutral.svg';
?>

<style>
  .ma-hero{position:relative;overflow:hidden;border-radius:22px;background:linear-gradient(135deg,#0f766e,#2563eb);color:#fff;padding:24px;margin-bottom:18px;box-shadow:0 18px 40px rgba(37,99,235,.22)}
  .ma-hero:after{content:"";position:absolute;right:-80px;top:-110px;width:285px;height:285px;border-radius:50%;background:rgba(255,255,255,.13)}
  .ma-profile{position:relative;z-index:1;display:flex;align-items:center;gap:16px;flex-wrap:wrap}
  .ma-photo{width:74px;height:74px;object-fit:cover;border-radius:20px;border:3px solid rgba(255,255,255,.75);background:#fff;box-shadow:0 10px 24px rgba(15,23,42,.2)}
  .ma-hero h1{margin:0 0 6px;font-weight:800;letter-spacing:-.02em}.ma-hero p{margin:0;color:rgba(255,255,255,.86)}
  .ma-filter{border:1px solid #e5edf5;border-radius:18px;background:#fff;box-shadow:0 10px 24px rgba(15,23,42,.055);margin-bottom:16px}
  .ma-filter .box-body{padding:18px 20px}.ma-filter label{font-size:12px;color:#475569;text-transform:uppercase;letter-spacing:.03em}
  .ma-kpi{border:1px solid #e5edf5;border-radius:18px;background:#fff;padding:16px;min-height:118px;margin-bottom:16px;box-shadow:0 8px 22px rgba(15,23,42,.045)}
  .ma-kpi i{width:38px;height:38px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;color:#fff;background:#0f766e}
  .ma-kpi span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.ma-kpi strong{display:block;color:#0f172a;font-size:25px;line-height:1.25}.ma-kpi small{color:#64748b}
  .ma-card{border:1px solid #e5edf5;border-radius:18px;background:#fff;box-shadow:0 10px 26px rgba(15,23,42,.055);margin-bottom:18px}.ma-card .box-header{padding:16px 20px 8px;border-bottom:0}.ma-card .box-title{font-weight:800;color:#0f172a}.ma-card .box-body{padding:14px 20px 20px}
  .ma-chart{height:300px}.ma-chart-sm{height:260px}
  .ma-table>thead>tr>th{background:#f8fafc;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:.03em}.ma-table>tbody>tr>td{vertical-align:middle;font-size:12px}
  .ma-timebox{display:flex;gap:8px;flex-wrap:wrap}.ma-timechip{padding:5px 8px;border-radius:999px;background:#f8fafc;border:1px solid #e5edf5;color:#334155;font-weight:700}
  .ma-note{padding:13px 14px;border-radius:15px;background:#f8fafc;border:1px solid #e5edf5;color:#475569;margin-bottom:14px}
  .ma-progress{height:9px;margin:9px 0 0;border-radius:99px;background:#e5e7eb;overflow:hidden}.ma-progress span{display:block;height:100%;background:linear-gradient(90deg,#0f766e,#22c55e)}
  @media(max-width:767px){.ma-hero h1{font-size:23px}.ma-chart,.ma-chart-sm{height:250px}}
</style>

<section class="content-header">
  <h1><?=hr_h('hr_my_attendance', 'My Attendance');?> <small>Employee Self Service</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li>
    <li>Employee Self Service</li>
    <li class="active"><?=hr_h('hr_my_attendance', 'My Attendance');?></li>
  </ol>
</section>

<section class="content">
  <?php if (!$employee): ?>
    <div class="alert alert-warning">
      <i class="fa fa-warning"></i>
      Data employee untuk user ini belum ditemukan. Pastikan user login sudah dihubungkan ke `erp_employee_master.user_id`.
    </div>
  <?php else: ?>
    <div class="ma-hero">
      <div class="ma-profile">
        <img class="ma-photo" src="<?=ma_h($photoUrl);?>" alt="Profile Photo">
        <div>
          <h1>Halo, <?=ma_h($employee->full_name);?></h1>
          <p><?=ma_h($employee->employee_no);?> &bull; <?=ma_h($employee->job_title_name ?: '-');?> &bull; <?=ma_h($employee->nm_dept ?: '-');?></p>
          <p style="margin-top:6px;">Ringkasan absensi <?=ma_h(ma_date($from));?> sampai <?=ma_h(ma_date($to));?>.</p>
        </div>
      </div>
    </div>

    <div class="box ma-filter">
      <div class="box-body">
        <form class="form-horizontal" method="get" action="">
          <div class="row">
            <div class="col-md-2 form-group">
              <label>Tanggal Mulai</label>
              <input type="text" name="tgl_awal" class="form-control ma-date" value="<?=ma_h($from);?>">
            </div>
            <div class="col-md-2 form-group">
              <label>Tanggal Akhir</label>
              <input type="text" name="tgl_akhir" class="form-control ma-date" value="<?=ma_h($to);?>">
            </div>
            <div class="col-md-3 form-group">
              <label><?=hr_h('common_status', 'Status');?></label>
              <select name="status" class="form-control ma-select2">
                <option value=""><?=hr_h('hr_all_status', 'All Status');?></option>
                <?php foreach (array('DRAFT','RECORDED','APPROVED','POSTED','REJECTED','CANCELLED') as $status): ?>
                  <option value="<?=$status;?>" <?=$filterStatus===$status?'selected':'';?>><?=$status;?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3 form-group">
              <label>Tipe Attendance</label>
              <select name="type" class="form-control ma-select2">
                <option value="">Semua Tipe</option>
                <?php foreach (array('REGULAR','OVERTIME','BUSINESS_TRIP','TRAINING','REMOTE','LEAVE','SICK','ABSENT') as $type): ?>
                  <option value="<?=$type;?>" <?=$filterType===$type?'selected':'';?>><?=$type;?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2 form-group">
              <label>&nbsp;</label>
              <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-filter"></i> Tampilkan</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="row">
      <div class="col-md-3 col-sm-6"><div class="ma-kpi"><i class="fa fa-calendar-check-o"></i><span>Presence Rate</span><strong><?=ma_h($presenceRate);?>%</strong><small><?=ma_h((int)$summary['present_days']);?> hadir dari <?=ma_h($expectedDays);?> hari kerja</small><div class="ma-progress"><span style="width:<?=ma_h($presenceRate);?>%"></span></div></div></div>
      <div class="col-md-3 col-sm-6"><div class="ma-kpi"><i class="fa fa-clock-o" style="background:#2563eb"></i><span>Punctual Rate</span><strong><?=ma_h($punctualRate);?>%</strong><small><?=ma_h((int)$summary['late_count']);?> kali terlambat, <?=ma_h((int)$summary['late_minutes']);?> menit</small><div class="ma-progress"><span style="width:<?=ma_h($punctualRate);?>%;background:linear-gradient(90deg,#2563eb,#38bdf8)"></span></div></div></div>
      <div class="col-md-3 col-sm-6"><div class="ma-kpi"><i class="fa fa-hourglass-half" style="background:#7c3aed"></i><span>Actual Hours</span><strong><?=ma_h(ma_num($summary['actual_hours'],2));?> h</strong><small>Rata-rata <?=ma_h(ma_num($avgHours,2));?> jam / record</small></div></div>
      <div class="col-md-3 col-sm-6"><div class="ma-kpi"><i class="fa fa-line-chart" style="background:#f59e0b"></i><span><?=hr_h('hr_overtime', 'Overtime');?></span><strong><?=ma_h(ma_num($summary['overtime_hours'],2));?> h</strong><small>Early leave <?=ma_h((int)$summary['early_minutes']);?> menit</small></div></div>
    </div>

    <div class="ma-note">
      <strong>Catatan mudah baca:</strong>
      hijau berarti hadir normal, kuning berarti izin/sakit/cuti atau perlu perhatian, merah berarti absent/rejected.
      Data ini bersifat monitoring pribadi, perubahan/koreksi absensi sebaiknya melalui workflow request terpisah.
    </div>

    <div class="row">
      <div class="col-md-8">
        <div class="box ma-card">
          <div class="box-header"><h3 class="box-title"><i class="fa fa-area-chart"></i> Actual vs Planned Hours</h3></div>
          <div class="box-body"><div id="ma_hours_chart" class="ma-chart"></div></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="box ma-card">
          <div class="box-header"><h3 class="box-title"><i class="fa fa-pie-chart"></i> Attendance Type</h3></div>
          <div class="box-body"><div id="ma_type_chart" class="ma-chart-sm"></div></div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-7">
        <div class="box ma-card">
          <div class="box-header"><h3 class="box-title"><i class="fa fa-warning"></i> Late Minutes Trend</h3></div>
          <div class="box-body"><div id="ma_late_chart" class="ma-chart-sm"></div></div>
        </div>
      </div>
      <div class="col-md-5">
        <div class="box ma-card">
          <div class="box-header"><h3 class="box-title"><i class="fa fa-check-square-o"></i> Posting Status</h3></div>
          <div class="box-body"><div id="ma_status_chart" class="ma-chart-sm"></div></div>
        </div>
      </div>
    </div>

    <div class="box ma-card">
      <div class="box-header">
        <h3 class="box-title"><i class="fa fa-list"></i> Riwayat Attendance</h3>
        <span class="pull-right text-muted">Maksimal 120 record sesuai filter</span>
      </div>
      <div class="box-body table-responsive">
        <table id="dtb_my_attendance" class="table table-bordered table-hover ma-table" style="width:100%;">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th><?=hr_h('hr_attendance', 'Attendance');?></th>
              <th><?=hr_h('hr_shift', 'Shift');?></th>
              <th>Plan / Actual</th>
              <th>Hours</th>
              <th>Exception</th>
              <th>Type</th>
              <th><?=hr_h('common_status', 'Status');?></th>
              <th><?=hr_h('common_action', 'Action');?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><strong><?=ma_h(ma_date($row->attendance_date));?></strong><br><small class="text-muted"><?=ma_h(date('l', strtotime($row->attendance_date)));?></small></td>
                <td><strong><?=ma_h($row->attendance_no);?></strong><br><small class="text-muted"><?=ma_h($row->attendance_source);?><?=trim((string)$row->sap_reference)!==''?' | '.ma_h($row->sap_reference):'';?></small></td>
                <td><strong><?=ma_h($row->shift_code ?: '-');?></strong><br><small class="text-muted"><?=ma_h($row->nama_shift ?: $row->assignment_no ?: '-');?></small></td>
                <td>
                  <div class="ma-timebox">
                    <span class="ma-timechip">Plan <?=ma_h(ma_time($row->planned_start));?>-<?=ma_h(ma_time($row->planned_end));?></span>
                    <span class="ma-timechip">Actual <?=ma_h(ma_time($row->actual_clock_in));?>-<?=ma_h(ma_time($row->actual_clock_out));?></span>
                  </div>
                </td>
                <td><strong><?=ma_h(ma_num($row->actual_hours,2));?> h</strong><br><small class="text-muted">Plan <?=ma_h(ma_num($row->planned_hours,2));?> h | OT <?=ma_h(ma_num($row->overtime_hours,2));?> h</small></td>
                <td>
                  <span class="label label-<?=$row->late_minutes>0?'warning':'success';?>">Late <?=ma_h((int)$row->late_minutes);?>m</span>
                  <span class="label label-<?=$row->early_leave_minutes>0?'warning':'success';?>">Early <?=ma_h((int)$row->early_leave_minutes);?>m</span>
                  <?php if (trim((string)$row->absence_reason) !== ''): ?><br><small class="text-muted"><?=ma_h($row->absence_reason);?></small><?php endif; ?>
                </td>
                <td><span class="label label-<?=ma_type_class($row->attendance_type);?>"><?=ma_h($row->attendance_type);?></span></td>
                <td><span class="label label-<?=ma_status_class($row->attendance_status);?>"><?=ma_h($row->attendance_status);?></span></td>
                <td class="text-center">
                  <button type="button" class="btn btn-info btn-xs btn-my-attendance-detail"
                    data-attendance="<?=ma_h($row->attendance_no);?>"
                    data-date="<?=ma_h(ma_date($row->attendance_date));?>"
                    data-shift="<?=ma_h(($row->shift_code ?: '-').' - '.($row->nama_shift ?: $row->assignment_no ?: '-'));?>"
                    data-plan="<?=ma_h(ma_time($row->planned_start).' - '.ma_time($row->planned_end));?>"
                    data-actual="<?=ma_h(ma_time($row->actual_clock_in).' - '.ma_time($row->actual_clock_out));?>"
                    data-hours="<?=ma_h(ma_num($row->actual_hours,2).' / plan '.ma_num($row->planned_hours,2).' / OT '.ma_num($row->overtime_hours,2));?>"
                    data-exception="<?=ma_h('Late '.(int)$row->late_minutes.'m, Early '.(int)$row->early_leave_minutes.'m'); ?>"
                    data-type="<?=ma_h($row->attendance_type);?>"
                    data-status="<?=ma_h($row->attendance_status);?>"
                    data-remarks="<?=ma_h($row->remarks ?: $row->absence_reason ?: $row->correction_reason ?: '-');?>">
                    <i class="fa fa-eye"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</section>

<div class="modal fade" id="modal_my_attendance_detail">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><i class="fa fa-clock-o"></i> Detail Attendance</h4>
      </div>
      <div class="modal-body" id="my_attendance_detail_body"></div>
    </div>
  </div>
</div>

<?php if ($employee): ?>
<script src="<?=base_url();?>assets/js/highcharts.js"></script>
<script>
$(function(){
  if ($.fn.datepicker) $('.ma-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});
  if ($.fn.select2) $('.ma-select2').select2({width:'100%',allowClear:true});
  if ($.fn.DataTable) $('#dtb_my_attendance').DataTable({
    pageLength:25,
    order:[[0,'desc']],
    dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons:[{extend:'collection',text:<?=hr_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','print']}],
    columnDefs:[{targets:[3,5,8],orderable:false},{targets:[8],searchable:false}]
  });
  $(document).on('click','.btn-my-attendance-detail',function(){
    var b=$(this);
    var html='<div class="table-responsive"><table class="table table-bordered table-striped">'+
      '<tr><th style="width:180px">Attendance No</th><td>'+b.data('attendance')+'</td></tr>'+
      '<tr><th><?=hr_h('hr_date', 'Date');?></th><td>'+b.data('date')+'</td></tr>'+
      '<tr><th><?=hr_h('hr_shift', 'Shift');?></th><td>'+b.data('shift')+'</td></tr>'+
      '<tr><th>Plan Time</th><td>'+b.data('plan')+'</td></tr>'+
      '<tr><th>Actual Time</th><td>'+b.data('actual')+'</td></tr>'+
      '<tr><th>Hours</th><td>'+b.data('hours')+'</td></tr>'+
      '<tr><th>Exception</th><td>'+b.data('exception')+'</td></tr>'+
      '<tr><th>Type / Status</th><td>'+b.data('type')+' / '+b.data('status')+'</td></tr>'+
      '<tr><th><?=hr_h('common_remarks', 'Remarks');?></th><td>'+b.data('remarks')+'</td></tr>'+
      '</table></div>';
    $('#my_attendance_detail_body').html(html);
    $('#modal_my_attendance_detail').modal('show');
  });
  if (typeof Highcharts === 'undefined') return;
  Highcharts.setOptions({lang:{thousandsSep:','},colors:['#0f766e','#2563eb','#f59e0b','#dc2626','#7c3aed','#0891b2','#16a34a','#64748b']});
  Highcharts.chart('ma_hours_chart',{chart:{type:'areaspline'},title:{text:null},xAxis:{categories:<?=json_encode($chartCategories);?>},yAxis:{title:{text:'Hours'}},tooltip:{shared:true,valueDecimals:2},plotOptions:{areaspline:{fillOpacity:.15,marker:{enabled:true,radius:3}}},series:[{name:'Actual Hours',data:<?=json_encode($chartActual);?>},{name:'Planned Hours',data:<?=json_encode($chartPlanned);?>}],credits:{enabled:false}});
  Highcharts.chart('ma_type_chart',{chart:{type:'pie'},title:{text:null},tooltip:{pointFormat:'<b>{point.y}</b> hari'},plotOptions:{pie:{innerSize:'58%',dataLabels:{enabled:true,format:'{point.name}: {point.y}'}}},series:[{name:'Type',data:<?=json_encode($typeChart);?>}],credits:{enabled:false}});
  Highcharts.chart('ma_late_chart',{chart:{type:'column'},title:{text:null},xAxis:{categories:<?=json_encode($chartCategories);?>},yAxis:{min:0,title:{text:'Minutes'},allowDecimals:false},legend:{enabled:false},tooltip:{valueSuffix:' menit'},plotOptions:{column:{borderRadius:4}},series:[{name:'Late Minutes',data:<?=json_encode($chartLate);?>,color:'#f59e0b'}],credits:{enabled:false}});
  Highcharts.chart('ma_status_chart',{chart:{type:'bar'},title:{text:null},xAxis:{type:'category'},yAxis:{title:{text:'Record'},allowDecimals:false},legend:{enabled:false},series:[{name:'Status',data:<?=json_encode($statusChart);?>}],credits:{enabled:false}});
});
</script>
<?php endif; ?>
