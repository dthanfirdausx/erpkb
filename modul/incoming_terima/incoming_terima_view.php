<?php
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$plants = $db->query("SELECT plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$storageLocations = $db->query("SELECT s.id,s.storage_code,s.storage_name,s.plant_id,p.plant_code FROM erp_storage_location s JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$storageBins = $db->query("SELECT b.id,b.bin_code,b.bin_name,b.storage_location_id,s.storage_code FROM erp_storage_bin b JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code");
$kpi = $db->fetch("SELECT COUNT(*) total_doc,
                          SUM(COALESCE(status,'0')='0') outstanding_doc,
                          SUM(COALESCE(status,'0')='1') posted_doc
                   FROM transfer
                   WHERE ke='1'");
?>
<style>
  .grprod-hero{background:linear-gradient(135deg,#1d4ed8,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(29,78,216,.18)}
  .grprod-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.grprod-hero p{margin:0;opacity:.9}
  .grprod-kpi{border-radius:12px;background:#fff;border:1px solid #edf2f7;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .grprod-kpi span{color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.grprod-kpi strong{display:block;font-size:25px;margin-top:6px;color:#111827}.grprod-kpi i{float:right;font-size:26px;color:#3c8dbc;opacity:.55}
  #dtb_gr_production td,#dtb_gr_production th{font-size:12px;vertical-align:middle}.grprod-action-buttons{white-space:nowrap;min-width:105px}.grprod-action-buttons .btn{margin-right:3px}
  .grprod-summary{border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:14px}.grprod-summary>div{padding:12px;border-right:1px solid #e5e7eb;background:#fbfdff;min-height:76px}.grprod-summary>div:last-child{border-right:0}.grprod-summary span{display:block;color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:.04em}.grprod-summary strong{display:block;margin-top:4px;color:#111827}
  .select2-container{width:100%!important}.required-label:after{content:' *';color:#dd4b39}
</style>
<section class="content-header">
  <h1>GR from Production Order <small>SAP PP Goods Receipt 101</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">GR from Production Order</li>
  </ol>
</section>
<section class="content">
  <div class="grprod-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Production Receipt Workbench</h1>
        <p>Posting penerimaan barang hasil produksi ke gudang dengan referensi transfer/production order, storage location, bin, dan stock type.</p>
      </div>
      <div class="col-md-4 text-right">
        <span class="btn btn-default disabled"><i class="fa fa-info-circle"></i> Movement Type 101</span>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-4"><div class="grprod-kpi"><i class="fa fa-file-text-o"></i><span>Total Documents</span><strong><?=intval($kpi->total_doc);?></strong></div></div>
    <div class="col-sm-4"><div class="grprod-kpi"><i class="fa fa-clock-o"></i><span>Outstanding</span><strong><?=intval($kpi->outstanding_doc);?></strong></div></div>
    <div class="col-sm-4"><div class="grprod-kpi"><i class="fa fa-check"></i><span>Posted</span><strong><?=intval($kpi->posted_doc);?></strong></div></div>
  </div>
  <div class="box">
    <div class="box-body">
      <form id="filter_grprod" class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2">Transfer Date</label>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1">Status</label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value="">Semua</option><option value="0">Outstanding</option><option value="1">Posted</option></select></div>
          <label class="control-label col-lg-1">Source</label>
          <div class="col-lg-2"><input id="filter_source" class="form-control" placeholder="Bagian / RO / dokumen"></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2">Plant</label>
          <div class="col-lg-2"><select id="filter_plant" class="form-control"><option value="">Semua Plant</option><?php foreach($plants as $p){ ?><option value="<?=htmlspecialchars($p->plant_code,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-lg-8"><button type="button" id="btn_filter_grprod" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button> <button type="button" id="btn_reset_grprod" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</button></div>
        </div>
      </form>
      <hr>
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_gr_production" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead><tr><th>No</th><th>Action</th><th>Document</th><th>Transfer Date</th><th>Production Ref</th><th>Source</th><th>Destination</th><th>Items</th><th>Qty</th><th>Received By</th><th>Posting Date</th><th>Status</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_detail_grprod" class="modal fade"><div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Production Receipt Detail</h4></div><div class="modal-body" id="isi_detail_grprod"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal">Close</button></div></div></div></div>

  <div id="modal_receive_grprod" class="modal fade"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form id="form_receive_grprod">
      <div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Post Goods Receipt from Production</h4></div>
      <div class="modal-body">
        <div class="alert alert-info">Posting akan memindahkan material dari <strong>TRANSIT</strong> ke <strong>GUDANG</strong> dengan movement type 101.</div>
        <input type="hidden" name="id_transfer" id="receive_id_transfer">
        <input type="hidden" name="no_transfer" id="receive_no_transfer">
        <div class="row">
          <div class="col-md-4 form-group"><label>Reference Transfer</label><input id="receive_ref_display" class="form-control" readonly></div>
          <div class="col-md-4 form-group"><label class="required-label">Document Date</label><input name="document_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-4 form-group"><label class="required-label">Posting Date</label><input name="posting_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
        </div>
        <div class="row">
          <div class="col-md-4 form-group"><label class="required-label">Plant</label><select id="receive_plant" name="plant" class="form-control" required><option value="">Pilih Plant</option><?php foreach($db->query("SELECT plant_code,plant_name,id FROM erp_plant WHERE status='Aktif' ORDER BY plant_code") as $p){ ?><option value="<?=htmlspecialchars($p->plant_code,ENT_QUOTES,'UTF-8');?>" data-id="<?=intval($p->id);?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-4 form-group"><label class="required-label">Storage Location</label><select id="receive_storage_location_id" name="storage_location_id" class="form-control" required><option value="">Pilih Storage Location</option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-4 form-group"><label>Storage Bin</label><select id="receive_storage_bin_id" name="storage_bin_id" class="form-control"><option value="">Tanpa Bin</option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=htmlspecialchars($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
        </div>
        <div class="row">
          <div class="col-md-4 form-group"><label class="required-label">Stock Type</label><select name="stock_type" class="form-control" required><option value="UNRESTRICTED">Unrestricted Use</option><option value="QUALITY">Quality Inspection</option><option value="BLOCKED">Blocked Stock</option></select></div>
          <div class="col-md-8 form-group"><label>Header Text</label><input name="reason" class="form-control" placeholder="Catatan posting / nomor confirmation"></div>
        </div>
        <div id="receive_item_preview"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Post Goods Receipt</button></div>
    </form>
  </div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function showGrProdError(m){$('.isi_warning_delete').text(m||'Proses GR Production gagal.');$('.error_data_delete').fadeIn();}
function parseGrProdResponse(res){if(typeof res==='string'){try{return JSON.parse(res);}catch(e){return {status:'error',error_message:res};}}return res;}
function filterReceiveLocation(){var plantId=$('#receive_plant option:selected').data('id')||'';$('#receive_storage_location_id option').each(function(){var p=$(this).data('plant-id');$(this).toggle(!p||!plantId||String(p)===String(plantId));});var selected=$('#receive_storage_location_id option:selected');if(plantId&&selected.data('plant-id')&&String(selected.data('plant-id'))!==String(plantId))$('#receive_storage_location_id').val('').trigger('change.select2');filterReceiveBin();}
function filterReceiveBin(){var sloc=$('#receive_storage_location_id').val();$('#receive_storage_bin_id option').each(function(){var s=$(this).data('storage-location-id');$(this).toggle(!s||!sloc||String(s)===String(sloc));});var selected=$('#receive_storage_bin_id option:selected');if(sloc&&selected.data('storage-location-id')&&String(selected.data('storage-location-id'))!==String(sloc))$('#receive_storage_bin_id').val('').trigger('change.select2');}
$(function(){
  if($.fn.datepicker){$('.filter-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_status,#filter_plant,#receive_plant,#receive_storage_location_id,#receive_storage_bin_id').select2({width:'100%'});}
  var dt=$('#dtb_gr_production').DataTable({bProcessing:true,bServerSide:true,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:'Export Data',buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[7,8],className:'text-right'},{width:'45px',targets:0},{width:'105px',targets:1}],ajax:{url:'<?=base_admin();?>modul/incoming_terima/incoming_terima_data.php',type:'post',data:function(d){d.tgl_awal=$('#filter_tgl_awal').val();d.tgl_akhir=$('#filter_tgl_akhir').val();d.status=$('#filter_status').val();d.source=$('#filter_source').val();d.plant=$('#filter_plant').val();},error:function(xhr){console.log(xhr);showGrProdError('Data GR Production gagal dimuat.');}}});
  $('#btn_filter_grprod').on('click',function(){dt.draw();});$('#filter_source').on('keyup',function(e){if(e.keyCode===13)dt.draw();});$('#btn_reset_grprod').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_source').val('');$('#filter_status,#filter_plant').val('').trigger('change');dt.draw();});
  $(document).on('click','.btn-detail-grprod',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/incoming_terima/incoming_terima_action.php?act=detail',{id_transfer:id},function(html){$('#isi_detail_grprod').html(html);$('#modal_detail_grprod').modal('show');}).fail(function(){showGrProdError('Detail dokumen gagal dibuka.');});});
  $(document).on('click','.btn-receive-grprod',function(){var id=$(this).data('id'),no=$(this).data('no');$('#receive_id_transfer').val(id);$('#receive_no_transfer').val(no);$('#receive_ref_display').val(no);$('#receive_item_preview').html('<div class="text-muted"><i class="fa fa-spinner fa-spin"></i> Memuat item...</div>');$.post('<?=base_admin();?>modul/incoming_terima/incoming_terima_action.php?act=item_preview',{id_transfer:id},function(html){$('#receive_item_preview').html(html);});$('#modal_receive_grprod').modal({backdrop:'static',keyboard:false});});
  $('#receive_plant').on('change',filterReceiveLocation);$('#receive_storage_location_id').on('change',filterReceiveBin);filterReceiveLocation();
  $('#form_receive_grprod').on('submit',function(e){e.preventDefault();var btn=$(this).find('button[type=submit]');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Posting...');$.ajax({url:'<?=base_admin();?>modul/incoming_terima/incoming_terima_action.php?act=receive',type:'POST',data:$(this).serialize(),dataType:'json',success:function(res){if(res.status==='good'){$('#modal_receive_grprod').modal('hide');dt.draw(false);}else showGrProdError(res.error_message);btn.prop('disabled',false).html('<i class="fa fa-check"></i> Post Goods Receipt');},error:function(xhr){showGrProdError(xhr.responseText);btn.prop('disabled',false).html('<i class="fa fa-check"></i> Post Goods Receipt');}});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
