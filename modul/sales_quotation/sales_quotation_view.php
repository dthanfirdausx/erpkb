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
include_once "sales_quotation_lib.php";
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$salesUsers = $db->query("SELECT DISTINCT sales_id FROM sales_quotation WHERE COALESCE(sales_id,'')<>'' ORDER BY sales_id");
$summary = sq_summary($db, array('tgl_awal'=>$defaultFrom,'tgl_akhir'=>$defaultTo));
?>
<style>
.sq-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.sq-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.sq-hero p{margin:0;opacity:.92}
.sq-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.sq-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.sq-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.sq-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}.sq-filter .form-group{margin-bottom:12px}
#dtb_sales_quotation th,#dtb_sales_quotation td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}
</style>
<section class="content-header">
  <h1><?=sd_h('sales_quotation', 'Sales Quotation');?> <small>SAP SD Quotation</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li><li><a href="#">Sales & Distribution</a></li><li class="active"><?=sd_h('sales_quotation', 'Sales Quotation');?></li></ol>
</section>
<section class="content">
  <div class="sq-hero"><div class="row"><div class="col-md-8"><h1><?=sd_h('sales_quotation', 'Sales Quotation');?></h1><p>Mengelola penawaran resmi ke customer dari inquiry/pre-sales sampai status sent, accepted, rejected, expired, atau cancelled.</p></div><div class="col-md-4 text-right"><a href="<?=base_index();?>sales-quotation/tambah" class="btn btn-success"><i class="fa fa-plus"></i> Add Quotation</a></div></div></div>
  <div class="row">
    <div class="col-sm-3"><div class="sq-kpi"><i class="fa fa-file-text-o"></i><span>Total Quotation</span><strong><?=number_format((float)$summary->total_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="sq-kpi"><i class="fa fa-clock-o"></i><span>Open/Sent</span><strong><?=number_format((float)$summary->open_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="sq-kpi"><i class="fa fa-cubes"></i><span>Total Qty</span><strong><?=number_format((float)$summary->total_qty,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="sq-kpi"><i class="fa fa-money"></i><span>Quote Amount</span><strong><?=number_format((float)$summary->total_amount,0,',','.');?></strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Quotation</h3></div><div class="box-body">
    <form class="form-horizontal sq-filter" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">Quotation Date</label><div class="col-lg-2"><div class="input-group date sq-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <div class="col-lg-2"><div class="input-group date sq-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1"><?=sd_h('sales_customer', 'Customer');?></label><div class="col-lg-5"><select id="filter_customer" class="form-control"></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=sd_h('common_status', 'Status');?></label><div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=sd_h('common_all', 'All');?></option><option>OPEN</option><option>SENT</option><option>ACCEPTED</option><option>REJECTED</option><option>EXPIRED</option><option>CANCELLED</option></select></div>
        <label class="control-label col-lg-2">Sales</label><div class="col-lg-2"><select id="filter_sales_person" class="form-control"><option value=""><?=sd_h('common_all', 'All');?></option><?php foreach($salesUsers as $u){ ?><option value="<?=sq_h($u->sales_id);?>"><?=sq_h($u->sales_id);?></option><?php } ?></select></div>
        <label class="control-label col-lg-1"><?=sd_h('common_search', 'Search');?></label><div class="col-lg-3"><input id="filter_keyword" class="form-control" placeholder="Quotation no/customer/subject/contact"></div>
      </div>
      <div class="form-group"><div class="col-lg-offset-2 col-lg-10"><button type="button" id="btn_filter_sq" class="btn btn-primary"><i class="fa fa-filter"></i> <?=sd_h('common_filter', 'Filter');?></button> <button type="button" id="btn_reset_sq" class="btn btn-default"><i class="fa fa-refresh"></i> <?=sd_h('common_reset', 'Reset');?></button> <button type="button" id="btn_excel_sq" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=sd_h('common_export_excel', 'Export Excel');?></button></div></div>
    </form>
  </div></div>
  <div class="box"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div><div class="table-responsive">
    <table id="dtb_sales_quotation" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=sd_h('common_no', 'No');?></th><th><?=sd_h('common_action', 'Action');?></th><th>Quotation</th><th><?=sd_h('sales_date', 'Date');?></th><th><?=sd_h('sales_customer', 'Customer');?></th><th>Subject</th><th><?=sd_h('common_status', 'Status');?></th><th>Valid Until</th><th class="text-right"><?=sd_h('sales_items', 'Items');?></th><th class="text-right"><?=sd_h('sales_amount', 'Amount');?></th><th><?=sd_h('sales_currency', 'Currency');?></th><th>Sales</th></tr></thead><tbody></tbody></table>
  </div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function sqFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),customer_id:$('#filter_customer').val(),status:$('#filter_status').val(),sales_person:$('#filter_sales_person').val(),keyword:$('#filter_keyword').val()};}
function sqError(m){$('.isi_warning_delete').text(m||<?=sd_js('sales_quotation_process_failed', 'Sales Quotation data failed to process.');?>);$('.error_data_delete').fadeIn();}
$(function(){
  if($.fn.datepicker){$('.sq-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_status,#filter_sales_person').select2({width:'100%',allowClear:true});$('#filter_customer').select2({width:'100%',allowClear:true,placeholder:<?=sd_js('sales_search_customer', 'Search customer...');?>,minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/sales_quotation/sales_quotation_action.php?act=customer_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
  var dt=$('#dtb_sales_quotation').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=sd_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[8,9],className:'text-right'},{width:'42px',targets:0},{width:'116px',targets:1}],ajax:{url:'<?=base_admin();?>modul/sales_quotation/sales_quotation_data.php',type:'post',data:function(d){$.extend(d,sqFilters());},error:function(xhr){console.log(xhr);sqError(<?=sd_js('sales_quotation_load_failed', 'Sales Quotation data failed to load.');?>);}}});
  $('#btn_filter_sq').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_sq').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_customer').val(null).trigger('change');$('#filter_status,#filter_sales_person').val('').trigger('change');dt.draw();});
  $('#btn_excel_sq').on('click',function(){window.location='<?=base_admin();?>modul/sales_quotation/sales_quotation_action.php?act=excel&'+$.param(sqFilters());});
  $(document).on('click','.btn-sq-status',function(){if(!confirm('Ubah status quotation menjadi '+$(this).data('status')+'?'))return;$.post('<?=base_admin();?>modul/sales_quotation/sales_quotation_action.php?act=status',{id:$(this).data('id'),status:$(this).data('status')},function(r){if(r.status==='good'){dt.draw(false);}else{sqError(r.error_message||'Gagal update status.');}},'json').fail(function(){sqError('Gagal update status.');});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
