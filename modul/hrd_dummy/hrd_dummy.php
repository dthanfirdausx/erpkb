<?php
$currentUrl = uri_segment(1);
$menuInfo = $db->fetch("SELECT m.*, p.page_name parent_page
  FROM sys_menu m
  LEFT JOIN sys_menu p ON p.id=m.parent
  WHERE m.url=? LIMIT 1", array($currentUrl));

$pageName = $menuInfo ? $menuInfo->page_name : 'HRD';
$parentName = ($menuInfo && $menuInfo->parent_page) ? $menuInfo->parent_page : 'Human Resource';

$scopeMap = array(
  'company-structure' => array('Organizational unit, company code alignment, legal entity, dan struktur reporting HR.'),
  'hrd-department' => array('Master department/divisi untuk assignment employee, approval, dan costing.'),
  'position' => array('Position catalog, headcount, reporting line, dan vacancy status.'),
  'job-title' => array('Job title, job family, grade mapping, dan standard role description.'),
  'work-location' => array('Lokasi kerja, plant/site assignment, area kerja, dan lokasi absensi.'),
  'employee-master-data' => array('Data personal, employment, organization assignment, payroll area, dan status karyawan.'),
  'employee-contract' => array('Kontrak kerja, masa berlaku, probation, renewal, dan attachment dokumen.'),
  'employee-family-data' => array('Data keluarga/tanggungan untuk benefit, pajak, dan administrasi HR.'),
  'employee-education' => array('Riwayat pendidikan, skill, qualification, dan kompetensi dasar.'),
  'employee-document' => array('Dokumen karyawan seperti KTP, NPWP, kontrak, sertifikat, dan file compliance.'),
  'employee-mutation' => array('Mutasi/promosi/demosi/transfer antar department, position, lokasi, atau company.'),
  'work-schedule' => array('Work schedule rule, working day, planned working hour, dan calendar kerja.'),
  'shift-schedule' => array('Penjadwalan shift employee/team dan assignment shift periodik.'),
  'attendance' => array('Absensi masuk/keluar, late, early leave, correction, dan source mesin absensi.'),
  'overtime' => array('Request lembur, approval lembur, planned/actual overtime, dan payroll integration.'),
  'leave-request' => array('Pengajuan cuti, quota check, attachment, dan workflow approval.'),
  'leave-approval' => array('Approval cuti oleh atasan/HR dan monitoring status cuti.'),
  'holiday-calendar' => array('Kalender libur nasional, cuti bersama, dan kalender kerja perusahaan.'),
  'payroll-component' => array('Komponen gaji, allowance, deduction, benefit, tax, dan formula payroll.'),
  'salary-structure' => array('Struktur salary grade, pay scale, basic salary, allowance package, dan effective date.'),
  'payroll-process' => array('Run payroll periodik, gross to net, koreksi payroll, dan preview payslip.'),
  'payroll-posting' => array('Posting payroll ke jurnal finance, cost center split, dan reconciliation.'),
  'payslip' => array('Payslip employee, distribusi slip gaji, dan riwayat payroll personal.'),
  'payroll-history' => array('History payroll per periode, employee, department, dan payroll area.'),
  'manpower-planning' => array('Rencana kebutuhan tenaga kerja berdasarkan department, position, dan budget.'),
  'job-vacancy' => array('Lowongan pekerjaan, requirement, publishing status, dan vacancy pipeline.'),
  'applicant-data' => array('Data pelamar, CV, source kandidat, dan status proses rekrutmen.'),
  'interview-schedule' => array('Jadwal interview, interviewer, feedback, dan calendar recruitment.'),
  'selection-result' => array('Hasil seleksi, scoring, recommendation, dan offering decision.'),
  'hiring' => array('Hiring process, conversion applicant ke employee, onboarding checklist.'),
  'training-catalog' => array('Catalog training, provider, competency target, cost, dan schedule template.'),
  'training-plan' => array('Rencana training tahunan/periode berdasarkan competency gap dan department.'),
  'training-registration' => array('Registrasi peserta training, approval, quota, dan attendance training.'),
  'training-result' => array('Hasil training, nilai, evaluasi, feedback, dan completion status.'),
  'certification' => array('Sertifikasi employee, masa berlaku, reminder expiry, dan compliance.'),
  'kpi-template' => array('Template KPI per role/department, weight, target, dan measurement rule.'),
  'employee-kpi' => array('Assignment KPI ke employee, target periodik, progress, dan self assessment.'),
  'performance-appraisal' => array('Penilaian performance, review cycle, score, dan calibration.'),
  'appraisal-approval' => array('Approval appraisal, final rating, dan sign-off manager/HR.'),
  'performance-history' => array('Riwayat penilaian employee, rating trend, dan development action.'),
  'my-profile' => array('Employee self service untuk melihat dan memperbarui data pribadi tertentu.'),
  'my-attendance' => array('Employee self service untuk melihat absensi, correction, dan riwayat kehadiran.'),
  'my-leave' => array('Employee self service untuk cuti, quota, request, dan status approval.'),
  'my-payslip' => array('Employee self service untuk melihat payslip dan history payroll pribadi.'),
  'my-request' => array('Employee self service untuk request HR seperti surat keterangan, perubahan data, dan klaim.'),
  'team-attendance' => array('Manager view untuk absensi team, exception, late, dan correction approval.'),
  'team-leave-approval' => array('Manager self service untuk approval cuti team dan monitoring quota.'),
  'team-overtime-approval' => array('Manager self service untuk approval lembur team dan validasi jam aktual.'),
  'team-performance' => array('Manager view untuk KPI, appraisal, dan performance team.'),
  'team-request-approval' => array('Manager approval center untuk request HR dari anggota team.'),
  'employee-report' => array('Report headcount, movement, organization assignment, dan data employee.'),
  'attendance-report' => array('Report absensi, late, absence, correction, dan attendance summary.'),
  'overtime-report' => array('Report lembur planned/actual, approval status, dan payroll impact.'),
  'leave-report' => array('Report cuti, quota, usage, pending approval, dan leave balance.'),
  'payroll-report' => array('Report payroll summary, component, cost center, dan posting status.'),
  'training-report' => array('Report training plan, realization, cost, result, dan certification.'),
  'performance-report' => array('Report KPI, appraisal score, rating distribution, dan performance trend.')
);

$scope = isset($scopeMap[$currentUrl]) ? $scopeMap[$currentUrl][0] : 'Placeholder modul HRD sesuai struktur SAP HCM.';
?>

<style>
  .hrd-placeholder-hero {
    position: relative;
    overflow: hidden;
    margin-bottom: 18px;
    padding: 26px 28px;
    border-radius: 20px;
    background: linear-gradient(135deg, #0f766e, #115e59);
    color: #fff;
    box-shadow: 0 16px 36px rgba(15, 118, 110, .22);
  }
  .hrd-placeholder-hero h1 {
    margin: 0 0 8px;
    font-weight: 800;
    letter-spacing: -.03em;
  }
  .hrd-placeholder-hero p {
    max-width: 780px;
    margin: 0;
    color: rgba(255,255,255,.82);
    line-height: 1.65;
  }
  .hrd-placeholder-card {
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 12px 30px rgba(15, 23, 42, .06);
  }
  .hrd-placeholder-card .box-body {
    padding: 24px;
  }
  .hrd-placeholder-badge {
    display: inline-block;
    margin-bottom: 12px;
    padding: 7px 11px;
    border-radius: 999px;
    background: #ecfdf5;
    color: #047857;
    font-weight: 800;
    font-size: 12px;
  }
  .hrd-placeholder-list {
    margin: 16px 0 0;
    padding-left: 18px;
    color: #475569;
    line-height: 1.9;
  }
  .hrd-placeholder-filter .form-group { margin-bottom: 10px; }
  .hrd-placeholder-table th,
  .hrd-placeholder-table td { font-size: 12px; vertical-align: middle !important; }
</style>

<section class="content-header">
  <h1><?=htmlspecialchars($pageName, ENT_QUOTES, 'UTF-8');?> <small>HRD SAP Placeholder</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li>Human Resource</li>
    <li><?=htmlspecialchars($parentName, ENT_QUOTES, 'UTF-8');?></li>
    <li class="active"><?=htmlspecialchars($pageName, ENT_QUOTES, 'UTF-8');?></li>
  </ol>
</section>

<section class="content">
  <div class="hrd-placeholder-hero">
    <h1><?=htmlspecialchars($pageName, ENT_QUOTES, 'UTF-8');?></h1>
    <p><?=htmlspecialchars($scope, ENT_QUOTES, 'UTF-8');?></p>
  </div>

  <div class="box hrd-placeholder-card">
    <div class="box-body">
      <span class="hrd-placeholder-badge">Dummy Page - Ready for Development</span>
      <h3 style="margin-top:0;">Modul HRD belum dikembangkan</h3>
      <p class="text-muted">
        Halaman ini disiapkan sebagai workspace awal modul HRD. Nanti fitur, tabel, workflow,
        approval, filter, dan export Excel bisa dikembangkan bertahap sesuai prioritas.
      </p>
      <ul class="hrd-placeholder-list">
        <li>Menu sudah terdaftar di struktur ERP dan bisa diberikan role akses.</li>
        <li>Ruang lingkup mengikuti pola SAP HCM / SuccessFactors.</li>
        <li>Belum ada transaksi atau master data yang dibuat pada halaman dummy ini.</li>
      </ul>
    </div>
  </div>

  <?php if ($currentUrl === 'training-report') { ?>
  <div class="row">
    <div class="col-md-3"><div class="small-box bg-aqua"><div class="inner"><h3>12</h3><p>Training Plan</p></div><div class="icon"><i class="fa fa-calendar"></i></div></div></div>
    <div class="col-md-3"><div class="small-box bg-green"><div class="inner"><h3>8</h3><p>Completed</p></div><div class="icon"><i class="fa fa-check"></i></div></div></div>
    <div class="col-md-3"><div class="small-box bg-yellow"><div class="inner"><h3>3</h3><p>In Progress</p></div><div class="icon"><i class="fa fa-hourglass-half"></i></div></div></div>
    <div class="col-md-3"><div class="small-box bg-red"><div class="inner"><h3>1</h3><p>Overdue</p></div><div class="icon"><i class="fa fa-warning"></i></div></div></div>
  </div>
  <div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Training Report</h3></div>
    <div class="box-body hrd-placeholder-filter">
      <form class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-md-2">Period</label>
          <div class="col-md-2"><input id="training_period_from" class="form-control" value="<?=date('Y-m-01');?>"></div>
          <div class="col-md-2"><input id="training_period_to" class="form-control" value="<?=date('Y-m-d');?>"></div>
          <label class="control-label col-md-1">Status</label>
          <div class="col-md-3"><select id="training_status" class="form-control select2"><option value="">All</option><option>Planned</option><option>In Progress</option><option>Completed</option><option>Overdue</option></select></div>
          <div class="col-md-2"><button type="button" id="training_reset" class="btn btn-default btn-block"><i class="fa fa-refresh"></i> Reset</button></div>
        </div>
      </form>
    </div>
  </div>
  <div class="box">
    <div class="box-body table-responsive">
      <table id="training_report_table" class="table table-bordered table-striped table-hover hrd-placeholder-table">
        <thead><tr><th>No</th><th>Training</th><th>Department</th><th>Plan Date</th><th>Participant</th><th>Status</th><th>Result</th><th>Action</th></tr></thead>
        <tbody>
          <tr><td>1</td><td>Safety Induction</td><td>Produksi</td><td><?=date('Y-m-d');?></td><td>24</td><td><span class="label label-success">Completed</span></td><td>Pass Rate 96%</td><td><button type="button" class="btn btn-info btn-xs training-detail" data-title="Safety Induction" data-status="Completed" data-result="Pass Rate 96%"><i class="fa fa-eye"></i></button></td></tr>
          <tr><td>2</td><td>Customs Compliance</td><td>Warehouse</td><td><?=date('Y-m-d', strtotime('+7 days'));?></td><td>12</td><td><span class="label label-warning">Planned</span></td><td>-</td><td><button type="button" class="btn btn-info btn-xs training-detail" data-title="Customs Compliance" data-status="Planned" data-result="-"><i class="fa fa-eye"></i></button></td></tr>
          <tr><td>3</td><td>Leadership Basic</td><td>HRD</td><td><?=date('Y-m-d', strtotime('+14 days'));?></td><td>8</td><td><span class="label label-primary">In Progress</span></td><td>Evaluation Open</td><td><button type="button" class="btn btn-info btn-xs training-detail" data-title="Leadership Basic" data-status="In Progress" data-result="Evaluation Open"><i class="fa fa-eye"></i></button></td></tr>
        </tbody>
      </table>
    </div>
  </div>
  <div class="modal fade" id="training_detail_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog"><div class="modal-content">
      <div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-eye"></i> Training Report Detail</h4></div>
      <div class="modal-body"><table class="table table-bordered table-condensed"><tbody id="training_detail_body"></tbody></table></div>
      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button></div>
    </div></div>
  </div>
  <script>
  $(function(){
    if($.fn.select2){$('#training_status').select2({width:'100%',allowClear:true});}
    var trainingTable=$('#training_report_table').DataTable({pageLength:10,dom:'Bfrtip',buttons:[{extend:'excelHtml5',text:'<i class="fa fa-file-excel-o"></i> Export Excel',className:'btn btn-success btn-sm',title:'Training Report'},{extend:'print',text:'<i class="fa fa-print"></i> Print',className:'btn btn-default btn-sm',title:'Training Report'}],columnDefs:[{targets:[7],orderable:false,searchable:false,className:'text-center'}]});
    $('#training_status').on('change',function(){trainingTable.column(5).search(this.value).draw();});
    $('#training_reset').on('click',function(){$('#training_status').val('').trigger('change');trainingTable.search('').columns().search('').draw();});
    $(document).on('click','.training-detail',function(){var b=$(this),rows=[['Training',b.data('title')],['Status',b.data('status')],['Result',b.data('result')]],html='';$.each(rows,function(_,r){html+='<tr><th style="width:34%;background:#f7f9fb">'+$('<div>').text(r[0]).html()+'</th><td>'+$('<div>').text(r[1]||'-').html()+'</td></tr>';});$('#training_detail_body').html(html);$('#training_detail_modal').modal('show');});
  });
  </script>
  <?php } ?>
</section>
