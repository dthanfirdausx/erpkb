<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
include_once "difference_posting_lib.php";
$defaultFrom=date('Y-m-01'); $defaultTo=date('Y-m-d');
$plants=$db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$storageLocations=$db->query("SELECT s.id,s.plant_id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$storageBins=$db->query("SELECT b.id,b.storage_location_id,b.bin_code,b.bin_name,s.storage_code FROM erp_storage_bin b LEFT JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code");
$kpi=$db->fetch("SELECT SUM(ready_items) ready_items,SUM(posted_items) posted_items,SUM(diff_abs) diff_abs FROM (SELECT SUM(status='COUNTED' AND COALESCE(difference_qty,0)<>0) ready_items,SUM(status='POSTED') posted_items,SUM(ABS(COALESCE(difference_qty,0))) diff_abs FROM cycle_count_document_items UNION ALL SELECT SUM(status='COUNTED' AND COALESCE(difference_qty,0)<>0) ready_items,SUM(status='POSTED') posted_items,SUM(ABS(COALESCE(difference_qty,0))) diff_abs FROM stock_opname_document_items) x");
?>
<style>
.dp-hero{background:linear-gradient(135deg,#7c2d12,#4338ca);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.dp-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.dp-hero p{margin:0;opacity:.92}
.dp-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.dp-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.dp-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.dp-kpi i{float:right;font-size:26px;color:#7c2d12;opacity:.55}.dp-filter .form-group{margin-bottom:12px}
#dtb_difference_posting th,#dtb_difference_posting td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}
</style>
<section class="content-header">
  <h1>Difference Posting <small>SAP Physical Inventory</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li><li><a href="<?=base_index();?>difference-posting"><?=wh_h(wh_t('warehouse_physical_inventory', 'Physical Inventory'));?></a></li><li class="active">Difference Posting</li></ol>
</section>
<section class="content">
  <div class="dp-hero"><div class="row"><div class="col-md-8"><h1>Difference Posting Workbench</h1><p>Posting selisih hasil count ke material document. Selisih positif memakai movement 701, selisih negatif memakai movement 702.</p></div><div class="col-md-4 text-right"><span class="label label-primary">SAP MI07 Style</span></div></div></div>
  <div class="row">
    <div class="col-sm-4"><div class="dp-kpi"><i class="fa fa-balance-scale"></i><span>Ready To Post</span><strong><?=number_format((float)$kpi->ready_items,0,',','.');?></strong></div></div>
    <div class="col-sm-4"><div class="dp-kpi"><i class="fa fa-check"></i><span>Posted Items</span><strong><?=number_format((float)$kpi->posted_items,0,',','.');?></strong></div></div>
    <div class="col-sm-4"><div class="dp-kpi"><i class="fa fa-cubes"></i><span>Total Difference</span><strong><?=number_format((float)$kpi->diff_abs,2,',','.');?></strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Difference Posting</h3></div><div class="box-body">
    <form class="form-horizontal dp-filter" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">Count Date</label><div class="col-lg-2"><div class="input-group date dp-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div><div class="col-lg-2"><div class="input-group date dp-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1">Doc Type</label><div class="col-lg-2"><select id="filter_doc_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="CYCLE_COUNT">Cycle Count</option><option value="STOCK_OPNAME">Stock Opname</option></select></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('common_status', 'Status'));?></label><div class="col-lg-2"><select id="filter_posting_status" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="READY">Ready</option><option value="POSTED">Posted</option><option value="ZERO">Zero Diff</option></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">Doc No</label><div class="col-lg-2"><input id="filter_doc_no" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_document_no_placeholder', 'Nomor dokumen'));?>"></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_material', 'Material'));?></label><div class="col-lg-3"><select id="filter_material" class="form-control"></select></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></label><div class="col-lg-3"><select id="filter_stock_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="UNRESTRICTED"><?=wh_h(wh_t('warehouse_unrestricted', 'Unrestricted'));?></option><option value="QUALITY"><?=wh_h(wh_t('warehouse_quality_inspection', 'Quality Inspection'));?></option><option value="BLOCKED"><?=wh_h(wh_t('warehouse_blocked', 'Blocked'));?></option></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">Variance</label><div class="col-lg-2"><select id="filter_variance_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="NONZERO">Ada Selisih</option><option value="POSITIVE">Positive / 701</option><option value="NEGATIVE">Negative / 702</option><option value="ZERO">Zero Difference</option></select></div>
        <label class="control-label col-lg-2"><?=wh_h(wh_t('common_plant', 'Plant'));?></label><div class="col-lg-2"><select id="filter_plant" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_plant', 'Semua Plant'));?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=dp_h($p->plant_code.' - '.$p->plant_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-1">SLoc</label><div class="col-lg-3"><select id="filter_storage_location" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_sloc', 'Semua SLoc'));?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=dp_h($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></label><div class="col-lg-4"><select id="filter_storage_bin" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_bin', 'Semua Bin'));?></option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=dp_h($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name);?></option><?php } ?></select></div>
        <div class="col-lg-6"><button type="button" id="btn_negative_dp" class="btn btn-danger"><i class="fa fa-minus-circle"></i> Tampilkan Negative</button></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('common_search', 'Search'));?></label><div class="col-lg-4"><input id="filter_keyword" class="form-control" placeholder="Doc / material / posting no"></div>
        <div class="col-lg-6"><button type="button" id="btn_filter_dp" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button> <button type="button" id="btn_reset_dp" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button> <button type="button" id="btn_excel_dp" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=wh_h(wh_t('common_download_excel', 'Download Excel'));?></button></div>
      </div>
    </form>
  </div></div>
  <div class="box"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div><div class="table-responsive">
    <table id="dtb_difference_posting" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th><?=wh_h(wh_t('warehouse_document', 'Document'));?></th><th><?=wh_h(wh_t('common_status', 'Status'));?></th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th class="text-right">System Qty</th><th class="text-right">Counted Qty</th><th class="text-right">Difference</th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th>Posting</th></tr></thead><tbody></tbody></table>
  </div></div></div>
</section>
<div class="modal fade" id="dp_detail_modal" tabindex="-1" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-eye"></i> Difference Posting Detail</h4></div>
    <div class="modal-body"><table class="table table-bordered table-condensed"><tbody id="dp_detail_body"></tbody></table></div>
    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button></div>
  </div></div>
</div>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function showDpError(m){$('.isi_warning_delete').text(m||'Data Difference Posting gagal diproses.');$('.error_data_delete').fadeIn();}
function dpFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),doc_type:$('#filter_doc_type').val(),doc_no:$('#filter_doc_no').val(),material_code:$('#filter_material').val(),plant_id:$('#filter_plant').val(),storage_location_id:$('#filter_storage_location').val(),storage_bin_id:$('#filter_storage_bin').val(),stock_type:$('#filter_stock_type').val(),posting_status:$('#filter_posting_status').val(),variance_type:$('#filter_variance_type').val(),keyword:$('#filter_keyword').val()};}
$(function(){
  if($.fn.datepicker){$('.dp-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_doc_type,#filter_posting_status,#filter_variance_type,#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type').select2({width:'100%',allowClear:true});$('#filter_material').select2({width:'100%',allowClear:true,placeholder:'Cari material...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/difference_posting/difference_posting_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
  $('#filter_plant').on('change',function(){var plant=$(this).val();$('#filter_storage_location option').each(function(){var op=$(this),p=op.data('plant-id');op.toggle(!p||!plant||String(p)===String(plant));});if(plant&&$('#filter_storage_location option:selected').data('plant-id')&&String($('#filter_storage_location option:selected').data('plant-id'))!==String(plant))$('#filter_storage_location').val('').trigger('change.select2');});
  $('#filter_storage_location').on('change',function(){var loc=$(this).val();$('#filter_storage_bin option').each(function(){var op=$(this),l=op.data('storage-location-id');op.toggle(!l||!loc||String(l)===String(loc));});if(loc&&$('#filter_storage_bin option:selected').data('storage-location-id')&&String($('#filter_storage_bin option:selected').data('storage-location-id'))!==String(loc))$('#filter_storage_bin').val('').trigger('change.select2');});
  var dt=$('#dtb_difference_posting').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[6,7,8],className:'text-right'},{width:'42px',targets:0},{width:'70px',targets:1}],ajax:{url:'<?=base_admin();?>modul/difference_posting/difference_posting_data.php',type:'post',data:function(d){$.extend(d,dpFilters());},error:function(xhr){console.log(xhr);showDpError('Data Difference Posting gagal dimuat.');}}});
  $('#btn_filter_dp').on('click',function(){dt.draw();});$('#filter_keyword,#filter_doc_no').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_dp').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_doc_no,#filter_keyword').val('');$('#filter_material').val(null).trigger('change');$('#filter_doc_type,#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type,#filter_variance_type,#filter_posting_status').val('').trigger('change');dt.draw();});
  $('#btn_negative_dp').on('click',function(){$('#filter_posting_status').val('').trigger('change');$('#filter_variance_type').val('NEGATIVE').trigger('change');dt.draw();});
  $('#btn_excel_dp').on('click',function(){window.location='<?=base_admin();?>modul/difference_posting/difference_posting_action.php?act=excel&'+$.param(dpFilters());});
  $(document).on('click','.btn-dp-post',function(){var btn=$(this);if(!confirm('Posting selisih physical inventory ini?'))return;$.post('<?=base_admin();?>modul/difference_posting/difference_posting_action.php?act=post',{doc_type:btn.data('doc-type'),item_id:btn.data('item-id')},function(res){if(res.status==='good'){alert('Difference posting berhasil: '+res.posting_no+' / MvT '+res.movement_type);dt.draw(false);}else{showDpError(res.error_message||'Difference posting gagal.');}},'json').fail(function(){showDpError('Difference posting gagal.');});});
  $(document).on('click','.btn-dp-detail',function(){
    var b=$(this),fields=[['Document',b.data('doc')],['Doc Type',b.data('doc-type')],['Count Date',b.data('count-date')],['Status',b.data('status')],['Material',b.data('material')],['Location',b.data('location')],['Stock Type',b.data('stock-type')],['System Qty',b.data('system-qty')],['Counted Qty',b.data('counted-qty')],['Difference',b.data('difference')],['Movement Type',b.data('movement')],['Posting No',b.data('posting')]],html='';
    $.each(fields,function(_,r){html+='<tr><th style="width:34%;background:#f7f9fb">'+$('<div>').text(r[0]).html()+'</th><td>'+$('<div>').text(r[1]||'-').html()+'</td></tr>';});
    $('#dp_detail_body').html(html);$('#dp_detail_modal').modal('show');
  });
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
