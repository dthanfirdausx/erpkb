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
                   FROM erp_other_goods_issue h
                   LEFT JOIN (
                     SELECT issue_id,SUM(amount) AS total_amount
                     FROM erp_other_goods_issue_detail
                     GROUP BY issue_id
                   ) ds ON ds.issue_id=h.id");
?>
<style>
  .ogi-hero{background:linear-gradient(135deg,#0f766e,#0f172a);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,118,110,.18)}
  .ogi-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.ogi-hero p{margin:0;opacity:.92}
  .ogi-kpi{border-radius:12px;background:#fff;border:1px solid #edf2f7;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .ogi-kpi span{color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.ogi-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}.ogi-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}
  #dtb_other_goods_issue td,#dtb_other_goods_issue th{font-size:12px;vertical-align:middle}
  .ogi-action-buttons{white-space:nowrap;min-width:112px}.ogi-action-buttons .btn{margin-right:3px}
  .ogi-items th{background:#f5f5f5}.ogi-items td,.ogi-items th{font-size:12px;vertical-align:middle!important}.ogi-items .form-control{height:30px;padding:4px 6px;font-size:12px}
  .ogi-stock-preview{max-height:150px;overflow:auto;min-width:360px}.ogi-stock-preview table td,.ogi-stock-preview table th{font-size:11px!important}
  .select2-container{width:100%!important}.required-label:after{content:' *';color:#dd4b39}.ogi-help{color:#64748b;margin-right:10px}
  #modal_create_ogi .modal-dialog{margin-top:18px;margin-bottom:18px}
  #modal_create_ogi .modal-body{max-height:calc(100vh - 210px);overflow-y:auto;overflow-x:hidden}
  #ogi_item_area{max-height:430px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:6px;background:#fff}
  #modal_create_ogi .modal-footer{position:sticky;bottom:0;background:#fff;z-index:2;border-top:1px solid #e5e7eb}
</style>
<section class="content-header">
  <h1>Other Goods Issue <small>SAP MM Movement Type 291</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Other Goods Issue</li>
  </ol>
</section>
<section class="content">
  <div class="ogi-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Other Goods Issue Workbench</h1>
        <p>Posting pengeluaran material lain-lain dengan FIFO stock layer, material document 291, jurnal otomatis, dan trace dokumen pabean.</p>
      </div>
      <div class="col-md-4 text-right">
        <?php if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") { ?>
          <button type="button" id="btn_open_create_ogi" class="btn btn-warning"><i class="fa fa-sign-out"></i> Create Issue 291</button>
        <?php } ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="ogi-kpi"><i class="fa fa-file-text-o"></i><span>Total Issue</span><strong><?=number_format((float)$kpi->total_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="ogi-kpi"><i class="fa fa-check"></i><span>Posted</span><strong><?=number_format((float)$kpi->posted_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="ogi-kpi"><i class="fa fa-undo"></i><span>Reversed</span><strong><?=number_format((float)$kpi->reversed_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="ogi-kpi"><i class="fa fa-money"></i><span>Total Amount</span><strong><?=number_format((float)$kpi->total_amount,2,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Other Goods Issue</h3></div>
    <div class="box-body">
      <form class="form-horizontal" id="filter_ogi" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=wh_h(wh_t('common_status', 'Status'));?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option>POSTED</option><option>REVERSED</option></select></div>
          <label class="control-label col-lg-1">Reason</label>
          <div class="col-lg-2"><select id="filter_reason_code" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="INTERNAL_USE">Internal Use</option><option value="ADJUSTMENT_ISSUE">Adjustment Issue</option><option value="DONATION">Donation</option><option value="DEMO">Demo</option><option value="TESTING">Testing</option><option value="MANAGEMENT_APPROVAL">Management Approval</option><option value="OTHER">Other</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('common_search', 'Search'));?></label>
          <div class="col-lg-5"><input id="filter_keyword" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_search_issue_trace', 'Issue no / material / no aju / no dokpab / no BPB / lot'));?>"></div>
          <div class="col-lg-5">
            <button type="button" id="btn_filter_ogi" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button>
            <button type="button" id="btn_reset_ogi" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button>
            <button type="button" id="btn_excel_ogi" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=wh_h(wh_t('common_download_excel', 'Download Excel'));?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_other_goods_issue" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th>Issue Doc</th><th><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></th><th>Other</th><th>Source Location</th><th>Reason</th><th>Items</th><th>Total Qty</th><th>Total Amount</th><th>Customs Trace</th><th><?=wh_h(wh_t('common_status', 'Status'));?></th><th>Created By</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_create_ogi" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content">
    <form id="form_create_ogi">
      <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Other Goods Issue 291</h4></div>
      <div class="modal-body">
        <div class="alert alert-info">Gunakan untuk pengeluaran material lain-lain yang tidak masuk production, cost center, asset, scrap, sample, atau return vendor. Sistem mengambil stock FIFO dan menyimpan asal BC/BPB per layer.</div>
        <div class="row">
          <div class="col-md-3 form-group"><label class="required-label">Purpose</label><select name="reason_code" class="form-control mandatory-ogi" required><option value="INTERNAL_USE">Internal Use</option><option value="ADJUSTMENT_ISSUE">Adjustment Issue</option><option value="DONATION">Donation</option><option value="DEMO">Demo</option><option value="TESTING">Testing</option><option value="MANAGEMENT_APPROVAL">Management Approval</option><option value="OTHER">Other</option></select></div>
          <div class="col-md-3 form-group"><label class="required-label">Purpose Text</label><input name="reason_text" class="form-control mandatory-ogi" value="Other goods issue" required></div>
          <div class="col-md-2 form-group"><label class="required-label">Document Date</label><input name="document_date" class="form-control date-field mandatory-ogi" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label class="required-label"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label><input name="posting_date" class="form-control date-field mandatory-ogi" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label><?=wh_h(wh_t('warehouse_reference', 'Reference'));?></label><input name="reference_no" class="form-control" placeholder="BA other / memo"></div>
        </div>
        <div class="row">
          <div class="col-md-2 form-group"><label>Source Plant</label><select id="plant_id" name="plant_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_plant', 'Semua Plant'));?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label>Source Storage Location</label><select id="storage_location_id" name="storage_location_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_sloc', 'Semua SLoc'));?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label>Source Storage Bin</label><select id="storage_bin_id" name="storage_bin_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_bin', 'Semua Bin'));?></option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=htmlspecialchars($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-2 form-group"><label>Issue Category</label><select name="issue_category" class="form-control"><option value="OPERATIONAL">Operational</option><option value="NON_OPERATIONAL">Non Operational</option><option value="DEMO">Demo</option><option value="TESTING">Testing</option><option value="DONATION">Donation</option><option value="OTHER">Other</option></select></div>
          <div class="col-md-2 form-group"><label>Recipient Type</label><select name="recipient_type" class="form-control"><option value="INTERNAL">Internal</option><option value="EXTERNAL">External</option><option value="CUSTOMER">Customer</option><option value="VENDOR">Vendor</option><option value="OTHER">Other</option></select></div>
          <div class="col-md-4 form-group"><label>Recipient Name</label><input name="recipient_name" class="form-control" placeholder="Departemen / PIC / pihak penerima"></div>
        </div>
        <div class="clearfix" style="margin-bottom:8px">
          <button type="button" id="btn_add_ogi_item" class="btn btn-default btn-sm"><i class="fa fa-plus"></i> Add Material</button>
          <span class="text-muted" style="margin-left:8px">Mandatory item: material dan issue qty. Trace pabean terisi otomatis dari stock layer.</span>
        </div>
        <div id="ogi_item_area" class="table-responsive">
          <table class="table table-bordered table-condensed ogi-items" style="margin-bottom:0">
            <thead><tr><th style="width:36px">#</th><th style="min-width:280px"><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th style="width:110px">Issue Qty</th><th style="width:80px"><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th style="min-width:360px">FIFO Stock Preview</th><th style="min-width:180px">Remark</th><th style="width:48px"></th></tr></thead>
            <tbody id="ogi_item_body"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <span id="ogi_submit_help" class="ogi-help">Pilih reason dan minimal satu item valid.</span>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_cancel', 'Cancel'));?></button>
        <button type="submit" id="btn_post_ogi" class="btn btn-primary" disabled><i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Issue 291</button>
      </div>
    </form>
  </div></div></div>

  <div id="modal_detail_ogi" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Other Goods Issue Trace Detail</h4></div><div class="modal-body" id="isi_detail_ogi"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showOgiError(m){$('.isi_warning_delete').text(m||'Other Goods Issue gagal diproses.');$('.error_data_delete').fadeIn();}
function renumberOgiItems(){
  $('#ogi_item_body tr').each(function(i){$(this).find('.ogi-row-no').text(i+1);});
}
function validateOgiForm(){
  var ok=true, rowOk=0;
  $('.mandatory-ogi').each(function(){if(!$(this).val())ok=false;});
  $('#ogi_item_body tr').each(function(){
    var material=$(this).find('.ogi-material').val();
    var qty=parseFloat($(this).find('.ogi-qty').val())||0;
    if(material && qty>0) rowOk++;
    if((material && qty<=0) || (!material && qty>0)) ok=false;
  });
  if(rowOk===0)ok=false;
  $('#btn_post_ogi').prop('disabled',!ok);
  $('#ogi_submit_help').text(ok?'Siap posting movement 291.':'Pilih reason dan minimal satu item valid.');
}
function reloadOgiPreview(row){
  var material=row.find('.ogi-material').val();
  var preview=row.find('.ogi-stock-preview');
  if(!material){preview.html('<span class="text-muted">Pilih material.</span>');return;}
  preview.html('<span class="text-muted"><i class="fa fa-spinner fa-spin"></i> Memuat stock layer...</span>');
  $.post('<?=base_admin();?>modul/other_goods_issue/other_goods_issue_action.php?act=stock_preview',{
    material_code:material,
    plant_id:$('#plant_id').val(),
    storage_location_id:$('#storage_location_id').val(),
    storage_bin_id:$('#storage_bin_id').val()
  },function(html){preview.html(html);}).fail(function(){preview.html('<span class="text-danger">Preview stock gagal dimuat.</span>');});
}
function initOgiMaterialSelect(row){
  row.find('.ogi-material').select2({width:'100%',dropdownParent:$('#modal_create_ogi'),placeholder:'Cari material stock...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/other_goods_issue/other_goods_issue_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||'',plant_id:$('#plant_id').val(),storage_location_id:$('#storage_location_id').val(),storage_bin_id:$('#storage_bin_id').val()};},processResults:function(d){return{results:d.results||[]};}}})
    .on('select2:select',function(e){var data=e.params.data||{};row.find('.ogi-uom').val(data.uom||'');reloadOgiPreview(row);validateOgiForm();})
    .on('change',validateOgiForm);
}
function addOgiItem(){
  var row=$('<tr>'+
    '<td class="text-center ogi-row-no"></td>'+
    '<td><select name="material_code[]" class="form-control ogi-material"></select></td>'+
    '<td><input type="number" min="0" step="0.00001" name="qty[]" class="form-control text-right ogi-qty" placeholder="0.00000"></td>'+
    '<td><input type="text" class="form-control ogi-uom" readonly></td>'+
    '<td><div class="ogi-stock-preview"><span class="text-muted">Pilih material.</span></div></td>'+
    '<td><input name="item_remarks[]" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_item_note_placeholder', 'Catatan item'));?>"></td>'+
    '<td class="text-center"><button type="button" class="btn btn-danger btn-xs btn-remove-ogi-item"><i class="fa fa-trash"></i></button></td>'+
  '</tr>');
  $('#ogi_item_body').append(row);
  initOgiMaterialSelect(row);
  renumberOgiItems();
  validateOgiForm();
}
function refreshAllOgiPreview(){
  $('#ogi_item_body tr').each(function(){reloadOgiPreview($(this));});
}
$(function(){
  if($.fn.datepicker){$('.filter-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_status,#filter_reason_code,#plant_id,#storage_location_id,#storage_bin_id').select2({width:'100%'});
  }
  var dt=$('#dtb_other_goods_issue').DataTable({bProcessing:true,bServerSide:true,pageLength:25,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[7,8,9],className:'text-right'},{width:'42px',targets:0},{width:'125px',targets:1}],ajax:{url:'<?=base_admin();?>modul/other_goods_issue/other_goods_issue_data.php',type:'post',data:function(d){d.tgl_awal=$('#filter_tgl_awal').val();d.tgl_akhir=$('#filter_tgl_akhir').val();d.status=$('#filter_status').val();d.reason_code=$('#filter_reason_code').val();d.keyword=$('#filter_keyword').val();},error:function(xhr){console.log(xhr);showOgiError('Data Other Goods Issue gagal dimuat.');}}});
  $('#btn_open_create_ogi').on('click',function(){if($('#ogi_item_body tr').length===0)addOgiItem();$('#modal_create_ogi').modal({backdrop:'static',keyboard:false});validateOgiForm();});
  $('#btn_add_ogi_item').on('click',addOgiItem);
  $(document).on('click','.btn-remove-ogi-item',function(){$(this).closest('tr').remove();renumberOgiItems();validateOgiForm();});
  $(document).on('keyup change','.mandatory-ogi,.ogi-qty',validateOgiForm);
  $('#plant_id').on('change',function(){var p=$(this).val();$('#storage_location_id option').each(function(){var plant=$(this).data('plant-id');$(this).toggle(!plant||!p||String(plant)===String(p));});if(p&&$('#storage_location_id option:selected').data('plant-id')&&String($('#storage_location_id option:selected').data('plant-id'))!==String(p))$('#storage_location_id').val('').trigger('change.select2');refreshAllOgiPreview();});
  $('#storage_location_id').on('change',function(){var loc=$(this).val();$('#storage_bin_id option').each(function(){var optionLoc=$(this).data('storage-location-id');$(this).toggle(!optionLoc||!loc||String(optionLoc)===String(loc));});if(loc&&$('#storage_bin_id option:selected').data('storage-location-id')&&String($('#storage_bin_id option:selected').data('storage-location-id'))!==String(loc))$('#storage_bin_id').val('').trigger('change.select2');refreshAllOgiPreview();});
  $('#storage_bin_id').on('change',refreshAllOgiPreview);
  $('#btn_filter_ogi').on('click',function(){dt.draw();});
  $('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_ogi').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_status,#filter_reason_code').val('').trigger('change');dt.draw();});
  $('#btn_excel_ogi').on('click',function(){var url='<?=base_admin();?>modul/other_goods_issue/other_goods_issue_action.php?act=excel&tgl_awal='+encodeURIComponent($('#filter_tgl_awal').val())+'&tgl_akhir='+encodeURIComponent($('#filter_tgl_akhir').val())+'&status='+encodeURIComponent($('#filter_status').val()||'')+'&reason_code='+encodeURIComponent($('#filter_reason_code').val()||'')+'&keyword='+encodeURIComponent($('#filter_keyword').val()||'');window.location.href=url;});
  $('#form_create_ogi').on('submit',function(e){e.preventDefault();validateOgiForm();if($('#btn_post_ogi').prop('disabled'))return;var btn=$('#btn_post_ogi');Swal.fire({title:'Post Issue 291?',text:'Stock akan dikurangi, jurnal biaya other goods issue dibuat, dan trace BC/BPB dikunci.',icon:'question',showCancelButton:true,confirmButtonText:'Post'}).then(function(result){if(!result.isConfirmed)return;btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=wh_h(wh_t('common_post', 'Post'));?>ing...');$.ajax({url:'<?=base_admin();?>modul/other_goods_issue/other_goods_issue_action.php?act=post',type:'POST',data:$('#form_create_ogi').serialize(),dataType:'json',success:function(res){if(res.status==='good'){$('#modal_create_ogi').modal('hide');$('#form_create_ogi')[0].reset();$('#plant_id,#storage_location_id,#storage_bin_id').val('').trigger('change');$('#ogi_item_body').empty();dt.draw(false);Swal.fire(<?=json_encode(wh_t('common_success', 'Success'));?>,'Other Goods Issue '+res.issue_no+' berhasil diposting.','success');}else{showOgiError(res.error_message);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Issue 291');}},error:function(xhr){showOgiError(xhr.responseText);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Issue 291');}});});});
  $(document).on('click','.btn-detail-ogi',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/other_goods_issue/other_goods_issue_action.php?act=detail',{id:id},function(html){$('#isi_detail_ogi').html(html);$('#modal_detail_ogi').modal('show');}).fail(function(){showOgiError('Detail issue gagal dibuka.');});});
  $(document).on('click','.btn-reversal-ogi',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:'Reversal Issue 292?',input:'text',inputLabel:'Reason reversal '+no,inputPlaceholder:<?=json_encode(wh_t('warehouse_reversal_reason_placeholder', 'Alasan reversal wajib diisi'));?>,showCancelButton:true,confirmButtonText:'Reversal',inputValidator:function(v){return !v?<?=json_encode(wh_t('warehouse_reason_required', 'Reason wajib diisi'));?>:undefined;}}).then(function(result){if(!result.isConfirmed)return;$.ajax({url:'<?=base_admin();?>modul/other_goods_issue/other_goods_issue_action.php?act=reversal',type:'POST',dataType:'json',data:{id:id,reason:result.value},success:function(res){if(res.status==='good'){Swal.fire(<?=json_encode(wh_t('common_success', 'Success'));?>,'Reversal 292 berhasil','success');dt.draw(false);}else{Swal.fire(<?=json_encode(wh_t('common_error', 'Error'));?>,res.error_message||<?=json_encode(wh_t('warehouse_reversal_failed', 'Reversal gagal'));?>,'error');}},error:function(xhr){Swal.fire(<?=json_encode(wh_t('common_error', 'Error'));?>,xhr.responseText,'error');}});});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
