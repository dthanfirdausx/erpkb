<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$plants = $db->query("SELECT id,plant_code,plant_name FROM erp_plant WHERE status='Aktif' ORDER BY plant_code");
$storageLocations = $db->query("SELECT s.id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$storageBins = $db->query("SELECT b.id,b.bin_code,b.bin_name,s.storage_code FROM erp_storage_bin b LEFT JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code");
$users = $db->query("
  SELECT created_by AS username FROM erp_storage_location_transfer WHERE created_by IS NOT NULL AND created_by<>''
  UNION
  SELECT created_by AS username FROM erp_storage_bin_transfer WHERE created_by IS NOT NULL AND created_by<>''
  UNION
  SELECT created_by AS username FROM erp_stock_type_transfer WHERE created_by IS NOT NULL AND created_by<>''
  ORDER BY username
");
$kpi = $db->fetch("
  SELECT COUNT(*) AS total_doc,
         COALESCE(SUM(status='POSTED'),0) AS posted_doc,
         COALESCE(SUM(status='REVERSED'),0) AS reversed_doc,
         COALESCE(SUM(total_qty),0) AS total_qty
  FROM (
    SELECT h.status,COALESCE(SUM(d.qty),0) AS total_qty FROM erp_storage_location_transfer h LEFT JOIN erp_storage_location_transfer_detail d ON d.transfer_id=h.id WHERE h.posting_date BETWEEN ? AND ? GROUP BY h.id,h.status
    UNION ALL
    SELECT h.status,COALESCE(SUM(d.qty),0) AS total_qty FROM erp_storage_bin_transfer h LEFT JOIN erp_storage_bin_transfer_detail d ON d.transfer_id=h.id WHERE h.posting_date BETWEEN ? AND ? GROUP BY h.id,h.status
    UNION ALL
    SELECT h.status,COALESCE(SUM(d.qty),0) AS total_qty FROM erp_stock_type_transfer h LEFT JOIN erp_stock_type_transfer_detail d ON d.transfer_id=h.id WHERE h.posting_date BETWEEN ? AND ? GROUP BY h.id,h.status
  ) x",
  array($defaultFrom,$defaultTo,$defaultFrom,$defaultTo,$defaultFrom,$defaultTo)
);
?>
<style>
  .th-hero{background:linear-gradient(135deg,#334155,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
  .th-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.th-hero p{margin:0;opacity:.92}
  .th-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .th-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.th-kpi strong{display:block;font-size:24px;margin-top:6px;color:#111827}.th-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}
  #dtb_transfer_history td,#dtb_transfer_history th{font-size:12px;vertical-align:middle}.th-action-buttons{white-space:nowrap;min-width:62px}
  .th-badge{display:inline-block;border-radius:999px;padding:3px 8px;font-size:11px;font-weight:600}.th-posted{background:#dcfce7;color:#166534}.th-reversed{background:#fee2e2;color:#991b1b}.th-type{background:#e0f2fe;color:#075985}
  .select2-container{width:100%!important}.th-detail-table th{width:190px;background:#f8fafc}.th-filter .form-group{margin-bottom:12px}
</style>
<section class="content-header">
  <h1>Transfer History <small>SAP MM Transfer Monitor</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Transfer History</li>
  </ol>
</section>
<section class="content">
  <div class="th-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Transfer History Workbench</h1>
        <p>Monitor terpusat untuk Storage Location Transfer, Storage Bin Transfer, dan Stock Type Transfer. Read-only audit trail dengan trace layer, dokumen BC/BPB, filter, dan export Excel.</p>
      </div>
      <div class="col-md-4 text-right"><span class="label label-primary"><?=wh_h(wh_t('warehouse_read_only_monitor', 'Read Only Monitor'));?></span></div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="th-kpi"><i class="fa fa-file-text-o"></i><span>Total Transfer</span><strong><?=number_format((float)$kpi->total_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="th-kpi"><i class="fa fa-check"></i><span>Posted</span><strong><?=number_format((float)$kpi->posted_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="th-kpi"><i class="fa fa-undo"></i><span>Reversed</span><strong><?=number_format((float)$kpi->reversed_doc,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="th-kpi"><i class="fa fa-cubes"></i><span>Total Qty</span><strong><?=number_format((float)$kpi->total_qty,2,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Transfer History</h3></div>
    <div class="box-body">
      <form id="filter_th" class="form-horizontal th-filter" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></label>
          <div class="col-lg-2"><div class="input-group date th-date"><input id="filter_tgl_awal" class="form-control" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date th-date"><input id="filter_tgl_akhir" class="form-control" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1">Type</label>
          <div class="col-lg-2"><select id="filter_transfer_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option value="SLT"><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></option><option value="SBT"><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></option><option value="STT"><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></option></select></div>
          <label class="control-label col-lg-1"><?=wh_h(wh_t('common_status', 'Status'));?></label>
          <div class="col-lg-2"><select id="filter_status" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option>POSTED</option><option>REVERSED</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_movement', 'Movement'));?></label>
          <div class="col-lg-2"><select id="filter_movement_type" class="form-control"><option value=""><?=wh_h(wh_t('common_all', 'Semua'));?></option><option>311</option><option>312</option><option>321</option><option>322</option><option>343</option><option>344</option><option>349</option><option>350</option></select></div>
          <label class="control-label col-lg-1"><?=wh_h(wh_t('common_plant', 'Plant'));?></label>
          <div class="col-lg-3"><select id="filter_plant_id" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_all_plant', 'Semua Plant'));?></option><?php foreach($plants as $p){ ?><option value="<?=intval($p->id);?>"><?=htmlspecialchars($p->plant_code.' - '.$p->plant_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <label class="control-label col-lg-1"><?=wh_h(wh_t('warehouse_user', 'User'));?></label>
          <div class="col-lg-3"><select id="filter_user" class="form-control"><option value="">Semua User</option><?php foreach($users as $u){ ?><option value="<?=htmlspecialchars($u->username,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($u->username,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></label>
          <div class="col-lg-3"><select id="filter_storage_location_id" class="form-control"><option value="">Source/Destination SLoc</option><?php foreach($storageLocations as $s){ ?><option value="<?=intval($s->id);?>"><?=htmlspecialchars($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <label class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></label>
          <div class="col-lg-3"><select id="filter_storage_bin_id" class="form-control"><option value="">Source/Destination Bin</option><?php foreach($storageBins as $b){ ?><option value="<?=intval($b->id);?>"><?=htmlspecialchars($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name,ENT_QUOTES,'UTF-8');?></option><?php } ?></select></div>
          <div class="col-lg-2"><select id="filter_stock_type" class="form-control"><option value=""><?=wh_h(wh_t('warehouse_stock_type', 'Stock Type'));?></option><option value="UNRESTRICTED"><?=wh_h(wh_t('warehouse_unrestricted', 'Unrestricted'));?></option><option value="QUALITY">Quality</option><option value="BLOCKED"><?=wh_h(wh_t('warehouse_blocked', 'Blocked'));?></option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2"><?=wh_h(wh_t('common_search', 'Search'));?></label>
          <div class="col-lg-5"><input id="filter_keyword" class="form-control" placeholder="Transfer no / material / no aju / dok pabean / BPB / reason"></div>
          <div class="col-lg-5">
            <button type="button" id="btn_filter_th" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button>
            <button type="button" id="btn_reset_th" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button>
            <button type="button" id="btn_excel_th" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=wh_h(wh_t('common_download_excel', 'Download Excel'));?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none"><button type="button" class="close hide_alert_notif">&times;</button><i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span></div>
      <div class="table-responsive">
        <table id="dtb_transfer_history" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead><tr><th><?=wh_h(wh_t('table_no', 'No'));?></th><th><?=wh_h(wh_t('common_action', 'Action'));?></th><th>Transfer Doc</th><th><?=wh_h(wh_t('warehouse_posting_date', 'Posting Date'));?></th><th>Type</th><th><?=wh_h(wh_t('warehouse_movement', 'Movement'));?></th><th>Source</th><th>Destination</th><th>Items</th><th>Total Qty</th><th>Total Amount</th><th>Trace</th><th><?=wh_h(wh_t('common_status', 'Status'));?></th><th><?=wh_h(wh_t('warehouse_user', 'User'));?></th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="modal_detail_th" class="modal fade"><div class="modal-dialog modal-lg" style="width:96%"><div class="modal-content"><div class="modal-header"><button class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Transfer Detail</h4></div><div class="modal-body" id="isi_detail_th"></div><div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div></div></div></div>
</section>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script>
function showThError(m){$('.isi_warning_delete').text(m||'Transfer History gagal diproses.');$('.error_data_delete').fadeIn();}
$(function(){
  if($.fn.datepicker){$('.th-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('#filter_transfer_type,#filter_status,#filter_movement_type,#filter_plant_id,#filter_user,#filter_storage_location_id,#filter_storage_bin_id,#filter_stock_type').select2({width:'100%'});}
  var dt=$('#dtb_transfer_history').DataTable({
    bProcessing:true,bServerSide:true,pageLength:25,
    dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons:[{extend:'collection',text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],
    columnDefs:[{targets:[0,1],orderable:false,searchable:false},{targets:[8,9,10],className:'text-right'},{width:'42px',targets:0},{width:'62px',targets:1}],
    ajax:{url:'<?=base_admin();?>modul/transfer_history/transfer_history_data.php',type:'post',data:function(d){
      d.tgl_awal=$('#filter_tgl_awal').val();d.tgl_akhir=$('#filter_tgl_akhir').val();d.transfer_type=$('#filter_transfer_type').val();d.status=$('#filter_status').val();d.movement_type=$('#filter_movement_type').val();d.plant_id=$('#filter_plant_id').val();d.storage_location_id=$('#filter_storage_location_id').val();d.storage_bin_id=$('#filter_storage_bin_id').val();d.stock_type=$('#filter_stock_type').val();d.user=$('#filter_user').val();d.keyword=$('#filter_keyword').val();
    },error:function(xhr){console.log(xhr);showThError('Data Transfer History gagal dimuat.');}}
  });
  $('#btn_filter_th').on('click',function(){dt.draw();});
  $('#filter_keyword').on('keyup',function(e){if(e.keyCode===13)dt.draw();});
  $('#btn_reset_th').on('click',function(){
    $('#filter_tgl_awal').val('<?=$defaultFrom;?>');$('#filter_tgl_akhir').val('<?=$defaultTo;?>');$('#filter_keyword').val('');
    $('#filter_transfer_type,#filter_status,#filter_movement_type,#filter_plant_id,#filter_user,#filter_storage_location_id,#filter_storage_bin_id,#filter_stock_type').val('').trigger('change');
    dt.draw();
  });
  $('#btn_excel_th').on('click',function(){
    var url='<?=base_admin();?>modul/transfer_history/transfer_history_action.php?act=excel'
      +'&tgl_awal='+encodeURIComponent($('#filter_tgl_awal').val())
      +'&tgl_akhir='+encodeURIComponent($('#filter_tgl_akhir').val())
      +'&transfer_type='+encodeURIComponent($('#filter_transfer_type').val()||'')
      +'&status='+encodeURIComponent($('#filter_status').val()||'')
      +'&movement_type='+encodeURIComponent($('#filter_movement_type').val()||'')
      +'&plant_id='+encodeURIComponent($('#filter_plant_id').val()||'')
      +'&storage_location_id='+encodeURIComponent($('#filter_storage_location_id').val()||'')
      +'&storage_bin_id='+encodeURIComponent($('#filter_storage_bin_id').val()||'')
      +'&stock_type='+encodeURIComponent($('#filter_stock_type').val()||'')
      +'&user='+encodeURIComponent($('#filter_user').val()||'')
      +'&keyword='+encodeURIComponent($('#filter_keyword').val()||'');
    window.location.href=url;
  });
  $(document).on('click','.btn-detail-th',function(){
    $.post('<?=base_admin();?>modul/transfer_history/transfer_history_action.php?act=detail',{id:$(this).data('id'),type:$(this).data('type')},function(html){
      $('#isi_detail_th').html(html);$('#modal_detail_th').modal('show');
    }).fail(function(){showThError('Detail transfer gagal dibuka.');});
  });
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
