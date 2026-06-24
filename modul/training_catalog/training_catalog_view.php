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
$defaultFrom=date('Y-m-d');
$defaultTo='9999-12-31';
$canInsert=isset($role_act['insert_act']) && $role_act['insert_act']==='Y';
?>
<style>
.tc-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:20px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.16)}
.tc-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.tc-hero p{margin:0;opacity:.92}
.tc-card{border-radius:12px;background:#fff;border:1px solid #e5edf5;box-shadow:0 5px 16px rgba(15,23,42,.05);margin-bottom:14px}
.tc-kpi{padding:14px;border-radius:12px;background:#f8fafc;border:1px solid #e5edf5}.tc-kpi b{font-size:22px;display:block}.tc-kpi span{color:#64748b}
#dtb_training_catalog th,#dtb_training_catalog td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}
.required-label:after{content:' *';color:#dd4b39}.tc-action{white-space:nowrap}.tc-pill{display:inline-block;border-radius:999px;padding:3px 9px;background:#ecfeff;color:#0f766e;font-weight:700;font-size:11px}
#modal_training_catalog .modal-body{max-height:calc(100vh - 185px);overflow-y:auto}.tc-note{color:#64748b;font-size:12px;margin-top:5px}
.tc-section-title{font-weight:700;color:#334155;border-bottom:1px solid #e5edf5;margin:12px 0 12px;padding-bottom:8px}
</style>
<section class="content-header">
  <h1><?=hr_h('hr_training_catalog', 'Training Catalog');?> <small>SAP HR Learning Catalog</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li><li><?=hr_h('hr_human_resource', 'Human Resource');?></li><li><?=hr_h('hr_training_development', 'Training & Development');?></li><li class="active"><?=hr_h('hr_training_catalog', 'Training Catalog');?></li></ol>
</section>
<section class="content">
  <div class="tc-hero">
    <div class="row">
      <div class="col-md-8"><h1><?=hr_h('hr_training_catalog_workbench', 'Training Catalog Workbench');?></h1><p><?=hr_h('hr_training_catalog_workbench_desc', 'Manage training master data for mandatory learning, certification, refresher, provider, assessment, certificate validity, cost, and HR ownership.');?></p></div>
      <div class="col-md-4 text-right"><?php if($canInsert){ ?><button id="btn_open_tc" class="btn btn-warning"><i class="fa fa-plus"></i> <?=hr_h('hr_create_training', 'Create Training');?></button><?php } ?> <button id="btn_export_tc" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=hr_h('common_export_excel', 'Export Excel');?></button></div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-2"><div class="tc-kpi"><span><?=hr_h('hr_total_catalog', 'Total Catalog');?></span><b id="kpi_total">0</b></div></div>
    <div class="col-sm-2"><div class="tc-kpi"><span><?=hr_h('hr_active', 'Active');?></span><b id="kpi_active">0</b></div></div>
    <div class="col-sm-2"><div class="tc-kpi"><span><?=hr_h('hr_mandatory', 'Mandatory');?></span><b id="kpi_mandatory">0</b></div></div>
    <div class="col-sm-2"><div class="tc-kpi"><span><?=hr_h('hr_certification', 'Certification');?></span><b id="kpi_certification">0</b></div></div>
    <div class="col-sm-2"><div class="tc-kpi"><span><?=hr_h('hr_avg_duration', 'Avg Duration');?></span><b id="kpi_avg_duration">0</b></div></div>
    <div class="col-sm-2"><div class="tc-kpi"><span><?=hr_h('hr_total_budget', 'Total Budget');?></span><b id="kpi_total_cost">0</b></div></div>
  </div>

  <div class="box tc-card">
    <div class="box-header"><h3 class="box-title"><i class="fa fa-filter"></i> <?=hr_h('hr_filter_training_catalog', 'Filter Training Catalog');?></h3></div>
    <div class="box-body">
      <form class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=hr_h('hr_validity', 'Validity');?></label>
          <div class="col-lg-2"><div class="input-group date tc-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date tc-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=hr_h('hr_category', 'Category');?></label>
          <div class="col-lg-2"><select id="filter_training_category" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>TECHNICAL</option><option>QUALITY</option><option>SAFETY</option><option>COMPLIANCE</option><option>LEADERSHIP</option><option>SOFT_SKILL</option><option>ONBOARDING</option><option>CERTIFICATION</option><option>OTHER</option></select></div>
          <label class="control-label col-lg-1"><?=hr_h('common_status', 'Status');?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>DRAFT</option><option>ACTIVE</option><option>INACTIVE</option><option>OBSOLETE</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=hr_h('hr_method', 'Method');?></label>
          <div class="col-lg-2"><select id="filter_delivery_method" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>CLASSROOM</option><option>ONLINE</option><option>BLENDED</option><option>ON_THE_JOB</option><option>WORKSHOP</option><option>EXTERNAL</option></select></div>
          <label class="control-label col-lg-1"><?=hr_h('common_type', 'Type');?></label>
          <div class="col-lg-2"><select id="filter_training_type" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>MANDATORY</option><option>OPTIONAL</option><option>CERTIFICATION</option><option>REFRESHER</option></select></div>
          <label class="control-label col-lg-1"><?=hr_h('hr_provider', 'Provider');?></label>
          <div class="col-lg-2"><select id="filter_provider_type" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>INTERNAL</option><option>EXTERNAL</option></select></div>
          <div class="col-lg-2"><input id="filter_keyword" class="form-control" placeholder="<?=hr_h('hr_search_training_catalog', 'Search code, name, provider, competency');?>"></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=hr_h('hr_owner_department', 'Owner Department');?></label>
          <div class="col-lg-4"><select id="filter_owner_department_code" class="form-control"></select></div>
          <div class="col-lg-6"><button id="btn_filter_tc" class="btn btn-primary"><i class="fa fa-filter"></i> <?=hr_h('common_filter', 'Filter');?></button> <button id="btn_reset_tc" class="btn btn-default"><i class="fa fa-refresh"></i> <?=hr_h('common_reset', 'Reset');?></button></div>
        </div>
      </form>
    </div>
  </div>

  <div class="box tc-card">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div>
      <div class="table-responsive"><table id="dtb_training_catalog" class="table table-bordered table-striped" style="width:100%"><thead><tr><th><?=hr_h('common_no', 'No');?></th><th><?=hr_h('common_action', 'Action');?></th><th><?=hr_h('hr_training', 'Training');?></th><th>Category / Type</th><th>Method / Level</th><th>Provider / Owner</th><th>Duration / Cost</th><th>Assessment</th><th>Validity</th><th><?=hr_h('common_status', 'Status');?></th><th><?=hr_h('common_updated', 'Updated');?></th></tr></thead><tbody></tbody></table></div>
    </div>
  </div>

  <div id="modal_training_catalog" class="modal fade">
    <div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content">
      <form id="form_training_catalog">
        <input type="hidden" name="id" id="tc_id">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Training Catalog</h4></div>
        <div class="modal-body">
          <div class="alert alert-info"><strong>SAP hint:</strong> Training Catalog adalah master course/program. Training Plan dan Registration nanti mengambil referensi dari catalog ini.</div>
          <div class="tc-section-title">Basic Course Data</div>
          <div class="row">
            <div class="col-md-2 form-group"><label class="required-label">Training Code</label><input name="training_code" id="training_code" class="form-control text-uppercase" required maxlength="30" placeholder="TRN-SAF-001"></div>
            <div class="col-md-4 form-group"><label class="required-label">Training Name</label><input name="training_name" id="training_name" class="form-control" required maxlength="150"></div>
            <div class="col-md-2 form-group"><label class="required-label">Category</label><select name="training_category" id="training_category" class="form-control" required><option value="">Pilih</option><option>TECHNICAL</option><option>QUALITY</option><option>SAFETY</option><option>COMPLIANCE</option><option>LEADERSHIP</option><option>SOFT_SKILL</option><option>ONBOARDING</option><option>CERTIFICATION</option><option>OTHER</option></select></div>
            <div class="col-md-2 form-group"><label class="required-label">Method</label><select name="delivery_method" id="delivery_method" class="form-control" required><option value="">Pilih</option><option>CLASSROOM</option><option>ONLINE</option><option>BLENDED</option><option>ON_THE_JOB</option><option>WORKSHOP</option><option>EXTERNAL</option></select></div>
            <div class="col-md-2 form-group"><label class="required-label"><?=hr_h('common_status', 'Status');?></label><select name="status" id="status" class="form-control" required><option>DRAFT</option><option>ACTIVE</option><option>INACTIVE</option><option>OBSOLETE</option></select></div>
          </div>
          <div class="row">
            <div class="col-md-2 form-group"><label class="required-label">Level</label><select name="training_level" id="training_level" class="form-control" required><option value="">Pilih</option><option>BASIC</option><option>INTERMEDIATE</option><option>ADVANCED</option><option>EXPERT</option></select></div>
            <div class="col-md-2 form-group"><label class="required-label">Type</label><select name="training_type" id="training_type" class="form-control" required><option value="">Pilih</option><option>MANDATORY</option><option>OPTIONAL</option><option>CERTIFICATION</option><option>REFRESHER</option></select></div>
            <div class="col-md-2 form-group"><label class="required-label">Duration Hours</label><input name="duration_hours" id="duration_hours" class="form-control text-right" type="number" min="0.25" step="0.25" required></div>
            <div class="col-md-2 form-group"><label>Max Participant</label><input name="max_participant" id="max_participant" class="form-control text-right" type="number" min="0" value="0"></div>
            <div class="col-md-2 form-group"><label class="required-label">Valid From</label><input name="valid_from" id="valid_from" class="form-control date-field" value="<?=$defaultFrom;?>" required></div>
            <div class="col-md-2 form-group"><label class="required-label">Valid To</label><input name="valid_to" id="valid_to" class="form-control date-field" value="<?=$defaultTo;?>" required></div>
          </div>
          <div class="tc-section-title">Learning Design</div>
          <div class="row">
            <div class="col-md-4 form-group"><label>Target Audience</label><textarea name="target_audience" id="target_audience" class="form-control" rows="3" placeholder="Contoh: Operator produksi, QC inspector, supervisor"></textarea></div>
            <div class="col-md-4 form-group"><label>Competency Area</label><input name="competency_area" id="competency_area" class="form-control" maxlength="120" placeholder="Safety, Quality, Customs Compliance"></div>
            <div class="col-md-4 form-group"><label>Prerequisite</label><textarea name="prerequisite" id="prerequisite" class="form-control" rows="3"></textarea></div>
          </div>
          <div class="row">
            <div class="col-md-6 form-group"><label>Learning Objective</label><textarea name="learning_objective" id="learning_objective" class="form-control" rows="4"></textarea></div>
            <div class="col-md-6 form-group"><label>Syllabus</label><textarea name="syllabus" id="syllabus" class="form-control" rows="4"></textarea></div>
          </div>
          <div class="tc-section-title">Provider, Cost, Assessment</div>
          <div class="row">
            <div class="col-md-2 form-group"><label class="required-label">Provider Type</label><select name="provider_type" id="provider_type" class="form-control" required><option>INTERNAL</option><option>EXTERNAL</option></select></div>
            <div class="col-md-3 form-group"><label>Provider Name</label><input name="provider_name" id="provider_name" class="form-control" maxlength="120"></div>
            <div class="col-md-3 form-group"><label>Owner Department</label><select name="owner_department_code" id="owner_department_code" class="form-control"></select></div>
            <div class="col-md-4 form-group"><label>Cost Center</label><select name="cost_center_code" id="cost_center_code" class="form-control"></select></div>
          </div>
          <div class="row">
            <div class="col-md-2 form-group"><label>Currency</label><select name="currency" id="currency" class="form-control"><option>IDR</option><option>USD</option><option>EUR</option><option>SGD</option><option>JPY</option></select></div>
            <div class="col-md-2 form-group"><label>Cost Estimate</label><input name="cost_estimate" id="cost_estimate" class="form-control text-right" type="number" min="0" step="0.01" value="0"></div>
            <div class="col-md-2 form-group"><label>Assessment</label><select name="assessment_required" id="assessment_required" class="form-control"><option>N</option><option>Y</option></select></div>
            <div class="col-md-2 form-group"><label>Passing Score</label><input name="passing_score" id="passing_score" class="form-control text-right" type="number" min="0" max="100" step="0.01"></div>
            <div class="col-md-2 form-group"><label>Certificate</label><select name="certificate_required" id="certificate_required" class="form-control"><option>N</option><option>Y</option></select></div>
            <div class="col-md-2 form-group"><label>Cert. Validity Month</label><input name="validity_months" id="validity_months" class="form-control text-right" type="number" min="0" value="0"></div>
          </div>
          <div class="tc-section-title">Reference</div>
          <div class="row">
            <div class="col-md-3 form-group"><label>SAP Reference</label><input name="sap_reference" id="sap_reference" class="form-control" maxlength="60"></div>
            <div class="col-md-9 form-group"><label><?=hr_h('common_remarks', 'Remarks');?></label><textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=hr_h('common_cancel', 'Cancel');?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Training</button></div>
      </form>
    </div></div>
  </div>

  <div id="modal_tc_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:92%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Training Catalog Detail</h4></div><div class="modal-body" id="tc_detail_body"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=hr_h('common_close', 'Close');?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function tcMoney(n){n=parseFloat(n||0);return n.toLocaleString('id-ID',{maximumFractionDigits:0});}
function tcError(m){$('.isi_warning_delete').text(m||'Training Catalog gagal diproses.');$('.error_data_delete').fadeIn();}
function tcFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),training_category:$('#filter_training_category').val(),delivery_method:$('#filter_delivery_method').val(),training_type:$('#filter_training_type').val(),provider_type:$('#filter_provider_type').val(),owner_department_code:$('#filter_owner_department_code').val(),status:$('#filter_status').val(),keyword:$('#filter_keyword').val()};}
function tcSetSelect(selector,value,text){var el=$(selector);if(!value){el.val(null).trigger('change');return;}var exists=false;el.find('option').each(function(){if(this.value==value){exists=true;return false;}});if(!exists)el.append(new Option(text||value,value,true,true));el.val(value).trigger('change');}
function resetTcForm(title){$('#form_training_catalog')[0].reset();$('#tc_id').val('');$('#owner_department_code,#cost_center_code').val(null).trigger('change');$('#training_category,#delivery_method,#training_level,#training_type,#provider_type,#currency,#assessment_required,#certificate_required,#status').val('').trigger('change');$('#provider_type').val('INTERNAL').trigger('change');$('#currency').val('IDR').trigger('change');$('#assessment_required,#certificate_required').val('N').trigger('change');$('#status').val('DRAFT').trigger('change');$('#valid_from').val('<?=$defaultFrom;?>');$('#valid_to').val('<?=$defaultTo;?>');$('#cost_estimate,#max_participant,#validity_months').val('0');$('#modal_training_catalog .modal-title').text(title||'Create Training Catalog');}
function tcAjaxSelect(selector,act,placeholder){$(selector).select2({width:'100%',allowClear:true,placeholder:placeholder,dropdownParent:$('#modal_training_catalog'),ajax:{url:'<?=base_admin();?>modul/training_catalog/training_catalog_action.php?act='+act,type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
$(function(){
  if($.fn.datepicker){$('.tc-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_training_category,#filter_delivery_method,#filter_training_type,#filter_provider_type,#filter_status,#training_category,#delivery_method,#training_level,#training_type,#provider_type,#currency,#assessment_required,#certificate_required,#status').select2({width:'100%',allowClear:true});
    $('#filter_owner_department_code').select2({width:'100%',allowClear:true,placeholder:'Semua Department',ajax:{url:'<?=base_admin();?>modul/training_catalog/training_catalog_action.php?act=department_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
    tcAjaxSelect('#owner_department_code','department_search','Pilih Owner Department...');
    tcAjaxSelect('#cost_center_code','cost_center_search','Pilih Cost Center...');
  }
  var dt=$('#dtb_training_catalog').DataTable({bProcessing:true,bServerSide:true,pageLength:25,order:[[2,'asc']],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{width:'42px',targets:0},{width:'160px',targets:1}],ajax:{url:'<?=base_admin();?>modul/training_catalog/training_catalog_data.php',type:'post',data:function(d){$.extend(d,tcFilters());},dataSrc:function(json){var k=json.kpi||{};$('#kpi_total').text(k.total||0);$('#kpi_active').text(k.active||0);$('#kpi_mandatory').text(k.mandatory||0);$('#kpi_certification').text(k.certification||0);$('#kpi_avg_duration').text((k.avg_duration||0)+' jam');$('#kpi_total_cost').text(tcMoney(k.total_cost||0));return json.data||[];},error:function(xhr){console.log(xhr.responseText);tcError('Data Training Catalog gagal dimuat.');}}});
  $('#btn_open_tc').click(function(){resetTcForm('Create Training Catalog');$('#modal_training_catalog').modal({backdrop:'static',keyboard:false});});
  $('#btn_filter_tc').click(function(){dt.draw();});
  $('#filter_keyword').keyup(function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_tc').click(function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_training_category,#filter_delivery_method,#filter_training_type,#filter_provider_type,#filter_owner_department_code,#filter_status').val(null).trigger('change');$('#filter_keyword').val('');dt.draw();});
  $('#btn_export_tc').click(function(){var q=$.param(tcFilters());window.location='<?=base_admin();?>modul/training_catalog/training_catalog_action.php?act=export&'+q;});
  $('.hide_alert_notif').click(function(){$('.error_data_delete').hide();});
  $('#training_code').on('keyup change',function(){this.value=this.value.toUpperCase();});
  $('#assessment_required').on('change',function(){if($(this).val()==='Y')$('#passing_score').attr('required',true);else $('#passing_score').removeAttr('required');});
  $('#form_training_catalog').submit(function(e){e.preventDefault();var btn=$(this).find('button[type=submit]');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=hr_h('common_saving', 'Saving...');?>');$.post('<?=base_admin();?>modul/training_catalog/training_catalog_action.php?act=save',$(this).serialize(),function(r){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Training');if(r.status==='good'){$('#modal_training_catalog').modal('hide');dt.draw(false);Swal.fire('Saved','Training Catalog berhasil disimpan.','success');}else tcError(r.error_message);},'json').fail(function(xhr){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Training');tcError(xhr.responseText);});});
  $(document).on('click','.btn-tc-detail',function(){$.post('<?=base_admin();?>modul/training_catalog/training_catalog_action.php?act=detail',{id:$(this).data('id')},function(html){$('#tc_detail_body').html(html);$('#modal_tc_detail').modal('show');});});
  $(document).on('click','.btn-tc-edit',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/training_catalog/training_catalog_action.php?act=get',{id:id},function(r){if(r.status!=='good'){tcError(r.error_message);return;}resetTcForm('Edit Training Catalog');var h=r.data||{};$('#tc_id').val(h.id);$('#training_code').val(h.training_code);$('#training_name').val(h.training_name);$('#training_category').val(h.training_category).trigger('change');$('#delivery_method').val(h.delivery_method).trigger('change');$('#training_level').val(h.training_level).trigger('change');$('#training_type').val(h.training_type).trigger('change');$('#provider_type').val(h.provider_type).trigger('change');$('#provider_name').val(h.provider_name);$('#duration_hours').val(h.duration_hours);$('#validity_months').val(h.validity_months);$('#target_audience').val(h.target_audience);$('#competency_area').val(h.competency_area);$('#prerequisite').val(h.prerequisite);$('#learning_objective').val(h.learning_objective);$('#syllabus').val(h.syllabus);$('#assessment_required').val(h.assessment_required).trigger('change');$('#passing_score').val(h.passing_score);$('#certificate_required').val(h.certificate_required).trigger('change');$('#cost_estimate').val(h.cost_estimate);$('#currency').val(h.currency).trigger('change');$('#max_participant').val(h.max_participant);tcSetSelect('#owner_department_code',h.owner_department_code,h.department_text);tcSetSelect('#cost_center_code',h.cost_center_code,h.cost_center_text);$('#sap_reference').val(h.sap_reference);$('#status').val(h.status).trigger('change');$('#valid_from').val(h.valid_from);$('#valid_to').val(h.valid_to);$('#remarks').val(h.remarks);$('#modal_training_catalog').modal({backdrop:'static',keyboard:false});},'json');});
  $(document).on('click','.btn-tc-status',function(){var id=$(this).data('id'),status=$(this).data('status');$.post('<?=base_admin();?>modul/training_catalog/training_catalog_action.php?act=status',{id:id,status:status},function(r){if(r.status==='good'){dt.draw(false);Swal.fire('Updated','Status Training Catalog berhasil diubah.','success');}else tcError(r.error_message);},'json');});
  $(document).on('click','.btn-tc-delete',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:erpLang('confirm_delete_title','Delete Confirmation'),text:no,icon:'warning',showCancelButton:true,confirmButtonText:erpLang('common_delete','Delete'),cancelButtonText:erpLang('common_cancel','Cancel')}).then(function(x){if(!x.isConfirmed)return;$.post('<?=base_admin();?>modul/training_catalog/training_catalog_action.php?act=delete',{id:id},function(r){if(r.status==='good'){dt.draw(false);Swal.fire(erpLang('common_deleted','Deleted'),erpLang('common_deleted_message','Data deleted successfully.'),'success');}else tcError(r.error_message);},'json').fail(function(xhr){tcError(xhr.responseText);});});});
});
</script>
