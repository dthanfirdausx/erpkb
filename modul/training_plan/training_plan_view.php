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
.tp-hero{background:linear-gradient(135deg,#1d4ed8,#7c3aed);color:#fff;border-radius:14px;padding:20px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.16)}
.tp-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.tp-hero p{margin:0;opacity:.92}
.tp-card{border-radius:12px;background:#fff;border:1px solid #e5edf5;box-shadow:0 5px 16px rgba(15,23,42,.05);margin-bottom:14px}
.tp-kpi{padding:14px;border-radius:12px;background:#f8fafc;border:1px solid #e5edf5}.tp-kpi b{font-size:22px;display:block}.tp-kpi span{color:#64748b}
#dtb_training_plan th,#dtb_training_plan td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}
.required-label:after{content:' *';color:#dd4b39}.tp-action{white-space:nowrap}.tp-pill{display:inline-block;border-radius:999px;padding:3px 9px;background:#eef2ff;color:#3730a3;font-weight:700;font-size:11px}
#modal_training_plan .modal-body{max-height:calc(100vh - 185px);overflow-y:auto}.tp-section-title{font-weight:700;color:#334155;border-bottom:1px solid #e5edf5;margin:12px 0 12px;padding-bottom:8px}
</style>
<section class="content-header">
  <h1><?=hr_h('hr_training_plan', 'Training Plan');?> <small>SAP HR Learning Plan</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li><li>Human Resource</li><li>Training & Development</li><li class="active"><?=hr_h('hr_training_plan', 'Training Plan');?></li></ol>
</section>
<section class="content">
  <div class="tp-hero"><div class="row"><div class="col-md-8"><h1>Training Plan Workbench</h1><p>Susun rencana training tahunan/periode dari catalog, target organisasi, budget, kuota, nominasi peserta, approval, dan status eksekusi.</p></div><div class="col-md-4 text-right"><?php if($canInsert){ ?><button id="btn_open_tp" class="btn btn-warning"><i class="fa fa-plus"></i> Create Plan</button><?php } ?> <button id="btn_export_tp" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=hr_h('common_export_excel', 'Export Excel');?></button></div></div></div>
  <div class="row">
    <div class="col-sm-2"><div class="tp-kpi"><span>Total Plan</span><b id="kpi_total">0</b></div></div>
    <div class="col-sm-2"><div class="tp-kpi"><span><?=hr_h('hr_approved', 'Approved');?></span><b id="kpi_approved">0</b></div></div>
    <div class="col-sm-2"><div class="tp-kpi"><span>Scheduled</span><b id="kpi_scheduled">0</b></div></div>
    <div class="col-sm-2"><div class="tp-kpi"><span>High Priority</span><b id="kpi_high_priority">0</b></div></div>
    <div class="col-sm-2"><div class="tp-kpi"><span>Participants</span><b id="kpi_participant">0</b></div></div>
    <div class="col-sm-2"><div class="tp-kpi"><span>Budget</span><b id="kpi_budget">0</b></div></div>
  </div>
  <div class="box tp-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Training Plan</h3></div><div class="box-body">
    <form class="form-horizontal" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">Plan Date</label>
        <div class="col-lg-2"><div class="input-group date tp-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <div class="col-lg-2"><div class="input-group date tp-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1">Year</label><div class="col-lg-1"><input id="filter_plan_year" class="form-control" value="<?=date('Y');?>"></div>
        <label class="control-label col-lg-1">Approval</label><div class="col-lg-3"><select id="filter_approval_status" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>DRAFT</option><option>SUBMITTED</option><option>APPROVED</option><option>REJECTED</option><option>CANCELLED</option><option>COMPLETED</option></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=hr_h('hr_training', 'Training');?></label><div class="col-lg-4"><select id="filter_training_catalog_id" class="form-control"></select></div>
        <label class="control-label col-lg-1">Dept</label><div class="col-lg-3"><select id="filter_target_department_code" class="form-control"></select></div>
        <div class="col-lg-2"><select id="filter_priority" class="form-control"><option value="">Priority</option><option>LOW</option><option>MEDIUM</option><option>HIGH</option><option>CRITICAL</option></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">Execution</label><div class="col-lg-2"><select id="filter_execution_status" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>NOT_STARTED</option><option>SCHEDULED</option><option>IN_PROGRESS</option><option>COMPLETED</option><option>CANCELLED</option></select></div>
        <label class="control-label col-lg-1"><?=hr_h('common_search', 'Search');?></label><div class="col-lg-4"><input id="filter_keyword" class="form-control" placeholder="Plan, catalog, department, owner"></div>
        <div class="col-lg-3"><button id="btn_filter_tp" class="btn btn-primary"><i class="fa fa-filter"></i> <?=hr_h('common_filter', 'Filter');?></button> <button id="btn_reset_tp" class="btn btn-default"><i class="fa fa-refresh"></i> <?=hr_h('common_reset', 'Reset');?></button></div>
      </div>
    </form>
  </div></div>
  <div class="box tp-card"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div><div class="table-responsive"><table id="dtb_training_plan" class="table table-bordered table-striped" style="width:100%"><thead><tr><th><?=hr_h('common_no', 'No');?></th><th><?=hr_h('common_action', 'Action');?></th><th>Plan</th><th><?=hr_h('hr_training_catalog', 'Training Catalog');?></th><th>Schedule / Source</th><th>Target</th><th>Priority / Owner</th><th>Budget / Quota</th><th>Participants</th><th><?=hr_h('common_status', 'Status');?></th><th>Updated</th></tr></thead><tbody></tbody></table></div></div></div>
  <div id="modal_training_plan" class="modal fade"><div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content"><form id="form_training_plan"><input type="hidden" name="id" id="tp_id"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Training Plan</h4></div><div class="modal-body">
    <div class="alert alert-info"><strong>SAP hint:</strong> Training Plan adalah rencana kebutuhan learning. Setelah approved, plan ini menjadi dasar registration/schedule/result.</div>
    <div class="tp-section-title">Plan Header</div>
    <div class="row">
      <div class="col-md-2 form-group"><label class="required-label">Plan Code</label><input name="plan_code" id="plan_code" class="form-control text-uppercase" required maxlength="30"></div>
      <div class="col-md-4 form-group"><label class="required-label">Plan Name</label><input name="plan_name" id="plan_name" class="form-control" required maxlength="160"></div>
      <div class="col-md-4 form-group"><label class="required-label"><?=hr_h('hr_training_catalog', 'Training Catalog');?></label><select name="training_catalog_id" id="training_catalog_id" class="form-control" required></select></div>
      <div class="col-md-2 form-group"><label class="required-label">Plan Year</label><input name="plan_year" id="plan_year" class="form-control" type="number" value="<?=date('Y');?>" required></div>
    </div>
    <div class="row">
      <div class="col-md-2 form-group"><label><?=hr_h('hr_period', 'Period');?></label><select name="plan_period" id="plan_period" class="form-control"><option>ANNUAL</option><option>Q1</option><option>Q2</option><option>Q3</option><option>Q4</option><option>MONTHLY</option><option>ADHOC</option></select></div>
      <div class="col-md-2 form-group"><label class="required-label">Start</label><input name="planned_start_date" id="planned_start_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
      <div class="col-md-2 form-group"><label class="required-label">End</label><input name="planned_end_date" id="planned_end_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
      <div class="col-md-2 form-group"><label>Priority</label><select name="priority" id="priority" class="form-control"><option>MEDIUM</option><option>LOW</option><option>HIGH</option><option>CRITICAL</option></select></div>
      <div class="col-md-2 form-group"><label>Approval</label><select name="approval_status" id="approval_status" class="form-control"><option>DRAFT</option><option>SUBMITTED</option><option>APPROVED</option><option>REJECTED</option><option>CANCELLED</option><option>COMPLETED</option></select></div>
      <div class="col-md-2 form-group"><label>Execution</label><select name="execution_status" id="execution_status" class="form-control"><option>NOT_STARTED</option><option>SCHEDULED</option><option>IN_PROGRESS</option><option>COMPLETED</option><option>CANCELLED</option></select></div>
    </div>
    <div class="tp-section-title">Target & Budget</div>
    <div class="row">
      <div class="col-md-3 form-group"><label>Target Department</label><select name="target_department_code" id="target_department_code" class="form-control"></select></div>
      <div class="col-md-3 form-group"><label>Target Job Title</label><select name="target_job_title_id" id="target_job_title_id" class="form-control"></select></div>
      <div class="col-md-2 form-group"><label>Employee Group</label><select name="target_employee_group" id="target_employee_group" class="form-control"><option value="">All</option><option>DIRECTOR</option><option>MANAGER</option><option>STAFF</option><option>NON_STAFF</option><option>OPERATOR</option><option>CONTRACT</option><option>DAILY_WORKER</option><option>TRAINEE</option></select></div>
      <div class="col-md-2 form-group"><label>Source</label><select name="source_type" id="source_type" class="form-control"><option>COMPETENCY_GAP</option><option>MANDATORY</option><option>MANAGER_REQUEST</option><option>SUCCESSION</option><option>REGULATORY</option><option>OTHER</option></select></div>
      <div class="col-md-2 form-group"><label>Planned Qty</label><input name="planned_participant" id="planned_participant" class="form-control text-right" type="number" min="0" value="0"></div>
    </div>
    <div class="row">
      <div class="col-md-2 form-group"><label>Currency</label><select name="currency" id="currency" class="form-control"><option>IDR</option><option>USD</option><option>EUR</option><option>SGD</option></select></div>
      <div class="col-md-2 form-group"><label>Budget</label><input name="budget_amount" id="budget_amount" class="form-control text-right" type="number" min="0" step="0.01" value="0"></div>
      <div class="col-md-3 form-group"><label>Plan Owner</label><input name="plan_owner" id="plan_owner" class="form-control" maxlength="80" placeholder="HR Learning Team"></div>
      <div class="col-md-5 form-group"><label>Location</label><input name="location" id="location" class="form-control" maxlength="150" placeholder="Training room / provider / online"></div>
    </div>
    <div class="tp-section-title">Participant Nomination</div>
    <div class="row"><div class="col-md-12 form-group"><label>Employees</label><select name="participants[]" id="participants" class="form-control" multiple></select><p class="help-block">Opsional. Jika belum pasti, cukup isi planned participant. Peserta bisa dilengkapi saat registration.</p></div></div>
    <div class="tp-section-title">Reason & Notes</div>
    <div class="row"><div class="col-md-6 form-group"><label>Business Reason</label><textarea name="business_reason" id="business_reason" class="form-control" rows="4"></textarea></div><div class="col-md-6 form-group"><label><?=hr_h('common_remarks', 'Remarks');?></label><textarea name="remarks" id="remarks" class="form-control" rows="4"></textarea></div></div>
  </div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=hr_h('common_cancel', 'Cancel');?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Plan</button></div></form></div></div></div>
  <div id="modal_tp_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:92%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Training Plan Detail</h4></div><div class="modal-body" id="tp_detail_body"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=hr_h('common_close', 'Close');?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function tpMoney(n){n=parseFloat(n||0);return n.toLocaleString('id-ID',{maximumFractionDigits:0});}
function tpError(m){$('.isi_warning_delete').text(m||'Training Plan gagal diproses.');$('.error_data_delete').fadeIn();}
function tpFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),plan_year:$('#filter_plan_year').val(),training_catalog_id:$('#filter_training_catalog_id').val(),target_department_code:$('#filter_target_department_code').val(),priority:$('#filter_priority').val(),approval_status:$('#filter_approval_status').val(),execution_status:$('#filter_execution_status').val(),keyword:$('#filter_keyword').val()};}
function tpSetSelect(selector,value,text){var el=$(selector);if(!value){el.val(null).trigger('change');return;}if(!el.find('option[value="'+value+'"]').length)el.append(new Option(text||value,value,true,true));el.val(value).trigger('change');}
function tpSetMultiParticipants(items){var el=$('#participants'),vals=[];(items||[]).forEach(function(p){if(!el.find('option[value="'+p.id+'"]').length)el.append(new Option(p.text,p.id,true,true));vals.push(String(p.id));});el.val(vals).trigger('change');}
function tpAjaxSelect(selector,act,placeholder,parent,multiple){$(selector).select2({width:'100%',allowClear:true,placeholder:placeholder,dropdownParent:parent||$(document.body),multiple:!!multiple,ajax:{url:'<?=base_admin();?>modul/training_plan/training_plan_action.php?act='+act,type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||'',department_code:$('#target_department_code').val()||''};},processResults:function(d){return{results:d.results||[]};}}});}
function resetTpForm(title){$('#form_training_plan')[0].reset();$('#tp_id').val('');$('#training_catalog_id,#target_department_code,#target_job_title_id,#participants').empty().val(null).trigger('change');$('#plan_year').val('<?=date('Y');?>');$('#planned_start_date,#planned_end_date').val('<?=date('Y-m-d');?>');$('#plan_period').val('ANNUAL').trigger('change');$('#priority').val('MEDIUM').trigger('change');$('#approval_status').val('DRAFT').trigger('change');$('#execution_status').val('NOT_STARTED').trigger('change');$('#source_type').val('COMPETENCY_GAP').trigger('change');$('#currency').val('IDR').trigger('change');$('#planned_participant,#budget_amount').val('0');$('#modal_training_plan .modal-title').text(title||'Create Training Plan');$.getJSON('<?=base_admin();?>modul/training_plan/training_plan_action.php?act=next_code',function(r){if(r.status==='good')$('#plan_code').val(r.code);});}
$(function(){
  if($.fn.datepicker){$('.tp-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_priority,#filter_approval_status,#filter_execution_status,#plan_period,#priority,#approval_status,#execution_status,#target_employee_group,#source_type,#currency').select2({width:'100%',allowClear:true});
    tpAjaxSelect('#filter_training_catalog_id','catalog_search','Semua Training',$(document.body),false);
    tpAjaxSelect('#filter_target_department_code','department_search','Semua Department',$(document.body),false);
    tpAjaxSelect('#training_catalog_id','catalog_search','Pilih Training Catalog',$('#modal_training_plan'),false);
    tpAjaxSelect('#target_department_code','department_search','Pilih Department',$('#modal_training_plan'),false);
    tpAjaxSelect('#target_job_title_id','job_title_search','Pilih Job Title',$('#modal_training_plan'),false);
    tpAjaxSelect('#participants','employee_search','Pilih peserta',$('#modal_training_plan'),true);
  }
  var dt=$('#dtb_training_plan').DataTable({bProcessing:true,bServerSide:true,pageLength:25,order:[[4,'desc']],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{width:'42px',targets:0},{width:'150px',targets:1}],ajax:{url:'<?=base_admin();?>modul/training_plan/training_plan_data.php',type:'post',data:function(d){$.extend(d,tpFilters());},dataSrc:function(json){var k=json.kpi||{};$('#kpi_total').text(k.total||0);$('#kpi_approved').text(k.approved||0);$('#kpi_scheduled').text(k.scheduled||0);$('#kpi_high_priority').text(k.high_priority||0);$('#kpi_participant').text(k.planned_participant||0);$('#kpi_budget').text(tpMoney(k.budget||0));return json.data||[];},error:function(xhr){console.log(xhr.responseText);tpError('Data Training Plan gagal dimuat.');}}});
  $('#btn_open_tp').click(function(){resetTpForm('Create Training Plan');$('#modal_training_plan').modal({backdrop:'static',keyboard:false});});
  $('#btn_filter_tp').click(function(){dt.draw();});
  $('#filter_keyword').keyup(function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_tp').click(function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_plan_year').val('<?=date('Y');?>');$('#filter_training_catalog_id,#filter_target_department_code,#filter_priority,#filter_approval_status,#filter_execution_status').val(null).trigger('change');$('#filter_keyword').val('');dt.draw();});
  $('#btn_export_tp').click(function(){window.location='<?=base_admin();?>modul/training_plan/training_plan_action.php?act=export&'+$.param(tpFilters());});
  $('.hide_alert_notif').click(function(){$('.error_data_delete').hide();});
  $('#plan_code').on('keyup change',function(){this.value=this.value.toUpperCase();});
  $('#participants').on('change',function(){var c=($(this).val()||[]).length;if(c>0 && parseInt($('#planned_participant').val()||0)<c)$('#planned_participant').val(c);});
  $('#form_training_plan').submit(function(e){e.preventDefault();var btn=$(this).find('button[type=submit]');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=hr_h('common_saving', 'Saving...');?>');$.post('<?=base_admin();?>modul/training_plan/training_plan_action.php?act=save',$(this).serialize(),function(r){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Plan');if(r.status==='good'){$('#modal_training_plan').modal('hide');dt.draw(false);Swal.fire('Saved','Training Plan berhasil disimpan.','success');}else tpError(r.error_message);},'json').fail(function(xhr){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Plan');tpError(xhr.responseText);});});
  $(document).on('click','.btn-tp-detail',function(){$.post('<?=base_admin();?>modul/training_plan/training_plan_action.php?act=detail',{id:$(this).data('id')},function(html){$('#tp_detail_body').html(html);$('#modal_tp_detail').modal('show');});});
  $(document).on('click','.btn-tp-edit',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/training_plan/training_plan_action.php?act=get',{id:id},function(r){if(r.status!=='good'){tpError(r.error_message);return;}resetTpForm('Edit Training Plan');var h=r.data||{};$('#tp_id').val(h.id);$('#plan_code').val(h.plan_code);$('#plan_name').val(h.plan_name);tpSetSelect('#training_catalog_id',h.training_catalog_id,h.catalog_text);$('#plan_year').val(h.plan_year);$('#plan_period').val(h.plan_period).trigger('change');$('#planned_start_date').val(h.planned_start_date);$('#planned_end_date').val(h.planned_end_date);tpSetSelect('#target_department_code',h.target_department_code,h.department_text);tpSetSelect('#target_job_title_id',h.target_job_title_id,h.job_title_text);$('#target_employee_group').val(h.target_employee_group).trigger('change');$('#priority').val(h.priority).trigger('change');$('#source_type').val(h.source_type).trigger('change');$('#planned_participant').val(h.planned_participant);$('#budget_amount').val(h.budget_amount);$('#currency').val(h.currency).trigger('change');$('#plan_owner').val(h.plan_owner);$('#location').val(h.location);$('#approval_status').val(h.approval_status).trigger('change');$('#execution_status').val(h.execution_status).trigger('change');$('#business_reason').val(h.business_reason);$('#remarks').val(h.remarks);tpSetMultiParticipants(h.participants||[]);$('#modal_training_plan').modal({backdrop:'static',keyboard:false});},'json');});
  $(document).on('click','.btn-tp-status',function(){var id=$(this).data('id'),status=$(this).data('status');$.post('<?=base_admin();?>modul/training_plan/training_plan_action.php?act=status',{id:id,status:status},function(r){if(r.status==='good'){dt.draw(false);Swal.fire('Updated','Status Training Plan berhasil diubah.','success');}else tpError(r.error_message);},'json');});
  $(document).on('click','.btn-tp-delete',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:erpLang('confirm_delete_title','Delete Confirmation'),text:no,icon:'warning',showCancelButton:true,confirmButtonText:erpLang('common_delete','Delete'),cancelButtonText:erpLang('common_cancel','Cancel')}).then(function(x){if(!x.isConfirmed)return;$.post('<?=base_admin();?>modul/training_plan/training_plan_action.php?act=delete',{id:id},function(r){if(r.status==='good'){dt.draw(false);Swal.fire(erpLang('common_deleted','Deleted'),erpLang('common_deleted_message','Data deleted successfully.'),'success');}else tpError(r.error_message);},'json').fail(function(xhr){tpError(xhr.responseText);});});});
});
</script>
