<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
include_once "stock_aging_lib.php";
$defaultAsOf = date('Y-m-d');
$plants = $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$storageLocations = $db->query("SELECT s.id,s.plant_id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$storageBins = $db->query("SELECT b.id,b.storage_location_id,b.bin_code,b.bin_name,s.storage_code FROM erp_storage_bin b LEFT JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code");
$kpi = $db->fetch("SELECT COUNT(*) AS layer_count,COUNT(DISTINCT kode) AS material_count,COALESCE(SUM(qty_sisa),0) AS total_qty,COALESCE(MAX(DATEDIFF(?,COALESCE(tgl_masuk,DATE(created_at)))),0) AS max_age FROM stock_layer WHERE qty_sisa>0 AND COALESCE(tgl_masuk,DATE(created_at))<=?", array($defaultAsOf,$defaultAsOf));
?>
<style>
  .saging-hero{background:linear-gradient(135deg,#7c2d12,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
  .saging-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.saging-hero p{margin:0;opacity:.92}
  .saging-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .saging-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.saging-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
  .saging-kpi i{float:right;font-size:26px;color:#b45309;opacity:.55}.saging-filter .form-group{margin-bottom:12px}
  #dtb_stock_aging th,#dtb_stock_aging td{font-size:12px;vertical-align:middle}.saging-bucket-link,.aging-bucket-link{color:#0f766e;text-decoration:underline}
  .select2-container{width:100%!important}.saging-layer-table th,.saging-layer-table td{font-size:12px;vertical-align:middle}.saging-help{color:#64748b;margin-top:6px}
</style>
<section class="content-header">
  <h1>Stock Aging <small>SAP MM Inventory Aging</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li><li><a href="<?=base_index();?>stock-aging"><?=wh_h(wh_t('warehouse_inventory_management', 'Inventory Management'));?></a></li><li class="active">Stock Aging</li></ol>
</section>
<section class="content">
  <div class="saging-hero"><div class="row"><div class="col-md-8"><h1>Stock Aging Workbench</h1><p>Analisa umur stok per material, plant, storage location, bin, dan stock type. Klik angka aging untuk melihat lot/batch dan dokumen BC asal stok.</p></div><div class="col-md-4 text-right"><span class="label label-primary"><?=wh_h(wh_t('warehouse_read_only_monitor', 'Read Only Monitor'));?></span></div></div></div>
  <div class="row">
    <div class="col-sm-3"><div class="saging-kpi"><i class="fa fa-cubes"></i><span>Open Qty</span><strong><?=number_format((float)$kpi->total_qty,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="saging-kpi"><i class="fa fa-list"></i><span>Open Layers</span><strong><?=number_format((float)$kpi->layer_count,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="saging-kpi"><i class="fa fa-tags"></i><span>Materials</span><strong><?=number_format((float)$kpi->material_count,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="saging-kpi"><i class="fa fa-clock-o"></i><span>Oldest Age</span><strong><?=number_format((float)$kpi->max_age,0,',','.');?> Hari</strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Stock Aging</h3></div><div class="box-body">
    <form class="form-horizontal saging-filter" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">As Of Date</label><div class="col-lg-2"><div class="input-group date saging-date"><input id="filter_as_of_date" class="form-control" value="<?=$defaultAsOf;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_material', 'Material'));?></label><div class="col-lg-4"><select id="filter_material" class="form-control"></select><div class="saging-help">Kosongkan untuk semua material.</div></div>
        <label class="control-label col-lg-1">Bucket</label><div class="col-lg-2"><select id="filter_aging_bucket" class="form-control"><option value="">Semua Bucket</option><?php foreach(saging_buckets() as $k=>$b){ ?><option value="<?=saging_h($k);?>"><?=saging_h($b['label']);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('common_plant', 'Plant'));?></label><div class="col-lg-2"><select id="filter_plant" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_plant', 'Semua Plant'));?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=saging_h($p->plant_code.' - '.$p->plant_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></label><div class="col-lg-2"><select id="filter_storage_location" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_sloc', 'Semua SLoc'));?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=saging_h($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></label><div class="col-lg-3"><select id="filter_storage_bin" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_bin', 'Semua Bin'));?></option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=saging_h($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></label><div class="col-lg-2"><select id="filter_stock_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="UNRESTRICTED"><?=wh_h(wh_t('warehouse_unrestricted', 'Unrestricted'));?></option><option value="QUALITY"><?=wh_h(wh_t('warehouse_quality_inspection', 'Quality Inspection'));?></option><option value="BLOCKED"><?=wh_h(wh_t('warehouse_blocked', 'Blocked'));?></option></select></div>
        <label class="control-label col-lg-2"><?=wh_h(wh_t('common_search', 'Search'));?></label><div class="col-lg-4"><input id="filter_keyword" class="form-control" placeholder="Material, BPB, No Aju, Dokumen BC"></div>
        <div class="col-lg-2"><button type="button" id="btn_filter_saging" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button> <button type="button" id="btn_reset_saging" class="btn btn-default"><i class="fa fa-refresh"></i></button> <button type="button" id="btn_excel_saging" class="btn btn-success"><i class="fa fa-file-excel-o"></i></button></div>
      </div>
    </form>
  </div></div>
  <div class="box"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div><div class="table-responsive">
    <table id="dtb_stock_aging" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th>Oldest / Max Age</th><th class="text-right">0-30</th><th class="text-right">31-60</th><th class="text-right">61-90</th><th class="text-right">91-180</th><th class="text-right">181-365</th><th class="text-right">&gt;365</th><th class="text-right">Total</th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th></tr></thead><tbody></tbody></table>
  </div></div></div>
  <div id="modal_layer_saging" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Detail Lot / Batch / Dokumen BC</h4></div><div class="modal-body" id="isi_layer_saging"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function showSagingError(m){$('.isi_warning_delete').text(m||'Data Stock Aging gagal diproses.');$('.error_data_delete').fadeIn();}
function sagingFilters(){return{as_of_date:$('#filter_as_of_date').val(),material_code:$('#filter_material').val(),plant_id:$('#filter_plant').val(),storage_location_id:$('#filter_storage_location').val(),storage_bin_id:$('#filter_storage_bin').val(),stock_type:$('#filter_stock_type').val(),aging_bucket:$('#filter_aging_bucket').val(),keyword:$('#filter_keyword').val()};}
$(function(){
  if($.fn.datepicker){$('.saging-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type,#filter_aging_bucket').select2({width:'100%',allowClear:true});
    $('#filter_material').select2({width:'100%',allowClear:true,placeholder:'Cari material...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/stock_aging/stock_aging_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
  }
  $('#filter_plant').on('change',function(){var plant=$(this).val();$('#filter_storage_location option').each(function(){var op=$(this),p=op.data('plant-id');op.toggle(!p||!plant||String(p)===String(plant));});if(plant&&$('#filter_storage_location option:selected').data('plant-id')&&String($('#filter_storage_location option:selected').data('plant-id'))!==String(plant))$('#filter_storage_location').val('').trigger('change.select2');});
  $('#filter_storage_location').on('change',function(){var loc=$(this).val();$('#filter_storage_bin option').each(function(){var op=$(this),l=op.data('storage-location-id');op.toggle(!l||!loc||String(l)===String(loc));});if(loc&&$('#filter_storage_bin option:selected').data('storage-location-id')&&String($('#filter_storage_bin option:selected').data('storage-location-id'))!==String(loc))$('#filter_storage_bin').val('').trigger('change.select2');});
  var dt=$('#dtb_stock_aging').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[5,6,7,8,9,10,11],className:'text-right'},{width:'42px',targets:0},{width:'58px',targets:1}],ajax:{url:'<?=base_admin();?>modul/stock_aging/stock_aging_data.php',type:'post',data:function(d){$.extend(d,sagingFilters());},error:function(xhr){console.log(xhr);showSagingError('Data Stock Aging gagal dimuat.');}}});
  $('#btn_filter_saging').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_saging').on('click',function(){$('#filter_as_of_date').val('<?=$defaultAsOf;?>');$('#filter_keyword').val('');$('#filter_material').val(null).trigger('change');$('#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type,#filter_aging_bucket').val('').trigger('change');dt.draw();});
  $('#btn_excel_saging').on('click',function(){window.location='<?=base_admin();?>modul/stock_aging/stock_aging_action.php?act=excel&'+$.param(sagingFilters());});
  $(document).on('click','.btn-aging-detail,.aging-bucket-link',function(){var el=$(this);$.post('<?=base_admin();?>modul/stock_aging/stock_aging_action.php?act=layer_detail',{material_code:el.data('material'),plant_id:el.data('plant-id'),storage_location_id:el.data('storage-location-id'),storage_bin_id:el.data('storage-bin-id'),stock_type:el.data('stock-type'),aging_bucket:el.data('bucket'),as_of_date:el.data('as-of-date')},function(html){$('#isi_layer_saging').html(html);$('#modal_layer_saging').modal('show');}).fail(function(){showSagingError('Detail lot/batch/dokumen BC gagal dibuka.');});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
