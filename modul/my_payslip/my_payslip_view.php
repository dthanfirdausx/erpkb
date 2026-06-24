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

function mpv_h($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
function mpv_date($v){return (!$v || $v==='0000-00-00') ? '-' : date('d M Y', strtotime($v));}
function mpv_money($v){return number_format((float)$v, 2, '.', ',');}
function mpv_num($v,$d=0){return number_format((float)$v,$d,'.',',');}
function mpv_valid_date($v,$fallback){return preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)$v)?$v:$fallback;}
function mpv_status_class($s){$m=array('DRAFT'=>'warning','GENERATED'=>'info','RELEASED'=>'success','VOID'=>'danger');return isset($m[$s])?$m[$s]:'default';}

$today = date('Y-m-d');
$from = mpv_valid_date(isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '', date('Y-01-01'));
$to = mpv_valid_date(isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '', $today);
$filterArea = isset($_GET['payroll_area']) ? trim($_GET['payroll_area']) : '';
$filterStatus = isset($_GET['payslip_status']) ? trim($_GET['payslip_status']) : '';
$employee = null; $rows = array();
$summary = array('total'=>0,'released'=>0,'generated'=>0,'voided'=>0,'gross'=>0,'earning'=>0,'deduction'=>0,'tax'=>0,'net'=>0);
$statusChart = array(); $netTrendCategories = array(); $netTrendData = array(); $grossTrendData = array();

$uid = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;
if ($uid > 0) {
  $employee = $db->fetch("SELECT e.*, u.username, u.foto_user, d.nm_dept, jt.job_title_code, jt.job_title_name
    FROM erp_employee_master e
    LEFT JOIN sys_users u ON u.id=e.user_id
    LEFT JOIN dept d ON d.kd_dept=e.department_code
    LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id
    WHERE e.user_id=? LIMIT 1", array($uid));
}
if ($employee) {
  $where = " WHERE ps.employee_id=? AND ps.period_from<=? AND ps.period_to>=? ";
  $params = array((int)$employee->id, $to, $from);
  if ($filterArea !== '') {$where .= " AND ps.payroll_area=? "; $params[] = $filterArea;}
  if ($filterStatus !== '') {$where .= " AND ps.payslip_status=? "; $params[] = $filterStatus;}
  $s = $db->fetch("SELECT COUNT(*) total,
      SUM(ps.payslip_status='RELEASED') released,
      SUM(ps.payslip_status='GENERATED') generated,
      SUM(ps.payslip_status='VOID') voided,
      COALESCE(SUM(ps.gross_pay),0) gross,
      COALESCE(SUM(ps.total_earning),0) earning,
      COALESCE(SUM(ps.total_deduction),0) deduction,
      COALESCE(SUM(ps.tax_amount),0) tax,
      COALESCE(SUM(ps.net_pay),0) net
    FROM erp_payslip ps $where", $params);
  if ($s) foreach ($summary as $k=>$v) $summary[$k] = isset($s->$k) ? (float)$s->$k : 0;
  $stmt = $db->query("SELECT ps.*, d.nm_dept
    FROM erp_payslip ps
    LEFT JOIN dept d ON d.kd_dept=ps.department_code
    $where ORDER BY ps.period_year DESC, ps.period_month DESC, ps.payslip_no DESC", $params);
  $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_OBJ) : array();
  $statusRows = $db->query("SELECT ps.payslip_status label, COUNT(*) total FROM erp_payslip ps $where GROUP BY ps.payslip_status ORDER BY total DESC", $params);
  foreach ($statusRows as $r) $statusChart[] = array($r->label, (int)$r->total);
  if (!$statusChart) $statusChart[] = array('No Data',0);
  $trend = array_reverse($rows);
  foreach ($trend as $r) {
    $netTrendCategories[] = date('M Y', mktime(0,0,0,(int)$r->period_month,1,(int)$r->period_year));
    $netTrendData[] = (float)$r->net_pay;
    $grossTrendData[] = (float)$r->gross_pay;
  }
}
$photoUrl = $employee ? erpkb_user_photo_url($employee->foto_user, 'back_profil_foto') : base_admin().'assets/dist/img/default-user-neutral.svg';
$areas = array('MONTHLY','DAILY','WEEKLY','CONTRACT','MANAGEMENT','ALL');
$statuses = array('DRAFT','GENERATED','RELEASED','VOID');
?>

<style>
.mps-hero{position:relative;overflow:hidden;border-radius:22px;background:linear-gradient(135deg,#312e81,#db2777);color:#fff;padding:24px;margin-bottom:18px;box-shadow:0 18px 40px rgba(219,39,119,.19)}.mps-hero:after{content:"";position:absolute;right:-80px;top:-110px;width:285px;height:285px;border-radius:50%;background:rgba(255,255,255,.13)}.mps-profile{position:relative;z-index:1;display:flex;align-items:center;gap:16px;flex-wrap:wrap}.mps-photo{width:74px;height:74px;object-fit:cover;border-radius:20px;border:3px solid rgba(255,255,255,.75);background:#fff;box-shadow:0 10px 24px rgba(15,23,42,.2)}.mps-hero h1{margin:0 0 6px;font-weight:800}.mps-hero p{margin:0;color:rgba(255,255,255,.86)}.mps-actions{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}.mps-filter,.mps-card{border:1px solid #e5edf5;border-radius:18px;background:#fff;box-shadow:0 10px 26px rgba(15,23,42,.055);margin-bottom:18px}.mps-filter .box-body,.mps-card .box-body{padding:17px 20px}.mps-filter label{font-size:12px;color:#475569;text-transform:uppercase;letter-spacing:.03em}.mps-kpi{border:1px solid #e5edf5;border-radius:18px;background:#fff;padding:16px;min-height:112px;margin-bottom:16px;box-shadow:0 8px 22px rgba(15,23,42,.045)}.mps-kpi i{width:38px;height:38px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;color:#fff;background:#db2777}.mps-kpi i.blue{background:#2563eb}.mps-kpi i.green{background:#0f766e}.mps-kpi i.orange{background:#f59e0b}.mps-kpi span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.mps-kpi strong{display:block;color:#0f172a;font-size:22px;line-height:1.25}.mps-kpi small{color:#64748b}.mps-chart{height:285px}.mps-card .box-header{padding:16px 20px 8px;border-bottom:0}.mps-card .box-title{font-weight:800;color:#0f172a}.mps-table>thead>tr>th{background:#f8fafc;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:.03em}.mps-table>tbody>tr>td{vertical-align:middle;font-size:12px}.mps-chip{display:inline-block;padding:5px 8px;border-radius:999px;background:#f8fafc;border:1px solid #e5edf5;color:#334155;font-weight:700;margin:2px 0}.select2-container{width:100%!important}.mps-slip-print{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px}@media(max-width:767px){.mps-actions{margin-left:0}.mps-chart{height:245px}}
</style>

<section class="content-header"><h1><?=hr_h('hr_my_payslip', 'My Payslip');?> <small>Employee Self Service</small></h1><ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li><li>Employee Self Service</li><li class="active"><?=hr_h('hr_my_payslip', 'My Payslip');?></li></ol></section>
<section class="content">
<?php if (!$employee): ?>
  <div class="alert alert-warning"><i class="fa fa-warning"></i> Data employee untuk user ini belum ditemukan. Pastikan user login sudah dihubungkan ke `erp_employee_master.user_id`.</div>
<?php else: ?>
  <div class="mps-hero"><div class="mps-profile"><img src="<?=mpv_h($photoUrl);?>" class="mps-photo" alt="Profile Photo"><div><h1>My Payslip, <?=mpv_h($employee->full_name);?></h1><p><?=mpv_h($employee->employee_no);?> &bull; <?=mpv_h($employee->job_title_name?:'-');?> &bull; <?=mpv_h($employee->nm_dept?:'-');?></p><p style="margin-top:6px">Lihat slip gaji, komponen earnings/deductions, status release, dan export riwayat payroll pribadi.</p></div><div class="mps-actions"><button class="btn btn-success" id="btnExportMyPayslip"><i class="fa fa-file-excel-o"></i> <?=hr_h('common_export_excel', 'Export Excel');?></button></div></div></div>

  <div class="box mps-filter"><div class="box-body"><form id="formFilterMyPayslip" method="get"><div class="row"><div class="col-md-2 form-group"><label>Tanggal Mulai</label><input name="tgl_awal" class="form-control mps-date" value="<?=mpv_h($from);?>"></div><div class="col-md-2 form-group"><label>Tanggal Akhir</label><input name="tgl_akhir" class="form-control mps-date" value="<?=mpv_h($to);?>"></div><div class="col-md-3 form-group"><label>Payroll Area</label><select name="payroll_area" class="form-control mps-select2"><option value="">Semua Area</option><?php foreach($areas as $a): ?><option value="<?=$a;?>" <?=$filterArea===$a?'selected':'';?>><?=$a;?></option><?php endforeach; ?></select></div><div class="col-md-3 form-group"><label><?=hr_h('common_status', 'Status');?></label><select name="payslip_status" class="form-control mps-select2"><option value=""><?=hr_h('hr_all_status', 'All Status');?></option><?php foreach($statuses as $s): ?><option value="<?=$s;?>" <?=$filterStatus===$s?'selected':'';?>><?=$s;?></option><?php endforeach; ?></select></div><div class="col-md-2 form-group"><label>&nbsp;</label><button class="btn btn-primary btn-block"><i class="fa fa-filter"></i> Tampilkan</button></div></div></form></div></div>

  <div class="row"><div class="col-md-3 col-sm-6"><div class="mps-kpi"><i class="fa fa-file-text-o"></i><span>Total Payslip</span><strong><?=mpv_num($summary['total']);?></strong><small><?=mpv_num($summary['released']);?> released</small></div></div><div class="col-md-3 col-sm-6"><div class="mps-kpi"><i class="fa fa-money green"></i><span><?=hr_h('hr_net_pay', 'Net Pay');?></span><strong><?=mpv_money($summary['net']);?></strong><small>Total sesuai filter</small></div></div><div class="col-md-3 col-sm-6"><div class="mps-kpi"><i class="fa fa-plus-circle blue"></i><span><?=hr_h('hr_gross_pay', 'Gross Pay');?></span><strong><?=mpv_money($summary['gross']);?></strong><small>Earning <?=mpv_money($summary['earning']);?></small></div></div><div class="col-md-3 col-sm-6"><div class="mps-kpi"><i class="fa fa-minus-circle orange"></i><span>Deduction + Tax</span><strong><?=mpv_money($summary['deduction']+$summary['tax']);?></strong><small>Ded <?=mpv_money($summary['deduction']);?> | Tax <?=mpv_money($summary['tax']);?></small></div></div></div>

  <div class="row"><div class="col-md-8"><div class="box mps-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-line-chart"></i> Gross vs Net Pay Trend</h3></div><div class="box-body"><div id="mps_pay_chart" class="mps-chart"></div></div></div></div><div class="col-md-4"><div class="box mps-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-pie-chart"></i> Payslip Status</h3></div><div class="box-body"><div id="mps_status_chart" class="mps-chart"></div></div></div></div></div>

  <div class="box mps-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-list"></i> Riwayat Payslip</h3></div><div class="box-body table-responsive"><table id="dtb_my_payslip" class="table table-bordered table-hover mps-table" style="width:100%"><thead><tr><th><?=hr_h('hr_payslip', 'Payslip');?></th><th><?=hr_h('hr_period', 'Period');?></th><th>Working</th><th>Gross</th><th>Deduction / Tax</th><th><?=hr_h('hr_net_pay', 'Net Pay');?></th><th><?=hr_h('common_status', 'Status');?></th><th><?=hr_h('common_action', 'Action');?></th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><strong><?=mpv_h($r->payslip_no);?></strong><br><small><?=mpv_h($r->payroll_run_no);?></small><br><span class="mps-chip"><?=mpv_h($r->payroll_area);?></span></td><td><strong><?=mpv_h(date('F Y', mktime(0,0,0,(int)$r->period_month,1,(int)$r->period_year)));?></strong><br><small><?=mpv_h(mpv_date($r->period_from));?> s/d <?=mpv_h(mpv_date($r->period_to));?></small><br><small>Pay date: <?=mpv_h(mpv_date($r->pay_date));?></small></td><td>Working <?=mpv_num($r->working_days,2);?><br><small>Paid <?=mpv_num($r->paid_days,2);?> | Absence <?=mpv_num($r->absence_days,2);?> | OT <?=mpv_num($r->overtime_hours,2);?></small></td><td><strong><?=mpv_money($r->gross_pay);?></strong><br><small>Earning <?=mpv_money($r->total_earning);?></small></td><td>Ded <?=mpv_money($r->total_deduction);?><br><small>Tax <?=mpv_money($r->tax_amount);?></small></td><td><strong style="font-size:15px"><?=mpv_money($r->net_pay);?></strong><br><small><?=mpv_h($r->currency);?></small></td><td><span class="label label-<?=mpv_status_class($r->payslip_status);?>"><?=mpv_h($r->payslip_status);?></span><br><small><?=mpv_h($r->release_channel);?> <?=mpv_h($r->released_at?:'');?></small></td><td><button class="btn btn-info btn-xs btn-mps-detail" data-id="<?=mpv_h($r->id);?>"><i class="fa fa-eye"></i> <?=hr_h('common_detail', 'Detail');?></button></td></tr><?php endforeach; ?></tbody></table></div></div>
  <div id="modal_mps_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:92%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-file-text-o"></i> Payslip Detail</h4></div><div class="modal-body" id="mps_detail_body"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=hr_h('common_close', 'Close');?></button></div></div></div></div>
<?php endif; ?>
</section>

<?php if($employee): ?>
<script src="<?=base_url();?>assets/js/highcharts.js"></script><script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
$(function(){if($.fn.datepicker)$('.mps-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});if($.fn.select2)$('.mps-select2').select2({width:'100%',allowClear:true});if($.fn.DataTable)$('#dtb_my_payslip').DataTable({pageLength:25,order:[[1,'desc']],columnDefs:[{targets:[7],orderable:false}]});$('#btnExportMyPayslip').click(function(){window.location='<?=base_admin();?>modul/my_payslip/my_payslip_action.php?act=export&'+$('#formFilterMyPayslip').serialize()});$(document).on('click','.btn-mps-detail',function(){$('#mps_detail_body').html('<div class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');$('#modal_mps_detail').modal('show');$.post('<?=base_admin();?>modul/my_payslip/my_payslip_action.php?act=detail',{id:$(this).data('id')},function(h){$('#mps_detail_body').html(h)}).fail(function(x){$('#mps_detail_body').html('<div class="alert alert-danger">'+(x.responseText||'Gagal memuat detail.')+'</div>')})});Highcharts.setOptions({lang:{thousandsSep:','},colors:['#db2777','#2563eb','#0f766e','#f59e0b','#dc2626','#7c3aed','#0891b2','#64748b']});Highcharts.chart('mps_pay_chart',{chart:{type:'areaspline'},title:{text:null},xAxis:{categories:<?=json_encode($netTrendCategories);?>},yAxis:{title:{text:'Amount'}},tooltip:{shared:true,valueDecimals:2},plotOptions:{areaspline:{fillOpacity:.15,marker:{enabled:true,radius:3}}},series:[{name:'Gross Pay',data:<?=json_encode($grossTrendData);?>},{name:'Net Pay',data:<?=json_encode($netTrendData);?>}],credits:{enabled:false}});Highcharts.chart('mps_status_chart',{chart:{type:'pie'},title:{text:null},tooltip:{pointFormat:'<b>{point.y}</b> slip'},plotOptions:{pie:{innerSize:'58%',dataLabels:{enabled:true,format:'{point.name}: {point.y}'}}},series:[{name:'Status',data:<?=json_encode($statusChart);?>}],credits:{enabled:false}})});
</script>
<?php endif; ?>
