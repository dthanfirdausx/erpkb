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
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$plants = $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$modalPlants = $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$storageLocations = $db->query("SELECT s.id,s.storage_code,s.storage_name,s.plant_id,p.plant_code FROM erp_storage_location s JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$kpi = $db->fetch("SELECT COUNT(*) total_doc,COALESCE(SUM(status='CREATED'),0) created_doc,COALESCE(SUM(status='RELEASED'),0) released_doc,COALESCE(SUM(status='IN_PROCESS'),0) process_doc FROM production_order");
?>
<style>
  .po-hero{background:linear-gradient(135deg,#4c1d95,#047857);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(76,29,149,.18)}
  .po-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.po-hero p{margin:0;opacity:.92}
  .po-kpi{border-radius:12px;background:#fff;border:1px solid #edf2f7;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .po-kpi span{color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.po-kpi strong{display:block;font-size:25px;margin-top:6px;color:#111827}.po-kpi i{float:right;font-size:26px;color:#6d28d9;opacity:.55}
  #dtb_production_order td,#dtb_production_order th{font-size:12px;vertical-align:middle}.po-action-buttons{white-space:nowrap;min-width:126px}.po-action-buttons .btn{margin-right:3px}
  .po-items td,.po-items th{font-size:12px;vertical-align:middle!important}.select2-container{width:100%!important}.required-label:after{content:' *';color:#dd4b39}
  #modal_create_po .modal-dialog{margin-top:18px;margin-bottom:18px}
  #modal_create_po .modal-body{max-height:calc(100vh - 210px);overflow-y:auto;overflow-x:hidden}
  #bom_preview{max-height:360px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:6px;padding:8px;background:#fff}
  #bom_preview .table-responsive{margin-bottom:0}
  #modal_create_po .modal-footer{position:sticky;bottom:0;background:#fff;z-index:2;border-top:1px solid #e5e7eb}
</style>
<section class="content-header">
  <h1><?=prod_h('production_order', 'Production Order');?> <small>SAP PP Order</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=prod_h('common_home', 'Home');?></a></li>
    <li class="active"><?=prod_h('production_order', 'Production Order');?></li>
  </ol>
</section>
<section class="content">
  <div class="po-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Production Order Workbench</h1>
        <p>Buat perintah produksi, explode BOM menjadi material requirement, release order, dan monitor issue bahan baku.</p>
      </div>
      <div class="col-md-4 text-right">
        <?php if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") { ?>
          <button type="button" id="btn_open_create_po" class="btn btn-warning"><i class="fa fa-plus"></i> Create Production Order</button>
        <?php } ?>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-3"><div class="po-kpi"><i class="fa fa-file-text-o"></i><span><?=prod_h('production_total_order', 'Total Order');?></span><strong><?=number_format((float)$kpi->total_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="po-kpi"><i class="fa fa-pencil"></i><span><?=prod_h('production_created', 'Created');?></span><strong><?=number_format((float)$kpi->created_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="po-kpi"><i class="fa fa-play"></i><span><?=prod_h('production_released', 'Released');?></span><strong><?=number_format((float)$kpi->released_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="po-kpi"><i class="fa fa-cogs"></i><span><?=prod_h('production_in_process', 'In Process');?></span><strong><?=number_format((float)$kpi->process_doc,0,',','.');?></strong></div></div>
  </div>
  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Production Order</h3></div>
    <div class="box-body">
      <form id="filter_po" class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=prod_h('production_start_date', 'Start Date');?></label>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=prod_h('production_plant', 'Plant');?></label>
          <div class="col-lg-2"><select id="filter_plant" class="form-control"><option value=""><?=prod_h('common_all', 'All');?></option><?php foreach($plants as $p){ ?><option value="<?=htmlspecialchars($p->plant_code,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <label class="control-label col-lg-1"><?=prod_h('common_status', 'Status');?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=prod_h('common_all', 'All');?></option><option>CREATED</option><option>RELEASED</option><option>IN_PROCESS</option><option>CONFIRMED</option><option>TECO</option><option>CLOSED</option><option>CANCELLED</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=prod_h('common_search', 'Search');?></label>
          <div class="col-lg-5"><input id="filter_keyword" class="form-control" placeholder="<?=prod_h('production_search_order', 'No order / material / remarks');?>"></div>
          <div class="col-lg-5"><button id="btn_filter_po" type="button" class="btn btn-primary"><i class="fa fa-filter"></i> <?=prod_h('common_filter', 'Filter');?></button> <button id="btn_reset_po" type="button" class="btn btn-default"><i class="fa fa-refresh"></i> <?=prod_h('common_reset', 'Reset');?></button></div>
        </div>
      </form>
    </div>
  </div>
  <div class="box"><div class="box-body">
    <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div>
    <div class="table-responsive"><table id="dtb_production_order" class="table table-bordered table-striped table-condensed" style="width:100%">
      <thead><tr><th><?=prod_h('common_no', 'No');?></th><th><?=prod_h('common_action', 'Action');?></th><th><?=prod_h('production_order', 'Production Order');?></th><th>Schedule</th><th><?=prod_h('production_header_material', 'Header Material');?></th><th><?=prod_h('production_qty', 'Qty');?></th><th>Plant/SLoc</th><th><?=prod_h('production_components', 'Components');?></th><th><?=prod_h('common_status', 'Status');?></th><th><?=prod_h('common_created_by', 'Created By');?></th></tr></thead>
      <tbody></tbody>
    </table></div>
  </div></div>

  <div id="modal_create_po" class="modal fade"><div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content">
    <form id="form_create_po">
      <div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Create Production Order</h4></div>
      <div class="modal-body">
        <div class="alert alert-info">Gunakan <strong>Make to Order</strong> untuk produksi khusus Sales Order customer. Gunakan <strong>Make to Stock</strong> untuk produksi stok umum atau barang setengah jadi.</div>
        <div class="row">
          <div class="col-md-3 form-group"><label class="required-label">Production Source</label><select id="order_strategy" name="order_strategy" class="form-control" required><option value="MTS">Make to Stock</option><option value="MTO">Make to Order</option></select></div>
          <div class="col-md-6 form-group po-so-field"><label>Sales Order Item</label><select id="sales_order_item" name="sales_order_item" class="form-control"></select><input type="hidden" id="id_sales_order" name="id_sales_order"><input type="hidden" id="no_sales_order" name="no_sales_order"><input type="hidden" id="id_sales_order_detail" name="id_sales_order_detail"><input type="hidden" id="customer_code" name="customer_code"><input type="hidden" id="customer_po" name="customer_po"></div>
          <div class="col-md-3 form-group po-so-field"><label>Customer / PO</label><input id="so_reference_display" class="form-control" readonly placeholder="Terisi otomatis dari SO"></div>
        </div>
        <div class="row">
          <div class="col-md-4 form-group"><label class="required-label">Material FG/SFG</label><select id="material_code" name="material_code" class="form-control" required></select><input type="hidden" id="material_name" name="material_name"><input type="hidden" id="uom" name="uom"></div>
          <div class="col-md-2 form-group"><label class="required-label"><?=prod_h('production_order_qty', 'Order Qty');?></label><input type="number" step="0.00001" min="0.00001" id="order_qty" name="order_qty" class="form-control" required></div>
          <div class="col-md-2 form-group"><label><?=prod_h('production_uom', 'UOM');?></label><input id="uom_display" class="form-control" readonly></div>
          <div class="col-md-2 form-group"><label>Order Type</label><select name="order_type" class="form-control"><option value="PP01">PP01 Standard</option><option value="PP02">PP02 Rework</option><option value="PP03">PP03 Trial</option></select></div>
          <div class="col-md-2 form-group"><label>Priority</label><select name="priority" class="form-control"><option>NORMAL</option><option>HIGH</option><option>URGENT</option><option>LOW</option></select></div>
        </div>
        <div class="row">
          <div class="col-md-3 form-group"><label class="required-label"><?=prod_h('production_plant', 'Plant');?></label><select id="plant" name="plant" class="form-control" required><option value=""><?=prod_h('production_select_plant', 'Select Plant');?></option><?php foreach($modalPlants as $p){ ?><option value="<?=htmlspecialchars($p->plant_code,ENT_QUOTES,'UTF-8');?>" data-id="<?=intval($p->id);?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label>Storage Location</label><select id="storage_location" name="storage_location" class="form-control"><option value=""><?=prod_h('production_select_sloc', 'Select SLoc');?></option><?php foreach($storageLocations as $s){ ?><option value="<?=htmlspecialchars($s->storage_code,ENT_QUOTES,'UTF-8');?>" data-plant-id="<?=intval($s->plant_id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-md-2 form-group"><label class="required-label"><?=prod_h('production_start_date', 'Start Date');?></label><input name="start_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label class="required-label"><?=prod_h('production_finish_date', 'Finish Date');?></label><input name="finish_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
          <div class="col-md-2 form-group"><label>Release</label><select name="auto_release" class="form-control"><option value="N">Create Only</option><option value="Y">Create & Release</option></select></div>
        </div>
        <div class="form-group"><label><?=prod_h('common_remarks', 'Remarks');?></label><input name="remarks" class="form-control" placeholder="<?=prod_h('production_order_remark_placeholder', 'Production / batch / sales order / customer notes');?>"></div>
        <h4>BOM Preview</h4>
        <div id="bom_preview"><div class="text-muted"><?=prod_h('production_select_material_qty_component', 'Select material and enter qty to view component requirement.');?></div></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=prod_h('common_cancel', 'Cancel');?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?=prod_h('production_save_order', 'Save Production Order');?></button></div>
    </form>
  </div></div></div>

  <div id="modal_detail_po" class="modal fade"><div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Production Order Detail</h4></div><div class="modal-body" id="isi_detail_po"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=prod_h('common_close', 'Close');?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showPoError(m){$('.isi_warning_delete').text(m||<?=prod_js('production_order_process_failed', 'Production Order failed to process.');?>);$('.error_data_delete').fadeIn();}
function loadBomPreview(){
  var mat=$('#material_code').val(), qty=$('#order_qty').val();
  if(!mat||!qty){$('#bom_preview').html('<div class="text-muted"><?=prod_h('production_select_material_qty_component', 'Select material and enter qty to view component requirement.');?></div>');return;}
  $('#bom_preview').html('<div class="text-muted"><i class="fa fa-spinner fa-spin"></i> Explode BOM...</div>');
  $.post('<?=base_admin();?>modul/production_order/production_order_action.php?act=bom_preview',{material_code:mat,order_qty:qty},function(html){$('#bom_preview').html(html);});
}
$(function(){
  if($.fn.datepicker){$('.filter-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  function resetSoReference(){ $('#sales_order_item').val(null).trigger('change'); $('#id_sales_order,#no_sales_order,#id_sales_order_detail,#customer_code,#customer_po,#so_reference_display').val(''); }
  function toggleOrderStrategy(){
    var mode=$('#order_strategy').val();
    if(mode==='MTO'){$('.po-so-field').show();$('#sales_order_item').prop('required',true);}
    else{$('.po-so-field').hide();$('#sales_order_item').prop('required',false);resetSoReference();}
  }
  if($.fn.select2){
    $('#filter_plant,#filter_status,#plant,#storage_location,#order_strategy').select2({width:'100%'});
    $('#sales_order_item').select2({width:'100%',dropdownParent:$('#modal_create_po'),placeholder:'Cari SO / customer / material...',allowClear:true,minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/production_order/production_order_action.php?act=so_item_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
    $('#material_code').select2({width:'100%',dropdownParent:$('#modal_create_po'),placeholder:<?=prod_js('production_search_fg_sfg', 'Search FG/SFG material...');?>,minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/production_order/production_order_action.php?act=material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
  }
  var dt=$('#dtb_production_order').DataTable({bProcessing:true,bServerSide:true,pageLength:25,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=prod_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[5,7],className:'text-right'},{width:'42px',targets:0},{width:'126px',targets:1}],ajax:{url:'<?=base_admin();?>modul/production_order/production_order_data.php',type:'post',data:function(d){d.tgl_awal=$('#filter_tgl_awal').val();d.tgl_akhir=$('#filter_tgl_akhir').val();d.status=$('#filter_status').val();d.plant=$('#filter_plant').val();d.keyword=$('#filter_keyword').val();},error:function(xhr){console.log(xhr);showPoError(<?=prod_js('production_order_load_failed', 'Production Order data failed to load.');?>);}}});
  $('#btn_open_create_po').on('click',function(){$('#modal_create_po').modal({backdrop:'static',keyboard:false});});
  $('#order_strategy').on('change',toggleOrderStrategy);
  toggleOrderStrategy();
  $('#sales_order_item').on('select2:select',function(e){var d=e.params.data;$('#id_sales_order').val(d.id_sales_order||'');$('#no_sales_order').val(d.no_sales_order||'');$('#id_sales_order_detail').val(d.id_sales_order_detail||'');$('#customer_code').val(d.customer_code||'');$('#customer_po').val(d.customer_po||'');$('#so_reference_display').val((d.customer_name||d.customer_code||'-')+' / PO '+(d.customer_po||'-'));$('#material_name').val(d.material_name||'');$('#uom,#uom_display').val(d.uom||'');$('#order_qty').val(d.remaining_qty||d.qty||'');var opt=new Option(d.material_code+' - '+(d.material_name||''),d.material_code,true,true);$('#material_code').append(opt).trigger('change');loadBomPreview();});
  $('#sales_order_item').on('select2:clear',function(){$('#id_sales_order,#no_sales_order,#id_sales_order_detail,#customer_code,#customer_po,#so_reference_display').val('');});
  $('#material_code').on('select2:select',function(e){var d=e.params.data;$('#material_name').val(d.material_name||'');$('#uom,#uom_display').val(d.uom||'');if(d.plant)$('#plant').val(d.plant).trigger('change');if(d.storage_location)$('#storage_location').val(d.storage_location).trigger('change');loadBomPreview();});
  $('#order_qty').on('keyup change',loadBomPreview);
  $('#plant').on('change',function(){var plantId=$('#plant option:selected').data('id');$('#storage_location option').each(function(){var p=$(this).data('plant-id');$(this).toggle(!p||!plantId||String(p)===String(plantId));});});
  $('#form_create_po').on('submit',function(e){e.preventDefault();if($('#order_strategy').val()==='MTO'&&!$('#id_sales_order_detail').val()){showPoError(<?=prod_js('production_mto_so_item_required', 'Make to Order must select Sales Order Item.');?>);return;}var btn=$(this).find('button[type=submit]');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> <?=prod_h('common_saving', 'Saving...');?>');$.ajax({url:'<?=base_admin();?>modul/production_order/production_order_action.php?act=create',type:'POST',data:$(this).serialize(),dataType:'json',success:function(res){if(res.status==='good'){$('#modal_create_po').modal('hide');$('#form_create_po')[0].reset();$('#material_code,#plant,#storage_location,#order_strategy').val('').trigger('change');$('#order_strategy').val('MTS').trigger('change');$('#bom_preview').html('<div class="text-muted"><?=prod_h('production_select_material_qty_component', 'Select material and enter qty to view component requirement.');?></div>');dt.draw(false);}else showPoError(res.error_message);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=prod_h('production_save_order', 'Save Production Order');?>');},error:function(xhr){showPoError(xhr.responseText);btn.prop('disabled',false).html('<i class="fa fa-save"></i> <?=prod_h('production_save_order', 'Save Production Order');?>');}});});
  $('#btn_filter_po').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});$('#btn_reset_po').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_plant,#filter_status').val('').trigger('change');dt.draw();});
  $(document).on('click','.btn-detail-po',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/production_order/production_order_action.php?act=detail',{id:id},function(html){$('#isi_detail_po').html(html);$('#modal_detail_po').modal('show');}).fail(function(){showPoError(<?=prod_js('production_order_detail_failed', 'Production Order detail failed to open.');?>);});});
  $(document).on('click','.btn-release-po',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:<?=prod_js('production_release_order_confirm', 'Release Production Order?');?>,text:no+' akan bisa dipakai untuk Issue to Production.',icon:'question',showCancelButton:true,confirmButtonText:<?=prod_js('production_release', 'Release');?>}).then(function(r){if(!r.isConfirmed)return;$.post('<?=base_admin();?>modul/production_order/production_order_action.php?act=release',{id:id},function(res){if(res.status==='good'){dt.draw(false);}else showPoError(res.error_message);},'json');});});
  $(document).on('click','.btn-cancel-po',function(){var id=$(this).data('id'),no=$(this).data('no');Swal.fire({title:<?=prod_js('production_cancel_order_confirm', 'Cancel Production Order?');?>,input:'text',inputLabel:'Reason cancel '+no,showCancelButton:true,confirmButtonText:'Cancel Order',inputValidator:function(v){return !v?<?=prod_js('production_reason_required', 'Reason is required');?>:undefined;}}).then(function(r){if(!r.isConfirmed)return;$.post('<?=base_admin();?>modul/production_order/production_order_action.php?act=cancel',{id:id,reason:r.value},function(res){if(res.status==='good'){dt.draw(false);}else showPoError(res.error_message);},'json');});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
