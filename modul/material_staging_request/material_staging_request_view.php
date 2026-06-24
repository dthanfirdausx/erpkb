<?php
if (!function_exists('prod_t')) {
  function prod_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('prod_h')) {
  function prod_h($key, $fallback = '') { return htmlspecialchars((string) prod_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('prod_js')) {
  function prod_js($key, $fallback = '') { return json_encode(prod_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
$defaultFrom=date('Y-m-01');
$defaultTo=date('Y-m-d');
$plants=$db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$storageLocations=$db->query("SELECT s.id,s.plant_id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$storageBins=$db->query("SELECT b.id,b.storage_location_id,b.bin_code,b.bin_name,s.storage_code FROM erp_storage_bin b LEFT JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code");
$kpi=$db->fetch("SELECT COUNT(*) total_doc,
                        SUM(CASE WHEN staging_status='REQUESTED' THEN 1 ELSE 0 END) requested_doc,
                        SUM(CASE WHEN staging_status='PICKING' THEN 1 ELSE 0 END) picking_doc,
                        SUM(CASE WHEN staging_status='STAGED' THEN 1 ELSE 0 END) staged_doc
                 FROM erp_material_staging_request
                 WHERE request_date BETWEEN ? AND ?",array($defaultFrom,$defaultTo));
?>
<style>
  .msr-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
  .msr-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.msr-hero p{margin:0;opacity:.92}
  .msr-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .msr-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.msr-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}.msr-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}
  .msr-filter .form-group,.msr-form .form-group{margin-bottom:12px}.select2-container{width:100%!important}
  #dtb_material_staging_request th,#dtb_material_staging_request td,.msr-item-table th,.msr-item-table td{font-size:12px;vertical-align:middle}
  .msr-actions{white-space:nowrap;min-width:145px}.msr-panel-title{font-weight:700;color:#334155}.msr-help{font-size:12px;color:#64748b;margin-top:5px}
  .msr-item-wrap{max-height:420px;overflow:auto;border:1px solid #e5e7eb;border-radius:8px}.msr-item-table{margin-bottom:0}.msr-item-table thead th{position:sticky;top:0;background:#f8fafc;z-index:2}
  .msr-section{border:1px solid #e5edf5;border-radius:10px;padding:14px;margin-bottom:14px;background:#fbfdff}
</style>
<section class="content-header">
  <h1>Material Staging Request <small>SAP PP/MM Staging</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=prod_h('common_home', 'Home');?></a></li>
    <li><a href="<?=base_index();?>material-staging-request">PPIC</a></li>
    <li class="active">Material Staging Request</li>
  </ol>
</section>
<section class="content">
  <div class="msr-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Material Staging Request Workbench</h1>
        <p>Permintaan persiapan bahan baku dari Production Order ke Warehouse sebelum Goods Issue to Production 261 diposting.</p>
      </div>
      <div class="col-md-4 text-right">
        <button type="button" id="btn_add_msr" class="btn btn-warning btn-lg"><i class="fa fa-plus"></i> Create Staging Request</button>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-3"><div class="msr-kpi"><i class="fa fa-file-text-o"></i><span>Total This Period</span><strong><?=number_format((float)$kpi->total_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="msr-kpi"><i class="fa fa-paper-plane"></i><span>Requested</span><strong><?=number_format((float)$kpi->requested_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="msr-kpi"><i class="fa fa-hand-paper-o"></i><span>Picking</span><strong><?=number_format((float)$kpi->picking_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="msr-kpi"><i class="fa fa-check"></i><span>Staged</span><strong><?=number_format((float)$kpi->staged_doc,0,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=prod_h('common_filter', 'Filter');?></h3></div>
    <div class="box-body">
      <form id="filter_msr" class="form-horizontal msr-filter" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2">Request Date</label>
          <div class="col-lg-2"><div class="input-group date msr-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date msr-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=prod_h('common_status', 'Status');?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=prod_h('common_all', 'All');?></option><option>DRAFT</option><option>REQUESTED</option><option>PICKING</option><option>STAGED</option><option>PARTIAL_ISSUE</option><option>ISSUED</option><option>CANCELLED</option></select></div>
          <label class="control-label col-lg-1"><?=prod_h('production_plant', 'Plant');?></label>
          <div class="col-lg-2"><select id="filter_plant" class="form-control"><option value=""><?=prod_h('common_all', 'All');?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2">Storage Location</label>
          <div class="col-lg-3"><select id="filter_storage_location" class="form-control"><option value=""><?=prod_h('common_all', 'All');?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <label class="control-label col-lg-1"><?=prod_h('common_search', 'Search');?></label>
          <div class="col-lg-3"><input id="filter_keyword" class="form-control" placeholder="MSR, Production Order, material, user"></div>
          <div class="col-lg-3">
            <button type="button" id="btn_filter_msr" class="btn btn-primary"><i class="fa fa-filter"></i> <?=prod_h('common_filter', 'Filter');?></button>
            <button type="button" id="btn_reset_msr" class="btn btn-default"><i class="fa fa-refresh"></i> <?=prod_h('common_reset', 'Reset');?></button>
            <button type="button" id="btn_excel_msr" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=prod_h('common_export_excel', 'Export Excel');?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_material_staging_request" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead><tr><th><?=prod_h('common_no', 'No');?></th><th><?=prod_h('common_action', 'Action');?></th><th>Staging Request</th><th><?=prod_h('production_order', 'Production Order');?></th><th><?=prod_h('production_date', 'Date');?></th><th>Source / Destination</th><th><?=prod_h('production_items', 'Items');?></th><th><?=prod_h('production_qty', 'Qty');?></th><th>Shortage</th><th>Priority</th><th><?=prod_h('common_status', 'Status');?></th><th><?=prod_h('common_created_by', 'Created By');?></th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_msr_form" class="modal fade">
    <div class="modal-dialog modal-lg" style="width:96%">
      <form id="form_msr" class="modal-content msr-form">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Material Staging Request</h4></div>
        <div class="modal-body">
          <input type="hidden" name="submit" id="msr_submit_flag" value="N">
          <div class="msr-section">
            <div class="row">
              <div class="col-md-8">
                <div class="form-group">
                  <label><?=prod_h('production_order', 'Production Order');?> <span class="text-danger">*</span></label>
                  <select id="msr_production_id" name="production_id" class="form-control" required></select>
                  <div class="msr-help">Hanya Production Order status RELEASED/IN_PROCESS yang masih memiliki kebutuhan material.</div>
                </div>
              </div>
              <div class="col-md-2"><div class="form-group"><label>Request Date</label><input name="request_date" id="msr_request_date" class="form-control" value="<?=date('Y-m-d');?>" required></div></div>
              <div class="col-md-2"><div class="form-group"><label>Required Date</label><input name="required_date" id="msr_required_date" class="form-control" value="<?=date('Y-m-d');?>"></div></div>
            </div>
            <div class="row">
              <div class="col-md-3"><div class="form-group"><label>Priority</label><select name="priority" class="form-control msr-select"><option>NORMAL</option><option>HIGH</option><option>URGENT</option><option>LOW</option></select></div></div>
              <div class="col-md-3"><div class="form-group"><label>Reference No</label><input name="reference_no" class="form-control" placeholder="MRP / Schedule / Catatan"></div></div>
              <div class="col-md-3"><div class="form-group"><label>Destination</label><input name="destination_storage_location" class="form-control" value="PRODUCTION"></div></div>
              <div class="col-md-3"><div class="form-group"><label>Staging Area</label><input name="destination_area" class="form-control" value="Production Staging Area"></div></div>
            </div>
          </div>
          <div class="msr-section">
            <div class="row">
              <div class="col-md-3"><div class="form-group"><label>Source Plant</label><select name="plant_id" id="msr_plant" class="form-control msr-select"><option value="">Auto / Semua</option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div></div>
              <div class="col-md-4"><div class="form-group"><label>Source Storage Location</label><select name="storage_location_id" id="msr_storage_location" class="form-control msr-select"><option value="">Semua SLoc</option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div></div>
              <div class="col-md-5"><div class="form-group"><label>Source Storage Bin</label><select name="storage_bin_id" id="msr_storage_bin" class="form-control msr-select"><option value="">Semua Bin</option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=htmlspecialchars($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div></div>
            </div>
          </div>
          <div class="form-group"><label><?=prod_h('common_remarks', 'Remarks');?></label><textarea name="remarks" class="form-control" rows="2" placeholder="Catatan request staging"></textarea></div>
          <div class="clearfix">
            <h4 class="pull-left msr-panel-title">Component List</h4>
            <button type="button" id="btn_reload_items_msr" class="btn btn-default btn-sm pull-right"><i class="fa fa-refresh"></i> Reload Component</button>
          </div>
          <div id="msr_items_area" class="msr-item-wrap"><div class="alert alert-info" style="margin:12px">Pilih Production Order untuk menampilkan daftar bahan baku.</div></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=prod_h('common_close', 'Close');?></button>
          <button type="button" id="btn_save_draft_msr" class="btn btn-default"><i class="fa fa-save"></i> Save Draft</button>
          <button type="button" id="btn_submit_msr" class="btn btn-primary"><i class="fa fa-paper-plane"></i> Submit Request</button>
        </div>
      </form>
    </div>
  </div>

  <div id="modal_detail_msr" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Material Staging Detail</h4></div><div class="modal-body" id="isi_detail_msr"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=prod_h('common_close', 'Close');?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function msrError(m){$('.isi_warning_delete').text(m||<?=prod_js('production_material_staging_process_failed', 'Material Staging Request failed to process.');?>);$('.error_data_delete').fadeIn();}
function msrFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),status:$('#filter_status').val(),plant_id:$('#filter_plant').val(),storage_location_id:$('#filter_storage_location').val(),keyword:$('#filter_keyword').val()};}
function msrQuery(){return $.param(msrFilters());}
function msrLoadItems(){
  var po=$('#msr_production_id').val();
  if(!po){$('#msr_items_area').html('<div class="alert alert-info" style="margin:12px">Pilih Production Order untuk menampilkan daftar bahan baku.</div>');return;}
  $('#msr_items_area').html('<div class="alert alert-info" style="margin:12px"><i class="fa fa-spinner fa-spin"></i> Memuat component...</div>');
  $.post('<?=base_admin();?>modul/material_staging_request/material_staging_request_action.php?act=production_items',{
    production_id:po,plant_id:$('#msr_plant').val(),storage_location_id:$('#msr_storage_location').val(),storage_bin_id:$('#msr_storage_bin').val()
  },function(html){$('#msr_items_area').html(html);}).fail(function(){msrError(<?=prod_js('production_component_order_load_failed', 'Production Order component failed to load.');?>);});
}
function msrSubmit(flag){
  $('#msr_submit_flag').val(flag);
  var has=false;$('#form_msr input[name="selected_line[]"]:checked').each(function(){var id=$(this).val();var qty=parseFloat(($('[name="request_qty['+id+']"]').val()||'0').replace(',','.'));if(qty>0)has=true;});
  if(!$('#msr_production_id').val()){msrError('Production Order wajib dipilih.');return;}
  if(!has){msrError('Minimal satu component dengan Request Qty lebih dari nol wajib dipilih.');return;}
  $.ajax({url:'<?=base_admin();?>modul/material_staging_request/material_staging_request_action.php?act=save',type:'POST',data:$('#form_msr').serialize(),dataType:'json',success:function(r){
    if(r.status==='good'){ $('#modal_msr_form').modal('hide'); dt_msr.draw(false); swal(<?=prod_js('common_success', 'Success');?>,'Material Staging Request '+(r.staging_no||'')+' tersimpan.','success'); }
    else msrError(r.error_message||'Data gagal disimpan.');
  },error:function(xhr){console.log(xhr.responseText);msrError('Data gagal disimpan.');}});
}
var dt_msr;
$(function(){
  if($.fn.datepicker){$('.msr-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});$('#msr_request_date,#msr_required_date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_status,#filter_plant,#filter_storage_location,.msr-select').select2({width:'100%',allowClear:true});
    $('#msr_production_id').select2({width:'100%',placeholder:<?=prod_js('production_search_production_order', 'Search Production Order...');?>,minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/material_staging_request/material_staging_request_action.php?act=production_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
  }
  $('#filter_plant,#msr_plant').on('change',function(){
    var isForm=this.id==='msr_plant',plant=$(this).val(),target=isForm?'#msr_storage_location':'#filter_storage_location';
    $(target+' option').each(function(){var op=$(this),p=op.data('plant-id');op.toggle(!p||!plant||String(p)===String(plant));});
    if(isForm)msrLoadItems();
  });
  $('#msr_storage_location').on('change',function(){
    var loc=$(this).val();$('#msr_storage_bin option').each(function(){var op=$(this),l=op.data('storage-location-id');op.toggle(!l||!loc||String(l)===String(loc));});msrLoadItems();
  });
  $('#msr_storage_bin,#msr_production_id').on('change',msrLoadItems);
  $('#btn_reload_items_msr').on('click',msrLoadItems);
  $(document).on('keyup change','.msr-request-qty',function(){var tr=$(this).closest('tr'),avail=parseFloat(tr.data('available')||0),qty=parseFloat(($(this).val()||'0').replace(',','.')),short=Math.max(qty-avail,0);tr.find('.msr-shortage').text(short.toLocaleString('id-ID',{minimumFractionDigits:5,maximumFractionDigits:5}));});
  dt_msr=$('#dtb_material_staging_request').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=prod_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{width:'38px',targets:0},{width:'150px',targets:1}],ajax:{url:'<?=base_admin();?>modul/material_staging_request/material_staging_request_data.php',type:'post',data:function(d){$.extend(d,msrFilters());},error:function(xhr){console.log(xhr.responseText);msrError(<?=prod_js('production_material_staging_load_failed', 'Material Staging data failed to load.');?>);}}});
  $('#btn_filter_msr').on('click',function(){dt_msr.draw();});
  $('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt_msr.draw();});
  $('#btn_reset_msr').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_status,#filter_plant,#filter_storage_location').val('').trigger('change');dt_msr.draw();});
  $('#btn_excel_msr').on('click',function(){window.location='<?=base_admin();?>modul/material_staging_request/material_staging_request_action.php?act=excel&'+msrQuery();});
  $('#btn_add_msr').on('click',function(){document.getElementById('form_msr').reset();$('#msr_production_id').val(null).trigger('change');$('.msr-select').val('').trigger('change');$('#msr_request_date,#msr_required_date').val('<?=date('Y-m-d');?>');$('#msr_items_area').html('<div class="alert alert-info" style="margin:12px">Pilih Production Order untuk menampilkan daftar bahan baku.</div>');$('#modal_msr_form').modal('show');});
  $('#btn_save_draft_msr').on('click',function(){msrSubmit('N');});
  $('#btn_submit_msr').on('click',function(){msrSubmit('Y');});
  $(document).on('click','.btn-detail-msr',function(){$.post('<?=base_admin();?>modul/material_staging_request/material_staging_request_action.php?act=detail',{id:$(this).data('id')},function(html){$('#isi_detail_msr').html(html);$('#modal_detail_msr').modal('show');}).fail(function(){msrError('Detail gagal dibuka.');});});
  $(document).on('click','.btn-status-msr',function(){var id=$(this).data('id'),act=$(this).data('act');swal({title:'Konfirmasi',text:'Lanjutkan aksi '+act+'?',type:'warning',showCancelButton:true,confirmButtonText:'Ya'},function(){$.post('<?=base_admin();?>modul/material_staging_request/material_staging_request_action.php?act='+act,{id:id},function(r){if(r.status==='good'){dt_msr.draw(false);swal(<?=prod_js('common_success', 'Success');?>,'Status berhasil diperbarui.','success');}else msrError(r.error_message);},'json').fail(function(){msrError('Status gagal diperbarui.');});});});
  $(document).on('click','.btn-cancel-msr',function(){var id=$(this).data('id');swal({title:'Cancel Material Staging',text:'Masukkan alasan cancel:',type:'input',showCancelButton:true,closeOnConfirm:false,inputPlaceholder:'Alasan'},function(reason){if(reason===false)return;$.post('<?=base_admin();?>modul/material_staging_request/material_staging_request_action.php?act=cancel',{id:id,reason:reason||''},function(r){if(r.status==='good'){dt_msr.draw(false);swal(<?=prod_js('common_success', 'Success');?>,'Material staging dibatalkan.','success');}else swal(<?=prod_js('common_failed', 'Failed');?>,r.error_message||<?=prod_js('production_cancel_failed', 'Cancel failed.');?>,'error');},'json').fail(function(){swal(<?=prod_js('common_failed', 'Failed');?>,<?=prod_js('production_cancel_process_failed', 'Cancel failed to process.');?>,'error');});});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
