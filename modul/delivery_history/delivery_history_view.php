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
include_once "delivery_history_lib.php";
$defaultFrom = date('Y-01-01');
$defaultTo = date('Y-m-d');
$customers = $db->query("SELECT kode_penerima,nama FROM penerima ORDER BY nama");
$shippingPoints = $db->query("SELECT shipping_code,shipping_name FROM erp_shipping_point WHERE status='Aktif' ORDER BY shipping_code");
$summary = dh_summary($db, array('tgl_awal'=>$defaultFrom,'tgl_akhir'=>$defaultTo,'customer'=>'all','status'=>'all','shipping_point'=>'','keyword'=>''));
?>
<style>
.dh-hero{background:linear-gradient(135deg,#334155,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.dh-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.dh-hero p{margin:0;opacity:.92}
.dh-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.dh-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.dh-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.dh-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}.dh-filter .form-group{margin-bottom:12px}
#dtb_delivery_history th,#dtb_delivery_history td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}.progress-xs{height:6px}.dh-detail-table th,.dh-detail-table td{font-size:12px;vertical-align:middle}
</style>
<section class="content-header"><h1><?=sd_h('sales_delivery_history', 'Delivery History');?> <small>SAP SD Document Flow</small></h1><ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li><li><a href="#">Sales & Distribution</a></li><li class="active"><?=sd_h('sales_delivery_history', 'Delivery History');?></li></ol></section>
<section class="content">
  <div class="dh-hero"><h1><?=sd_h('sales_delivery_history', 'Delivery History');?></h1><p>Monitoring alur dokumen pengiriman dari Outbound Delivery, Picking, Packing List, Surat Jalan, sampai Goods Issue 601.</p></div>
  <div class="row">
    <div class="col-sm-3"><div class="dh-kpi"><i class="fa fa-truck"></i><span>Total Delivery</span><strong><?=number_format((float)$summary->total_delivery,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="dh-kpi"><i class="fa fa-check-circle"></i><span>PGI/Completed</span><strong><?=number_format((float)$summary->completed_delivery,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="dh-kpi"><i class="fa fa-upload"></i><span>GI Posted</span><strong><?=number_format((float)$summary->posted_gi,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="dh-kpi"><i class="fa fa-money"></i><span>Delivery Value</span><strong><?=number_format((float)$summary->amount,0,',','.');?></strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Delivery History</h3></div><div class="box-body">
    <form class="form-horizontal dh-filter" onsubmit="return false;">
      <div class="form-group"><label class="control-label col-lg-2"><?=sd_h('sales_delivery_date', 'Delivery Date');?></label><div class="col-lg-2"><div class="input-group date dh-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div><div class="col-lg-2"><div class="input-group date dh-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div><label class="control-label col-lg-1"><?=sd_h('sales_customer', 'Customer');?></label><div class="col-lg-5"><select id="filter_customer" class="form-control"><option value="all"><?=sd_h('sales_all_customer', 'All Customer');?></option><?php foreach($customers as $c){ ?><option value="<?=dh_h($c->kode_penerima);?>"><?=dh_h($c->kode_penerima.' - '.$c->nama);?></option><?php } ?></select></div></div>
      <div class="form-group"><label class="control-label col-lg-2">Delivery Status</label><div class="col-lg-2"><select id="filter_status" class="form-control"><option value="all"><?=sd_h('common_all', 'All');?></option><option>CREATED</option><option>PICKING</option><option>PICKED</option><option>PACKED</option><option>PGI</option><option>COMPLETED</option><option>CANCELLED</option></select></div><label class="control-label col-lg-2"><?=sd_h('sales_shipping_point', 'Shipping Point');?></label><div class="col-lg-2"><select id="filter_shipping_point" class="form-control"><option value=""><?=sd_h('common_all', 'All');?></option><?php foreach($shippingPoints as $sp){ ?><option value="<?=dh_h($sp->shipping_code);?>"><?=dh_h($sp->shipping_code.' - '.$sp->shipping_name);?></option><?php } ?></select></div><label class="control-label col-lg-1"><?=sd_h('common_search', 'Search');?></label><div class="col-lg-3"><input id="filter_keyword" class="form-control" placeholder="Delivery/SO/customer/SJ/GI/packing"></div></div>
      <div class="form-group"><div class="col-lg-offset-2 col-lg-10"><button id="btn_filter_dh" class="btn btn-primary"><i class="fa fa-filter"></i> <?=sd_h('common_filter', 'Filter');?></button> <button id="btn_reset_dh" class="btn btn-default"><i class="fa fa-refresh"></i> <?=sd_h('common_reset', 'Reset');?></button> <button id="btn_excel_dh" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=sd_h('common_export_excel', 'Export Excel');?></button></div></div>
    </form>
  </div></div>
  <div class="box"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div><div class="table-responsive"><table id="dtb_delivery_history" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=sd_h('common_no', 'No');?></th><th><?=sd_h('common_action', 'Action');?></th><th>Delivery/SO</th><th><?=sd_h('sales_date', 'Date');?></th><th><?=sd_h('sales_customer', 'Customer');?></th><th><?=sd_h('common_status', 'Status');?></th><th>Flow</th><th><?=sd_h('sales_picking', 'Picking');?></th><th>Packing</th><th><?=sd_h('sales_surat_jalan', 'Surat Jalan');?></th><th>GI</th><th>Delivery Qty</th><th>GI Qty</th><th>Value</th><th>Ship Point</th><th>Vehicle/Driver</th></tr></thead><tbody></tbody></table></div></div></div>
</section>
<div id="modal_dh_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Delivery Document Flow</h4></div><div class="modal-body" id="dh_detail_body"></div></div></div></div>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function dhFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),customer:$('#filter_customer').val(),status:$('#filter_status').val(),shipping_point:$('#filter_shipping_point').val(),keyword:$('#filter_keyword').val()};}
function dhError(m){$('.isi_warning_delete').text(m||<?=sd_js('sales_delivery_history_process_failed', 'Delivery History data failed to process.');?>);$('.error_data_delete').fadeIn();}
$(function(){
 if($.fn.datepicker){$('.dh-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
 if($.fn.select2){$('#filter_customer,#filter_status,#filter_shipping_point').select2({width:'100%'});}
 var dt=$('#dtb_delivery_history').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,scrollX:true,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=sd_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[11,12,13],className:'text-right'},{width:'42px',targets:0},{width:'56px',targets:1}],ajax:{url:'<?=base_admin();?>modul/delivery_history/delivery_history_data.php',type:'post',data:function(d){$.extend(d,dhFilters());},error:function(xhr){console.log(xhr.responseText);dhError(<?=sd_js('sales_delivery_history_load_failed', 'Delivery History data failed to load.');?>);}}});
 $('#btn_filter_dh').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
 $('#btn_reset_dh').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_customer,#filter_status').val('all').trigger('change');$('#filter_shipping_point').val('').trigger('change');dt.draw();});
 $('#btn_excel_dh').on('click',function(){window.location='<?=base_admin();?>modul/delivery_history/delivery_history_action.php?act=excel&'+$.param(dhFilters());});
 $(document).on('click','.btn-dh-detail',function(){$.post('<?=base_admin();?>modul/delivery_history/delivery_history_action.php?act=detail',{id:$(this).data('id')},function(html){$('#dh_detail_body').html(html);$('#modal_dh_detail').modal('show');}).fail(function(xhr){console.log(xhr.responseText);dhError('Detail delivery history gagal dibuka.');});});
 $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
