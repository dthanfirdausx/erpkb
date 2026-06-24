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
require_once "customer_invoice_lib.php";
function ci_rows($q){$r=array();foreach($q as $x)$r[]=$x;return $r;}
$customers=ci_rows($db->query("SELECT kode_pemasok,nama FROM (SELECT kode_penerima kode_pemasok,nama FROM penerima WHERE kode_penerima IS NOT NULL UNION SELECT kode_pemasok,nama FROM customer WHERE kode_pemasok IS NOT NULL) x ORDER BY nama"));
$arAccounts=ci_rows($db->query("SELECT r.no_rek,r.nama_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE c.no_rek IS NULL AND (r.no_rek LIKE '121%' OR r.nama_rek LIKE '%Piutang%') ORDER BY r.no_rek"));
$revenueAccounts=ci_rows($db->query("SELECT r.no_rek,r.nama_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE c.no_rek IS NULL AND (r.no_rek LIKE '4%' OR r.nama_rek LIKE '%Penjualan%') ORDER BY r.no_rek"));
$allAccounts=ci_rows($db->query("SELECT r.no_rek,r.nama_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE c.no_rek IS NULL ORDER BY r.no_rek"));
$currencies=ci_rows($db->query("SELECT jenis_valas FROM matauang GROUP BY jenis_valas ORDER BY jenis_valas='IDR' DESC, jenis_valas"));
?>
<link href="<?=base_admin();?>assets/plugins/select2/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="<?=base_admin();?>assets/plugins/datepicker/datepicker3.css">
<script src="<?=base_admin();?>assets/plugins/datepicker/bootstrap-datepicker.js"></script>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>.ci-table th,.ci-table td{font-size:12px;vertical-align:middle!important}.ci-kpi .description-header{font-size:20px}.ci-help{background:#f8fbff;border-left:3px solid #3c8dbc;padding:10px;margin-bottom:12px}.required:after{content:" *";color:#dd4b39}</style>

<section class="content-header">
  <h1><?=fin_h('finance_customer_invoice', 'Customer Invoice');?> <small>SAP FI-AR Billing</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=fin_h('common_home', 'Home');?></a></li><li>Akunting</li><li>Accounts Receivable</li><li class="active"><?=fin_h('finance_customer_invoice', 'Customer Invoice');?></li></ol>
</section>

<section class="content">
  <div class="row ci-kpi">
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span>Total Invoice</span><h5 id="kpi_total" class="description-header text-blue">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span>Open AR</span><h5 id="kpi_open" class="description-header text-yellow">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span><?=fin_h('finance_posted', 'Posted');?></span><h5 id="kpi_posted" class="description-header text-green">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span>Documents</span><h5 id="kpi_count" class="description-header">0</h5></div></div></div></div>
  </div>

  <div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Customer Invoice Cockpit</h3><div class="box-tools"><button id="btn_add" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Create Customer Invoice</button> <button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> <?=fin_h('common_export_excel', 'Export Excel');?></button></div></div>
    <div class="box-body">
      <form id="ci_filter" class="form-horizontal">
        <div class="form-group">
          <label class="control-label col-md-1">Tanggal</label>
          <div class="col-md-2"><div class="input-group date"><input id="start_date" class="form-control" value="<?=date('Y-m-01');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <div class="col-md-2"><div class="input-group date"><input id="end_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <label class="control-label col-md-1"><?=fin_h('common_status', 'Status');?></label>
          <div class="col-md-2"><select id="filter_status" class="form-control select2-filter"><option value="">All</option><option value="DRAFT"><?=fin_h('finance_draft', 'Draft');?></option><option value="POSTED"><?=fin_h('finance_posted', 'Posted');?></option><option value="REVERSED">Reversed</option><option value="CANCELLED"><?=fin_h('finance_cancelled', 'Cancelled');?></option></select></div>
          <label class="control-label col-md-1"><?=fin_h('finance_customer', 'Customer');?></label>
          <div class="col-md-3"><select id="filter_customer_code" class="form-control select2-filter"><option value=""></option><?php foreach($customers as $c){ ?><option value="<?=ci_h($c->kode_pemasok);?>"><?=ci_h($c->kode_pemasok.' - '.$c->nama);?></option><?php } ?></select></div>
        </div>
        <div class="form-group"><div class="col-md-12"><button type="button" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> Tampilkan</button> <button type="button" id="btn_reset" class="btn btn-default"><i class="fa fa-refresh"></i> <?=fin_h('common_reset', 'Reset');?></button></div></div>
      </form>
      <div class="ci-help"><i class="fa fa-info-circle"></i> Posting Customer Invoice otomatis membuat jurnal <b>Dr AR Customer</b>, <b>Cr Revenue</b>, dan <b>Cr Output VAT</b> bila ada. Tax Invoice Out juga dibuat otomatis saat posting.</div>
      <div class="table-responsive"><table id="customer_invoice_table" class="table table-bordered table-striped table-hover ci-table"><thead><tr><th><?=fin_h('common_no', 'No');?></th><th>Posting</th><th>Invoice No</th><th><?=fin_h('finance_customer', 'Customer');?></th><th><?=fin_h('common_status', 'Status');?></th><th><?=fin_h('finance_reference', 'Reference');?></th><th>Net</th><th><?=fin_h('finance_tax', 'Tax');?></th><th>Gross</th><th>Outstanding</th><th>Curr</th><th><?=fin_h('common_action', 'Action');?></th></tr></thead><tbody id="ci_body"></tbody></table></div>
    </div>
  </div>
</section>

<div class="modal fade" id="ci_modal"><div class="modal-dialog modal-lg"><div class="modal-content"><form id="ci_form"><input type="hidden" name="id_sales" id="id_sales"><div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4>Customer Invoice Entry</h4></div><div class="modal-body">
  <div class="row">
    <div class="col-md-3"><label><?=fin_h('common_no', 'No');?></label><input name="no_sales_invoice" id="no_sales_invoice" class="form-control" readonly></div>
    <div class="col-md-5"><label class="required">Bill-to Customer</label><select name="bill_to" id="bill_to" class="form-control select2-modal" required><option value=""></option><?php foreach($customers as $c){ ?><option value="<?=ci_h($c->kode_pemasok);?>"><?=ci_h($c->kode_pemasok.' - '.$c->nama);?></option><?php } ?></select></div>
    <div class="col-md-4"><label>Ship-to Customer</label><select name="ship_to" id="ship_to" class="form-control select2-modal"><option value=""></option><?php foreach($customers as $c){ ?><option value="<?=ci_h($c->kode_pemasok);?>"><?=ci_h($c->kode_pemasok.' - '.$c->nama);?></option><?php } ?></select></div>
  </div><br>
  <div class="row">
    <div class="col-md-3"><label>Invoice Date</label><div class="input-group date"><input name="invoice_date" id="invoice_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
    <div class="col-md-3"><label><?=fin_h('finance_posting_date', 'Posting Date');?></label><div class="input-group date"><input name="posting_date" id="posting_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
    <div class="col-md-3"><label><?=fin_h('finance_due_date', 'Due Date');?></label><div class="input-group date"><input name="due_date" id="due_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
    <div class="col-md-3"><label>Payment Term</label><input name="term" id="term" class="form-control" placeholder="NET 30"></div>
  </div><br>
  <div class="row">
    <div class="col-md-3"><label>Reference Type</label><select name="reference_type" id="reference_type" class="form-control select2-modal"><option value="MANUAL">Manual</option><option value="SO">Sales Order</option><option value="DELIVERY">Delivery</option></select></div>
    <div class="col-md-3"><label>Reference No</label><input name="reference_no" id="reference_no" class="form-control"></div>
    <div class="col-md-3"><label>Sales Order</label><input name="no_sales_order" id="no_sales_order" class="form-control"></div>
    <div class="col-md-3"><label>Customer PO</label><input name="nopo" id="nopo" class="form-control"></div>
  </div><br>
  <div class="row">
    <div class="col-md-4"><label class="required">AR Account</label><select name="ar_account" id="ar_account" class="form-control select2-modal" required><option value=""></option><?php foreach($arAccounts as $a){ ?><option value="<?=ci_h($a->no_rek);?>"><?=ci_h($a->no_rek.' - '.$a->nama_rek);?></option><?php } ?></select></div>
    <div class="col-md-4"><label class="required">Revenue Account</label><select name="revenue_account" id="revenue_account" class="form-control select2-modal" required><option value=""></option><?php foreach($revenueAccounts as $a){ ?><option value="<?=ci_h($a->no_rek);?>"><?=ci_h($a->no_rek.' - '.$a->nama_rek);?></option><?php } ?></select></div>
    <div class="col-md-4"><label>Tax Account</label><select name="tax_account" id="tax_account" class="form-control select2-modal"><option value=""></option><?php foreach($allAccounts as $a){ ?><option value="<?=ci_h($a->no_rek);?>"><?=ci_h($a->no_rek.' - '.$a->nama_rek);?></option><?php } ?></select></div>
  </div><br>
  <div class="row">
    <div class="col-md-3"><label>Item Code</label><input name="item_code" id="item_code" class="form-control"></div>
    <div class="col-md-5"><label class="required">Item Description</label><input name="item_description" id="item_description" class="form-control" required></div>
    <div class="col-md-2"><label>Qty</label><input name="qty" id="qty" type="number" step="0.0001" class="form-control text-right" value="1"></div>
    <div class="col-md-2"><label>Unit</label><input name="unit" id="unit" class="form-control" value="EA"></div>
  </div><br>
  <div class="row">
    <div class="col-md-3"><label class="required">Net Amount</label><input name="net_amount" id="net_amount" type="number" step="0.01" class="form-control text-right" required></div>
    <div class="col-md-2"><label>Tax Rate %</label><input name="tax_rate" id="tax_rate" type="number" step="0.0001" class="form-control text-right" value="11"></div>
    <div class="col-md-2"><label><?=fin_h('finance_tax_code', 'Tax Code');?></label><input name="tax_code" id="tax_code" class="form-control" value="PPN-OUT"></div>
    <div class="col-md-2"><label>Tax Amount</label><input name="tax_amount" id="tax_amount" type="number" step="0.01" class="form-control text-right" value="0"></div>
    <div class="col-md-3"><label class="required">Gross Amount</label><input name="gross_amount" id="gross_amount" type="number" step="0.01" class="form-control text-right" readonly></div>
  </div><br>
  <div class="row">
    <div class="col-md-3"><label><?=fin_h('finance_currency', 'Currency');?></label><select name="valuta" id="valuta" class="form-control select2-modal"><?php foreach($currencies as $c){ ?><option value="<?=ci_h($c->jenis_valas);?>" <?=$c->jenis_valas==='IDR'?'selected':'';?>><?=ci_h($c->jenis_valas);?></option><?php } ?></select></div>
    <div class="col-md-9"><label><?=fin_h('finance_description', 'Description');?></label><input name="catatan" id="catatan" class="form-control" required></div>
  </div>
</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=fin_h('common_close', 'Close');?></button><button type="button" class="btn btn-warning ci-save" data-act="save">Save Draft</button><button type="button" class="btn btn-success ci-save" data-act="post"><?=fin_h('common_post', 'Post');?></button></div></form></div></div></div>

<div class="modal fade" id="ci_detail_modal"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4>Customer Invoice Detail</h4></div><div class="modal-body" id="ci_detail_body"></div></div></div></div>

<script>
$(function(){
  if($.fn.datepicker){$('.input-group.date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});}
  if($.fn.select2){$('.select2-filter').select2({width:'100%',allowClear:true,placeholder:'All'});}
  var customerInvoiceTable=null;
  function initSel(){ if($.fn.select2){$('.select2-modal').select2({width:'100%',dropdownParent:$('#ci_modal'),allowClear:true});} }
  function params(){return{start_date:$('#start_date').val(),end_date:$('#end_date').val(),status:$('#filter_status').val(),customer_code:$('#filter_customer_code').val()};}
  function calcGross(){var net=parseFloat($('#net_amount').val()||0),rate=parseFloat($('#tax_rate').val()||0),tax=parseFloat($('#tax_amount').val()||0);if(rate>0){tax=net*rate/100;$('#tax_amount').val(tax.toFixed(2));}$('#gross_amount').val((net+tax).toFixed(2));}
  function rebuildCustomerInvoiceTable(){ if(customerInvoiceTable){customerInvoiceTable.destroy();} customerInvoiceTable=$('#customer_invoice_table').DataTable({pageLength:25,order:[[1,'desc']],dom:'Bfrtip',buttons:[{extend:'excelHtml5',text:'<i class="fa fa-file-excel-o"></i> Excel',className:'btn btn-success btn-sm',title:'Customer Invoice'},{extend:'print',text:'<i class="fa fa-print"></i> Print',className:'btn btn-default btn-sm',title:'Customer Invoice'}],columnDefs:[{targets:[6,7,8,9],className:'text-right'},{targets:[11],orderable:false,searchable:false,className:'text-center'}]});}
  function loadData(){ if(customerInvoiceTable){customerInvoiceTable.destroy();customerInvoiceTable=null;} $('#ci_body').html('<tr><td colspan="12" class="text-center">Loading...</td></tr>'); $.post('<?=base_admin();?>modul/customer_invoice/customer_invoice_action.php?act=filter',params(),function(r){ if(r.status==='success'){$('#ci_body').html(r.html);$('#kpi_total').text(r.total);$('#kpi_open').text(r.open);$('#kpi_posted').text(r.posted);$('#kpi_count').text(r.count);rebuildCustomerInvoiceTable();}else{$('#ci_body').html('<tr><td colspan="12" class="text-danger text-center">'+r.message+'</td></tr>');}},'json');}
  $('#btn_filter').on('click',loadData);
  $('#btn_reset').on('click',function(){$('#ci_filter')[0].reset();$('.select2-filter').val('').trigger('change');$('#start_date').val('<?=date('Y-m-01');?>');$('#end_date').val('<?=date('Y-m-d');?>');loadData();});
  $('#btn_add').on('click',function(){ $('#ci_form')[0].reset(); $('#id_sales').val(''); $('#invoice_date,#posting_date,#due_date').val('<?=date('Y-m-d');?>'); $('#qty').val('1'); $('#unit').val('EA'); $('#tax_rate').val('11'); $('#tax_code').val('PPN-OUT'); $('#tax_amount,#gross_amount').val('0'); $.getJSON('<?=base_admin();?>modul/customer_invoice/customer_invoice_action.php?act=get_no',function(r){$('#no_sales_invoice').val(r.no);}); $('#ci_modal').modal('show'); initSel(); });
  $('#net_amount,#tax_rate,#tax_amount').on('keyup change',calcGross);
  $(document).on('click','.ci-save',function(){var act=$(this).data('act');calcGross();$.post('<?=base_admin();?>modul/customer_invoice/customer_invoice_action.php?act='+act,$('#ci_form').serialize(),function(r){if(r.status==='success'){Swal.fire('Berhasil',r.message,'success');$('#ci_modal').modal('hide');loadData();}else Swal.fire('Gagal',r.message,'error');},'json');});
  $(document).on('click','.ci-edit',function(){$.getJSON('<?=base_admin();?>modul/customer_invoice/customer_invoice_action.php?act=get&id='+$(this).data('id'),function(r){if(r.status!=='success'){Swal.fire('Gagal',r.message,'error');return;}var d=r.data;$('#ci_form')[0].reset();$.each(d,function(k,v){$('#'+k).val(v);});$('#id_sales').val(d.id_sales);$('#ci_modal').modal('show');initSel();$('.select2-modal').trigger('change');calcGross();});});
  $(document).on('click','.ci-detail',function(){$('#ci_detail_modal').modal('show');$('#ci_detail_body').html(<?=fin_js('common_loading', 'Loading...');?>);$.getJSON('<?=base_admin();?>modul/customer_invoice/customer_invoice_action.php?act=detail&id='+$(this).data('id'),function(r){$('#ci_detail_body').html(r.status==='success'?r.html:'<div class="alert alert-danger">'+r.message+'</div>');});});
  $(document).on('click','.ci-post',function(){var id=$(this).data('id');Swal.fire({title:'Post customer invoice?',icon:'question',showCancelButton:true}).then(function(x){if(x.isConfirmed){$.post('<?=base_admin();?>modul/customer_invoice/customer_invoice_action.php?act=post',{id_sales:id},function(r){r.status==='success'?Swal.fire('Berhasil',r.message,'success'):Swal.fire('Gagal',r.message,'error');loadData();},'json');}});});
  $(document).on('click','.ci-reverse',function(){var id=$(this).data('id');Swal.fire({title:'Reversal customer invoice?',text:'Invoice posted akan dikoreksi dengan jurnal reversal.',icon:'warning',showCancelButton:true}).then(function(x){if(x.isConfirmed){$.post('<?=base_admin();?>modul/customer_invoice/customer_invoice_action.php?act=reverse',{id:id},function(r){r.status==='success'?Swal.fire('Berhasil',r.message,'success'):Swal.fire('Gagal',r.message,'error');loadData();},'json');}});});
  $('#btn_excel').on('click',function(){window.open('<?=base_admin();?>modul/customer_invoice/customer_invoice_action.php?act=excel&'+$.param(params()));});
  loadData();
});
</script>
