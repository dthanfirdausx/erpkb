<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$vendors = $db->query("SELECT kode_pemasok,nama FROM pemasok ORDER BY nama");
$plants = $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$storageLocations = $db->query("SELECT s.id,s.storage_code,s.storage_name,s.plant_id,p.plant_code FROM erp_storage_location s JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$kpi = $db->fetch("SELECT COUNT(*) total_doc,COALESCE(SUM(status='POSTED'),0) posted_doc,COALESCE(SUM(status='CANCELLED'),0) cancelled_doc FROM erp_vendor_return");
?>
<style>
  .rtv-hero{background:linear-gradient(135deg,#991b1b,#b45309);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(153,27,27,.18)}
  .rtv-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.rtv-hero p{margin:0;opacity:.9}
  .rtv-kpi{border-radius:12px;background:#fff;border:1px solid #edf2f7;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .rtv-kpi span{color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.rtv-kpi strong{display:block;font-size:25px;margin-top:6px;color:#111827}.rtv-kpi i{float:right;font-size:26px;color:#dd4b39;opacity:.55}
  #dtb_return_to_vendor td,#dtb_return_to_vendor th{font-size:12px;vertical-align:middle}.rtv-action-buttons{white-space:nowrap;min-width:86px}.rtv-action-buttons .btn{margin-right:3px}
  .rtv-items th{background:#f5f5f5}.rtv-items td,.rtv-items th{font-size:12px;vertical-align:middle!important}.rtv-items .form-control{height:30px;padding:4px 6px;font-size:12px}
  .select2-container{width:100%!important}.required-label:after{content:' *';color:#dd4b39}
</style>
<section class="content-header">
  <h1>Return to Vendor <small>SAP MM Return Delivery 122</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Return to Vendor</li>
  </ol>
</section>
<section class="content">
  <div class="rtv-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Return Delivery Workbench</h1>
        <p>Return barang ke vendor dengan referensi Goods Receipt, validasi stock layer, reason code, dan material movement type 122.</p>
      </div>
      <div class="col-md-4 text-right">
        <?php if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") { ?>
          <button type="button" id="btn_open_create_rtv" class="btn btn-warning"><i class="fa fa-reply"></i> Create Return</button>
        <?php } ?>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-4"><div class="rtv-kpi"><i class="fa fa-file-text-o"></i><span>Total Return</span><strong><?=intval($kpi->total_doc);?></strong></div></div>
    <div class="col-sm-4"><div class="rtv-kpi"><i class="fa fa-check"></i><span>Posted</span><strong><?=intval($kpi->posted_doc);?></strong></div></div>
    <div class="col-sm-4"><div class="rtv-kpi"><i class="fa fa-ban"></i><span>Cancelled</span><strong><?=intval($kpi->cancelled_doc);?></strong></div></div>
  </div>
  <div class="box">
    <div class="box-body">
      <form id="filter_rtv" class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1">Vendor</label>
          <div class="col-lg-3"><select id="filter_vendor" class="form-control"><option value="">Semua Vendor</option><?php foreach($vendors as $v){ ?><option value="<?=htmlspecialchars($v->kode_pemasok,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($v->kode_pemasok.' - '.$v->nama,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value="">Semua Status</option><option>POSTED</option><option>CANCELLED</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('common_search', 'Search'));?></label>
          <div class="col-lg-5"><input id="filter_keyword" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_search_return_vendor', 'Return no / GR no / vendor / reason'));?>"></div>
          <div class="col-lg-5"><button type="button" id="btn_filter_rtv" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button> <button type="button" id="btn_reset_rtv" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button></div>
        </div>
      </form>
      <hr>
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_return_to_vendor" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th>Return</th><th><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></th><th>Vendor</th><th>Source GR</th><th>Reason</th><th>Items</th><th>Total Qty</th><th><?=wh_h(wh_t('common_status', 'Status'));?></th><th>Created By</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_create_rtv" class="modal fade"><div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content">
    <form id="form_create_rtv">
      <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Return to Vendor</h4></div>
      <div class="modal-body">
        <div class="alert alert-info">Pilih Goods Receipt asal, isi qty return per item, lalu posting. Sistem akan mengurangi stock layer tersedia dan membuat movement type <strong>122 OUT</strong>.</div>
        <div class="row">
          <div class="col-md-4 form-group"><label class="required-label">Source Goods Receipt</label><select id="source_no_bpb" name="source_no_bpb" class="form-control" required></select></div>
          <div class="col-md-2 form-group"><label class="required-label">Document Date</label><input name="document_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label class="required-label"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label><input name="posting_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-4 form-group"><label>Reference No</label><input name="reference_no" class="form-control" placeholder="Surat jalan return / approval / NCR"></div>
        </div>
        <div class="row">
          <div class="col-md-3 form-group"><label class="required-label">Reason Code</label><select name="return_reason_code" class="form-control" required><option value="QUALITY_REJECT">Quality Reject</option><option value="OVER_DELIVERY">Over Delivery</option><option value="WRONG_MATERIAL">Wrong Material</option><option value="DAMAGED">Damaged</option><option value="CUSTOMS_RETURN">Customs Return</option><option value="OTHER">Other</option></select></div>
          <div class="col-md-5 form-group"><label class="required-label">Reason Text</label><input name="return_reason_text" class="form-control" required placeholder="Alasan return ke vendor"></div>
          <div class="col-md-2 form-group"><label><?=wh_h(wh_t('common_plant', 'Plant'));?></label><select id="plant_id" name="plant_id" class="form-control"><option value="">Dari GR</option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-2 form-group"><label><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></label><select id="storage_location_id" name="storage_location_id" class="form-control"><option value="">Dari GR</option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
        </div>
        <div id="rtv_item_area"><div class="text-muted">Pilih Source Goods Receipt untuk memuat item tersedia.</div></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_cancel', 'Cancel'));?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Return</button></div>
    </form>
  </div></div></div>

  <div id="modal_detail_rtv" class="modal fade"><div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Return to Vendor Detail</h4></div><div class="modal-body" id="isi_detail_rtv"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function showRtvError(m){$('.isi_warning_delete').text(m||'Proses Return to Vendor gagal.');$('.error_data_delete').fadeIn();}
$(function(){
  if($.fn.datepicker){$('.filter-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_vendor,#filter_status,#plant_id,#storage_location_id').select2({width:'100%'});$('#source_no_bpb').select2({width:'100%',dropdownParent:$('#modal_create_rtv'),placeholder:'Cari GR / vendor / no aju',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/return_to_vendor/return_to_vendor_action.php?act=gr_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
  var dt=$('#dtb_return_to_vendor').DataTable({bProcessing:true,bServerSide:true,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[7,8],className:'text-right'},{width:'45px',targets:0},{width:'86px',targets:1}],ajax:{url:'<?=base_admin();?>modul/return_to_vendor/return_to_vendor_data.php',type:'post',data:function(d){d.tgl_awal=$('#filter_tgl_awal').val();d.tgl_akhir=$('#filter_tgl_akhir').val();d.vendor=$('#filter_vendor').val();d.status=$('#filter_status').val();d.keyword=$('#filter_keyword').val();},error:function(xhr){console.log(xhr);showRtvError('Data Return to Vendor gagal dimuat.');}}});
  $('#btn_open_create_rtv').on('click',function(){$('#modal_create_rtv').modal({backdrop:'static',keyboard:false});});
  $('#btn_filter_rtv').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});$('#btn_reset_rtv').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_vendor,#filter_status').val('').trigger('change');dt.draw();});
  $('#source_no_bpb').on('select2:select',function(e){var no=e.params.data.id;$('#rtv_item_area').html('<div class="text-muted"><i class="fa fa-spinner fa-spin"></i> Memuat item...</div>');$.post('<?=base_admin();?>modul/return_to_vendor/return_to_vendor_action.php?act=gr_items',{no_bpb:no},function(html){$('#rtv_item_area').html(html);});});
  $('#plant_id').on('change',function(){var p=$(this).val();$('#storage_location_id option').each(function(){var plant=$(this).data('plant-id');$(this).toggle(!plant||!p||String(plant)===String(p));});if(p&&$('#storage_location_id option:selected').data('plant-id')&&String($('#storage_location_id option:selected').data('plant-id'))!==String(p))$('#storage_location_id').val('').trigger('change.select2');});
  $('#form_create_rtv').on('submit',function(e){e.preventDefault();var btn=$(this).find('button[type=submit]');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=wh_h(wh_t('common_post', 'Post'));?>ing...');$.ajax({url:'<?=base_admin();?>modul/return_to_vendor/return_to_vendor_action.php?act=post',type:'POST',data:$(this).serialize(),dataType:'json',success:function(res){if(res.status==='good'){$('#modal_create_rtv').modal('hide');$('#form_create_rtv')[0].reset();$('#source_no_bpb,#plant_id,#storage_location_id').val('').trigger('change');$('#rtv_item_area').html('<div class="text-muted">Pilih Source Goods Receipt untuk memuat item tersedia.</div>');dt.draw(false);}else showRtvError(res.error_message);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Return');},error:function(xhr){showRtvError(xhr.responseText);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Return');}});});
  $(document).on('click','.btn-detail-rtv',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/return_to_vendor/return_to_vendor_action.php?act=detail',{id:id},function(html){$('#isi_detail_rtv').html(html);$('#modal_detail_rtv').modal('show');}).fail(function(){showRtvError('Detail return gagal dibuka.');});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
