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
                   FROM erp_sample_issue h
                   LEFT JOIN (
                     SELECT issue_id,SUM(amount) AS total_amount
                     FROM erp_sample_issue_detail
                     GROUP BY issue_id
                   ) ds ON ds.issue_id=h.id");
?>
<style>
  .smp-hero{background:linear-gradient(135deg,#0f766e,#0f172a);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,118,110,.18)}
  .smp-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.smp-hero p{margin:0;opacity:.92}
  .smp-kpi{border-radius:12px;background:#fff;border:1px solid #edf2f7;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .smp-kpi span{color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.smp-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}.smp-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}
  #dtb_sample_issue td,#dtb_sample_issue th{font-size:12px;vertical-align:middle}
  .smp-action-buttons{white-space:nowrap;min-width:112px}.smp-action-buttons .btn{margin-right:3px}
  .smp-items th{background:#f5f5f5}.smp-items td,.smp-items th{font-size:12px;vertical-align:middle!important}.smp-items .form-control{height:30px;padding:4px 6px;font-size:12px}
  .smp-stock-preview{max-height:150px;overflow:auto;min-width:360px}.smp-stock-preview table td,.smp-stock-preview table th{font-size:11px!important}
  .select2-container{width:100%!important}.required-label:after{content:' *';color:#dd4b39}.smp-help{color:#64748b;margin-right:10px}
  #modal_create_smp .modal-dialog{margin-top:18px;margin-bottom:18px}
  #modal_create_smp .modal-body{max-height:calc(100vh - 210px);overflow-y:auto;overflow-x:hidden}
  #smp_item_area{max-height:430px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:6px;background:#fff}
  #modal_create_smp .modal-footer{position:sticky;bottom:0;background:#fff;z-index:2;border-top:1px solid #e5e7eb}
</style>
<section class="content-header">
  <h1>Sample Issue <small>SAP MM Movement Type 333</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Sample Issue</li>
  </ol>
</section>
<section class="content">
  <div class="smp-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Goods Sample Issue Workbench</h1>
        <p>Posting sample material dengan FIFO stock layer, material document 333, jurnal otomatis, dan trace dokumen pabean.</p>
      </div>
      <div class="col-md-4 text-right">
        <?php if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") { ?>
          <button type="button" id="btn_open_create_smp" class="btn btn-warning"><i class="fa fa-flask"></i> Create Issue 333</button>
        <?php } ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="smp-kpi"><i class="fa fa-file-text-o"></i><span>Total Issue</span><strong><?=number_format((float)$kpi->total_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="smp-kpi"><i class="fa fa-check"></i><span>Posted</span><strong><?=number_format((float)$kpi->posted_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="smp-kpi"><i class="fa fa-undo"></i><span>Reversed</span><strong><?=number_format((float)$kpi->reversed_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="smp-kpi"><i class="fa fa-money"></i><span>Total Amount</span><strong><?=number_format((float)$kpi->total_amount,2,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Sample Issue</h3></div>
    <div class="box-body">
      <form class="form-horizontal" id="filter_scr" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=wh_h(wh_t('common_status', 'Status'));?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option>POSTED</option><option>REVERSED</option></select></div>
          <label class="control-label col-lg-1">Reason</label>
          <div class="col-lg-2"><select id="filter_reason_code" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="CUSTOMER_SAMPLE">Customer Sample</option><option value="RND_SAMPLE">R&D Sample</option><option value="SALES_SAMPLE">Sales Sample</option><option value="QC_SAMPLE">QC Sample</option><option value="LAB_TEST">Lab Test</option><option value="OTHER">Other</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('common_search', 'Search'));?></label>
          <div class="col-lg-5"><input id="filter_keyword" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_search_issue_trace', 'Issue no / material / no aju / no dokpab / no BPB / lot'));?>"></div>
          <div class="col-lg-5">
            <button type="button" id="btn_filter_smp" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button>
            <button type="button" id="btn_reset_smp" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button>
            <button type="button" id="btn_excel_smp" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=wh_h(wh_t('common_download_excel', 'Download Excel'));?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_sample_issue" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th>Issue Doc</th><th><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></th><th>Sample</th><th>Source Location</th><th>Reason</th><th>Items</th><th>Total Qty</th><th>Total Amount</th><th>Customs Trace</th><th><?=wh_h(wh_t('common_status', 'Status'));?></th><th>Created By</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_create_smp" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content">
    <form id="form_create_smp">
      <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Sample Issue 333</h4></div>
      <div class="modal-body">
        <div class="alert alert-info">Gunakan untuk customer sample, R&D sample, sales sample, QC sample, atau lab test. Sistem mengambil stock FIFO dan menyimpan asal BC/BPB per layer.</div>
        <div class="row">
          <div class="col-md-3 form-group"><label class="required-label">Purpose</label><select name="reason_code" class="form-control mandatory-smp" required><option value="CUSTOMER_SAMPLE">Customer Sample</option><option value="RND_SAMPLE">R&D Sample</option><option value="SALES_SAMPLE">Sales Sample</option><option value="QC_SAMPLE">QC Sample</option><option value="LAB_TEST">Lab Test</option><option value="OTHER">Other</option></select></div>
          <div class="col-md-3 form-group"><label class="required-label">Purpose Text</label><input name="reason_text" class="form-control mandatory-smp" value="Sample material" required></div>
          <div class="col-md-2 form-group"><label class="required-label">Document Date</label><input name="document_date" class="form-control date-field mandatory-smp" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label class="required-label"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label><input name="posting_date" class="form-control date-field mandatory-smp" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label><?=wh_h(wh_t('warehouse_reference', 'Reference'));?></label><input name="reference_no" class="form-control" placeholder="BA sample / memo"></div>
        </div>
        <div class="row">
          <div class="col-md-2 form-group"><label>Source Plant</label><select id="plant_id" name="plant_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_plant', 'Semua Plant'));?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label>Source Storage Location</label><select id="storage_location_id" name="storage_location_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_sloc', 'Semua SLoc'));?></option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>" data-plant-id="<?=intval($s->plant_id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label>Source Storage Bin</label><select id="storage_bin_id" name="storage_bin_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_bin', 'Semua Bin'));?></option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>" data-storage-location-id="<?=intval($b->storage_location_id);?>"><?=htmlspecialchars($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-2 form-group"><label>Sample Type</label><select name="sample_type" class="form-control"><option value="FREE_SAMPLE">Free Sample</option><option value="CUSTOMER_TRIAL">Customer Trial</option><option value="LAB_SAMPLE">Lab Sample</option><option value="DISPLAY_SAMPLE">Display Sample</option><option value="OTHER">Other</option></select></div>
          <div class="col-md-2 form-group"><label>Recipient Type</label><select name="recipient_type" class="form-control"><option value="CUSTOMER">Customer</option><option value="INTERNAL">Internal</option><option value="RND">R&D</option><option value="QC">QC</option><option value="SALES">Sales</option></select></div>
          <div class="col-md-4 form-group"><label>Recipient Name</label><input name="recipient_name" class="form-control" placeholder="Nama customer / departemen / PIC"></div>
        </div>
        <div class="clearfix" style="margin-bottom:8px">
          <button type="button" id="btn_add_smp_item" class="btn btn-default btn-sm"><i class="fa fa-plus"></i> Add Material</button>
          <span class="text-muted" style="margin-left:8px">Mandatory item: material dan issue qty. Trace pabean terisi otomatis dari stock layer.</span>
        </div>
        <div id="smp_item_area" class="table-responsive">
          <table class="table table-bordered table-condensed smp-items" style="margin-bottom:0">
            <thead><tr><th style="width:36px">#</th><th style="min-width:280px"><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th style="width:110px">Issue Qty</th><th style="width:80px"><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th style="min-width:360px">FIFO Stock Preview</th><th style="min-width:180px">Remark</th><th style="width:48px"></th></tr></thead>
            <tbody id="smp_item_body"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <span id="smp_submit_help" class="smp-help">Pilih reason dan minimal satu item valid.</span>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_cancel', 'Cancel'));?></button>
        <button type="submit" id="btn_post_smp" class="btn btn-primary" disabled><i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Issue 333</button>
      </div>
    </form>
  </div></div></div>

  <div id="modal_detail_smp" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Sample Issue Trace Detail</h4></div><div class="modal-body" id="isi_detail_smp"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showSmpError(m){$('.isi_warning_delete').text(m||'Sample Issue gagal diproses.');$('.error_data_delete').fadeIn();}
function renumberSmpItems(){
  $('#smp_item_body tr').each(function(i){$(this).find('.smp-row-no').text(i+1);});
}
function validateSmpForm(){
  var ok=true, rowOk=0;
  $('.mandatory-smp').each(function(){if(!$(this).val())ok=false;});
  $('#smp_item_body tr').each(function(){
    var material=$(this).find('.smp-material').val();
    var qty=parseFloat($(this).find('.smp-qty').val())||0;
    if(material && qty>0) rowOk++;
    if((material && qty<=0) || (!material && qty>0)) ok=false;
  });
  if(rowOk===0)ok=false;
  $('#btn_post_smp').prop('disabled',!ok);
  $('#smp_submit_help').text(ok?'Siap posting movement 333.':'Pilih reason dan minimal satu item valid.');
}
function reloadSmpPreview(row){
  var material=row.find('.smp-material').val();
  var preview=row.find('.smp-stock-preview');
  if(!material){preview.html('<span class="text-muted">Pilih material.</span>');return;}
  preview.html('<span class="text-muted"><i class="fa fa-spinner fa-spin"></i> Memuat stock layer...</span>');
  $.post('<?=base_admin();?>modul/sample_issue/sample_issue_action.php?act=stock_preview',{
    material_code:material,
    plant_id:$('#plant_id').val(),
    storage_location_id:$('#storage_location_id').val(),
    storage_bin_id:$('#storage_bin_id').val()
  },function(html){preview.html(html);}).fail(function(){preview.html('<span class="text-danger">Preview stock gagal dimuat.</span>');});
}
function initSmpMaterialSelect(row){
  row.find('.smp-material').select2({width:'100%',dropdownParent:$('#modal_create_smp'),placeholder:'Cari material stock...',minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/sample_issue/sample_issue_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||'',plant_id:$('#plant_id').val(),storage_location_id:$('#storage_location_id').val(),storage_bin_id:$('#storage_bin_id').val()};},processResults:function(d){return{results:d.results||[]};}}})
    .on('select2:select',function(e){var data=e.params.data||{};row.find('.smp-uom').val(data.uom||'');reloadSmpPreview(row);validateSmpForm();})
    .on('change',validateSmpForm);
}
function addSmpItem(){
  var row=$('<tr>'+
    '<td class="text-center smp-row-no"></td>'+
    '<td><select name="material_code[]" class="form-control smp-material"></select></td>'+
    '<td><input type="number" min="0" step="0.00001" name="qty[]" class="form-control text-right smp-qty" placeholder="0.00000"></td>'+
    '<td><input type="text" class="form-control smp-uom" readonly></td>'+
    '<td><div class="smp-stock-preview"><span class="text-muted">Pilih material.</span></div></td>'+
    '<td><input name="item_remarks[]" class="form-control" placeholder="<?=wh_h(wh_t('warehouse_item_note_placeholder', 'Catatan item'));?>"></td>'+
    '<td class="text-center"><button type="button" class="btn btn-danger btn-xs btn-remove-smp-item"><i class="fa fa-trash"></i></button></td>'+
  '</tr>');
  $('#smp_item_body').append(row);
  initSmpMaterialSelect(row);
  renumberSmpItems();
  validateSmpForm();
}
function refreshAllSmpPreview(){
  $('#smp_item_body tr').each(function(){reloadSmpPreview($(this));});
}
$(function(){
  if($.fn.datepicker){$('.filter-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_status,#filter_reason_code,#plant_id,#storage_location_id,#storage_bin_id').select2({width:'100%'});
  }
  var dt=$('#dtb_sample_issue').DataTable({bProcessing:true,bServerSide:true,pageLength:25,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[7,8,9],className:'text-right'},{width:'42px',targets:0},{width:'125px',targets:1}],ajax:{url:'<?=base_admin();?>modul/sample_issue/sample_issue_data.php',type:'post',data:function(d){d.tgl_awal=$('#filter_tgl_awal').val();d.tgl_akhir=$('#filter_tgl_akhir').val();d.status=$('#filter_status').val();d.reason_code=$('#filter_reason_code').val();d.keyword=$('#filter_keyword').val();},error:function(xhr){console.log(xhr);showSmpError('Data Sample Issue gagal dimuat.');}}});
  $('#btn_open_create_smp').on('click',function(){if($('#smp_item_body tr').length===0)addSmpItem();$('#modal_create_smp').modal({backdrop:'static',keyboard:false});validateSmpForm();});
  $('#btn_add_smp_item').on('click',addSmpItem);
  $(document).on('click','.btn-remove-smp-item',function(){$(this).closest('tr').remove();renumberSmpItems();validateSmpForm();});
  $(document).on('keyup change','.mandatory-smp,.smp-qty',validateSmpForm);
  $('#plant_id').on('change',function(){var p=$(this).val();$('#storage_location_id option').each(function(){var plant=$(this).data('plant-id');$(this).toggle(!plant||!p||String(plant)===String(p));});if(p&&$('#storage_location_id option:selected').data('plant-id')&&String($('#storage_location_id option:selected').data('plant-id'))!==String(p))$('#storage_location_id').val('').trigger('change.select2');refreshAllSmpPreview();});
  $('#storage_location_id').on('change',function(){var loc=$(this).val();$('#storage_bin_id option').each(function(){var optionLoc=$(this).data('storage-location-id');$(this).toggle(!optionLoc||!loc||String(optionLoc)===String(loc));});if(loc&&$('#storage_bin_id option:selected').data('storage-location-id')&&String($('#storage_bin_id option:selected').data('storage-location-id'))!==String(loc))$('#storage_bin_id').val('').trigger('change.select2');refreshAllSmpPreview();});
  $('#storage_bin_id').on('change',refreshAllSmpPreview);
  $('#btn_filter_smp').on('click',function(){dt.draw();});
  $('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_smp').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_status,#filter_reason_code').val('').trigger('change');dt.draw();});
  $('#btn_excel_smp').on('click',function(){var url='<?=base_admin();?>modul/sample_issue/sample_issue_action.php?act=excel&tgl_awal='+encodeURIComponent($('#filter_tgl_awal').val())+'&tgl_akhir='+encodeURIComponent($('#filter_tgl_akhir').val())+'&status='+encodeURIComponent($('#filter_status').val()||'')+'&reason_code='+encodeURIComponent($('#filter_reason_code').val()||'')+'&keyword='+encodeURIComponent($('#filter_keyword').val()||'');window.location.href=url;});
  $('#form_create_smp').on('submit',function(e){e.preventDefault();validateSmpForm();if($('#btn_post_smp').prop('disabled'))return;var btn=$('#btn_post_smp');Swal.fire({title:'Post Issue 333?',text:'Stock akan dikurangi, jurnal biaya sample dibuat, dan trace BC/BPB dikunci.',icon:'question',showCancelButton:true,confirmButtonText:'Post'}).then(function(result){if(!result.isConfirmed)return;btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=wh_h(wh_t('common_post', 'Post'));?>ing...');$.ajax({url:'<?=base_admin();?>modul/sample_issue/sample_issue_action.php?act=post',type:'POST',data:$('#form_create_smp').serialize(),dataType:'json',success:function(res){if(res.status==='good'){$('#modal_create_smp').modal('hide');$('#form_create_smp')[0].reset();$('#plant_id,#storage_location_id,#storage_bin_id').val('').trigger('change');$('#smp_item_body').empty();dt.draw(false);Swal.fire(<?=json_encode(wh_t('common_success', 'Success'));?>,'Sample Issue '+res.issue_no+' berhasil diposting.','success');}else{showSmpError(res.error_message);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Issue 333');}},error:function(xhr){showSmpError(xhr.responseText);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> Issue 333');}});});});
  $(document).on('click','.btn-detail-smp',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/sample_issue/sample_issue_action.php?act=detail',{id:id},function(html){$('#isi_detail_smp').html(html);$('#modal_detail_smp').modal('show');}).fail(function(){showSmpError('Detail issue gagal dibuka.');});});
  $(document).on('click','.btn-reversal-smp',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:'Reversal Issue 334?',input:'text',inputLabel:'Reason reversal '+no,inputPlaceholder:<?=json_encode(wh_t('warehouse_reversal_reason_placeholder', 'Alasan reversal wajib diisi'));?>,showCancelButton:true,confirmButtonText:'Reversal',inputValidator:function(v){return !v?<?=json_encode(wh_t('warehouse_reason_required', 'Reason wajib diisi'));?>:undefined;}}).then(function(result){if(!result.isConfirmed)return;$.ajax({url:'<?=base_admin();?>modul/sample_issue/sample_issue_action.php?act=reversal',type:'POST',dataType:'json',data:{id:id,reason:result.value},success:function(res){if(res.status==='good'){Swal.fire(<?=json_encode(wh_t('common_success', 'Success'));?>,'Reversal 334 berhasil','success');dt.draw(false);}else{Swal.fire(<?=json_encode(wh_t('common_error', 'Error'));?>,res.error_message||<?=json_encode(wh_t('warehouse_reversal_failed', 'Reversal gagal'));?>,'error');}},error:function(xhr){Swal.fire(<?=json_encode(wh_t('common_error', 'Error'));?>,xhr.responseText,'error');}});});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
