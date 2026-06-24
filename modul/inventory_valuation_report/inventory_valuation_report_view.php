<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
include_once "inventory_valuation_report_lib.php";
$defaultAsOf = date('Y-m-d');
$plants = iterator_to_array($db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code"));
$storageLocations = iterator_to_array($db->query("SELECT s.id,s.plant_id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code"));
$storageBins = iterator_to_array($db->query("SELECT b.id,b.storage_location_id,b.bin_code,b.bin_name,s.storage_code FROM erp_storage_bin b LEFT JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code"));
$materialTypes = iterator_to_array($db->query("SELECT id,type_code,type_name FROM erp_material_type WHERE status='Aktif' ORDER BY type_code"));
$materialGroups = iterator_to_array($db->query("SELECT id,group_code,group_name FROM erp_material_group WHERE status='Aktif' ORDER BY group_code"));
$summaryGroups = ivr_group_layers(ivr_load_layers($db, array('as_of_date'=>$defaultAsOf)));
$kpiQty=0; $kpiValue=0; $kpiZero=0; foreach($summaryGroups as $g){$kpiQty+=(float)$g->total_qty; $kpiValue+=(float)$g->total_value; $kpiZero+=(int)$g->zero_layers;}
?>
<style>
.ivr-hero{background:linear-gradient(135deg,#14532d,#1d4ed8);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.ivr-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.ivr-hero p{margin:0;opacity:.92}
.ivr-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.ivr-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.ivr-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.ivr-kpi i{float:right;font-size:26px;color:#15803d;opacity:.55}.ivr-filter .form-group{margin-bottom:12px}
#dtb_inventory_valuation th,#dtb_inventory_valuation td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}.ivr-layer-table th,.ivr-layer-table td{font-size:12px;vertical-align:middle!important}.ivr-help{color:#64748b;margin-top:6px}
</style>
<section class="content-header">
  <h1>Inventory Valuation Report <small>SAP MM Stock Valuation</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li><li><a href="<?=base_index();?>inventory-valuation-report"><?=wh_h(wh_t('warehouse_reports', 'Warehouse Reports'));?></a></li><li class="active">Inventory Valuation Report</li></ol>
</section>
<section class="content">
  <div class="ivr-hero"><div class="row"><div class="col-md-8"><h1>Inventory Valuation Report</h1><p>Valuasi persediaan berdasarkan open stock layer FIFO, plant, storage location, bin, stock type, material type/group, dan dokumen pabean.</p></div><div class="col-md-4 text-right"><span class="label label-primary">Read Only Valuation</span></div></div></div>
  <div class="row">
    <div class="col-sm-3"><div class="ivr-kpi"><i class="fa fa-cubes"></i><span>Open Qty</span><strong><?=number_format($kpiQty,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="ivr-kpi"><i class="fa fa-money"></i><span>Stock Value</span><strong><?=number_format($kpiValue,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="ivr-kpi"><i class="fa fa-tags"></i><span>Valuation Groups</span><strong><?=number_format(count($summaryGroups),0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="ivr-kpi"><i class="fa fa-warning"></i><span>Zero Price Layers</span><strong><?=number_format($kpiZero,0,',','.');?></strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Inventory Valuation</h3></div><div class="box-body">
    <form class="form-horizontal ivr-filter" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">As Of Date</label><div class="col-lg-2"><div class="input-group date ivr-date"><input id="filter_as_of_date" class="form-control" value="<?=$defaultAsOf;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_material', 'Material'));?></label><div class="col-lg-4"><select id="filter_material" class="form-control"></select><div class="ivr-help">Kosongkan untuk semua material.</div></div>
        <label class="control-label col-lg-1">Valuation</label><div class="col-lg-2"><select id="filter_valuation_status" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="VALUED">Valued</option><option value="ZERO">Zero Price</option></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('common_plant', 'Plant'));?></label><div class="col-lg-2"><select id="filter_plant" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_plant', 'Semua Plant'));?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=ivr_h($p->plant_code.' - '.$p->plant_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></label><div class="col-lg-2"><select id="filter_storage_location" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_sloc', 'Semua SLoc'));?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=ivr_h($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></label><div class="col-lg-3"><select id="filter_storage_bin" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_bin', 'Semua Bin'));?></option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=ivr_h($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></label><div class="col-lg-2"><select id="filter_stock_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="UNRESTRICTED"><?=wh_h(wh_t('warehouse_unrestricted', 'Unrestricted'));?></option><option value="QUALITY"><?=wh_h(wh_t('warehouse_quality_inspection', 'Quality Inspection'));?></option><option value="BLOCKED"><?=wh_h(wh_t('warehouse_blocked', 'Blocked'));?></option></select></div>
        <label class="control-label col-lg-2">Material Type</label><div class="col-lg-2"><select id="filter_material_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><?php foreach($materialTypes as $m){ ?><option value="<?=intval($m->id);?>"><?=ivr_h($m->type_code.' - '.$m->type_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-1">Group</label><div class="col-lg-3"><select id="filter_material_group" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><?php foreach($materialGroups as $m){ ?><option value="<?=intval($m->id);?>"><?=ivr_h($m->group_code.' - '.$m->group_name);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('common_search', 'Search'));?></label><div class="col-lg-5"><input id="filter_keyword" class="form-control" placeholder="Material / BPB / No Aju / Dokumen BC"></div>
        <div class="col-lg-5"><button type="button" id="btn_filter_ivr" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button> <button type="button" id="btn_reset_ivr" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button> <button type="button" id="btn_excel_ivr" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=wh_h(wh_t('common_export_excel', 'Export Excel'));?></button></div>
      </div>
    </form>
  </div></div>
  <div class="box"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div><div class="table-responsive">
    <table id="dtb_inventory_valuation" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th>Type / Group</th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th class="text-right"><?=wh_h(wh_t('warehouse_qty', 'Qty'));?></th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th class="text-right">Avg Price</th><th class="text-right">Value</th><th>Price Range</th><th>Layers</th><th>BC Docs</th><th><?=wh_h(wh_t('common_status', 'Status'));?></th></tr></thead><tbody></tbody></table>
  </div></div></div>
  <div id="modal_detail_ivr" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Inventory Valuation Layer Detail</h4></div><div class="modal-body" id="isi_detail_ivr"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function showIvrError(m){$('.isi_warning_delete').text(m||'Data Inventory Valuation gagal diproses.');$('.error_data_delete').fadeIn();}
function ivrFilters(){return{as_of_date:$('#filter_as_of_date').val(),material_code:$('#filter_material').val(),plant_id:$('#filter_plant').val(),storage_location_id:$('#filter_storage_location').val(),storage_bin_id:$('#filter_storage_bin').val(),stock_type:$('#filter_stock_type').val(),material_type_id:$('#filter_material_type').val(),material_group_id:$('#filter_material_group').val(),valuation_status:$('#filter_valuation_status').val(),keyword:$('#filter_keyword').val()};}
$(function(){
  if($.fn.datepicker){$('.ivr-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type,#filter_material_type,#filter_material_group,#filter_valuation_status').select2({width:'100%',allowClear:true});$('#filter_material').select2({width:'100%',allowClear:true,placeholder:'Cari material...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/inventory_valuation_report/inventory_valuation_report_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
  $('#filter_plant').on('change',function(){var plant=$(this).val();$('#filter_storage_location option').each(function(){var op=$(this),p=op.data('plant-id');op.toggle(!p||!plant||String(p)===String(plant));});if(plant&&$('#filter_storage_location option:selected').data('plant-id')&&String($('#filter_storage_location option:selected').data('plant-id'))!==String(plant))$('#filter_storage_location').val('').trigger('change.select2');});
  $('#filter_storage_location').on('change',function(){var loc=$(this).val();$('#filter_storage_bin option').each(function(){var op=$(this),l=op.data('storage-location-id');op.toggle(!l||!loc||String(l)===String(loc));});if(loc&&$('#filter_storage_bin option:selected').data('storage-location-id')&&String($('#filter_storage_bin option:selected').data('storage-location-id'))!==String(loc))$('#filter_storage_bin').val('').trigger('change.select2');});
  var dt=$('#dtb_inventory_valuation').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[5,7,8],className:'text-right'},{width:'42px',targets:0},{width:'58px',targets:1}],ajax:{url:'<?=base_admin();?>modul/inventory_valuation_report/inventory_valuation_report_data.php',type:'post',data:function(d){$.extend(d,ivrFilters());},error:function(xhr){console.log(xhr);showIvrError('Data Inventory Valuation gagal dimuat.');}}});
  $('#btn_filter_ivr').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_ivr').on('click',function(){$('#filter_as_of_date').val('<?=$defaultAsOf;?>');$('#filter_keyword').val('');$('#filter_material').val(null).trigger('change');$('#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type,#filter_material_type,#filter_material_group,#filter_valuation_status').val('').trigger('change');dt.draw();});
  $('#btn_excel_ivr').on('click',function(){window.location='<?=base_admin();?>modul/inventory_valuation_report/inventory_valuation_report_action.php?act=excel&'+$.param(ivrFilters());});
  $(document).on('click','.btn-ivr-detail',function(){var el=$(this);$.post('<?=base_admin();?>modul/inventory_valuation_report/inventory_valuation_report_action.php?act=layer_detail',{material_code:el.data('material'),plant_id:el.data('plant-id'),storage_location_id:el.data('storage-location-id'),storage_bin_id:el.data('storage-bin-id'),stock_type:el.data('stock-type'),as_of_date:el.data('as-of-date')},function(html){$('#isi_detail_ivr').html(html);$('#modal_detail_ivr').modal('show');}).fail(function(){showIvrError('Detail valuation layer gagal dibuka.');});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
