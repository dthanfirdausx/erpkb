<?php
if (!function_exists('fin_t')) {
  function fin_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('fin_h')) {
  function fin_h($key, $fallback = '') { return htmlspecialchars((string) fin_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('fin_js')) {
  function fin_js($key, $fallback = '') { return json_encode(fin_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
function ap_v_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$vendors=$db->query("SELECT kode_pemasok,nama FROM pemasok WHERE kode_pemasok IS NOT NULL ORDER BY nama");
?>
<link href="<?=base_admin();?>assets/plugins/select2/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="<?=base_admin();?>assets/plugins/datepicker/datepicker3.css">
<script src="<?=base_admin();?>assets/plugins/datepicker/bootstrap-datepicker.js"></script>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.ap-table th,.ap-table td{font-size:12px;vertical-align:middle!important}
.ap-kpi .description-header{font-size:20px}
.ap-help{background:#f8fbff;border-left:3px solid #00a65a;padding:10px;margin-bottom:12px}
.ap-filter .form-group{margin-bottom:12px}
</style>

<section class="content-header">
  <h1><?=fin_h('finance_ap_aging', 'AP Aging');?> <small>SAP FI-AP Open Item Analysis</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=fin_h('common_home', 'Home');?></a></li><li>Akunting</li><li>Accounts Payable</li><li class="active"><?=fin_h('finance_ap_aging', 'AP Aging');?></li></ol>
</section>

<section class="content">
  <div class="row ap-kpi">
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span>Total AP Outstanding</span><h5 id="kpi_total" class="description-header text-blue">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span>Current / Not Due</span><h5 id="kpi_current" class="description-header text-green">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span><?=fin_h('finance_overdue', 'Overdue');?></span><h5 id="kpi_overdue" class="description-header text-red">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span>Open Items</span><h5 id="kpi_count" class="description-header">0</h5></div></div></div></div>
  </div>
  <div class="row ap-kpi">
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-calendar"></i></span><div class="info-box-content"><span class="info-box-text">1-30 Days</span><span id="kpi_d1" class="info-box-number">0.00</span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-orange"><i class="fa fa-calendar-times-o"></i></span><div class="info-box-content"><span class="info-box-text">31-60 Days</span><span id="kpi_d31" class="info-box-number">0.00</span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-warning"></i></span><div class="info-box-content"><span class="info-box-text">61-90 Days</span><span id="kpi_d61" class="info-box-number">0.00</span></div></div></div>
    <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-maroon"><i class="fa fa-exclamation-triangle"></i></span><div class="info-box-content"><span class="info-box-text">&gt;90 Days</span><span id="kpi_d91" class="info-box-number">0.00</span></div></div></div>
  </div>

  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title">Payables Aging Cockpit</h3>
      <div class="box-tools"><button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> <?=fin_h('common_export_excel', 'Export Excel');?></button></div>
    </div>
    <div class="box-body">
      <form class="form-horizontal ap-filter" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-md-1">As Of</label>
          <div class="col-md-2"><div class="input-group date"><input id="as_of_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <label class="control-label col-md-1"><?=fin_h('finance_vendor', 'Vendor');?></label>
          <div class="col-md-4"><select id="vendor_code" class="form-control ap-select"><option value=""></option><?php foreach($vendors as $v){ ?><option value="<?=ap_v_h($v->kode_pemasok);?>"><?=ap_v_h($v->kode_pemasok.' - '.$v->nama);?></option><?php } ?></select></div>
          <label class="control-label col-md-1"><?=fin_h('common_status', 'Status');?></label>
          <div class="col-md-3"><select id="payment_status" class="form-control ap-select"><option value="">All</option><option value="OPEN">Open</option><option value="PARTIAL">Partial</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-md-1">Bucket</label>
          <div class="col-md-3"><select id="bucket" class="form-control ap-select"><option value="">All</option><option value="CURRENT">Current / Not Due</option><option value="1-30">1-30 Days</option><option value="31-60">31-60 Days</option><option value="61-90">61-90 Days</option><option value=">90">&gt;90 Days</option></select></div>
          <div class="col-md-8"><button type="button" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> Tampilkan</button> <button type="button" id="btn_reset" class="btn btn-default"><i class="fa fa-refresh"></i> <?=fin_h('common_reset', 'Reset');?></button></div>
        </div>
      </form>
      <div class="ap-help"><i class="fa fa-info-circle"></i> AP Aging membaca <b>Vendor Invoice POSTED</b> yang masih <b>OPEN/PARTIAL</b>, dikurangi <b>Vendor Payment POSTED</b> sampai tanggal As Of. Bucket dihitung dari due date seperti open item analysis SAP FI-AP.</div>
      <div class="table-responsive">
        <table id="ap_aging_table" class="table table-bordered table-striped table-hover ap-table">
          <thead><tr><th><?=fin_h('common_no', 'No');?></th><th><?=fin_h('finance_vendor', 'Vendor');?></th><th><?=fin_h('finance_invoice', 'Invoice');?></th><th>Posting</th><th>Due</th><th><?=fin_h('common_status', 'Status');?></th><th>Gross</th><th>Paid</th><th>Outstanding</th><th>Age</th><th>Bucket</th><th><?=fin_h('common_action', 'Action');?></th></tr></thead>
          <tbody id="ap_body"></tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="ap_detail_modal"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4>AP Aging Detail</h4></div><div class="modal-body" id="ap_detail_body"></div></div></div></div>

<script>
$(function(){
  if($.fn.datepicker){$('.input-group.date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('.ap-select').select2({width:'100%',allowClear:true,placeholder:'All'});}
  var apTable=null;
  function params(){return{as_of_date:$('#as_of_date').val(),vendor_code:$('#vendor_code').val(),bucket:$('#bucket').val(),payment_status:$('#payment_status').val()};}
  function rebuildApTable(){
    if(apTable){apTable.destroy();}
    apTable=$('#ap_aging_table').DataTable({
      pageLength:25,
      order:[[9,'desc']],
      dom:'Bfrtip',
      buttons:[
        {extend:'excelHtml5',text:'<i class="fa fa-file-excel-o"></i> Excel',className:'btn btn-success btn-sm',title:'AP Aging'},
        {extend:'print',text:'<i class="fa fa-print"></i> Print',className:'btn btn-default btn-sm',title:'AP Aging'}
      ],
      columnDefs:[
        {targets:[6,7,8,9],className:'text-right'},
        {targets:[11],orderable:false,searchable:false,className:'text-center'}
      ]
    });
  }
  function loadData(){
    if(apTable){apTable.destroy();apTable=null;}
    $('#ap_body').html('<tr><td colspan="12" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');
    $.post('<?=base_admin();?>modul/ap_aging/ap_aging_action.php?act=filter',params(),function(r){
      if(r.status==='success'){
        $('#ap_body').html(r.html);$('#kpi_total').text(r.total);$('#kpi_current').text(r.current);$('#kpi_overdue').text(r.overdue);$('#kpi_count').text(r.count);
        $('#kpi_d1').text(r.d1);$('#kpi_d31').text(r.d31);$('#kpi_d61').text(r.d61);$('#kpi_d91').text(r.d91);
        rebuildApTable();
      }else $('#ap_body').html('<tr><td colspan="12" class="text-danger text-center">'+r.message+'</td></tr>');
    },'json');
  }
  $('#btn_filter').on('click',loadData);
  $('#btn_reset').on('click',function(){$('#as_of_date').val('<?=date('Y-m-d');?>');$('#vendor_code,#bucket,#payment_status').val('').trigger('change');loadData();});
  $(document).on('click','.ap-detail',function(){
    $('#ap_detail_modal').modal('show');$('#ap_detail_body').html(<?=fin_js('common_loading', 'Loading...');?>);
    $.getJSON('<?=base_admin();?>modul/ap_aging/ap_aging_action.php?act=detail&id='+$(this).data('id'),function(r){$('#ap_detail_body').html(r.status==='success'?r.html:'<div class="alert alert-danger">'+r.message+'</div>');});
  });
  $('#btn_excel').on('click',function(){window.open('<?=base_admin();?>modul/ap_aging/ap_aging_action.php?act=excel&'+$.param(params()));});
  loadData();
});
</script>
