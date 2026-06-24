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
                   FROM erp_scrap_issue h
                   LEFT JOIN (
                     SELECT issue_id,SUM(amount) AS total_amount
                     FROM erp_scrap_issue_detail
                     GROUP BY issue_id
                   ) ds ON ds.issue_id=h.id");
?>
<style>
  .scr-hero{background:linear-gradient(135deg,#0f766e,#0f172a);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,118,110,.18)}
  .scr-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.scr-hero p{margin:0;opacity:.92}
  .scr-kpi{border-radius:12px;background:#fff;border:1px solid #edf2f7;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .scr-kpi span{color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.scr-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}.scr-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}
  #dtb_scrap_issue td,#dtb_scrap_issue th{font-size:12px;vertical-align:middle}
  .scr-action-buttons{white-space:nowrap;min-width:112px}.scr-action-buttons .btn{margin-right:3px}
  .scr-items th{background:#f5f5f5}.scr-items td,.scr-items th{font-size:12px;vertical-align:middle!important}.scr-items .form-control{height:30px;padding:4px 6px;font-size:12px}
  .scr-stock-preview{max-height:150px;overflow:auto;min-width:360px}.scr-stock-preview table td,.scr-stock-preview table th{font-size:11px!important}
  .select2-container{width:100%!important}.required-label:after{content:' *';color:#dd4b39}.scr-help{color:#64748b;margin-right:10px}
  #modal_create_scr .modal-dialog{margin-top:18px;margin-bottom:18px}
  #modal_create_scr .modal-body{max-height:calc(100vh - 210px);overflow-y:auto;overflow-x:hidden}
  #scr_item_area{max-height:430px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:6px;background:#fff}
  #modal_create_scr .modal-footer{position:sticky;bottom:0;background:#fff;z-index:2;border-top:1px solid #e5e7eb}
</style>
<section class="content-header">
  <h1>Scrap Issue <small>SAP MM Movement Type 551</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Scrap Issue</li>
  </ol>
</section>
<section class="content">
  <div class="scr-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Goods Scrap Issue Workbench</h1>
        <p>Posting scrap material dengan FIFO stock layer, material document 551, jurnal otomatis, dan trace dokumen pabean.</p>
      </div>
      <div class="col-md-4 text-right">
        <?php if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") { ?>
          <button type="button" id="btn_open_create_scr" class="btn btn-warning"><i class="fa fa-building-o"></i> Create Issue 551</button>
        <?php } ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="scr-kpi"><i class="fa fa-file-text-o"></i><span>Total Issue</span><strong><?=number_format((float)$kpi->total_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="scr-kpi"><i class="fa fa-check"></i><span>Posted</span><strong><?=number_format((float)$kpi->posted_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="scr-kpi"><i class="fa fa-undo"></i><span>Reversed</span><strong><?=number_format((float)$kpi->reversed_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="scr-kpi"><i class="fa fa-money"></i><span>Total Amount</span><strong><?=number_format((float)$kpi->total_amount,2,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Scrap Issue</h3></div>
    <div class="box-body">
      <form class="form-horizontal" id="filter_scr" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=wh_h(wh_t('common_status', 'Status'));?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option>POSTED</option><option>REVERSED</option></select></div>
          <label class="control-label col-lg-1">Reason</label>
          <div class="col-lg-2"><select id="filter_reason_code" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="DAMAGED">Damaged</option><option value="EXPIRED">Expired</option><option value="QUALITY_REJECT">Quality Reject</option><option value="PROCESS_WASTE">Process Waste</option><option value="OBSOLETE">Obsolete</option><option value="OTHER">Other</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('common_search', 'Search'));?></label>
          <div class="col-lg-5"><input id="filter_keyword" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_search_issue_trace', 'Issue no / material / no aju / no dokpab / no BPB / lot'));?>"></div>
          <div class="col-lg-5">
            <button type="button" id="btn_filter_scr" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button>
            <button type="button" id="btn_reset_scr" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button>
            <button type="button" id="btn_excel_scr" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=wh_h(wh_t('common_download_excel', 'Download Excel'));?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_scrap_issue" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th>Issue Doc</th><th><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></th><th>Scrap</th><th>Source Location</th><th>Reason</th><th>Items</th><th>Total Qty</th><th>Total Amount</th><th>Customs Trace</th><th><?=wh_h(wh_t('common_status', 'Status'));?></th><th>Created By</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_create_scr" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content">
    <form id="form_create_scr">
      <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Scrap Issue 551</h4></div>
      <div class="modal-body">
        <div class="alert alert-info">Gunakan untuk material rusak, expired, quality reject, process waste, obsolete, atau scrap lain. Sistem mengambil stock FIFO dan menyimpan asal BC/BPB per layer.</div>
        <div class="row">
          <div class="col-md-3 form-group"><label class="required-label">Reason Code</label><select name="reason_code" class="form-control mandatory-scr" required><option value="DAMAGED">Damaged</option><option value="EXPIRED">Expired</option><option value="QUALITY_REJECT">Quality Reject</option><option value="PROCESS_WASTE">Process Waste</option><option value="OBSOLETE">Obsolete</option><option value="OTHER">Other</option></select></div>
          <div class="col-md-3 form-group"><label class="required-label">Reason Text</label><input name="reason_text" class="form-control mandatory-scr" value="Scrap material" required></div>
          <div class="col-md-2 form-group"><label class="required-label">Document Date</label><input name="document_date" class="form-control date-field mandatory-scr" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label class="required-label"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label><input name="posting_date" class="form-control date-field mandatory-scr" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label><?=wh_h(wh_t('warehouse_reference', 'Reference'));?></label><input name="reference_no" class="form-control" placeholder="BA scrap / memo"></div>
        </div>
        <div class="row">
          <div class="col-md-2 form-group"><label>Source Plant</label><select id="plant_id" name="plant_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_plant', 'Semua Plant'));?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label>Source Storage Location</label><select id="storage_location_id" name="storage_location_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_sloc', 'Semua SLoc'));?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label>Source Storage Bin</label><select id="storage_bin_id" name="storage_bin_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_bin', 'Semua Bin'));?></option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=htmlspecialchars($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-4 form-group"><label>Scrap Category</label><input name="scrap_category" class="form-control" placeholder="Contoh: Material rusak / reject produksi / expired"></div>
        </div>
        <div class="clearfix" style="margin-bottom:8px">
          <button type="button" id="btn_add_scr_item" class="btn btn-default btn-sm"><i class="fa fa-plus"></i> Add Material</button>
          <span class="text-muted" style="margin-left:8px">Mandatory item: material dan issue qty. Trace pabean terisi otomatis dari stock layer.</span>
        </div>
        <div id="scr_item_area" class="table-responsive">
          <table class="table table-bordered table-condensed scr-items" style="margin-bottom:0">
            <thead><tr><th style="width:36px">#</th><th style="min-width:280px"><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th style="width:110px">Issue Qty</th><th style="width:80px"><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th style="min-width:360px">FIFO Stock Preview</th><th style="min-width:180px">Remark</th><th style="width:48px"></th></tr></thead>
            <tbody id="scr_item_body"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <span id="scr_submit_help" class="scr-help">Pilih reason dan minimal satu item valid.</span>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_cancel', 'Cancel'));?></button>
        <button type="submit" id="btn_post_scr" class="btn btn-primary" disabled><i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Issue 551</button>
      </div>
    </form>
  </div></div></div>

  <div id="modal_detail_scr" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Scrap Issue Trace Detail</h4></div><div class="modal-body" id="isi_detail_scr"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showScrError(m){$('.isi_warning_delete').text(m||'Scrap Issue gagal diproses.');$('.error_data_delete').fadeIn();}
function renumberScrItems(){
  $('#scr_item_body tr').each(function(i){$(this).find('.scr-row-no').text(i+1);});
}
function validateScrForm(){
  var ok=true, rowOk=0;
  $('.mandatory-scr').each(function(){if(!$(this).val())ok=false;});
  $('#scr_item_body tr').each(function(){
    var material=$(this).find('.scr-material').val();
    var qty=parseFloat($(this).find('.scr-qty').val())||0;
    if(material && qty>0) rowOk++;
    if((material && qty<=0) || (!material && qty>0)) ok=false;
  });
  if(rowOk===0)ok=false;
  $('#btn_post_scr').prop('disabled',!ok);
  $('#scr_submit_help').text(ok?'Siap posting movement 551.':'Pilih reason dan minimal satu item valid.');
}
function reloadScrPreview(row){
  var material=row.find('.scr-material').val();
  var preview=row.find('.scr-stock-preview');
  if(!material){preview.html('<span class="text-muted">Pilih material.</span>');return;}
  preview.html('<span class="text-muted"><i class="fa fa-spinner fa-spin"></i> Memuat stock layer...</span>');
  $.post('<?=base_admin();?>modul/scrap_issue/scrap_issue_action.php?act=stock_preview',{
    material_code:material,
    plant_id:$('#plant_id').val(),
    storage_location_id:$('#storage_location_id').val(),
    storage_bin_id:$('#storage_bin_id').val()
  },function(html){preview.html(html);}).fail(function(){preview.html('<span class="text-danger">Preview stock gagal dimuat.</span>');});
}
function initScrMaterialSelect(row){
  row.find('.scr-material').select2({width:'100%',dropdownParent:$('#modal_create_scr'),placeholder:'Cari material stock...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/scrap_issue/scrap_issue_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||'',plant_id:$('#plant_id').val(),storage_location_id:$('#storage_location_id').val(),storage_bin_id:$('#storage_bin_id').val()};},processResults:function(d){return{results:d.results||[]};}}})
    .on('select2:select',function(e){var data=e.params.data||{};row.find('.scr-uom').val(data.uom||'');reloadScrPreview(row);validateScrForm();})
    .on('change',validateScrForm);
}
function addScrItem(){
  var row=$('<tr>'+
    '<td class="text-center scr-row-no"></td>'+
    '<td><select name="material_code[]" class="form-control scr-material"></select></td>'+
    '<td><input type="number" min="0" step="0.00001" name="qty[]" class="form-control text-right scr-qty" placeholder="0.00000"></td>'+
    '<td><input type="text" class="form-control scr-uom" readonly></td>'+
    '<td><div class="scr-stock-preview"><span class="text-muted">Pilih material.</span></div></td>'+
    '<td><input name="item_remarks[]" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_item_note_placeholder', 'Catatan item'));?>"></td>'+
    '<td class="text-center"><button type="button" class="btn btn-danger btn-xs btn-remove-scr-item"><i class="fa fa-trash"></i></button></td>'+
  '</tr>');
  $('#scr_item_body').append(row);
  initScrMaterialSelect(row);
  renumberScrItems();
  validateScrForm();
}
function refreshAllScrPreview(){
  $('#scr_item_body tr').each(function(){reloadScrPreview($(this));});
}
$(function(){
  if($.fn.datepicker){$('.filter-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_status,#filter_reason_code,#plant_id,#storage_location_id,#storage_bin_id').select2({width:'100%'});
  }
  var dt=$('#dtb_scrap_issue').DataTable({bProcessing:true,bServerSide:true,pageLength:25,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[7,8,9],className:'text-right'},{width:'42px',targets:0},{width:'125px',targets:1}],ajax:{url:'<?=base_admin();?>modul/scrap_issue/scrap_issue_data.php',type:'post',data:function(d){d.tgl_awal=$('#filter_tgl_awal').val();d.tgl_akhir=$('#filter_tgl_akhir').val();d.status=$('#filter_status').val();d.reason_code=$('#filter_reason_code').val();d.keyword=$('#filter_keyword').val();},error:function(xhr){console.log(xhr);showScrError('Data Scrap Issue gagal dimuat.');}}});
  $('#btn_open_create_scr').on('click',function(){if($('#scr_item_body tr').length===0)addScrItem();$('#modal_create_scr').modal({backdrop:'static',keyboard:false});validateScrForm();});
  $('#btn_add_scr_item').on('click',addScrItem);
  $(document).on('click','.btn-remove-scr-item',function(){$(this).closest('tr').remove();renumberScrItems();validateScrForm();});
  $(document).on('keyup change','.mandatory-scr,.scr-qty',validateScrForm);
  $('#plant_id').on('change',function(){var p=$(this).val();$('#storage_location_id option').each(function(){var plant=$(this).data('plant-id');$(this).toggle(!plant||!p||String(plant)===String(p));});if(p&&$('#storage_location_id option:selected').data('plant-id')&&String($('#storage_location_id option:selected').data('plant-id'))!==String(p))$('#storage_location_id').val('').trigger('change.select2');refreshAllScrPreview();});
  $('#storage_location_id').on('change',function(){var loc=$(this).val();$('#storage_bin_id option').each(function(){var optionLoc=$(this).data('storage-location-id');$(this).toggle(!optionLoc||!loc||String(optionLoc)===String(loc));});if(loc&&$('#storage_bin_id option:selected').data('storage-location-id')&&String($('#storage_bin_id option:selected').data('storage-location-id'))!==String(loc))$('#storage_bin_id').val('').trigger('change.select2');refreshAllScrPreview();});
  $('#storage_bin_id').on('change',refreshAllScrPreview);
  $('#btn_filter_scr').on('click',function(){dt.draw();});
  $('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_scr').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_status,#filter_reason_code').val('').trigger('change');dt.draw();});
  $('#btn_excel_scr').on('click',function(){var url='<?=base_admin();?>modul/scrap_issue/scrap_issue_action.php?act=excel&tgl_awal='+encodeURIComponent($('#filter_tgl_awal').val())+'&tgl_akhir='+encodeURIComponent($('#filter_tgl_akhir').val())+'&status='+encodeURIComponent($('#filter_status').val()||'')+'&reason_code='+encodeURIComponent($('#filter_reason_code').val()||'')+'&keyword='+encodeURIComponent($('#filter_keyword').val()||'');window.location.href=url;});
  $('#form_create_scr').on('submit',function(e){e.preventDefault();validateScrForm();if($('#btn_post_scr').prop('disabled'))return;var btn=$('#btn_post_scr');Swal.fire({title:'Post Issue 551?',text:'Stock akan dikurangi, jurnal biaya scrap dibuat, dan trace BC/BPB dikunci.',icon:'question',showCancelButton:true,confirmButtonText:'Post'}).then(function(result){if(!result.isConfirmed)return;btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=wh_h(wh_t('common_post', 'Post'));?>ing...');$.ajax({url:'<?=base_admin();?>modul/scrap_issue/scrap_issue_action.php?act=post',type:'POST',data:$('#form_create_scr').serialize(),dataType:'json',success:function(res){if(res.status==='good'){$('#modal_create_scr').modal('hide');$('#form_create_scr')[0].reset();$('#plant_id,#storage_location_id,#storage_bin_id').val('').trigger('change');$('#scr_item_body').empty();dt.draw(false);Swal.fire(<?=json_encode(wh_t('common_success', 'Success'));?>,'Scrap Issue '+res.issue_no+' berhasil diposting.','success');}else{showScrError(res.error_message);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Issue 551');}},error:function(xhr){showScrError(xhr.responseText);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Issue 551');}});});});
  $(document).on('click','.btn-detail-scr',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/scrap_issue/scrap_issue_action.php?act=detail',{id:id},function(html){$('#isi_detail_scr').html(html);$('#modal_detail_scr').modal('show');}).fail(function(){showScrError('Detail issue gagal dibuka.');});});
  $(document).on('click','.btn-reversal-scr',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:'Reversal Issue 552?',input:'text',inputLabel:'Reason reversal '+no,inputPlaceholder:<?=json_encode(wh_t('warehouse_reversal_reason_placeholder', 'Alasan reversal wajib diisi'));?>,showCancelButton:true,confirmButtonText:'Reversal',inputValidator:function(v){return !v?<?=json_encode(wh_t('warehouse_reason_required', 'Reason wajib diisi'));?>:undefined;}}).then(function(result){if(!result.isConfirmed)return;$.ajax({url:'<?=base_admin();?>modul/scrap_issue/scrap_issue_action.php?act=reversal',type:'POST',dataType:'json',data:{id:id,reason:result.value},success:function(res){if(res.status==='good'){Swal.fire(<?=json_encode(wh_t('common_success', 'Success'));?>,'Reversal 552 berhasil','success');dt.draw(false);}else{Swal.fire(<?=json_encode(wh_t('common_error', 'Error'));?>,res.error_message||<?=json_encode(wh_t('warehouse_reversal_failed', 'Reversal gagal'));?>,'error');}},error:function(xhr){Swal.fire(<?=json_encode(wh_t('common_error', 'Error'));?>,xhr.responseText,'error');}});});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
