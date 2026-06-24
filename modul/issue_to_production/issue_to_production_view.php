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
                          COALESCE(SUM(status='REVERSED'),0) AS reversed_doc
                   FROM erp_issue_production");
?>
<style>
  .gip-hero{background:linear-gradient(135deg,#581c87,#1d4ed8);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(88,28,135,.18)}
  .gip-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.gip-hero p{margin:0;opacity:.92}
  .gip-kpi{border-radius:12px;background:#fff;border:1px solid #edf2f7;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .gip-kpi span{color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.gip-kpi strong{display:block;font-size:25px;margin-top:6px;color:#111827}.gip-kpi i{float:right;font-size:26px;color:#6d28d9;opacity:.55}
  #dtb_issue_to_production td,#dtb_issue_to_production th{font-size:12px;vertical-align:middle}.gip-action-buttons{white-space:nowrap;min-width:92px}.gip-action-buttons .btn{margin-right:3px}
  .gip-items th{background:#f5f5f5}.gip-items td,.gip-items th{font-size:12px;vertical-align:middle!important}.gip-items .form-control{height:30px;padding:4px 6px;font-size:12px}
  .select2-container{width:100%!important}.required-label:after{content:' *';color:#dd4b39}.gip-help{color:#64748b;margin-right:10px}
  #modal_create_gip .modal-dialog{margin-top:18px;margin-bottom:18px}
  #modal_create_gip .modal-body{max-height:calc(100vh - 210px);overflow-y:auto;overflow-x:hidden}
  #gip_item_area{max-height:390px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:6px;padding:8px;background:#fff}
  #gip_item_area .table-responsive{margin-bottom:0}
  #modal_create_gip .modal-footer{position:sticky;bottom:0;background:#fff;z-index:2;border-top:1px solid #e5e7eb}
</style>
<section class="content-header">
  <h1>Issue to Production <small>SAP MM Movement 261</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Issue to Production</li>
  </ol>
</section>
<section class="content">
  <div class="gip-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Goods Issue to Production Workbench</h1>
        <p>Posting pemakaian bahan baku ke Production Order dengan FIFO stock layer dan trace lot/batch serta dokumen pabean asal.</p>
      </div>
      <div class="col-md-4 text-right">
        <?php if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") { ?>
          <button type="button" id="btn_open_create_gip" class="btn btn-warning"><i class="fa fa-industry"></i> Create Issue 261</button>
        <?php } ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-4"><div class="gip-kpi"><i class="fa fa-file-text-o"></i><span>Total Issue</span><strong><?=number_format((float)$kpi->total_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-4"><div class="gip-kpi"><i class="fa fa-check"></i><span>Posted</span><strong><?=number_format((float)$kpi->posted_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-4"><div class="gip-kpi"><i class="fa fa-undo"></i><span>Reversed</span><strong><?=number_format((float)$kpi->reversed_doc,0,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Issue to Production</h3></div>
    <div class="box-body">
      <form class="form-horizontal" id="filter_gip" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=wh_h(wh_t('common_status', 'Status'));?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option>POSTED</option><option>REVERSED</option></select></div>
          <label class="control-label col-lg-1">Prod.</label>
          <div class="col-lg-2"><input id="filter_production_no" class="form-control" placeholder="No produksi"></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('common_search', 'Search'));?></label>
          <div class="col-lg-5"><input id="filter_keyword" class="form-control" placeholder="Issue no / material / no aju / no dokpab / lot / no BPB"></div>
          <div class="col-lg-5"><button type="button" id="btn_filter_gip" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button> <button type="button" id="btn_reset_gip" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button></div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_issue_to_production" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th>Issue Doc</th><th><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></th><th>Production Order</th><th>Source Location</th><th>Reason</th><th>Items</th><th>Total Qty</th><th>Customs Trace</th><th><?=wh_h(wh_t('common_status', 'Status'));?></th><th>Created By</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_create_gip" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content">
    <form id="form_create_gip">
      <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Issue to Production 261</h4></div>
      <div class="modal-body">
        <div class="alert alert-info">Pilih Production Order, sistem memuat bahan baku rencana dan mengambil stok FIFO dari stock layer. Trace lot/batch, No Aju, dan No Dokpab otomatis tersimpan per layer.</div>
        <div class="row">
          <div class="col-md-4 form-group"><label class="required-label">Production Order</label><select id="production_id" name="production_id" class="form-control" required></select></div>
          <div class="col-md-2 form-group"><label class="required-label">Document Date</label><input name="document_date" class="form-control date-field mandatory-gip" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label class="required-label"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label><input name="posting_date" class="form-control date-field mandatory-gip" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-4 form-group"><label><?=wh_h(wh_t('warehouse_reference', 'Reference'));?></label><input name="reference_no" class="form-control" placeholder="Shift / batch produksi / operator / referensi"></div>
        </div>
        <div class="row">
          <div class="col-md-2 form-group"><label>Source Plant</label><select id="plant_id" name="plant_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_plant', 'Semua Plant'));?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label>Source Storage Location</label><select id="storage_location_id" name="storage_location_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_sloc', 'Semua SLoc'));?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label>Source Storage Bin</label><select id="storage_bin_id" name="storage_bin_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_bin', 'Semua Bin'));?></option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=htmlspecialchars($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-2 form-group"><label class="required-label">Reason Code</label><select name="reason_code" class="form-control mandatory-gip" required><option value="PROD_CONSUMPTION">Production Consumption</option><option value="REWORK">Rework</option><option value="TRIAL">Trial Production</option><option value="ADJUSTMENT">Adjustment</option></select></div>
          <div class="col-md-2 form-group"><label class="required-label">Reason Text</label><input name="reason_text" class="form-control mandatory-gip" value="Pemakaian bahan baku produksi" required></div>
        </div>
        <div id="gip_item_area"><div class="text-muted">Pilih Production Order untuk memuat bahan baku.</div></div>
      </div>
      <div class="modal-footer">
        <span id="gip_submit_help" class="gip-help">Pilih production order dan minimal satu item valid.</span>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_cancel', 'Cancel'));?></button>
        <button type="submit" id="btn_post_gip" class="btn btn-primary" disabled><i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Issue 261</button>
      </div>
    </form>
  </div></div></div>

  <div id="modal_detail_gip" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Issue to Production Trace Detail</h4></div><div class="modal-body" id="isi_detail_gip"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showGipError(m){$('.isi_warning_delete').text(m||'Issue to Production gagal diproses.');$('.error_data_delete').fadeIn();}
function validateGipForm(){
  var ok=true, checked=0;
  $('.mandatory-gip').each(function(){if(!$(this).val())ok=false;});
  if(!$('#production_id').val()) ok=false;
  $('input[name="selected_line[]"]:checked').each(function(){checked++;var id=$(this).val(),qty=parseFloat($('input[name="issue_qty['+id+']"]').val())||0;if(qty<=0)ok=false;});
  if(checked===0)ok=false;
  $('#btn_post_gip').prop('disabled',!ok);
  $('#gip_submit_help').text(ok?'Siap posting movement 261.':'Pilih production order dan minimal satu item valid.');
}
function reloadGipItems(){
  var prod=$('#production_id').val();
  if(!prod) return;
  $('#gip_item_area').html('<div class="text-muted"><i class="fa fa-spinner fa-spin"></i> Memuat bahan baku dan stock layer...</div>');
  $.post('<?=base_admin();?>modul/issue_to_production/issue_to_production_action.php?act=production_items',{
    production_id:prod,
    plant_id:$('#plant_id').val(),
    storage_location_id:$('#storage_location_id').val(),
    storage_bin_id:$('#storage_bin_id').val()
  },function(html){$('#gip_item_area').html(html);validateGipForm();}).fail(function(){showGipError('Item production order gagal dimuat.');});
}
$(function(){
  if($.fn.datepicker){$('.filter-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_status,#plant_id,#storage_location_id,#storage_bin_id').select2({width:'100%'});
    $('#production_id').select2({width:'100%',dropdownParent:$('#modal_create_gip'),placeholder:'Cari Production Order...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/issue_to_production/issue_to_production_action.php?act=production_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
  }
  var dt=$('#dtb_issue_to_production').DataTable({bProcessing:true,bServerSide:true,pageLength:25,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[7,8],className:'text-right'},{width:'42px',targets:0},{width:'92px',targets:1}],ajax:{url:'<?=base_admin();?>modul/issue_to_production/issue_to_production_data.php',type:'post',data:function(d){d.tgl_awal=$('#filter_tgl_awal').val();d.tgl_akhir=$('#filter_tgl_akhir').val();d.status=$('#filter_status').val();d.production_no=$('#filter_production_no').val();d.keyword=$('#filter_keyword').val();},error:function(xhr){console.log(xhr);showGipError('Data Issue to Production gagal dimuat.');}}});
  $('#btn_open_create_gip').on('click',function(){$('#modal_create_gip').modal({backdrop:'static',keyboard:false});validateGipForm();});
  $('#production_id').on('select2:select',reloadGipItems);
  $('#plant_id').on('change',function(){var p=$(this).val();$('#storage_location_id option').each(function(){var plant=$(this).data('plant-id');$(this).toggle(!plant||!p||String(plant)===String(p));});if(p&&$('#storage_location_id option:selected').data('plant-id')&&String($('#storage_location_id option:selected').data('plant-id'))!==String(p))$('#storage_location_id').val('').trigger('change.select2');reloadGipItems();});
  $('#storage_location_id').on('change',function(){var loc=$(this).val();$('#storage_bin_id option').each(function(){var optionLoc=$(this).data('storage-location-id');$(this).toggle(!optionLoc||!loc||String(optionLoc)===String(loc));});if(loc&&$('#storage_bin_id option:selected').data('storage-location-id')&&String($('#storage_bin_id option:selected').data('storage-location-id'))!==String(loc))$('#storage_bin_id').val('').trigger('change.select2');reloadGipItems();});
  $('#storage_bin_id').on('change',reloadGipItems);
  $(document).on('keyup change','.mandatory-gip,.issue-qty,input[name="selected_line[]"]',validateGipForm);
  $('#btn_filter_gip').on('click',function(){dt.draw();});$('#filter_keyword,#filter_production_no').on('keyup',function(e){if(e.keyCode===13)dt.draw();});$('#btn_reset_gip').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword,#filter_production_no').val('');$('#filter_status').val('').trigger('change');dt.draw();});
  $('#form_create_gip').on('submit',function(e){e.preventDefault();validateGipForm();if($('#btn_post_gip').prop('disabled'))return;var btn=$('#btn_post_gip');Swal.fire({title:'Post Issue 261?',text:'Stock bahan baku akan dikurangi dan trace pabean akan dikunci.',icon:'question',showCancelButton:true,confirmButtonText:'Post'}).then(function(result){if(!result.isConfirmed)return;btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=wh_h(wh_t('common_post', 'Post'));?>ing...');$.ajax({url:'<?=base_admin();?>modul/issue_to_production/issue_to_production_action.php?act=post',type:'POST',data:$('#form_create_gip').serialize(),dataType:'json',success:function(res){if(res.status==='good'){$('#modal_create_gip').modal('hide');$('#form_create_gip')[0].reset();$('#production_id,#plant_id,#storage_location_id,#storage_bin_id').val('').trigger('change');$('#gip_item_area').html('<div class="text-muted">Pilih Production Order untuk memuat bahan baku.</div>');dt.draw(false);}else{showGipError(res.error_message);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Issue 261');}},error:function(xhr){showGipError(xhr.responseText);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Issue 261');}});});});
  $(document).on('click','.btn-detail-gip',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/issue_to_production/issue_to_production_action.php?act=detail',{id:id},function(html){$('#isi_detail_gip').html(html);$('#modal_detail_gip').modal('show');}).fail(function(){showGipError('Detail issue gagal dibuka.');});});
  $(document).on('click','.btn-reversal-gip',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:'Reversal Issue 262?',input:'text',inputLabel:'Reason reversal '+no,inputPlaceholder:<?=json_encode(wh_t('warehouse_reversal_reason_placeholder', 'Alasan reversal wajib diisi'));?>,showCancelButton:true,confirmButtonText:'Reversal',inputValidator:function(v){return !v?<?=json_encode(wh_t('warehouse_reason_required', 'Reason wajib diisi'));?>:undefined;}}).then(function(result){if(!result.isConfirmed)return;$.ajax({url:'<?=base_admin();?>modul/issue_to_production/issue_to_production_action.php?act=reversal',type:'POST',dataType:'json',data:{id:id,reason:result.value},success:function(res){if(res.status==='good'){Swal.fire(<?=json_encode(wh_t('common_success', 'Success'));?>,'Reversal 262 berhasil','success');dt.draw(false);}else{Swal.fire(<?=json_encode(wh_t('common_error', 'Error'));?>,res.error_message||<?=json_encode(wh_t('warehouse_reversal_failed', 'Reversal gagal'));?>,'error');}},error:function(xhr){Swal.fire(<?=json_encode(wh_t('common_error', 'Error'));?>,xhr.responseText,'error');}});});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
