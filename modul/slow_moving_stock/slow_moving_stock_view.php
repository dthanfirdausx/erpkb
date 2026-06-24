<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
include_once "slow_moving_stock_lib.php";
$defaultAsOf = date('Y-m-d');
$defaultThreshold = 90;
$plants = $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$storageLocations = $db->query("SELECT s.id,s.plant_id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$storageBins = $db->query("SELECT b.id,b.storage_location_id,b.bin_code,b.bin_name,s.storage_code FROM erp_storage_bin b LEFT JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code");
$docTypes = $db->query("SELECT DISTINCT jenis_dokpab FROM stock_layer WHERE jenis_dokpab IS NOT NULL AND jenis_dokpab<>'' ORDER BY jenis_dokpab");
$kpiInput = array('as_of_date'=>$defaultAsOf,'threshold_days'=>$defaultThreshold,'slow_only'=>'Y');
$kpiLayers = sms_load_layers($db, $kpiInput);
$kpiQty = 0; $kpiMaterials = array(); $kpiCritical = 0; $kpiMaxIdle = 0;
foreach ($kpiLayers as $layer) {
  $kpiQty += (float)$layer->qty_sisa;
  $kpiMaterials[$layer->kode] = true;
  if ($layer->risk_label === 'Critical') $kpiCritical += (float)$layer->qty_sisa;
  if ((int)$layer->idle_days > $kpiMaxIdle) $kpiMaxIdle = (int)$layer->idle_days;
}
?>
<style>
.sms-hero{background:linear-gradient(135deg,#991b1b,#7c2d12);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.sms-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.sms-hero p{margin:0;opacity:.92}
.sms-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.sms-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.sms-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.sms-kpi i{float:right;font-size:26px;color:#b91c1c;opacity:.55}.sms-filter .form-group{margin-bottom:12px}
#dtb_slow_moving_stock th,#dtb_slow_moving_stock td{font-size:12px;vertical-align:middle}.sms-stock-link{color:#0f766e;text-decoration:underline}.select2-container{width:100%!important}.sms-detail-table th,.sms-detail-table td{font-size:12px;vertical-align:middle!important}.sms-help{color:#64748b;margin-top:6px}
</style>
<section class="content-header">
  <h1>Slow Moving Stock <small>SAP MM Inventory Monitoring</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li><li><a href="<?=base_index();?>slow-moving-stock"><?=wh_h(wh_t('warehouse_inventory_management', 'Inventory Management'));?></a></li><li class="active">Slow Moving Stock</li></ol>
</section>
<section class="content">
  <div class="sms-hero"><div class="row"><div class="col-md-8"><h1>Slow Moving Stock Workbench</h1><p>Monitor stok yang tidak bergerak berdasarkan last goods issue/konsumsi, lokasi, stock type, lot/layer, dan dokumen pabean asal.</p></div><div class="col-md-4 text-right"><span class="label label-primary"><?=wh_h(wh_t('warehouse_read_only_monitor', 'Read Only Monitor'));?></span></div></div></div>
  <div class="row">
    <div class="col-sm-3"><div class="sms-kpi"><i class="fa fa-hourglass-half"></i><span>Slow Qty</span><strong><?=number_format($kpiQty,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="sms-kpi"><i class="fa fa-warning"></i><span>Critical Qty</span><strong><?=number_format($kpiCritical,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="sms-kpi"><i class="fa fa-tags"></i><span>Materials</span><strong><?=number_format(count($kpiMaterials),0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="sms-kpi"><i class="fa fa-clock-o"></i><span>Max Idle</span><strong><?=number_format($kpiMaxIdle,0,',','.');?> Hari</strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Slow Moving</h3></div><div class="box-body">
    <form class="form-horizontal sms-filter" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">As Of Date</label><div class="col-lg-2"><div class="input-group date sms-date"><input id="filter_as_of_date" class="form-control" value="<?=$defaultAsOf;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-2">Threshold Days</label><div class="col-lg-2"><input id="filter_threshold_days" type="number" min="1" class="form-control" value="<?=$defaultThreshold;?>"><div class="sms-help">Default SAP monitor: 90 hari tanpa issue.</div></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_material', 'Material'));?></label><div class="col-lg-3"><select id="filter_material" class="form-control"></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('common_plant', 'Plant'));?></label><div class="col-lg-2"><select id="filter_plant" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_plant', 'Semua Plant'));?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=sms_h($p->plant_code.' - '.$p->plant_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></label><div class="col-lg-2"><select id="filter_storage_location" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_sloc', 'Semua SLoc'));?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=sms_h($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></label><div class="col-lg-3"><select id="filter_storage_bin" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_bin', 'Semua Bin'));?></option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=sms_h($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></label><div class="col-lg-2"><select id="filter_stock_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="UNRESTRICTED"><?=wh_h(wh_t('warehouse_unrestricted', 'Unrestricted'));?></option><option value="QUALITY"><?=wh_h(wh_t('warehouse_quality_inspection', 'Quality Inspection'));?></option><option value="BLOCKED"><?=wh_h(wh_t('warehouse_blocked', 'Blocked'));?></option></select></div>
        <label class="control-label col-lg-1">Risk</label><div class="col-lg-2"><select id="filter_risk_label" class="form-control"><option value="">Semua Risk</option><option value="Critical">Critical</option><option value="Slow Moving">Slow Moving</option><option value="Normal">Normal</option></select></div>
        <label class="control-label col-lg-1">Slow Only</label><div class="col-lg-1"><select id="filter_slow_only" class="form-control"><option value="Y">Ya</option><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option></select></div>
        <label class="control-label col-lg-1">Jenis BC</label><div class="col-lg-2"><select id="filter_jenis_dokpab" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><?php foreach($docTypes as $d){ ?><option value="<?=sms_h($d->jenis_dokpab);?>"><?=sms_h($d->jenis_dokpab);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">No Aju</label><div class="col-lg-3"><input id="filter_no_aju" class="form-control" placeholder="Nomor aju"></div>
        <label class="control-label col-lg-1">No Daftar</label><div class="col-lg-3"><input id="filter_no_dokpab" class="form-control" placeholder="Nomor pendaftaran"></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('common_search', 'Search'));?></label><div class="col-lg-2"><input id="filter_keyword" class="form-control" placeholder="Material / BPB / BC"></div>
      </div>
      <div class="form-group">
        <div class="col-lg-offset-2 col-lg-10"><button type="button" id="btn_filter_sms" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button> <button type="button" id="btn_reset_sms" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button> <button type="button" id="btn_excel_sms" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=wh_h(wh_t('common_download_excel', 'Download Excel'));?></button></div>
      </div>
    </form>
  </div></div>
  <div class="box"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div><div class="table-responsive">
    <table id="dtb_slow_moving_stock" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th>Risk</th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th>Last Issue / Move</th><th>Oldest / Layer</th><th class="text-right">Idle Days</th><th class="text-right">Critical Qty</th><th class="text-right">Slow Qty</th><th class="text-right">Total Qty</th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th></tr></thead><tbody></tbody></table>
  </div></div></div>
  <div id="modal_detail_sms" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Detail Lot / Batch / Dokumen BC</h4></div><div class="modal-body" id="isi_detail_sms"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function showSmsError(m){$('.isi_warning_delete').text(m||'Data Slow Moving Stock gagal diproses.');$('.error_data_delete').fadeIn();}
function smsFilters(){return{as_of_date:$('#filter_as_of_date').val(),threshold_days:$('#filter_threshold_days').val(),material_code:$('#filter_material').val(),plant_id:$('#filter_plant').val(),storage_location_id:$('#filter_storage_location').val(),storage_bin_id:$('#filter_storage_bin').val(),stock_type:$('#filter_stock_type').val(),risk_label:$('#filter_risk_label').val(),jenis_dokpab:$('#filter_jenis_dokpab').val(),no_aju:$('#filter_no_aju').val(),no_dokpab:$('#filter_no_dokpab').val(),slow_only:$('#filter_slow_only').val(),keyword:$('#filter_keyword').val()};}
$(function(){
  if($.fn.datepicker){$('.sms-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type,#filter_risk_label,#filter_jenis_dokpab,#filter_slow_only').select2({width:'100%',allowClear:true});$('#filter_material').select2({width:'100%',allowClear:true,placeholder:'Cari material...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/slow_moving_stock/slow_moving_stock_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
  $('#filter_plant').on('change',function(){var plant=$(this).val();$('#filter_storage_location option').each(function(){var op=$(this),p=op.data('plant-id');op.toggle(!p||!plant||String(p)===String(plant));});if(plant&&$('#filter_storage_location option:selected').data('plant-id')&&String($('#filter_storage_location option:selected').data('plant-id'))!==String(plant))$('#filter_storage_location').val('').trigger('change.select2');});
  $('#filter_storage_location').on('change',function(){var loc=$(this).val();$('#filter_storage_bin option').each(function(){var op=$(this),l=op.data('storage-location-id');op.toggle(!l||!loc||String(l)===String(loc));});if(loc&&$('#filter_storage_bin option:selected').data('storage-location-id')&&String($('#filter_storage_bin option:selected').data('storage-location-id'))!==String(loc))$('#filter_storage_bin').val('').trigger('change.select2');});
  var dt=$('#dtb_slow_moving_stock').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[7,8,9,10],className:'text-right'},{width:'42px',targets:0},{width:'58px',targets:1}],ajax:{url:'<?=base_admin();?>modul/slow_moving_stock/slow_moving_stock_data.php',type:'post',data:function(d){$.extend(d,smsFilters());},error:function(xhr){console.log(xhr);showSmsError('Data Slow Moving Stock gagal dimuat.');}}});
  $('#btn_filter_sms').on('click',function(){dt.draw();});$('#filter_keyword,#filter_no_aju,#filter_no_dokpab,#filter_threshold_days').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_sms').on('click',function(){$('#filter_as_of_date').val('<?=$defaultAsOf;?>');$('#filter_threshold_days').val('<?=$defaultThreshold;?>');$('#filter_keyword,#filter_no_aju,#filter_no_dokpab').val('');$('#filter_material').val(null).trigger('change');$('#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type,#filter_risk_label,#filter_jenis_dokpab').val('').trigger('change');$('#filter_slow_only').val('Y').trigger('change');dt.draw();});
  $('#btn_excel_sms').on('click',function(){window.location='<?=base_admin();?>modul/slow_moving_stock/slow_moving_stock_action.php?act=excel&'+$.param(smsFilters());});
  $(document).on('click','.btn-sms-detail,.sms-stock-link',function(){var el=$(this);$.post('<?=base_admin();?>modul/slow_moving_stock/slow_moving_stock_action.php?act=detail',{material_code:el.data('material'),plant_id:el.data('plant-id'),storage_location_id:el.data('storage-location-id'),storage_bin_id:el.data('storage-bin-id'),stock_type:el.data('stock-type'),as_of_date:el.data('as-of-date'),threshold_days:el.data('threshold-days'),risk_label:el.data('risk-label'),slow_only:el.data('slow-only')},function(html){$('#isi_detail_sms').html(html);$('#modal_detail_sms').modal('show');}).fail(function(){showSmsError('Detail lot/batch/dokumen BC gagal dibuka.');});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
