<?php
$currentUser = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$currentGroup = isset($_SESSION['group_level']) ? $_SESSION['group_level'] : '';
$approvers = $db->query("SELECT DISTINCT approver FROM purchase_requisition_approval ORDER BY approver");
if (!function_exists('approval_view_t')) {
  function approval_view_t($key, $fallback = '')
  {
    return lang_text($key, $fallback);
  }
}
if (!function_exists('approval_view_h')) {
  function approval_view_h($value)
  {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}
$approvalViewLang = array(
  'processFailed' => approval_view_t('approval_center_process_failed', 'Approval process failed.'),
  'loadFailed' => approval_view_t('approval_center_load_failed', 'Approval data failed to load.'),
  'detailFailed' => approval_view_t('approval_center_detail_failed', 'Approval detail failed to open.'),
  'rejectNoteRequired' => approval_view_t('approval_center_reject_note_required', 'Rejection note is required.'),
  'serverError' => approval_view_t('common_server_error', 'Server error.'),
  'exportData' => approval_view_t('common_export_data', 'Export Data'),
  'approve' => approval_view_t('approval_center_approve', 'Approve'),
  'reject' => approval_view_t('approval_center_reject', 'Reject'),
  'approvePr' => approval_view_t('approval_center_approve_pr', 'Approve Purchase Requisition'),
  'rejectPr' => approval_view_t('approval_center_reject_pr', 'Reject Purchase Requisition')
);
?>
<style>
  .approval-hero {
    background: linear-gradient(135deg, #1d4ed8 0%, #0f766e 100%);
    color: #fff;
    border-radius: 14px;
    padding: 22px 24px;
    margin-bottom: 18px;
    box-shadow: 0 10px 24px rgba(29,78,216,.18);
  }
  .approval-hero h1 { margin: 0 0 6px; font-size: 26px; font-weight: 700; }
  .approval-hero p { margin: 0; opacity: .9; }
  .approval-hero .approval-user-pill {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 999px;
    background: rgba(255,255,255,.16);
    margin-top: 8px;
  }
  .approval-kpi {
    border-radius: 12px;
    background: #fff;
    border: 1px solid #edf2f7;
    padding: 16px;
    margin-bottom: 14px;
    box-shadow: 0 4px 14px rgba(15,23,42,.05);
  }
  .approval-kpi span { color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
  .approval-kpi strong { display: block; font-size: 28px; margin-top: 6px; color: #111827; }
  .approval-kpi i { float: right; font-size: 28px; color: #3c8dbc; opacity: .55; }
  .approval-filter-box {
    border-radius: 12px;
    border-top: 0;
    box-shadow: 0 4px 14px rgba(15,23,42,.06);
  }
  .approval-actions { white-space: nowrap; min-width: 96px; }
  .approval-actions .btn { margin-right: 3px; }
  #dtb_approval_center td { vertical-align: middle; }
  #dtb_approval_center td, #dtb_approval_center th { font-size: 12px; }
  .approval-detail .approval-title { margin-top: 0; font-weight: 700; }
  .approval-summary {
    margin: 14px 0 20px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
  }
  .approval-summary > div {
    min-height: 82px;
    border-right: 1px solid #e5e7eb;
    padding: 12px 14px;
    background: #fbfdff;
  }
  .approval-summary > div:last-child { border-right: 0; }
  .approval-summary span {
    display: block;
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .04em;
  }
  .approval-summary strong { display: block; color: #111827; margin-top: 4px; }
  .approval-summary small { display: block; color: #64748b; margin-top: 3px; }
  .approval-items td, .approval-items th { font-size: 12px; }
  .approval-timeline {
    list-style: none;
    margin: 0;
    padding: 0;
    border-left: 2px solid #e5e7eb;
  }
  .approval-timeline li {
    position: relative;
    padding: 0 0 14px 18px;
  }
  .approval-timeline li:before {
    content: "";
    position: absolute;
    left: -6px;
    top: 3px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #94a3b8;
  }
  .approval-timeline li.approved:before { background: #00a65a; }
  .approval-timeline li.rejected:before { background: #dd4b39; }
  .approval-timeline li.pending:before { background: #f39c12; }
  .approval-timeline strong { display: block; }
  .approval-timeline span { display: block; color: #64748b; font-size: 12px; margin: 2px 0; }
  .approval-timeline p { margin: 0; color: #374151; }
</style>

<section class="content-header">
  <h1><?=approval_view_h(approval_view_t('approval_center_title', 'Approval Center'));?> <small><?=approval_view_h(approval_view_t('approval_center_subtitle', 'SAP Business Workflow'));?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=approval_view_h(approval_view_t('common_home', 'Home'));?></a></li>
    <li class="active"><?=approval_view_h(approval_view_t('approval_center_title', 'Approval Center'));?></li>
  </ol>
</section>

<section class="content">
  <div class="approval-hero">
    <div class="row">
      <div class="col-md-8">
        <h1><?=approval_view_h(approval_view_t('approval_center_worklist', 'Approval Worklist'));?></h1>
        <p><?=approval_view_h(approval_view_t('approval_center_intro', 'Decision center for Purchase Requisition release strategy.'));?></p>
      </div>
      <div class="col-md-4 text-right">
        <span class="approval-user-pill"><i class="fa fa-user"></i> <?=htmlspecialchars($currentUser.' / '.$currentGroup, ENT_QUOTES, 'UTF-8');?></span>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3">
      <div class="approval-kpi"><i class="fa fa-clock-o"></i><span><?=approval_view_h(approval_view_t('approval_center_pending', 'Pending'));?></span><strong id="kpi_pending">0</strong></div>
    </div>
    <div class="col-sm-3">
      <div class="approval-kpi"><i class="fa fa-check"></i><span><?=approval_view_h(approval_view_t('approval_center_approved_today', 'Approved Today'));?></span><strong id="kpi_approved_today">0</strong></div>
    </div>
    <div class="col-sm-3">
      <div class="approval-kpi"><i class="fa fa-times"></i><span><?=approval_view_h(approval_view_t('approval_center_rejected', 'Rejected'));?></span><strong id="kpi_rejected">0</strong></div>
    </div>
    <div class="col-sm-3">
      <div class="approval-kpi"><i class="fa fa-inbox"></i><span><?=approval_view_h(approval_view_t('approval_center_my_worklist', 'My Worklist'));?></span><strong id="kpi_total">0</strong></div>
    </div>
  </div>

  <div class="box approval-filter-box">
    <div class="box-body">
      <form id="filter_approval_center" class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=approval_view_h(approval_view_t('approval_center_pr_date', 'PR Date'));?></label>
          <div class="col-lg-2">
            <div class="input-group date filter-date">
              <input type="text" id="filter_tgl_awal" class="form-control" placeholder="<?=approval_view_h(approval_view_t('common_start_date', 'Start date'));?>" autocomplete="off">
              <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
            </div>
          </div>
          <div class="col-lg-2">
            <div class="input-group date filter-date">
              <input type="text" id="filter_tgl_akhir" class="form-control" placeholder="<?=approval_view_h(approval_view_t('common_end_date', 'End date'));?>" autocomplete="off">
              <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
            </div>
          </div>
          <label class="control-label col-lg-1"><?=approval_view_h(approval_view_t('common_status', 'Status'));?></label>
          <div class="col-lg-2">
            <select id="filter_status" class="form-control">
              <option value=""><?=approval_view_h(approval_view_t('approval_center_all_status', 'All Status'));?></option>
              <option value="PENDING" selected>PENDING</option>
              <option value="APPROVED">APPROVED</option>
              <option value="REJECTED">REJECTED</option>
            </select>
          </div>
          <label class="control-label col-lg-1"><?=approval_view_h(approval_view_t('approval_center_approver', 'Approver'));?></label>
          <div class="col-lg-2">
            <select id="filter_approver" class="form-control">
              <option value=""><?=approval_view_h(approval_view_t('approval_center_all_approver', 'All Approver'));?></option>
              <?php foreach ($approvers as $approver) { ?>
                <option value="<?=htmlspecialchars($approver->approver, ENT_QUOTES, 'UTF-8');?>"><?=htmlspecialchars($approver->approver, ENT_QUOTES, 'UTF-8');?></option>
              <?php } ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=approval_view_h(approval_view_t('common_search', 'Search'));?></label>
          <div class="col-lg-5">
            <input type="text" id="filter_reference" class="form-control" placeholder="<?=approval_view_h(approval_view_t('approval_center_search_placeholder', 'Search PR no / requestor / department / material'));?>">
          </div>
          <div class="col-lg-5">
            <button type="button" id="btn_filter_approval" class="btn btn-primary"><i class="fa fa-filter"></i> <?=approval_view_h(approval_view_t('common_filter', 'Filter'));?></button>
            <button type="button" id="btn_reset_approval" class="btn btn-default"><i class="fa fa-refresh"></i> <?=approval_view_h(approval_view_t('common_reset', 'Reset'));?></button>
          </div>
        </div>
      </form>

      <div class="alert alert-warning fade in error_data_delete" style="display:none">
        <button type="button" class="close hide_alert_notif">&times;</button>
        <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
      </div>

      <div class="table-responsive">
        <table id="dtb_approval_center" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead>
            <tr>
              <th><?=approval_view_h(approval_view_t('table_no', 'No'));?></th>
              <th><?=approval_view_h(approval_view_t('common_action', 'Action'));?></th>
              <th><?=approval_view_h(approval_view_t('approval_center_document', 'Document'));?></th>
              <th><?=approval_view_h(approval_view_t('approval_center_pr_date', 'PR Date'));?></th>
              <th><?=approval_view_h(approval_view_t('approval_center_requestor', 'Requestor'));?></th>
              <th><?=approval_view_h(approval_view_t('approval_center_priority', 'Priority'));?></th>
              <th><?=approval_view_h(approval_view_t('form_plant', 'Plant'));?></th>
              <th class="text-right"><?=approval_view_h(approval_view_t('approval_center_items', 'Items'));?></th>
              <th class="text-right"><?=approval_view_h(approval_view_t('approval_center_open_qty', 'Open Qty'));?></th>
              <th class="text-right"><?=approval_view_h(approval_view_t('approval_center_est_value', 'Est. Value'));?></th>
              <th><?=approval_view_h(approval_view_t('approval_center_approver', 'Approver'));?></th>
              <th><?=approval_view_h(approval_view_t('common_status', 'Status'));?></th>
              <th><?=approval_view_h(approval_view_t('approval_center_required_date', 'Required Date'));?></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_detail_approval" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg" style="width: 94%">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><?=approval_view_h(approval_view_t('approval_center_detail_title', 'Approval Detail'));?></h4>
        </div>
        <div class="modal-body" id="isi_detail_approval"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=approval_view_h(approval_view_t('common_close', 'Close'));?></button>
        </div>
      </div>
    </div>
  </div>

  <div id="modal_decision_approval" class="modal fade" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="form_decision_approval">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title" id="decision_title"><?=approval_view_h(approval_view_t('approval_center_decision_title', 'Approval Decision'));?></h4>
          </div>
          <div class="modal-body">
            <input type="hidden" id="decision_id">
            <input type="hidden" id="decision_act">
            <div class="alert alert-info" id="decision_doc"></div>
            <div class="form-group">
              <label><?=approval_view_h(approval_view_t('approval_center_decision_note', 'Decision Note'));?> <span id="decision_required" class="text-danger" style="display:none">*</span></label>
              <textarea id="decision_note" class="form-control" rows="4" placeholder="<?=approval_view_h(approval_view_t('approval_center_decision_placeholder', 'Write approval/rejection note'));?>"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?=approval_view_h(approval_view_t('common_cancel', 'Cancel'));?></button>
            <button type="submit" class="btn btn-primary" id="btn_submit_decision"><i class="fa fa-save"></i> <?=approval_view_h(approval_view_t('approval_center_submit_decision', 'Submit Decision'));?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
var approvalLang = <?=json_encode($approvalViewLang);?>;
function refreshApprovalSummary() {
  $.ajax({
    type: 'GET',
    dataType: 'json',
    url: '<?=base_admin();?>modul/approval_center/approval_center_action.php?act=summary',
    success: function(res) {
      if (res.status === 'good') {
        $('#kpi_pending').text(res.pending_count);
        $('#kpi_approved_today').text(res.approved_today);
        $('#kpi_rejected').text(res.rejected_count);
        $('#kpi_total').text(res.total_count);
      }
    }
  });
}

function showApprovalError(message) {
  $('.isi_warning_delete').text(message || approvalLang.processFailed);
  $('.error_data_delete').fadeIn();
}

$(function() {
  if ($.fn.datepicker) {
    $('.filter-date').datepicker({ autoclose:true, format:'yyyy-mm-dd', todayHighlight:true });
  }
  if ($.fn.select2) {
    $('#filter_status, #filter_approver').select2({ width:'100%' });
  }

  var dtbApproval = $('#dtb_approval_center').DataTable({
    bProcessing: true,
    bServerSide: true,
    dom: "<'row'<'col-sm-12'B>>" + "<'row'<'col-sm-6'l><'col-sm-6'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons: [{ extend:'collection', text: approvalLang.exportData, buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5'] }],
    columnDefs: [
      { targets:[0,1], orderable:false, searchable:false },
      { targets:[7,8,9], className:'text-right' },
      { width:'42px', targets:0, className:'dt-center' },
      { width:'105px', targets:1, className:'dt-center' }
    ],
    ajax: {
      url: '<?=base_admin();?>modul/approval_center/approval_center_data.php',
      type: 'post',
      data: function(d) {
        d.tgl_awal = $('#filter_tgl_awal').val();
        d.tgl_akhir = $('#filter_tgl_akhir').val();
        d.status = $('#filter_status').val();
        d.approver = $('#filter_approver').val();
        d.reference = $('#filter_reference').val();
      },
      error: function(xhr) {
        console.log(xhr);
        showApprovalError(approvalLang.loadFailed);
      }
    }
  });

  refreshApprovalSummary();

  $('#btn_filter_approval').on('click', function(){ dtbApproval.draw(); });
  $('#filter_reference').on('keyup', function(e){ if (e.keyCode === 13) dtbApproval.draw(); });
  $('#btn_reset_approval').on('click', function(){
    $('#filter_tgl_awal,#filter_tgl_akhir,#filter_reference').val('');
    $('#filter_status').val('PENDING').trigger('change');
    $('#filter_approver').val('').trigger('change');
    dtbApproval.search('').columns().search('').draw();
  });

  $(document).on('click', '.btn-detail-approval', function(){
    var id = $(this).data('id');
    $('#loadnya').show();
    $.ajax({
      type:'POST',
      url:'<?=base_admin();?>modul/approval_center/approval_center_action.php?act=detail',
      data:{id:id},
      success:function(html){
        $('#loadnya').hide();
        $('#isi_detail_approval').html(html);
        $('#modal_detail_approval').modal('show');
      },
      error:function(){
        $('#loadnya').hide();
        showApprovalError(approvalLang.detailFailed);
      }
    });
  });

  $(document).on('click', '.btn-approve,.btn-reject', function(){
    var act = $(this).hasClass('btn-approve') ? 'approve' : 'reject';
    var noDoc = $(this).data('no');
    $('#decision_id').val($(this).data('id'));
    $('#decision_act').val(act);
    $('#decision_note').val('');
    $('#decision_doc').text((act === 'approve' ? approvalLang.approve + ' ' : approvalLang.reject + ' ') + noDoc);
    $('#decision_required').toggle(act === 'reject');
    $('#decision_title').text(act === 'approve' ? approvalLang.approvePr : approvalLang.rejectPr);
    $('#btn_submit_decision')
      .removeClass('btn-success btn-danger btn-primary')
      .addClass(act === 'approve' ? 'btn-success' : 'btn-danger')
      .html(act === 'approve' ? '<i class="fa fa-check"></i> ' + approvalLang.approve : '<i class="fa fa-times"></i> ' + approvalLang.reject);
    $('#modal_decision_approval').modal('show');
  });

  $('#form_decision_approval').on('submit', function(e){
    e.preventDefault();
    var id = $('#decision_id').val();
    var act = $('#decision_act').val();
    var note = $('#decision_note').val();
    if (act === 'reject' && $.trim(note) === '') {
      showApprovalError(approvalLang.rejectNoteRequired);
      return;
    }
    $('#loadnya').show();
    $('#btn_submit_decision').prop('disabled', true);
    $.ajax({
      type:'POST',
      dataType:'json',
      url:'<?=base_admin();?>modul/approval_center/approval_center_action.php?act=' + act,
      data:{id:id, note:note},
      success:function(responseText){
        $('#loadnya').hide();
        $('#btn_submit_decision').prop('disabled', false);
        $.each(responseText, function(index) {
          if (responseText[index].status === 'good') {
            $('.error_data_delete').hide();
            $('#modal_decision_approval').modal('hide');
            $('#modal_detail_approval').modal('hide');
            refreshApprovalSummary();
            dtbApproval.draw(false);
          } else {
            showApprovalError(responseText[index].error_message);
          }
        });
      },
      error:function(){
        $('#loadnya').hide();
        $('#btn_submit_decision').prop('disabled', false);
        showApprovalError(approvalLang.serverError);
      }
    });
  });

  $(document).on('click', '.hide_alert_notif', function(){
    $('.error_data_delete').hide();
  });
});
</script>
