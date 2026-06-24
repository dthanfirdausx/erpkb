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
.pos-hero{background:linear-gradient(135deg,#0f766e,#2563eb);color:#fff;border-radius:14px;padding:20px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.16)}
.pos-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.pos-hero p{margin:0;opacity:.93}
.pos-card{border-radius:12px;background:#fff;border:1px solid #e5edf5;box-shadow:0 5px 16px rgba(15,23,42,.05);margin-bottom:14px}
.pos-kpi{padding:14px;border-radius:12px;background:#f8fafc;border:1px solid #e5edf5}.pos-kpi b{font-size:22px;display:block}
#dtb_position th,#dtb_position td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}
.required-label:after{content:' *';color:#dd4b39}.pos-action{white-space:nowrap}.pos-pill{display:inline-block;border-radius:999px;padding:3px 9px;background:#ecfeff;color:#155e75;font-weight:700;font-size:11px}
#modal_position .modal-body{max-height:calc(100vh - 185px);overflow-y:auto}.pos-note{color:#64748b;font-size:12px;margin-top:5px}
</style>
<section class="content-header">
  <h1><?=hr_h('hr_position', 'Position');?> <small>SAP HR Position Management</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li><li>Human Resource</li><li>Organization Management</li><li class="active"><?=hr_h('hr_position', 'Position');?></li></ol>
</section>
<section class="content">
  <div class="pos-hero">
    <div class="row">
      <div class="col-md-8"><h1>Position Workbench</h1><p>Kelola position sebagai slot organisasi: holder employee, reporting line, FTE, vacancy, cost center, profit center, work location, dan validity.</p></div>
      <div class="col-md-4 text-right"><?php if($canInsert){ ?><button id="btn_open_pos" class="btn btn-warning"><i class="fa fa-plus"></i> Create Position</button><?php } ?> <button id="btn_export_pos" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=hr_h('common_export_excel', 'Export Excel');?></button></div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-2"><div class="pos-kpi"><span>Total</span><b id="kpi_total">0</b></div></div>
    <div class="col-sm-2"><div class="pos-kpi"><span><?=hr_h('hr_active', 'Active');?></span><b id="kpi_active">0</b></div></div>
    <div class="col-sm-2"><div class="pos-kpi"><span>Occupied</span><b id="kpi_occupied">0</b></div></div>
    <div class="col-sm-2"><div class="pos-kpi"><span>Vacant</span><b id="kpi_vacant">0</b></div></div>
    <div class="col-sm-2"><div class="pos-kpi"><span>Plan FTE</span><b id="kpi_planned_fte">0.00</b></div></div>
    <div class="col-sm-2"><div class="pos-kpi"><span>Occ FTE</span><b id="kpi_occupied_fte">0.00</b></div></div>
  </div>

  <div class="box pos-card">
    <div class="box-header"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Position</h3></div>
    <div class="box-body">
      <form class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2">Validity</label>
          <div class="col-lg-2"><div class="input-group date pos-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date pos-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1">Type</label>
          <div class="col-lg-2"><select id="filter_position_type" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>STRUCTURAL</option><option>FUNCTIONAL</option><option>OPERATIONAL</option><option>PROJECT</option><option>TEMPORARY</option></select></div>
          <label class="control-label col-lg-1"><?=hr_h('common_status', 'Status');?></label>
          <div class="col-lg-2"><select id="filter_position_status" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>PLANNED</option><option>APPROVED</option><option>ACTIVE</option><option>INACTIVE</option><option>OBSOLETE</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2">Vacancy</label>
          <div class="col-lg-2"><select id="filter_vacancy_status" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>VACANT</option><option>OCCUPIED</option><option>PARTIAL</option><option>OVERSTAFFED</option><option>FROZEN</option></select></div>
          <label class="control-label col-lg-1"><?=hr_h('hr_department', 'Department');?></label>
          <div class="col-lg-3"><select id="filter_department_code" class="form-control"></select></div>
          <label class="control-label col-lg-1"><?=hr_h('common_search', 'Search');?></label>
          <div class="col-lg-3"><input id="filter_keyword" class="form-control" placeholder="Position, job title, employee, department"></div>
        </div>
        <div class="form-group"><div class="col-lg-offset-2 col-lg-10"><button id="btn_filter_pos" class="btn btn-primary"><i class="fa fa-filter"></i> <?=hr_h('common_filter', 'Filter');?></button> <button id="btn_reset_pos" class="btn btn-default"><i class="fa fa-refresh"></i> <?=hr_h('common_reset', 'Reset');?></button></div></div>
      </form>
    </div>
  </div>

  <div class="box pos-card">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div>
      <div class="table-responsive"><table id="dtb_position" class="table table-bordered table-striped" style="width:100%"><thead><tr><th><?=hr_h('common_no', 'No');?></th><th><?=hr_h('common_action', 'Action');?></th><th><?=hr_h('hr_position', 'Position');?></th><th><?=hr_h('hr_job_title', 'Job Title');?></th><th>Department / Org</th><th>Reports To</th><th>Holder</th><th>Vacancy</th><th><?=hr_h('common_status', 'Status');?></th><th>FTE</th><th>Cost / Profit</th><th>Updated</th></tr></thead><tbody></tbody></table></div>
    </div>
  </div>

  <div id="modal_position" class="modal fade">
    <div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content">
      <form id="form_position">
        <input type="hidden" name="id" id="pos_id">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Position</h4></div>
        <div class="modal-body">
          <div class="alert alert-info"><strong>SAP hint:</strong> Position adalah object organisasi yang ditempati employee. Satu Job Title bisa punya banyak Position, misalnya beberapa Operator Mixing.</div>
          <div class="row">
            <div class="col-md-2 form-group"><label class="required-label">Position Code</label><input name="position_code" id="position_code" class="form-control text-uppercase" required maxlength="30" placeholder="POS-FIN-001"></div>
            <div class="col-md-4 form-group"><label class="required-label">Position Name</label><input name="position_name" id="position_name" class="form-control" required maxlength="150" placeholder="Finance Manager Position"></div>
            <div class="col-md-3 form-group"><label>Short Name</label><input name="position_short_name" id="position_short_name" class="form-control" maxlength="80"></div>
            <div class="col-md-3 form-group"><label class="required-label"><?=hr_h('hr_job_title', 'Job Title');?></label><select name="job_title_id" id="job_title_id" class="form-control"></select></div>
          </div>
          <div class="row">
            <div class="col-md-2 form-group"><label class="required-label">Type</label><select name="position_type" id="position_type" class="form-control" required><option>STRUCTURAL</option><option>FUNCTIONAL</option><option>OPERATIONAL</option><option>PROJECT</option><option>TEMPORARY</option></select></div>
            <div class="col-md-2 form-group"><label class="required-label">Category</label><select name="position_category" id="position_category" class="form-control" required><option>REGULAR</option><option>KEY_POSITION</option><option>CRITICAL</option><option>SUCCESSION</option><option>APPRENTICE</option></select></div>
            <div class="col-md-2 form-group"><label class="required-label">Employee Group</label><select name="employee_group" id="employee_group" class="form-control" required><option>DIRECTOR</option><option>MANAGER</option><option selected>STAFF</option><option>NON_STAFF</option><option>OPERATOR</option><option>CONTRACT</option><option>DAILY_WORKER</option><option>TRAINEE</option></select></div>
            <div class="col-md-2 form-group"><label>Pay Grade</label><select name="pay_grade" id="pay_grade" class="form-control"><option value="">Pilih Grade</option><option>G01</option><option>G02</option><option>G03</option><option>G04</option><option>G05</option><option>G06</option><option>G07</option><option>G08</option><option>G09</option><option>G10</option><option>M01</option><option>M02</option><option>M03</option><option>DIR</option></select></div>
            <div class="col-md-2 form-group"><label>Planned FTE</label><input name="planned_fte" id="planned_fte" class="form-control text-right" type="number" step="0.01" min="0.01" value="1.00"></div>
            <div class="col-md-2 form-group"><label>Occupied FTE</label><input name="occupied_fte" id="occupied_fte" class="form-control text-right" type="number" step="0.01" min="0" value="0.00"></div>
          </div>
          <div class="row">
            <div class="col-md-3 form-group"><label><?=hr_h('hr_department', 'Department');?></label><select name="department_code" id="department_code" class="form-control"></select></div>
            <div class="col-md-3 form-group"><label>Company Structure / Org Unit</label><select name="company_structure_id" id="company_structure_id" class="form-control"></select></div>
            <div class="col-md-3 form-group"><label>Reports To Position</label><select name="reports_to_position_id" id="reports_to_position_id" class="form-control"></select></div>
            <div class="col-md-3 form-group"><label>Holder Employee</label><select name="holder_employee_id" id="holder_employee_id" class="form-control"></select><div class="pos-note">Kosongkan jika position masih vacant.</div></div>
          </div>
          <div class="row">
            <div class="col-md-3 form-group"><label>Cost Center</label><select name="cost_center_code" id="cost_center_code" class="form-control"></select></div>
            <div class="col-md-3 form-group"><label>Profit Center</label><select name="profit_center_code" id="profit_center_code" class="form-control"></select></div>
            <div class="col-md-3 form-group"><label><?=hr_h('hr_work_location', 'Work Location');?></label><select name="work_location_id" id="work_location_id" class="form-control"></select></div>
            <div class="col-md-1 form-group"><label>Headcount</label><input name="headcount_plan" id="headcount_plan" class="form-control text-right" type="number" min="1" value="1"></div>
            <div class="col-md-2 form-group"><label>Vacancy</label><select name="vacancy_status" id="vacancy_status" class="form-control"><option>VACANT</option><option>OCCUPIED</option><option>PARTIAL</option><option>OVERSTAFFED</option><option>FROZEN</option></select></div>
          </div>
          <div class="row">
            <div class="col-md-2 form-group"><label class="required-label">Valid From</label><input name="valid_from" id="valid_from" class="form-control date-field" value="<?=$defaultFrom;?>" required></div>
            <div class="col-md-2 form-group"><label class="required-label">Valid To</label><input name="valid_to" id="valid_to" class="form-control date-field" value="<?=$defaultTo;?>" required></div>
            <div class="col-md-2 form-group"><label><?=hr_h('common_status', 'Status');?></label><select name="position_status" id="position_status" class="form-control"><option>PLANNED</option><option>APPROVED</option><option>ACTIVE</option><option>INACTIVE</option><option>OBSOLETE</option></select></div>
            <div class="col-md-3 form-group"><label>SAP Reference</label><input name="sap_reference" id="sap_reference" class="form-control" maxlength="50"></div>
            <div class="col-md-3 form-group"><label><?=hr_h('common_remarks', 'Remarks');?></label><input name="remarks" id="remarks" class="form-control" maxlength="255"></div>
          </div>
          <div class="row">
            <div class="col-md-3 form-group"><label>Job Description</label><textarea name="job_description" id="job_description" class="form-control" rows="4"></textarea></div>
            <div class="col-md-3 form-group"><label>Qualification Requirement</label><textarea name="qualification_requirement" id="qualification_requirement" class="form-control" rows="4"></textarea></div>
            <div class="col-md-3 form-group"><label>Authority Limit</label><textarea name="authority_limit" id="authority_limit" class="form-control" rows="4"></textarea></div>
            <div class="col-md-3 form-group"><label>Succession Plan Note</label><textarea name="succession_plan_note" id="succession_plan_note" class="form-control" rows="4"></textarea></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=hr_h('common_cancel', 'Cancel');?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Position</button></div>
      </form>
    </div></div>
  </div>

  <div id="modal_pos_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:92%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Position Detail</h4></div><div class="modal-body" id="pos_detail_body"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=hr_h('common_close', 'Close');?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function posError(m){$('.isi_warning_delete').text(m||'Position gagal diproses.');$('.error_data_delete').fadeIn();}
function posFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),position_type:$('#filter_position_type').val(),position_status:$('#filter_position_status').val(),vacancy_status:$('#filter_vacancy_status').val(),department_code:$('#filter_department_code').val(),keyword:$('#filter_keyword').val()};}
function posSetSelect(selector,value,text){var el=$(selector);if(!value){el.val(null).trigger('change');return;}var exists=false;el.find('option').each(function(){if(this.value==value){exists=true;return false;}});if(!exists)el.append(new Option(text||value,value,true,true));el.val(value).trigger('change');}
function resetPosForm(title){$('#form_position')[0].reset();$('#pos_id').val('');$('#job_title_id,#department_code,#company_structure_id,#reports_to_position_id,#holder_employee_id,#cost_center_code,#profit_center_code,#work_location_id').val(null).trigger('change');$('#position_type').val('STRUCTURAL').trigger('change');$('#position_category').val('REGULAR').trigger('change');$('#employee_group').val('STAFF').trigger('change');$('#vacancy_status').val('VACANT').trigger('change');$('#position_status').val('PLANNED').trigger('change');$('#valid_from').val('<?=$defaultFrom;?>');$('#valid_to').val('<?=$defaultTo;?>');$('#planned_fte').val('1.00');$('#occupied_fte').val('0.00');$('#headcount_plan').val('1');$('#modal_position .modal-title').text(title||'Create Position');}
function posAjaxSelect(selector,act,placeholder){$(selector).select2({width:'100%',allowClear:true,placeholder:placeholder,dropdownParent:$('#modal_position'),ajax:{url:'<?=base_admin();?>modul/position/position_action.php?act='+act,type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||'',exclude:$('#pos_id').val()};},processResults:function(d){return{results:d.results||[]};}}});}
$(function(){
  if($.fn.datepicker){$('.pos-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_position_type,#filter_position_status,#filter_vacancy_status,#position_type,#position_category,#employee_group,#pay_grade,#vacancy_status,#position_status').select2({width:'100%',allowClear:true});
    $('#filter_department_code').select2({width:'100%',allowClear:true,placeholder:'Semua Department',ajax:{url:'<?=base_admin();?>modul/position/position_action.php?act=department_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
    posAjaxSelect('#job_title_id','job_title_search','Pilih Job Title...');
    posAjaxSelect('#department_code','department_search','Pilih Department...');
    posAjaxSelect('#company_structure_id','company_structure_search','Pilih Org Unit...');
    posAjaxSelect('#reports_to_position_id','position_search','Pilih Reports To...');
    posAjaxSelect('#holder_employee_id','employee_search','Pilih Holder Employee...');
    posAjaxSelect('#cost_center_code','cost_center_search','Pilih Cost Center...');
    posAjaxSelect('#profit_center_code','profit_center_search','Pilih Profit Center...');
    posAjaxSelect('#work_location_id','work_location_search','Pilih Work Location...');
  }
  var dt=$('#dtb_position').DataTable({bProcessing:true,bServerSide:true,pageLength:25,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=hr_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{width:'42px',targets:0},{width:'150px',targets:1}],ajax:{url:'<?=base_admin();?>modul/position/position_data.php',type:'post',data:function(d){$.extend(d,posFilters());},dataSrc:function(json){var k=json.kpi||{};$('#kpi_total').text(k.total||0);$('#kpi_active').text(k.active||0);$('#kpi_occupied').text(k.occupied||0);$('#kpi_vacant').text(k.vacant||0);$('#kpi_planned_fte').text(k.planned_fte||'0.00');$('#kpi_occupied_fte').text(k.occupied_fte||'0.00');return json.data||[];},error:function(xhr){console.log(xhr.responseText);posError('Data Position gagal dimuat.');}}});
  $('#btn_open_pos').click(function(){resetPosForm('Create Position');$('#modal_position').modal({backdrop:'static',keyboard:false});});
  $('#btn_filter_pos').click(function(){dt.draw();});
  $('#filter_keyword').keyup(function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_pos').click(function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_position_type,#filter_position_status,#filter_vacancy_status,#filter_department_code').val(null).trigger('change');$('#filter_keyword').val('');dt.draw();});
  $('#btn_export_pos').click(function(){var f=posFilters();var q=$.param(f);window.location='<?=base_admin();?>modul/position/position_action.php?act=export&'+q;});
  $('.hide_alert_notif').click(function(){$('.error_data_delete').hide();});
  $('#position_code').on('keyup change',function(){this.value=this.value.toUpperCase();});
  $('#holder_employee_id').on('change',function(){if($(this).val()){$('#vacancy_status').val('OCCUPIED').trigger('change');$('#occupied_fte').val($('#planned_fte').val()||'1.00');}else{$('#vacancy_status').val('VACANT').trigger('change');$('#occupied_fte').val('0.00');}});
  $('#form_position').submit(function(e){e.preventDefault();var btn=$(this).find('button[type=submit]');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=hr_h('common_saving', 'Saving...');?>');$.post('<?=base_admin();?>modul/position/position_action.php?act=save',$(this).serialize(),function(r){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Position');if(r.status==='good'){$('#modal_position').modal('hide');dt.draw(false);Swal.fire('Saved','Position berhasil disimpan.','success');}else posError(r.error_message);},'json').fail(function(xhr){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Position');posError(xhr.responseText);});});
  $(document).on('click','.btn-pos-detail',function(){$.post('<?=base_admin();?>modul/position/position_action.php?act=detail',{id:$(this).data('id')},function(html){$('#pos_detail_body').html(html);$('#modal_pos_detail').modal('show');});});
  $(document).on('click','.btn-pos-edit',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/position/position_action.php?act=get',{id:id},function(r){if(r.status!=='good'){posError(r.error_message);return;}resetPosForm('Edit Position');var h=r.data||{};$('#pos_id').val(h.id);$('#position_code').val(h.position_code);$('#position_name').val(h.position_name);$('#position_short_name').val(h.position_short_name);$('#position_type').val(h.position_type).trigger('change');$('#position_category').val(h.position_category).trigger('change');posSetSelect('#job_title_id',h.job_title_id,h.job_title_text);posSetSelect('#department_code',h.department_code,h.department_text);posSetSelect('#company_structure_id',h.company_structure_id,h.company_structure_text);posSetSelect('#reports_to_position_id',h.reports_to_position_id,h.reports_to_text);posSetSelect('#holder_employee_id',h.holder_employee_id,h.holder_text);posSetSelect('#cost_center_code',h.cost_center_code,h.cost_center_text);posSetSelect('#profit_center_code',h.profit_center_code,h.profit_center_text);posSetSelect('#work_location_id',h.work_location_id,h.work_location_text);$('#employee_group').val(h.employee_group).trigger('change');$('#pay_grade').val(h.pay_grade).trigger('change');$('#planned_fte').val(h.planned_fte);$('#occupied_fte').val(h.occupied_fte);$('#headcount_plan').val(h.headcount_plan);$('#vacancy_status').val(h.vacancy_status).trigger('change');$('#position_status').val(h.position_status).trigger('change');$('#valid_from').val(h.valid_from);$('#valid_to').val(h.valid_to);$('#sap_reference').val(h.sap_reference);$('#remarks').val(h.remarks);$('#job_description').val(h.job_description);$('#qualification_requirement').val(h.qualification_requirement);$('#authority_limit').val(h.authority_limit);$('#succession_plan_note').val(h.succession_plan_note);$('#modal_position').modal({backdrop:'static',keyboard:false});},'json');});
  $(document).on('click','.btn-pos-status',function(){var id=$(this).data('id'),status=$(this).data('status');$.post('<?=base_admin();?>modul/position/position_action.php?act=status',{id:id,status:status},function(r){if(r.status==='good'){dt.draw(false);Swal.fire('Updated','Status Position berhasil diubah.','success');}else posError(r.error_message);},'json');});
  $(document).on('click','.btn-pos-delete',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:erpLang('confirm_delete_title','Delete Confirmation'),text:no,icon:'warning',showCancelButton:true,confirmButtonText:erpLang('common_delete','Delete'),cancelButtonText:erpLang('common_cancel','Cancel')}).then(function(x){if(!x.isConfirmed)return;$.post('<?=base_admin();?>modul/position/position_action.php?act=delete',{id:id},function(r){if(r.status==='good'){dt.draw(false);Swal.fire(erpLang('common_deleted','Deleted'),erpLang('common_deleted_message','Data deleted successfully.'),'success');}else posError(r.error_message);},'json').fail(function(xhr){posError(xhr.responseText);});});});
});
</script>
