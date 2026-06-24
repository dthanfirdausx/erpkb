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
include_once "quotation_follow_up_lib.php";
$defaultFrom=date('Y-m-01');$defaultTo=date('Y-m-d');
$salesUsers=$db->query("SELECT sales_id AS username FROM sales_quotation WHERE COALESCE(sales_id,'')<>'' UNION SELECT sales_person AS username FROM sales_quotation_followup WHERE COALESCE(sales_person,'')<>'' ORDER BY username");
$summary=qfu_summary($db,array('tgl_awal'=>$defaultFrom,'tgl_akhir'=>$defaultTo));
?>
<style>
.qfu-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.qfu-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.qfu-hero p{margin:0;opacity:.92}
.qfu-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.qfu-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.qfu-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.qfu-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}.qfu-filter .form-group{margin-bottom:12px}
#dtb_quotation_follow_up th,#dtb_quotation_follow_up td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}
</style>
<section class="content-header">
  <h1><?=sd_h('sales_quotation_follow_up', 'Quotation Follow Up');?> <small>SAP SD Activity Tracking</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li><li><a href="#">Sales & Distribution</a></li><li class="active"><?=sd_h('sales_quotation_follow_up', 'Quotation Follow Up');?></li></ol>
</section>
<section class="content">
  <div class="qfu-hero"><div class="row"><div class="col-md-8"><h1><?=sd_h('sales_quotation_follow_up', 'Quotation Follow Up');?></h1><p>Monitoring aktivitas follow-up quotation: reminder, negosiasi, klarifikasi teknis, revisi harga, closing, next action, dan probability.</p></div><div class="col-md-4 text-right"><span class="label label-primary">Pre-Sales Control</span></div></div></div>
  <div class="row">
    <div class="col-sm-3"><div class="qfu-kpi"><i class="fa fa-file-text-o"></i><span>Quotation</span><strong><?=number_format((float)$summary->total_quotes,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="qfu-kpi"><i class="fa fa-phone"></i><span>Never Followed</span><strong><?=number_format((float)$summary->never_followed,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="qfu-kpi"><i class="fa fa-calendar"></i><span>Due Follow Up</span><strong><?=number_format((float)$summary->due_today,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="qfu-kpi"><i class="fa fa-percent"></i><span>Avg Probability</span><strong><?=number_format((float)$summary->avg_probability,1,',','.');?>%</strong></div></div>
  </div>
  <div class="box"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Follow Up</h3></div><div class="box-body">
    <form class="form-horizontal qfu-filter" onsubmit="return false;">
      <div class="form-group">
        <label class="control-label col-lg-2">Quotation Date</label><div class="col-lg-2"><div class="input-group date qfu-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <div class="col-lg-2"><div class="input-group date qfu-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
        <label class="control-label col-lg-1"><?=sd_h('sales_customer', 'Customer');?></label><div class="col-lg-5"><select id="filter_customer" class="form-control"></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2">Quote Status</label><div class="col-lg-2"><select id="filter_quote_status" class="form-control"><option value=""><?=sd_h('common_all', 'All');?></option><option>OPEN</option><option>SENT</option><option>ACCEPTED</option><option>REJECTED</option><option>EXPIRED</option><option>CANCELLED</option></select></div>
        <label class="control-label col-lg-2">Follow Up Status</label><div class="col-lg-2"><select id="filter_followup_status" class="form-control"><option value=""><?=sd_h('common_all', 'All');?></option><option>OPEN</option><option>WAITING_CUSTOMER</option><option>NEED_REVISION</option><option>WON</option><option>LOST</option><option>CANCELLED</option></select></div>
        <label class="control-label col-lg-1">Sales</label><div class="col-lg-3"><select id="filter_sales_person" class="form-control"><option value=""><?=sd_h('common_all', 'All');?></option><?php foreach($salesUsers as $u){ ?><option value="<?=qfu_h($u->username);?>"><?=qfu_h($u->username);?></option><?php } ?></select></div>
      </div>
      <div class="form-group">
        <label class="control-label col-lg-2"><?=sd_h('common_search', 'Search');?></label><div class="col-lg-5"><input id="filter_keyword" class="form-control" placeholder="Quotation/customer/subject/next action"></div>
        <div class="col-lg-2"><label style="padding-top:7px"><input type="checkbox" id="filter_due_only" value="1"> Due only</label></div>
        <div class="col-lg-3"><button type="button" id="btn_filter_qfu" class="btn btn-primary"><i class="fa fa-filter"></i> <?=sd_h('common_filter', 'Filter');?></button> <button type="button" id="btn_reset_qfu" class="btn btn-default"><i class="fa fa-refresh"></i> <?=sd_h('common_reset', 'Reset');?></button> <button type="button" id="btn_excel_qfu" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=sd_h('common_export_excel', 'Export Excel');?></button></div>
      </div>
    </form>
  </div></div>
  <div class="box"><div class="box-body"><div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div><div class="table-responsive">
    <table id="dtb_quotation_follow_up" class="table table-bordered table-striped table-condensed" style="width:100%"><thead><tr><th><?=sd_h('common_no', 'No');?></th><th><?=sd_h('common_action', 'Action');?></th><th>Quotation</th><th><?=sd_h('sales_customer', 'Customer');?></th><th>Subject</th><th>Quote Status</th><th>Last Follow Up</th><th>FU Status</th><th>Next Follow Up</th><th>Prob.</th><th>Sales</th><th>Summary</th></tr></thead><tbody></tbody></table>
  </div></div></div>
</section>
<div id="modal_qfu" class="modal fade"><div class="modal-dialog modal-lg"><div class="modal-content"><form id="form_qfu"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Add Quotation Follow Up</h4></div><div class="modal-body">
  <input type="hidden" name="quotation_id" id="qfu_quotation_id">
  <div class="alert alert-info"><strong id="qfu_quote_no"></strong> - catat hasil komunikasi terbaru dan next action.</div>
  <div class="row">
    <div class="col-sm-4"><div class="form-group"><label>Follow Up Date</label><input name="followup_date" class="form-control qfu-datetime" value="<?=date('Y-m-d H:i');?>"></div></div>
    <div class="col-sm-4"><div class="form-group"><label>Contact Method</label><select name="contact_method" class="form-control qfu-select"><option>PHONE</option><option>EMAIL</option><option>WHATSAPP</option><option>MEETING</option><option>VISIT</option><option>OTHER</option></select></div></div>
    <div class="col-sm-4"><div class="form-group"><label>Activity Type</label><select name="activity_type" class="form-control qfu-select"><option>REMINDER</option><option>NEGOTIATION</option><option>TECHNICAL_CLARIFICATION</option><option>PRICE_REVISION</option><option>CLOSING</option><option>OTHER</option></select></div></div>
  </div>
  <div class="row">
    <div class="col-sm-4"><div class="form-group"><label>Contact Person</label><input name="contact_person" class="form-control"></div></div>
    <div class="col-sm-4"><div class="form-group"><label>Sales Person</label><input name="sales_person" class="form-control" value="<?=qfu_h(qfu_username());?>"></div></div>
    <div class="col-sm-4"><div class="form-group"><label>Result Status</label><select name="result_status" class="form-control qfu-select"><option>OPEN</option><option>WAITING_CUSTOMER</option><option>NEED_REVISION</option><option>WON</option><option>LOST</option><option>CANCELLED</option></select></div></div>
  </div>
  <div class="row">
    <div class="col-sm-4"><div class="form-group"><label>Probability %</label><input name="probability_percent" class="form-control text-right" value="50"></div></div>
    <div class="col-sm-4"><div class="form-group"><label>Next Follow Up</label><input name="next_followup_date" class="form-control qfu-datetime" value="<?=date('Y-m-d H:i', strtotime('+3 days'));?>"></div></div>
    <div class="col-sm-4"><div class="form-group"><label>Lost Reason</label><input name="lost_reason" class="form-control"></div></div>
  </div>
  <div class="form-group"><label>Discussion Summary</label><textarea name="discussion_summary" class="form-control" rows="3" required></textarea></div>
  <div class="form-group"><label>Next Action</label><input name="next_action" class="form-control" required></div>
</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=sd_h('common_close', 'Close');?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Follow Up</button></div></form></div></div></div>
<div id="modal_qfu_history" class="modal fade"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Follow Up History</h4></div><div class="modal-body" id="qfu_history_body"></div></div></div></div>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function qfuFilters(){return{tgl_awal:$('#filter_tgl_awal').val(),tgl_akhir:$('#filter_tgl_akhir').val(),customer_id:$('#filter_customer').val(),quote_status:$('#filter_quote_status').val(),followup_status:$('#filter_followup_status').val(),sales_person:$('#filter_sales_person').val(),due_only:$('#filter_due_only').is(':checked')?'1':'',keyword:$('#filter_keyword').val()};}
function qfuError(m){$('.isi_warning_delete').text(m||<?=sd_js('sales_follow_up_process_failed', 'Follow Up data failed to process.');?>);$('.error_data_delete').fadeIn();}
$(function(){
  if($.fn.datepicker){$('.qfu-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_quote_status,#filter_followup_status,#filter_sales_person,.qfu-select').select2({width:'100%',allowClear:true});$('#filter_customer').select2({width:'100%',allowClear:true,placeholder:<?=sd_js('sales_search_customer', 'Search customer...');?>,minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/quotation_follow_up/quotation_follow_up_action.php?act=customer_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});}
  var dt=$('#dtb_quotation_follow_up').DataTable({bProcessing:true,bServerSide:true,pageLength:25,ordering:false,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:<?=sd_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[9],className:'text-right'},{width:'42px',targets:0},{width:'76px',targets:1}],ajax:{url:'<?=base_admin();?>modul/quotation_follow_up/quotation_follow_up_data.php',type:'post',data:function(d){$.extend(d,qfuFilters());},error:function(xhr){console.log(xhr);qfuError(<?=sd_js('sales_follow_up_load_failed', 'Quotation Follow Up data failed to load.');?>);}}});
  $('#btn_filter_qfu').on('click',function(){dt.draw();});$('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_qfu').on('click',function(){$('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_due_only').prop('checked',false);$('#filter_customer').val(null).trigger('change');$('#filter_quote_status,#filter_followup_status,#filter_sales_person').val('').trigger('change');dt.draw();});
  $('#btn_excel_qfu').on('click',function(){window.location='<?=base_admin();?>modul/quotation_follow_up/quotation_follow_up_action.php?act=excel&'+$.param(qfuFilters());});
  $(document).on('click','.btn-qfu-add',function(){
    $('#form_qfu')[0].reset();
    $('#qfu_quotation_id').val($(this).data('id'));
    $('#qfu_quote_no').text($(this).data('no'));
    $('input[name="followup_date"]').val('<?=date('Y-m-d H:i');?>');
    $('input[name="next_followup_date"]').val('<?=date('Y-m-d H:i', strtotime('+3 days'));?>');
    $('input[name="sales_person"]').val('<?=qfu_h(qfu_username());?>');
    $('.qfu-select').trigger('change');
    $('#modal_qfu').modal('show');
  });
  $('#form_qfu').on('submit',function(e){e.preventDefault();$.post('<?=base_admin();?>modul/quotation_follow_up/quotation_follow_up_action.php?act=save',$(this).serialize(),function(r){if(r.status==='good'){$('#modal_qfu').modal('hide');dt.draw(false);}else{qfuError(r.error_message||'Gagal simpan follow-up.');}},'json').fail(function(xhr){console.log(xhr.responseText);qfuError('Gagal simpan follow-up.');});});
  $(document).on('click','.btn-qfu-history',function(){$.post('<?=base_admin();?>modul/quotation_follow_up/quotation_follow_up_action.php?act=history',{quotation_id:$(this).data('id')},function(html){$('#qfu_history_body').html(html);$('#modal_qfu_history').modal('show');}).fail(function(){qfuError('History gagal dibuka.');});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
