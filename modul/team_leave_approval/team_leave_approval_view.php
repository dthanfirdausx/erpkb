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

function tlv_h($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function tlv_num($value, $dec = 0){ return number_format((float)$value, $dec, '.', ','); }
function tlv_date($value){ return ($value && $value !== '0000-00-00') ? date('d M Y', strtotime($value)) : '-'; }
function tlv_valid_date($value, $fallback){ $value = trim((string)$value); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback; }
function tlv_in_placeholders($count){ return implode(',', array_fill(0, max(1, (int)$count), '?')); }
function tlv_status_class($status)
{
  $map = array('DRAFT'=>'default','SUBMITTED'=>'info','MANAGER_APPROVED'=>'primary','HR_APPROVED'=>'primary','APPROVED'=>'success','REJECTED'=>'danger','RETURNED'=>'warning','CANCELLED'=>'default');
  return isset($map[$status]) ? $map[$status] : 'default';
}
function tlv_type_class($type)
{
  $map = array('ANNUAL_LEAVE'=>'success','SICK_LEAVE'=>'warning','SPECIAL_LEAVE'=>'info','MATERNITY_LEAVE'=>'primary','PATERNITY_LEAVE'=>'primary','MARRIAGE_LEAVE'=>'info','BEREAVEMENT_LEAVE'=>'default','UNPAID_LEAVE'=>'danger','PERMISSION'=>'warning','OTHER'=>'default');
  return isset($map[$type]) ? $map[$type] : 'default';
}

$today = date('Y-m-d');
$from = tlv_valid_date(isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '', date('Y-01-01'));
$to = tlv_valid_date(isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '', $today);
if (strtotime($from) > strtotime($to)) $from = $to;
$filterEmployee = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$filterDept = isset($_GET['department_code']) ? trim($_GET['department_code']) : '';
$filterType = isset($_GET['leave_type']) ? trim($_GET['leave_type']) : '';
$filterStatus = isset($_GET['workflow_status']) ? trim($_GET['workflow_status']) : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

$manager = null; $subordinates = array(); $subIds = array(); $rows = array(); $approvalMap = array();
$summary = array('total'=>0,'pending_manager'=>0,'pending_hr'=>0,'approved'=>0,'rejected'=>0,'returned'=>0,'requested_days'=>0,'approved_days'=>0);
$typeChart = array(); $statusChart = array(); $monthlyChart = array(); $monthlyCategories = array();
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
  $where = " WHERE l.employee_id IN (".tlv_in_placeholders(count($subIds)).") AND l.start_date<=? AND l.end_date>=? ";
  $params[] = $to; $params[] = $from;
  if ($filterEmployee && in_array($filterEmployee, $subIds, true)) { $where .= " AND l.employee_id=? "; $params[] = $filterEmployee; }
  if ($filterDept !== '') { $where .= " AND l.department_code=? "; $params[] = $filterDept; }
  if ($filterType !== '') { $where .= " AND l.leave_type=? "; $params[] = $filterType; }
  if ($filterStatus !== '') { $where .= " AND l.workflow_status=? "; $params[] = $filterStatus; }
  if ($keyword !== '') {
    $kw = '%'.$keyword.'%';
    $where .= " AND (l.leave_no LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ? OR l.reason LIKE ? OR l.remarks LIKE ?) ";
    array_push($params, $kw, $kw, $kw, $kw, $kw);
  }

  $sumRow = $db->fetch("SELECT COUNT(*) total,
      COALESCE(SUM(l.workflow_status='SUBMITTED'),0) pending_manager,
      COALESCE(SUM(l.workflow_status IN ('MANAGER_APPROVED','HR_APPROVED')),0) pending_hr,
      COALESCE(SUM(l.workflow_status='APPROVED'),0) approved,
      COALESCE(SUM(l.workflow_status='REJECTED'),0) rejected,
      COALESCE(SUM(l.workflow_status='RETURNED'),0) returned,
      COALESCE(SUM(l.total_days),0) requested_days,
      COALESCE(SUM(CASE WHEN l.workflow_status='APPROVED' THEN l.total_days ELSE 0 END),0) approved_days
    FROM erp_leave_request l JOIN erp_employee_master e ON e.id=l.employee_id $where", $params);
  if ($sumRow) foreach ($summary as $k=>$v) $summary[$k] = isset($sumRow->$k) ? (float)$sumRow->$k : 0;

  $stmtRows = $db->query("SELECT l.*, e.employee_no, e.full_name, e.employee_group, d.nm_dept, jt.job_title_name,
      h.employee_no handover_no, h.full_name handover_name,
      la.approval_no last_approval_no, la.decision last_decision, la.decision_date last_decision_date, la.approval_note last_approval_note
    FROM erp_leave_request l
    JOIN erp_employee_master e ON e.id=l.employee_id
    LEFT JOIN dept d ON d.kd_dept=l.department_code
    LEFT JOIN erp_job_title jt ON jt.id=l.job_title_id
    LEFT JOIN erp_employee_master h ON h.id=l.handover_to_employee_id
    LEFT JOIN (SELECT x.* FROM erp_leave_approval x JOIN (SELECT leave_request_id, MAX(id) id FROM erp_leave_approval GROUP BY leave_request_id) y ON y.id=x.id) la ON la.leave_request_id=l.id
    $where
    ORDER BY FIELD(l.workflow_status,'SUBMITTED','RETURNED','MANAGER_APPROVED','HR_APPROVED','APPROVED','REJECTED','CANCELLED','DRAFT'), l.start_date DESC, l.leave_no DESC
    LIMIT 300", $params);
  $rows = $stmtRows ? $stmtRows->fetchAll(PDO::FETCH_OBJ) : array();
  $ids = array(); foreach ($rows as $r) $ids[] = (int)$r->id;
  if ($ids) {
    $logs = $db->query("SELECT a.*, e.employee_no, e.full_name
      FROM erp_leave_approval a
      LEFT JOIN erp_employee_master e ON e.id=a.approver_employee_id
      WHERE a.leave_request_id IN (".tlv_in_placeholders(count($ids)).")
      ORDER BY a.leave_request_id, a.id", $ids);
    if ($logs) foreach ($logs as $l) {
      if (!isset($approvalMap[$l->leave_request_id])) $approvalMap[$l->leave_request_id] = array();
      $approvalMap[$l->leave_request_id][] = $l;
    }
  }

  $typeRows = $db->query("SELECT l.leave_type label, COUNT(*) total FROM erp_leave_request l JOIN erp_employee_master e ON e.id=l.employee_id $where GROUP BY l.leave_type ORDER BY total DESC", $params);
  if ($typeRows) foreach ($typeRows as $r) $typeChart[] = array($r->label ?: 'UNKNOWN', (int)$r->total);
  $statusRows = $db->query("SELECT l.workflow_status label, COUNT(*) total FROM erp_leave_request l JOIN erp_employee_master e ON e.id=l.employee_id $where GROUP BY l.workflow_status ORDER BY total DESC", $params);
  if ($statusRows) foreach ($statusRows as $r) $statusChart[] = array($r->label ?: 'UNKNOWN', (int)$r->total);
  $monthRows = $db->query("SELECT DATE_FORMAT(l.start_date,'%Y-%m') ym, SUM(l.total_days) total_days
    FROM erp_leave_request l JOIN erp_employee_master e ON e.id=l.employee_id $where
    GROUP BY DATE_FORMAT(l.start_date,'%Y-%m')
    ORDER BY ym", $params);
  if ($monthRows) foreach ($monthRows as $r) { $monthlyCategories[] = $r->ym; $monthlyChart[] = (float)$r->total_days; }
}

$leaveTypes = array('ANNUAL_LEAVE','SICK_LEAVE','SPECIAL_LEAVE','MATERNITY_LEAVE','PATERNITY_LEAVE','MARRIAGE_LEAVE','BEREAVEMENT_LEAVE','UNPAID_LEAVE','PERMISSION','OTHER');
$statuses = array('SUBMITTED','MANAGER_APPROVED','HR_APPROVED','APPROVED','REJECTED','RETURNED','CANCELLED','DRAFT');
$teamSize = count($subordinates);
$approvalRate = $summary['total'] > 0 ? round(($summary['approved'] / $summary['total']) * 100, 1) : 0;
$photoUrl = $manager ? erpkb_user_photo_url($manager->foto_user, 'back_profil_foto') : base_admin().'assets/dist/img/default-user-neutral.svg';
?>
<style>
.tl-hero{position:relative;overflow:hidden;border-radius:20px;background:linear-gradient(135deg,#1e3a8a,#0f766e 55%,#16a34a);color:#fff;padding:24px;margin-bottom:18px;box-shadow:0 18px 42px rgba(15,23,42,.18)}.tl-hero:after{content:"";position:absolute;right:-95px;top:-120px;width:330px;height:330px;border-radius:50%;background:rgba(255,255,255,.12)}.tl-profile{position:relative;z-index:1;display:flex;gap:16px;align-items:center;flex-wrap:wrap}.tl-photo{width:72px;height:72px;border-radius:22px;object-fit:cover;background:#fff;border:3px solid rgba(255,255,255,.7)}.tl-hero h1{margin:0 0 6px;font-weight:800;letter-spacing:-.02em}.tl-hero p{margin:0;color:rgba(255,255,255,.86)}.tl-hero .btn{border-radius:999px;font-weight:700}
.tl-card,.tl-filter{border:1px solid #e5edf5;border-radius:18px;background:#fff;box-shadow:0 10px 24px rgba(15,23,42,.055);margin-bottom:16px}.tl-filter .box-body,.tl-card .box-body{padding:18px 20px}.tl-card .box-header{padding:16px 20px 4px;border-bottom:0}.tl-card .box-title{font-weight:800;color:#0f172a}.tl-filter label{font-size:12px;color:#475569;text-transform:uppercase;letter-spacing:.03em}.tl-filter .form-control{border-radius:10px}.select2-container{width:100%!important}
.tl-kpi{border:1px solid #e5edf5;border-radius:18px;background:#fff;padding:16px;min-height:118px;margin-bottom:16px;box-shadow:0 8px 22px rgba(15,23,42,.045)}.tl-kpi i{width:38px;height:38px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;color:#fff;background:#0f766e}.tl-kpi i.blue{background:#2563eb}.tl-kpi i.orange{background:#f59e0b}.tl-kpi i.red{background:#dc2626}.tl-kpi span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.tl-kpi strong{display:block;color:#0f172a;font-size:25px;line-height:1.25}.tl-kpi small{color:#64748b}.tl-progress{height:8px;margin-top:7px;border-radius:999px;background:#e5e7eb;overflow:hidden}.tl-progress span{display:block;height:100%;background:linear-gradient(90deg,#0f766e,#22c55e)}
.tl-chart{height:285px}.tl-chart-sm{height:255px}.tl-table>thead>tr>th{background:#f8fafc;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:.03em}.tl-table>tbody>tr>td{vertical-align:middle;font-size:12px}.tl-pill{display:inline-block;border-radius:999px;padding:4px 9px;background:#eef2ff;color:#3730a3;font-weight:800;font-size:11px;margin:2px 0}.tl-timeline{margin:8px 0 0;padding-left:17px;color:#64748b}.tl-timeline li{margin-bottom:5px}.tl-action{white-space:nowrap}.tl-empty{padding:24px;border:1px dashed #cbd5e1;border-radius:18px;background:#f8fafc;color:#475569}
@media(max-width:767px){.tl-hero h1{font-size:23px}.tl-chart,.tl-chart-sm{height:245px}}
</style>

<section class="content-header">
  <h1><?=hr_h('hr_team_leave_approval', 'Team Leave Approval');?> <small>Manager Self Service</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li><li>Manager Self Service</li><li class="active"><?=hr_h('hr_team_leave_approval', 'Team Leave Approval');?></li></ol>
</section>
<section class="content">
  <div class="tl-hero">
    <div class="row">
      <div class="col-md-8">
        <div class="tl-profile">
          <img class="tl-photo" src="<?=tlv_h($photoUrl);?>" alt="Manager">
          <div>
            <h1>Team Leave Approval Board</h1>
            <p><?= $manager ? tlv_h($manager->employee_no.' - '.$manager->full_name) : 'Manager belum terhubung ke employee master'; ?><?= $manager && $manager->nm_dept ? ' | '.tlv_h($manager->nm_dept) : ''; ?></p>
            <p>Review pengajuan cuti bawahan langsung, lihat workload absence, dan proses approve, return, atau reject dengan catatan yang jelas.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 text-right" style="position:relative;z-index:1">
        <a class="btn btn-success" href="<?=base_admin();?>modul/team_leave_approval/team_leave_approval_action.php?act=export&<?=http_build_query($_GET);?>"><i class="fa fa-file-excel-o"></i> <?=hr_h('common_export_excel', 'Export Excel');?></a>
      </div>
    </div>
  </div>

  <?php if (!$manager) { ?>
    <div class="tl-empty"><b>Employee profile manager belum ditemukan.</b><br>User login ini belum tersambung ke `erp_employee_master.user_id`.</div>
  <?php } elseif (empty($subordinates)) { ?>
    <div class="tl-empty"><b>Belum ada bawahan langsung.</b><br>Isi `manager_employee_id` di Employee Master agar approval team muncul untuk manager ini.</div>
  <?php } else { ?>
  <div class="box tl-filter">
    <div class="box-body">
      <form method="get" class="form-horizontal">
        <div class="form-group">
          <label class="col-lg-1 control-label"><?=hr_h('hr_date', 'Date');?></label>
          <div class="col-lg-2"><input name="tgl_awal" class="form-control tl-date" value="<?=tlv_h($from);?>"></div>
          <div class="col-lg-2"><input name="tgl_akhir" class="form-control tl-date" value="<?=tlv_h($to);?>"></div>
          <label class="col-lg-1 control-label"><?=hr_h('hr_employee', 'Employee');?></label>
          <div class="col-lg-3"><select name="employee_id" class="form-control select2-basic"><option value="">All Team Member</option><?php foreach($subordinates as $s){ ?><option value="<?=$s->id;?>" <?=$filterEmployee===(int)$s->id?'selected':'';?>><?=tlv_h($s->employee_no.' - '.$s->full_name);?></option><?php } ?></select></div>
          <label class="col-lg-1 control-label"><?=hr_h('common_status', 'Status');?></label>
          <div class="col-lg-2"><select name="workflow_status" class="form-control select2-basic"><option value="">All</option><?php foreach($statuses as $s){ ?><option value="<?=$s;?>" <?=$filterStatus===$s?'selected':'';?>><?=$s;?></option><?php } ?></select></div>
        </div>
        <div class="form-group">
          <label class="col-lg-1 control-label">Dept</label>
          <div class="col-lg-2"><select name="department_code" class="form-control select2-basic"><option value="">All Department</option><?php $seen=array(); foreach($subordinates as $s){ if($s->department_code && !isset($seen[$s->department_code])){ $seen[$s->department_code]=1; ?><option value="<?=tlv_h($s->department_code);?>" <?=$filterDept===$s->department_code?'selected':'';?>><?=tlv_h($s->department_code.' - '.$s->nm_dept);?></option><?php }} ?></select></div>
          <label class="col-lg-1 control-label">Type</label>
          <div class="col-lg-2"><select name="leave_type" class="form-control select2-basic"><option value="">All</option><?php foreach($leaveTypes as $t){ ?><option value="<?=$t;?>" <?=$filterType===$t?'selected':'';?>><?=$t;?></option><?php } ?></select></div>
          <label class="col-lg-1 control-label"><?=hr_h('common_search', 'Search');?></label>
          <div class="col-lg-3"><input name="keyword" class="form-control" value="<?=tlv_h($keyword);?>" placeholder="Leave no, employee, reason"></div>
          <div class="col-lg-2"><button class="btn btn-primary"><i class="fa fa-filter"></i> <?=hr_h('common_filter', 'Filter');?></button> <a class="btn btn-default" href="<?=base_index();?>team-leave-approval"><i class="fa fa-refresh"></i></a></div>
        </div>
      </form>
    </div>
  </div>

  <div class="row">
    <div class="col-md-2 col-sm-6"><div class="tl-kpi"><i class="fa fa-users"></i><span>Team</span><strong><?=tlv_num($teamSize);?></strong><small>Bawahan langsung</small></div></div>
    <div class="col-md-2 col-sm-6"><div class="tl-kpi"><i class="fa fa-hourglass-half blue"></i><span>Need Action</span><strong><?=tlv_num($summary['pending_manager']);?></strong><small>Submitted manager step</small></div></div>
    <div class="col-md-2 col-sm-6"><div class="tl-kpi"><i class="fa fa-check orange"></i><span><?=hr_h('hr_approved', 'Approved');?></span><strong><?=tlv_num($summary['approved']);?></strong><div class="tl-progress"><span style="width:<?=$approvalRate;?>%"></span></div></div></div>
    <div class="col-md-2 col-sm-6"><div class="tl-kpi"><i class="fa fa-calendar"></i><span>Requested Days</span><strong><?=tlv_num($summary['requested_days'],1);?></strong><small><?=tlv_num($summary['approved_days'],1);?> approved days</small></div></div>
    <div class="col-md-2 col-sm-6"><div class="tl-kpi"><i class="fa fa-reply red"></i><span>Returned</span><strong><?=tlv_num($summary['returned']);?></strong><small>Perlu revisi employee</small></div></div>
    <div class="col-md-2 col-sm-6"><div class="tl-kpi"><i class="fa fa-user-md"></i><span>Pending HR</span><strong><?=tlv_num($summary['pending_hr']);?></strong><small>Manager approved / HR review</small></div></div>
  </div>

  <div class="row">
    <div class="col-md-4"><div class="box tl-card"><div class="box-header"><h3 class="box-title"><?=hr_h('hr_leave_type', 'Leave Type');?></h3></div><div class="box-body"><div id="tl_type_chart" class="tl-chart-sm"></div></div></div></div>
    <div class="col-md-4"><div class="box tl-card"><div class="box-header"><h3 class="box-title">Workflow Status</h3></div><div class="box-body"><div id="tl_status_chart" class="tl-chart-sm"></div></div></div></div>
    <div class="col-md-4"><div class="box tl-card"><div class="box-header"><h3 class="box-title">Leave Days by Month</h3></div><div class="box-body"><div id="tl_month_chart" class="tl-chart-sm"></div></div></div></div>
  </div>

  <div class="box tl-card">
    <div class="box-header"><h3 class="box-title"><i class="fa fa-check-square-o"></i> Leave Approval Queue</h3></div>
    <div class="box-body table-responsive">
      <div class="alert alert-warning error_data_delete" style="display:none"><button class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div>
      <table id="tl_leave_table" class="table table-bordered table-striped tl-table" style="width:100%">
        <thead><tr><th>Leave</th><th><?=hr_h('hr_employee', 'Employee');?></th><th><?=hr_h('hr_period', 'Period');?></th><th>Days / Quota</th><th>Handover</th><th><?=hr_h('common_status', 'Status');?></th><th>Reason</th><th>Approval History</th><th><?=hr_h('common_action', 'Action');?></th></tr></thead>
        <tbody>
          <?php foreach($rows as $r){ $canApprove = $r->workflow_status === 'SUBMITTED' && $r->approval_level === 'MANAGER'; ?>
            <tr>
              <td><b><?=tlv_h($r->leave_no);?></b><br><span class="label label-<?=tlv_type_class($r->leave_type);?>"><?=tlv_h($r->leave_type);?></span><br><small>Req <?=tlv_h($r->request_date);?></small></td>
              <td><b><?=tlv_h($r->employee_no);?></b><br><?=tlv_h($r->full_name);?><br><small><?=tlv_h(($r->department_code ?: '-').' - '.($r->nm_dept ?: '-'));?></small></td>
              <td data-order="<?=tlv_h($r->start_date);?>"><b><?=tlv_date($r->start_date);?></b><br><small>s/d <?=tlv_date($r->end_date);?></small><br><span class="tl-pill"><?=tlv_h($r->start_half_day.' / '.$r->end_half_day);?></span></td>
              <td class="text-right"><b><?=tlv_num($r->total_days,2);?> hari</b><br><small>Quota <?=tlv_num($r->leave_quota_before,2);?> -> <?=tlv_num($r->leave_quota_after,2);?></small></td>
              <td><?=tlv_h($r->handover_no ? $r->handover_no.' - '.$r->handover_name : '-');?></td>
              <td><span class="label label-<?=tlv_status_class($r->workflow_status);?>"><?=tlv_h($r->workflow_status);?></span><br><small><?=tlv_h($r->approval_level.' / '.$r->decision);?></small></td>
              <td><?=nl2br(tlv_h($r->reason ?: '-'));?></td>
              <td><?php $logs = isset($approvalMap[$r->id]) ? $approvalMap[$r->id] : array(); if(!$logs){ ?><span class="text-muted">Belum ada log.</span><?php } else { ?><ul class="tl-timeline"><?php foreach($logs as $l){ ?><li><b><?=tlv_h($l->approval_step);?></b> <?=tlv_h($l->decision);?> <small><?=tlv_h($l->decision_date ?: '-');?></small><br><small><?=tlv_h(trim(($l->employee_no ?: '').' '.($l->full_name ?: '')) ?: '-');?></small></li><?php } ?></ul><?php } ?></td>
              <td class="tl-action">
                <button class="btn btn-info btn-xs btn-tl-detail" data-id="<?=(int)$r->id;?>" title="<?=hr_h('common_detail', 'Detail');?>"><i class="fa fa-eye"></i></button>
                <?php if($canApprove){ ?>
                  <button class="btn btn-success btn-xs btn-tl-decision" data-id="<?=(int)$r->id;?>" data-no="<?=tlv_h($r->leave_no);?>" data-decision="APPROVE" title="<?=hr_h('common_approve', 'Approve');?>"><i class="fa fa-check"></i></button>
                  <button class="btn btn-warning btn-xs btn-tl-decision" data-id="<?=(int)$r->id;?>" data-no="<?=tlv_h($r->leave_no);?>" data-decision="RETURN" title="Return"><i class="fa fa-reply"></i></button>
                  <button class="btn btn-danger btn-xs btn-tl-decision" data-id="<?=(int)$r->id;?>" data-no="<?=tlv_h($r->leave_no);?>" data-decision="REJECT" title="<?=hr_h('common_reject', 'Reject');?>"><i class="fa fa-times"></i></button>
                <?php } else { ?><span class="text-muted">No action</span><?php } ?>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php } ?>
</section>

<div id="modal_tl_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:92%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Leave Detail</h4></div><div class="modal-body" id="tl_detail_body"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=hr_h('common_close', 'Close');?></button></div></div></div></div>

<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="<?=base_admin();?>assets/js/highcharts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function tlError(m){$('.isi_warning_delete').text(m||'Team Leave Approval gagal diproses.');$('.error_data_delete').fadeIn();}
$(function(){
  if($.fn.datepicker){$('.tl-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('.select2-basic').select2({width:'100%',allowClear:true});}
  if($.fn.DataTable){$('#tl_leave_table').DataTable({pageLength:25,order:[[2,'desc']],columnDefs:[{targets:[7,8],orderable:false}]});}
  $('.hide_alert_notif').click(function(){$('.error_data_delete').hide();});
  $(document).on('click','.btn-tl-detail',function(){
    $.post('<?=base_admin();?>modul/team_leave_approval/team_leave_approval_action.php?act=detail',{id:$(this).data('id')},function(html){$('#tl_detail_body').html(html);$('#modal_tl_detail').modal('show');}).fail(function(xhr){tlError(xhr.responseText);});
  });
  $(document).on('click','.btn-tl-decision',function(){
    var id=$(this).data('id'), no=$(this).data('no'), decision=$(this).data('decision');
    var cfg={title:decision+' leave request?',text:no,input:'textarea',inputPlaceholder:'Catatan approval',icon:decision==='APPROVE'?'question':'warning',showCancelButton:true,confirmButtonText:decision};
    if(decision!=='APPROVE'){cfg.inputValidator=function(v){return !v?'Catatan wajib diisi untuk '+decision:undefined;};}
    Swal.fire(cfg).then(function(x){
      if(!x.isConfirmed)return;
      $.post('<?=base_admin();?>modul/team_leave_approval/team_leave_approval_action.php?act=decision',{leave_request_id:id,decision:decision,approval_note:x.value||''},function(r){
        if(r.status==='good'){Swal.fire('Saved',r.message,'success').then(function(){location.reload();});} else tlError(r.error_message);
      },'json').fail(function(xhr){tlError(xhr.responseText);});
    });
  });
  if(typeof Highcharts !== 'undefined' && document.getElementById('tl_type_chart')){
    Highcharts.chart('tl_type_chart',{chart:{type:'pie'},title:{text:null},credits:{enabled:false},plotOptions:{pie:{innerSize:'58%',dataLabels:{enabled:true,format:'{point.name}<br>{point.y}'}}},series:[{name:'Request',data:<?=json_encode($typeChart);?>}]});
    Highcharts.chart('tl_status_chart',{chart:{type:'bar'},title:{text:null},xAxis:{type:'category'},yAxis:{title:{text:'Request'},allowDecimals:false},legend:{enabled:false},credits:{enabled:false},series:[{name:'Status',data:<?=json_encode($statusChart);?>,color:'#2563eb'}]});
    Highcharts.chart('tl_month_chart',{chart:{type:'column'},title:{text:null},xAxis:{categories:<?=json_encode($monthlyCategories);?>},yAxis:{title:{text:'Days'}},credits:{enabled:false},series:[{name:'Leave Days',data:<?=json_encode($monthlyChart);?>,color:'#0f766e'}]});
  }
});
</script>
