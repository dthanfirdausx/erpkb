<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
include_once "batch_lot_traceability_lib.php";
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$plants = $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$storageLocations = $db->query("SELECT s.id,s.plant_id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$storageBins = $db->query("SELECT b.id,b.storage_location_id,b.bin_code,b.bin_name,s.storage_code FROM erp_storage_bin b LEFT JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code");
$docTypes = $db->query("SELECT DISTINCT jenis_dokpab FROM stock_layer WHERE jenis_dokpab IS NOT NULL AND jenis_dokpab<>'' ORDER BY jenis_dokpab");
$kpi = $db->fetch("SELECT COUNT(*) layer_count,COUNT(DISTINCT kode) material_count,COALESCE(SUM(qty_sisa),0) open_qty,COALESCE(SUM(qty_masuk-qty_sisa),0) used_qty FROM stock_layer WHERE qty_sisa>0");
?>
<style>
.blt-hero{background:linear-gradient(135deg,#4f46e5,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.blt-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.blt-hero p{margin:0;opacity:.92}
.blt-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.blt-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.blt-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.blt-kpi i{float:right;font-size:26px;color:#4f46e5;opacity:.55}.blt-filter .form-group{margin-bottom:12px}
#dtb_batch_lot_traceability th,#dtb_batch_lot_traceability td{font-size:12px;vertical-align:middle}.blt-stock-link{color:#0f766e;text-decoration:underline}.select2-container{width:100%!important}
</style>
<section class="content-header">
  <h1>Batch / Lot Traceability <small>SAP MM Batch Genealogy</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li><li><a href="<?=base_index();?>batch-lot-traceability"><?=wh_h(wh_t('warehouse_inventory_management', 'Inventory Management'));?></a></li><li class="active">Batch / Lot Traceability</li></ol>
</section>
<section class="content">
  <div class="blt-hero"><div class="row"><div class="col-md-8"><h1>Batch / Lot Traceability Workbench</h1><p>Trace lot dari penerimaan, dokumen pabean, movement, where-used, sampai genealogy bahan baku untuk output produksi.</p></div><div class="col-md-4 text-right"><span class="label label-primary"><?=wh_h(wh_t('warehouse_read_only_monitor', 'Read Only Monitor'));?></span></div></div></div>
  <div class="row">
    <div class="col-sm-3"><div class="blt-kpi"><i class="fa fa-cubes"></i><span>Open Qty</span><strong><?=number_format((float)$kpi->open_qty,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="blt-kpi"><i class="fa fa-list"></i><span>Open Layers</span><strong><?=number_format((float)$kpi->layer_count,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="blt-kpi"><i class="fa fa-tags"></i><span>Materials</span><strong><?=number_format((float)$kpi->material_count,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="blt-kpi"><i class="fa fa-share-alt"></i><span>Used Qty</span><strong><?=number_format((float)$kpi->used_qty,2,',','.');?></strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Batch / Lot</h3></div><div class="box-body">
    <form class="form-horizontal blt-filter" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">Receipt Date</label><div class="col-lg-2"><div class="input-group date blt-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div><div class="col-lg-2"><div class="input-group date blt-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_material', 'Material'));?></label><div class="col-lg-5"><select id="filter_material" class="form-control"></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('common_plant', 'Plant'));?></label><div class="col-lg-2"><select id="filter_plant" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_plant', 'Semua Plant'));?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=blt_h($p->plant_code.' - '.$p->plant_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></label><div class="col-lg-2"><select id="filter_storage_location" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_sloc', 'Semua SLoc'));?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=blt_h($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></label><div class="col-lg-3"><select id="filter_storage_bin" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_bin', 'Semua Bin'));?></option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=blt_h($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></label><div class="col-lg-2"><select id="filter_stock_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="UNRESTRICTED"><?=wh_h(wh_t('warehouse_unrestricted', 'Unrestricted'));?></option><option value="QUALITY"><?=wh_h(wh_t('warehouse_quality_inspection', 'Quality Inspection'));?></option><option value="BLOCKED"><?=wh_h(wh_t('warehouse_blocked', 'Blocked'));?></option></select></div>
        <label class="control-label col-lg-2">Dokumen BC</label><div class="col-lg-2"><select id="filter_jenis_dokpab" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><?php foreach($docTypes as $d){ ?><option value="<?=blt_h($d->jenis_dokpab);?>"><?=blt_h($d->jenis_dokpab);?></option><?php } ?></select></div>
        <label class="control-label col-lg-1">Open</label><div class="col-lg-1"><select id="filter_open_only" class="form-control"><option value="Y">Ya</option><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option></select></div>
        <div class="col-lg-2"><input id="filter_keyword" class="form-control" placeholder="BPB / Aju / BC / lot"></div>
      </div>
      <div class="form-group"><div class="col-lg-offset-2 col-lg-10"><button type="button" id="btn_filter_blt" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button> <button type="button" id="btn_reset_blt" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button> <button type="button" id="btn_excel_blt" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=wh_h(wh_t('common_export_excel', 'Export Excel'));?></button></div></div>
    </form>
  </div></div>
  <div class="box"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div><div class="table-responsive">
    <table id="dtb_batch_lot_traceability" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th>Layer</th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th>Receipt</th><th>Batch / Aju</th><th>Dokumen BC</th><th class="text-right">Qty Masuk</th><th class="text-right">Qty Used</th><th class="text-right">Qty Sisa</th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th>Trace Status</th></tr></thead><tbody></tbody></table>
  </div></div></div>
  <div id="modal_detail_blt" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Batch / Lot Trace Detail</h4></div><div class="modal-body" id="isi_detail_blt"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function showBltError(m){$('.isi_warning_delete').text(m||'Data Batch/Lot Traceability gagal diproses.');$('.error_data_delete').fadeIn();}
function bltFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),material_code:$('#filter_material').val(),plant_id:$('#filter_plant').val(),storage_location_id:$('#filter_storage_location').val(),storage_bin_id:$('#filter_storage_bin').val(),stock_type:$('#filter_stock_type').val(),jenis_dokpab:$('#filter_jenis_dokpab').val(),open_only:$('#filter_open_only').val(),keyword:$('#filter_keyword').val()};}
$(function(){
  if($.fn.datepicker){$('.blt-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type,#filter_jenis_dokpab,#filter_open_only').select2({width:'100%',allowClear:true});$('#filter_material').select2({width:'100%',allowClear:true,placeholder:'Cari material...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/batch_lot_traceability/batch_lot_traceability_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
  $('#filter_plant').on('change',function(){var plant=$(this).val();$('#filter_storage_location option').each(function(){var op=$(this),p=op.data('plant-id');op.toggle(!p||!plant||String(p)===String(plant));});if(plant&&$('#filter_storage_location option:selected').data('plant-id')&&String($('#filter_storage_location option:selected').data('plant-id'))!==String(plant))$('#filter_storage_location').val('').trigger('change.select2');});
  $('#filter_storage_location').on('change',function(){var loc=$(this).val();$('#filter_storage_bin option').each(function(){var op=$(this),l=op.data('storage-location-id');op.toggle(!l||!loc||String(l)===String(loc));});if(loc&&$('#filter_storage_bin option:selected').data('storage-location-id')&&String($('#filter_storage_bin option:selected').data('storage-location-id'))!==String(loc))$('#filter_storage_bin').val('').trigger('change.select2');});
  var dt=$('#dtb_batch_lot_traceability').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[8,9,10],className:'text-right'},{width:'42px',targets:0},{width:'58px',targets:1}],ajax:{url:'<?=base_admin();?>modul/batch_lot_traceability/batch_lot_traceability_data.php',type:'post',data:function(d){$.extend(d,bltFilters());},error:function(xhr){console.log(xhr);showBltError('Data Batch/Lot Traceability gagal dimuat.');}}});
  $('#btn_filter_blt').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_blt').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_material').val(null).trigger('change');$('#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type,#filter_jenis_dokpab').val('').trigger('change');$('#filter_open_only').val('Y').trigger('change');dt.draw();});
  $('#btn_excel_blt').on('click',function(){window.location='<?=base_admin();?>modul/batch_lot_traceability/batch_lot_traceability_action.php?act=excel&'+$.param(bltFilters());});
  $(document).on('click','.btn-blt-detail,.blt-stock-link',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/batch_lot_traceability/batch_lot_traceability_action.php?act=detail',{id:id},function(html){$('#isi_detail_blt').html(html);$('#modal_detail_blt').modal('show');}).fail(function(){showBltError('Detail Batch/Lot gagal dibuka.');});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
