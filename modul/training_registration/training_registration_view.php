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
$defaultFrom=date('Y-01-01');
$defaultTo=date('Y-12-31');
$canInsert=isset($role_act['insert_act']) && $role_act['insert_act']==='Y';
?>
<style>
.tr-hero{background:linear-gradient(135deg,#7c3aed,#0f766e);color:#fff;border-radius:14px;padding:20px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.16)}
.tr-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.tr-hero p{margin:0;opacity:.92}
.tr-card{border-radius:12px;background:#fff;border:1px solid #e5edf5;box-shadow:0 5px 16px rgba(15,23,42,.05);margin-bottom:14px}
.tr-kpi{padding:14px;border-radius:12px;background:#f8fafc;border:1px solid #e5edf5}.tr-kpi b{font-size:22px;display:block}.tr-kpi span{color:#64748b}
#dtb_training_registration th,#dtb_training_registration td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}
.required-label:after{content:' *';color:#dd4b39}.tr-action{white-space:nowrap}#modal_training_registration .modal-body{max-height:calc(100vh - 185px);overflow-y:auto}.tr-section-title{font-weight:700;color:#334155;border-bottom:1px solid #e5edf5;margin:12px 0 12px;padding-bottom:8px}
</style>
<section class="content-header">
  <h1><?=hr_h('hr_training_registration', 'Training Registration');?> <small>SAP HR Course Booking</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li><li>Human Resource</li><li>Training & Development</li><li class="active"><?=hr_h('hr_training_registration', 'Training Registration');?></li></ol>
</section>
<section class="content">
  <div class="tr-hero"><div class="row"><div class="col-md-8"><h1>Training Registration Workbench</h1><p>Kelola booking peserta dari training plan, approval registrasi, waitlist, attendance, learning hours, score, dan certificate reference.</p></div><div class="col-md-4 text-right"><?php if($canInsert){ ?><button id="btn_open_tr" class="btn btn-warning"><i class="fa fa-plus"></i> Register Employee</button><?php } ?> <button id="btn_export_tr" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=hr_h('common_export_excel', 'Export Excel');?></button></div></div></div>
  <div class="row">
    <div class="col-sm-2"><div class="tr-kpi"><span>Total</span><b id="kpi_total">0</b></div></div>
    <div class="col-sm-2"><div class="tr-kpi"><span>Registered</span><b id="kpi_registered">0</b></div></div>
    <div class="col-sm-2"><div class="tr-kpi"><span>Waitlist</span><b id="kpi_waitlist">0</b></div></div>
    <div class="col-sm-2"><div class="tr-kpi"><span>Present</span><b id="kpi_present">0</b></div></div>
    <div class="col-sm-2"><div class="tr-kpi"><span>Completed</span><b id="kpi_completed">0</b></div></div>
    <div class="col-sm-2"><div class="tr-kpi"><span>Avg Score</span><b id="kpi_avg_score">0</b></div></div>
  </div>
  <div class="box tr-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Training Registration</h3></div><div class="box-body">
    <form class="form-horizontal" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">Registration Date</label>
        <div class="col-lg-2"><div class="input-group date tr-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <div class="col-lg-2"><div class="input-group date tr-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1">Plan</label><div class="col-lg-5"><select id="filter_training_plan_id" class="form-control"></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=hr_h('hr_department', 'Department');?></label><div class="col-lg-3"><select id="filter_department_code" class="form-control"></select></div>
        <label class="control-label col-lg-1">Reg.</label><div class="col-lg-2"><select id="filter_registration_status" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>REGISTERED</option><option>WAITLIST</option><option>CANCELLED</option><option>ATTENDED</option><option>NO_SHOW</option><option>COMPLETED</option></select></div>
        <label class="control-label col-lg-1">Attend</label><div class="col-lg-3"><select id="filter_attendance_status" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>NOT_MARKED</option><option>PRESENT</option><option>ABSENT</option><option>PARTIAL</option></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">Approval</label><div class="col-lg-2"><select id="filter_approval_status" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>DRAFT</option><option>SUBMITTED</option><option>APPROVED</option><option>REJECTED</option></select></div>
        <label class="control-label col-lg-1"><?=hr_h('common_search', 'Search');?></label><div class="col-lg-4"><input id="filter_keyword" class="form-control" placeholder="Registration, plan, training, employee"></div>
        <div class="col-lg-3"><button id="btn_filter_tr" class="btn btn-primary"><i class="fa fa-filter"></i> <?=hr_h('common_filter', 'Filter');?></button> <button id="btn_reset_tr" class="btn btn-default"><i class="fa fa-refresh"></i> <?=hr_h('common_reset', 'Reset');?></button></div>
      </div>
    </form>
  </div></div>
  <div class="box tr-card"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div><div class="table-responsive"><table id="dtb_training_registration" class="table table-bordered table-striped" style="width:100%"><thead><tr><th><?=hr_h('common_no', 'No');?></th><th><?=hr_h('common_action', 'Action');?></th><th>Registration</th><th><?=hr_h('hr_training_plan', 'Training Plan');?></th><th><?=hr_h('hr_employee', 'Employee');?></th><th>Department / Job</th><th>Schedule</th><th><?=hr_h('common_status', 'Status');?></th><th><?=hr_h('hr_attendance', 'Attendance');?></th><th><?=hr_h('hr_score', 'Score');?></th><th>Updated</th></tr></thead><tbody></tbody></table></div></div></div>
  <div id="modal_training_registration" class="modal fade"><div class="modal-dialog modal-lg" style="width:92%"><div class="modal-content"><form id="form_training_registration"><input type="hidden" name="id" id="tr_id"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Register Employee</h4></div><div class="modal-body">
    <div class="alert alert-info"><strong>SAP hint:</strong> Registration adalah booking peserta pada training plan. Employee bisa diambil dari nominasi plan atau manual jika dibutuhkan HR.</div>
    <div class="tr-section-title">Registration Header</div>
    <div class="row">
      <div class="col-md-2 form-group"><label class="required-label">Reg. No</label><input name="registration_no" id="registration_no" class="form-control text-uppercase" required maxlength="30"></div>
      <div class="col-md-5 form-group"><label class="required-label"><?=hr_h('hr_training_plan', 'Training Plan');?></label><select name="training_plan_id" id="training_plan_id" class="form-control" required></select></div>
      <div class="col-md-3 form-group"><label class="required-label"><?=hr_h('hr_employee', 'Employee');?></label><select name="employee_id" id="employee_id" class="form-control" required></select></div>
      <div class="col-md-2 form-group"><label class="required-label">Reg. Date</label><input name="registration_date" id="registration_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
    </div>
    <div class="row">
      <div class="col-md-3 form-group"><label>Source</label><select name="registration_source" id="registration_source" class="form-control"><option>PLAN_NOMINATION</option><option>MANUAL</option><option>MANAGER_REQUEST</option><option>EMPLOYEE_SELF_SERVICE</option></select></div>
      <div class="col-md-3 form-group"><label>Registration Status</label><select name="registration_status" id="registration_status" class="form-control"><option>REGISTERED</option><option>WAITLIST</option><option>CANCELLED</option><option>ATTENDED</option><option>NO_SHOW</option><option>COMPLETED</option></select></div>
      <div class="col-md-3 form-group"><label>Approval</label><select name="approval_status" id="approval_status" class="form-control"><option>APPROVED</option><option>DRAFT</option><option>SUBMITTED</option><option>REJECTED</option></select></div>
      <div class="col-md-3 form-group"><label><?=hr_h('hr_attendance', 'Attendance');?></label><select name="attendance_status" id="attendance_status" class="form-control"><option>NOT_MARKED</option><option>PRESENT</option><option>ABSENT</option><option>PARTIAL</option></select></div>
    </div>
    <div class="tr-section-title">Attendance & Result Reference</div>
    <div class="row">
      <div class="col-md-3 form-group"><label>Check In</label><input name="check_in_time" id="check_in_time" class="form-control datetime-field" placeholder="YYYY-MM-DD HH:MM:SS"></div>
      <div class="col-md-3 form-group"><label>Check Out</label><input name="check_out_time" id="check_out_time" class="form-control datetime-field" placeholder="YYYY-MM-DD HH:MM:SS"></div>
      <div class="col-md-2 form-group"><label>Learning Hours</label><input name="learning_hours" id="learning_hours" class="form-control text-right" type="number" min="0" step="0.25" value="0"></div>
      <div class="col-md-2 form-group"><label><?=hr_h('hr_score', 'Score');?></label><input name="score" id="score" class="form-control text-right" type="number" min="0" max="100" step="0.01"></div>
      <div class="col-md-2 form-group"><label>Certificate Date</label><input name="certificate_date" id="certificate_date" class="form-control date-field"></div>
    </div>
    <div class="row">
      <div class="col-md-4 form-group"><label>Certificate No</label><input name="certificate_no" id="certificate_no" class="form-control" maxlength="60"></div>
      <div class="col-md-4 form-group"><label>Cancellation Reason</label><input name="cancellation_reason" id="cancellation_reason" class="form-control" maxlength="255"></div>
      <div class="col-md-4 form-group"><label><?=hr_h('common_remarks', 'Remarks');?></label><textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea></div>
    </div>
  </div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=hr_h('common_cancel', 'Cancel');?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Registration</button></div></form></div></div></div>
  <div id="modal_tr_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:90%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Training Registration Detail</h4></div><div class="modal-body" id="tr_detail_body"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=hr_h('common_close', 'Close');?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function trError(m){$('.isi_warning_delete').text(m||'Training Registration gagal diproses.');$('.error_data_delete').fadeIn();}
function trFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),training_plan_id:$('#filter_training_plan_id').val(),department_code:$('#filter_department_code').val(),registration_status:$('#filter_registration_status').val(),approval_status:$('#filter_approval_status').val(),attendance_status:$('#filter_attendance_status').val(),keyword:$('#filter_keyword').val()};}
function trSetSelect(selector,value,text){var el=$(selector);if(!value){el.val(null).trigger('change');return;}if(!el.find('option[value="'+value+'"]').length)el.append(new Option(text||value,value,true,true));el.val(value).trigger('change');}
function trAjaxSelect(selector,act,placeholder,parent){$(selector).select2({width:'100%',allowClear:true,placeholder:placeholder,dropdownParent:parent||$(document.body),ajax:{url:'<?=base_admin();?>modul/training_registration/training_registration_action.php?act='+act,type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||'',training_plan_id:$('#training_plan_id').val()||''};},processResults:function(d){return{results:d.results||[]};}}});}
function resetTrForm(title){$('#form_training_registration')[0].reset();$('#tr_id').val('');$('#training_plan_id,#employee_id').empty().val(null).trigger('change');$('#registration_date').val('<?=date('Y-m-d');?>');$('#registration_source').val('PLAN_NOMINATION').trigger('change');$('#registration_status').val('REGISTERED').trigger('change');$('#approval_status').val('APPROVED').trigger('change');$('#attendance_status').val('NOT_MARKED').trigger('change');$('#learning_hours').val('0');$('#modal_training_registration .modal-title').text(title||'Register Employee');$.getJSON('<?=base_admin();?>modul/training_registration/training_registration_action.php?act=next_no',function(r){if(r.status==='good')$('#registration_no').val(r.code);});}
$(function(){
  if($.fn.datepicker){$('.tr-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_registration_status,#filter_approval_status,#filter_attendance_status,#registration_source,#registration_status,#approval_status,#attendance_status').select2({width:'100%',allowClear:true});
    trAjaxSelect('#filter_training_plan_id','plan_search','Semua Training Plan',$(document.body));
    trAjaxSelect('#filter_department_code','department_search','Semua Department',$(document.body));
    trAjaxSelect('#training_plan_id','plan_search','Pilih Training Plan',$('#modal_training_registration'));
    trAjaxSelect('#employee_id','employee_search','Pilih Employee',$('#modal_training_registration'));
  }
  var dt=$('#dtb_training_registration').DataTable({bProcessing:true,bServerSide:true,pageLength:25,order:[[6,'desc']],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{width:'42px',targets:0},{width:'150px',targets:1}],ajax:{url:'<?=base_admin();?>modul/training_registration/training_registration_data.php',type:'post',data:function(d){$.extend(d,trFilters());},dataSrc:function(json){var k=json.kpi||{};$('#kpi_total').text(k.total||0);$('#kpi_registered').text(k.registered||0);$('#kpi_waitlist').text(k.waitlist||0);$('#kpi_present').text(k.present||0);$('#kpi_completed').text(k.completed||0);$('#kpi_avg_score').text(k.avg_score||0);return json.data||[];},error:function(xhr){console.log(xhr.responseText);trError('Data Training Registration gagal dimuat.');}}});
  $('#btn_open_tr').click(function(){resetTrForm('Register Employee');$('#modal_training_registration').modal({backdrop:'static',keyboard:false});});
  $('#btn_filter_tr').click(function(){dt.draw();});
  $('#filter_keyword').keyup(function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_tr').click(function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_training_plan_id,#filter_department_code,#filter_registration_status,#filter_approval_status,#filter_attendance_status').val(null).trigger('change');$('#filter_keyword').val('');dt.draw();});
  $('#btn_export_tr').click(function(){window.location='<?=base_admin();?>modul/training_registration/training_registration_action.php?act=export&'+$.param(trFilters());});
  $('.hide_alert_notif').click(function(){$('.error_data_delete').hide();});
  $('#registration_no').on('keyup change',function(){this.value=this.value.toUpperCase();});
  $('#training_plan_id').on('change',function(){$('#employee_id').val(null).trigger('change');});
  $('#form_training_registration').submit(function(e){e.preventDefault();var btn=$(this).find('button[type=submit]');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=hr_h('common_saving', 'Saving...');?>');$.post('<?=base_admin();?>modul/training_registration/training_registration_action.php?act=save',$(this).serialize(),function(r){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Registration');if(r.status==='good'){$('#modal_training_registration').modal('hide');dt.draw(false);Swal.fire('Saved','Training Registration berhasil disimpan.','success');}else trError(r.error_message);},'json').fail(function(xhr){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Registration');trError(xhr.responseText);});});
  $(document).on('click','.btn-tr-detail',function(){$.post('<?=base_admin();?>modul/training_registration/training_registration_action.php?act=detail',{id:$(this).data('id')},function(html){$('#tr_detail_body').html(html);$('#modal_tr_detail').modal('show');});});
  $(document).on('click','.btn-tr-edit',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/training_registration/training_registration_action.php?act=get',{id:id},function(r){if(r.status!=='good'){trError(r.error_message);return;}resetTrForm('Edit Training Registration');var h=r.data||{};$('#tr_id').val(h.id);$('#registration_no').val(h.registration_no);trSetSelect('#training_plan_id',h.training_plan_id,h.plan_text);trSetSelect('#employee_id',h.employee_id,h.employee_text);$('#registration_date').val(h.registration_date);$('#registration_source').val(h.registration_source).trigger('change');$('#registration_status').val(h.registration_status).trigger('change');$('#approval_status').val(h.approval_status).trigger('change');$('#attendance_status').val(h.attendance_status).trigger('change');$('#check_in_time').val(h.check_in_time);$('#check_out_time').val(h.check_out_time);$('#learning_hours').val(h.learning_hours);$('#score').val(h.score);$('#certificate_no').val(h.certificate_no);$('#certificate_date').val(h.certificate_date);$('#cancellation_reason').val(h.cancellation_reason);$('#remarks').val(h.remarks);$('#modal_training_registration').modal({backdrop:'static',keyboard:false});},'json');});
  $(document).on('click','.btn-tr-attend',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/training_registration/training_registration_action.php?act=mark_present',{id:id},function(r){if(r.status==='good'){dt.draw(false);Swal.fire('Updated','Attendance berhasil ditandai present.','success');}else trError(r.error_message);},'json');});
  $(document).on('click','.btn-tr-delete',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:erpLang('confirm_delete_title','Delete Confirmation'),text:no,icon:'warning',showCancelButton:true,confirmButtonText:erpLang('common_delete','Delete'),cancelButtonText:erpLang('common_cancel','Cancel')}).then(function(x){if(!x.isConfirmed)return;$.post('<?=base_admin();?>modul/training_registration/training_registration_action.php?act=delete',{id:id},function(r){if(r.status==='good'){dt.draw(false);Swal.fire(erpLang('common_deleted','Deleted'),erpLang('common_deleted_message','Data deleted successfully.'),'success');}else trError(r.error_message);},'json').fail(function(xhr){trError(xhr.responseText);});});});
});
</script>
