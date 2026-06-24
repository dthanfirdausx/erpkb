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

function trv_h($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function trv_date($value){ return ($value && $value !== '0000-00-00') ? date('d M Y', strtotime($value)) : '-'; }
function trv_num($value, $dec = 0){ return number_format((float)$value, $dec, '.', ','); }
function trv_valid_date($value, $fallback){ $value = trim((string)$value); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback; }
function trv_in_placeholders($count){ return implode(',', array_fill(0, max(1, (int)$count), '?')); }
function trv_status_class($status){ $map=array('DRAFT'=>'warning','SUBMITTED'=>'info','MANAGER_APPROVED'=>'primary','HR_REVIEW'=>'primary','APPROVED'=>'success','REJECTED'=>'danger','RETURNED'=>'warning','CANCELLED'=>'default','CLOSED'=>'success'); return isset($map[$status])?$map[$status]:'default'; }
function trv_priority_class($priority){ $map=array('LOW'=>'default','NORMAL'=>'info','HIGH'=>'warning','URGENT'=>'danger'); return isset($map[$priority])?$map[$priority]:'default'; }
function trv_trim($text, $max=90){ $text=(string)$text; return strlen($text)>$max ? substr($text,0,$max).'...' : $text; }

$today = date('Y-m-d');
$from = trv_valid_date(isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '', date('Y-01-01'));
$to = trv_valid_date(isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '', $today);
if (strtotime($from) > strtotime($to)) $from = $to;
$filterEmployee = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$filterDept = isset($_GET['department_code']) ? trim($_GET['department_code']) : '';
$filterCat = isset($_GET['request_category']) ? trim($_GET['request_category']) : '';
$filterStatus = isset($_GET['workflow_status']) ? trim($_GET['workflow_status']) : '';
$filterPriority = isset($_GET['priority']) ? trim($_GET['priority']) : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

$manager = null; $subordinates = array(); $subIds = array(); $rows = array();
$summary = array('total'=>0,'submitted'=>0,'manager_approved'=>0,'approved'=>0,'returned'=>0,'rejected'=>0,'urgent'=>0);
$categoryChart = array(); $statusChart = array(); $priorityChart = array();
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
  $where = " WHERE r.employee_id IN (".trv_in_placeholders(count($subIds)).") AND r.request_date BETWEEN ? AND ? ";
  $params[] = $from; $params[] = $to;
  if ($filterEmployee && in_array($filterEmployee, $subIds, true)) { $where .= " AND r.employee_id=? "; $params[] = $filterEmployee; }
  if ($filterDept !== '') { $where .= " AND r.department_code=? "; $params[] = $filterDept; }
  if ($filterCat !== '') { $where .= " AND r.request_category=? "; $params[] = $filterCat; }
  if ($filterStatus !== '') { $where .= " AND r.workflow_status=? "; $params[] = $filterStatus; }
  if ($filterPriority !== '') { $where .= " AND r.priority=? "; $params[] = $filterPriority; }
  if ($keyword !== '') {
    $kw = '%'.$keyword.'%';
    $where .= " AND (r.request_no LIKE ? OR r.employee_no LIKE ? OR e.full_name LIKE ? OR r.subject LIKE ? OR r.request_type LIKE ? OR r.description LIKE ?) ";
    array_push($params, $kw, $kw, $kw, $kw, $kw, $kw);
  }

  $sum = $db->fetch("SELECT COUNT(*) total,
      COALESCE(SUM(r.workflow_status='SUBMITTED'),0) submitted,
      COALESCE(SUM(r.workflow_status='MANAGER_APPROVED'),0) manager_approved,
      COALESCE(SUM(r.workflow_status='APPROVED'),0) approved,
      COALESCE(SUM(r.workflow_status='RETURNED'),0) returned,
      COALESCE(SUM(r.workflow_status='REJECTED'),0) rejected,
      COALESCE(SUM(r.priority='URGENT'),0) urgent
    FROM erp_employee_request r JOIN erp_employee_master e ON e.id=r.employee_id $where", $params);
  if ($sum) foreach($summary as $k=>$v) $summary[$k] = isset($sum->$k) ? (float)$sum->$k : 0;

  $stmt = $db->query("SELECT r.*, e.full_name, e.employee_group, d.nm_dept, jt.job_title_name, hr.employee_no hr_no, hr.full_name hr_name
    FROM erp_employee_request r
    JOIN erp_employee_master e ON e.id=r.employee_id
    LEFT JOIN dept d ON d.kd_dept=r.department_code
    LEFT JOIN erp_job_title jt ON jt.id=r.job_title_id
    LEFT JOIN erp_employee_master hr ON hr.id=r.hr_reviewer_employee_id
    $where
    ORDER BY FIELD(r.workflow_status,'SUBMITTED','RETURNED','MANAGER_APPROVED','HR_REVIEW','APPROVED','REJECTED','CANCELLED','CLOSED','DRAFT'), FIELD(r.priority,'URGENT','HIGH','NORMAL','LOW'), r.request_date DESC, r.request_no DESC
    LIMIT 300", $params);
  $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_OBJ) : array();

  $catRows = $db->query("SELECT r.request_category label, COUNT(*) total FROM erp_employee_request r JOIN erp_employee_master e ON e.id=r.employee_id $where GROUP BY r.request_category ORDER BY total DESC", $params);
  if ($catRows) foreach($catRows as $r) $categoryChart[] = array($r->label, (int)$r->total);
  $statusRows = $db->query("SELECT r.workflow_status label, COUNT(*) total FROM erp_employee_request r JOIN erp_employee_master e ON e.id=r.employee_id $where GROUP BY r.workflow_status ORDER BY total DESC", $params);
  if ($statusRows) foreach($statusRows as $r) $statusChart[] = array($r->label, (int)$r->total);
  $priorityRows = $db->query("SELECT r.priority label, COUNT(*) total FROM erp_employee_request r JOIN erp_employee_master e ON e.id=r.employee_id $where GROUP BY r.priority ORDER BY FIELD(r.priority,'URGENT','HIGH','NORMAL','LOW')", $params);
  if ($priorityRows) foreach($priorityRows as $r) $priorityChart[] = array($r->label, (int)$r->total);
}

$teamSize = count($subordinates);
$cats = array('EMPLOYEE_DATA','CERTIFICATE','CLAIM','BENEFIT','PAYROLL','ATTENDANCE_CORRECTION','DOCUMENT','FACILITY','OTHER');
$statuses = array('SUBMITTED','MANAGER_APPROVED','HR_REVIEW','APPROVED','REJECTED','RETURNED','CANCELLED','CLOSED','DRAFT');
$priorities = array('LOW','NORMAL','HIGH','URGENT');
$photoUrl = $manager ? erpkb_user_photo_url(isset($manager->foto_user) ? $manager->foto_user : '', 'back_profil_foto') : base_admin().'assets/dist/img/default-user-neutral.svg';
?>
<style>
.tr-hero{position:relative;overflow:hidden;border-radius:20px;background:linear-gradient(135deg,#0f766e,#0891b2 55%,#2563eb);color:#fff;padding:24px;margin-bottom:18px;box-shadow:0 18px 42px rgba(15,23,42,.18)}.tr-hero:after{content:"";position:absolute;right:-95px;top:-120px;width:330px;height:330px;border-radius:50%;background:rgba(255,255,255,.12)}.tr-profile{position:relative;z-index:1;display:flex;gap:16px;align-items:center;flex-wrap:wrap}.tr-photo{width:72px;height:72px;border-radius:22px;object-fit:cover;background:#fff;border:3px solid rgba(255,255,255,.7)}.tr-hero h1{margin:0 0 6px;font-weight:800}.tr-hero p{margin:0;color:rgba(255,255,255,.86)}.tr-hero .btn{border-radius:999px;font-weight:700}.tr-card,.tr-filter{border:1px solid #e5edf5;border-radius:18px;background:#fff;box-shadow:0 10px 24px rgba(15,23,42,.055);margin-bottom:16px}.tr-filter .box-body,.tr-card .box-body{padding:18px 20px}.tr-card .box-header{padding:16px 20px 4px;border-bottom:0}.tr-card .box-title{font-weight:800;color:#0f172a}.tr-filter label{font-size:12px;color:#475569;text-transform:uppercase;letter-spacing:.03em}.select2-container{width:100%!important}.tr-kpi{border:1px solid #e5edf5;border-radius:18px;background:#fff;padding:16px;min-height:118px;margin-bottom:16px;box-shadow:0 8px 22px rgba(15,23,42,.045)}.tr-kpi i{width:38px;height:38px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;color:#fff;background:#0891b2}.tr-kpi i.blue{background:#2563eb}.tr-kpi i.green{background:#0f766e}.tr-kpi i.orange{background:#f59e0b}.tr-kpi i.red{background:#dc2626}.tr-kpi span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.tr-kpi strong{display:block;color:#0f172a;font-size:25px;line-height:1.25}.tr-kpi small{color:#64748b}.tr-chart{height:285px}.tr-chart-sm{height:255px}.tr-table>thead>tr>th{background:#f8fafc;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:.03em}.tr-table>tbody>tr>td{vertical-align:middle;font-size:12px}.tr-empty{padding:24px;border:1px dashed #cbd5e1;border-radius:18px;background:#f8fafc;color:#475569}.tr-action{white-space:nowrap}.tr-subject{font-weight:800;color:#0f172a}
</style>
<section class="content-header"><h1><?=hr_h('hr_team_request_approval', 'Team Request Approval');?> <small>Manager Self Service</small></h1><ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li><li>Manager Self Service</li><li class="active"><?=hr_h('hr_team_request_approval', 'Team Request Approval');?></li></ol></section>
<section class="content">
<div class="tr-hero"><div class="row"><div class="col-md-8"><div class="tr-profile"><img class="tr-photo" src="<?=trv_h($photoUrl);?>" alt="Manager"><div><h1>Team Request Approval Desk</h1><p><?= $manager ? trv_h($manager->employee_no.' - '.$manager->full_name) : 'Manager belum terhubung ke employee master'; ?><?= $manager && $manager->nm_dept ? ' | '.trv_h($manager->nm_dept) : ''; ?></p><p>Review request umum bawahan: employee data, certificate, claim, payroll, attendance correction, document, facility, dan request lainnya.</p></div></div></div><div class="col-md-4 text-right" style="position:relative;z-index:1"><a class="btn btn-success" href="<?=base_admin();?>modul/team_request_approval/team_request_approval_action.php?act=export&<?=http_build_query($_GET);?>"><i class="fa fa-file-excel-o"></i> <?=hr_h('common_export_excel', 'Export Excel');?></a></div></div></div>
<?php if(!$manager){ ?><div class="tr-empty"><b>Employee profile manager belum ditemukan.</b><br>User login ini belum tersambung ke `erp_employee_master.user_id`.</div><?php } elseif(empty($subordinates)){ ?><div class="tr-empty"><b>Belum ada bawahan langsung.</b><br>Isi `manager_employee_id` di Employee Master agar request team muncul.</div><?php } else { ?>
<div class="box tr-filter"><div class="box-body"><form method="get" class="form-horizontal"><div class="form-group"><label class="col-lg-1 control-label"><?=hr_h('hr_date', 'Date');?></label><div class="col-lg-2"><input name="tgl_awal" class="form-control tr-date" value="<?=trv_h($from);?>"></div><div class="col-lg-2"><input name="tgl_akhir" class="form-control tr-date" value="<?=trv_h($to);?>"></div><label class="col-lg-1 control-label"><?=hr_h('hr_employee', 'Employee');?></label><div class="col-lg-3"><select name="employee_id" class="form-control select2-basic"><option value="">All Team Member</option><?php foreach($subordinates as $s){ ?><option value="<?=$s->id;?>" <?=$filterEmployee===(int)$s->id?'selected':'';?>><?=trv_h($s->employee_no.' - '.$s->full_name);?></option><?php } ?></select></div><label class="col-lg-1 control-label"><?=hr_h('common_status', 'Status');?></label><div class="col-lg-2"><select name="workflow_status" class="form-control select2-basic"><option value="">All</option><?php foreach($statuses as $s){ ?><option value="<?=$s;?>" <?=$filterStatus===$s?'selected':'';?>><?=$s;?></option><?php } ?></select></div></div><div class="form-group"><label class="col-lg-1 control-label">Dept</label><div class="col-lg-2"><select name="department_code" class="form-control select2-basic"><option value="">All Department</option><?php $seen=array(); foreach($subordinates as $s){ if($s->department_code && !isset($seen[$s->department_code])){ $seen[$s->department_code]=1; ?><option value="<?=trv_h($s->department_code);?>" <?=$filterDept===$s->department_code?'selected':'';?>><?=trv_h($s->department_code.' - '.$s->nm_dept);?></option><?php }} ?></select></div><label class="col-lg-1 control-label">Category</label><div class="col-lg-2"><select name="request_category" class="form-control select2-basic"><option value="">All</option><?php foreach($cats as $c){ ?><option value="<?=$c;?>" <?=$filterCat===$c?'selected':'';?>><?=$c;?></option><?php } ?></select></div><label class="col-lg-1 control-label">Priority</label><div class="col-lg-2"><select name="priority" class="form-control select2-basic"><option value="">All</option><?php foreach($priorities as $p){ ?><option value="<?=$p;?>" <?=$filterPriority===$p?'selected':'';?>><?=$p;?></option><?php } ?></select></div><div class="col-lg-2"><input name="keyword" class="form-control" value="<?=trv_h($keyword);?>" placeholder="Request no, employee, subject"></div><div class="col-lg-1"><button class="btn btn-primary"><i class="fa fa-filter"></i></button> <a class="btn btn-default" href="<?=base_index();?>team-request-approval"><i class="fa fa-refresh"></i></a></div></div></form></div></div>
<div class="row"><div class="col-md-2 col-sm-6"><div class="tr-kpi"><i class="fa fa-users"></i><span>Team</span><strong><?=trv_num($teamSize);?></strong><small>Bawahan langsung</small></div></div><div class="col-md-2 col-sm-6"><div class="tr-kpi"><i class="fa fa-hourglass-half blue"></i><span>Need Action</span><strong><?=trv_num($summary['submitted']);?></strong><small>SUBMITTED</small></div></div><div class="col-md-2 col-sm-6"><div class="tr-kpi"><i class="fa fa-check green"></i><span><?=hr_h('hr_approved', 'Approved');?></span><strong><?=trv_num($summary['approved']+$summary['manager_approved']);?></strong><small>Manager/Final approved</small></div></div><div class="col-md-2 col-sm-6"><div class="tr-kpi"><i class="fa fa-reply orange"></i><span>Returned</span><strong><?=trv_num($summary['returned']);?></strong><small>Perlu revisi</small></div></div><div class="col-md-2 col-sm-6"><div class="tr-kpi"><i class="fa fa-times red"></i><span><?=hr_h('hr_rejected', 'Rejected');?></span><strong><?=trv_num($summary['rejected']);?></strong><small>Ditolak</small></div></div><div class="col-md-2 col-sm-6"><div class="tr-kpi"><i class="fa fa-bolt red"></i><span>Urgent</span><strong><?=trv_num($summary['urgent']);?></strong><small>Prioritas urgent</small></div></div></div>
<div class="row"><div class="col-md-4"><div class="box tr-card"><div class="box-header"><h3 class="box-title">Category</h3></div><div class="box-body"><div id="tr_cat_chart" class="tr-chart-sm"></div></div></div></div><div class="col-md-4"><div class="box tr-card"><div class="box-header"><h3 class="box-title">Workflow Status</h3></div><div class="box-body"><div id="tr_status_chart" class="tr-chart-sm"></div></div></div></div><div class="col-md-4"><div class="box tr-card"><div class="box-header"><h3 class="box-title">Priority</h3></div><div class="box-body"><div id="tr_priority_chart" class="tr-chart-sm"></div></div></div></div></div>
<div class="box tr-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-check-square-o"></i> Request Approval Queue</h3></div><div class="box-body table-responsive"><div class="alert alert-warning error_data_delete" style="display:none"><button class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div><table id="tr_request_table" class="table table-bordered table-striped tr-table" style="width:100%"><thead><tr><th>Request</th><th><?=hr_h('hr_employee', 'Employee');?></th><th>Subject</th><th><?=hr_h('hr_date', 'Date');?></th><th>Priority</th><th><?=hr_h('common_status', 'Status');?></th><th>Notes</th><th><?=hr_h('common_action', 'Action');?></th></tr></thead><tbody><?php foreach($rows as $r){ $canApprove=$r->workflow_status==='SUBMITTED' && $r->approval_level==='MANAGER'; ?><tr><td><b><?=trv_h($r->request_no);?></b><br><small><?=trv_h($r->request_category);?></small><br><small><?=trv_h($r->request_type);?></small></td><td><b><?=trv_h($r->employee_no);?></b><br><?=trv_h($r->full_name);?><br><small><?=trv_h(($r->department_code?:'-').' - '.($r->nm_dept?:'-'));?></small></td><td><div class="tr-subject"><?=trv_h($r->subject);?></div><small><?=trv_h(trv_trim($r->description,100));?></small></td><td data-order="<?=trv_h($r->request_date);?>"><?=trv_date($r->request_date);?><br><small>Need: <?=trv_date($r->required_date);?></small></td><td><span class="label label-<?=trv_priority_class($r->priority);?>"><?=trv_h($r->priority);?></span></td><td><span class="label label-<?=trv_status_class($r->workflow_status);?>"><?=trv_h($r->workflow_status);?></span><br><small><?=trv_h($r->approval_level.' / '.$r->decision);?></small></td><td><?=trv_h($r->manager_note ?: $r->hr_note ?: $r->resolution_note ?: '-');?></td><td class="tr-action"><button class="btn btn-info btn-xs btn-tr-detail" data-id="<?=(int)$r->id;?>" title="<?=hr_h('common_detail', 'Detail');?>"><i class="fa fa-eye"></i></button> <?php if($canApprove){ ?><button class="btn btn-success btn-xs btn-tr-decision" data-id="<?=(int)$r->id;?>" data-no="<?=trv_h($r->request_no);?>" data-decision="APPROVE" title="<?=hr_h('common_approve', 'Approve');?>"><i class="fa fa-check"></i></button> <button class="btn btn-warning btn-xs btn-tr-decision" data-id="<?=(int)$r->id;?>" data-no="<?=trv_h($r->request_no);?>" data-decision="RETURN" title="Return"><i class="fa fa-reply"></i></button> <button class="btn btn-danger btn-xs btn-tr-decision" data-id="<?=(int)$r->id;?>" data-no="<?=trv_h($r->request_no);?>" data-decision="REJECT" title="<?=hr_h('common_reject', 'Reject');?>"><i class="fa fa-times"></i></button><?php } else { ?><span class="text-muted">No action</span><?php } ?></td></tr><?php } ?></tbody></table></div></div>
<?php } ?>
</section>
<div id="modal_tr_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:92%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Request Detail</h4></div><div class="modal-body" id="tr_detail_body"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=hr_h('common_close', 'Close');?></button></div></div></div></div>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script><script src="<?=base_admin();?>assets/js/highcharts.js"></script><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function trError(m){$('.isi_warning_delete').text(m||'Team Request Approval gagal diproses.');$('.error_data_delete').fadeIn();}
$(function(){if($.fn.datepicker){$('.tr-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}if($.fn.select2){$('.select2-basic').select2({width:'100%',allowClear:true});}if($.fn.DataTable){$('#tr_request_table').DataTable({pageLength:25,order:[[3,'desc']],columnDefs:[{targets:[7],orderable:false}]});}$('.hide_alert_notif').click(function(){$('.error_data_delete').hide();});$(document).on('click','.btn-tr-detail',function(){$.post('<?=base_admin();?>modul/team_request_approval/team_request_approval_action.php?act=detail',{id:$(this).data('id')},function(html){$('#tr_detail_body').html(html);$('#modal_tr_detail').modal('show');}).fail(function(xhr){trError(xhr.responseText);});});$(document).on('click','.btn-tr-decision',function(){var id=$(this).data('id'),no=$(this).data('no'),decision=$(this).data('decision');var cfg={title:decision+' request?',text:no,input:'textarea',inputPlaceholder:'Catatan manager',icon:decision==='APPROVE'?'question':'warning',showCancelButton:true,confirmButtonText:decision};if(decision!=='APPROVE'){cfg.inputValidator=function(v){return !v?'Catatan wajib diisi untuk '+decision:undefined;};}Swal.fire(cfg).then(function(x){if(!x.isConfirmed)return;$.post('<?=base_admin();?>modul/team_request_approval/team_request_approval_action.php?act=decision',{id:id,decision:decision,note:x.value||''},function(r){if(r.status==='good'){Swal.fire('Saved',r.message,'success').then(function(){location.reload();});}else trError(r.error_message);},'json').fail(function(xhr){trError(xhr.responseText);});});});if(typeof Highcharts!=='undefined'&&document.getElementById('tr_cat_chart')){Highcharts.chart('tr_cat_chart',{chart:{type:'pie'},title:{text:null},credits:{enabled:false},plotOptions:{pie:{innerSize:'58%',dataLabels:{enabled:true,format:'{point.name}<br>{point.y}'}}},series:[{name:'Category',data:<?=json_encode($categoryChart);?>}]});Highcharts.chart('tr_status_chart',{chart:{type:'bar'},title:{text:null},xAxis:{type:'category'},yAxis:{title:{text:'Request'},allowDecimals:false},legend:{enabled:false},credits:{enabled:false},series:[{name:'Status',data:<?=json_encode($statusChart);?>,color:'#2563eb'}]});Highcharts.chart('tr_priority_chart',{chart:{type:'column'},title:{text:null},xAxis:{type:'category'},yAxis:{title:{text:'Request'},allowDecimals:false},legend:{enabled:false},credits:{enabled:false},series:[{name:'Priority',data:<?=json_encode($priorityChart);?>,color:'#f59e0b'}]});}});
</script>
