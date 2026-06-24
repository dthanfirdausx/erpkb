<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
include_once "count_entry_lib.php";
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$plants = $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$storageLocations = $db->query("SELECT s.id,s.plant_id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$storageBins = $db->query("SELECT b.id,b.storage_location_id,b.bin_code,b.bin_name,s.storage_code FROM erp_storage_bin b LEFT JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code");
$kpi = $db->fetch("SELECT SUM(open_items) open_items,SUM(counted_items) counted_items,SUM(total_items) total_items FROM (SELECT SUM(status='OPEN') open_items,SUM(status='COUNTED') counted_items,COUNT(*) total_items FROM cycle_count_document_items UNION ALL SELECT SUM(status='OPEN') open_items,SUM(status='COUNTED') counted_items,COUNT(*) total_items FROM stock_opname_document_items) x");
?>
<style>
.ce-hero{background:linear-gradient(135deg,#4338ca,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.ce-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.ce-hero p{margin:0;opacity:.92}
.ce-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.ce-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.ce-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.ce-kpi i{float:right;font-size:26px;color:#4338ca;opacity:.55}.ce-filter .form-group{margin-bottom:12px}
#dtb_count_entry th,#dtb_count_entry td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}.ce-counted-qty{min-width:110px}.ce-remarks{min-width:160px}.ce-diff{font-weight:700}
</style>
<section class="content-header">
  <h1>Count Entry <small>SAP Physical Inventory</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li><li><a href="<?=base_index();?>count-entry"><?=wh_h(wh_t('warehouse_physical_inventory', 'Physical Inventory'));?></a></li><li class="active">Count Entry</li></ol>
</section>
<section class="content">
  <div class="ce-hero"><div class="row"><div class="col-md-8"><h1>Count Entry Workbench</h1><p>Input hasil hitung fisik untuk dokumen Cycle Count dan Stock Opname, lalu sistem menghitung selisih otomatis.</p></div><div class="col-md-4 text-right"><span class="label label-primary">SAP MI04 Style</span></div></div></div>
  <div class="row">
    <div class="col-sm-4"><div class="ce-kpi"><i class="fa fa-pencil-square-o"></i><span>Open Items</span><strong><?=number_format((float)$kpi->open_items,0,',','.');?></strong></div></div>
    <div class="col-sm-4"><div class="ce-kpi"><i class="fa fa-check"></i><span>Counted Items</span><strong><?=number_format((float)$kpi->counted_items,0,',','.');?></strong></div></div>
    <div class="col-sm-4"><div class="ce-kpi"><i class="fa fa-list"></i><span>Total Items</span><strong><?=number_format((float)$kpi->total_items,0,',','.');?></strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Count Entry</h3></div><div class="box-body">
    <form class="form-horizontal ce-filter" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">Count Date</label><div class="col-lg-2"><div class="input-group date ce-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div><div class="col-lg-2"><div class="input-group date ce-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1">Doc Type</label><div class="col-lg-2"><select id="filter_doc_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="CYCLE_COUNT">Cycle Count</option><option value="STOCK_OPNAME">Stock Opname</option></select></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('common_status', 'Status'));?></label><div class="col-lg-2"><select id="filter_item_status" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="OPEN">Open</option><option value="COUNTED">Counted</option><option value="POSTED">Posted</option><option value="CANCELLED">Cancelled</option></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">Doc No</label><div class="col-lg-2"><input id="filter_doc_no" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_document_no_placeholder', 'Nomor dokumen'));?>"></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_material', 'Material'));?></label><div class="col-lg-3"><select id="filter_material" class="form-control"></select></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></label><div class="col-lg-3"><select id="filter_stock_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="UNRESTRICTED"><?=wh_h(wh_t('warehouse_unrestricted', 'Unrestricted'));?></option><option value="QUALITY"><?=wh_h(wh_t('warehouse_quality_inspection', 'Quality Inspection'));?></option><option value="BLOCKED"><?=wh_h(wh_t('warehouse_blocked', 'Blocked'));?></option></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('common_plant', 'Plant'));?></label><div class="col-lg-2"><select id="filter_plant" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_plant', 'Semua Plant'));?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=ce_h($p->plant_code.' - '.$p->plant_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></label><div class="col-lg-2"><select id="filter_storage_location" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_sloc', 'Semua SLoc'));?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=ce_h($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name);?></option><?php } ?></select></div>
        <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></label><div class="col-lg-3"><select id="filter_storage_bin" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_bin', 'Semua Bin'));?></option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=ce_h($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=wh_h(wh_t('common_search', 'Search'));?></label><div class="col-lg-4"><input id="filter_keyword" class="form-control" placeholder="Doc / material / remark"></div>
        <div class="col-lg-6"><button type="button" id="btn_filter_ce" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button> <button type="button" id="btn_reset_ce" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button> <button type="button" id="btn_save_all_ce" class="btn btn-warning"><i class="fa fa-save"></i> <?=wh_h(wh_t('common_save', 'Save'));?> All</button> <button type="button" id="btn_excel_ce" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=wh_h(wh_t('common_download_excel', 'Download Excel'));?></button></div>
      </div>
    </form>
  </div></div>
  <div class="box"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div><div class="table-responsive">
    <table id="dtb_count_entry" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th>Save</th><th><?=wh_h(wh_t('warehouse_document', 'Document'));?></th><th><?=wh_h(wh_t('common_status', 'Status'));?></th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th class="text-right">System Qty</th><th>Counted Qty</th><th class="text-right">Difference</th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th>Remarks</th></tr></thead><tbody></tbody></table>
  </div></div></div>
</section>
<div class="modal fade" id="ce_detail_modal" tabindex="-1" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-eye"></i> Count Entry Detail</h4></div>
    <div class="modal-body"><table class="table table-bordered table-condensed"><tbody id="ce_detail_body"></tbody></table></div>
    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button></div>
  </div></div>
</div>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function showCeError(m){$('.isi_warning_delete').text(m||'Data Count Entry gagal diproses.');$('.error_data_delete').fadeIn();}
function ceFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),doc_type:$('#filter_doc_type').val(),doc_no:$('#filter_doc_no').val(),material_code:$('#filter_material').val(),plant_id:$('#filter_plant').val(),storage_location_id:$('#filter_storage_location').val(),storage_bin_id:$('#filter_storage_bin').val(),stock_type:$('#filter_stock_type').val(),item_status:$('#filter_item_status').val(),keyword:$('#filter_keyword').val()};}
$(function(){
  if($.fn.datepicker){$('.ce-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_doc_type,#filter_item_status,#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type').select2({width:'100%',allowClear:true});$('#filter_material').select2({width:'100%',allowClear:true,placeholder:'Cari material...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/count_entry/count_entry_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
  $('#filter_plant').on('change',function(){var plant=$(this).val();$('#filter_storage_location option').each(function(){var op=$(this),p=op.data('plant-id');op.toggle(!p||!plant||String(p)===String(plant));});if(plant&&$('#filter_storage_location option:selected').data('plant-id')&&String($('#filter_storage_location option:selected').data('plant-id'))!==String(plant))$('#filter_storage_location').val('').trigger('change.select2');});
  $('#filter_storage_location').on('change',function(){var loc=$(this).val();$('#filter_storage_bin option').each(function(){var op=$(this),l=op.data('storage-location-id');op.toggle(!l||!loc||String(l)===String(loc));});if(loc&&$('#filter_storage_bin option:selected').data('storage-location-id')&&String($('#filter_storage_bin option:selected').data('storage-location-id'))!==String(loc))$('#filter_storage_bin').val('').trigger('change.select2');});
  var dt=$('#dtb_count_entry').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[6,8],className:'text-right'},{width:'42px',targets:0},{width:'58px',targets:1}],ajax:{url:'<?=base_admin();?>modul/count_entry/count_entry_data.php',type:'post',data:function(d){$.extend(d,ceFilters());},error:function(xhr){console.log(xhr);showCeError('Data Count Entry gagal dimuat.');}}});
  $('#btn_filter_ce').on('click',function(){dt.draw();});$('#filter_keyword,#filter_doc_no').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_ce').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_doc_no,#filter_keyword').val('');$('#filter_material').val(null).trigger('change');$('#filter_doc_type,#filter_item_status,#filter_plant,#filter_storage_location,#filter_storage_bin,#filter_stock_type').val('').trigger('change');dt.draw();});
  $('#btn_excel_ce').on('click',function(){window.location='<?=base_admin();?>modul/count_entry/count_entry_action.php?act=excel&'+$.param(ceFilters());});
  $(document).on('click','.btn-ce-save',function(){var btn=$(this),tr=btn.closest('tr'),qty=tr.find('.ce-counted-qty').val(),remarks=tr.find('.ce-remarks').val();$.post('<?=base_admin();?>modul/count_entry/count_entry_action.php?act=save_count',{doc_type:btn.data('doc-type'),item_id:btn.data('item-id'),counted_qty:qty,remarks:remarks},function(res){if(res.status==='good'){tr.find('.ce-diff').text(res.difference_label).removeClass('text-danger text-success').addClass(res.difference_qty<0?'text-danger':(res.difference_qty>0?'text-success':''));dt.draw(false);}else{showCeError(res.error_message||'Count entry gagal disimpan.');}},'json').fail(function(){showCeError('Count entry gagal disimpan.');});});
  $(document).on('click','.btn-ce-detail',function(){
    var b=$(this),fields=[['Document',b.data('doc')],['Doc Type',b.data('doc-type')],['Count Date',b.data('count-date')],['Status',b.data('status')],['Material',b.data('material')],['Location',b.data('location')],['Stock Type',b.data('stock-type')],['System Qty',b.data('system-qty')],['Counted Qty',b.data('counted-qty')],['Difference',b.data('difference')],['UOM',b.data('uom')]],html='';
    $.each(fields,function(_,r){html+='<tr><th style="width:34%;background:#f7f9fb">'+$('<div>').text(r[0]).html()+'</th><td>'+$('<div>').text(r[1]||'-').html()+'</td></tr>';});
    $('#ce_detail_body').html(html);$('#ce_detail_modal').modal('show');
  });
  $('#btn_save_all_ce').on('click',function(){
    var items=[],invalid=0;
    $('#dtb_count_entry tbody tr').each(function(){
      var tr=$(this),btn=tr.find('.btn-ce-save'),qty=tr.find('.ce-counted-qty').val(),remarks=tr.find('.ce-remarks').val();
      if(!btn.length||btn.prop('disabled'))return;
      if(qty===''||isNaN(parseFloat(String(qty).replace(',','.')))){invalid++;return;}
      items.push({doc_type:btn.data('doc-type'),item_id:btn.data('item-id'),counted_qty:qty,remarks:remarks});
    });
    if(items.length<1){showCeError(invalid>0?'Ada counted qty yang belum valid.':'Tidak ada item editable pada halaman ini.');return;}
    if(invalid>0&&!confirm('Ada '+invalid+' baris yang counted qty-nya kosong/tidak valid dan akan dilewati. Lanjut simpan '+items.length+' item?'))return;
    if(invalid===0&&!confirm('Simpan semua '+items.length+' item count entry pada halaman ini?'))return;
    var btn=$(this);btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
    $.post('<?=base_admin();?>modul/count_entry/count_entry_action.php?act=save_all',{items:items},function(res){
      if(res.status==='good'||res.status==='partial'){
        alert(res.message||'Save all selesai.');
        if(res.errors&&res.errors.length)showCeError(res.errors.join(' | '));
        dt.draw(false);
      }else{
        showCeError(res.error_message||'Save all count entry gagal.');
      }
    },'json').fail(function(){showCeError('Save all count entry gagal.');}).always(function(){btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_save', 'Save'));?> All');});
  });
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
