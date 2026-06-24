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
.ts-hero{background:linear-gradient(135deg,#0f766e,#ca8a04);color:#fff;border-radius:14px;padding:20px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.16)}
.ts-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.ts-hero p{margin:0;opacity:.92}
.ts-card{border-radius:12px;background:#fff;border:1px solid #e5edf5;box-shadow:0 5px 16px rgba(15,23,42,.05);margin-bottom:14px}
.ts-kpi{padding:14px;border-radius:12px;background:#f8fafc;border:1px solid #e5edf5}.ts-kpi b{font-size:22px;display:block}.ts-kpi span{color:#64748b}
#dtb_training_result th,#dtb_training_result td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}
.required-label:after{content:' *';color:#dd4b39}.ts-action{white-space:nowrap}#modal_training_result .modal-body{max-height:calc(100vh - 185px);overflow-y:auto}.ts-section-title{font-weight:700;color:#334155;border-bottom:1px solid #e5edf5;margin:12px 0 12px;padding-bottom:8px}
</style>
<section class="content-header">
  <h1><?=hr_h('hr_training_result', 'Training Result');?> <small>SAP HR Learning Evaluation</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li><li>Human Resource</li><li>Training & Development</li><li class="active"><?=hr_h('hr_training_result', 'Training Result');?></li></ol>
</section>
<section class="content">
  <div class="ts-hero"><div class="row"><div class="col-md-8"><h1>Training Result Workbench</h1><p>Catat hasil training, evaluasi, pass/fail, competency achieved, certificate, feedback, dan follow-up action dari peserta yang sudah registrasi.</p></div><div class="col-md-4 text-right"><?php if($canInsert){ ?><button id="btn_open_ts" class="btn btn-warning"><i class="fa fa-plus"></i> Create Result</button><?php } ?> <button id="btn_export_ts" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=hr_h('common_export_excel', 'Export Excel');?></button></div></div></div>
  <div class="row">
    <div class="col-sm-2"><div class="ts-kpi"><span>Total</span><b id="kpi_total">0</b></div></div>
    <div class="col-sm-2"><div class="ts-kpi"><span>Passed</span><b id="kpi_passed">0</b></div></div>
    <div class="col-sm-2"><div class="ts-kpi"><span>Failed</span><b id="kpi_failed">0</b></div></div>
    <div class="col-sm-2"><div class="ts-kpi"><span>Completed</span><b id="kpi_completed">0</b></div></div>
    <div class="col-sm-2"><div class="ts-kpi"><span>Certified</span><b id="kpi_certified">0</b></div></div>
    <div class="col-sm-2"><div class="ts-kpi"><span>Avg Score</span><b id="kpi_avg_score">0</b></div></div>
  </div>
  <div class="box ts-card"><div class="box-header"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Training Result</h3></div><div class="box-body">
    <form class="form-horizontal" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">Result Date</label>
        <div class="col-lg-2"><div class="input-group date ts-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <div class="col-lg-2"><div class="input-group date ts-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1">Plan</label><div class="col-lg-5"><select id="filter_training_plan_id" class="form-control"></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=hr_h('hr_department', 'Department');?></label><div class="col-lg-3"><select id="filter_department_code" class="form-control"></select></div>
        <label class="control-label col-lg-1">Method</label><div class="col-lg-2"><select id="filter_evaluation_method" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>EXAM</option><option>PRACTICAL</option><option>OBSERVATION</option><option>ATTENDANCE_ONLY</option><option>MIXED</option></select></div>
        <label class="control-label col-lg-1">Result</label><div class="col-lg-3"><select id="filter_result_status" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>DRAFT</option><option>PASSED</option><option>FAILED</option><option>INCOMPLETE</option><option>NOT_EVALUATED</option></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">Completion</label><div class="col-lg-2"><select id="filter_completion_status" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>NOT_STARTED</option><option>IN_PROGRESS</option><option>COMPLETED</option><option>CANCELLED</option></select></div>
        <label class="control-label col-lg-1">Cert.</label><div class="col-lg-2"><select id="filter_certificate_issued" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>Y</option><option>N</option></select></div>
        <label class="control-label col-lg-1"><?=hr_h('common_search', 'Search');?></label><div class="col-lg-2"><input id="filter_keyword" class="form-control" placeholder="Result, employee, cert"></div>
        <div class="col-lg-2"><button id="btn_filter_ts" class="btn btn-primary"><i class="fa fa-filter"></i> <?=hr_h('common_filter', 'Filter');?></button> <button id="btn_reset_ts" class="btn btn-default"><i class="fa fa-refresh"></i> <?=hr_h('common_reset', 'Reset');?></button></div>
      </div>
    </form>
  </div></div>
  <div class="box ts-card"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div><div class="table-responsive"><table id="dtb_training_result" class="table table-bordered table-striped" style="width:100%"><thead><tr><th><?=hr_h('common_no', 'No');?></th><th><?=hr_h('common_action', 'Action');?></th><th>Result</th><th><?=hr_h('hr_training', 'Training');?></th><th><?=hr_h('hr_employee', 'Employee');?></th><th>Department / Job</th><th>Date / Method</th><th><?=hr_h('hr_score', 'Score');?></th><th><?=hr_h('common_status', 'Status');?></th><th>Certificate</th><th>Updated</th></tr></thead><tbody></tbody></table></div></div></div>
  <div id="modal_training_result" class="modal fade"><div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content"><form id="form_training_result"><input type="hidden" name="id" id="ts_id"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Training Result</h4></div><div class="modal-body">
    <div class="alert alert-info"><strong>SAP hint:</strong> Result dibuat dari registration peserta. Setelah disimpan, score/certificate akan ikut memperbarui ringkasan pada Training Registration.</div>
    <div class="ts-section-title">Result Header</div>
    <div class="row">
      <div class="col-md-2 form-group"><label class="required-label">Result No</label><input name="result_no" id="result_no" class="form-control text-uppercase" required maxlength="30"></div>
      <div class="col-md-6 form-group"><label class="required-label"><?=hr_h('hr_training_registration', 'Training Registration');?></label><select name="training_registration_id" id="training_registration_id" class="form-control" required></select></div>
      <div class="col-md-2 form-group"><label class="required-label">Result Date</label><input name="result_date" id="result_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
      <div class="col-md-2 form-group"><label>Method</label><select name="evaluation_method" id="evaluation_method" class="form-control"><option>EXAM</option><option>PRACTICAL</option><option>OBSERVATION</option><option>ATTENDANCE_ONLY</option><option>MIXED</option></select></div>
    </div>
    <div class="ts-section-title">Score & Status</div>
    <div class="row">
      <div class="col-md-2 form-group"><label>Pre Test</label><input name="pre_test_score" id="pre_test_score" class="form-control text-right" type="number" min="0" max="100" step="0.01"></div>
      <div class="col-md-2 form-group"><label>Post Test</label><input name="post_test_score" id="post_test_score" class="form-control text-right" type="number" min="0" max="100" step="0.01"></div>
      <div class="col-md-2 form-group"><label>Final Score</label><input name="final_score" id="final_score" class="form-control text-right" type="number" min="0" max="100" step="0.01"></div>
      <div class="col-md-2 form-group"><label>Passing Score</label><input name="passing_score" id="passing_score" class="form-control text-right" type="number" min="0" max="100" step="0.01"></div>
      <div class="col-md-2 form-group"><label>Result Status</label><select name="result_status" id="result_status" class="form-control"><option>DRAFT</option><option>PASSED</option><option>FAILED</option><option>INCOMPLETE</option><option>NOT_EVALUATED</option></select></div>
      <div class="col-md-2 form-group"><label>Completion</label><select name="completion_status" id="completion_status" class="form-control"><option>COMPLETED</option><option>IN_PROGRESS</option><option>NOT_STARTED</option><option>CANCELLED</option></select></div>
    </div>
    <div class="row">
      <div class="col-md-2 form-group"><label>Competency</label><select name="competency_achieved" id="competency_achieved" class="form-control"><option>Y</option><option>N</option><option>PARTIAL</option></select></div>
      <div class="col-md-2 form-group"><label>Certificate</label><select name="certificate_issued" id="certificate_issued" class="form-control"><option>N</option><option>Y</option></select></div>
      <div class="col-md-3 form-group"><label>Certificate No</label><input name="certificate_no" id="certificate_no" class="form-control" maxlength="60"></div>
      <div class="col-md-2 form-group"><label>Cert. Date</label><input name="certificate_date" id="certificate_date" class="form-control date-field"></div>
      <div class="col-md-3 form-group"><label>Valid Until</label><input name="certificate_valid_until" id="certificate_valid_until" class="form-control date-field"></div>
    </div>
    <div class="ts-section-title">Evaluation & Follow Up</div>
    <div class="row">
      <div class="col-md-3 form-group"><label>Evaluator</label><input name="evaluator_name" id="evaluator_name" class="form-control" maxlength="120" placeholder="HR / Trainer"></div>
      <div class="col-md-2 form-group"><label>Training Feedback</label><input name="training_feedback_score" id="training_feedback_score" class="form-control text-right" type="number" min="0" max="100" step="0.01"></div>
      <div class="col-md-2 form-group"><label>Trainer Feedback</label><input name="trainer_feedback_score" id="trainer_feedback_score" class="form-control text-right" type="number" min="0" max="100" step="0.01"></div>
      <div class="col-md-5 form-group"><label><?=hr_h('common_remarks', 'Remarks');?></label><input name="remarks" id="remarks" class="form-control"></div>
    </div>
    <div class="row"><div class="col-md-6 form-group"><label>Improvement Note</label><textarea name="improvement_note" id="improvement_note" class="form-control" rows="4"></textarea></div><div class="col-md-6 form-group"><label>Follow Up Action</label><textarea name="follow_up_action" id="follow_up_action" class="form-control" rows="4"></textarea></div></div>
  </div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=hr_h('common_cancel', 'Cancel');?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Result</button></div></form></div></div></div>
  <div id="modal_ts_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:90%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Training Result Detail</h4></div><div class="modal-body" id="ts_detail_body"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=hr_h('common_close', 'Close');?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function tsError(m){$('.isi_warning_delete').text(m||'Training Result gagal diproses.');$('.error_data_delete').fadeIn();}
function tsFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),training_plan_id:$('#filter_training_plan_id').val(),department_code:$('#filter_department_code').val(),evaluation_method:$('#filter_evaluation_method').val(),result_status:$('#filter_result_status').val(),completion_status:$('#filter_completion_status').val(),certificate_issued:$('#filter_certificate_issued').val(),keyword:$('#filter_keyword').val()};}
function tsSetSelect(selector,value,text){var el=$(selector);if(!value){el.val(null).trigger('change');return;}if(!el.find('option[value="'+value+'"]').length)el.append(new Option(text||value,value,true,true));el.val(value).trigger('change');}
function tsAjaxSelect(selector,act,placeholder,parent){$(selector).select2({width:'100%',allowClear:true,placeholder:placeholder,dropdownParent:parent||$(document.body),ajax:{url:'<?=base_admin();?>modul/training_result/training_result_action.php?act='+act,type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
function resetTsForm(title){$('#form_training_result')[0].reset();$('#ts_id').val('');$('#training_registration_id').empty().val(null).trigger('change');$('#result_date').val('<?=date('Y-m-d');?>');$('#evaluation_method').val('EXAM').trigger('change');$('#result_status').val('DRAFT').trigger('change');$('#completion_status').val('COMPLETED').trigger('change');$('#competency_achieved').val('Y').trigger('change');$('#certificate_issued').val('N').trigger('change');$('#modal_training_result .modal-title').text(title||'Create Training Result');$.getJSON('<?=base_admin();?>modul/training_result/training_result_action.php?act=next_no',function(r){if(r.status==='good')$('#result_no').val(r.code);});}
$(function(){
  if($.fn.datepicker){$('.ts-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_evaluation_method,#filter_result_status,#filter_completion_status,#filter_certificate_issued,#evaluation_method,#result_status,#completion_status,#competency_achieved,#certificate_issued').select2({width:'100%',allowClear:true});
    tsAjaxSelect('#filter_training_plan_id','plan_search','Semua Training Plan',$(document.body));
    tsAjaxSelect('#filter_department_code','department_search','Semua Department',$(document.body));
    tsAjaxSelect('#training_registration_id','registration_search','Pilih Training Registration',$('#modal_training_result'));
  }
  var dt=$('#dtb_training_result').DataTable({bProcessing:true,bServerSide:true,pageLength:25,order:[[6,'desc']],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{width:'42px',targets:0},{width:'150px',targets:1}],ajax:{url:'<?=base_admin();?>modul/training_result/training_result_data.php',type:'post',data:function(d){$.extend(d,tsFilters());},dataSrc:function(json){var k=json.kpi||{};$('#kpi_total').text(k.total||0);$('#kpi_passed').text(k.passed||0);$('#kpi_failed').text(k.failed||0);$('#kpi_completed').text(k.completed||0);$('#kpi_certified').text(k.certified||0);$('#kpi_avg_score').text(k.avg_score||0);return json.data||[];},error:function(xhr){console.log(xhr.responseText);tsError('Data Training Result gagal dimuat.');}}});
  $('#btn_open_ts').click(function(){resetTsForm('Create Training Result');$('#modal_training_result').modal({backdrop:'static',keyboard:false});});
  $('#btn_filter_ts').click(function(){dt.draw();});
  $('#filter_keyword').keyup(function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_ts').click(function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_training_plan_id,#filter_department_code,#filter_evaluation_method,#filter_result_status,#filter_completion_status,#filter_certificate_issued').val(null).trigger('change');$('#filter_keyword').val('');dt.draw();});
  $('#btn_export_ts').click(function(){window.location='<?=base_admin();?>modul/training_result/training_result_action.php?act=export&'+$.param(tsFilters());});
  $('.hide_alert_notif').click(function(){$('.error_data_delete').hide();});
  $('#result_no').on('keyup change',function(){this.value=this.value.toUpperCase();});
  $('#certificate_issued').on('change',function(){if($(this).val()==='Y')$('#certificate_no').attr('required',true);else $('#certificate_no').removeAttr('required');});
  $('#final_score,#passing_score').on('keyup change',function(){var f=parseFloat($('#final_score').val()),p=parseFloat($('#passing_score').val());if(!isNaN(f)&&!isNaN(p)){$('#result_status').val(f>=p?'PASSED':'FAILED').trigger('change');}});
  $('#form_training_result').submit(function(e){e.preventDefault();var btn=$(this).find('button[type=submit]');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=hr_h('common_saving', 'Saving...');?>');$.post('<?=base_admin();?>modul/training_result/training_result_action.php?act=save',$(this).serialize(),function(r){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Result');if(r.status==='good'){$('#modal_training_result').modal('hide');dt.draw(false);Swal.fire('Saved','Training Result berhasil disimpan.','success');}else tsError(r.error_message);},'json').fail(function(xhr){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Result');tsError(xhr.responseText);});});
  $(document).on('click','.btn-ts-detail',function(){$.post('<?=base_admin();?>modul/training_result/training_result_action.php?act=detail',{id:$(this).data('id')},function(html){$('#ts_detail_body').html(html);$('#modal_ts_detail').modal('show');});});
  $(document).on('click','.btn-ts-edit',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/training_result/training_result_action.php?act=get',{id:id},function(r){if(r.status!=='good'){tsError(r.error_message);return;}resetTsForm('Edit Training Result');var h=r.data||{};$('#ts_id').val(h.id);$('#result_no').val(h.result_no);tsSetSelect('#training_registration_id',h.training_registration_id,h.registration_text);$('#result_date').val(h.result_date);$('#evaluation_method').val(h.evaluation_method).trigger('change');$('#pre_test_score').val(h.pre_test_score);$('#post_test_score').val(h.post_test_score);$('#final_score').val(h.final_score);$('#passing_score').val(h.passing_score);$('#result_status').val(h.result_status).trigger('change');$('#completion_status').val(h.completion_status).trigger('change');$('#competency_achieved').val(h.competency_achieved).trigger('change');$('#certificate_issued').val(h.certificate_issued).trigger('change');$('#certificate_no').val(h.certificate_no);$('#certificate_date').val(h.certificate_date);$('#certificate_valid_until').val(h.certificate_valid_until);$('#evaluator_name').val(h.evaluator_name);$('#training_feedback_score').val(h.training_feedback_score);$('#trainer_feedback_score').val(h.trainer_feedback_score);$('#improvement_note').val(h.improvement_note);$('#follow_up_action').val(h.follow_up_action);$('#remarks').val(h.remarks);$('#modal_training_result').modal({backdrop:'static',keyboard:false});},'json');});
  $(document).on('click','.btn-ts-pass',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/training_result/training_result_action.php?act=mark_passed',{id:id},function(r){if(r.status==='good'){dt.draw(false);Swal.fire('Updated','Training Result berhasil ditandai PASSED.','success');}else tsError(r.error_message);},'json');});
  $(document).on('click','.btn-ts-delete',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:erpLang('confirm_delete_title','Delete Confirmation'),text:no,icon:'warning',showCancelButton:true,confirmButtonText:erpLang('common_delete','Delete'),cancelButtonText:erpLang('common_cancel','Cancel')}).then(function(x){if(!x.isConfirmed)return;$.post('<?=base_admin();?>modul/training_result/training_result_action.php?act=delete',{id:id},function(r){if(r.status==='good'){dt.draw(false);Swal.fire(erpLang('common_deleted','Deleted'),erpLang('common_deleted_message','Data deleted successfully.'),'success');}else tsError(r.error_message);},'json').fail(function(xhr){tsError(xhr.responseText);});});});
});
</script>
