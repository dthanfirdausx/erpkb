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
include_once "sales_order_monitoring_lib.php";
$defaultFrom=date('Y-01-01');$defaultTo=date('Y-m-d');
$customers=$db->query("SELECT kode_penerima,nama FROM penerima ORDER BY nama");
$salesUsers=$db->query("SELECT DISTINCT sales_id FROM sales_order WHERE COALESCE(sales_id,'')<>'' ORDER BY sales_id");
$summary=som_summary($db,array('tgl_awal'=>$defaultFrom,'tgl_akhir'=>$defaultTo,'customer'=>'all','status_so'=>'all','approval_status'=>'all','delivery_status'=>'all'));
?>
<style>
.som-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.som-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.som-hero p{margin:0;opacity:.92}
.som-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.som-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.som-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.som-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}.som-filter .form-group{margin-bottom:12px}
#dtb_sales_order_monitoring th,#dtb_sales_order_monitoring td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}.progress-xs{height:6px}
.som-detail-table th{width:180px;background:#f8fafc}
</style>
<section class="content-header">
  <h1><?=sd_h('sales_order_monitoring', 'Sales Order Monitoring');?> <small>SAP SD Fulfillment Cockpit</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li><li><a href="#">Sales & Distribution</a></li><li class="active"><?=sd_h('sales_order_monitoring', 'Sales Order Monitoring');?></li></ol>
</section>
<section class="content">
  <div class="som-hero"><div class="row"><div class="col-md-8"><h1><?=sd_h('sales_order_monitoring', 'Sales Order Monitoring');?></h1><p>Dashboard pemantauan SO dari approval, produksi, delivery, sampai overdue fulfillment. Dibuat read-only seperti SAP SD order tracking.</p></div><div class="col-md-4 text-right"><span class="label label-primary">Read Only Monitor</span></div></div></div>
  <div class="row">
    <div class="col-sm-3"><div class="som-kpi"><i class="fa fa-file-text-o"></i><span>Total SO</span><strong><?=number_format((float)$summary->total_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="som-kpi"><i class="fa fa-truck"></i><span>Full Delivered</span><strong><?=number_format((float)$summary->full_delivered,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="som-kpi"><i class="fa fa-warning"></i><span>Overdue</span><strong><?=number_format((float)$summary->overdue_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="som-kpi"><i class="fa fa-money"></i><span>Order Value</span><strong><?=number_format((float)$summary->total_amount,0,',','.');?></strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Monitoring</h3></div><div class="box-body">
    <form class="form-horizontal som-filter" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">SO Date</label><div class="col-lg-2"><div class="input-group date som-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <div class="col-lg-2"><div class="input-group date som-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1"><?=sd_h('sales_customer', 'Customer');?></label><div class="col-lg-5"><select id="filter_customer" class="form-control"><option value="all"><?=sd_h('sales_all_customer', 'All Customer');?></option><?php foreach($customers as $c){ ?><option value="<?=som_h($c->kode_penerima);?>"><?=som_h($c->kode_penerima.' - '.$c->nama);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">SO Status</label><div class="col-lg-2"><select id="filter_status_so" class="form-control"><option value="all"><?=sd_h('common_all', 'All');?></option><option>BELUM PRODUKSI</option><option>PRODUKSI BELUM FULL</option><option>PROSES PRODUKSI</option><option>DIKIRIM SEBAGIAN</option><option>SUDAH DIKIRIM</option></select></div>
        <label class="control-label col-lg-2">Approval</label><div class="col-lg-2"><select id="filter_approval_status" class="form-control"><option value="all"><?=sd_h('common_all', 'All');?></option><option>PENDING</option><option>APPROVED</option><option>REJECTED</option><option>CANCELLED</option><option>SUBMITTED</option><option>DRAFT</option></select></div>
        <label class="control-label col-lg-1">Delivery</label><div class="col-lg-3"><select id="filter_delivery_status" class="form-control"><option value="all"><?=sd_h('common_all', 'All');?></option><option value="NOT_DELIVERED">Not Delivered</option><option value="PARTIAL">Partial</option><option value="FULL">Full Delivered</option></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">Sales</label><div class="col-lg-2"><select id="filter_sales_person" class="form-control"><option value=""><?=sd_h('common_all', 'All');?></option><?php foreach($salesUsers as $u){ ?><option value="<?=som_h($u->sales_id);?>"><?=som_h($u->sales_id);?></option><?php } ?></select></div>
        <label class="control-label col-lg-1"><?=sd_h('common_search', 'Search');?></label><div class="col-lg-4"><input id="filter_keyword" class="form-control" placeholder="SO/PO/customer/note/address"></div>
        <div class="col-lg-3"><label style="padding-top:7px"><input type="checkbox" id="filter_overdue_only" value="1"> Overdue only</label></div>
      </div>
      <div class="form-group"><div class="col-lg-offset-2 col-lg-10"><button type="button" id="btn_filter_som" class="btn btn-primary"><i class="fa fa-filter"></i> <?=sd_h('common_filter', 'Filter');?></button> <button type="button" id="btn_reset_som" class="btn btn-default"><i class="fa fa-refresh"></i> <?=sd_h('common_reset', 'Reset');?></button> <button type="button" id="btn_excel_som" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=sd_h('common_export_excel', 'Export Excel');?></button></div></div>
    </form>
  </div></div>
  <div class="box"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div><div class="table-responsive">
    <table id="dtb_sales_order_monitoring" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=sd_h('common_no', 'No');?></th><th><?=sd_h('common_action', 'Action');?></th><th><?=sd_h('sales_order', 'Sales Order');?></th><th><?=sd_h('sales_date', 'Date');?></th><th><?=sd_h('sales_customer', 'Customer');?></th><th><?=sd_h('sales_delivery_date', 'Delivery Date');?></th><th>Approval</th><th>SO Status</th><th>SO Qty</th><th>Production</th><th>Delivery</th><th>Value</th><th>Curr</th><th>Sales</th></tr></thead><tbody></tbody></table>
  </div></div></div>
</section>
<div id="modal_som_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Sales Order Monitoring Detail</h4></div><div class="modal-body" id="som_detail_body"></div></div></div></div>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function somFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),customer:$('#filter_customer').val(),status_so:$('#filter_status_so').val(),approval_status:$('#filter_approval_status').val(),delivery_status:$('#filter_delivery_status').val(),sales_person:$('#filter_sales_person').val(),overdue_only:$('#filter_overdue_only').is(':checked')?'1':'',keyword:$('#filter_keyword').val()};}
function somError(m){$('.isi_warning_delete').text(m||<?=sd_js('sales_monitoring_process_failed', 'Monitoring data failed to process.');?>);$('.error_data_delete').fadeIn();}
$(function(){
  if($.fn.datepicker){$('.som-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_customer,#filter_status_so,#filter_approval_status,#filter_delivery_status,#filter_sales_person').select2({width:'100%'});}
  var dt=$('#dtb_sales_order_monitoring').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=sd_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[8,9,10,11],className:'text-right'},{width:'42px',targets:0},{width:'74px',targets:1}],ajax:{url:'<?=base_admin();?>modul/sales_order_monitoring/sales_order_monitoring_data.php',type:'post',data:function(d){$.extend(d,somFilters());},error:function(xhr){console.log(xhr);somError(<?=sd_js('sales_order_monitoring_load_failed', 'Sales Order Monitoring data failed to load.');?>);}}});
  $('#btn_filter_som').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_som').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_overdue_only').prop('checked',false);$('#filter_customer').val('all').trigger('change');$('#filter_status_so,#filter_approval_status,#filter_delivery_status').val('all').trigger('change');$('#filter_sales_person').val('').trigger('change');dt.draw();});
  $('#btn_excel_som').on('click',function(){window.location='<?=base_admin();?>modul/sales_order_monitoring/sales_order_monitoring_action.php?act=excel&'+$.param(somFilters());});
  $(document).on('click','.btn-som-detail',function(){$.post('<?=base_admin();?>modul/sales_order_monitoring/sales_order_monitoring_action.php?act=detail',{id:$(this).data('id')},function(html){$('#som_detail_body').html(html);$('#modal_som_detail').modal('show');}).fail(function(){somError('Detail gagal dibuka.');});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
