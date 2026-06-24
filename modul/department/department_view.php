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
?>
<style>
.dept-hero{background:linear-gradient(135deg,#0f766e,#2563eb);color:#fff;border-radius:14px;padding:20px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.16)}
.dept-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.dept-hero p{margin:0;opacity:.92}
.dept-card{border-radius:12px;background:#fff;border:1px solid #e5edf5;box-shadow:0 5px 16px rgba(15,23,42,.05);margin-bottom:14px}
.dept-card .box-header{border-bottom:1px solid #eef2f7}.dept-note{color:#64748b;font-size:12px;margin-top:5px}
#dtb_department th,#dtb_department td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}
.required-label:after{content:' *';color:#dd4b39}.dept-action{white-space:nowrap}.dept-pill{display:inline-block;border-radius:999px;padding:3px 9px;background:#eef2ff;color:#3730a3;font-weight:700;font-size:11px}
#modal_department .modal-body{max-height:calc(100vh - 185px);overflow-y:auto}.dept-kpi{padding:14px;border-radius:12px;background:#f8fafc;border:1px solid #e5edf5}.dept-kpi b{font-size:22px;display:block}
</style>
<section class="content-header">
  <h1><?=hr_h('hr_department', 'Department');?> <small>SAP HR Organization Unit Assignment</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li><li>Human Resource</li><li>Organization Management</li><li class="active"><?=hr_h('hr_department', 'Department');?></li></ol>
</section>
<section class="content">
  <div class="dept-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Department Master</h1>
        <p>Kelola department sebagai master organisasi untuk employee assignment, approval, cost center, profit center, dan reporting HR/Finance.</p>
      </div>
      <div class="col-md-4 text-right">
        <?php if($canInsert){ ?><button id="btn_open_dept" class="btn btn-warning"><i class="fa fa-plus"></i> Create Department</button><?php } ?>
      </div>
    </div>
  </div>

  <div class="row" id="dept_kpi_row">
    <div class="col-sm-3"><div class="dept-kpi"><span>Total Department</span><b id="kpi_total">0</b></div></div>
    <div class="col-sm-3"><div class="dept-kpi"><span><?=hr_h('hr_active', 'Active');?></span><b id="kpi_active">0</b></div></div>
    <div class="col-sm-3"><div class="dept-kpi"><span>Production/Warehouse</span><b id="kpi_ops">0</b></div></div>
    <div class="col-sm-3"><div class="dept-kpi"><span>With Cost Center</span><b id="kpi_cost">0</b></div></div>
  </div>

  <div class="box dept-card">
    <div class="box-header"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Department</h3></div>
    <div class="box-body">
      <form class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2">Validity</label>
          <div class="col-lg-2"><div class="input-group date dept-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date dept-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1">Type</label>
          <div class="col-lg-2"><select id="filter_dept_type" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>FUNCTIONAL</option><option>OPERATIONAL</option><option>SUPPORT</option><option>SALES</option><option>PRODUCTION</option><option>WAREHOUSE</option><option>QUALITY</option><option>FINANCE</option><option>HR</option></select></div>
          <label class="control-label col-lg-1"><?=hr_h('common_status', 'Status');?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>ACTIVE</option><option>INACTIVE</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=hr_h('common_search', 'Search');?></label>
          <div class="col-lg-7"><input id="filter_keyword" class="form-control" placeholder="Kode, nama, cost center, profit center, manager, SAP reference"></div>
          <div class="col-lg-3">
            <button id="btn_filter_dept" class="btn btn-primary"><i class="fa fa-filter"></i> <?=hr_h('common_filter', 'Filter');?></button>
            <button id="btn_reset_dept" class="btn btn-default"><i class="fa fa-refresh"></i> <?=hr_h('common_reset', 'Reset');?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box dept-card">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_department" class="table table-bordered table-striped" style="width:100%">
          <thead><tr><th><?=hr_h('common_no', 'No');?></th><th><?=hr_h('common_action', 'Action');?></th><th><?=hr_h('hr_department', 'Department');?></th><th>Type</th><th>Parent</th><th>Organization Assignment</th><th>Cost/Profit</th><th><?=hr_h('hr_manager', 'Manager');?></th><th>Validity</th><th><?=hr_h('common_status', 'Status');?></th><th>Updated</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_department" class="modal fade">
    <div class="modal-dialog modal-lg" style="width:92%">
      <div class="modal-content">
        <form id="form_department">
          <input type="hidden" name="id" id="dept_id">
          <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Department</h4></div>
          <div class="modal-body">
            <div class="alert alert-info"><strong>SAP hint:</strong> Department sebaiknya ditautkan ke Org Unit/Personnel Subarea, Cost Center untuk biaya, dan Profit Center untuk reporting laba rugi.</div>
            <div class="row">
              <div class="col-md-2 form-group"><label class="required-label">Dept Code</label><input name="kd_dept" id="kd_dept" class="form-control text-uppercase" required maxlength="8" placeholder="DEP-FIN"></div>
              <div class="col-md-4 form-group"><label class="required-label"><?=hr_h('hr_department_name', 'Department Name');?></label><input name="nm_dept" id="nm_dept" class="form-control" required maxlength="100" placeholder="Finance & Accounting"></div>
              <div class="col-md-3 form-group"><label>Short Name</label><input name="dept_short_name" id="dept_short_name" class="form-control" maxlength="50" placeholder="Finance"></div>
              <div class="col-md-3 form-group"><label class="required-label">Department Type</label><select name="dept_type" id="dept_type" class="form-control" required><option value="">Pilih Type</option><option>FUNCTIONAL</option><option>OPERATIONAL</option><option>SUPPORT</option><option>SALES</option><option>PRODUCTION</option><option>WAREHOUSE</option><option>QUALITY</option><option>FINANCE</option><option>HR</option></select></div>
            </div>
            <div class="row">
              <div class="col-md-3 form-group"><label>Parent Department</label><select name="parent_dept_code" id="parent_dept_code" class="form-control"></select><div class="dept-note">Kosongkan untuk root department.</div></div>
              <div class="col-md-3 form-group"><label>Company Structure / Org Unit</label><select name="company_structure_id" id="company_structure_id" class="form-control"></select></div>
              <div class="col-md-3 form-group"><label>Cost Center</label><select name="cost_center_code" id="cost_center_code" class="form-control"></select></div>
              <div class="col-md-3 form-group"><label>Profit Center</label><select name="profit_center_code" id="profit_center_code" class="form-control"></select></div>
            </div>
            <div class="row">
              <div class="col-md-3 form-group"><label>Department Manager</label><select name="manager_user_id" id="manager_user_id" class="form-control"></select></div>
              <div class="col-md-3 form-group"><label>Functional Area</label><select name="functional_area" id="functional_area" class="form-control"><option value="">Pilih Functional Area</option><option>ADMINISTRATION</option><option>FINANCE</option><option>HUMAN_RESOURCE</option><option>PROCUREMENT</option><option>WAREHOUSE</option><option>PRODUCTION</option><option>QUALITY</option><option>SALES_DISTRIBUTION</option><option>CUSTOMS_COMPLIANCE</option><option>ENGINEERING</option><option>IT_SUPPORT</option></select></div>
              <div class="col-md-2 form-group"><label class="required-label">Valid From</label><input name="valid_from" id="valid_from" class="form-control date-field" value="<?=$defaultFrom;?>" required></div>
              <div class="col-md-2 form-group"><label class="required-label">Valid To</label><input name="valid_to" id="valid_to" class="form-control date-field" value="<?=$defaultTo;?>" required></div>
              <div class="col-md-2 form-group"><label><?=hr_h('common_status', 'Status');?></label><select name="status" id="status" class="form-control"><option>ACTIVE</option><option>INACTIVE</option></select></div>
            </div>
            <div class="row">
              <div class="col-md-4 form-group"><label>SAP Reference</label><input name="sap_reference" id="sap_reference" class="form-control" maxlength="50" placeholder="SAP-ORGEH-OU-FIN"></div>
              <div class="col-md-8 form-group"><label><?=hr_h('common_remarks', 'Remarks');?></label><textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="Catatan fungsi department, approval scope, atau assignment HR/payroll."></textarea></div>
            </div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=hr_h('common_cancel', 'Cancel');?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Department</button></div>
        </form>
      </div>
    </div>
  </div>

  <div id="modal_dept_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:92%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Department Detail</h4></div><div class="modal-body" id="dept_detail_body"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=hr_h('common_close', 'Close');?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function deptError(m){$('.isi_warning_delete').text(m||'Department gagal diproses.');$('.error_data_delete').fadeIn();}
function deptFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),dept_type:$('#filter_dept_type').val(),status:$('#filter_status').val(),keyword:$('#filter_keyword').val()};}
function setSelectValue(selector,value,text){var el=$(selector);if(!value){el.val(null).trigger('change');return;}var exists=false;el.find('option').each(function(){if(this.value==value){exists=true;return false;}});if(!exists)el.append(new Option(text||value,value,true,true));el.val(value).trigger('change');}
function resetDeptForm(title){$('#form_department')[0].reset();$('#dept_id').val('');$('#parent_dept_code,#company_structure_id,#cost_center_code,#profit_center_code,#manager_user_id').val(null).trigger('change');$('#dept_type,#functional_area').val('').trigger('change');$('#status').val('ACTIVE').trigger('change');$('#valid_from').val('<?=$defaultFrom;?>');$('#valid_to').val('<?=$defaultTo;?>');$('#kd_dept').prop('readonly',false);$('#modal_department .modal-title').text(title||'Create Department');}
function initDeptSelect2(){
  if(!$.fn.select2)return;
  $('#filter_dept_type,#filter_status,#dept_type,#functional_area,#status').select2({width:'100%',allowClear:true});
  $('#parent_dept_code').select2({width:'100%',allowClear:true,placeholder:'Pilih Parent Department...',dropdownParent:$('#modal_department'),ajax:{url:'<?=base_admin();?>modul/department/department_action.php?act=department_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||'',exclude:$('#kd_dept').val()};},processResults:function(d){return{results:d.results||[]};}}});
  $('#company_structure_id').select2({width:'100%',allowClear:true,placeholder:'Pilih Org Unit...',dropdownParent:$('#modal_department'),ajax:{url:'<?=base_admin();?>modul/department/department_action.php?act=company_structure_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
  $('#cost_center_code').select2({width:'100%',allowClear:true,placeholder:'Pilih Cost Center...',dropdownParent:$('#modal_department'),ajax:{url:'<?=base_admin();?>modul/department/department_action.php?act=cost_center_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
  $('#profit_center_code').select2({width:'100%',allowClear:true,placeholder:'Pilih Profit Center...',dropdownParent:$('#modal_department'),ajax:{url:'<?=base_admin();?>modul/department/department_action.php?act=profit_center_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
  $('#manager_user_id').select2({width:'100%',allowClear:true,placeholder:'Pilih Manager...',dropdownParent:$('#modal_department'),ajax:{url:'<?=base_admin();?>modul/department/department_action.php?act=user_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
}
$(function(){
  if($.fn.datepicker){$('.dept-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  initDeptSelect2();
  var dt=$('#dtb_department').DataTable({bProcessing:true,bServerSide:true,pageLength:25,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=hr_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{width:'42px',targets:0},{width:'155px',targets:1}],ajax:{url:'<?=base_admin();?>modul/department/department_data.php',type:'post',data:function(d){$.extend(d,deptFilters());},dataSrc:function(json){var k=json.kpi||{};$('#kpi_total').text(k.total||0);$('#kpi_active').text(k.active||0);$('#kpi_ops').text(k.ops||0);$('#kpi_cost').text(k.with_cost_center||0);return json.data||[];},error:function(xhr){console.log(xhr.responseText);deptError('Data Department gagal dimuat.');}}});
  $('#btn_open_dept').click(function(){resetDeptForm('Create Department');$('#modal_department').modal({backdrop:'static',keyboard:false});});
  $('#btn_filter_dept').click(function(){dt.draw();});
  $('#filter_keyword').keyup(function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_dept').click(function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_dept_type,#filter_status').val('').trigger('change');$('#filter_keyword').val('');dt.draw();});
  $('.hide_alert_notif').click(function(){$('.error_data_delete').hide();});
  $('#kd_dept').on('keyup change',function(){this.value=this.value.toUpperCase();});
  $('#form_department').submit(function(e){e.preventDefault();var btn=$(this).find('button[type=submit]');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=hr_h('common_saving', 'Saving...');?>');$.post('<?=base_admin();?>modul/department/department_action.php?act=save',$(this).serialize(),function(r){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Department');if(r.status==='good'){$('#modal_department').modal('hide');dt.draw(false);Swal.fire('Saved','Department berhasil disimpan.','success');}else deptError(r.error_message);},'json').fail(function(xhr){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Department');deptError(xhr.responseText);});});
  $(document).on('click','.btn-dept-detail',function(){$.post('<?=base_admin();?>modul/department/department_action.php?act=detail',{id:$(this).data('id')},function(html){$('#dept_detail_body').html(html);$('#modal_dept_detail').modal('show');});});
  $(document).on('click','.btn-dept-edit',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/department/department_action.php?act=get',{id:id},function(r){if(r.status!=='good'){deptError(r.error_message);return;}resetDeptForm('Edit Department');var h=r.data||{};$('#dept_id').val(h.kd_dept);$('#kd_dept').val(h.kd_dept).prop('readonly',true);$('#nm_dept').val(h.nm_dept);$('#dept_short_name').val(h.dept_short_name);$('#dept_type').val(h.dept_type).trigger('change');setSelectValue('#parent_dept_code',h.parent_dept_code,h.parent_dept_text);setSelectValue('#company_structure_id',h.company_structure_id,h.company_structure_text);setSelectValue('#cost_center_code',h.cost_center_code,h.cost_center_text);setSelectValue('#profit_center_code',h.profit_center_code,h.profit_center_text);setSelectValue('#manager_user_id',h.manager_user_id,h.manager_text);$('#functional_area').val(h.functional_area).trigger('change');$('#valid_from').val(h.valid_from);$('#valid_to').val(h.valid_to);$('#status').val(h.status).trigger('change');$('#sap_reference').val(h.sap_reference);$('#remarks').val(h.remarks);$('#modal_department').modal({backdrop:'static',keyboard:false});},'json');});
  $(document).on('click','.btn-dept-delete',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:erpLang('confirm_delete_title','Delete Confirmation'),text:no,icon:'warning',showCancelButton:true,confirmButtonText:erpLang('common_delete','Delete'),cancelButtonText:erpLang('common_cancel','Cancel')}).then(function(x){if(!x.isConfirmed)return;$.post('<?=base_admin();?>modul/department/department_action.php?act=delete',{id:id},function(r){if(r.status==='good'){dt.draw(false);Swal.fire(erpLang('common_deleted','Deleted'),erpLang('common_deleted_message','Data deleted successfully.'),'success');}else deptError(r.error_message);},'json').fail(function(xhr){deptError(xhr.responseText);});});});
  $(document).on('click','.btn-dept-status',function(){var id=$(this).data('id'),status=$(this).data('status');$.post('<?=base_admin();?>modul/department/department_action.php?act=status',{id:id,status:status},function(r){if(r.status==='good'){dt.draw(false);Swal.fire('Updated','Status department berhasil diubah.','success');}else deptError(r.error_message);},'json');});
});
</script>
