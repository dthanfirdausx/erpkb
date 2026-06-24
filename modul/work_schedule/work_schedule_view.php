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
$defaultFrom = date('Y-01-01');
$defaultTo = '9999-12-31';
$canInsert = isset($role_act['insert_act']) && $role_act['insert_act'] === 'Y';

if (!function_exists('ws_t')) {
    function ws_t($key, $fallback = '')
    {
        return lang_text($key, $fallback);
    }
}
if (!function_exists('ws_h')) {
    function ws_h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$wsJsLang = array(
    'all' => ws_t('common_all', 'All'),
    'close' => ws_t('common_close', 'Close'),
    'cancel' => ws_t('common_cancel', 'Cancel'),
    'delete' => ws_t('common_delete', 'Delete'),
    'saving' => ws_t('common_saving', 'Saving...'),
    'saved' => ws_t('common_saved', 'Saved'),
    'deleted' => ws_t('common_deleted', 'Deleted'),
    'success' => ws_t('common_success', 'Success'),
    'export_datatable' => ws_t('common_export_datatable', 'Export DataTable'),
    'error_default' => ws_t('work_schedule_error_default', 'Work Schedule failed to process.'),
    'load_failed' => ws_t('work_schedule_load_failed', 'Work Schedule data failed to load.'),
    'modal_create' => ws_t('work_schedule_modal_create', 'Create Work Schedule'),
    'modal_edit' => ws_t('work_schedule_modal_edit', 'Edit Work Schedule'),
    'save' => ws_t('work_schedule_save', 'Save Schedule'),
    'saved_message' => ws_t('work_schedule_saved_message', 'Work Schedule saved successfully.'),
    'status_message' => ws_t('work_schedule_status_message', 'Status updated successfully.'),
    'delete_title' => ws_t('work_schedule_delete_title', 'Delete Work Schedule?'),
    'deleted_message' => ws_t('work_schedule_deleted_message', 'Work Schedule deleted successfully.'),
    'all_department' => ws_t('common_all', 'All') . ' ' . ws_t('common_department', 'Department'),
    'select_calendar' => ws_t('work_schedule_factory_calendar', 'Factory Calendar'),
    'select_shift' => ws_t('work_schedule_default_shift', 'Default Shift'),
    'select_work_location' => ws_t('work_schedule_work_location', 'Work Location'),
    'select_department' => ws_t('common_department', 'Department'),
);
?>
<style>
.ws-hero{background:linear-gradient(135deg,#0f766e,#2563eb);color:#fff;border-radius:14px;padding:20px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.16)}
.ws-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.ws-hero p{margin:0;opacity:.94}
.ws-card{border-radius:12px;background:#fff;border:1px solid #e5edf5;box-shadow:0 5px 16px rgba(15,23,42,.05);margin-bottom:14px}
.ws-kpi{padding:14px;border-radius:12px;background:#f8fafc;border:1px solid #e5edf5}.ws-kpi b{font-size:22px;display:block}
#dtb_work_schedule th,#dtb_work_schedule td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}.required-label:after{content:' *';color:#dd4b39}
.ws-action{white-space:nowrap}#modal_work_schedule .modal-body{max-height:calc(100vh - 185px);overflow-y:auto}
.ws-day-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px;margin-bottom:10px}
.ws-section-title{font-weight:700;color:#334155;margin:8px 0 12px;border-bottom:1px solid #e5e7eb;padding-bottom:7px}
</style>

<section class="content-header">
  <h1><?=ws_h(ws_t('work_schedule_title', 'Work Schedule'));?> <small><?=ws_h(ws_t('work_schedule_subtitle', 'SAP HR Time Management'));?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=ws_h(ws_t('common_home', 'Home'));?></a></li>
    <li><?=ws_h(ws_t('work_schedule_hr', 'Human Resource'));?></li>
    <li><?=ws_h(ws_t('work_schedule_time_management', 'Time Management'));?></li>
    <li class="active"><?=ws_h(ws_t('work_schedule_title', 'Work Schedule'));?></li>
  </ol>
</section>

<section class="content">
  <div class="ws-hero">
    <div class="row">
      <div class="col-md-8">
        <h1><?=ws_h(ws_t('work_schedule_workbench', 'Work Schedule Workbench'));?></h1>
        <p><?=ws_h(ws_t('work_schedule_intro', 'Manage work schedules based on factory calendar, shift, work location, department, employee group, working days, working hours, grace, overtime, and attendance rules.'));?></p>
      </div>
      <div class="col-md-4 text-right">
        <?php if ($canInsert) { ?>
          <button id="btn_open_ws" class="btn btn-warning"><i class="fa fa-plus"></i> <?=ws_h(ws_t('work_schedule_create', 'Create Schedule'));?></button>
        <?php } ?>
        <button id="btn_export_ws" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=ws_h(ws_t('common_export_excel', 'Export Excel'));?></button>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-2"><div class="ws-kpi"><span><?=ws_h(ws_t('common_total', 'Total'));?></span><b id="kpi_total">0</b></div></div>
    <div class="col-sm-2"><div class="ws-kpi"><span><?=ws_h(ws_t('common_active', 'Active'));?></span><b id="kpi_active">0</b></div></div>
    <div class="col-sm-2"><div class="ws-kpi"><span><?=hr_h('hr_shift', 'Shift');?></span><b id="kpi_shift_schedule">0</b></div></div>
    <div class="col-sm-2"><div class="ws-kpi"><span>Production</span><b id="kpi_production">0</b></div></div>
    <div class="col-sm-2"><div class="ws-kpi"><span><?=ws_h(ws_t('work_schedule_avg_weekly_hours', 'Avg Weekly Hours'));?></span><b id="kpi_avg_weekly_hours">0</b></div></div>
    <div class="col-sm-2"><div class="ws-kpi"><span><?=ws_h(ws_t('common_today', 'Today'));?></span><b><?=date('d M');?></b></div></div>
  </div>

  <div class="box ws-card">
    <div class="box-header"><h3 class="box-title"><i class="fa fa-filter"></i> <?=ws_h(ws_t('work_schedule_filter_title', 'Filter Work Schedule'));?></h3></div>
    <div class="box-body">
      <form class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=ws_h(ws_t('work_schedule_validity', 'Validity'));?></label>
          <div class="col-lg-2"><div class="input-group date ws-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date ws-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=ws_h(ws_t('common_type', 'Type'));?></label>
          <div class="col-lg-2"><select id="filter_schedule_type" class="form-control"><option value=""><?=ws_h(ws_t('common_all', 'All'));?></option><option>FIXED</option><option>FLEXIBLE</option><option>SHIFT</option><option>ROTATION</option><option>REMOTE</option><option>PART_TIME</option></select></div>
          <label class="control-label col-lg-1"><?=ws_h(ws_t('common_category', 'Category'));?></label>
          <div class="col-lg-2"><select id="filter_schedule_category" class="form-control"><option value=""><?=ws_h(ws_t('common_all', 'All'));?></option><option>OFFICE</option><option>PRODUCTION</option><option>WAREHOUSE</option><option>SALES</option><option>SUPPORT</option><option>REMOTE</option><option>OTHER</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=ws_h(ws_t('common_department', 'Department'));?></label>
          <div class="col-lg-3"><select id="filter_department_code" class="form-control"></select></div>
          <label class="control-label col-lg-1"><?=ws_h(ws_t('common_status', 'Status'));?></label>
          <div class="col-lg-2"><select id="filter_schedule_status" class="form-control"><option value=""><?=ws_h(ws_t('common_all', 'All'));?></option><option>DRAFT</option><option>ACTIVE</option><option>INACTIVE</option></select></div>
          <div class="col-lg-4"><input id="filter_keyword" class="form-control" placeholder="<?=ws_h(ws_t('work_schedule_filter_keyword', 'Schedule code, name, location, SAP ref'));?>"></div>
        </div>
        <div class="form-group">
          <div class="col-lg-offset-2 col-lg-10">
            <button id="btn_filter_ws" class="btn btn-primary"><i class="fa fa-filter"></i> <?=ws_h(ws_t('common_filter', 'Filter'));?></button>
            <button id="btn_reset_ws" class="btn btn-default"><i class="fa fa-refresh"></i> <?=ws_h(ws_t('common_reset', 'Reset'));?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box ws-card">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_work_schedule" class="table table-bordered table-striped" style="width:100%">
          <thead>
            <tr>
              <th><?=hr_h('common_no', 'No');?></th>
              <th><?=ws_h(ws_t('common_action', 'Action'));?></th>
              <th><?=ws_h(ws_t('work_schedule_schedule', 'Schedule'));?></th>
              <th><?=ws_h(ws_t('work_schedule_type_category', 'Type / Category'));?></th>
              <th><?=ws_h(ws_t('work_schedule_calendar_shift', 'Calendar / Shift'));?></th>
              <th><?=ws_h(ws_t('work_schedule_work_location', 'Work Location'));?></th>
              <th><?=ws_h(ws_t('work_schedule_department_group', 'Department / Group'));?></th>
              <th><?=ws_h(ws_t('work_schedule_working_days', 'Working Days'));?></th>
              <th><?=ws_h(ws_t('work_schedule_hours_grace', 'Hours / Grace'));?></th>
              <th><?=ws_h(ws_t('common_status', 'Status'));?></th>
              <th><?=ws_h(ws_t('common_updated', 'Updated'));?></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_work_schedule" class="modal fade">
    <div class="modal-dialog modal-lg" style="width:94%">
      <div class="modal-content">
        <form id="form_work_schedule">
          <input type="hidden" name="id" id="ws_id">
          <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><?=ws_h(ws_t('work_schedule_modal_create', 'Create Work Schedule'));?></h4></div>
          <div class="modal-body">
            <div class="alert alert-info"><strong>SAP hint:</strong> <?=ws_h(ws_t('work_schedule_sap_hint', 'Work Schedule defines the work pattern used by attendance, overtime, leave, shift schedule, and manpower capacity planning.'));?></div>
            <div class="ws-section-title"><?=ws_h(ws_t('work_schedule_basic_data', 'Basic Data'));?></div>
            <div class="row">
              <div class="col-md-2 form-group"><label class="required-label"><?=ws_h(ws_t('work_schedule_schedule_code', 'Schedule Code'));?></label><input name="schedule_code" id="schedule_code" class="form-control text-uppercase" required maxlength="30" placeholder="WS-..."></div>
              <div class="col-md-4 form-group"><label class="required-label"><?=ws_h(ws_t('work_schedule_schedule_name', 'Schedule Name'));?></label><input name="schedule_name" id="schedule_name" class="form-control" required maxlength="150"></div>
              <div class="col-md-2 form-group"><label><?=ws_h(ws_t('common_type', 'Type'));?></label><select name="schedule_type" id="schedule_type" class="form-control"><option>FIXED</option><option>FLEXIBLE</option><option>SHIFT</option><option>ROTATION</option><option>REMOTE</option><option>PART_TIME</option></select></div>
              <div class="col-md-2 form-group"><label><?=ws_h(ws_t('common_category', 'Category'));?></label><select name="schedule_category" id="schedule_category" class="form-control"><option>OFFICE</option><option>PRODUCTION</option><option>WAREHOUSE</option><option>SALES</option><option>SUPPORT</option><option>REMOTE</option><option>OTHER</option></select></div>
              <div class="col-md-2 form-group"><label><?=ws_h(ws_t('common_status', 'Status'));?></label><select name="schedule_status" id="schedule_status" class="form-control"><option>DRAFT</option><option>ACTIVE</option><option>INACTIVE</option></select></div>
            </div>
            <div class="ws-section-title"><?=ws_h(ws_t('work_schedule_org_assignment', 'Organization Assignment'));?></div>
            <div class="row">
              <div class="col-md-3 form-group"><label><?=ws_h(ws_t('work_schedule_factory_calendar', 'Factory Calendar'));?></label><select name="calendar_id" id="calendar_id" class="form-control"></select></div>
              <div class="col-md-3 form-group"><label><?=ws_h(ws_t('work_schedule_default_shift', 'Default Shift'));?></label><select name="default_shift_id" id="default_shift_id" class="form-control"></select></div>
              <div class="col-md-3 form-group"><label><?=ws_h(ws_t('work_schedule_work_location', 'Work Location'));?></label><select name="work_location_id" id="work_location_id" class="form-control"></select></div>
              <div class="col-md-3 form-group"><label><?=ws_h(ws_t('common_department', 'Department'));?></label><select name="department_code" id="department_code" class="form-control"></select></div>
            </div>
            <div class="row">
              <div class="col-md-3 form-group"><label><?=ws_h(ws_t('work_schedule_employee_group', 'Employee Group'));?></label><select name="employee_group" id="employee_group" class="form-control"><option>DIRECTOR</option><option>MANAGER</option><option>STAFF</option><option>NON_STAFF</option><option>OPERATOR</option><option>CONTRACT</option><option>DAILY_WORKER</option><option>TRAINEE</option></select></div>
              <div class="col-md-3 form-group"><label><?=ws_h(ws_t('work_schedule_sap_reference', 'SAP Reference'));?></label><input name="sap_reference" id="sap_reference" class="form-control"></div>
              <div class="col-md-3 form-group"><label><?=ws_h(ws_t('work_schedule_valid_from', 'Valid From'));?></label><input name="valid_from" id="valid_from" class="form-control date-field" value="<?=$defaultFrom;?>"></div>
              <div class="col-md-3 form-group"><label><?=ws_h(ws_t('work_schedule_valid_to', 'Valid To'));?></label><input name="valid_to" id="valid_to" class="form-control date-field" value="<?=$defaultTo;?>"></div>
            </div>
            <div class="ws-section-title"><?=ws_h(ws_t('work_schedule_working_pattern', 'Working Pattern'));?></div>
            <div class="ws-day-box">
              <div class="row">
                <div class="col-md-1 form-group"><label><?=ws_h(ws_t('work_schedule_mon', 'Mon'));?></label><select name="monday" id="monday" class="form-control"><option>Y</option><option>N</option></select></div>
                <div class="col-md-1 form-group"><label><?=ws_h(ws_t('work_schedule_tue', 'Tue'));?></label><select name="tuesday" id="tuesday" class="form-control"><option>Y</option><option>N</option></select></div>
                <div class="col-md-1 form-group"><label><?=ws_h(ws_t('work_schedule_wed', 'Wed'));?></label><select name="wednesday" id="wednesday" class="form-control"><option>Y</option><option>N</option></select></div>
                <div class="col-md-1 form-group"><label><?=ws_h(ws_t('work_schedule_thu', 'Thu'));?></label><select name="thursday" id="thursday" class="form-control"><option>Y</option><option>N</option></select></div>
                <div class="col-md-1 form-group"><label><?=ws_h(ws_t('work_schedule_fri', 'Fri'));?></label><select name="friday" id="friday" class="form-control"><option>Y</option><option>N</option></select></div>
                <div class="col-md-1 form-group"><label><?=ws_h(ws_t('work_schedule_sat', 'Sat'));?></label><select name="saturday" id="saturday" class="form-control"><option>N</option><option>Y</option></select></div>
                <div class="col-md-1 form-group"><label><?=ws_h(ws_t('work_schedule_sun', 'Sun'));?></label><select name="sunday" id="sunday" class="form-control"><option>N</option><option>Y</option></select></div>
                <div class="col-md-2 form-group"><label><?=ws_h(ws_t('work_schedule_planned_start', 'Planned Start'));?></label><input name="planned_start" id="planned_start" class="form-control" placeholder="08:00"></div>
                <div class="col-md-2 form-group"><label><?=ws_h(ws_t('work_schedule_planned_end', 'Planned End'));?></label><input name="planned_end" id="planned_end" class="form-control" placeholder="17:00"></div>
                <div class="col-md-1 form-group"><label><?=ws_h(ws_t('work_schedule_break', 'Break'));?></label><input name="break_minutes" id="break_minutes" type="number" min="0" class="form-control" value="60"></div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-2 form-group"><label><?=ws_h(ws_t('work_schedule_hours_day', 'Hours / Day'));?></label><input name="working_hours_per_day" id="working_hours_per_day" type="number" step="0.01" min="0.01" class="form-control" value="8"></div>
              <div class="col-md-2 form-group"><label><?=ws_h(ws_t('work_schedule_hours_week', 'Hours / Week'));?></label><input name="working_hours_per_week" id="working_hours_per_week" type="number" step="0.01" min="0.01" class="form-control" value="40"></div>
              <div class="col-md-2 form-group"><label><?=ws_h(ws_t('work_schedule_grace_in', 'Grace In Min'));?></label><input name="grace_in_minutes" id="grace_in_minutes" type="number" min="0" class="form-control" value="0"></div>
              <div class="col-md-2 form-group"><label><?=ws_h(ws_t('work_schedule_grace_out', 'Grace Out Min'));?></label><input name="grace_out_minutes" id="grace_out_minutes" type="number" min="0" class="form-control" value="0"></div>
              <div class="col-md-2 form-group"><label><?=ws_h(ws_t('work_schedule_overtime_eligible', 'Overtime Eligible'));?></label><select name="overtime_eligible" id="overtime_eligible" class="form-control"><option>Y</option><option>N</option></select></div>
              <div class="col-md-2 form-group"><label><?=ws_h(ws_t('work_schedule_attendance_required', 'Attendance Required'));?></label><select name="attendance_required" id="attendance_required" class="form-control"><option>Y</option><option>N</option></select></div>
            </div>
            <div class="form-group"><label><?=ws_h(ws_t('work_schedule_remarks', 'Remarks'));?></label><textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="<?=ws_h(ws_t('work_schedule_remarks_placeholder', 'Notes about schedule rules, exceptions, or HR policy references.'));?>"></textarea></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?=ws_h(ws_t('common_cancel', 'Cancel'));?></button>
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?=ws_h(ws_t('work_schedule_save', 'Save Schedule'));?></button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div id="modal_ws_detail" class="modal fade">
    <div class="modal-dialog modal-lg" style="width:92%">
      <div class="modal-content">
        <div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><?=ws_h(ws_t('work_schedule_detail_title', 'Work Schedule Detail'));?></h4></div>
        <div class="modal-body" id="ws_detail_body"></div>
        <div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=ws_h(ws_t('common_close', 'Close'));?></button></div>
      </div>
    </div>
  </div>
</section>

<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var wsLang = <?=json_encode($wsJsLang, JSON_UNESCAPED_UNICODE);?>;
function wsError(m){$('.isi_warning_delete').text(m||wsLang.error_default);$('.error_data_delete').fadeIn();}
function wsFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),schedule_type:$('#filter_schedule_type').val(),schedule_category:$('#filter_schedule_category').val(),department_code:$('#filter_department_code').val(),schedule_status:$('#filter_schedule_status').val(),keyword:$('#filter_keyword').val()};}
function wsSet(s,v,t){var e=$(s);if(!v){e.val(null).trigger('change');return;}if(!e.find('option[value="'+v+'"]').length)e.append(new Option(t||v,v,true,true));e.val(v).trigger('change');}
function wsReset(title){$('#form_work_schedule')[0].reset();$('#ws_id').val('');$('#calendar_id,#default_shift_id,#work_location_id,#department_code').val(null).trigger('change');$('#schedule_type').val('FIXED').trigger('change');$('#schedule_category').val('OFFICE').trigger('change');$('#schedule_status').val('DRAFT').trigger('change');$('#employee_group').val('STAFF').trigger('change');$('#monday,#tuesday,#wednesday,#thursday,#friday').val('Y').trigger('change');$('#saturday,#sunday').val('N').trigger('change');$('#overtime_eligible,#attendance_required').val('Y').trigger('change');$('#valid_from').val('<?=$defaultFrom;?>');$('#valid_to').val('<?=$defaultTo;?>');$('#break_minutes').val(60);$('#working_hours_per_day').val(8);$('#working_hours_per_week').val(40);$('#grace_in_minutes,#grace_out_minutes').val(0);$('#modal_work_schedule .modal-title').text(title||wsLang.modal_create);}
function wsAjaxSelect(selector,act,placeholder,parent){$(selector).select2({width:'100%',allowClear:true,placeholder:placeholder,dropdownParent:parent||$(document.body),ajax:{url:'<?=base_admin();?>modul/work_schedule/work_schedule_action.php?act='+act,type:'POST',dataType:'json',delay:250,data:function(q){return{term:q.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
$(function(){
  if($.fn.datepicker){$('.ws-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_schedule_type,#filter_schedule_category,#filter_schedule_status,#schedule_type,#schedule_category,#schedule_status,#employee_group,#monday,#tuesday,#wednesday,#thursday,#friday,#saturday,#sunday,#overtime_eligible,#attendance_required').select2({width:'100%',allowClear:true});wsAjaxSelect('#filter_department_code','department_search',wsLang.all_department);wsAjaxSelect('#calendar_id','calendar_search',wsLang.select_calendar,$('#modal_work_schedule'));wsAjaxSelect('#default_shift_id','shift_search',wsLang.select_shift,$('#modal_work_schedule'));wsAjaxSelect('#work_location_id','work_location_search',wsLang.select_work_location,$('#modal_work_schedule'));wsAjaxSelect('#department_code','department_search',wsLang.select_department,$('#modal_work_schedule'));}
  var dt=$('#dtb_work_schedule').DataTable({bProcessing:true,bServerSide:true,pageLength:25,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:wsLang.export_datatable,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{width:'42px',targets:0},{width:'150px',targets:1}],ajax:{url:'<?=base_admin();?>modul/work_schedule/work_schedule_data.php',type:'post',data:function(d){$.extend(d,wsFilters());},dataSrc:function(j){var k=j.kpi||{};$('#kpi_total').text(k.total||0);$('#kpi_active').text(k.active||0);$('#kpi_shift_schedule').text(k.shift_schedule||0);$('#kpi_production').text(k.production||0);$('#kpi_avg_weekly_hours').text(k.avg_weekly_hours||0);return j.data||[];},error:function(x){console.log(x.responseText);wsError(wsLang.load_failed);}}});
  $('#btn_open_ws').click(function(){wsReset(wsLang.modal_create);$('#modal_work_schedule').modal({backdrop:'static',keyboard:false});});
  $('#btn_filter_ws').click(function(){dt.draw();});
  $('#filter_keyword').keyup(function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_ws').click(function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_schedule_type,#filter_schedule_category,#filter_schedule_status,#filter_department_code').val(null).trigger('change');$('#filter_keyword').val('');dt.draw();});
  $('#btn_export_ws').click(function(){window.location='<?=base_admin();?>modul/work_schedule/work_schedule_action.php?act=export&'+$.param(wsFilters());});
  $('.hide_alert_notif').click(function(){$('.error_data_delete').hide();});
  $('#schedule_code').on('keyup change',function(){this.value=this.value.toUpperCase();});
  $('#default_shift_id').on('select2:select',function(e){var txt=e.params.data.text||'',m=txt.match(/\((\d{2}:\d{2})-(\d{2}:\d{2})\)/);if(m){$('#planned_start').val(m[1]);$('#planned_end').val(m[2]);}});
  $('#form_work_schedule').submit(function(e){e.preventDefault();var b=$(this).find('button[type=submit]');b.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> '+wsLang.saving);$.post('<?=base_admin();?>modul/work_schedule/work_schedule_action.php?act=save',$(this).serialize(),function(r){b.prop('disabled',false).html('<i class="fa fa-save"></i> '+wsLang.save);if(r.status==='good'){$('#modal_work_schedule').modal('hide');dt.draw(false);Swal.fire(wsLang.saved,wsLang.saved_message,'success');}else wsError(r.error_message);},'json').fail(function(x){b.prop('disabled',false).html('<i class="fa fa-save"></i> '+wsLang.save);wsError(x.responseText);});});
  $(document).on('click','.btn-ws-detail',function(){$.post('<?=base_admin();?>modul/work_schedule/work_schedule_action.php?act=detail',{id:$(this).data('id')},function(h){$('#ws_detail_body').html(h);$('#modal_ws_detail').modal('show');});});
  $(document).on('click','.btn-ws-edit',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/work_schedule/work_schedule_action.php?act=get',{id:id},function(r){if(r.status!=='good'){wsError(r.error_message);return;}wsReset(wsLang.modal_edit);var h=r.data||{};Object.keys(h).forEach(function(k){var el=$('#'+k);if(el.length&&!['calendar_id','default_shift_id','work_location_id','department_code'].includes(k))el.val(h[k]);});$('#ws_id').val(h.id);wsSet('#calendar_id',h.calendar_id,h.calendar_text);wsSet('#default_shift_id',h.default_shift_id,h.default_shift_text);wsSet('#work_location_id',h.work_location_id,h.work_location_text);wsSet('#department_code',h.department_code,h.department_text);$('#schedule_type,#schedule_category,#schedule_status,#employee_group,#monday,#tuesday,#wednesday,#thursday,#friday,#saturday,#sunday,#overtime_eligible,#attendance_required').trigger('change');$('#modal_work_schedule').modal({backdrop:'static',keyboard:false});},'json');});
  $(document).on('click','.btn-ws-status',function(){var id=$(this).data('id'),status=$(this).data('status');$.post('<?=base_admin();?>modul/work_schedule/work_schedule_action.php?act=status',{id:id,status:status},function(r){if(r.status==='good'){dt.draw(false);Swal.fire(wsLang.success,wsLang.status_message,'success');}else wsError(r.error_message);},'json');});
  $(document).on('click','.btn-ws-delete',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:wsLang.delete_title,text:no,icon:'warning',showCancelButton:true,confirmButtonText:wsLang.delete,cancelButtonText:wsLang.cancel}).then(function(x){if(!x.isConfirmed)return;$.post('<?=base_admin();?>modul/work_schedule/work_schedule_action.php?act=delete',{id:id},function(r){if(r.status==='good'){dt.draw(false);Swal.fire(wsLang.deleted,wsLang.deleted_message,'success');}else wsError(r.error_message);},'json').fail(function(x){wsError(x.responseText);});});});
});
</script>
