<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
include_once __DIR__."/goods_issue_delivery_lib.php";
$defaultFrom = date('Y-01-01');
$defaultTo = date('Y-m-d');
$customers = $db->query("SELECT kode_penerima,nama FROM penerima ORDER BY nama");
$shippingPoints = $db->query("SELECT shipping_code,shipping_name FROM erp_shipping_point WHERE status='Aktif' ORDER BY shipping_code");
$customsDocTypes = $db->query("SELECT DISTINCT jenis_dokpab FROM detail_catatan WHERE status=2 AND COALESCE(jenis_dokpab,'')<>'' ORDER BY jenis_dokpab");
$summary = gid_summary($db, array('tgl_awal'=>$defaultFrom,'tgl_akhir'=>$defaultTo,'customer'=>'all','status'=>'all','shipping_point'=>'','keyword'=>''));
?>
<style>
.gid-hero{background:linear-gradient(135deg,#1d4ed8,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.gid-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.gid-hero p{margin:0;opacity:.92}
.gid-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.gid-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.gid-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.gid-kpi i{float:right;font-size:26px;color:#1d4ed8;opacity:.55}.gid-filter .form-group{margin-bottom:12px}
#dtb_goods_issue_delivery th,#dtb_goods_issue_delivery td,.gid-item-table th,.gid-item-table td,.gid-trace-table th,.gid-trace-table td{font-size:12px;vertical-align:middle}
.select2-container{width:100%!important}.gid-action .btn{margin-right:3px}.gid-action .btn:last-child{margin-right:0}
.gid-item-table .form-control{font-size:12px;height:30px;padding:4px 7px}
.gid-section-title{font-weight:700;color:#0f172a;margin:10px 0 12px;padding-bottom:7px;border-bottom:1px solid #e5edf5}
</style>
<section class="content-header"><h1><?=sd_h('sales_goods_issue_delivery', 'Goods Issue for Delivery');?> <small>SAP SD Movement 601</small></h1><ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li><li><a href="#">Sales & Distribution</a></li><li class="active"><?=sd_h('sales_goods_issue_delivery', 'Goods Issue for Delivery');?></li></ol></section>
<section class="content">
  <div class="gid-hero"><div class="row"><div class="col-md-8"><h1><?=sd_h('sales_goods_issue_delivery', 'Goods Issue for Delivery');?></h1><p>Posting goods issue 601 dari Outbound Delivery untuk mengurangi stok, mencatat material document, jurnal, dan trace batch/lot/dokumen BC.</p></div><div class="col-md-4 text-right"><button id="btn_add_gid" class="btn btn-success"><i class="fa fa-upload"></i> <?=wh_h(wh_t('common_post', 'Post'));?> GI Delivery</button></div></div></div>
  <div class="row">
    <div class="col-sm-3"><div class="gid-kpi"><i class="fa fa-file-text-o"></i><span>Total GI</span><strong><?=number_format((float)$summary->total_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="gid-kpi"><i class="fa fa-check-circle"></i><span><?=sd_h('sales_posted', 'Posted');?></span><strong><?=number_format((float)$summary->posted_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="gid-kpi"><i class="fa fa-undo"></i><span>Reversed</span><strong><?=number_format((float)$summary->reversed_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="gid-kpi"><i class="fa fa-money"></i><span>Value</span><strong><?=number_format((float)$summary->posted_amount,0,',','.');?></strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Goods Issue</h3></div><div class="box-body">
    <form class="form-horizontal gid-filter" onsubmit="return false;">
      <div class="form-group"><label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label><div class="col-lg-2"><div class="input-group date gid-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div><div class="col-lg-2"><div class="input-group date gid-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div><label class="control-label col-lg-1"><?=sd_h('sales_customer', 'Customer');?></label><div class="col-lg-5"><select id="filter_customer" class="form-control"><option value="all"><?=sd_h('sales_all_customer', 'All Customer');?></option><?php foreach($customers as $c){ ?><option value="<?=gid_h($c->kode_penerima);?>"><?=gid_h($c->kode_penerima.' - '.$c->nama);?></option><?php } ?></select></div></div>
      <div class="form-group"><label class="control-label col-lg-2"><?=wh_h(wh_t('common_status', 'Status'));?></label><div class="col-lg-2"><select id="filter_status" class="form-control"><option value="all"><?=wh_h(wh_t('common_all', 'Semua'));?></option><option>POSTED</option><option>REVERSED</option><option>CANCELLED</option></select></div><label class="control-label col-lg-2"><?=sd_h('sales_shipping_point', 'Shipping Point');?></label><div class="col-lg-2"><select id="filter_shipping_point" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><?php foreach($shippingPoints as $sp){ ?><option value="<?=gid_h($sp->shipping_code);?>"><?=gid_h($sp->shipping_code.' - '.$sp->shipping_name);?></option><?php } ?></select></div><label class="control-label col-lg-1"><?=wh_h(wh_t('common_search', 'Search'));?></label><div class="col-lg-3"><input id="filter_keyword" class="form-control" placeholder="GI/Delivery/SO/customer/SJ"></div></div>
      <div class="form-group"><div class="col-lg-offset-2 col-lg-10"><button id="btn_filter_gid" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button> <button id="btn_reset_gid" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button> <button id="btn_excel_gid" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=wh_h(wh_t('common_export_excel', 'Export Excel'));?></button></div></div>
    </form>
  </div></div>
  <div class="box"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div><div class="table-responsive"><table id="dtb_goods_issue_delivery" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th>GI No</th><th>Posting</th><th>Delivery/SO</th><th><?=sd_h('sales_customer', 'Customer');?></th><th><?=wh_h(wh_t('common_status', 'Status'));?></th><th><?=sd_h('sales_items', 'Items');?></th><th><?=wh_h(wh_t('warehouse_qty', 'Qty'));?></th><th><?=wh_h(wh_t('warehouse_amount', 'Amount'));?></th><th>Ship Point</th><th>Vehicle/Driver</th></tr></thead><tbody></tbody></table></div></div></div>
</section>
<div id="modal_gid" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><form id="form_gid"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Post Goods Issue for Delivery</h4></div><div class="modal-body">
  <div class="row"><div class="col-sm-4"><div class="form-group"><label><?=sd_h('sales_outbound_delivery', 'Outbound Delivery');?></label><select id="delivery_select" name="delivery_id" class="form-control" required></select></div></div><div class="col-sm-2"><div class="form-group"><label><?=sd_h('sales_document_date', 'Document Date');?></label><input name="document_date" class="form-control gid-date-input" value="<?=date('Y-m-d');?>"></div></div><div class="col-sm-2"><div class="form-group"><label><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label><input name="posting_date" class="form-control gid-date-input" value="<?=date('Y-m-d');?>"></div></div><div class="col-sm-4"><div class="form-group"><label>Delivery Info</label><input id="delivery_info" class="form-control" readonly></div></div></div>
  <div class="gid-section-title"><i class="fa fa-file-text-o"></i> Dokumen Pabean Keluar</div>
  <div class="row">
    <div class="col-sm-3"><div class="form-group"><label>Jenis Dokumen BC <span class="text-red">*</span></label><select name="outbound_bc_type" id="outbound_bc_type" class="form-control gid-select" required><option value="">Pilih BC</option><?php foreach($customsDocTypes as $doc){ ?><option value="<?=gid_h($doc->jenis_dokpab);?>"><?=gid_h($doc->jenis_dokpab);?></option><?php } ?></select></div></div>
    <div class="col-sm-3"><div class="form-group"><label>Tujuan Pengeluaran <span class="text-red">*</span></label><select name="outbound_bc_purpose" id="outbound_bc_purpose" class="form-control" required><option value="">Pilih jenis BC dulu</option></select><input type="hidden" name="outbound_bc_purpose_code" id="outbound_bc_purpose_code"></div></div>
    <div class="col-sm-3"><div class="form-group"><label>No Aju</label><input name="outbound_no_aju" class="form-control" placeholder="Nomor pengajuan BC"></div></div>
    <div class="col-sm-3"><div class="form-group"><label>Tanggal Aju</label><input name="outbound_tgl_aju" class="form-control gid-date-input" placeholder="yyyy-mm-dd"></div></div>
  </div>
  <div class="row">
    <div class="col-sm-3"><div class="form-group"><label>No Daftar</label><input name="outbound_no_daftar" class="form-control" placeholder="No dokumen/daftar BC"></div></div>
    <div class="col-sm-3"><div class="form-group"><label>Tanggal Daftar</label><input name="outbound_tgl_daftar" class="form-control gid-date-input" placeholder="yyyy-mm-dd"></div></div>
    <div class="col-sm-3"><div class="form-group"><label>Kantor Bea Cukai</label><input name="outbound_customs_office" class="form-control" placeholder="KPPBC"></div></div>
    <div class="col-sm-3"><div class="form-group"><label>Negara Tujuan</label><input name="outbound_destination_country" class="form-control" placeholder="Khusus ekspor"></div></div>
  </div>
  <div class="form-group"><label>Catatan Pabean</label><textarea name="outbound_customs_remarks" class="form-control" rows="2" placeholder="Informasi tambahan dokumen pabean keluar"></textarea></div>
  <div class="gid-section-title"><i class="fa fa-cubes"></i> Material Items</div>
  <div class="table-responsive"><table class="table table-bordered table-condensed gid-item-table" id="table_gid_items"><thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('warehouse_material', 'Material'));?></th><th class="text-right">Delivery Qty</th><th class="text-right">GI Posted</th><th>GI Qty</th><th><?=wh_h(wh_t('warehouse_uom', 'UOM'));?></th><th class="text-right">Available Stock</th><th>Remark</th></tr></thead><tbody><tr><td colspan="8" class="text-center text-muted">Pilih Outbound Delivery untuk load item.</td></tr></tbody></table></div>
  <div class="form-group"><label><?=sd_h('common_remarks', 'Remarks');?></label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?=wh_h(wh_t('common_post', 'Post'));?> GI 601</button></div></form></div></div></div>
<div id="modal_gid_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Goods Issue Delivery Detail</h4></div><div class="modal-body" id="gid_detail_body"></div></div></div></div>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function gidFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),customer:$('#filter_customer').val(),status:$('#filter_status').val(),shipping_point:$('#filter_shipping_point').val(),keyword:$('#filter_keyword').val()};}
function gidError(m){$('.isi_warning_delete').text(m||<?=json_encode(wh_t('warehouse_goods_issue_delivery_process_failed', 'Goods Issue Delivery failed to process.'));?>);$('.error_data_delete').fadeIn();}
function loadCustomsPurpose(jenis, selected){
 $('#outbound_bc_purpose').html('<option value=""><?=sd_h('common_loading', 'Loading...');?></option>').trigger('change');
 $('#outbound_bc_purpose_code').val('');
 if(!jenis){$('#outbound_bc_purpose').html('<option value=""><?=wh_h(wh_t('warehouse_select_bc_type_first', 'Select BC type first'));?></option>').trigger('change');return;}
 $.post('<?=base_admin();?>modul/goods_issue_delivery/goods_issue_delivery_action.php?act=customs_purpose',{jenis_dokpab:jenis},function(r){
   var html='<option value=""><?=wh_h(wh_t('warehouse_select_outbound_purpose', 'Select outbound purpose'));?></option>';
   $.each(r.results||[],function(_,x){html+='<option value="'+$('<div>').text(x.purpose||x.text).html()+'" data-code="'+$('<div>').text(x.id).html()+'" data-kod="'+$('<div>').text(x.kod_dokpab||'').html()+'">'+$('<div>').text(x.text||x.purpose||'').html()+'</option>';});
   $('#outbound_bc_purpose').html(html);
   if(selected)$('#outbound_bc_purpose').val(selected);
   $('#outbound_bc_purpose').trigger('change');
 },'json').fail(function(xhr){console.log(xhr.responseText);$('#outbound_bc_purpose').html('<option value=""><?=wh_h(wh_t('warehouse_purpose_load_failed_short', 'Purpose failed to load'));?></option>').trigger('change');gidError(<?=json_encode(wh_t('warehouse_purpose_load_failed', 'Outbound purpose failed to load from detail_catatan.'));?>);});
}
$(function(){
 if($.fn.datepicker){$('.gid-date,.gid-date-input').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
 if($.fn.select2){$('#filter_customer,#filter_status,#filter_shipping_point,.gid-select,#outbound_bc_purpose').select2({width:'100%'});$('#delivery_select').select2({width:'100%',placeholder:<?=json_encode(wh_t('warehouse_search_open_outbound_delivery', 'Search open Outbound Delivery...'));?>,minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/goods_issue_delivery/goods_issue_delivery_action.php?act=delivery_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}}).on('select2:select',function(e){var x=e.params.data;$('#delivery_info').val((x.customer||'')+' | '+(x.shipping_point||'-')+' | '+(x.vehicle_no||'-')+'/'+(x.driver_name||'-'));loadDeliveryItems(x.id);});}
 var dt=$('#dtb_goods_issue_delivery').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[7,8,9],className:'text-right'},{width:'42px',targets:0},{width:'92px',targets:1}],fnCreatedRow:function(nRow,aData){var id=aData[aData.length-1],status=String(aData[aData.length-2]||'');var b='<div class="gid-action"><button type="button" class="btn btn-info btn-xs btn-gid-detail" data-id="'+id+'" title="<?=wh_h(wh_t('common_detail', 'Detail'));?>"><i class="fa fa-eye"></i></button>';if(status==='POSTED')b+='<button type="button" class="btn btn-danger btn-xs btn-gid-reversal" data-id="'+id+'" title="<?=wh_h(wh_t('warehouse_reversal', 'Reversal'));?>"><i class="fa fa-undo"></i></button>';b+='</div>';$('td:eq(1)',nRow).html(b);},ajax:{url:'<?=base_admin();?>modul/goods_issue_delivery/goods_issue_delivery_data.php',type:'post',data:function(d){$.extend(d,gidFilters());},error:function(xhr){console.log(xhr.responseText);gidError(<?=json_encode(wh_t('warehouse_goods_issue_delivery_load_failed', 'Goods Issue Delivery data failed to load.'));?>);}}});
 function loadDeliveryItems(id){$.post('<?=base_admin();?>modul/goods_issue_delivery/goods_issue_delivery_action.php?act=delivery_items',{delivery_id:id},function(html){$('#table_gid_items tbody').html(html);},'html').fail(function(){gidError(<?=json_encode(wh_t('warehouse_delivery_item_load_failed', 'Delivery item failed to load.'));?>);});}
 $('#btn_add_gid').on('click',function(){$('#form_gid')[0].reset();$('#delivery_select').val(null).trigger('change');$('#outbound_bc_type').val('').trigger('change');loadCustomsPurpose('');$('#delivery_info').val('');$('#table_gid_items tbody').html('<tr><td colspan="8" class="text-center text-muted"><?=wh_h(wh_t('warehouse_select_outbound_delivery_to_load_items', 'Select Outbound Delivery to load items.'));?></td></tr>');$('#modal_gid').modal('show');});
 $('#outbound_bc_type').on('change',function(){loadCustomsPurpose($(this).val());});
 $('#outbound_bc_purpose').on('change',function(){var opt=$(this).find('option:selected');$('#outbound_bc_purpose_code').val(opt.data('code')||'');});
 $('#btn_filter_gid').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
 $('#btn_reset_gid').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_customer,#filter_status').val('all').trigger('change');$('#filter_shipping_point').val('').trigger('change');dt.draw();});
 $('#btn_excel_gid').on('click',function(){window.location='<?=base_admin();?>modul/goods_issue_delivery/goods_issue_delivery_action.php?act=excel&'+$.param(gidFilters());});
 $('#form_gid').on('submit',function(e){e.preventDefault();var ok=true;$('.gid-qty').each(function(){var q=parseFloat(String($(this).val()||'0').replace(',','.'))||0,max=parseFloat($(this).data('max'))||0;if(q<0||q>max+0.00001){ok=false;$(this).closest('td').addClass('has-error');}else{$(this).closest('td').removeClass('has-error');}});if(!ok){gidError(<?=json_encode(wh_t('warehouse_gi_qty_invalid', 'GI Qty cannot be negative or exceed open delivery qty.'));?>);return;}$.post('<?=base_admin();?>modul/goods_issue_delivery/goods_issue_delivery_action.php?act=post',$(this).serialize(),function(r){if(r.status==='good'){$('#modal_gid').modal('hide');dt.draw(false);}else{gidError(r.error_message||<?=json_encode(wh_t('warehouse_goods_issue_delivery_post_failed', 'Goods Issue Delivery failed to post.'));?>);}},'json').fail(function(xhr){console.log(xhr.responseText);gidError(<?=json_encode(wh_t('warehouse_goods_issue_delivery_post_failed', 'Goods Issue Delivery failed to post.'));?>);});});
 $(document).on('click','.btn-gid-detail',function(){$.post('<?=base_admin();?>modul/goods_issue_delivery/goods_issue_delivery_action.php?act=detail',{id:$(this).data('id')},function(html){$('#gid_detail_body').html(html);$('#modal_gid_detail').modal('show');}).fail(function(){gidError(<?=json_encode(wh_t('common_detail_open_failed', 'Detail failed to open.'));?>);});});
 $(document).on('click','.btn-gid-reversal',function(){var id=$(this).data('id'),reason=prompt(<?=json_encode(wh_t('warehouse_gi_delivery_reversal_reason_prompt', 'Enter GI Delivery reversal reason:'));?>);if(!reason)return;$.post('<?=base_admin();?>modul/goods_issue_delivery/goods_issue_delivery_action.php?act=reversal',{id:id,reason:reason},function(r){if(r.status==='good'){dt.draw(false);}else{gidError(r.error_message||<?=json_encode(wh_t('warehouse_reversal_failed', 'Reversal failed.'));?>);}},'json').fail(function(xhr){console.log(xhr.responseText);gidError(<?=json_encode(wh_t('warehouse_reversal_failed', 'Reversal failed.'));?>);});});
 $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
