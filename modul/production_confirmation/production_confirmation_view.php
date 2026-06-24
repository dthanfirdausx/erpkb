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
$plants = $db->query("SELECT DISTINCT plant FROM production_order WHERE plant IS NOT NULL AND plant<>'' ORDER BY plant");
$operators = $db->query("SELECT DISTINCT operator_name FROM production_order_confirmation WHERE operator_name IS NOT NULL AND operator_name<>'' ORDER BY operator_name");
$shifts = $db->query("SELECT kode_shift, nama_shift FROM erp_shift WHERE status='Aktif' ORDER BY kode_shift");
$confirmableOrder = $db->fetch("SELECT COUNT(*) AS jml FROM production_order WHERE status IN ('RELEASED','IN_PROCESS')");
$kpi = $db->fetch(
  "SELECT COUNT(*) AS total_doc,
          COALESCE(SUM(CASE WHEN status='POSTED' THEN yield_qty ELSE 0 END),0) AS yield_qty,
          COALESCE(SUM(CASE WHEN status='POSTED' THEN scrap_qty ELSE 0 END),0) AS scrap_qty,
          COALESCE(SUM(CASE WHEN status='POSTED' THEN rework_qty ELSE 0 END),0) AS rework_qty
   FROM production_order_confirmation
   WHERE posting_date BETWEEN ? AND ?",
   array($defaultFrom, $defaultTo)
);
?>
<style>
  .pc-hero{background:linear-gradient(135deg,#6d28d9,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(109,40,217,.18)}
  .pc-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.pc-hero p{margin:0;opacity:.92}
  .pc-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .pc-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.pc-kpi strong{display:block;font-size:24px;margin-top:6px;color:#111827}
  .pc-kpi i{float:right;font-size:26px;color:#6d28d9;opacity:.55}.pc-filter .form-group{margin-bottom:12px}
  #dtb_production_confirmation td,#dtb_production_confirmation th{font-size:12px;vertical-align:middle}.pc-action-buttons{white-space:nowrap;min-width:78px}
  .select2-container{width:100%!important}.required-label:after{content:' *';color:#dd4b39}.pc-mini-kpi span{display:block;color:#64748b;font-size:11px;text-transform:uppercase}.pc-mini-kpi strong{font-size:18px}
  .pc-order-card{border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc;padding:14px;margin-bottom:14px}.pc-order-card h4{margin-top:0;font-weight:700}
  #modal_create_pc .modal-dialog{margin-top:18px;margin-bottom:18px}#modal_create_pc .modal-body{max-height:calc(100vh - 210px);overflow-y:auto;overflow-x:hidden}
  #modal_create_pc .modal-footer{position:sticky;bottom:0;background:#fff;z-index:2;border-top:1px solid #e5e7eb}.pc-detail-table th{width:170px;background:#f8fafc}
</style>
<section class="content-header">
  <h1><?=prod_h('production_confirmation', 'Production Confirmation');?> <small><?=prod_h('production_confirmation_subtitle', 'SAP PP Confirmation');?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=prod_h('common_home', 'Home');?></a></li>
    <li class="active"><?=prod_h('production_confirmation', 'Production Confirmation');?></li>
  </ol>
</section>
<section class="content">
  <div class="pc-hero">
    <div class="row">
      <div class="col-md-8">
        <h1><?=prod_h('production_confirmation_workbench', 'Production Confirmation Workbench');?></h1>
        <p><?=prod_h('production_confirmation_intro', 'Record actual production after Issue to Production: yield, scrap, rework, operation, operator, shift, and final confirmation.');?></p>
      </div>
      <div class="col-md-4 text-right">
        <?php if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") { ?>
          <button type="button" id="btn_open_create_pc" class="btn btn-warning"><i class="fa fa-check-square-o"></i> <?=prod_h('production_confirm_production', 'Confirm Production');?></button>
        <?php } ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="pc-kpi"><i class="fa fa-file-text-o"></i><span><?=prod_h('production_confirmation_short', 'Confirmation');?></span><strong><?=number_format((float)$kpi->total_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="pc-kpi"><i class="fa fa-check"></i><span><?=prod_h('production_yield_qty', 'Yield Qty');?></span><strong><?=number_format((float)$kpi->yield_qty,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="pc-kpi"><i class="fa fa-trash"></i><span><?=prod_h('production_scrap_qty', 'Scrap Qty');?></span><strong><?=number_format((float)$kpi->scrap_qty,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="pc-kpi"><i class="fa fa-repeat"></i><span><?=prod_h('production_rework_qty', 'Rework Qty');?></span><strong><?=number_format((float)$kpi->rework_qty,2,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=prod_h('production_filter_result', 'Filter Production Result');?></h3></div>
    <div class="box-body">
      <form id="filter_pc" class="form-horizontal pc-filter" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=prod_h('production_posting_date', 'Posting Date');?></label>
          <div class="col-lg-2"><div class="input-group date pc-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date pc-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=prod_h('production_plant', 'Plant');?></label>
          <div class="col-lg-2"><select id="filter_plant" class="form-control"><option value=""><?=prod_h('common_all', 'All');?></option><?php foreach($plants as $p){ ?><option value="<?=htmlspecialchars($p->plant,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($p->plant,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <label class="control-label col-lg-1"><?=prod_h('common_status', 'Status');?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=prod_h('common_all', 'All');?></option><option value="POSTED">POSTED</option><option value="REVERSED">REVERSED</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=prod_h('production_operator', 'Operator');?></label>
          <div class="col-lg-3"><select id="filter_operator" class="form-control"><option value=""><?=prod_h('production_all_operator', 'All Operator');?></option><?php foreach($operators as $op){ ?><option value="<?=htmlspecialchars($op->operator_name,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($op->operator_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <label class="control-label col-lg-1"><?=prod_h('common_search', 'Search');?></label>
          <div class="col-lg-3"><input id="filter_keyword" class="form-control" placeholder="<?=prod_h('production_search_confirmation', 'Confirmation / Production Order / Material / Work Center');?>"></div>
          <div class="col-lg-3">
            <button type="button" id="btn_filter_pc" class="btn btn-primary"><i class="fa fa-filter"></i> <?=prod_h('common_filter', 'Filter');?></button>
            <button type="button" id="btn_reset_pc" class="btn btn-default"><i class="fa fa-refresh"></i> <?=prod_h('common_reset', 'Reset');?></button>
            <button type="button" id="btn_export_pc" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=prod_h('common_excel', 'Excel');?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_production_confirmation" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead>
            <tr>
              <th><?=prod_h('common_no', 'No');?></th>
              <th><?=prod_h('common_action', 'Action');?></th>
              <th><?=prod_h('production_confirmation_short', 'Confirmation');?></th>
              <th><?=prod_h('production_order', 'Production Order');?></th>
              <th><?=prod_h('production_material', 'Material');?></th>
              <th><?=prod_h('production_operation', 'Operation');?></th>
              <th><?=prod_h('production_result_qty', 'Qty Result');?></th>
              <th><?=prod_h('production_operator', 'Operator');?></th>
              <th><?=prod_h('production_final', 'Final');?></th>
              <th><?=prod_h('common_status', 'Status');?></th>
              <th><?=prod_h('common_created_by', 'Created By');?></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_create_pc" class="modal fade">
    <div class="modal-dialog modal-lg" style="width:94%">
      <div class="modal-content">
        <form id="form_create_pc">
          <div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><?=prod_h('production_create_confirmation', 'Create Production Confirmation');?></h4></div>
          <div class="modal-body">
            <div class="alert alert-info"><?=prod_h('production_confirmation_info', 'Production Confirmation records actual production output. After final confirmation, the order changes to CONFIRMED and is ready for GR from Production Order.');?></div>
            <?php if (!$confirmableOrder || (int)$confirmableOrder->jml === 0) { ?>
              <div class="alert alert-warning"><i class="fa fa-warning"></i> <?=prod_h('production_no_confirmable_order', 'No Production Order with status RELEASED or IN_PROCESS is available. Release a Production Order first before creating confirmation.');?></div>
            <?php } ?>
            <div class="row">
              <div class="col-md-5 form-group"><label class="required-label"><?=prod_h('production_order', 'Production Order');?></label><select id="id_production_order" name="id_production_order" class="form-control" required></select></div>
              <div class="col-md-2 form-group"><label class="required-label"><?=prod_h('production_document_date', 'Document Date');?></label><input name="document_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
              <div class="col-md-2 form-group"><label class="required-label"><?=prod_h('production_posting_date', 'Posting Date');?></label><input name="posting_date" class="form-control date-field" value="<?=date('Y-m-d');?>" required></div>
              <div class="col-md-3 form-group"><label class="required-label"><?=prod_h('production_operation', 'Operation');?></label><select id="operation_no" name="operation_no" class="form-control" required></select></div>
            </div>
            <div id="order_info"><div class="text-muted"><?=prod_h('production_select_order_hint', 'Select Production Order to view remaining qty and material issue.');?></div></div>
            <div class="row">
              <div class="col-md-3 form-group"><label class="required-label"><?=prod_h('production_yield_qty', 'Yield Qty');?></label><input type="number" step="0.00001" min="0" id="yield_qty" name="yield_qty" class="form-control text-right" value="0" required></div>
              <div class="col-md-3 form-group"><label><?=prod_h('production_scrap_qty', 'Scrap Qty');?></label><input type="number" step="0.00001" min="0" id="scrap_qty" name="scrap_qty" class="form-control text-right" value="0"></div>
              <div class="col-md-3 form-group"><label><?=prod_h('production_rework_qty', 'Rework Qty');?></label><input type="number" step="0.00001" min="0" name="rework_qty" class="form-control text-right" value="0"></div>
              <div class="col-md-3 form-group"><label><?=prod_h('production_final_confirmation', 'Final Confirmation');?></label><select name="final_confirmation" class="form-control"><option value="N"><?=prod_h('production_no_partial', 'No - Partial');?></option><option value="Y"><?=prod_h('production_yes_final', 'Yes - Final');?></option></select></div>
            </div>
            <div class="row">
              <div class="col-md-3 form-group"><label><?=prod_h('production_scrap_handling', 'Scrap Handling');?></label><select name="scrap_handling" id="scrap_handling" class="form-control"><option value="LOSS"><?=prod_h('production_scrap_as_loss', 'Scrap as Loss');?></option><option value="STOCK"><?=prod_h('production_scrap_to_material', 'Scrap to Scrap Material');?></option></select></div>
              <div class="col-md-5 form-group pc-scrap-stock-field" style="display:none"><label><?=prod_h('production_scrap_material', 'Scrap Material');?></label><select name="scrap_material_code" id="scrap_material_code" class="form-control"></select><small class="text-muted"><?=prod_h('production_scrap_material_hint', 'If scrap still has value or will be stored, select the scrap material code.');?></small></div>
              <div class="col-md-4 form-group pc-scrap-stock-field" style="display:none"><label><?=prod_h('production_scrap_receipt_location', 'Scrap Receipt Location');?></label><div class="form-control-static"><?=prod_h('production_scrap_receipt_hint', 'Default to Storage Location type SCRAP, for example SCR1 / DEFAULT.');?></div></div>
            </div>
            <div class="row">
              <div class="col-md-3 form-group"><label class="required-label"><?=prod_h('production_operator', 'Operator');?></label><input name="operator_name" class="form-control" value="<?=htmlspecialchars(isset($_SESSION['username'])?$_SESSION['username']:'',ENT_QUOTES,'UTF-8');?>" required></div>
              <div class="col-md-3 form-group"><label><?=prod_h('production_shift', 'Shift');?></label><select name="shift_code" class="form-control"><option value=""><?=prod_h('production_select_shift', 'Select Shift');?></option><?php foreach($shifts as $shift){ ?><option value="<?=htmlspecialchars($shift->kode_shift,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($shift->kode_shift.' - '.$shift->nama_shift,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
              <div class="col-md-3 form-group"><label><?=prod_h('production_start_time', 'Start Time');?></label><input type="datetime-local" name="start_time" class="form-control datetime-field"></div>
              <div class="col-md-3 form-group"><label><?=prod_h('production_end_time', 'End Time');?></label><input type="datetime-local" name="end_time" class="form-control datetime-field"></div>
            </div>
            <div class="row">
              <div class="col-md-3 form-group"><label><?=prod_h('production_labor_time_hour', 'Labor Time (hour)');?></label><input type="number" step="0.01" min="0" name="labor_time" class="form-control text-right" value="0"></div>
              <div class="col-md-3 form-group"><label><?=prod_h('production_machine_time_hour', 'Machine Time (hour)');?></label><input type="number" step="0.01" min="0" name="machine_time" class="form-control text-right" value="0"></div>
              <div class="col-md-6 form-group"><label><?=prod_h('common_remarks', 'Remarks');?></label><input name="remarks" class="form-control" placeholder="<?=prod_h('production_confirmation_remark_placeholder', 'Production result, downtime, batch, or issue notes');?>"></div>
            </div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=prod_h('common_cancel', 'Cancel');?></button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?=prod_h('production_post_confirmation', 'Post Confirmation');?></button></div>
        </form>
      </div>
    </div>
  </div>

  <div id="modal_detail_pc" class="modal fade">
    <div class="modal-dialog modal-lg" style="width:94%">
      <div class="modal-content">
        <div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><?=prod_h('production_confirmation_detail', 'Production Confirmation Detail');?></h4></div>
        <div class="modal-body" id="isi_detail_pc"></div>
        <div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=prod_h('common_close', 'Close');?></button></div>
      </div>
    </div>
  </div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var pcLang = {
  selectOrderHint: <?=prod_js('production_select_order_hint', 'Select Production Order to view remaining qty and material issue.');?>,
  loadingOrder: <?=prod_js('production_loading_order', 'Loading order...');?>,
  posting: <?=prod_js('production_posting_progress', 'Posting...');?>,
  postConfirmation: <?=prod_js('production_post_confirmation', 'Post Confirmation');?>,
  detailOpenFailed: <?=prod_js('production_confirmation_detail_failed', 'Production Confirmation detail failed to open.');?>,
  reverseTitle: <?=prod_js('production_reverse_confirmation', 'Reverse Confirmation?');?>,
  reverseReason: <?=prod_js('production_reverse_reason', 'Reversal reason');?>,
  reverse: <?=prod_js('production_reverse', 'Reverse');?>,
  reasonRequired: <?=prod_js('production_reason_required', 'Reason is required');?>
};
function showPcError(m){$('.isi_warning_delete').text(m||<?=prod_js('production_confirmation_process_failed', 'Production Confirmation failed to process.');?>);$('.error_data_delete').fadeIn();}
function pcFilterParams(){
  return {
    tgl_awal:$('#filter_tgl_awal').val(),
    tgl_akhir:$('#filter_tgl_akhir').val(),
    plant:$('#filter_plant').val(),
    status:$('#filter_status').val(),
    operator_name:$('#filter_operator').val(),
    keyword:$('#filter_keyword').val()
  };
}
function loadOrderInfo(id){
  if(!id){$('#order_info').html('<div class="text-muted">'+pcLang.selectOrderHint+'</div>');return;}
  $('#order_info').html('<div class="text-muted"><i class="fa fa-spinner fa-spin"></i> '+pcLang.loadingOrder+'</div>');
  $.post('<?=base_admin();?>modul/production_confirmation/production_confirmation_action.php?act=order_info',{id:id},function(html){$('#order_info').html(html);});
  $('#operation_no').empty().trigger('change.select2');
}
function openCreatePcModal(){
  var form = $('#form_create_pc')[0];
  if(form) form.reset();
  $('#id_production_order,#operation_no,#scrap_material_code').empty().trigger('change.select2');
  $('#scrap_handling').val('LOSS').trigger('change.select2');
  toggleScrapStockFields(false);
  $('#order_info').html('<div class="text-muted">'+pcLang.selectOrderHint+'</div>');
  $('#modal_create_pc').modal({backdrop:'static',keyboard:false});
}
function toggleScrapStockFields(isStock){
  $('.pc-scrap-stock-field').toggle(!!isStock);
  if(!isStock) $('#scrap_material_code').val(null).trigger('change.select2');
}
$(function(){
  $(document).off('click.pcCreate','#btn_open_create_pc').on('click.pcCreate','#btn_open_create_pc',function(e){e.preventDefault();openCreatePcModal();});
  if($.fn.datepicker){$('.pc-date,.date-field').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){
    $('#filter_plant,#filter_status,#filter_operator,select[name=shift_code],select[name=final_confirmation],#scrap_handling').select2({width:'100%'});
    $('#id_production_order').select2({width:'100%',dropdownParent:$('#modal_create_pc'),placeholder:<?=prod_js('production_search_production_order', 'Search Production Order...');?>,minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/production_confirmation/production_confirmation_action.php?act=order_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
    $('#operation_no').select2({width:'100%',dropdownParent:$('#modal_create_pc'),placeholder:<?=prod_js('production_select_operation', 'Select Operation');?>});
    $('#scrap_material_code').select2({width:'100%',dropdownParent:$('#modal_create_pc'),placeholder:<?=prod_js('production_search_scrap_material', 'Search scrap material...');?>,allowClear:true,minimumInputLength:1,ajax:{url:'<?=base_admin();?>modul/production_confirmation/production_confirmation_action.php?act=scrap_material_search',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}});
  }
  var dt=$('#dtb_production_confirmation').DataTable({
    bProcessing:true,bServerSide:true,pageLength:25,
    dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons:[{extend:'collection',text:<?=prod_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],
    columnDefs:[{targets:[0,1],orderable:false,searchable:false},{width:'42px',targets:0},{width:'82px',targets:1}],
    ajax:{url:'<?=base_admin();?>modul/production_confirmation/production_confirmation_data.php',type:'post',data:function(d){$.extend(d,pcFilterParams());},error:function(xhr){console.log(xhr);showPcError(<?=prod_js('production_confirmation_load_failed', 'Production Confirmation data failed to load.');?>);}}
  });
  $('#scrap_handling').on('change',function(){
    toggleScrapStockFields($(this).val()==='STOCK');
  });
  $('#id_production_order').on('select2:select',function(e){
    var d=e.params.data;
    loadOrderInfo(d.id);
    $('#yield_qty').val(d.remaining_qty||0);
    $.post('<?=base_admin();?>modul/production_confirmation/production_confirmation_action.php?act=operations',{id:d.id},function(res){
      $('#operation_no').empty();
      if(res.results && res.results.length){
        $.each(res.results,function(_,op){var opt=new Option(op.text,op.id,false,false);$(opt).data('work_center',op.work_center);$('#operation_no').append(opt);});
      }
      $('#operation_no').trigger('change.select2');
    },'json');
  });
  $('#form_create_pc').on('submit',function(e){
    e.preventDefault();
    var btn=$(this).find('button[type=submit]');
    btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> '+pcLang.posting);
    $.ajax({url:'<?=base_admin();?>modul/production_confirmation/production_confirmation_action.php?act=confirm',type:'POST',data:$(this).serialize(),dataType:'json',success:function(res){
      if(res.status==='good'){$('#modal_create_pc').modal('hide');dt.draw(false);}
      else showPcError(res.error_message);
      btn.prop('disabled',false).html('<i class="fa fa-save"></i> '+pcLang.postConfirmation);
    },error:function(xhr){showPcError(xhr.responseText);btn.prop('disabled',false).html('<i class="fa fa-save"></i> '+pcLang.postConfirmation);}});
  });
  $('#btn_filter_pc').on('click',function(){dt.draw();});
  $('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_pc').on('click',function(){
    $('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');
    $('#filter_plant,#filter_status,#filter_operator').val('').trigger('change');
    dt.draw();
  });
  $('#btn_export_pc').on('click',function(){
    var q=$.param(pcFilterParams());
    window.location='<?=base_admin();?>modul/production_confirmation/production_confirmation_action.php?act=excel&'+q;
  });
  $(document).on('click','.btn-detail-pc',function(){
    $.post('<?=base_admin();?>modul/production_confirmation/production_confirmation_action.php?act=detail',{id:$(this).data('id')},function(html){$('#isi_detail_pc').html(html);$('#modal_detail_pc').modal('show');}).fail(function(){showPcError(pcLang.detailOpenFailed);});
  });
  $(document).on('click','.btn-reverse-pc',function(){
    var id=$(this).data('id'),no=$(this).data('no');
    Swal.fire({title:pcLang.reverseTitle,input:'text',inputLabel:pcLang.reverseReason+' '+no,showCancelButton:true,confirmButtonText:pcLang.reverse,inputValidator:function(v){return !v?pcLang.reasonRequired:undefined;}}).then(function(r){
      if(!r.isConfirmed)return;
      $.post('<?=base_admin();?>modul/production_confirmation/production_confirmation_action.php?act=reverse',{id:id,reason:r.value},function(res){if(res.status==='good'){dt.draw(false);}else showPcError(res.error_message);},'json').fail(function(xhr){showPcError(xhr.responseText);});
    });
  });
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
