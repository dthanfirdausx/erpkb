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
include_once "sales_order_approval_lib.php";
$defaultFrom=date('Y-01-01');$defaultTo=date('Y-m-d');
$customers=$db->query("SELECT kode_penerima,nama FROM penerima ORDER BY nama");
$salesUsers=$db->query("SELECT DISTINCT sales_id FROM sales_order WHERE COALESCE(sales_id,'')<>'' ORDER BY sales_id");
$summary=soa_summary($db,array('tgl_awal'=>$defaultFrom,'tgl_akhir'=>$defaultTo,'approval_status'=>'all'));
?>
<style>
.soa-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.soa-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.soa-hero p{margin:0;opacity:.92}
.soa-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.soa-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.soa-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.soa-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}.soa-filter .form-group{margin-bottom:12px}
#dtb_sales_order_approval th,#dtb_sales_order_approval td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}.soa-detail-table th{width:180px;background:#f8fafc}
</style>
<section class="content-header">
  <h1><?=sd_h('sales_order_approval', 'Sales Order Approval');?> <small>SAP SD Release Strategy</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li><li><a href="#">Sales & Distribution</a></li><li class="active"><?=sd_h('sales_order_approval', 'Sales Order Approval');?></li></ol>
</section>
<section class="content">
  <div class="soa-hero"><div class="row"><div class="col-md-8"><h1><?=sd_h('sales_order_approval', 'Sales Order Approval');?></h1><p>Worklist approval Sales Order sebelum dokumen diproses ke produksi, delivery, dan billing. Approval mengikuti konsep SAP release strategy sederhana.</p></div><div class="col-md-4 text-right"><span class="label label-primary">Release Workflow</span></div></div></div>
  <div class="row">
    <div class="col-sm-3"><div class="soa-kpi"><i class="fa fa-file-text-o"></i><span>Total SO</span><strong><?=number_format((float)$summary->total_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="soa-kpi"><i class="fa fa-clock-o"></i><span>Pending</span><strong><?=number_format((float)$summary->pending_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="soa-kpi"><i class="fa fa-check-circle"></i><span><?=sd_h('sales_approved', 'Approved');?></span><strong><?=number_format((float)$summary->approved_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="soa-kpi"><i class="fa fa-money"></i><span>Total Value</span><strong><?=number_format((float)$summary->total_amount,0,',','.');?></strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Approval</h3></div><div class="box-body">
    <form class="form-horizontal soa-filter" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">SO Date</label><div class="col-lg-2"><div class="input-group date soa-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <div class="col-lg-2"><div class="input-group date soa-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1"><?=sd_h('sales_customer', 'Customer');?></label><div class="col-lg-5"><select id="filter_customer" class="form-control"><option value="all"><?=sd_h('sales_all_customer', 'All Customer');?></option><?php foreach($customers as $c){ ?><option value="<?=soa_h($c->kode_penerima);?>"><?=soa_h($c->kode_penerima.' - '.$c->nama);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">Approval Status</label><div class="col-lg-2"><select id="filter_approval_status" class="form-control"><option value="all"><?=sd_h('common_all', 'All');?></option><option value="PENDING" selected>PENDING</option><option>SUBMITTED</option><option>APPROVED</option><option>REJECTED</option><option>CANCELLED</option><option>DRAFT</option></select></div>
        <label class="control-label col-lg-2">Sales</label><div class="col-lg-2"><select id="filter_sales_person" class="form-control"><option value=""><?=sd_h('common_all', 'All');?></option><?php foreach($salesUsers as $u){ ?><option value="<?=soa_h($u->sales_id);?>"><?=soa_h($u->sales_id);?></option><?php } ?></select></div>
        <label class="control-label col-lg-1"><?=sd_h('common_search', 'Search');?></label><div class="col-lg-3"><input id="filter_keyword" class="form-control" placeholder="SO/PO/customer/note"></div>
      </div>
      <div class="form-group"><div class="col-lg-offset-2 col-lg-10"><label style="margin-right:14px"><input type="checkbox" id="filter_my_worklist" value="1"> My worklist</label><button type="button" id="btn_filter_soa" class="btn btn-primary"><i class="fa fa-filter"></i> <?=sd_h('common_filter', 'Filter');?></button> <button type="button" id="btn_reset_soa" class="btn btn-default"><i class="fa fa-refresh"></i> <?=sd_h('common_reset', 'Reset');?></button> <button type="button" id="btn_excel_soa" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=sd_h('common_export_excel', 'Export Excel');?></button></div></div>
    </form>
  </div></div>
  <div class="box"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div><div class="table-responsive">
    <table id="dtb_sales_order_approval" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=sd_h('common_no', 'No');?></th><th><?=sd_h('common_action', 'Action');?></th><th><?=sd_h('sales_order', 'Sales Order');?></th><th><?=sd_h('sales_date', 'Date');?></th><th><?=sd_h('sales_customer', 'Customer');?></th><th><?=sd_h('common_status', 'Status');?></th><th>Release</th><th><?=sd_h('sales_items', 'Items');?></th><th><?=sd_h('sales_qty', 'Qty');?></th><th>Value</th><th>Curr</th><th>Sales</th><th>Note</th></tr></thead><tbody></tbody></table>
  </div></div></div>
</section>
<div id="modal_soa_action" class="modal fade"><div class="modal-dialog"><div class="modal-content"><form id="form_soa_action"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title" id="soa_action_title">Approval Action</h4></div><div class="modal-body"><input type="hidden" name="id" id="soa_action_id"><input type="hidden" name="decision" id="soa_action_decision"><div class="form-group"><label>Note</label><textarea name="note" class="form-control" rows="3" placeholder="Catatan approval/rejection"></textarea></div></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal" type="button"><?=sd_h('common_close', 'Close');?></button><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> <?=sd_h('common_submit', 'Submit');?></button></div></form></div></div></div>
<div id="modal_soa_detail" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Sales Order Approval Detail</h4></div><div class="modal-body" id="soa_detail_body"></div></div></div></div>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function soaFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),customer:$('#filter_customer').val(),approval_status:$('#filter_approval_status').val(),sales_person:$('#filter_sales_person').val(),my_worklist:$('#filter_my_worklist').is(':checked')?'1':'',keyword:$('#filter_keyword').val()};}
function soaError(m){$('.isi_warning_delete').text(m||<?=sd_js('sales_approval_process_failed', 'Approval data failed to process.');?>);$('.error_data_delete').fadeIn();}
$(function(){
  if($.fn.datepicker){$('.soa-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_customer,#filter_approval_status,#filter_sales_person').select2({width:'100%'});}
  var dt=$('#dtb_sales_order_approval').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=sd_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[7,8,9],className:'text-right'},{width:'42px',targets:0},{width:'100px',targets:1}],ajax:{url:'<?=base_admin();?>modul/sales_order_approval/sales_order_approval_data.php',type:'post',data:function(d){$.extend(d,soaFilters());},error:function(xhr){console.log(xhr);soaError(<?=sd_js('sales_order_approval_load_failed', 'Sales Order Approval data failed to load.');?>);}}});
  $('#btn_filter_soa').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_soa').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_my_worklist').prop('checked',false);$('#filter_customer').val('all').trigger('change');$('#filter_approval_status').val('PENDING').trigger('change');$('#filter_sales_person').val('').trigger('change');dt.draw();});
  $('#btn_excel_soa').on('click',function(){window.location='<?=base_admin();?>modul/sales_order_approval/sales_order_approval_action.php?act=excel&'+$.param(soaFilters());});
  $(document).on('click','.btn-soa-act',function(){var decision=$(this).data('act');$('#soa_action_id').val($(this).data('id'));$('#soa_action_decision').val(decision);$('#soa_action_title').text(decision==='approve'?'Approve Sales Order':'Reject Sales Order');$('#form_soa_action textarea[name="note"]').val('');$('#modal_soa_action').modal('show');});
  $('#form_soa_action').on('submit',function(e){e.preventDefault();var decision=$('#soa_action_decision').val();$.post('<?=base_admin();?>modul/sales_order_approval/sales_order_approval_action.php?act='+decision,$(this).serialize(),function(r){if(r.status==='good'){$('#modal_soa_action').modal('hide');dt.draw(false);}else{soaError(r.error_message||<?=sd_js('sales_approval_failed', 'Approval failed to process.');?>);}},'json').fail(function(xhr){console.log(xhr.responseText);soaError(<?=sd_js('sales_approval_failed', 'Approval failed to process.');?>);});});
  $(document).on('click','.btn-soa-detail,.btn-soa-history',function(){var mode=$(this).hasClass('btn-soa-history')?'history':'detail';$.post('<?=base_admin();?>modul/sales_order_approval/sales_order_approval_action.php?act='+mode,{id:$(this).data('id')},function(html){$('#soa_detail_body').html(html);$('#modal_soa_detail').modal('show');}).fail(function(){soaError('Detail gagal dibuka.');});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
