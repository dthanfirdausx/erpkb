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

function tov_h($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function tov_num($value, $dec = 0){ return number_format((float)$value, $dec, '.', ','); }
function tov_date($value){ return ($value && $value !== '0000-00-00') ? date('d M Y', strtotime($value)) : '-'; }
function tov_time($value){ return ($value && $value !== '0000-00-00 00:00:00') ? date('H:i', strtotime($value)) : '-'; }
function tov_valid_date($value, $fallback){ $value = trim((string)$value); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback; }
function tov_in_placeholders($count){ return implode(',', array_fill(0, max(1, (int)$count), '?')); }
function tov_status_class($status)
{
  $map = array('DRAFT'=>'warning','REQUESTED'=>'info','APPROVED'=>'success','REJECTED'=>'danger','POSTED'=>'primary','CANCELLED'=>'default');
  return isset($map[$status]) ? $map[$status] : 'default';
}

$today = date('Y-m-d');
$from = tov_valid_date(isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '', date('Y-m-01'));
$to = tov_valid_date(isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '', $today);
if (strtotime($from) > strtotime($to)) $from = $to;
$filterEmployee = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$filterDept = isset($_GET['department_code']) ? trim($_GET['department_code']) : '';
$filterType = isset($_GET['overtime_type']) ? trim($_GET['overtime_type']) : '';
$filterStatus = isset($_GET['overtime_status']) ? trim($_GET['overtime_status']) : '';
$filterSource = isset($_GET['request_source']) ? trim($_GET['request_source']) : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

$manager = null; $subordinates = array(); $subIds = array(); $rows = array(); $memberSummary = array();
$summary = array('total'=>0,'requested'=>0,'approved'=>0,'rejected'=>0,'posted'=>0,'requested_hours'=>0,'approved_hours'=>0,'payable_hours'=>0,'amount'=>0);
$statusChart = array(); $typeChart = array(); $hoursCategories = array(); $hoursRequested = array(); $hoursApproved = array(); $amountData = array();
$uid = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;
if ($uid > 0) {
  $manager = $db->fetch("SELECT e.*, u.username, u.foto_user, d.nm_dept, jt.job_title_name
    FROM erp_employee_master e
    LEFT JOIN sys_users u ON u.id=e.user_id
    LEFT JOIN dept d ON d.kd_dept=e.department_code
    LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id
    WHERE e.user_id=? LIMIT 1", array($uid));
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
  $where = " WHERE ot.employee_id IN (".tov_in_placeholders(count($subIds)).") AND ot.overtime_date BETWEEN ? AND ? ";
  $params[] = $from; $params[] = $to;
  if ($filterEmployee && in_array($filterEmployee, $subIds, true)) { $where .= " AND ot.employee_id=? "; $params[] = $filterEmployee; }
  if ($filterDept !== '') { $where .= " AND ot.department_code=? "; $params[] = $filterDept; }
  if ($filterType !== '') { $where .= " AND ot.overtime_type=? "; $params[] = $filterType; }
  if ($filterStatus !== '') { $where .= " AND ot.overtime_status=? "; $params[] = $filterStatus; }
  if ($filterSource !== '') { $where .= " AND ot.request_source=? "; $params[] = $filterSource; }
  if ($keyword !== '') {
    $kw = '%'.$keyword.'%';
    $where .= " AND (ot.overtime_no LIKE ? OR ot.employee_no LIKE ? OR e.full_name LIKE ? OR ot.overtime_reason LIKE ? OR ot.sap_reference LIKE ?) ";
    array_push($params, $kw, $kw, $kw, $kw, $kw);
  }

  $sumRow = $db->fetch("SELECT COUNT(*) total,
      COALESCE(SUM(ot.overtime_status='REQUESTED'),0) requested,
      COALESCE(SUM(ot.overtime_status='APPROVED'),0) approved,
      COALESCE(SUM(ot.overtime_status='REJECTED'),0) rejected,
      COALESCE(SUM(ot.overtime_status='POSTED'),0) posted,
      COALESCE(SUM(ot.requested_hours),0) requested_hours,
      COALESCE(SUM(ot.approved_hours),0) approved_hours,
      COALESCE(SUM(ot.payable_hours),0) payable_hours,
      COALESCE(SUM(ot.estimated_amount),0) amount
    FROM erp_overtime ot JOIN erp_employee_master e ON e.id=ot.employee_id $where", $params);
  if ($sumRow) foreach ($summary as $k=>$v) $summary[$k] = isset($sumRow->$k) ? (float)$sumRow->$k : 0;

  $stmtRows = $db->query("SELECT ot.*, e.full_name, e.employee_group, d.nm_dept, cc.cost_center_name, a.actual_hours attendance_actual_hours, a.overtime_hours attendance_ot
    FROM erp_overtime ot
    JOIN erp_employee_master e ON e.id=ot.employee_id
    LEFT JOIN dept d ON d.kd_dept=ot.department_code
    LEFT JOIN erp_cost_center cc ON cc.cost_center_code=ot.cost_center_code
    LEFT JOIN erp_attendance a ON a.id=ot.attendance_id
    $where
    ORDER BY FIELD(ot.overtime_status,'REQUESTED','APPROVED','REJECTED','POSTED','CANCELLED','DRAFT'), ot.overtime_date DESC, ot.overtime_no DESC
    LIMIT 300", $params);
  $rows = $stmtRows ? $stmtRows->fetchAll(PDO::FETCH_OBJ) : array();

  $stmtMember = $db->query("SELECT e.id, e.employee_no, e.full_name, e.department_code, d.nm_dept,
      COUNT(ot.id) total,
      COALESCE(SUM(ot.overtime_status='REQUESTED'),0) requested,
      COALESCE(SUM(ot.overtime_status='APPROVED'),0) approved,
      COALESCE(SUM(ot.requested_hours),0) requested_hours,
      COALESCE(SUM(ot.approved_hours),0) approved_hours,
      COALESCE(SUM(ot.payable_hours),0) payable_hours,
      COALESCE(SUM(ot.estimated_amount),0) amount
    FROM erp_employee_master e
    LEFT JOIN dept d ON d.kd_dept=e.department_code
    LEFT JOIN erp_overtime ot ON ot.employee_id=e.id AND ot.overtime_date BETWEEN ? AND ?
    WHERE e.id IN (".tov_in_placeholders(count($subIds)).")
    GROUP BY e.id
    ORDER BY payable_hours DESC, e.full_name", array_merge(array($from, $to), $subIds));
  $memberSummary = $stmtMember ? $stmtMember->fetchAll(PDO::FETCH_OBJ) : array();

  $statusRows = $db->query("SELECT ot.overtime_status label, COUNT(*) total FROM erp_overtime ot JOIN erp_employee_master e ON e.id=ot.employee_id $where GROUP BY ot.overtime_status ORDER BY total DESC", $params);
  if ($statusRows) foreach ($statusRows as $r) $statusChart[] = array($r->label ?: 'UNKNOWN', (int)$r->total);
  $typeRows = $db->query("SELECT ot.overtime_type label, COUNT(*) total FROM erp_overtime ot JOIN erp_employee_master e ON e.id=ot.employee_id $where GROUP BY ot.overtime_type ORDER BY total DESC", $params);
  if ($typeRows) foreach ($typeRows as $r) $typeChart[] = array($r->label ?: 'UNKNOWN', (int)$r->total);
  foreach (array_slice($memberSummary, 0, 10) as $m) {
    $hoursCategories[] = $m->employee_no.' - '.$m->full_name;
    $hoursRequested[] = (float)$m->requested_hours;
    $hoursApproved[] = (float)$m->approved_hours;
    $amountData[] = (float)$m->amount;
  }
}

$teamSize = count($subordinates);
$approvalRate = $summary['total'] > 0 ? round((($summary['approved'] + $summary['posted']) / $summary['total']) * 100, 1) : 0;
$photoUrl = $manager ? erpkb_user_photo_url($manager->foto_user, 'back_profil_foto') : base_admin().'assets/dist/img/default-user-neutral.svg';
$types = array('REGULAR_OT','HOLIDAY_OT','WEEKEND_OT','CALL_OUT','PROJECT_OT','EMERGENCY_OT');
$statuses = array('REQUESTED','APPROVED','REJECTED','POSTED','CANCELLED','DRAFT');
$sources = array('ATTENDANCE','MANUAL','IMPORT','MOBILE','WEB');
?>
<style>
.to-hero{position:relative;overflow:hidden;border-radius:20px;background:linear-gradient(135deg,#7c2d12,#1d4ed8 55%,#0f766e);color:#fff;padding:24px;margin-bottom:18px;box-shadow:0 18px 42px rgba(15,23,42,.18)}.to-hero:after{content:"";position:absolute;right:-95px;top:-120px;width:330px;height:330px;border-radius:50%;background:rgba(255,255,255,.12)}.to-profile{position:relative;z-index:1;display:flex;gap:16px;align-items:center;flex-wrap:wrap}.to-photo{width:72px;height:72px;border-radius:22px;object-fit:cover;background:#fff;border:3px solid rgba(255,255,255,.7)}.to-hero h1{margin:0 0 6px;font-weight:800;letter-spacing:-.02em}.to-hero p{margin:0;color:rgba(255,255,255,.86)}.to-hero .btn{border-radius:999px;font-weight:700}
.to-card,.to-filter{border:1px solid #e5edf5;border-radius:18px;background:#fff;box-shadow:0 10px 24px rgba(15,23,42,.055);margin-bottom:16px}.to-filter .box-body,.to-card .box-body{padding:18px 20px}.to-card .box-header{padding:16px 20px 4px;border-bottom:0}.to-card .box-title{font-weight:800;color:#0f172a}.to-filter label{font-size:12px;color:#475569;text-transform:uppercase;letter-spacing:.03em}.select2-container{width:100%!important}
.to-kpi{border:1px solid #e5edf5;border-radius:18px;background:#fff;padding:16px;min-height:118px;margin-bottom:16px;box-shadow:0 8px 22px rgba(15,23,42,.045)}.to-kpi i{width:38px;height:38px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;color:#fff;background:#7c2d12}.to-kpi i.blue{background:#2563eb}.to-kpi i.green{background:#0f766e}.to-kpi i.red{background:#dc2626}.to-kpi span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.to-kpi strong{display:block;color:#0f172a;font-size:25px;line-height:1.25}.to-kpi small{color:#64748b}.to-progress{height:8px;margin-top:7px;border-radius:999px;background:#e5e7eb;overflow:hidden}.to-progress span{display:block;height:100%;background:linear-gradient(90deg,#0f766e,#22c55e)}
.to-chart{height:285px}.to-chart-sm{height:255px}.to-table>thead>tr>th{background:#f8fafc;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:.03em}.to-table>tbody>tr>td{vertical-align:middle;font-size:12px}.to-chip{display:inline-block;border-radius:999px;padding:4px 9px;background:#fff7ed;color:#9a3412;font-weight:800;font-size:11px;margin:2px 0}.to-timebox{display:flex;gap:6px;flex-wrap:wrap}.to-timechip{padding:5px 8px;border-radius:999px;background:#f8fafc;border:1px solid #e5edf5;color:#334155;font-weight:700}.to-action{white-space:nowrap}.to-empty{padding:24px;border:1px dashed #cbd5e1;border-radius:18px;background:#f8fafc;color:#475569}
@media(max-width:767px){.to-hero h1{font-size:23px}.to-chart,.to-chart-sm{height:245px}}
</style>

<section class="content-header">
  <h1><?=hr_h('hr_team_overtime_approval', 'Team Overtime Approval');?> <small>Manager Self Service</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li><li>Manager Self Service</li><li class="active"><?=hr_h('hr_team_overtime_approval', 'Team Overtime Approval');?></li></ol>
</section>
<section class="content">
  <div class="to-hero">
    <div class="row">
      <div class="col-md-8"><div class="to-profile"><img class="to-photo" src="<?=tov_h($photoUrl);?>" alt="Manager"><div><h1>Team Overtime Control Room</h1><p><?= $manager ? tov_h($manager->employee_no.' - '.$manager->full_name) : 'Manager belum terhubung ke employee master'; ?><?= $manager && $manager->nm_dept ? ' | '.tov_h($manager->nm_dept) : ''; ?></p><p>Monitor lembur bawahan: request dari attendance/manual, approval, payable hours, estimasi biaya, dan alasan lembur.</p></div></div></div>
      <div class="col-md-4 text-right" style="position:relative;z-index:1"><a class="btn btn-success" href="<?=base_admin();?>modul/team_overtime_approval/team_overtime_approval_action.php?act=export&<?=http_build_query($_GET);?>"><i class="fa fa-file-excel-o"></i> <?=hr_h('common_export_excel', 'Export Excel');?></a></div>
    </div>
  </div>

  <?php if (!$manager) { ?>
    <div class="to-empty"><b>Employee profile manager belum ditemukan.</b><br>User login ini belum tersambung ke `erp_employee_master.user_id`.</div>
  <?php } elseif (empty($subordinates)) { ?>
    <div class="to-empty"><b>Belum ada bawahan langsung.</b><br>Isi `manager_employee_id` di Employee Master agar data overtime team muncul untuk manager ini.</div>
  <?php } else { ?>
  <div class="box to-filter"><div class="box-body"><form method="get" class="form-horizontal">
    <div class="form-group"><label class="col-lg-1 control-label"><?=hr_h('hr_date', 'Date');?></label><div class="col-lg-2"><input name="tgl_awal" class="form-control to-date" value="<?=tov_h($from);?>"></div><div class="col-lg-2"><input name="tgl_akhir" class="form-control to-date" value="<?=tov_h($to);?>"></div><label class="col-lg-1 control-label"><?=hr_h('hr_employee', 'Employee');?></label><div class="col-lg-3"><select name="employee_id" class="form-control select2-basic"><option value="">All Team Member</option><?php foreach($subordinates as $s){ ?><option value="<?=$s->id;?>" <?=$filterEmployee===(int)$s->id?'selected':'';?>><?=tov_h($s->employee_no.' - '.$s->full_name);?></option><?php } ?></select></div><label class="col-lg-1 control-label"><?=hr_h('common_status', 'Status');?></label><div class="col-lg-2"><select name="overtime_status" class="form-control select2-basic"><option value="">All</option><?php foreach($statuses as $s){ ?><option value="<?=$s;?>" <?=$filterStatus===$s?'selected':'';?>><?=$s;?></option><?php } ?></select></div></div>
    <div class="form-group"><label class="col-lg-1 control-label">Dept</label><div class="col-lg-2"><select name="department_code" class="form-control select2-basic"><option value="">All Department</option><?php $seen=array(); foreach($subordinates as $s){ if($s->department_code && !isset($seen[$s->department_code])){ $seen[$s->department_code]=1; ?><option value="<?=tov_h($s->department_code);?>" <?=$filterDept===$s->department_code?'selected':'';?>><?=tov_h($s->department_code.' - '.$s->nm_dept);?></option><?php }} ?></select></div><label class="col-lg-1 control-label">Type</label><div class="col-lg-2"><select name="overtime_type" class="form-control select2-basic"><option value="">All</option><?php foreach($types as $t){ ?><option value="<?=$t;?>" <?=$filterType===$t?'selected':'';?>><?=$t;?></option><?php } ?></select></div><label class="col-lg-1 control-label">Source</label><div class="col-lg-2"><select name="request_source" class="form-control select2-basic"><option value="">All</option><?php foreach($sources as $s){ ?><option value="<?=$s;?>" <?=$filterSource===$s?'selected':'';?>><?=$s;?></option><?php } ?></select></div><div class="col-lg-2"><input name="keyword" class="form-control" value="<?=tov_h($keyword);?>" placeholder="OT no, employee, reason"></div><div class="col-lg-1"><button class="btn btn-primary"><i class="fa fa-filter"></i></button> <a class="btn btn-default" href="<?=base_index();?>team-overtime-approval"><i class="fa fa-refresh"></i></a></div></div>
  </form></div></div>

  <div class="row">
    <div class="col-md-2 col-sm-6"><div class="to-kpi"><i class="fa fa-users"></i><span>Team</span><strong><?=tov_num($teamSize);?></strong><small>Bawahan langsung</small></div></div>
    <div class="col-md-2 col-sm-6"><div class="to-kpi"><i class="fa fa-hourglass-half blue"></i><span>Need Action</span><strong><?=tov_num($summary['requested']);?></strong><small>REQUESTED</small></div></div>
    <div class="col-md-2 col-sm-6"><div class="to-kpi"><i class="fa fa-check green"></i><span>Approval Rate</span><strong><?=tov_num($approvalRate,1);?>%</strong><div class="to-progress"><span style="width:<?=$approvalRate;?>%"></span></div></div></div>
    <div class="col-md-2 col-sm-6"><div class="to-kpi"><i class="fa fa-clock-o"></i><span>Requested Hrs</span><strong><?=tov_num($summary['requested_hours'],1);?></strong><small><?=hr_h('hr_approved', 'Approved');?> <?=tov_num($summary['approved_hours'],1);?></small></div></div>
    <div class="col-md-2 col-sm-6"><div class="to-kpi"><i class="fa fa-money green"></i><span>Payable</span><strong><?=tov_num($summary['payable_hours'],1);?></strong><small>Jam payable</small></div></div>
    <div class="col-md-2 col-sm-6"><div class="to-kpi"><i class="fa fa-calculator red"></i><span>Amount</span><strong><?=tov_num($summary['amount'],0);?></strong><small>Estimasi biaya</small></div></div>
  </div>

  <div class="row">
    <div class="col-md-5"><div class="box to-card"><div class="box-header"><h3 class="box-title">Overtime Hours by Employee</h3></div><div class="box-body"><div id="to_hours_chart" class="to-chart"></div></div></div></div>
    <div class="col-md-4"><div class="box to-card"><div class="box-header"><h3 class="box-title">Estimated Amount</h3></div><div class="box-body"><div id="to_amount_chart" class="to-chart"></div></div></div></div>
    <div class="col-md-3"><div class="box to-card"><div class="box-header"><h3 class="box-title"><?=hr_h('common_status', 'Status');?></h3></div><div class="box-body"><div id="to_status_chart" class="to-chart-sm"></div></div></div></div>
  </div>

  <div class="box to-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-users"></i> Team Overtime Summary</h3></div><div class="box-body table-responsive">
    <table id="to_member_table" class="table table-bordered table-striped to-table"><thead><tr><th><?=hr_h('hr_employee', 'Employee');?></th><th><?=hr_h('hr_department', 'Department');?></th><th class="text-right">Records</th><th class="text-right">Requested</th><th class="text-right"><?=hr_h('hr_approved', 'Approved');?></th><th class="text-right">Requested Hrs</th><th class="text-right">Approved Hrs</th><th class="text-right">Payable Hrs</th><th class="text-right">Amount</th></tr></thead><tbody><?php foreach($memberSummary as $m){ ?><tr><td><b><?=tov_h($m->employee_no);?></b><br><?=tov_h($m->full_name);?></td><td><?=tov_h(($m->department_code ?: '-').' - '.($m->nm_dept ?: '-'));?></td><td class="text-right"><?=tov_num($m->total);?></td><td class="text-right"><?=tov_num($m->requested);?></td><td class="text-right"><?=tov_num($m->approved);?></td><td class="text-right"><?=tov_num($m->requested_hours,2);?></td><td class="text-right"><?=tov_num($m->approved_hours,2);?></td><td class="text-right"><?=tov_num($m->payable_hours,2);?></td><td class="text-right"><?=tov_num($m->amount,2);?></td></tr><?php } ?></tbody></table>
  </div></div>

  <div class="box to-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-check-square-o"></i> Overtime Approval Queue</h3></div><div class="box-body table-responsive"><div class="alert alert-warning error_data_delete" style="display:none"><button class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div>
    <table id="to_detail_table" class="table table-bordered table-striped to-table" style="width:100%"><thead><tr><th><?=hr_h('hr_overtime', 'Overtime');?></th><th><?=hr_h('hr_employee', 'Employee');?></th><th>Time</th><th>Hours</th><th>Amount</th><th><?=hr_h('common_status', 'Status');?></th><th>Reason</th><th><?=hr_h('common_action', 'Action');?></th></tr></thead><tbody><?php foreach($rows as $r){ $canApprove=$r->overtime_status==='REQUESTED'; ?><tr><td><b><?=tov_h($r->overtime_no);?></b><br><small><?=tov_date($r->overtime_date);?></small><br><span class="to-chip"><?=tov_h($r->overtime_type);?></span></td><td><b><?=tov_h($r->employee_no);?></b><br><?=tov_h($r->full_name);?><br><small><?=tov_h(($r->department_code ?: '-').' - '.($r->nm_dept ?: '-'));?></small></td><td data-order="<?=tov_h($r->overtime_date);?>"><div class="to-timebox"><span class="to-timechip">Plan <?=tov_time($r->planned_start);?>-<?=tov_time($r->planned_end);?></span><span class="to-timechip">Actual <?=tov_time($r->actual_start);?>-<?=tov_time($r->actual_end);?></span></div><small><?=tov_h($r->request_source.' / '.($r->attendance_no ?: '-'));?></small></td><td class="text-right">Req <b><?=tov_num($r->requested_hours,2);?></b><br>App <?=tov_num($r->approved_hours,2);?><br>Pay <?=tov_num($r->payable_hours,2);?> x <?=tov_num($r->rate_multiplier,2);?></td><td class="text-right"><b><?=tov_num($r->estimated_amount,2);?></b><br><small><?=tov_h($r->cost_center_code ?: '-');?></small></td><td><span class="label label-<?=tov_status_class($r->overtime_status);?>"><?=tov_h($r->overtime_status);?></span><br><small><?=tov_h($r->approved_by ?: '-');?> <?=tov_h($r->approved_at ?: '');?></small></td><td><?=nl2br(tov_h($r->overtime_reason ?: '-'));?><?= $r->reject_reason ? '<br><small class="text-danger">Reject: '.tov_h($r->reject_reason).'</small>' : ''; ?></td><td class="to-action"><button class="btn btn-info btn-xs btn-to-detail" data-id="<?=(int)$r->id;?>" title="<?=hr_h('common_detail', 'Detail');?>"><i class="fa fa-eye"></i></button> <?php if($canApprove){ ?><button class="btn btn-success btn-xs btn-to-decision" data-id="<?=(int)$r->id;?>" data-no="<?=tov_h($r->overtime_no);?>" data-decision="APPROVE" title="<?=hr_h('common_approve', 'Approve');?>"><i class="fa fa-check"></i></button> <button class="btn btn-danger btn-xs btn-to-decision" data-id="<?=(int)$r->id;?>" data-no="<?=tov_h($r->overtime_no);?>" data-decision="REJECT" title="<?=hr_h('common_reject', 'Reject');?>"><i class="fa fa-times"></i></button><?php } else { ?><span class="text-muted">No action</span><?php } ?></td></tr><?php } ?></tbody></table>
  </div></div>
  <?php } ?>
</section>

<div id="modal_to_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:92%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Overtime Detail</h4></div><div class="modal-body" id="to_detail_body"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=hr_h('common_close', 'Close');?></button></div></div></div></div>

<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="<?=base_admin();?>assets/js/highcharts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function toError(m){$('.isi_warning_delete').text(m||'Team Overtime Approval gagal diproses.');$('.error_data_delete').fadeIn();}
$(function(){
  if($.fn.datepicker){$('.to-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('.select2-basic').select2({width:'100%',allowClear:true});}
  if($.fn.DataTable){$('#to_member_table').DataTable({pageLength:10,order:[[7,'desc']]});$('#to_detail_table').DataTable({pageLength:25,order:[[2,'desc']],columnDefs:[{targets:[7],orderable:false}]});}
  $('.hide_alert_notif').click(function(){$('.error_data_delete').hide();});
  $(document).on('click','.btn-to-detail',function(){$.post('<?=base_admin();?>modul/team_overtime_approval/team_overtime_approval_action.php?act=detail',{id:$(this).data('id')},function(html){$('#to_detail_body').html(html);$('#modal_to_detail').modal('show');}).fail(function(xhr){toError(xhr.responseText);});});
  $(document).on('click','.btn-to-decision',function(){var id=$(this).data('id'),no=$(this).data('no'),decision=$(this).data('decision');var cfg={title:decision+' overtime?',text:no,input:'textarea',inputPlaceholder:'Catatan manager',icon:decision==='APPROVE'?'question':'warning',showCancelButton:true,confirmButtonText:decision};if(decision==='REJECT'){cfg.inputValidator=function(v){return !v?'Reject reason wajib diisi':undefined;};}Swal.fire(cfg).then(function(x){if(!x.isConfirmed)return;$.post('<?=base_admin();?>modul/team_overtime_approval/team_overtime_approval_action.php?act=decision',{id:id,decision:decision,note:x.value||''},function(r){if(r.status==='good'){Swal.fire('Saved',r.message,'success').then(function(){location.reload();});}else toError(r.error_message);},'json').fail(function(xhr){toError(xhr.responseText);});});});
  if(typeof Highcharts !== 'undefined' && document.getElementById('to_hours_chart')){
    Highcharts.chart('to_hours_chart',{chart:{type:'bar'},title:{text:null},xAxis:{categories:<?=json_encode($hoursCategories);?>},yAxis:{title:{text:'Hours'}},tooltip:{shared:true},credits:{enabled:false},series:[{name:'Requested',data:<?=json_encode($hoursRequested);?>,color:'#f97316'},{name:'Approved',data:<?=json_encode($hoursApproved);?>,color:'#0f766e'}]});
    Highcharts.chart('to_amount_chart',{chart:{type:'column'},title:{text:null},xAxis:{categories:<?=json_encode($hoursCategories);?>},yAxis:{title:{text:'Amount'}},credits:{enabled:false},series:[{name:'Estimated Amount',data:<?=json_encode($amountData);?>,color:'#2563eb'}]});
    Highcharts.chart('to_status_chart',{chart:{type:'pie'},title:{text:null},credits:{enabled:false},plotOptions:{pie:{innerSize:'58%',dataLabels:{enabled:true,format:'{point.name}<br>{point.y}'}}},series:[{name:'Status',data:<?=json_encode($statusChart);?>}]});
  }
});
</script>
