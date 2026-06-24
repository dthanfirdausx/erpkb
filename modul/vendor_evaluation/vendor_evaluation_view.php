<?php
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$vendors = $db->query("SELECT kode_pemasok,nama FROM pemasok ORDER BY nama");
$orgs = $db->query("SELECT org_code,org_name FROM erp_purchasing_organization WHERE status='Aktif' ORDER BY org_code");
$plants = $db->query("SELECT plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$kpi = $db->fetch("SELECT COUNT(*) total_eval,
                          SUM(status='DRAFT') draft_count,
                          SUM(status='FINALIZED') finalized_count,
                          AVG(total_score) avg_score
                   FROM erp_vendor_evaluation");
if (!function_exists('ve_view_t')) {
  function ve_view_t($key, $fallback = '')
  {
    return lang_text($key, $fallback);
  }
}
if (!function_exists('ve_view_h')) {
  function ve_view_h($value)
  {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}
$veViewLang = array(
  'processFailed' => ve_view_t('vendor_evaluation_process_failed', 'Vendor Evaluation process failed.'),
  'loadFailed' => ve_view_t('vendor_evaluation_load_failed', 'Vendor evaluation data failed to load.'),
  'detailFailed' => ve_view_t('vendor_evaluation_detail_failed', 'Evaluation detail failed to open.'),
  'exportData' => ve_view_t('common_export_data', 'Export Data'),
  'generating' => ve_view_t('vendor_evaluation_generating', 'Generating...'),
  'generateDraft' => ve_view_t('vendor_evaluation_generate_draft', 'Generate Draft'),
  'finalizeConfirm' => ve_view_t('vendor_evaluation_finalize_confirm', 'Finalize Vendor Evaluation {no}?'),
  'cancelConfirm' => ve_view_t('vendor_evaluation_cancel_confirm', 'Cancel Vendor Evaluation {no}?')
);
?>
<style>
  .ve-hero{background:linear-gradient(135deg,#1d4ed8,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(29,78,216,.18)}
  .ve-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.ve-hero p{margin:0;opacity:.9}
  .ve-kpi{border-radius:12px;background:#fff;border:1px solid #edf2f7;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .ve-kpi span{color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.ve-kpi strong{display:block;font-size:25px;margin-top:6px;color:#111827}.ve-kpi i{float:right;font-size:26px;color:#3c8dbc;opacity:.55}
  #dtb_vendor_evaluation td,#dtb_vendor_evaluation th{font-size:12px;vertical-align:middle}.ve-action-buttons{white-space:nowrap;min-width:128px}.ve-action-buttons .btn{margin-right:3px}
  .ve-score-ring{width:82px;height:82px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#eef6ff;color:#1d4ed8;font-size:22px;font-weight:700;border:6px solid #3c8dbc}
  .ve-score-table th{background:#f5f5f5}.select2-container{width:100%!important}
  .ve-rating-A{background:#00a65a}.ve-rating-B{background:#3c8dbc}.ve-rating-C{background:#f39c12}.ve-rating-D{background:#dd4b39}
</style>
<section class="content-header">
  <h1><?=ve_view_h(ve_view_t('vendor_evaluation_title', 'Vendor Evaluation'));?> <small><?=ve_view_h(ve_view_t('vendor_evaluation_subtitle', 'SAP MM Supplier Scorecard'));?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=ve_view_h(ve_view_t('common_home', 'Home'));?></a></li>
    <li class="active"><?=ve_view_h(ve_view_t('vendor_evaluation_title', 'Vendor Evaluation'));?></li>
  </ol>
</section>
<section class="content">
  <div class="ve-hero">
    <div class="row">
      <div class="col-md-8">
        <h1><?=ve_view_h(ve_view_t('vendor_evaluation_workbench', 'Vendor Evaluation Workbench'));?></h1>
        <p><?=ve_view_h(ve_view_t('vendor_evaluation_intro', 'Evaluate vendor performance from PO, GR, RFQ, quality, service, and compliance.'));?></p>
      </div>
      <div class="col-md-4 text-right">
        <?php if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") { ?>
          <button type="button" class="btn btn-warning" id="btn_open_generate"><i class="fa fa-magic"></i> <?=ve_view_h(ve_view_t('vendor_evaluation_generate', 'Generate Evaluation'));?></button>
        <?php } ?>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-3"><div class="ve-kpi"><i class="fa fa-list"></i><span><?=ve_view_h(ve_view_t('vendor_evaluation_total', 'Total Evaluation'));?></span><strong><?=intval($kpi->total_eval);?></strong></div></div>
    <div class="col-sm-3"><div class="ve-kpi"><i class="fa fa-pencil"></i><span><?=ve_view_h(ve_view_t('vendor_evaluation_draft', 'Draft'));?></span><strong><?=intval($kpi->draft_count);?></strong></div></div>
    <div class="col-sm-3"><div class="ve-kpi"><i class="fa fa-lock"></i><span><?=ve_view_h(ve_view_t('vendor_evaluation_finalized', 'Finalized'));?></span><strong><?=intval($kpi->finalized_count);?></strong></div></div>
    <div class="col-sm-3"><div class="ve-kpi"><i class="fa fa-star"></i><span><?=ve_view_h(ve_view_t('vendor_evaluation_average_score', 'Average Score'));?></span><strong><?=number_format((float)$kpi->avg_score,2,',','.');?></strong></div></div>
  </div>
  <div class="box">
    <div class="box-body">
      <form id="filter_vendor_eval" class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=ve_view_h(ve_view_t('vendor_evaluation_period', 'Period'));?></label>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_period_from" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date filter-date"><input id="filter_period_to" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=ve_view_h(ve_view_t('vendor_evaluation_vendor', 'Vendor'));?></label>
          <div class="col-lg-3"><select id="filter_vendor" class="form-control"><option value=""><?=ve_view_h(ve_view_t('vendor_evaluation_all_vendor', 'All Vendor'));?></option><?php foreach($vendors as $v){ ?><option value="<?=ve_view_h($v->kode_pemasok);?>"><?=ve_view_h($v->kode_pemasok.' - '.$v->nama);?></option><?php } ?></select></div>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=ve_view_h(ve_view_t('vendor_evaluation_all_status', 'All Status'));?></option><option>DRAFT</option><option>FINALIZED</option><option>CANCELLED</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=ve_view_h(ve_view_t('vendor_evaluation_rating', 'Rating'));?></label>
          <div class="col-lg-2"><select id="filter_rating" class="form-control"><option value=""><?=ve_view_h(ve_view_t('vendor_evaluation_all_rating', 'All Rating'));?></option><option>A</option><option>B</option><option>C</option><option>D</option></select></div>
          <label class="control-label col-lg-1"><?=ve_view_h(ve_view_t('common_search', 'Search'));?></label>
          <div class="col-lg-4"><input id="filter_keyword" class="form-control" placeholder="<?=ve_view_h(ve_view_t('vendor_evaluation_search_placeholder', 'Search evaluation no / vendor / evaluator'));?>"></div>
          <div class="col-lg-3"><button type="button" id="btn_filter_ve" class="btn btn-primary"><i class="fa fa-filter"></i> <?=ve_view_h(ve_view_t('common_filter', 'Filter'));?></button> <button type="button" id="btn_reset_ve" class="btn btn-default"><i class="fa fa-refresh"></i> <?=ve_view_h(ve_view_t('common_reset', 'Reset'));?></button></div>
        </div>
      </form>
      <hr>
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_vendor_evaluation" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead><tr><th><?=ve_view_h(ve_view_t('table_no', 'No'));?></th><th><?=ve_view_h(ve_view_t('common_action', 'Action'));?></th><th><?=ve_view_h(ve_view_t('vendor_evaluation_evaluation', 'Evaluation'));?></th><th><?=ve_view_h(ve_view_t('vendor_evaluation_period', 'Period'));?></th><th><?=ve_view_h(ve_view_t('vendor_evaluation_vendor', 'Vendor'));?></th><th><?=ve_view_h(ve_view_t('vendor_evaluation_po', 'PO'));?></th><th><?=ve_view_h(ve_view_t('vendor_evaluation_gr', 'GR'));?></th><th><?=ve_view_h(ve_view_t('vendor_evaluation_delivery', 'Delivery'));?></th><th><?=ve_view_h(ve_view_t('vendor_evaluation_quality', 'Quality'));?></th><th><?=ve_view_h(ve_view_t('vendor_evaluation_total_score', 'Total'));?></th><th><?=ve_view_h(ve_view_t('vendor_evaluation_rating', 'Rating'));?></th><th><?=ve_view_h(ve_view_t('common_status', 'Status'));?></th><th><?=ve_view_h(ve_view_t('vendor_evaluation_evaluator', 'Evaluator'));?></th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_generate_ve" class="modal fade"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form id="form_generate_ve">
      <div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><?=ve_view_h(ve_view_t('vendor_evaluation_generate_title', 'Generate Vendor Evaluation'));?></h4></div>
      <div class="modal-body">
        <div class="alert alert-info"><?=ve_view_h(ve_view_t('vendor_evaluation_generate_help', 'Automatic score is calculated from PO, GR, RFQ award, and blocked receipt.'));?></div>
        <div class="row">
          <div class="col-md-6 form-group"><label class="required-label"><?=ve_view_h(ve_view_t('vendor_evaluation_vendor', 'Vendor'));?></label><select name="vendor_code" id="gen_vendor" class="form-control" required><option value=""><?=ve_view_h(ve_view_t('common_select_vendor', 'Select Vendor'));?></option><?php foreach($db->query("SELECT kode_pemasok,nama FROM pemasok ORDER BY nama") as $v){ ?><option value="<?=ve_view_h($v->kode_pemasok);?>"><?=ve_view_h($v->kode_pemasok.' - '.$v->nama);?></option><?php } ?></select></div>
          <div class="col-md-3 form-group"><label class="required-label"><?=ve_view_h(ve_view_t('vendor_evaluation_period_from', 'Period From'));?></label><input name="period_from" class="form-control date-field" value="<?=$defaultFrom;?>" required></div>
          <div class="col-md-3 form-group"><label class="required-label"><?=ve_view_h(ve_view_t('vendor_evaluation_period_to', 'Period To'));?></label><input name="period_to" class="form-control date-field" value="<?=$defaultTo;?>" required></div>
        </div>
        <div class="row">
          <div class="col-md-4 form-group"><label><?=ve_view_h(ve_view_t('vendor_evaluation_purchasing_org', 'Purchasing Org'));?></label><select name="purchasing_org" class="form-control select2-generate"><option value=""><?=ve_view_h(ve_view_t('common_all', 'All'));?></option><?php foreach($orgs as $o){ ?><option value="<?=ve_view_h($o->org_code);?>"><?=ve_view_h($o->org_code.' - '.$o->org_name);?></option><?php } ?></select></div>
          <div class="col-md-4 form-group"><label><?=ve_view_h(ve_view_t('form_plant', 'Plant'));?></label><select name="plant" class="form-control select2-generate"><option value=""><?=ve_view_h(ve_view_t('common_all', 'All'));?></option><?php foreach($plants as $p){ ?><option value="<?=ve_view_h($p->plant_code);?>"><?=ve_view_h($p->plant_code.' - '.$p->plant_name);?></option><?php } ?></select></div>
          <div class="col-md-2 form-group"><label><?=ve_view_h(ve_view_t('vendor_evaluation_service_score', 'Service Score'));?></label><input type="number" min="0" max="100" step="0.01" name="service_score" class="form-control" value="80"></div>
          <div class="col-md-2 form-group"><label><?=ve_view_h(ve_view_t('vendor_evaluation_compliance_score', 'Compliance Score'));?></label><input type="number" min="0" max="100" step="0.01" name="compliance_score" class="form-control" value="80"></div>
        </div>
        <div class="form-group"><label><?=ve_view_h(ve_view_t('vendor_evaluation_notes', 'Remarks'));?></label><textarea name="remarks" class="form-control" rows="3" placeholder="<?=ve_view_h(ve_view_t('vendor_evaluation_remarks_placeholder', 'Evaluation notes, audit, certificate, complaint, or vendor action plan'));?>"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=ve_view_h(ve_view_t('common_cancel', 'Cancel'));?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?=ve_view_h(ve_view_t('vendor_evaluation_generate_draft', 'Generate Draft'));?></button></div>
    </form>
  </div></div></div>

  <div id="modal_detail_ve" class="modal fade"><div class="modal-dialog modal-lg" style="width:94%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><?=ve_view_h(ve_view_t('vendor_evaluation_detail_title', 'Vendor Evaluation Detail'));?></h4></div><div class="modal-body" id="isi_detail_ve"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=ve_view_h(ve_view_t('common_close', 'Close'));?></button></div></div></div></div>
  <div id="modal_score_ve" class="modal fade"><div class="modal-dialog"><div class="modal-content"><form id="form_score_ve"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><?=ve_view_h(ve_view_t('vendor_evaluation_update_scorecard', 'Update Manual Scorecard'));?></h4></div><div class="modal-body" id="isi_score_ve"></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=ve_view_h(ve_view_t('common_cancel', 'Cancel'));?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?=ve_view_h(ve_view_t('vendor_evaluation_save_score', 'Save Score'));?></button></div></form></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
var veLang = <?=json_encode($veViewLang);?>;
function showVeError(m){$('.isi_warning_delete').text(m||veLang.processFailed);$('.error_data_delete').fadeIn();}
function parseVeResponse(res){if(typeof res==='string'){try{return JSON.parse(res);}catch(e){return [{status:'error',error_message:res}];}}return res;}
$(function(){
  if($.fn.datepicker){$('.filter-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_vendor,#filter_status,#filter_rating').select2({width:'100%'});$('#gen_vendor,.select2-generate').select2({width:'100%',dropdownParent:$('#modal_generate_ve')});}
  var dt=$('#dtb_vendor_evaluation').DataTable({
    bProcessing:true,bServerSide:true,
    dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons:[{extend:'collection',text:veLang.exportData,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],
    columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[5,6,7,8,9],className:'text-right'},{width:'45px',targets:0},{width:'132px',targets:1}],
    ajax:{url:'<?=base_admin();?>modul/vendor_evaluation/vendor_evaluation_data.php',type:'post',data:function(d){d.period_from=$('#filter_period_from').val();d.period_to=$('#filter_period_to').val();d.vendor=$('#filter_vendor').val();d.status=$('#filter_status').val();d.rating=$('#filter_rating').val();d.keyword=$('#filter_keyword').val();},error:function(xhr){console.log(xhr);showVeError(veLang.loadFailed);}}
  });
  $('#btn_open_generate').on('click',function(){$('#modal_generate_ve').modal({backdrop:'static',keyboard:false});});
  $('#btn_filter_ve').on('click',function(){dt.draw();});
  $('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_ve').on('click',function(){$('#filter_period_from').val('<?=$defaultFrom;?>');$('#filter_period_to').val('<?=$defaultTo;?>');$('#filter_keyword').val('');$('#filter_vendor,#filter_status,#filter_rating').val('').trigger('change');dt.draw();});
  $('#form_generate_ve').on('submit',function(e){e.preventDefault();var btn=$(this).find('button[type=submit]');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> '+veLang.generating);$.ajax({url:'<?=base_admin();?>modul/vendor_evaluation/vendor_evaluation_action.php?act=generate',type:'POST',data:$(this).serialize(),dataType:'json',success:function(res){var r=$.isArray(res)?res[0]:res;if(r.status==='good'){$('#modal_generate_ve').modal('hide');dt.draw(false);}else showVeError(r.error_message);btn.prop('disabled',false).html('<i class="fa fa-save"></i> '+veLang.generateDraft);},error:function(xhr){showVeError(xhr.responseText);btn.prop('disabled',false).html('<i class="fa fa-save"></i> '+veLang.generateDraft);}});});
  $(document).on('click','.btn-detail-ve',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/vendor_evaluation/vendor_evaluation_action.php?act=detail',{id:id},function(html){$('#isi_detail_ve').html(html);$('#modal_detail_ve').modal('show');}).fail(function(){showVeError(veLang.detailFailed);});});
  $(document).on('click','.btn-score-ve',function(){var id=$(this).data('id');$.post('<?=base_admin();?>modul/vendor_evaluation/vendor_evaluation_action.php?act=score_form',{id:id},function(html){$('#isi_score_ve').html(html);$('#modal_score_ve').modal('show');});});
  $('#form_score_ve').on('submit',function(e){e.preventDefault();$.ajax({url:'<?=base_admin();?>modul/vendor_evaluation/vendor_evaluation_action.php?act=save_score',type:'POST',data:$(this).serialize(),dataType:'json',success:function(res){var r=$.isArray(res)?res[0]:res;if(r.status==='good'){$('#modal_score_ve').modal('hide');dt.draw(false);}else showVeError(r.error_message);},error:function(xhr){showVeError(xhr.responseText);}});});
  $(document).on('click','.btn-finalize-ve',function(){var id=$(this).data('id'),no=$(this).data('no');if(!confirm(veLang.finalizeConfirm.replace('{no}',no)))return;$.post('<?=base_admin();?>modul/vendor_evaluation/vendor_evaluation_action.php?act=finalize',{id:id},function(res){var r=parseVeResponse(res);if(r[0].status==='good')dt.draw(false);else showVeError(r[0].error_message);});});
  $(document).on('click','.btn-cancel-ve',function(){var id=$(this).data('id'),no=$(this).data('no');if(!confirm(veLang.cancelConfirm.replace('{no}',no)))return;$.post('<?=base_admin();?>modul/vendor_evaluation/vendor_evaluation_action.php?act=cancel',{id:id},function(res){var r=parseVeResponse(res);if(r[0].status==='good')dt.draw(false);else showVeError(r[0].error_message);});});
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
