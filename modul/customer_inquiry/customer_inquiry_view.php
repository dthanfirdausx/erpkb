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
include_once "customer_inquiry_lib.php";
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$salesUsers = $db->query("SELECT DISTINCT sales_person FROM sales_inquiry WHERE COALESCE(sales_person,'')<>'' ORDER BY sales_person");
$summary = ciq_summary($db, array('tgl_awal'=>$defaultFrom,'tgl_akhir'=>$defaultTo));
?>
<style>
.ciq-hero{background:linear-gradient(135deg,#0f766e,#2563eb);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.ciq-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.ciq-hero p{margin:0;opacity:.92}
.ciq-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.ciq-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.ciq-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.ciq-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}.ciq-filter .form-group{margin-bottom:12px}
#dtb_customer_inquiry th,#dtb_customer_inquiry td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}
</style>
<section class="content-header">
  <h1><?=sd_h('sales_customer_inquiry', 'Customer Inquiry');?> <small>SAP SD Pre-Sales</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li><li><a href="#">Sales & Distribution</a></li><li class="active"><?=sd_h('sales_customer_inquiry', 'Customer Inquiry');?></li></ol>
</section>
<section class="content">
  <div class="ciq-hero">
    <div class="row">
      <div class="col-md-8"><h1><?=sd_h('sales_customer_inquiry', 'Customer Inquiry');?></h1><p>Mencatat permintaan awal customer sebelum quotation: kebutuhan material, estimasi qty, tanggal delivery, prioritas, dan status follow-up.</p></div>
      <div class="col-md-4 text-right"><a href="<?=base_index();?>customer-inquiry/tambah" class="btn btn-success"><i class="fa fa-plus"></i> Add Inquiry</a></div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-3"><div class="ciq-kpi"><i class="fa fa-folder-open"></i><span>Total Inquiry</span><strong><?=number_format((float)$summary->total_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="ciq-kpi"><i class="fa fa-clock-o"></i><span><?=sd_h('sales_open', 'Open');?></span><strong><?=number_format((float)$summary->open_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="ciq-kpi"><i class="fa fa-cubes"></i><span>Total Qty</span><strong><?=number_format((float)$summary->total_qty,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="ciq-kpi"><i class="fa fa-money"></i><span>Est. Amount</span><strong><?=number_format((float)$summary->total_amount,0,',','.');?></strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Inquiry</h3></div><div class="box-body">
    <form class="form-horizontal ciq-filter" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">Inquiry Date</label>
        <div class="col-lg-2"><div class="input-group date ciq-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <div class="col-lg-2"><div class="input-group date ciq-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1"><?=sd_h('sales_customer', 'Customer');?></label><div class="col-lg-5"><select id="filter_customer" class="form-control"></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=sd_h('common_status', 'Status');?></label><div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=sd_h('common_all', 'All');?></option><option>OPEN</option><option>QUOTED</option><option>WON</option><option>LOST</option><option>CANCELLED</option></select></div>
        <label class="control-label col-lg-2">Priority</label><div class="col-lg-2"><select id="filter_priority" class="form-control"><option value=""><?=sd_h('common_all', 'All');?></option><option>LOW</option><option>NORMAL</option><option>HIGH</option><option>URGENT</option></select></div>
        <label class="control-label col-lg-1">Sales</label><div class="col-lg-3"><select id="filter_sales_person" class="form-control"><option value=""><?=sd_h('common_all', 'All');?></option><?php foreach($salesUsers as $u){ ?><option value="<?=ciq_h($u->sales_person);?>"><?=ciq_h($u->sales_person);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=sd_h('common_search', 'Search');?></label><div class="col-lg-6"><input id="filter_keyword" class="form-control" placeholder="Inquiry no, customer, subject, contact, remark"></div>
        <div class="col-lg-4"><button type="button" id="btn_filter_ciq" class="btn btn-primary"><i class="fa fa-filter"></i> <?=sd_h('common_filter', 'Filter');?></button> <button type="button" id="btn_reset_ciq" class="btn btn-default"><i class="fa fa-refresh"></i> <?=sd_h('common_reset', 'Reset');?></button> <button type="button" id="btn_excel_ciq" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=sd_h('common_export_excel', 'Export Excel');?></button></div>
      </div>
    </form>
  </div></div>
  <div class="box"><div class="box-body">
    <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
    <div class="table-responsive"><table id="dtb_customer_inquiry" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=sd_h('common_no', 'No');?></th><th><?=sd_h('common_action', 'Action');?></th><th>Inquiry</th><th><?=sd_h('sales_date', 'Date');?></th><th><?=sd_h('sales_customer', 'Customer');?></th><th>Subject</th><th>Priority</th><th><?=sd_h('common_status', 'Status');?></th><th>Req. Delivery</th><th class="text-right"><?=sd_h('sales_items', 'Items');?></th><th class="text-right">Est. Amount</th><th>Sales</th></tr></thead><tbody></tbody></table></div>
  </div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function ciqFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),customer_id:$('#filter_customer').val(),status:$('#filter_status').val(),priority:$('#filter_priority').val(),sales_person:$('#filter_sales_person').val(),keyword:$('#filter_keyword').val()};}
function ciqError(m){$('.isi_warning_delete').text(m||<?=sd_js('sales_customer_inquiry_process_failed', 'Customer Inquiry data failed to process.');?>);$('.error_data_delete').fadeIn();}
$(function(){
  if($.fn.datepicker){$('.ciq-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_status,#filter_priority,#filter_sales_person').select2({width:'100%',allowClear:true});
    $('#filter_customer').select2({width:'100%',allowClear:true,placeholder:<?=sd_js('sales_search_customer', 'Search customer...');?>,minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/customer_inquiry/customer_inquiry_action.php?act=customer_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
  }
  var dt=$('#dtb_customer_inquiry').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=sd_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[9,10],className:'text-right'},{width:'42px',targets:0},{width:'116px',targets:1}],ajax:{url:'<?=base_admin();?>modul/customer_inquiry/customer_inquiry_data.php',type:'post',data:function(d){$.extend(d,ciqFilters());},error:function(xhr){console.log(xhr);ciqError(<?=sd_js('sales_customer_inquiry_load_failed', 'Customer Inquiry data failed to load.');?>);}}});
  $('#btn_filter_ciq').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_ciq').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_customer').val(null).trigger('change');$('#filter_status,#filter_priority,#filter_sales_person').val('').trigger('change');dt.draw();});
  $('#btn_excel_ciq').on('click',function(){window.location='<?=base_admin();?>modul/customer_inquiry/customer_inquiry_action.php?act=excel&'+$.param(ciqFilters());});
  $(document).on('click','.btn-ciq-cancel',function(){if(!confirm('Cancel inquiry ini?'))return;$.post('<?=base_admin();?>modul/customer_inquiry/customer_inquiry_action.php?act=status',{id:$(this).data('id'),status:'CANCELLED'},function(r){if(r.status==='good'){dt.draw(false);}else{ciqError(r.error_message||'Gagal cancel inquiry.');}},'json').fail(function(){ciqError('Gagal cancel inquiry.');});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
