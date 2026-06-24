<style>
.pr-hero{background:linear-gradient(135deg,#0f172a,#1d4ed8);color:#fff;border-radius:16px;padding:22px;margin-bottom:18px;box-shadow:0 12px 28px rgba(15,23,42,.18)}
.pr-hero h1{margin:0 0 7px;font-size:28px;font-weight:800}.pr-hero p{margin:0;color:#dbeafe}.pr-actions{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap}.pr-actions .btn{border:0;border-radius:9px;font-weight:700;box-shadow:0 8px 18px rgba(15,23,42,.14)}
.pr-card{border:1px solid #e5edf5;border-radius:14px;background:#fff;box-shadow:0 7px 20px rgba(15,23,42,.055);margin-bottom:16px}.pr-card .box-header{border-bottom:1px solid #edf2f7;padding:14px 16px}.pr-card .box-title{font-weight:800;color:#0f172a}
.pr-kpi{border:1px solid #e5edf5;border-radius:14px;background:#fff;padding:15px;min-height:104px;margin-bottom:16px;box-shadow:0 6px 16px rgba(15,23,42,.045)}.pr-kpi i{width:38px;height:38px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;color:#fff;background:#1d4ed8;margin-bottom:9px}.pr-kpi span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase}.pr-kpi strong{display:block;font-size:22px;color:#0f172a;line-height:1.25}
.pr-filter label{font-size:12px;color:#475569;text-transform:uppercase;letter-spacing:.03em}.pr-filter .form-control{border-radius:8px}.pr-action-buttons{white-space:nowrap}.pr-action-buttons .btn{margin-right:3px;border-radius:7px}
#dtb_pr th,#dtb_pr td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}@media(max-width:991px){.pr-actions{justify-content:flex-start;margin-top:12px}}
</style>
<?php
if (!function_exists('pr_lang_t')) {
  function pr_lang_t($key, $fallback = '') {
    return lang_text($key, $fallback);
  }
}
if (!function_exists('pr_lang_h')) {
  function pr_lang_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}
$prSummary = $db->fetch("SELECT COUNT(*) total_pr,
                                SUM(status='SUBMITTED') submitted_pr,
                                SUM(status='APPROVED') approved_pr,
                                SUM(status IN ('DRAFT','SUBMITTED','APPROVED','PARTIAL_PO')) open_pr
                         FROM purchase_requisition");
$prViewLang = array(
  'detailOpenFailed' => pr_lang_t('purchase_requisition_detail_open_failed', 'Gagal membuka detail PR.'),
  'cancelConfirm' => pr_lang_t('purchase_requisition_cancel_confirm', 'Cancel Purchase Requisition ini?'),
  'cancelFailed' => pr_lang_t('purchase_requisition_cancel_failed', 'Gagal cancel PR.'),
  'cancelServerError' => pr_lang_t('purchase_requisition_cancel_server_error', 'Server error saat cancel PR.'),
);
?>

<section class="content-header">
  <h1><?=pr_lang_h(pr_lang_t('purchase_requisition_title','Purchase Requisition'));?> <small><?=pr_lang_h(pr_lang_t('purchase_requisition_subtitle','SAP MM PR'));?></small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=pr_lang_h(pr_lang_t('common_home','Home'));?></a></li>
    <li class="active"><?=pr_lang_h(pr_lang_t('purchase_requisition_title','Purchase Requisition'));?></li>
  </ol>
</section>

<section class="content">
  <div class="pr-hero">
    <div class="row">
      <div class="col-md-7">
        <h1><i class="fa fa-file-text-o"></i> <?=pr_lang_h(pr_lang_t('purchase_requisition_workbench','Purchase Requisition'));?></h1>
        <p><?=pr_lang_h(pr_lang_t('purchase_requisition_intro','SAP MM PR workbench untuk memonitor kebutuhan pembelian, status approval, open quantity, dan kesiapan konversi ke Purchase Order.'));?></p>
      </div>
      <div class="col-md-5 pr-actions">
        <?php if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") { ?>
          <a href="<?=base_index();?>pr/tambah" class="btn btn-warning"><i class="fa fa-plus"></i> <?=pr_lang_h(pr_lang_t('purchase_requisition_create','Create PR'));?></a>
        <?php } ?>
        <button type="button" id="btn_excel_pr_top" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=pr_lang_h(pr_lang_t('common_export_excel','Export Excel'));?></button>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-3"><div class="pr-kpi"><i class="fa fa-list"></i><span><?=pr_lang_h(pr_lang_t('purchase_requisition_total_pr','Total PR'));?></span><strong><?=number_format($prSummary ? $prSummary->total_pr : 0,0,',','.');?></strong><small><?=pr_lang_h(pr_lang_t('purchase_requisition_total_pr_hint','Semua purchase requisition'));?></small></div></div>
    <div class="col-md-3"><div class="pr-kpi"><i class="fa fa-clock-o"></i><span><?=pr_lang_h(pr_lang_t('purchase_requisition_submitted','Submitted'));?></span><strong><?=number_format($prSummary ? $prSummary->submitted_pr : 0,0,',','.');?></strong><small><?=pr_lang_h(pr_lang_t('purchase_requisition_submitted_hint','Menunggu approval/review'));?></small></div></div>
    <div class="col-md-3"><div class="pr-kpi"><i class="fa fa-check"></i><span><?=pr_lang_h(pr_lang_t('purchase_requisition_approved','Approved'));?></span><strong><?=number_format($prSummary ? $prSummary->approved_pr : 0,0,',','.');?></strong><small><?=pr_lang_h(pr_lang_t('purchase_requisition_approved_hint','Siap proses purchasing'));?></small></div></div>
    <div class="col-md-3"><div class="pr-kpi"><i class="fa fa-folder-open-o"></i><span><?=pr_lang_h(pr_lang_t('purchase_requisition_open_pr','Open PR'));?></span><strong><?=number_format($prSummary ? $prSummary->open_pr : 0,0,',','.');?></strong><small><?=pr_lang_h(pr_lang_t('purchase_requisition_open_pr_hint','Masih ada proses lanjut'));?></small></div></div>
  </div>

  <div class="box pr-card pr-filter">
    <div class="box-header"><h3 class="box-title"><i class="fa fa-filter"></i> <?=pr_lang_h(pr_lang_t('purchase_requisition_filter_title','Filter Purchase Requisition'));?></h3></div>
    <div class="box-body">
      <form id="filter_pr" class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=pr_lang_h(pr_lang_t('purchase_requisition_pr_date','PR Date'));?></label>
          <div class="col-lg-2">
            <div class="input-group date filter-date">
              <input type="text" id="filter_tgl_awal" class="form-control" placeholder="<?=pr_lang_h(pr_lang_t('purchase_order_start_date','Start date'));?>" autocomplete="off">
              <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
            </div>
          </div>
          <div class="col-lg-2">
            <div class="input-group date filter-date">
              <input type="text" id="filter_tgl_akhir" class="form-control" placeholder="<?=pr_lang_h(pr_lang_t('purchase_order_end_date','End date'));?>" autocomplete="off">
              <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=pr_lang_h(pr_lang_t('common_status','Status'));?></label>
          <div class="col-lg-3">
            <select id="filter_status" class="form-control">
              <option value=""><?=pr_lang_h(pr_lang_t('purchase_requisition_all_status','Semua Status'));?></option>
              <option value="DRAFT">DRAFT</option>
              <option value="SUBMITTED">SUBMITTED</option>
              <option value="APPROVED">APPROVED</option>
              <option value="REJECTED">REJECTED</option>
              <option value="PARTIAL_PO">PARTIAL_PO</option>
              <option value="CONVERTED_PO">CONVERTED_PO</option>
              <option value="CLOSED">CLOSED</option>
              <option value="CANCELLED">CANCELLED</option>
            </select>
          </div>
          <label class="control-label col-lg-1"><?=pr_lang_h(pr_lang_t('common_plant','Plant'));?></label>
          <div class="col-lg-3">
            <select id="filter_plant" class="form-control">
              <option value=""><?=pr_lang_h(pr_lang_t('purchase_requisition_all_plant','Semua Plant'));?></option>
              <?php foreach ($db->query("SELECT plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code") as $plant) { ?>
                <option value="<?=htmlspecialchars($plant->plant_code,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($plant->plant_code.' - '.$plant->plant_name,ENT_QUOTES,'UTF-8');?></option>
              <?php } ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=pr_lang_h(pr_lang_t('search','Search'));?></label>
          <div class="col-lg-5">
            <input type="text" id="filter_reference" class="form-control" placeholder="<?=pr_lang_h(pr_lang_t('purchase_requisition_search_placeholder','Cari No PR / requestor / department / material'));?>">
          </div>
          <div class="col-lg-4">
            <button type="button" id="btn_filter_pr" class="btn btn-primary"><i class="fa fa-filter"></i> <?=pr_lang_h(pr_lang_t('common_filter','Filter'));?></button>
            <button type="button" id="btn_reset_pr" class="btn btn-default"><i class="fa fa-refresh"></i> <?=pr_lang_h(pr_lang_t('common_reset','Reset'));?></button>
            <button type="button" id="btn_excel_pr" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=pr_lang_h(pr_lang_t('common_export_excel','Export Excel'));?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box pr-card">
    <div class="box-header"><h3 class="box-title"><i class="fa fa-table"></i> <?=pr_lang_h(pr_lang_t('purchase_requisition_data_title','Data Purchase Requisition'));?></h3></div>
    <div class="box-body">
      <div class="alert alert-warning fade in error_data_delete" style="display:none">
        <button type="button" class="close hide_alert_notif">&times;</button>
        <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
      </div>

      <div class="table-responsive">
        <table id="dtb_pr" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead>
            <tr>
              <th>No</th>
              <th><?=pr_lang_h(pr_lang_t('common_action','Action'));?></th>
              <th><?=pr_lang_h(pr_lang_t('purchase_requisition_no_pr','No PR'));?></th>
              <th><?=pr_lang_h(pr_lang_t('purchase_requisition_pr_date','PR Date'));?></th>
              <th><?=pr_lang_h(pr_lang_t('purchase_requisition_document_type','Document Type'));?></th>
              <th><?=pr_lang_h(pr_lang_t('common_plant','Plant'));?></th>
              <th><?=pr_lang_h(pr_lang_t('purchase_requisition_requestor','Requestor'));?></th>
              <th><?=pr_lang_h(pr_lang_t('common_department','Department'));?></th>
              <th><?=pr_lang_h(pr_lang_t('purchase_requisition_priority','Priority'));?></th>
              <th><?=pr_lang_h(pr_lang_t('purchase_requisition_required_date','Required Date'));?></th>
              <th class="text-right"><?=pr_lang_h(pr_lang_t('purchase_order_items','Items'));?></th>
              <th class="text-right"><?=pr_lang_h(pr_lang_t('purchase_requisition_open_qty','Open Qty'));?></th>
              <th><?=pr_lang_h(pr_lang_t('common_status','Status'));?></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_detail" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg" style="width: 92%">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><?=pr_lang_h(pr_lang_t('purchase_requisition_detail_title','Detail Purchase Requisition'));?></h4>
        </div>
        <div class="modal-body" id="isi_detail"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-dismiss="modal"><?=pr_lang_h(pr_lang_t('common_close','Close'));?></button>
        </div>
      </div>
    </div>
  </div>
</section>

<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
var prViewLang = <?=json_encode($prViewLang, JSON_UNESCAPED_UNICODE);?>;
function detail_pr(id) {
  $('#loadnya').show();
  $.ajax({
    type: 'POST',
    url: '<?=base_admin();?>modul/pr/pr_action.php?act=show_detail',
    data: {id:id},
    success: function(data) {
      $('#loadnya').hide();
      $('#isi_detail').html(data);
      $('#modal_detail').modal('show');
    },
    error: function() {
      $('#loadnya').hide();
      alert(prViewLang.detailOpenFailed);
    }
  });
}

$(function() {
  if ($.fn.datepicker) {
    $('.filter-date').datepicker({ autoclose:true, format:'yyyy-mm-dd', todayHighlight:true });
  }
  if ($.fn.select2) {
    $('#filter_status, #filter_plant').select2({ width:'100%', allowClear:true });
  }
  function prFilters(){
    return {
      tgl_awal: $('#filter_tgl_awal').val(),
      tgl_akhir: $('#filter_tgl_akhir').val(),
      status: $('#filter_status').val(),
      plant: $('#filter_plant').val(),
      reference: $('#filter_reference').val()
    };
  }

  var dtb_pr = $('#dtb_pr').DataTable({
    bProcessing: true,
    bServerSide: true,
    dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    columnDefs: [
      { targets:[0,1], orderable:false, searchable:false },
      { targets:[10,11], className:'text-right' },
      { width:'45px', targets:0, className:'dt-center' },
      { width:'90px', targets:1, className:'dt-center' }
    ],
    ajax: {
      url: '<?=base_admin();?>modul/pr/pr_data.php',
      type: 'post',
      data: function(d) {
        $.extend(d, prFilters());
      },
      error: function(xhr) { console.log(xhr); }
    }
  });

  $('#btn_filter_pr').on('click', function(){ dtb_pr.draw(); });
  $('#filter_reference').on('keyup', function(e){ if (e.keyCode === 13) dtb_pr.draw(); });
  $('#btn_excel_pr,#btn_excel_pr_top').on('click', function(){
    window.location = '<?=base_admin();?>modul/pr/pr_action.php?act=excel&' + $.param(prFilters());
  });
  $('#btn_reset_pr').on('click', function(){
    $('#filter_tgl_awal,#filter_tgl_akhir,#filter_reference').val('');
    $('#filter_status,#filter_plant').val('').trigger('change');
    dtb_pr.search('').columns().search('').draw();
  });

  $(document).on('click', '.btn-cancel-pr', function(){
    var id = $(this).data('id');
    if (!confirm(prViewLang.cancelConfirm)) return;
    $('#loadnya').show();
    $.ajax({
      type:'POST',
      dataType:'json',
      url:'<?=base_admin();?>modul/pr/pr_action.php?act=cancel',
      data:{id:id},
      success:function(responseText){
        $('#loadnya').hide();
        $.each(responseText, function(index) {
          if (responseText[index].status === 'good') {
            $('.error_data_delete').hide();
            dtb_pr.draw();
          } else {
            $('.isi_warning_delete').text(responseText[index].error_message || prViewLang.cancelFailed);
            $('.error_data_delete').fadeIn();
          }
        });
      },
      error:function(){
        $('#loadnya').hide();
        $('.isi_warning_delete').text(prViewLang.cancelServerError);
        $('.error_data_delete').fadeIn();
      }
    });
  });
});
</script>
