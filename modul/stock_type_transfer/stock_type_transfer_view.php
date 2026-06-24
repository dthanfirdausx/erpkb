<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$plants = $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$storageLocations = $db->query("SELECT s.id,s.storage_code,s.storage_name,s.plant_id,p.plant_code FROM erp_storage_location s JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$storageBins = $db->query("SELECT b.id,b.bin_code,b.bin_name,b.storage_location_id,s.storage_code FROM erp_storage_bin b JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code");
$kpi = $db->fetch("SELECT COUNT(*) AS total_doc,
                          COALESCE(SUM(status='POSTED'),0) AS posted_doc,
                          COALESCE(SUM(status='REVERSED'),0) AS reversed_doc,
                          COALESCE(SUM(ds.total_qty),0) AS total_qty
                   FROM erp_stock_type_transfer h
                   LEFT JOIN (
                     SELECT transfer_id,SUM(qty) AS total_qty
                     FROM erp_stock_type_transfer_detail
                     GROUP BY transfer_id
                   ) ds ON ds.transfer_id=h.id
                   WHERE h.posting_date BETWEEN ? AND ?",
                   array($defaultFrom, $defaultTo));
?>
<style>
  .stt-hero{background:linear-gradient(135deg,#1d4ed8,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(29,78,216,.18)}
  .stt-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.stt-hero p{margin:0;opacity:.92}
  .stt-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .stt-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.stt-kpi strong{display:block;font-size:24px;margin-top:6px;color:#111827}.stt-kpi i{float:right;font-size:26px;color:#2563eb;opacity:.55}
  #dtb_stock_type_transfer td,#dtb_stock_type_transfer th{font-size:12px;vertical-align:middle}
  .stt-action-buttons{white-space:nowrap;min-width:118px}.stt-items td,.stt-items th{font-size:12px;vertical-align:middle!important}.stt-items .form-control{height:30px;padding:4px 6px;font-size:12px}
  .stt-stock-preview{max-height:150px;overflow:auto;min-width:360px}.stt-stock-preview table td,.stt-stock-preview table th{font-size:11px!important}
  .select2-container{width:100%!important}.required-label:after{content:' *';color:#dd4b39}.stt-help{color:#64748b;margin-right:10px}
  #modal_create_stt .modal-dialog{margin-top:18px;margin-bottom:18px}
  #modal_create_stt .modal-body{max-height:calc(100vh - 210px);overflow-y:auto;overflow-x:hidden}
  #stt_item_area{max-height:430px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:6px;background:#fff}
  #modal_create_stt .modal-footer{position:sticky;bottom:0;background:#fff;z-index:2;border-top:1px solid #e5e7eb}
</style>
<section class="content-header">
  <h1>Stock Type Transfer <small>SAP MM Stock Type Change</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Stock Type Transfer</li>
  </ol>
</section>
<section class="content">
  <div class="stt-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Stock Type Transfer Workbench</h1>
        <p>Transfer stock antar stock type dalam lokasi/bin yang sama. Movement otomatis mengikuti SAP style: 321/322, 343/344, 349/350, dengan trace dokumen BC/BPB tetap terbawa.</p>
      </div>
      <div class="col-md-4 text-right">
        <?php if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") { ?>
          <button type="button" id="btn_open_create_stt" class="btn btn-warning"><i class="fa fa-plus"></i> Create Stock Type Transfer</button>
        <?php } ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="stt-kpi"><i class="fa fa-exchange"></i><span>Total Transfer</span><strong><?=number_format((float)$kpi->total_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="stt-kpi"><i class="fa fa-check"></i><span>Posted</span><strong><?=number_format((float)$kpi->posted_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="stt-kpi"><i class="fa fa-undo"></i><span>Reversed</span><strong><?=number_format((float)$kpi->reversed_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="stt-kpi"><i class="fa fa-cubes"></i><span>Total Qty</span><strong><?=number_format((float)$kpi->total_qty,2,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Stock Type Transfer</h3></div>
    <div class="box-body">
      <form class="form-horizontal" id="filter_stt" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label>
          <div class="col-lg-2"><div class="input-group date stt-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date stt-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=wh_h(wh_t('common_status', 'Status'));?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option>POSTED</option><option>REVERSED</option></select></div>
          <label class="control-label col-lg-1">SLoc</label>
          <div class="col-lg-2"><select id="filter_source_storage_location_id" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2">Source Type</label>
          <div class="col-lg-2"><select id="filter_source_stock_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="UNRESTRICTED"><?=wh_h(wh_t('warehouse_unrestricted', 'Unrestricted'));?></option><option value="QUALITY"><?=wh_h(wh_t('warehouse_quality_inspection', 'Quality Inspection'));?></option><option value="BLOCKED"><?=wh_h(wh_t('warehouse_blocked', 'Blocked'));?></option></select></div>
          <label class="control-label col-lg-1">Dest Type</label>
          <div class="col-lg-2"><select id="filter_destination_stock_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="UNRESTRICTED"><?=wh_h(wh_t('warehouse_unrestricted', 'Unrestricted'));?></option><option value="QUALITY"><?=wh_h(wh_t('warehouse_quality_inspection', 'Quality Inspection'));?></option><option value="BLOCKED"><?=wh_h(wh_t('warehouse_blocked', 'Blocked'));?></option></select></div>
          <label class="control-label col-lg-1"><?=wh_h(wh_t('common_search', 'Search'));?></label>
          <div class="col-lg-3"><input id="filter_keyword" class="form-control" placeholder="Transfer no / material / no aju / no dokpab / no BPB"></div>
          <div class="col-lg-1">
            <button type="button" id="btn_filter_stt" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button>
          </div>
        </div>
        <div class="form-group">
          <div class="col-lg-offset-2 col-lg-10">
            <button type="button" id="btn_reset_stt" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button>
            <button type="button" id="btn_excel_stt" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Excel</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_stock_type_transfer" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th>Transfer Doc</th><th><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></th><th>Source</th><th>Destination</th><th>Reason</th><th>Items</th><th>Total Qty</th><th>Total Amount</th><th>Trace</th><th><?=wh_h(wh_t('common_status', 'Status'));?></th><th>Created By</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_create_stt" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content">
    <form id="form_create_stt">
      <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Stock Type Transfer</h4></div>
      <div class="modal-body">
        <div class="alert alert-info">Stock type transfer mengubah status stock dalam lokasi/bin yang sama, misalnya Quality Inspection ke Unrestricted (321) atau Unrestricted ke Blocked (344). Tidak ada jurnal biaya karena nilai inventory tidak berpindah ownership.</div>
        <div class="row">
          <div class="col-md-2 form-group"><label class="required-label">Document Date</label><input name="document_date" class="form-control date-field mandatory-stt" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label class="required-label"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label><input name="posting_date" class="form-control date-field mandatory-stt" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label><?=wh_h(wh_t('warehouse_reference', 'Reference'));?></label><input name="reference_no" class="form-control" placeholder="Memo / request"></div>
          <div class="col-md-3 form-group"><label class="required-label">Reason</label><select name="reason_code" class="form-control mandatory-stt"><option value="QUALITY_RELEASE">Quality Release</option><option value="QUALITY_HOLD">Quality Hold</option><option value="BLOCK_STOCK">Block Stock</option><option value="UNBLOCK_STOCK">Unblock Stock</option><option value="STOCK_RECLASS">Stock Reclassification</option><option value="OTHER">Other</option></select></div>
          <div class="col-md-3 form-group"><label class="required-label">Reason Text</label><input name="reason_text" class="form-control mandatory-stt" value="Stock type transfer" required></div>
        </div>
        <div class="row">
          <div class="col-md-3 form-group"><label class="required-label"><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></label><select id="source_storage_location_id" name="source_storage_location_id" class="form-control mandatory-stt"><option value="">Pilih Storage Location</option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select><input type="hidden" id="destination_storage_location_id" name="destination_storage_location_id"></div>
          <div class="col-md-3 form-group"><label><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></label><select id="source_storage_bin_id" name="source_storage_bin_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_bin', 'Semua Bin'));?></option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=htmlspecialchars($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select><input type="hidden" id="destination_storage_bin_id" name="destination_storage_bin_id"></div>
          <div class="col-md-3 form-group"><label class="required-label">Source Stock Type</label><select id="source_stock_type" name="source_stock_type" class="form-control mandatory-stt"><option value="">Pilih Source Type</option><option value="UNRESTRICTED">Unrestricted Use</option><option value="QUALITY"><?=wh_h(wh_t('warehouse_quality_inspection', 'Quality Inspection'));?></option><option value="BLOCKED">Blocked Stock</option></select></div>
          <div class="col-md-3 form-group"><label class="required-label">Destination Stock Type</label><select id="destination_stock_type" name="destination_stock_type" class="form-control mandatory-stt"><option value="">Pilih Destination Type</option><option value="UNRESTRICTED">Unrestricted Use</option><option value="QUALITY"><?=wh_h(wh_t('warehouse_quality_inspection', 'Quality Inspection'));?></option><option value="BLOCKED">Blocked Stock</option></select></div>
        </div>
        <div class="clearfix" style="margin-bottom:8px">
          <button type="button" id="btn_add_stt_item" class="btn btn-default btn-sm"><i class="fa fa-plus"></i> Add Material</button>
          <span class="text-muted" style="margin-left:8px">Mandatory item: material dan transfer qty. FIFO layer dan dokumen pabean otomatis ikut.</span>
        </div>
        <div id="stt_item_area" class="table-responsive">
          <table class="table table-bordered table-condensed stt-items" style="margin-bottom:0">
            <thead><tr><th style="width:36px">#</th><th style="min-width:280px"><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th style="width:120px">Transfer Qty</th><th style="width:80px"><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th style="min-width:360px">FIFO Source Layer Preview</th><th style="min-width:180px">Remark</th><th style="width:48px"></th></tr></thead>
            <tbody id="stt_item_body"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <span id="stt_submit_help" class="stt-help">Lengkapi field mandatory dan minimal satu item valid.</span>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_cancel', 'Cancel'));?></button>
        <button type="submit" id="btn_post_stt" class="btn btn-primary" disabled><i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Stock Type Transfer</button>
      </div>
    </form>
  </div></div></div>

  <div id="modal_detail_stt" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Stock Type Transfer Detail</h4></div><div class="modal-body" id="isi_detail_stt"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showSttError(m){$('.isi_warning_delete').text(m||'Stock Type Transfer gagal diproses.');$('.error_data_delete').fadeIn();}
function renumberSttItems(){$('#stt_item_body tr').each(function(i){$(this).find('.stt-row-no').text(i+1);});}
function sttMovementCode(){
  var src=$('#source_stock_type').val(),dst=$('#destination_stock_type').val();
  var map={'QUALITY>UNRESTRICTED':'321','UNRESTRICTED>QUALITY':'322','BLOCKED>UNRESTRICTED':'343','UNRESTRICTED>BLOCKED':'344','BLOCKED>QUALITY':'349','QUALITY>BLOCKED':'350'};
  return map[src+'>'+dst]||'';
}
function syncSttDestination(){
  $('#destination_storage_location_id').val($('#source_storage_location_id').val());
  $('#destination_storage_bin_id').val($('#source_storage_bin_id').val()||'');
}
function validateSttForm(){
  syncSttDestination();
  var ok=true,rowOk=0,src=$('#source_storage_location_id').val(),srcType=$('#source_stock_type').val(),dstType=$('#destination_stock_type').val(),move=sttMovementCode();
  $('.mandatory-stt').each(function(){if(!$(this).val())ok=false;});
  if(!src||!srcType||!dstType||srcType===dstType||!move)ok=false;
  $('#stt_item_body tr').each(function(){var material=$(this).find('.stt-material').val(),qty=parseFloat($(this).find('.stt-qty').val())||0;if(material&&qty>0)rowOk++;if((material&&qty<=0)||(!material&&qty>0))ok=false;});
  if(rowOk===0)ok=false;
  $('#btn_post_stt').prop('disabled',!ok);
  $('#stt_submit_help').text(ok?'Siap posting movement '+move+'.':'Lengkapi mandatory, stock type harus berbeda, dan minimal satu item valid.');
}
function filterBin(selectId, slocId){
  var loc=$(slocId).val();
  $(selectId+' option').each(function(){var optionLoc=$(this).data('storage-location-id');$(this).toggle(!optionLoc||!loc||String(optionLoc)===String(loc));});
  var selected=$(selectId+' option:selected');
  if(loc&&selected.data('storage-location-id')&&String(selected.data('storage-location-id'))!==String(loc))$(selectId).val('').trigger('change.select2');
}
function reloadSttPreview(row){
  var material=row.find('.stt-material').val(),preview=row.find('.stt-stock-preview');
  if(!material){preview.html('<span class="text-muted">Pilih material.</span>');return;}
  preview.html('<span class="text-muted"><i class="fa fa-spinner fa-spin"></i> Memuat source layer...</span>');
  $.post('<?=base_admin();?>modul/stock_type_transfer/stock_type_transfer_action.php?act=stock_preview',{material_code:material,source_storage_location_id:$('#source_storage_location_id').val(),source_storage_bin_id:$('#source_storage_bin_id').val(),source_stock_type:$('#source_stock_type').val()},function(html){preview.html(html);}).fail(function(){preview.html('<span class="text-danger">Preview gagal dimuat.</span>');});
}
function initSttMaterialSelect(row){
  row.find('.stt-material').select2({width:'100%',dropdownParent:$('#modal_create_stt'),placeholder:'Cari material source stock...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/stock_type_transfer/stock_type_transfer_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||'',source_storage_location_id:$('#source_storage_location_id').val(),source_storage_bin_id:$('#source_storage_bin_id').val(),source_stock_type:$('#source_stock_type').val()};},processResults:function(d){return{results:d.results||[]};}}})
    .on('select2:select',function(e){var data=e.params.data||{};row.find('.stt-uom').val(data.uom||'');reloadSttPreview(row);validateSttForm();})
    .on('change',validateSttForm);
}
function addSttItem(){
  var row=$('<tr><td class="text-center stt-row-no"></td><td><select name="material_code[]" class="form-control stt-material"></select></td><td><input type="number" min="0" step="0.00001" name="qty[]" class="form-control text-right stt-qty" placeholder="0.00000"></td><td><input type="text" class="form-control stt-uom" readonly></td><td><div class="stt-stock-preview"><span class="text-muted">Pilih material.</span></div></td><td><input name="item_remarks[]" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_item_note_placeholder', 'Catatan item'));?>"></td><td class="text-center"><button type="button" class="btn btn-danger btn-xs btn-remove-stt-item"><i class="fa fa-trash"></i></button></td></tr>');
  $('#stt_item_body').append(row);initSttMaterialSelect(row);renumberSttItems();validateSttForm();
}
function refreshAllSttPreview(){$('#stt_item_body tr').each(function(){reloadSttPreview($(this));});}
$(function(){
  if($.fn.datepicker){$('.stt-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_status,#filter_source_storage_location_id,#filter_source_stock_type,#filter_destination_stock_type,#source_storage_location_id,#source_storage_bin_id,#source_stock_type,#destination_stock_type').select2({width:'100%'});}
  var dt=$('#dtb_stock_type_transfer').DataTable({bProcessing:true,bServerSide:true,pageLength:25,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[7,8,9],className:'text-right'},{width:'42px',targets:0},{width:'120px',targets:1}],ajax:{url:'<?=base_admin();?>modul/stock_type_transfer/stock_type_transfer_data.php',type:'post',data:function(d){d.tgl_awal=$('#filter_tgl_awal').val();d.tgl_akhir=$('#filter_tgl_akhir').val();d.status=$('#filter_status').val();d.source_storage_location_id=$('#filter_source_storage_location_id').val();d.source_stock_type=$('#filter_source_stock_type').val();d.destination_stock_type=$('#filter_destination_stock_type').val();d.keyword=$('#filter_keyword').val();},error:function(xhr){console.log(xhr);showSttError('Data Stock Type Transfer gagal dimuat.');}}});
  $('#btn_open_create_stt').on('click',function(){if($('#stt_item_body tr').length===0)addSttItem();$('#modal_create_stt').modal({backdrop:'static',keyboard:false});validateSttForm();});
  $('#btn_add_stt_item').on('click',addSttItem);
  $(document).on('click','.btn-remove-stt-item',function(){$(this).closest('tr').remove();renumberSttItems();validateSttForm();});
  $(document).on('keyup change','.mandatory-stt,.stt-qty',validateSttForm);
  $('#source_storage_location_id').on('change',function(){filterBin('#source_storage_bin_id','#source_storage_location_id');syncSttDestination();refreshAllSttPreview();validateSttForm();});
  $('#source_storage_bin_id,#source_stock_type,#destination_stock_type').on('change',function(){syncSttDestination();refreshAllSttPreview();validateSttForm();});
  $('#btn_filter_stt').on('click',function(){dt.draw();});
  $('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_stt').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_status,#filter_source_storage_location_id,#filter_source_stock_type,#filter_destination_stock_type').val('').trigger('change');dt.draw();});
  $('#btn_excel_stt').on('click',function(){var url='<?=base_admin();?>modul/stock_type_transfer/stock_type_transfer_action.php?act=excel&tgl_awal='+encodeURIComponent($('#filter_tgl_awal').val())+'&tgl_akhir='+encodeURIComponent($('#filter_tgl_akhir').val())+'&status='+encodeURIComponent($('#filter_status').val()||'')+'&source_storage_location_id='+encodeURIComponent($('#filter_source_storage_location_id').val()||'')+'&source_stock_type='+encodeURIComponent($('#filter_source_stock_type').val()||'')+'&destination_stock_type='+encodeURIComponent($('#filter_destination_stock_type').val()||'')+'&keyword='+encodeURIComponent($('#filter_keyword').val()||'');window.location.href=url;});
  $('#form_create_stt').on('submit',function(e){e.preventDefault();validateSttForm();if($('#btn_post_stt').prop('disabled'))return;var btn=$('#btn_post_stt'),move=sttMovementCode();Swal.fire({title:'Post Movement '+move+'?',text:'Stock type akan berubah, lokasi/bin tetap sama.',icon:'question',showCancelButton:true,confirmButtonText:'Post'}).then(function(result){if(!result.isConfirmed)return;btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=wh_h(wh_t('common_post', 'Post'));?>ing...');$.ajax({url:'<?=base_admin();?>modul/stock_type_transfer/stock_type_transfer_action.php?act=post',type:'POST',data:$('#form_create_stt').serialize(),dataType:'json',success:function(res){if(res.status==='good'){$('#modal_create_stt').modal('hide');$('#form_create_stt')[0].reset();$('#source_storage_location_id,#source_storage_bin_id,#source_stock_type,#destination_stock_type').val('').trigger('change');$('#stt_item_body').empty();dt.draw(false);Swal.fire(<?=json_encode(wh_t('common_success', 'Success'));?>,'Stock Type Transfer '+res.transfer_no+' berhasil diposting.','success');}else{showSttError(res.error_message);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Stock Type Transfer');}},error:function(xhr){showSttError(xhr.responseText);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Stock Type Transfer');}});});});
  $(document).on('click','.btn-detail-stt',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/stock_type_transfer/stock_type_transfer_action.php?act=detail',{id:id},function(html){$('#isi_detail_stt').html(html);$('#modal_detail_stt').modal('show');}).fail(function(){showSttError('Detail transfer gagal dibuka.');});});
  $(document).on('click','.btn-reversal-stt',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:'Reversal Stock Type Transfer?',input:'text',inputLabel:'Reason reversal '+no,inputPlaceholder:<?=json_encode(wh_t('warehouse_reversal_reason_placeholder', 'Alasan reversal wajib diisi'));?>,showCancelButton:true,confirmButtonText:'Reversal',inputValidator:function(v){return !v?<?=json_encode(wh_t('warehouse_reason_required', 'Reason wajib diisi'));?>:undefined;}}).then(function(result){if(!result.isConfirmed)return;$.ajax({url:'<?=base_admin();?>modul/stock_type_transfer/stock_type_transfer_action.php?act=reversal',type:'POST',dataType:'json',data:{id:id,reason:result.value},success:function(res){if(res.status==='good'){Swal.fire(<?=json_encode(wh_t('common_success', 'Success'));?>,'Reversal berhasil','success');dt.draw(false);}else{Swal.fire(<?=json_encode(wh_t('common_error', 'Error'));?>,res.error_message||<?=json_encode(wh_t('warehouse_reversal_failed', 'Reversal gagal'));?>,'error');}},error:function(xhr){Swal.fire(<?=json_encode(wh_t('common_error', 'Error'));?>,xhr.responseText,'error');}});});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
