<?php
if (!function_exists('po_i18n_t')) {
  function po_i18n_t($key, $fallback = '')
  {
    return lang_text($key, $fallback);
  }
}
if (!function_exists('po_i18n_h')) {
  function po_i18n_h($value)
  {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}
$poLang = array(
  'export_data' => po_i18n_t('common_export_data', 'Export Data'),
  'print' => po_i18n_t('purchase_order_print', 'Print Purchase Order'),
  'edit' => po_i18n_t('edit', 'Edit'),
  'delete' => po_i18n_t('common_delete', 'Delete'),
  'loading' => 'Loading...',
  'loading_detail' => po_i18n_t('purchase_order_detail_title', 'Purchase Order Detail') . '...',
  'detail_failed' => po_i18n_t('common_load_failed', 'Data failed to load.'),
);
?>
<style>
  #dtb_purchase_order td,#dtb_purchase_order th{font-size:12px;vertical-align:middle}
  .po-detail-link{color:#1d4ed8;text-decoration:none}.po-detail-link:hover{text-decoration:underline}
  .po-action-buttons{white-space:nowrap;min-width:120px}.po-action-buttons .btn{margin-right:3px}
  .po-hero{background:linear-gradient(135deg,#1d4ed8,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(29,78,216,.18)}
  .po-hero h1{margin:0 0 6px;font-size:25px;font-weight:700}.po-hero p{margin:0;opacity:.9}.select2-container{width:100%!important}
  #modal_po_detail .modal-dialog{width:calc(100vw - 40px);max-width:1100px}
  #modal_po_detail .modal-content{border:0;border-radius:14px;overflow:hidden;box-shadow:0 18px 52px rgba(15,23,42,.28)}
  #modal_po_detail .modal-header{background:linear-gradient(135deg,#1d4ed8,#0f766e);color:#fff;border-bottom:0}
  #modal_po_detail .modal-title{font-weight:800;color:#fff}
  #modal_po_detail .modal-body{max-height:calc(100vh - 180px);overflow:auto;background:#f8fafc}
  .po-detail-card{background:#fff;border:1px solid #e5edf5;border-radius:12px;padding:13px;margin-bottom:12px;box-shadow:0 5px 16px rgba(15,23,42,.045)}
  .po-detail-card span{display:block;color:#64748b;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.03em}.po-detail-card strong{display:block;color:#0f172a;font-size:14px;margin-top:3px}
  .po-detail-table th,.po-detail-table td{font-size:12px;vertical-align:middle!important}
</style>
<section class="content-header">
  <h1><?=po_i18n_h(po_i18n_t('purchase_order_title', 'Purchase Order'));?> <small><?=po_i18n_h(po_i18n_t('purchase_order_subtitle', 'SAP MM Purchasing Document'));?></small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=po_i18n_h(po_i18n_t('common_home', 'Home'));?></a></li><li class="active"><?=po_i18n_h(po_i18n_t('purchase_order_title', 'Purchase Order'));?></li></ol>
</section>
<section class="content">
  <div class="po-hero">
    <div class="row">
      <div class="col-md-8"><h1><?=po_i18n_h(po_i18n_t('purchase_order_workbench', 'Purchase Order Workbench'));?></h1><p><?=po_i18n_h(po_i18n_t('purchase_order_intro', 'Monitor purchase orders from RFQ award / approved PR through goods receipt.'));?></p></div>
      <div class="col-md-4 text-right">
        <?php if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") { ?>
          <a href="<?=base_index();?>purchase-order/tambah" class="btn btn-warning"><i class="fa fa-plus"></i> <?=po_i18n_h(po_i18n_t('purchase_order_create', 'Create PO'));?></a>
        <?php } ?>
      </div>
    </div>
  </div>
  <div class="box">
    <div class="box-body">
      <form id="form_filter_po" class="form-horizontal" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=po_i18n_h(po_i18n_t('purchase_order_po_date', 'PO Date'));?></label>
          <div class="col-lg-2"><div class="input-group date filter-date"><input type="text" class="form-control" id="tgl_awal" placeholder="<?=po_i18n_h(po_i18n_t('purchase_order_start_date', 'Start date'));?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date filter-date"><input type="text" class="form-control" id="tgl_akhir" placeholder="<?=po_i18n_h(po_i18n_t('purchase_order_end_date', 'End date'));?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=po_i18n_h(po_i18n_t('purchase_order_vendor', 'Vendor'));?></label>
          <div class="col-lg-3"><select id="supplier" class="form-control"><option value="all"><?=po_i18n_h(po_i18n_t('purchase_order_all_vendor', 'All Vendors'));?></option><?php foreach ($db->query("SELECT kode_pemasok,nama FROM pemasok ORDER BY nama") as $vendor) { ?><option value="<?=htmlspecialchars($vendor->kode_pemasok,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($vendor->kode_pemasok.' - '.$vendor->nama,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=po_i18n_h(po_i18n_t('purchase_order_gr_status', 'GR Status'));?></label>
          <div class="col-lg-2"><select id="status_po" class="form-control"><option value="all"><?=po_i18n_h(po_i18n_t('common_all', 'All'));?></option><option value="OPEN">OPEN</option><option value="PARTIAL">PARTIAL</option><option value="CLOSED">CLOSED</option></select></div>
          <label class="control-label col-lg-1"><?=po_i18n_h(po_i18n_t('purchase_order_approval', 'Approval'));?></label>
          <div class="col-lg-2"><select id="approval_status" class="form-control"><option value="all"><?=po_i18n_h(po_i18n_t('common_all', 'All'));?></option><option value="Pending">Pending</option><option value="Approved">Approved</option><option value="Rejected">Rejected</option></select></div>
          <div class="col-lg-5">
            <button type="button" class="btn btn-primary" id="btn_filter"><i class="fa fa-filter"></i> <?=po_i18n_h(po_i18n_t('common_filter', 'Filter'));?></button>
            <button type="button" class="btn btn-default" id="btn_reset"><i class="fa fa-refresh"></i> <?=po_i18n_h(po_i18n_t('common_reset', 'Reset'));?></button>
            <button type="button" class="btn btn-success" id="btn_excel"><i class="fa fa-file-excel-o"></i> <?=po_i18n_h(po_i18n_t('common_export_excel', 'Export Excel'));?></button>
          </div>
        </div>
      </form>
      <hr>
      <div class="alert alert-warning fade in error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_purchase_order" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead><tr><th>No</th><th><?=po_i18n_h(po_i18n_t('purchase_order_po', 'PO'));?></th><th><?=po_i18n_h(po_i18n_t('purchase_order_po_date', 'PO Date'));?></th><th><?=po_i18n_h(po_i18n_t('purchase_order_vendor', 'Vendor'));?></th><th><?=po_i18n_h(po_i18n_t('purchase_order_delivery_term', 'Delivery Term'));?></th><th><?=po_i18n_h(po_i18n_t('purchase_order_payment_term', 'Payment Term'));?></th><th><?=po_i18n_h(po_i18n_t('purchase_order_items', 'Items'));?></th><th><?=po_i18n_h(po_i18n_t('purchase_order_gr_qty', 'GR Qty'));?></th><th><?=po_i18n_h(po_i18n_t('purchase_order_gr_status', 'GR Status'));?></th><th><?=po_i18n_h(po_i18n_t('purchase_order_approval', 'Approval'));?></th><th><?=po_i18n_h(po_i18n_t('common_action', 'Action'));?></th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<div id="modal_po_detail" class="modal fade" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:.9">&times;</button>
        <h4 class="modal-title"><i class="fa fa-file-text-o"></i> <?=po_i18n_h(po_i18n_t('purchase_order_detail_title', 'Purchase Order Detail'));?></h4>
      </div>
      <div class="modal-body" id="po_detail_body">
        <div class="text-center text-muted" style="padding:30px"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=po_i18n_h(po_i18n_t('common_close', 'Close'));?></button>
      </div>
    </div>
  </div>
</div>
<?php
$edit = (isset($role_act["up_act"]) && $role_act["up_act"]=="Y")
  ? "<a href=\"".base_index()."purchase-order/edit/'+aData[indek]+'\" class=\"btn btn-primary btn-xs\" title=\"".po_i18n_h(po_i18n_t('edit', 'Edit'))."\"><i class=\"fa fa-pencil\"></i></a>"
  : "";
$del = (isset($role_act["del_act"]) && $role_act["del_act"]=="Y")
  ? "<button data-id=\"'+aData[indek]+'\" data-uri=\"".base_admin()."modul/purchase_order/purchase_order_action.php\" class=\"btn btn-danger hapus_dtb_notif btn-xs\" title=\"".po_i18n_h(po_i18n_t('common_delete', 'Delete'))."\" data-variable=\"dtb_purchase_order\"><i class=\"fa fa-trash\"></i></button>"
  : "";
?>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
var poLang = <?=json_encode($poLang, JSON_UNESCAPED_UNICODE);?>;
$(function(){
  if($.fn.datepicker){$('.filter-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#supplier,#status_po,#approval_status').select2({width:'100%'});}
  var dtb_purchase_order=$("#dtb_purchase_order").DataTable({
    fnCreatedRow:function(nRow,aData){var indek=aData.length-1;$('td:eq('+indek+')',nRow).html('<div class="po-action-buttons"><a href="<?=base_url();?>modul/purchase_order/cetak_po.php?po_no='+aData[indek]+'" target="_blank" class="btn btn-success btn-xs" title="'+poLang.print+'"><i class="fa fa-print"></i></a> <?=$edit;?> <?=$del;?></div>');$(nRow).attr('id','line_'+aData[indek]);},
    dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons:[{extend:'collection',text:poLang.export_data,buttons:['pdfHtml5','csvHtml5','copyHtml5','excelHtml5']}],
    bProcessing:true,bServerSide:true,
    columnDefs:[{targets:[10],orderable:false,searchable:false},{targets:[6,7],className:'text-right'},{width:'45px',targets:0,orderable:false,searchable:false,className:'dt-center'}],
    ajax:{url:'<?=base_admin();?>modul/purchase_order/purchase_order_data.php',type:'post',data:function(d){d.tgl_awal=$('#tgl_awal').val();d.tgl_akhir=$('#tgl_akhir').val();d.supplier=$('#supplier').val();d.status_po=$('#status_po').val();d.approval_status=$('#approval_status').val();},error:function(xhr){console.log(xhr);}}
  });
  $('#btn_filter').on('click',function(){dtb_purchase_order.draw();});
  $('#btn_reset').on('click',function(){$('#tgl_awal,#tgl_akhir').val('');$('#supplier,#status_po,#approval_status').val('all').trigger('change');dtb_purchase_order.draw();});
  $('#btn_excel').on('click',function(){window.open('<?=base_admin();?>modul/purchase_order/purchase_order_action.php?act=excel&tgl_awal='+$('#tgl_awal').val()+'&tgl_akhir='+$('#tgl_akhir').val()+'&supplier='+$('#supplier').val()+'&status='+$('#status_po').val(),'_blank');});
  $(document).on('click','.po-detail-link',function(e){
    e.preventDefault();
    var id=$(this).data('id');
    $('#po_detail_body').html('<div class="text-center text-muted" style="padding:30px"><i class="fa fa-spinner fa-spin"></i> '+poLang.loading_detail+'</div>');
    $('#modal_po_detail').modal('show');
    $.ajax({
      url:'<?=base_admin();?>modul/purchase_order/purchase_order_action.php?act=detail',
      type:'POST',
      data:{id:id},
      success:function(html){$('#po_detail_body').html(html);},
      error:function(xhr){$('#po_detail_body').html('<div class="alert alert-danger">'+poLang.detail_failed+'<br><small>'+xhr.responseText+'</small></div>');}
    });
  });
});
</script>
