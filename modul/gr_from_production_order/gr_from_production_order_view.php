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
if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$plants = $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$modalPlants = $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$storageLocations = $db->query("SELECT s.id,s.storage_code,s.storage_name,s.plant_id,p.plant_code FROM erp_storage_location s JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$modalStorageLocations = $db->query("SELECT s.id,s.storage_code,s.storage_name,s.plant_id,p.plant_code FROM erp_storage_location s JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$storageBins = $db->query("SELECT b.id,b.bin_code,b.bin_name,b.storage_location_id,s.storage_code FROM erp_storage_bin b JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code");
$kpi = $db->fetch("SELECT COUNT(*) total_doc,COALESCE(SUM(status='POSTED'),0) posted_doc,COALESCE(SUM(status='REVERSED'),0) reversed_doc FROM erp_gr_production");
?>
<style>
  .grpo-hero{background:linear-gradient(135deg,#0f766e,#2563eb);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,118,110,.18)}
  .grpo-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.grpo-hero p{margin:0;opacity:.92}
  .grpo-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .grpo-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.grpo-kpi strong{display:block;font-size:24px;margin-top:6px;color:#111827}.grpo-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}
  #dtb_gr_from_production td,#dtb_gr_from_production th{font-size:12px;vertical-align:middle}.grpo-action-buttons{white-space:nowrap;min-width:84px}.select2-container{width:100%!important}.required-label:after{content:' *';color:#dd4b39}
  #modal_create_grpo .modal-body{max-height:calc(100vh - 210px);overflow-y:auto;overflow-x:hidden}#modal_create_grpo .modal-footer{position:sticky;bottom:0;background:#fff;z-index:2;border-top:1px solid #e5e7eb}
</style>
<section class="content-header">
  <h1><?=prod_h('production_gr_from_order', 'GR from Production Order');?> <small>SAP PP Movement 101</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=prod_h('common_home', 'Home');?></a></li><li class="active"><?=prod_h('production_gr_from_order', 'GR from Production Order');?></li></ol>
</section>
<section class="content">
  <div class="grpo-hero">
    <div class="row">
      <div class="col-md-8"><h1>Production Goods Receipt</h1><p>Terima hasil produksi dari Production Confirmation ke gudang, lengkap dengan trace bahan baku, lot, dan dokumen BC asal.</p></div>
      <div class="col-md-4 text-right"><?php if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") { ?><button type="button" id="btn_open_create_grpo" class="btn btn-warning"><i class="fa fa-download"></i> <?=wh_h(wh_t('common_post', 'Post'));?> GR Production</button><?php } ?></div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-4"><div class="grpo-kpi"><i class="fa fa-file-text-o"></i><span>Total GR</span><strong><?=number_format((float)$kpi->total_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-4"><div class="grpo-kpi"><i class="fa fa-check"></i><span>Posted</span><strong><?=number_format((float)$kpi->posted_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-4"><div class="grpo-kpi"><i class="fa fa-undo"></i><span>Reversed</span><strong><?=number_format((float)$kpi->reversed_doc,0,',','.');?></strong></div></div>
  </div>
  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> GR Production</h3></div>
    <div class="box-body">
      <form class="form-horizontal" id="filter_grpo" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label>
          <div class="col-lg-2"><div class="input-group date grpo-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date grpo-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=wh_h(wh_t('common_plant', 'Plant'));?></label>
          <div class="col-lg-2"><select id="filter_plant_id" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <label class="control-label col-lg-1"><?=wh_h(wh_t('common_status', 'Status'));?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option>POSTED</option><option>REVERSED</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></label>
          <div class="col-lg-3"><select id="filter_storage_location_id" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <label class="control-label col-lg-1"><?=wh_h(wh_t('common_search', 'Search'));?></label>
          <div class="col-lg-3"><input id="filter_keyword" class="form-control" placeholder="GR / Production Order / material / BC / lot"></div>
          <div class="col-lg-3"><button id="btn_filter_grpo" type="button" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button> <button id="btn_reset_grpo" type="button" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button> <button id="btn_export_grpo" type="button" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=prod_h('common_excel', 'Excel');?></button></div>
        </div>
      </form>
    </div>
  </div>
  <div class="box"><div class="box-body">
    <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div>
    <div class="table-responsive"><table id="dtb_gr_from_production" class="table table-bordered table-striped table-condensed" style="width:100%">
      <thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th>GR Document</th><th>Production Ref</th><th>Output Material</th><th><?=wh_h(wh_t('warehouse_qty', 'Qty'));?></th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th><?=wh_h(wh_t('warehouse_location', 'Location'));?></th><th><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></th><th>Trace</th><th><?=wh_h(wh_t('common_status', 'Status'));?></th><th><?=wh_h(wh_t('warehouse_user', 'User'));?></th></tr></thead>
      <tbody></tbody>
    </table></div>
  </div></div>

  <div id="modal_create_grpo" class="modal fade"><div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content">
    <form id="form_create_grpo">
      <div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Post GR from Production Order</h4></div>
      <div class="modal-body">
        <div class="alert alert-info">Pilih Production Confirmation yang sudah posted. Sistem akan membuat movement 101, stock layer hasil produksi, dan trace bahan baku asal.</div>
        <div class="row">
          <div class="col-md-5 form-group"><label class="required-label"><?=prod_h('production_confirmation', 'Production Confirmation');?></label><select id="id_confirmation" name="id_confirmation" class="form-control" required></select></div>
          <div class="col-md-2 form-group"><label class="required-label">Document Date</label><input name="document_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label class="required-label"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label><input name="posting_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-3 form-group"><label class="required-label">Receipt Qty</label><input type="number" step="0.00001" min="0.00001" id="receipt_qty" name="qty" class="form-control text-right" required></div>
        </div>
        <div id="confirmation_info"><div class="text-muted">Pilih Production Confirmation untuk melihat remaining qty dan trace.</div></div>
        <div class="row">
          <div class="col-md-3 form-group"><label class="required-label"><?=wh_h(wh_t('common_plant', 'Plant'));?></label><select id="plant_id" name="plant_id" class="form-control" required><option value=""><?=prod_h('production_select_plant', 'Select Plant');?></option><?php foreach($modalPlants as $p){ ?><option value="<?=intval($p->id);?>" data-code="<?=htmlspecialchars($p->plant_code,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label class="required-label"><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></label><select id="storage_location_id" name="storage_location_id" class="form-control" required><option value=""><?=prod_h('production_select_sloc', 'Select SLoc');?></option><?php foreach($modalStorageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>" data-code="<?=htmlspecialchars($s->storage_code,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></label><select id="storage_bin_id" name="storage_bin_id" class="form-control"><option value="">Tanpa Bin</option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=htmlspecialchars($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label class="required-label"><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></label><select name="stock_type" class="form-control" required><option value="UNRESTRICTED"><?=wh_h(wh_t('warehouse_unrestricted', 'Unrestricted'));?></option><option value="QUALITY"><?=wh_h(wh_t('warehouse_quality_inspection', 'Quality Inspection'));?></option><option value="BLOCKED"><?=wh_h(wh_t('warehouse_blocked', 'Blocked'));?></option></select></div>
        </div>
        <div class="form-group"><label>Header Text</label><input name="remarks" class="form-control" placeholder="Catatan GR / batch produksi / QC"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_cancel', 'Cancel'));?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> GR 101</button></div>
    </form>
  </div></div></div>

  <div id="modal_detail_grpo" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">GR Production Detail</h4></div><div class="modal-body" id="isi_detail_grpo"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showGrpoError(m){$('.isi_warning_delete').text(m||<?=prod_js('production_gr_process_failed', 'GR Production failed to process.');?>);$('.error_data_delete').fadeIn();}
function grpoFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),plant_id:$('#filter_plant_id').val(),storage_location_id:$('#filter_storage_location_id').val(),status:$('#filter_status').val(),keyword:$('#filter_keyword').val()};}
function filterSLoc(){var p=$('#plant_id').val();$('#storage_location_id option').each(function(){var pid=$(this).data('plant-id');$(this).toggle(!pid||!p||String(pid)===String(p));});filterBin();}
function filterBin(){var s=$('#storage_location_id').val();$('#storage_bin_id option').each(function(){var sid=$(this).data('storage-location-id');$(this).toggle(!sid||!s||String(sid)===String(s));});}
$(function(){
  if($.fn.datepicker){$('.grpo-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_plant_id,#filter_storage_location_id,#filter_status,#plant_id,#storage_location_id,#storage_bin_id,select[name=stock_type]').select2({width:'100%'});
    $('#id_confirmation').select2({width:'100%',dropdownParent:$('#modal_create_grpo'),placeholder:'Cari confirmation / production order...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/gr_from_production_order/gr_from_production_order_action.php?act=confirmation_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
  }
  var dt=$('#dtb_gr_from_production').DataTable({bProcessing:true,bServerSide:true,pageLength:25,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[5,9],className:'text-right'},{width:'42px',targets:0},{width:'86px',targets:1}],ajax:{url:'<?=base_admin();?>modul/gr_from_production_order/gr_from_production_order_data.php',type:'post',data:function(d){$.extend(d,grpoFilters());},error:function(xhr){console.log(xhr);showGrpoError(<?=prod_js('production_gr_load_failed', 'GR Production data failed to load.');?>);}}});
  $('#btn_open_create_grpo').on('click',function(){$('#form_create_grpo')[0].reset();$('#id_confirmation').empty().trigger('change');$('#confirmation_info').html('<div class="text-muted">Pilih Production Confirmation untuk melihat remaining qty dan trace.</div>');$('#modal_create_grpo').modal({backdrop:'static',keyboard:false});});
  $('#id_confirmation').on('select2:select',function(e){var d=e.params.data;$('#receipt_qty').val(d.remaining_qty||0);$.post('<?=base_admin();?>modul/gr_from_production_order/gr_from_production_order_action.php?act=confirmation_info',{id:d.id},function(html){$('#confirmation_info').html(html);});});
  $('#plant_id').on('change',filterSLoc);$('#storage_location_id').on('change',filterBin);
  $('#form_create_grpo').on('submit',function(e){e.preventDefault();var btn=$(this).find('button[type=submit]');Swal.fire({title:'Post GR 101?',text:'Hasil produksi akan masuk ke stock dan trace bahan asal akan dikunci.',icon:'question',showCancelButton:true,confirmButtonText:'Post'}).then(function(r){if(!r.isConfirmed)return;btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=wh_h(wh_t('common_post', 'Post'));?>ing...');$.ajax({url:'<?=base_admin();?>modul/gr_from_production_order/gr_from_production_order_action.php?act=post',type:'POST',data:$('#form_create_grpo').serialize(),dataType:'json',success:function(res){if(res.status==='good'){$('#modal_create_grpo').modal('hide');dt.draw(false);}else showGrpoError(res.error_message);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> GR 101');},error:function(xhr){showGrpoError(xhr.responseText);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> GR 101');}});});});
  $('#btn_filter_grpo').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});$('#btn_reset_grpo').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_plant_id,#filter_storage_location_id,#filter_status').val('').trigger('change');dt.draw();});
  $('#btn_export_grpo').on('click',function(){window.location='<?=base_admin();?>modul/gr_from_production_order/gr_from_production_order_action.php?act=excel&'+$.param(grpoFilters());});
  $(document).on('click','.btn-detail-grpo',function(){$.post('<?=base_admin();?>modul/gr_from_production_order/gr_from_production_order_action.php?act=detail',{id:$(this).data('id')},function(html){$('#isi_detail_grpo').html(html);$('#modal_detail_grpo').modal('show');}).fail(function(){showGrpoError(<?=prod_js('production_gr_detail_failed', 'GR detail failed to open.');?>);});});
	  $(document).on('click','.btn-reverse-grpo',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:'Reverse GR?',input:'text',inputLabel:'Reason reversal '+no,showCancelButton:true,confirmButtonText:'Reverse',inputValidator:function(v){return !v?<?=prod_js('production_reason_required', 'Reason is required');?>:undefined;}}).then(function(r){if(!r.isConfirmed)return;$.post('<?=base_admin();?>modul/gr_from_production_order/gr_from_production_order_action.php?act=reverse',{id:id,reason:r.value},function(res){if(res.status==='good')dt.draw(false);else showGrpoError(res.error_message);},'json');});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
