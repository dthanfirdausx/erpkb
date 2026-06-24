<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
include_once "cycle_count_lib.php";
$defaultAsOf = date('Y-m-d');
$plants = iterator_to_array($db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code"));
$storageLocations = iterator_to_array($db->query("SELECT s.id,s.plant_id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code"));
$storageBins = iterator_to_array($db->query("SELECT b.id,b.storage_location_id,b.bin_code,b.bin_name,s.storage_code FROM erp_storage_bin b LEFT JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code"));
$kpiRows = cc_load_groups($db, array('as_of_date'=>$defaultAsOf));
$kpiDue = 0; $kpiUpcoming = 0; $kpiQty = 0; $kpiOpenDocs = 0;
foreach ($kpiRows as $row) {
  if ($row->due_status === 'Due') $kpiDue++;
  if ($row->due_status === 'Upcoming') $kpiUpcoming++;
  if ($row->open_doc_no) $kpiOpenDocs++;
  $kpiQty += (float)$row->system_qty;
}
?>
<style>
.cc-hero{background:linear-gradient(135deg,#1d4ed8,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.cc-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.cc-hero p{margin:0;opacity:.92}
.cc-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.cc-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.cc-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.cc-kpi i{float:right;font-size:26px;color:#1d4ed8;opacity:.55}.cc-filter .form-group{margin-bottom:12px}
#dtb_cycle_count th,#dtb_cycle_count td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}.cc-detail-table th,.cc-detail-table td{font-size:12px;vertical-align:middle!important}.cc-help{color:#64748b;margin-top:6px}
</style>
<section class="content-header">
  <h1>Cycle Count <small>SAP Physical Inventory</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li><li><a href="<?=base_index();?>cycle-count"><?=wh_h(wh_t('warehouse_physical_inventory', 'Physical Inventory'));?></a></li><li class="active">Cycle Count</li></ol>
</section>
<section class="content">
  <div class="cc-hero"><div class="row"><div class="col-md-8"><h1>Cycle Count Workbench</h1><p>Proposal cycle count berdasarkan saldo stok terbuka, cycle class A/B/C, interval hitung, lokasi, dan dokumen pabean asal stok.</p></div><div class="col-md-4 text-right"><span class="label label-primary"><?=wh_h(wh_t('warehouse_sap_im_style', 'SAP IM Style'));?></span><br><button type="button" id="btn_open_create_cc" class="btn btn-success btn-sm" style="margin-top:12px"><i class="fa fa-plus"></i> <?=wh_h(wh_t('warehouse_create_cycle_count', 'Create Cycle Count'));?></button></div></div></div>
  <div class="row">
    <div class="col-sm-3"><div class="cc-kpi"><i class="fa fa-check-square-o"></i><span>Due Items</span><strong><?=number_format($kpiDue,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="cc-kpi"><i class="fa fa-clock-o"></i><span>Upcoming</span><strong><?=number_format($kpiUpcoming,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="cc-kpi"><i class="fa fa-folder-open"></i><span>Open Docs</span><strong><?=number_format($kpiOpenDocs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="cc-kpi"><i class="fa fa-cubes"></i><span>Total Qty</span><strong><?=number_format($kpiQty,2,',','.');?></strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Cycle Count</h3></div><div class="box-body">
    <form class="form-horizontal cc-filter" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">As Of Date</label><div class="col-lg-2"><div class="input-group date cc-date"><input id="filter_as_of_date" class="form-control" value="<?=$defaultAsOf;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_material', 'Material'));?></label><div class="col-lg-4"><select id="filter_material" class="form-control"></select><div class="cc-help">Kosongkan untuk semua material.</div></div>
        <label class="control-label col-lg-1">Class</label><div class="col-lg-2"><select id="filter_cycle_class" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="A">A - Fast count</option><option value="B">B - Normal</option><option value="C">C - Low frequency</option></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('common_plant', 'Plant'));?></label><div class="col-lg-2"><select id="filter_plant" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_plant', 'Semua Plant'));?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=cc_h($p->plant_code.' - '.$p->plant_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></label><div class="col-lg-2"><select id="filter_storage_location" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_sloc', 'Semua SLoc'));?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=cc_h($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></label><div class="col-lg-3"><select id="filter_storage_bin" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_bin', 'Semua Bin'));?></option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=cc_h($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></label><div class="col-lg-2"><select id="filter_stock_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="UNRESTRICTED"><?=wh_h(wh_t('warehouse_unrestricted', 'Unrestricted'));?></option><option value="QUALITY"><?=wh_h(wh_t('warehouse_quality_inspection', 'Quality Inspection'));?></option><option value="BLOCKED"><?=wh_h(wh_t('warehouse_blocked', 'Blocked'));?></option></select></div>
        <label class="control-label col-lg-2">Due Status</label><div class="col-lg-2"><select id="filter_due_status" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="Due">Due</option><option value="Upcoming">Upcoming</option><option value="Not Due">Not Due</option></select></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('common_search', 'Search'));?></label><div class="col-lg-3"><input id="filter_keyword" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_search_material_doc_bc', 'Material / BPB / BC / No Aju'));?>"></div>
      </div>
      <div class="form-group">
        <div class="col-lg-offset-2 col-lg-10"><button type="button" id="btn_filter_cc" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button> <button type="button" id="btn_reset_cc" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button> <button type="button" id="btn_excel_cc" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=wh_h(wh_t('common_download_excel', 'Download Excel'));?></button></div>
      </div>
    </form>
  </div></div>
  <div class="box"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div><div class="table-responsive">
    <table id="dtb_cycle_count" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th>Due</th><th>Class</th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th>Last Count</th><th>Oldest / Layer</th><th class="text-right">System Qty</th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th></tr></thead><tbody></tbody></table>
  </div></div></div>
  <div id="modal_detail_cc" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Detail Layer / Dokumen BC</h4></div><div class="modal-body" id="isi_detail_cc"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div></div></div></div>
  <div id="modal_create_cc" class="modal fade"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-plus"></i> <?=wh_h(wh_t('warehouse_create_cycle_count', 'Create Cycle Count'));?> Document</h4></div>
    <div class="modal-body">
      <div class="alert alert-info"><strong>Petunjuk:</strong> pilih material dan lokasi stok yang akan dihitung. Setelah dokumen dibuat, hasil hitung fisik diinput lewat menu <strong>Count Entry</strong>.</div>
      <form class="form-horizontal" id="form_create_cc" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-sm-3">Count Date <span class="text-red">*</span></label>
          <div class="col-sm-4"><div class="input-group date cc-date"><input id="cc_create_as_of_date" class="form-control cc-create-required" value="<?=$defaultAsOf;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        </div>
        <div class="form-group">
          <label class="control-label col-sm-3">Material <span class="text-red">*</span></label>
          <div class="col-sm-8"><select id="cc_create_material" class="form-control cc-create-required"></select><p class="help-block">Material diambil dari master barang dan saldo stock layer yang masih tersedia.</p></div>
        </div>
        <div class="form-group">
          <label class="control-label col-sm-3">Plant <span class="text-red">*</span></label>
          <div class="col-sm-8"><select id="cc_create_plant" class="form-control cc-create-required"><option value="">Pilih Plant</option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=cc_h($p->plant_code.' - '.$p->plant_name);?></option><?php } ?></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-sm-3">Storage Location <span class="text-red">*</span></label>
          <div class="col-sm-8"><select id="cc_create_storage_location" class="form-control cc-create-required"><option value="">Pilih Storage Location</option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=cc_h($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name);?></option><?php } ?></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-sm-3"><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></label>
          <div class="col-sm-8"><select id="cc_create_storage_bin" class="form-control"><option value="">Semua / Tanpa Bin</option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=cc_h($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name);?></option><?php } ?></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-sm-3">Stock Type <span class="text-red">*</span></label>
          <div class="col-sm-5"><select id="cc_create_stock_type" class="form-control cc-create-required"><option value="">Pilih Stock Type</option><option value="UNRESTRICTED"><?=wh_h(wh_t('warehouse_unrestricted', 'Unrestricted'));?></option><option value="QUALITY"><?=wh_h(wh_t('warehouse_quality_inspection', 'Quality Inspection'));?></option><option value="BLOCKED"><?=wh_h(wh_t('warehouse_blocked', 'Blocked'));?></option></select></div>
        </div>
      </form>
    </div>
    <div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button><button type="button" id="btn_save_create_cc" class="btn btn-success" disabled><i class="fa fa-save"></i> <?=wh_h(wh_t('warehouse_create_document', 'Create Document'));?></button></div>
  </div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function showCcError(m){$('.isi_warning_delete').text(m||<?=json_encode(wh_t('warehouse_cycle_count_process_failed', 'Data Cycle Count gagal diproses.'));?>);$('.error_data_delete').fadeIn();}
function ccFilters(){return{as_of_date:$('#filter_as_of_date').val(),material_code:$('#filter_material').val(),plant_id:$('#filter_plant').val(),storage_location_id:$('#filter_storage_location').val(),storage_bin_id:$('#filter_storage_bin').val(),stock_type:$('#filter_stock_type').val(),cycle_class:$('#filter_cycle_class').val(),due_status:$('#filter_due_status').val(),keyword:$('#filter_keyword').val()};}
function ccCreateValid(){var ok=true;$('.cc-create-required').each(function(){if(!$(this).val())ok=false;});$('#btn_save_create_cc').prop('disabled',!ok);}
function ccApplyCreateLocationFilter(){
  var plant=$('#cc_create_plant').val();
  $('#cc_create_storage_location option').each(function(){var op=$(this),p=op.data('plant-id');op.toggle(!p||!plant||String(p)===String(plant));});
  if(plant&&$('#cc_create_storage_location option:selected').data('plant-id')&&String($('#cc_create_storage_location option:selected').data('plant-id'))!==String(plant))$('#cc_create_storage_location').val('').trigger('change.select2');
}
function ccApplyCreateBinFilter(){
  var loc=$('#cc_create_storage_location').val();
  $('#cc_create_storage_bin option').each(function(){var op=$(this),l=op.data('storage-location-id');op.toggle(!l||!loc||String(l)===String(loc));});
  if(loc&&$('#cc_create_storage_bin option:selected').data('storage-location-id')&&String($('#cc_create_storage_bin option:selected').data('storage-location-id'))!==String(loc))$('#cc_create_storage_bin').val('').trigger('change.select2');
}
$(function(){
  if($.fn.datepicker){$('.cc-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type,#filter_cycle_class,#filter_due_status,#cc_create_plant,#cc_create_storage_location,#cc_create_storage_bin,#cc_create_stock_type').select2({width:'100%',allowClear:true});$('#filter_material,#cc_create_material').select2({width:'100%',allowClear:true,placeholder:'Cari material...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/cycle_count/cycle_count_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
  $('#filter_plant').on('change',function(){var plant=$(this).val();$('#filter_storage_location option').each(function(){var op=$(this),p=op.data('plant-id');op.toggle(!p||!plant||String(p)===String(plant));});if(plant&&$('#filter_storage_location option:selected').data('plant-id')&&String($('#filter_storage_location option:selected').data('plant-id'))!==String(plant))$('#filter_storage_location').val('').trigger('change.select2');});
  $('#filter_storage_location').on('change',function(){var loc=$(this).val();$('#filter_storage_bin option').each(function(){var op=$(this),l=op.data('storage-location-id');op.toggle(!l||!loc||String(l)===String(loc));});if(loc&&$('#filter_storage_bin option:selected').data('storage-location-id')&&String($('#filter_storage_bin option:selected').data('storage-location-id'))!==String(loc))$('#filter_storage_bin').val('').trigger('change.select2');});
  var dt=$('#dtb_cycle_count').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[8],className:'text-right'},{width:'42px',targets:0},{width:'100px',targets:1}],ajax:{url:'<?=base_admin();?>modul/cycle_count/cycle_count_data.php',type:'post',data:function(d){$.extend(d,ccFilters());},error:function(xhr){console.log(xhr);showCcError(<?=json_encode(wh_t('warehouse_cycle_count_load_failed', 'Data Cycle Count gagal dimuat.'));?>);}}});
  $('#btn_filter_cc').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_cc').on('click',function(){$('#filter_as_of_date').val('<?=$defaultAsOf;?>');$('#filter_keyword').val('');$('#filter_material').val(null).trigger('change');$('#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type,#filter_cycle_class,#filter_due_status').val('').trigger('change');dt.draw();});
  $('#btn_excel_cc').on('click',function(){window.location='<?=base_admin();?>modul/cycle_count/cycle_count_action.php?act=excel&'+$.param(ccFilters());});
  $('#btn_open_create_cc').on('click',function(){
    $('#cc_create_as_of_date').val($('#filter_as_of_date').val()||'<?=$defaultAsOf;?>');
    $('#cc_create_material').val($('#filter_material').val()).trigger('change');
    $('#cc_create_plant').val($('#filter_plant').val()).trigger('change');
    $('#cc_create_storage_location').val($('#filter_storage_location').val()).trigger('change');
    $('#cc_create_storage_bin').val($('#filter_storage_bin').val()).trigger('change');
    $('#cc_create_stock_type').val($('#filter_stock_type').val()||'UNRESTRICTED').trigger('change');
    ccApplyCreateLocationFilter();ccApplyCreateBinFilter();ccCreateValid();
    $('#modal_create_cc').modal('show');
  });
  $('#cc_create_plant').on('change',function(){ccApplyCreateLocationFilter();ccApplyCreateBinFilter();ccCreateValid();});
  $('#cc_create_storage_location').on('change',function(){ccApplyCreateBinFilter();ccCreateValid();});
  $('#cc_create_material,#cc_create_storage_bin,#cc_create_stock_type,#cc_create_as_of_date').on('change keyup',ccCreateValid);
  $('#btn_save_create_cc').on('click',function(){
    if($(this).prop('disabled'))return;
    var btn=$(this);btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Creating...');
    $.post('<?=base_admin();?>modul/cycle_count/cycle_count_action.php?act=create_doc',{manual_create:1,material_code:$('#cc_create_material').val(),plant_id:$('#cc_create_plant').val(),storage_location_id:$('#cc_create_storage_location').val(),storage_bin_id:$('#cc_create_storage_bin').val(),stock_type:$('#cc_create_stock_type').val(),as_of_date:$('#cc_create_as_of_date').val()},function(res){
      if(res.status==='good'){alert(res.message||<?=json_encode(wh_t('warehouse_cycle_count_created', 'Dokumen cycle count berhasil dibuat.'));?>);$('#modal_create_cc').modal('hide');dt.draw();}
      else{showCcError(res.error_message||<?=json_encode(wh_t('warehouse_cycle_count_create_failed', 'Gagal membuat dokumen cycle count.'));?>);}
    },'json').fail(function(){showCcError(<?=json_encode(wh_t('warehouse_cycle_count_create_failed', 'Gagal membuat dokumen cycle count.'));?>);}).always(function(){btn.html('<i class="fa fa-save"></i> <?=wh_h(wh_t('warehouse_create_document', 'Create Document'));?>');ccCreateValid();});
  });
  $(document).on('click','.btn-cc-detail',function(){var el=$(this);$.post('<?=base_admin();?>modul/cycle_count/cycle_count_action.php?act=detail',{material_code:el.data('material'),plant_id:el.data('plant-id'),storage_location_id:el.data('storage-location-id'),storage_bin_id:el.data('storage-bin-id'),stock_type:el.data('stock-type'),as_of_date:el.data('as-of-date')},function(html){$('#isi_detail_cc').html(html);$('#modal_detail_cc').modal('show');}).fail(function(){showCcError(<?=json_encode(wh_t('warehouse_detail_layer_failed', 'Detail layer gagal dibuka.'));?>);});});
  $(document).on('click','.btn-cc-create',function(){var el=$(this);if(!confirm(<?=json_encode(wh_t('warehouse_cycle_count_create_confirm', 'Buat dokumen cycle count untuk material/lokasi ini?'));?>))return;$.post('<?=base_admin();?>modul/cycle_count/cycle_count_action.php?act=create_doc',{material_code:el.data('material'),plant_id:el.data('plant-id'),storage_location_id:el.data('storage-location-id'),storage_bin_id:el.data('storage-bin-id'),stock_type:el.data('stock-type'),as_of_date:el.data('as-of-date')},function(res){if(res.status==='good'){alert(res.message||<?=json_encode(wh_t('warehouse_cycle_count_created', 'Dokumen cycle count berhasil dibuat.'));?>);dt.draw();}else{showCcError(res.error_message||<?=json_encode(wh_t('warehouse_cycle_count_create_failed', 'Gagal membuat dokumen cycle count.'));?>);}},'json').fail(function(){showCcError(<?=json_encode(wh_t('warehouse_cycle_count_create_failed', 'Gagal membuat dokumen cycle count.'));?>);});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
