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
                          COALESCE(SUM(h.status='POSTED'),0) AS posted_doc,
                          COALESCE(SUM(h.status='REVERSED'),0) AS reversed_doc,
                          COALESCE(SUM(ds.total_amount),0) AS total_amount
                   FROM erp_issue_cost_center h
                   LEFT JOIN (
                     SELECT issue_id,SUM(amount) AS total_amount
                     FROM erp_issue_cost_center_detail
                     GROUP BY issue_id
                   ) ds ON ds.issue_id=h.id");
?>
<style>
  .icc-hero{background:linear-gradient(135deg,#0f766e,#0f172a);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,118,110,.18)}
  .icc-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.icc-hero p{margin:0;opacity:.92}
  .icc-kpi{border-radius:12px;background:#fff;border:1px solid #edf2f7;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .icc-kpi span{color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.icc-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}.icc-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}
  #dtb_issue_to_cost_center td,#dtb_issue_to_cost_center th{font-size:12px;vertical-align:middle}
  .icc-action-buttons{white-space:nowrap;min-width:112px}.icc-action-buttons .btn{margin-right:3px}
  .icc-items th{background:#f5f5f5}.icc-items td,.icc-items th{font-size:12px;vertical-align:middle!important}.icc-items .form-control{height:30px;padding:4px 6px;font-size:12px}
  .icc-stock-preview{max-height:150px;overflow:auto;min-width:360px}.icc-stock-preview table td,.icc-stock-preview table th{font-size:11px!important}
  .select2-container{width:100%!important}.required-label:after{content:' *';color:#dd4b39}.icc-help{color:#64748b;margin-right:10px}
  #modal_create_icc .modal-dialog{margin-top:18px;margin-bottom:18px}
  #modal_create_icc .modal-body{max-height:calc(100vh - 210px);overflow-y:auto;overflow-x:hidden}
  #icc_item_area{max-height:430px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:6px;background:#fff}
  #modal_create_icc .modal-footer{position:sticky;bottom:0;background:#fff;z-index:2;border-top:1px solid #e5e7eb}
</style>
<section class="content-header">
  <h1>Issue to Cost Center <small>SAP MM Movement Type 201</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Issue to Cost Center</li>
  </ol>
</section>
<section class="content">
  <div class="icc-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Goods Issue to Cost Center Workbench</h1>
        <p>Posting pemakaian material non-produksi ke Cost Center dengan FIFO stock layer, material document 201, jurnal otomatis, dan trace dokumen pabean.</p>
      </div>
      <div class="col-md-4 text-right">
        <?php if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") { ?>
          <button type="button" id="btn_open_create_icc" class="btn btn-warning"><i class="fa fa-building-o"></i> Create Issue 201</button>
        <?php } ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="icc-kpi"><i class="fa fa-file-text-o"></i><span>Total Issue</span><strong><?=number_format((float)$kpi->total_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="icc-kpi"><i class="fa fa-check"></i><span>Posted</span><strong><?=number_format((float)$kpi->posted_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="icc-kpi"><i class="fa fa-undo"></i><span>Reversed</span><strong><?=number_format((float)$kpi->reversed_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="icc-kpi"><i class="fa fa-money"></i><span>Total Amount</span><strong><?=number_format((float)$kpi->total_amount,2,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Issue to Cost Center</h3></div>
    <div class="box-body">
      <form class="form-horizontal" id="filter_icc" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=wh_h(wh_t('common_status', 'Status'));?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option>POSTED</option><option>REVERSED</option></select></div>
          <label class="control-label col-lg-1">Cost Center</label>
          <div class="col-lg-2"><select id="filter_cost_center_id" class="form-control"></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('common_search', 'Search'));?></label>
          <div class="col-lg-5"><input id="filter_keyword" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_search_issue_trace', 'Issue no / material / no aju / no dokpab / no BPB / lot'));?>"></div>
          <div class="col-lg-5">
            <button type="button" id="btn_filter_icc" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button>
            <button type="button" id="btn_reset_icc" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button>
            <button type="button" id="btn_excel_icc" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=wh_h(wh_t('common_download_excel', 'Download Excel'));?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_issue_to_cost_center" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th>Issue Doc</th><th><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></th><th>Cost Center</th><th>Source Location</th><th>Reason</th><th>Items</th><th>Total Qty</th><th>Total Amount</th><th>Customs Trace</th><th><?=wh_h(wh_t('common_status', 'Status'));?></th><th>Created By</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_create_icc" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content">
    <form id="form_create_icc">
      <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Issue to Cost Center 201</h4></div>
      <div class="modal-body">
        <div class="alert alert-info">Gunakan untuk pemakaian material ke biaya operasional, maintenance, sample internal, atau kebutuhan cost center lain. Sistem mengambil stock FIFO dan menyimpan asal BC/BPB per layer.</div>
        <div class="row">
          <div class="col-md-4 form-group"><label class="required-label">Cost Center</label><select id="cost_center_id" name="cost_center_id" class="form-control mandatory-icc" required></select></div>
          <div class="col-md-2 form-group"><label class="required-label">Document Date</label><input name="document_date" class="form-control date-field mandatory-icc" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label class="required-label"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label><input name="posting_date" class="form-control date-field mandatory-icc" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-4 form-group"><label><?=wh_h(wh_t('warehouse_reference', 'Reference'));?></label><input name="reference_no" class="form-control" placeholder="WO maintenance / memo / referensi internal"></div>
        </div>
        <div class="row">
          <div class="col-md-2 form-group"><label>Source Plant</label><select id="plant_id" name="plant_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_plant', 'Semua Plant'));?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label>Source Storage Location</label><select id="storage_location_id" name="storage_location_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_sloc', 'Semua SLoc'));?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label>Source Storage Bin</label><select id="storage_bin_id" name="storage_bin_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_bin', 'Semua Bin'));?></option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=htmlspecialchars($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-2 form-group"><label class="required-label">Reason Code</label><select name="reason_code" class="form-control mandatory-icc" required><option value="COST_CENTER_CONSUMPTION">Cost Center Consumption</option><option value="MAINTENANCE">Maintenance</option><option value="SAMPLE_INTERNAL">Sample Internal</option><option value="OFFICE_USE">Office Use</option><option value="OTHER">Other</option></select></div>
          <div class="col-md-2 form-group"><label class="required-label">Reason Text</label><input name="reason_text" class="form-control mandatory-icc" value="Pemakaian material cost center" required></div>
        </div>
        <div class="clearfix" style="margin-bottom:8px">
          <button type="button" id="btn_add_icc_item" class="btn btn-default btn-sm"><i class="fa fa-plus"></i> Add Material</button>
          <span class="text-muted" style="margin-left:8px">Mandatory item: material dan issue qty. Trace pabean terisi otomatis dari stock layer.</span>
        </div>
        <div id="icc_item_area" class="table-responsive">
          <table class="table table-bordered table-condensed icc-items" style="margin-bottom:0">
            <thead><tr><th style="width:36px">#</th><th style="min-width:280px"><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th style="width:110px">Issue Qty</th><th style="width:80px"><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th style="min-width:360px">FIFO Stock Preview</th><th style="min-width:180px">Remark</th><th style="width:48px"></th></tr></thead>
            <tbody id="icc_item_body"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <span id="icc_submit_help" class="icc-help">Pilih cost center dan minimal satu item valid.</span>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_cancel', 'Cancel'));?></button>
        <button type="submit" id="btn_post_icc" class="btn btn-primary" disabled><i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Issue 201</button>
      </div>
    </form>
  </div></div></div>

  <div id="modal_detail_icc" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Issue to Cost Center Trace Detail</h4></div><div class="modal-body" id="isi_detail_icc"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showIccError(m){$('.isi_warning_delete').text(m||'Issue to Cost Center gagal diproses.');$('.error_data_delete').fadeIn();}
function iccSelect2Ajax(selector,parent){
  $(selector).select2({width:'100%',dropdownParent:parent||$(document.body),placeholder:'Cari Cost Center...',allowClear:true,ajax:{url:'<?=base_admin();?>modul/issue_to_cost_center/issue_to_cost_center_action.php?act=cost_center_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
}
function renumberIccItems(){
  $('#icc_item_body tr').each(function(i){$(this).find('.icc-row-no').text(i+1);});
}
function validateIccForm(){
  var ok=true, rowOk=0;
  $('.mandatory-icc').each(function(){if(!$(this).val())ok=false;});
  $('#icc_item_body tr').each(function(){
    var material=$(this).find('.icc-material').val();
    var qty=parseFloat($(this).find('.icc-qty').val())||0;
    if(material && qty>0) rowOk++;
    if((material && qty<=0) || (!material && qty>0)) ok=false;
  });
  if(rowOk===0)ok=false;
  $('#btn_post_icc').prop('disabled',!ok);
  $('#icc_submit_help').text(ok?'Siap posting movement 201.':'Pilih cost center dan minimal satu item valid.');
}
function reloadIccPreview(row){
  var material=row.find('.icc-material').val();
  var preview=row.find('.icc-stock-preview');
  if(!material){preview.html('<span class="text-muted">Pilih material.</span>');return;}
  preview.html('<span class="text-muted"><i class="fa fa-spinner fa-spin"></i> Memuat stock layer...</span>');
  $.post('<?=base_admin();?>modul/issue_to_cost_center/issue_to_cost_center_action.php?act=stock_preview',{
    material_code:material,
    plant_id:$('#plant_id').val(),
    storage_location_id:$('#storage_location_id').val(),
    storage_bin_id:$('#storage_bin_id').val()
  },function(html){preview.html(html);}).fail(function(){preview.html('<span class="text-danger">Preview stock gagal dimuat.</span>');});
}
function initIccMaterialSelect(row){
  row.find('.icc-material').select2({width:'100%',dropdownParent:$('#modal_create_icc'),placeholder:'Cari material stock...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/issue_to_cost_center/issue_to_cost_center_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||'',plant_id:$('#plant_id').val(),storage_location_id:$('#storage_location_id').val(),storage_bin_id:$('#storage_bin_id').val()};},processResults:function(d){return{results:d.results||[]};}}})
    .on('select2:select',function(e){var data=e.params.data||{};row.find('.icc-uom').val(data.uom||'');reloadIccPreview(row);validateIccForm();})
    .on('change',validateIccForm);
}
function addIccItem(){
  var row=$('<tr>'+
    '<td class="text-center icc-row-no"></td>'+
    '<td><select name="material_code[]" class="form-control icc-material"></select></td>'+
    '<td><input type="number" min="0" step="0.00001" name="qty[]" class="form-control text-right icc-qty" placeholder="0.00000"></td>'+
    '<td><input type="text" class="form-control icc-uom" readonly></td>'+
    '<td><div class="icc-stock-preview"><span class="text-muted">Pilih material.</span></div></td>'+
    '<td><input name="item_remarks[]" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_item_note_placeholder', 'Catatan item'));?>"></td>'+
    '<td class="text-center"><button type="button" class="btn btn-danger btn-xs btn-remove-icc-item"><i class="fa fa-trash"></i></button></td>'+
  '</tr>');
  $('#icc_item_body').append(row);
  initIccMaterialSelect(row);
  renumberIccItems();
  validateIccForm();
}
function refreshAllIccPreview(){
  $('#icc_item_body tr').each(function(){reloadIccPreview($(this));});
}
$(function(){
  if($.fn.datepicker){$('.filter-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_status,#plant_id,#storage_location_id,#storage_bin_id').select2({width:'100%'});
    iccSelect2Ajax('#filter_cost_center_id');
    iccSelect2Ajax('#cost_center_id',$('#modal_create_icc'));
  }
  var dt=$('#dtb_issue_to_cost_center').DataTable({bProcessing:true,bServerSide:true,pageLength:25,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[7,8,9],className:'text-right'},{width:'42px',targets:0},{width:'125px',targets:1}],ajax:{url:'<?=base_admin();?>modul/issue_to_cost_center/issue_to_cost_center_data.php',type:'post',data:function(d){d.tgl_awal=$('#filter_tgl_awal').val();d.tgl_akhir=$('#filter_tgl_akhir').val();d.status=$('#filter_status').val();d.cost_center_id=$('#filter_cost_center_id').val();d.keyword=$('#filter_keyword').val();},error:function(xhr){console.log(xhr);showIccError('Data Issue to Cost Center gagal dimuat.');}}});
  $('#btn_open_create_icc').on('click',function(){if($('#icc_item_body tr').length===0)addIccItem();$('#modal_create_icc').modal({backdrop:'static',keyboard:false});validateIccForm();});
  $('#btn_add_icc_item').on('click',addIccItem);
  $(document).on('click','.btn-remove-icc-item',function(){$(this).closest('tr').remove();renumberIccItems();validateIccForm();});
  $(document).on('keyup change','.mandatory-icc,.icc-qty',validateIccForm);
  $('#plant_id').on('change',function(){var p=$(this).val();$('#storage_location_id option').each(function(){var plant=$(this).data('plant-id');$(this).toggle(!plant||!p||String(plant)===String(p));});if(p&&$('#storage_location_id option:selected').data('plant-id')&&String($('#storage_location_id option:selected').data('plant-id'))!==String(p))$('#storage_location_id').val('').trigger('change.select2');refreshAllIccPreview();});
  $('#storage_location_id').on('change',function(){var loc=$(this).val();$('#storage_bin_id option').each(function(){var optionLoc=$(this).data('storage-location-id');$(this).toggle(!optionLoc||!loc||String(optionLoc)===String(loc));});if(loc&&$('#storage_bin_id option:selected').data('storage-location-id')&&String($('#storage_bin_id option:selected').data('storage-location-id'))!==String(loc))$('#storage_bin_id').val('').trigger('change.select2');refreshAllIccPreview();});
  $('#storage_bin_id').on('change',refreshAllIccPreview);
  $('#btn_filter_icc').on('click',function(){dt.draw();});
  $('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_icc').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_status').val('').trigger('change');$('#filter_cost_center_id').val(null).trigger('change');dt.draw();});
  $('#btn_excel_icc').on('click',function(){var url='<?=base_admin();?>modul/issue_to_cost_center/issue_to_cost_center_action.php?act=excel&tgl_awal='+encodeURIComponent($('#filter_tgl_awal').val())+'&tgl_akhir='+encodeURIComponent($('#filter_tgl_akhir').val())+'&status='+encodeURIComponent($('#filter_status').val()||'')+'&cost_center_id='+encodeURIComponent($('#filter_cost_center_id').val()||'')+'&keyword='+encodeURIComponent($('#filter_keyword').val()||'');window.location.href=url;});
  $('#form_create_icc').on('submit',function(e){e.preventDefault();validateIccForm();if($('#btn_post_icc').prop('disabled'))return;var btn=$('#btn_post_icc');Swal.fire({title:'Post Issue 201?',text:'Stock akan dikurangi, jurnal biaya cost center dibuat, dan trace BC/BPB dikunci.',icon:'question',showCancelButton:true,confirmButtonText:'Post'}).then(function(result){if(!result.isConfirmed)return;btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=wh_h(wh_t('common_post', 'Post'));?>ing...');$.ajax({url:'<?=base_admin();?>modul/issue_to_cost_center/issue_to_cost_center_action.php?act=post',type:'POST',data:$('#form_create_icc').serialize(),dataType:'json',success:function(res){if(res.status==='good'){$('#modal_create_icc').modal('hide');$('#form_create_icc')[0].reset();$('#cost_center_id,#plant_id,#storage_location_id,#storage_bin_id').val('').trigger('change');$('#icc_item_body').empty();dt.draw(false);Swal.fire(<?=json_encode(wh_t('common_success', 'Success'));?>,'Issue to Cost Center '+res.issue_no+' berhasil diposting.','success');}else{showIccError(res.error_message);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Issue 201');}},error:function(xhr){showIccError(xhr.responseText);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Issue 201');}});});});
  $(document).on('click','.btn-detail-icc',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/issue_to_cost_center/issue_to_cost_center_action.php?act=detail',{id:id},function(html){$('#isi_detail_icc').html(html);$('#modal_detail_icc').modal('show');}).fail(function(){showIccError('Detail issue gagal dibuka.');});});
  $(document).on('click','.btn-reversal-icc',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:'Reversal Issue 202?',input:'text',inputLabel:'Reason reversal '+no,inputPlaceholder:<?=json_encode(wh_t('warehouse_reversal_reason_placeholder', 'Alasan reversal wajib diisi'));?>,showCancelButton:true,confirmButtonText:'Reversal',inputValidator:function(v){return !v?<?=json_encode(wh_t('warehouse_reason_required', 'Reason wajib diisi'));?>:undefined;}}).then(function(result){if(!result.isConfirmed)return;$.ajax({url:'<?=base_admin();?>modul/issue_to_cost_center/issue_to_cost_center_action.php?act=reversal',type:'POST',dataType:'json',data:{id:id,reason:result.value},success:function(res){if(res.status==='good'){Swal.fire(<?=json_encode(wh_t('common_success', 'Success'));?>,'Reversal 202 berhasil','success');dt.draw(false);}else{Swal.fire(<?=json_encode(wh_t('common_error', 'Error'));?>,res.error_message||<?=json_encode(wh_t('warehouse_reversal_failed', 'Reversal gagal'));?>,'error');}},error:function(xhr){Swal.fire(<?=json_encode(wh_t('common_error', 'Error'));?>,xhr.responseText,'error');}});});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
