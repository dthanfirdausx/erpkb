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
$parents = $db->query("SELECT id,structure_code,structure_name,structure_type FROM erp_company_structure WHERE status IN ('DRAFT','ACTIVE') ORDER BY structure_type,structure_code");
?>
<style>
.cs-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:20px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.16)}
.cs-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.cs-hero p{margin:0;opacity:.92}
.cs-card{border-radius:12px;background:#fff;border:1px solid #e5edf5;box-shadow:0 5px 16px rgba(15,23,42,.05);margin-bottom:14px}
#dtb_company_structure th,#dtb_company_structure td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}
.required-label:after{content:' *';color:#dd4b39}.cs-action{white-space:nowrap}.cs-note{color:#64748b;font-size:12px;margin-top:5px}
.cs-type-pill{display:inline-block;border-radius:999px;padding:3px 9px;background:#eef2ff;color:#3730a3;font-weight:700;font-size:11px}
#modal_cs .modal-body{max-height:calc(100vh - 185px);overflow-y:auto}
.cs-tree{margin:0;padding-left:18px}.cs-tree li{margin:6px 0}.cs-tree small{color:#64748b}
</style>
<section class="content-header">
  <h1><?=hr_h('hr_company_structure', 'Company Structure');?> <small>SAP HCM Enterprise Structure</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li><li>Human Resource</li><li>Organization Management</li><li class="active"><?=hr_h('hr_company_structure', 'Company Structure');?></li></ol>
</section>
<section class="content">
  <div class="cs-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Company Structure Workbench</h1>
        <p>Kelola struktur organisasi SAP HR: company, company code, business area, personnel area, personnel subarea, dan org unit sebagai fondasi employee master, payroll, approval, dan reporting.</p>
      </div>
      <div class="col-md-4 text-right">
        <button id="btn_open_cs" class="btn btn-warning"><i class="fa fa-plus"></i> Create Structure</button>
      </div>
    </div>
  </div>

  <div class="box cs-card">
    <div class="box-header"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Company Structure</h3></div>
    <div class="box-body">
      <form class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2">Validity Overlap</label>
          <div class="col-lg-2"><div class="input-group date cs-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date cs-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1">Type</label>
          <div class="col-lg-2"><select id="filter_type" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>COMPANY</option><option>COMPANY_CODE</option><option>BUSINESS_AREA</option><option>PERSONNEL_AREA</option><option>PERSONNEL_SUBAREA</option><option>ORG_UNIT</option></select></div>
          <label class="control-label col-lg-1"><?=hr_h('common_status', 'Status');?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=hr_h('common_all', 'All');?></option><option>DRAFT</option><option>ACTIVE</option><option>INACTIVE</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=hr_h('common_search', 'Search');?></label>
          <div class="col-lg-7"><input id="filter_keyword" class="form-control" placeholder="Code / name / legal entity / city / SAP reference"></div>
          <div class="col-lg-3">
            <button id="btn_filter_cs" class="btn btn-primary"><i class="fa fa-filter"></i> <?=hr_h('common_filter', 'Filter');?></button>
            <button id="btn_reset_cs" class="btn btn-default"><i class="fa fa-refresh"></i> <?=hr_h('common_reset', 'Reset');?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box cs-card">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_company_structure" class="table table-bordered table-striped" style="width:100%">
          <thead><tr><th><?=hr_h('common_no', 'No');?></th><th><?=hr_h('common_action', 'Action');?></th><th>Code / Name</th><th>Type</th><th>Parent</th><th>Legal Entity</th><th>Location</th><th>Validity</th><th>Cost/Profit</th><th><?=hr_h('common_status', 'Status');?></th><th>Updated</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_cs" class="modal fade">
    <div class="modal-dialog modal-lg" style="width:92%">
      <div class="modal-content">
        <form id="form_cs">
          <input type="hidden" name="id" id="cs_id">
          <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Company Structure</h4></div>
          <div class="modal-body">
            <div class="alert alert-info"><strong>SAP hint:</strong> urutan umum adalah Company -> Company Code -> Business Area / Personnel Area -> Personnel Subarea -> Org Unit.</div>
            <div class="row">
              <div class="col-md-2 form-group"><label class="required-label">Code</label><input name="structure_code" id="structure_code" class="form-control text-uppercase" required maxlength="20" placeholder="CC01"></div>
              <div class="col-md-4 form-group"><label class="required-label">Name</label><input name="structure_name" id="structure_name" class="form-control" required maxlength="150" placeholder="PT ABC Company Code"></div>
              <div class="col-md-3 form-group"><label class="required-label">Structure Type</label><select name="structure_type" id="structure_type" class="form-control" required><option value="">Pilih Type</option><option>COMPANY</option><option>COMPANY_CODE</option><option>BUSINESS_AREA</option><option>PERSONNEL_AREA</option><option>PERSONNEL_SUBAREA</option><option>ORG_UNIT</option></select></div>
              <div class="col-md-3 form-group"><label>Parent</label><select name="parent_id" id="parent_id" class="form-control"><option value="">Root / No Parent</option><?php foreach($parents as $p){ ?><option value="<?=intval($p->id);?>" data-type="<?=htmlspecialchars($p->structure_type,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($p->structure_code.' - '.$p->structure_name.' ['.$p->structure_type.']',ENT_QUOTES,'UTF-8');?></option><?php } ?></select><div class="cs-note">Company root tidak wajib punya parent.</div></div>
            </div>
            <div class="row">
              <div class="col-md-4 form-group"><label>Legal Entity Name</label><input name="legal_entity_name" id="legal_entity_name" class="form-control" maxlength="150"></div>
              <div class="col-md-2 form-group"><label>Tax ID / NPWP</label><input name="tax_id" id="tax_id" class="form-control" maxlength="50"></div>
              <div class="col-md-2 form-group"><label>Country</label><input name="country" id="country" class="form-control text-uppercase" maxlength="3" value="ID"></div>
              <div class="col-md-2 form-group"><label>Currency</label><input name="currency" id="currency" class="form-control text-uppercase" maxlength="3" value="IDR"></div>
              <div class="col-md-2 form-group"><label>SAP Ref</label><input name="sap_reference" id="sap_reference" class="form-control" maxlength="50"></div>
            </div>
            <div class="row">
              <div class="col-md-2 form-group"><label class="required-label">Valid From</label><input name="valid_from" id="valid_from" class="form-control date-field" value="<?=$defaultFrom;?>" required></div>
              <div class="col-md-2 form-group"><label class="required-label">Valid To</label><input name="valid_to" id="valid_to" class="form-control date-field" value="<?=$defaultTo;?>" required></div>
              <div class="col-md-4 form-group"><label>Address</label><input name="address" id="address" class="form-control" maxlength="255"></div>
              <div class="col-md-2 form-group"><label>City</label><input name="city" id="city" class="form-control" maxlength="100"></div>
              <div class="col-md-2 form-group"><label><?=hr_h('common_status', 'Status');?></label><input id="status_view" class="form-control" value="DRAFT" readonly></div>
            </div>
            <div class="row">
              <div class="col-md-3 form-group"><label>Phone</label><input name="phone" id="phone" class="form-control" maxlength="50"></div>
              <div class="col-md-3 form-group"><label>Email</label><input type="email" name="email" id="email" class="form-control" maxlength="100"></div>
              <div class="col-md-3 form-group"><label>Cost Center Code</label><select name="cost_center_code" id="cost_center_code" class="form-control"></select></div>
              <div class="col-md-3 form-group"><label>Profit Center Code</label><select name="profit_center_code" id="profit_center_code" class="form-control"></select></div>
            </div>
            <div class="form-group"><label><?=hr_h('common_remarks', 'Remarks');?></label><textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="Catatan struktur, assignment HR/payroll, atau referensi organisasi."></textarea></div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=hr_h('common_cancel', 'Cancel');?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Draft</button></div>
        </form>
      </div>
    </div>
  </div>

  <div id="modal_cs_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:92%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Company Structure Detail</h4></div><div class="modal-body" id="cs_detail_body"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=hr_h('common_close', 'Close');?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function csError(m){$('.isi_warning_delete').text(m||'Company Structure gagal diproses.');$('.error_data_delete').fadeIn();}
function csFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),type:$('#filter_type').val(),status:$('#filter_status').val(),keyword:$('#filter_keyword').val()};}
function csSetSelectValue(selector,value,text){var el=$(selector);if(!value){el.val(null).trigger('change');return;}var exists=false;el.find('option').each(function(){if(this.value==value){exists=true;return false;}});if(!exists){el.append(new Option(text||value,value,true,true));}el.val(value).trigger('change');}
function openCsForm(title){$('#form_cs')[0].reset();$('#cs_id').val('');$('#parent_id,#structure_type,#cost_center_code,#profit_center_code').val('').trigger('change');$('#valid_from').val('<?=$defaultFrom;?>');$('#valid_to').val('<?=$defaultTo;?>');$('#country').val('ID');$('#currency').val('IDR');$('#status_view').val('DRAFT');$('#modal_cs .modal-title').text(title||'Create Company Structure');$('#modal_cs').modal({backdrop:'static',keyboard:false});}
$(function(){
  if($.fn.datepicker){$('.cs-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_type,#filter_status,#structure_type,#parent_id').select2({width:'100%',allowClear:true});
    $('#cost_center_code').select2({width:'100%',allowClear:true,placeholder:'Pilih Cost Center...',dropdownParent:$('#modal_cs'),minimumInputLength:0,ajax:{url:'<?=base_admin();?>modul/company_structure/company_structure_action.php?act=cost_center_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
    $('#profit_center_code').select2({width:'100%',allowClear:true,placeholder:'Pilih Profit Center...',dropdownParent:$('#modal_cs'),minimumInputLength:0,ajax:{url:'<?=base_admin();?>modul/company_structure/company_structure_action.php?act=profit_center_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
  }
  var dt=$('#dtb_company_structure').DataTable({bProcessing:true,bServerSide:true,pageLength:25,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=hr_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{width:'42px',targets:0},{width:'150px',targets:1}],ajax:{url:'<?=base_admin();?>modul/company_structure/company_structure_data.php',type:'post',data:function(d){$.extend(d,csFilters());},error:function(xhr){console.log(xhr.responseText);csError('Data Company Structure gagal dimuat.');}}});
  $('#btn_open_cs').click(function(){openCsForm('Create Company Structure');});
  $('#btn_filter_cs').click(function(){dt.draw();});
  $('#filter_keyword').keyup(function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_cs').click(function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_type,#filter_status').val('').trigger('change');$('#filter_keyword').val('');dt.draw();});
  $('.hide_alert_notif').click(function(){$('.error_data_delete').hide();});
  $('#structure_code,#country,#currency').on('keyup change',function(){this.value=this.value.toUpperCase();});
  $('#form_cs').submit(function(e){e.preventDefault();var btn=$(this).find('button[type=submit]');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=hr_h('common_saving', 'Saving...');?>');$.post('<?=base_admin();?>modul/company_structure/company_structure_action.php?act=save',$(this).serialize(),function(r){if(r.status==='good'){$('#modal_cs').modal('hide');dt.draw(false);Swal.fire('Saved','Company Structure berhasil disimpan.','success');}else csError(r.error_message);btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Draft');},'json').fail(function(xhr){csError(xhr.responseText);btn.prop('disabled',false).html('<i class="fa fa-save"></i> Save Draft');});});
  $(document).on('click','.btn-cs-detail',function(){$.post('<?=base_admin();?>modul/company_structure/company_structure_action.php?act=detail',{id:$(this).data('id')},function(html){$('#cs_detail_body').html(html);$('#modal_cs_detail').modal('show');});});
  $(document).on('click','.btn-cs-edit',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/company_structure/company_structure_action.php?act=get',{id:id},function(r){if(r.status!=='good'){csError(r.error_message);return;}openCsForm('Edit Company Structure');var h=r.data||{};$('#cs_id').val(h.id);$('#structure_code').val(h.structure_code);$('#structure_name').val(h.structure_name);$('#structure_type').val(h.structure_type).trigger('change');$('#parent_id').val(h.parent_id||'').trigger('change');$('#legal_entity_name').val(h.legal_entity_name);$('#tax_id').val(h.tax_id);$('#country').val(h.country);$('#currency').val(h.currency);$('#sap_reference').val(h.sap_reference);$('#valid_from').val(h.valid_from);$('#valid_to').val(h.valid_to);$('#address').val(h.address);$('#city').val(h.city);$('#phone').val(h.phone);$('#email').val(h.email);csSetSelectValue('#cost_center_code',h.cost_center_code,h.cost_center_text);csSetSelectValue('#profit_center_code',h.profit_center_code,h.profit_center_text);$('#status_view').val(h.status);$('#remarks').val(h.remarks);},'json');});
  function csWorkflow(act,id,no,title,input){var cfg={title:title,text:no,icon:'question',showCancelButton:true,confirmButtonText:title};if(input){cfg.input='text';cfg.inputLabel='Reason '+no;cfg.inputValidator=function(v){return !v?'Reason wajib diisi':undefined;};}Swal.fire(cfg).then(function(x){if(!x.isConfirmed)return;var data={id:id};if(input)data.reason=x.value;$.post('<?=base_admin();?>modul/company_structure/company_structure_action.php?act='+act,data,function(r){if(r.status==='good'){dt.draw(false);Swal.fire('Success',r.message||'Action berhasil.','success');}else csError(r.error_message);},'json').fail(function(xhr){csError(xhr.responseText);});});}
  $(document).on('click','.btn-cs-activate',function(){csWorkflow('activate',$(this).data('id'),$(this).data('no'),'Activate Structure');});
  $(document).on('click','.btn-cs-inactive',function(){csWorkflow('inactive',$(this).data('id'),$(this).data('no'),'Set Inactive',true);});
  $(document).on('click','.btn-cs-delete',function(){csWorkflow('delete',$(this).data('id'),$(this).data('no'),'Delete Draft');});
});
</script>
