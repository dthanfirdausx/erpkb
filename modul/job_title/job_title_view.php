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
$defaultTo='9999-12-31';
$canInsert=isset($role_act['insert_act']) && $role_act['insert_act']==='Y';
?>
<style>
.jt-hero{background:linear-gradient(135deg,#4338ca,#0f766e);color:#fff;border-radius:14px;padding:20px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.16)}
.jt-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.jt-hero p{margin:0;opacity:.92}
.jt-card{border-radius:12px;background:#fff;border:1px solid #e5edf5;box-shadow:0 5px 16px rgba(15,23,42,.05);margin-bottom:14px}
.jt-kpi{padding:14px;border-radius:12px;background:#f8fafc;border:1px solid #e5edf5}.jt-kpi b{font-size:22px;display:block}
#dtb_job_title th,#dtb_job_title td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}
.required-label:after{content:' *';color:#dd4b39}.jt-action{white-space:nowrap}.jt-pill{display:inline-block;border-radius:999px;padding:3px 9px;background:#eef2ff;color:#3730a3;font-weight:700;font-size:11px}
#modal_job_title .modal-body{max-height:calc(100vh - 185px);overflow-y:auto}.jt-note{color:#64748b;font-size:12px;margin-top:5px}
</style>
<section class="content-header">
  <h1><?=hr_h('hr_job_title', 'Job Title');?> <small>SAP HR Job Master</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li><li>Human Resource</li><li>Organization Management</li><li class="active"><?=hr_h('hr_job_title', 'Job Title');?></li></ol>
</section>
<section class="content">
  <div class="jt-hero">
    <div class="row">
      <div class="col-md-8"><h1>Job Title Workbench</h1><p>Kelola job title untuk job catalog, position planning, employee assignment, pay grade, approval, dan organization reporting.</p></div>
      <div class="col-md-4 text-right"><?php if($canInsert){ ?><button id="btn_open_jt" class="btn btn-warning"><i class="fa fa-plus"></i> Create Job Title</button><?php } ?></div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-3"><div class="jt-kpi"><span>Total Job Title</span><b id="kpi_total">0</b></div></div>
    <div class="col-sm-3"><div class="jt-kpi"><span><?=hr_h('hr_active', 'Active');?></span><b id="kpi_active">0</b></div></div>
    <div class="col-sm-3"><div class="jt-kpi"><span>Leadership</span><b id="kpi_leadership">0</b></div></div>
    <div class="col-sm-3"><div class="jt-kpi"><span>Headcount Plan</span><b id="kpi_headcount">0</b></div></div>
  </div>

  <div class="box jt-card">
    <div class="box-header"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Job Title</h3></div>
    <div class="box-body">
      <form class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2">Validity</label>
          <div class="col-lg-2"><div class="input-group date jt-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date jt-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1">Family</label>
          <div class="col-lg-2"><select id="filter_job_family" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>EXECUTIVE</option><option>MANAGEMENT</option><option>PROFESSIONAL</option><option>SUPERVISOR</option><option>STAFF</option><option>OPERATOR</option><option>TECHNICIAN</option><option>ADMINISTRATION</option><option>SALES</option><option>QUALITY</option><option>WAREHOUSE</option><option>PRODUCTION</option><option>FINANCE</option><option>HR</option><option>IT</option><option>PROCUREMENT</option></select></div>
          <label class="control-label col-lg-1"><?=hr_h('common_status', 'Status');?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>DRAFT</option><option>ACTIVE</option><option>INACTIVE</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2">Level</label>
          <div class="col-lg-2"><select id="filter_job_level" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>L1</option><option>L2</option><option>L3</option><option>L4</option><option>L5</option><option>L6</option><option>L7</option><option>L8</option><option>L9</option><option>L10</option></select></div>
          <label class="control-label col-lg-1"><?=hr_h('hr_department', 'Department');?></label>
          <div class="col-lg-3"><select id="filter_department_code" class="form-control"></select></div>
          <label class="control-label col-lg-1"><?=hr_h('common_search', 'Search');?></label>
          <div class="col-lg-3"><input id="filter_keyword" class="form-control" placeholder="Code, title, grade, department, cost center"></div>
        </div>
        <div class="form-group"><div class="col-lg-offset-2 col-lg-10"><button id="btn_filter_jt" class="btn btn-primary"><i class="fa fa-filter"></i> <?=hr_h('common_filter', 'Filter');?></button> <button id="btn_reset_jt" class="btn btn-default"><i class="fa fa-refresh"></i> <?=hr_h('common_reset', 'Reset');?></button></div></div>
      </form>
    </div>
  </div>

  <div class="box jt-card">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div>
      <div class="table-responsive"><table id="dtb_job_title" class="table table-bordered table-striped" style="width:100%"><thead><tr><th><?=hr_h('common_no', 'No');?></th><th><?=hr_h('common_action', 'Action');?></th><th><?=hr_h('hr_job_title', 'Job Title');?></th><th>Family / Group</th><th>Level</th><th>Department / Org</th><th>Reports To</th><th>Cost/Profit</th><th>Headcount</th><th><?=hr_h('common_status', 'Status');?></th><th>Updated</th></tr></thead><tbody></tbody></table></div>
    </div>
  </div>

  <div id="modal_job_title" class="modal fade">
    <div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content">
      <form id="form_job_title">
        <input type="hidden" name="id" id="jt_id">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Job Title</h4></div>
        <div class="modal-body">
          <div class="alert alert-info"><strong>SAP hint:</strong> Job Title adalah katalog pekerjaan. Position nanti bisa memakai Job Title ini untuk assignment employee dan headcount planning.</div>
          <div class="row">
            <div class="col-md-2 form-group"><label class="required-label">Job Code</label><input name="job_title_code" id="job_title_code" class="form-control text-uppercase" required maxlength="20" placeholder="JT-FIN-MGR"></div>
            <div class="col-md-4 form-group"><label class="required-label">Job Title Name</label><input name="job_title_name" id="job_title_name" class="form-control" required maxlength="120" placeholder="Finance Manager"></div>
            <div class="col-md-3 form-group"><label>Short Name</label><input name="job_title_short_name" id="job_title_short_name" class="form-control" maxlength="60"></div>
            <div class="col-md-3 form-group"><label class="required-label">Job Family</label><select name="job_family" id="job_family" class="form-control" required><option value="">Pilih Family</option><option>EXECUTIVE</option><option>MANAGEMENT</option><option>PROFESSIONAL</option><option>SUPERVISOR</option><option>STAFF</option><option>OPERATOR</option><option>TECHNICIAN</option><option>ADMINISTRATION</option><option>SALES</option><option>QUALITY</option><option>WAREHOUSE</option><option>PRODUCTION</option><option>FINANCE</option><option>HR</option><option>IT</option><option>PROCUREMENT</option></select></div>
          </div>
          <div class="row">
            <div class="col-md-2 form-group"><label class="required-label">Job Level</label><select name="job_level" id="job_level" class="form-control" required><option value="">Level</option><option>L1</option><option>L2</option><option>L3</option><option>L4</option><option>L5</option><option>L6</option><option>L7</option><option>L8</option><option>L9</option><option>L10</option></select></div>
            <div class="col-md-3 form-group"><label class="required-label">Employee Group</label><select name="employee_group" id="employee_group" class="form-control" required><option value="">Pilih Group</option><option>DIRECTOR</option><option>MANAGER</option><option>STAFF</option><option>NON_STAFF</option><option>OPERATOR</option><option>CONTRACT</option><option>DAILY_WORKER</option><option>TRAINEE</option></select></div>
            <div class="col-md-3 form-group"><label>Employee Subgroup</label><select name="employee_subgroup" id="employee_subgroup" class="form-control"><option value="">Pilih Subgroup</option><option>PERMANENT_MONTHLY</option><option>PERMANENT_DAILY</option><option>CONTRACT_MONTHLY</option><option>CONTRACT_DAILY</option><option>PROBATION</option><option>INTERNSHIP</option><option>OUTSOURCED</option></select></div>
            <div class="col-md-2 form-group"><label>Pay Grade</label><select name="pay_grade" id="pay_grade" class="form-control"><option value="">Pilih Grade</option><option>G01</option><option>G02</option><option>G03</option><option>G04</option><option>G05</option><option>G06</option><option>G07</option><option>G08</option><option>G09</option><option>G10</option><option>M01</option><option>M02</option><option>M03</option><option>DIR</option></select></div>
            <div class="col-md-2 form-group"><label>Headcount Plan</label><input name="headcount_plan" id="headcount_plan" class="form-control text-right" type="number" min="0" value="0"></div>
          </div>
          <div class="row">
            <div class="col-md-3 form-group"><label><?=hr_h('hr_department', 'Department');?></label><select name="department_code" id="department_code" class="form-control"></select></div>
            <div class="col-md-3 form-group"><label>Company Structure / Org Unit</label><select name="company_structure_id" id="company_structure_id" class="form-control"></select></div>
            <div class="col-md-3 form-group"><label>Reports To Job Title</label><select name="reports_to_job_title_id" id="reports_to_job_title_id" class="form-control"></select></div>
            <div class="col-md-3 form-group"><label>Work Location Type</label><select name="work_location_type" id="work_location_type" class="form-control"><option>OFFICE</option><option>PLANT</option><option>WAREHOUSE</option><option>FIELD</option><option>REMOTE</option><option>HYBRID</option></select></div>
          </div>
          <div class="row">
            <div class="col-md-3 form-group"><label>Cost Center</label><select name="cost_center_code" id="cost_center_code" class="form-control"></select></div>
            <div class="col-md-3 form-group"><label>Profit Center</label><select name="profit_center_code" id="profit_center_code" class="form-control"></select></div>
            <div class="col-md-3 form-group"><label>Minimum Education</label><select name="minimum_education" id="minimum_education" class="form-control"><option value="">Pilih Education</option><option>SMA/SMK</option><option>D1-D3</option><option>S1</option><option>S2</option><option>S3</option><option>Professional Certification</option></select></div>
            <div class="col-md-3 form-group"><label>Competency Profile</label><select name="competency_profile" id="competency_profile" class="form-control"><option value="">Pilih Profile</option><option>Leadership</option><option>Finance Accounting</option><option>HR Operation</option><option>Production Operation</option><option>Warehouse Management</option><option>Quality Management</option><option>Procurement</option><option>Sales Distribution</option><option>Customs Compliance</option><option>IT Support</option><option>Maintenance Engineering</option></select></div>
          </div>
          <div class="row">
            <div class="col-md-2 form-group"><label class="required-label">Valid From</label><input name="valid_from" id="valid_from" class="form-control date-field" value="<?=$defaultFrom;?>" required></div>
            <div class="col-md-2 form-group"><label class="required-label">Valid To</label><input name="valid_to" id="valid_to" class="form-control date-field" value="<?=$defaultTo;?>" required></div>
            <div class="col-md-2 form-group"><label><?=hr_h('common_status', 'Status');?></label><select name="status" id="status" class="form-control"><option>DRAFT</option><option>ACTIVE</option><option>INACTIVE</option></select></div>
            <div class="col-md-3 form-group"><label>SAP Reference</label><input name="sap_reference" id="sap_reference" class="form-control" maxlength="50"></div>
            <div class="col-md-3 form-group"><label><?=hr_h('common_remarks', 'Remarks');?></label><input name="remarks" id="remarks" class="form-control" maxlength="255"></div>
          </div>
          <div class="row">
            <div class="col-md-4 form-group"><label>Job Purpose</label><textarea name="job_purpose" id="job_purpose" class="form-control" rows="4"></textarea></div>
            <div class="col-md-4 form-group"><label>Key Responsibility</label><textarea name="key_responsibility" id="key_responsibility" class="form-control" rows="4"></textarea></div>
            <div class="col-md-4 form-group"><label>Authority Limit</label><textarea name="authority_limit" id="authority_limit" class="form-control" rows="4"></textarea></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=hr_h('common_cancel', 'Cancel');?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Job Title</button></div>
      </form>
    </div></div>
  </div>

  <div id="modal_jt_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:92%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Job Title Detail</h4></div><div class="modal-body" id="jt_detail_body"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=hr_h('common_close', 'Close');?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function jtError(m){$('.isi_warning_delete').text(m||'Job Title gagal diproses.');$('.error_data_delete').fadeIn();}
function jtFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),job_family:$('#filter_job_family').val(),job_level:$('#filter_job_level').val(),department_code:$('#filter_department_code').val(),status:$('#filter_status').val(),keyword:$('#filter_keyword').val()};}
function jtSetSelect(selector,value,text){var el=$(selector);if(!value){el.val(null).trigger('change');return;}var exists=false;el.find('option').each(function(){if(this.value==value){exists=true;return false;}});if(!exists)el.append(new Option(text||value,value,true,true));el.val(value).trigger('change');}
function resetJtForm(title){$('#form_job_title')[0].reset();$('#jt_id').val('');$('#department_code,#company_structure_id,#reports_to_job_title_id,#cost_center_code,#profit_center_code').val(null).trigger('change');$('#job_family,#job_level,#employee_group,#employee_subgroup,#pay_grade,#work_location_type,#minimum_education,#competency_profile,#status').val('').trigger('change');$('#work_location_type').val('OFFICE').trigger('change');$('#status').val('DRAFT').trigger('change');$('#valid_from').val('<?=$defaultFrom;?>');$('#valid_to').val('<?=$defaultTo;?>');$('#headcount_plan').val('0');$('#modal_job_title .modal-title').text(title||'Create Job Title');}
function jtAjaxSelect(selector,act,placeholder){$(selector).select2({width:'100%',allowClear:true,placeholder:placeholder,dropdownParent:$('#modal_job_title'),ajax:{url:'<?=base_admin();?>modul/job_title/job_title_action.php?act='+act,type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||'',exclude:$('#jt_id').val()};},processResults:function(d){return{results:d.results||[]};}}});}
$(function(){
  if($.fn.datepicker){$('.jt-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_job_family,#filter_job_level,#filter_status,#job_family,#job_level,#employee_group,#employee_subgroup,#pay_grade,#work_location_type,#minimum_education,#competency_profile,#status').select2({width:'100%',allowClear:true});
    $('#filter_department_code').select2({width:'100%',allowClear:true,placeholder:'Semua Department',ajax:{url:'<?=base_admin();?>modul/job_title/job_title_action.php?act=department_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
    jtAjaxSelect('#department_code','department_search','Pilih Department...');
    jtAjaxSelect('#company_structure_id','company_structure_search','Pilih Org Unit...');
    jtAjaxSelect('#reports_to_job_title_id','job_title_search','Pilih Reports To...');
    jtAjaxSelect('#cost_center_code','cost_center_search','Pilih Cost Center...');
    jtAjaxSelect('#profit_center_code','profit_center_search','Pilih Profit Center...');
  }
  var dt=$('#dtb_job_title').DataTable({bProcessing:true,bServerSide:true,pageLength:25,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=hr_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{width:'42px',targets:0},{width:'155px',targets:1}],ajax:{url:'<?=base_admin();?>modul/job_title/job_title_data.php',type:'post',data:function(d){$.extend(d,jtFilters());},dataSrc:function(json){var k=json.kpi||{};$('#kpi_total').text(k.total||0);$('#kpi_active').text(k.active||0);$('#kpi_leadership').text(k.leadership||0);$('#kpi_headcount').text(k.headcount_plan||0);return json.data||[];},error:function(xhr){console.log(xhr.responseText);jtError('Data Job Title gagal dimuat.');}}});
  $('#btn_open_jt').click(function(){resetJtForm('Create Job Title');$('#modal_job_title').modal({backdrop:'static',keyboard:false});});
  $('#btn_filter_jt').click(function(){dt.draw();});
  $('#filter_keyword').keyup(function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_jt').click(function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_job_family,#filter_job_level,#filter_status,#filter_department_code').val(null).trigger('change');$('#filter_keyword').val('');dt.draw();});
  $('.hide_alert_notif').click(function(){$('.error_data_delete').hide();});
  $('#job_title_code').on('keyup change',function(){this.value=this.value.toUpperCase();});
  $('#form_job_title').submit(function(e){e.preventDefault();var btn=$(this).find('button[type=submit]');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=hr_h('common_saving', 'Saving...');?>');$.post('<?=base_admin();?>modul/job_title/job_title_action.php?act=save',$(this).serialize(),function(r){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Job Title');if(r.status==='good'){$('#modal_job_title').modal('hide');dt.draw(false);Swal.fire('Saved','Job Title berhasil disimpan.','success');}else jtError(r.error_message);},'json').fail(function(xhr){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Job Title');jtError(xhr.responseText);});});
  $(document).on('click','.btn-jt-detail',function(){$.post('<?=base_admin();?>modul/job_title/job_title_action.php?act=detail',{id:$(this).data('id')},function(html){$('#jt_detail_body').html(html);$('#modal_jt_detail').modal('show');});});
  $(document).on('click','.btn-jt-edit',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/job_title/job_title_action.php?act=get',{id:id},function(r){if(r.status!=='good'){jtError(r.error_message);return;}resetJtForm('Edit Job Title');var h=r.data||{};$('#jt_id').val(h.id);$('#job_title_code').val(h.job_title_code);$('#job_title_name').val(h.job_title_name);$('#job_title_short_name').val(h.job_title_short_name);$('#job_family').val(h.job_family).trigger('change');$('#job_level').val(h.job_level).trigger('change');$('#employee_group').val(h.employee_group).trigger('change');$('#employee_subgroup').val(h.employee_subgroup).trigger('change');jtSetSelect('#department_code',h.department_code,h.department_text);jtSetSelect('#company_structure_id',h.company_structure_id,h.company_structure_text);jtSetSelect('#reports_to_job_title_id',h.reports_to_job_title_id,h.reports_to_text);jtSetSelect('#cost_center_code',h.cost_center_code,h.cost_center_text);jtSetSelect('#profit_center_code',h.profit_center_code,h.profit_center_text);$('#pay_grade').val(h.pay_grade).trigger('change');$('#work_location_type').val(h.work_location_type).trigger('change');$('#headcount_plan').val(h.headcount_plan);$('#minimum_education').val(h.minimum_education).trigger('change');$('#competency_profile').val(h.competency_profile).trigger('change');$('#valid_from').val(h.valid_from);$('#valid_to').val(h.valid_to);$('#status').val(h.status).trigger('change');$('#sap_reference').val(h.sap_reference);$('#remarks').val(h.remarks);$('#job_purpose').val(h.job_purpose);$('#key_responsibility').val(h.key_responsibility);$('#authority_limit').val(h.authority_limit);$('#modal_job_title').modal({backdrop:'static',keyboard:false});},'json');});
  $(document).on('click','.btn-jt-status',function(){var id=$(this).data('id'),status=$(this).data('status');$.post('<?=base_admin();?>modul/job_title/job_title_action.php?act=status',{id:id,status:status},function(r){if(r.status==='good'){dt.draw(false);Swal.fire('Updated','Status Job Title berhasil diubah.','success');}else jtError(r.error_message);},'json');});
  $(document).on('click','.btn-jt-delete',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:erpLang('confirm_delete_title','Delete Confirmation'),text:no,icon:'warning',showCancelButton:true,confirmButtonText:erpLang('common_delete','Delete'),cancelButtonText:erpLang('common_cancel','Cancel')}).then(function(x){if(!x.isConfirmed)return;$.post('<?=base_admin();?>modul/job_title/job_title_action.php?act=delete',{id:id},function(r){if(r.status==='good'){dt.draw(false);Swal.fire(erpLang('common_deleted','Deleted'),erpLang('common_deleted_message','Data deleted successfully.'),'success');}else jtError(r.error_message);},'json').fail(function(xhr){jtError(xhr.responseText);});});});
});
</script>
