<style>
  .rfq-hero{background:linear-gradient(135deg,#1d4ed8 0%,#0f766e 100%);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(29,78,216,.18)}
  .rfq-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.rfq-hero p{margin:0;opacity:.9}
  .rfq-kpi{border-radius:12px;background:#fff;border:1px solid #edf2f7;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .rfq-kpi span{color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.rfq-kpi strong{display:block;font-size:25px;margin-top:6px;color:#111827}.rfq-kpi i{float:right;font-size:26px;color:#3c8dbc;opacity:.55}
  #dtb_rfq td,#dtb_rfq th{font-size:12px;vertical-align:middle}.rfq-action-buttons{white-space:nowrap;min-width:120px}.rfq-action-buttons .btn{margin-right:3px}
  .select2-container{width:100%!important}
</style>
<?php
if (!function_exists('rfq_t')) {
  function rfq_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('rfq_h')) {
  function rfq_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$rfqViewLang = array(
  'processFailed' => rfq_t('rfq_process_failed','Proses RFQ gagal.'),
  'loadFailed' => rfq_t('rfq_load_failed','Data RFQ gagal dimuat.'),
  'detailFailed' => rfq_t('rfq_detail_failed','Detail RFQ gagal dibuka.'),
  'sendConfirm' => rfq_t('rfq_send_confirm','Kirim RFQ {no} ke vendor?'),
  'cancelConfirm' => rfq_t('rfq_cancel_confirm','Cancel RFQ {no}?'),
  'awardConfirm' => rfq_t('rfq_award_confirm','Award quotation ini?'),
  'exportData' => rfq_t('common_export_data','Export Data'),
);
?>
<section class="content-header">
  <h1><?=rfq_h(rfq_t('rfq_title','Request for Quotation'));?> <small><?=rfq_h(rfq_t('rfq_subtitle','SAP MM RFQ'));?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=rfq_h(rfq_t('common_home','Home'));?></a></li>
    <li class="active"><?=rfq_h(rfq_t('rfq_title','Request for Quotation'));?></li>
  </ol>
</section>
<section class="content">
  <div class="rfq-hero">
    <div class="row">
      <div class="col-md-8">
        <h1><?=rfq_h(rfq_t('rfq_workbench','RFQ Workbench'));?></h1>
        <p><?=rfq_h(rfq_t('rfq_intro','Buat RFQ dari PR approved, invite vendor, capture quotation, compare landed price, lalu award vendor terbaik.'));?></p>
      </div>
      <div class="col-md-4 text-right">
        <a href="<?=base_index();?>rfq/tambah" class="btn btn-warning"><i class="fa fa-plus"></i> <?=rfq_h(rfq_t('rfq_create','Create RFQ'));?></a>
      </div>
    </div>
  </div>
  <div class="row">
    <?php
    $kpi = $db->fetch("SELECT SUM(status='DRAFT') draft_count,SUM(status='SENT') sent_count,SUM(status='QUOTED') quoted_count,SUM(status='AWARDED') awarded_count FROM erp_rfq");
    ?>
    <div class="col-sm-3"><div class="rfq-kpi"><i class="fa fa-pencil-square-o"></i><span><?=rfq_h(rfq_t('rfq_status_draft','Draft'));?></span><strong><?=intval($kpi->draft_count);?></strong></div></div>
    <div class="col-sm-3"><div class="rfq-kpi"><i class="fa fa-paper-plane"></i><span><?=rfq_h(rfq_t('rfq_status_sent','Sent'));?></span><strong><?=intval($kpi->sent_count);?></strong></div></div>
    <div class="col-sm-3"><div class="rfq-kpi"><i class="fa fa-money"></i><span><?=rfq_h(rfq_t('rfq_status_quoted','Quoted'));?></span><strong><?=intval($kpi->quoted_count);?></strong></div></div>
    <div class="col-sm-3"><div class="rfq-kpi"><i class="fa fa-trophy"></i><span><?=rfq_h(rfq_t('rfq_status_awarded','Awarded'));?></span><strong><?=intval($kpi->awarded_count);?></strong></div></div>
  </div>
  <div class="box">
    <div class="box-body">
      <form class="form-horizontal" id="filter_rfq" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=rfq_h(rfq_t('rfq_date','RFQ Date'));?></label>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_awal" class="form-control" placeholder="<?=rfq_h(rfq_t('purchase_order_start_date','awal'));?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_tgl_akhir" class="form-control" placeholder="<?=rfq_h(rfq_t('purchase_order_end_date','akhir'));?>"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=rfq_h(rfq_t('common_status','Status'));?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=rfq_h(rfq_t('common_all','Semua'));?></option><option>DRAFT</option><option>SENT</option><option>QUOTED</option><option>AWARDED</option><option>CLOSED</option><option>CANCELLED</option></select></div>
          <label class="control-label col-lg-1"><?=rfq_h(rfq_t('purchase_requisition_vendor','Vendor'));?></label>
          <div class="col-lg-2"><select id="filter_vendor" class="form-control"><option value=""><?=rfq_h(rfq_t('common_all','Semua'));?></option><?php foreach($db->query("SELECT kode_pemasok,nama FROM pemasok ORDER BY nama") as $v){ ?><option value="<?=htmlspecialchars($v->kode_pemasok,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($v->kode_pemasok.' - '.$v->nama,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=rfq_h(rfq_t('search','Search'));?></label>
          <div class="col-lg-5"><input id="filter_reference" class="form-control" placeholder="<?=rfq_h(rfq_t('rfq_filter_placeholder','Cari RFQ / subject / material / vendor'));?>"></div>
          <div class="col-lg-5"><button id="btn_filter_rfq" class="btn btn-primary"><i class="fa fa-filter"></i> <?=rfq_h(rfq_t('common_filter','Filter'));?></button> <button id="btn_reset_rfq" class="btn btn-default"><i class="fa fa-refresh"></i> <?=rfq_h(rfq_t('common_reset','Reset'));?></button></div>
        </div>
      </form>
      <hr>
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_rfq" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead><tr><th><?=rfq_h(rfq_t('common_no','No'));?></th><th><?=rfq_h(rfq_t('common_action','Action'));?></th><th><?=rfq_h(rfq_t('rfq_code','RFQ'));?></th><th><?=rfq_h(rfq_t('rfq_date','RFQ Date'));?></th><th><?=rfq_h(rfq_t('rfq_deadline_short','Deadline'));?></th><th><?=rfq_h(rfq_t('common_plant','Plant'));?></th><th><?=rfq_h(rfq_t('rfq_items','Items'));?></th><th><?=rfq_h(rfq_t('rfq_vendors','Vendors'));?></th><th><?=rfq_h(rfq_t('rfq_quotes','Quotes'));?></th><th><?=rfq_h(rfq_t('rfq_best_value','Best Value'));?></th><th><?=rfq_h(rfq_t('common_status','Status'));?></th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
  <div id="modal_detail_rfq" class="modal fade"><div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><?=rfq_h(rfq_t('rfq_detail_title','RFQ Detail'));?></h4></div><div class="modal-body" id="isi_detail_rfq"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=rfq_h(rfq_t('common_close','Close'));?></button></div></div></div></div>
  <div id="modal_quote_rfq" class="modal fade"><div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content"><form id="form_quote_rfq"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><?=rfq_h(rfq_t('rfq_input_quotation','Input Vendor Quotation'));?></h4></div><div class="modal-body" id="isi_quote_rfq"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=rfq_h(rfq_t('common_cancel','Cancel'));?></button><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> <?=rfq_h(rfq_t('rfq_save_quotation','Save Quotation'));?></button></div></form></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
var rfqViewLang=<?=json_encode($rfqViewLang, JSON_UNESCAPED_UNICODE);?>;
function rfqMsg(template,no){return String(template||'').replace('{no}',no||'');}
function showRfqError(m){$('.isi_warning_delete').text(m||rfqViewLang.processFailed);$('.error_data_delete').fadeIn();}
$(function(){
  if($.fn.datepicker){$('.filter-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_status,#filter_vendor').select2({width:'100%'});}
  var dt=$('#dtb_rfq').DataTable({bProcessing:true,bServerSide:true,dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",buttons:[{extend:'collection',text:rfqViewLang.exportData,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[6,7,8],className:'text-right'},{width:'45px',targets:0},{width:'130px',targets:1}],ajax:{url:'<?=base_admin();?>modul/rfq/rfq_data.php',type:'post',data:function(d){d.tgl_awal=$('#filter_tgl_awal').val();d.tgl_akhir=$('#filter_tgl_akhir').val();d.status=$('#filter_status').val();d.vendor=$('#filter_vendor').val();d.reference=$('#filter_reference').val();},error:function(xhr){console.log(xhr);showRfqError(rfqViewLang.loadFailed);}}});
  $('#btn_filter_rfq').on('click',function(){dt.draw();});$('#filter_reference').on('keyup',function(e){if(e.keyCode===13)dt.draw();});$('#btn_reset_rfq').on('click',function(){$('#filter_tgl_awal,#filter_tgl_akhir,#filter_reference').val('');$('#filter_status,#filter_vendor').val('').trigger('change');dt.draw();});
  $(document).on('click','.btn-detail-rfq',function(){var id=$(this).data('id');$('#loadnya').show();$.post('<?=base_admin();?>modul/rfq/rfq_action.php?act=detail',{id:id},function(html){$('#loadnya').hide();$('#isi_detail_rfq').html(html);$('#modal_detail_rfq').modal('show');}).fail(function(){ $('#loadnya').hide();showRfqError(rfqViewLang.detailFailed);});});
  $(document).on('click','.btn-send-rfq',function(){var id=$(this).data('id'),no=$(this).data('no');if(!confirm(rfqMsg(rfqViewLang.sendConfirm,no)))return;$.post('<?=base_admin();?>modul/rfq/rfq_action.php?act=send',{id:id},function(res){var r=typeof res==='string'?JSON.parse(res):res;if(r[0].status==='good'){dt.draw(false);}else showRfqError(r[0].error_message);});});
  $(document).on('click','.btn-cancel-rfq',function(){var id=$(this).data('id'),no=$(this).data('no');if(!confirm(rfqMsg(rfqViewLang.cancelConfirm,no)))return;$.post('<?=base_admin();?>modul/rfq/rfq_action.php?act=cancel',{id:id},function(res){var r=typeof res==='string'?JSON.parse(res):res;if(r[0].status==='good'){dt.draw(false);}else showRfqError(r[0].error_message);});});
  $(document).on('click','.btn-open-quote',function(){var id=$(this).data('id');$('#loadnya').show();$.post('<?=base_admin();?>modul/rfq/rfq_action.php?act=quote_form',{id:id},function(html){$('#loadnya').hide();$('#isi_quote_rfq').html(html);if($.fn.select2)$('#quote_vendor_id').select2({width:'100%',dropdownParent:$('#modal_quote_rfq')});if($.fn.datepicker)$('.date-field-quote').datepicker({format:'yyyy-mm-dd',autoclose:true});$('#modal_quote_rfq').modal('show');});});
  $('#form_quote_rfq').on('submit',function(e){e.preventDefault();$.ajax({url:'<?=base_admin();?>modul/rfq/rfq_action.php?act=save_quote',type:'POST',data:$(this).serialize(),dataType:'json',success:function(res){var r=$.isArray(res)?res[0]:res;if(r.status==='good'){$('#modal_quote_rfq').modal('hide');dt.draw(false);}else showRfqError(r.error_message);},error:function(xhr){showRfqError(xhr.responseText);}});});
  $(document).on('click','.btn-award-quote',function(){var id=$(this).data('id');if(!confirm(rfqViewLang.awardConfirm))return;$.post('<?=base_admin();?>modul/rfq/rfq_action.php?act=award',{quote_id:id},function(res){var r=typeof res==='string'?JSON.parse(res):res;if(r[0].status==='good'){$('#modal_detail_rfq').modal('hide');dt.draw(false);}else showRfqError(r[0].error_message);});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
