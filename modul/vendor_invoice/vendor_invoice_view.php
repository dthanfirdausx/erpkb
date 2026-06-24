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
require_once "vendor_invoice_lib.php";
function vi_rows($query){$rows=array();foreach($query as $row)$rows[]=$row;return $rows;}
$vendors=vi_rows($db->query("SELECT kode_pemasok,nama FROM pemasok ORDER BY nama"));
$accounts=vi_rows($db->query("SELECT r.no_rek,r.nama_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE c.no_rek IS NULL ORDER BY r.no_rek"));
$apAccounts=vi_rows($db->query("SELECT r.no_rek,r.nama_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE c.no_rek IS NULL AND (r.no_rek LIKE '211%' OR r.nama_rek LIKE '%Hutang%' OR r.nama_rek LIKE '%A/P%') ORDER BY r.no_rek"));
$currencies=vi_rows($db->query("SELECT jenis_valas FROM matauang GROUP BY jenis_valas ORDER BY jenis_valas='IDR' DESC, jenis_valas"));
$costCenters=vi_rows($db->query("SELECT id,cost_center_code,cost_center_name FROM erp_cost_center WHERE status='Aktif' ORDER BY cost_center_code"));
$profitCenters=vi_rows($db->query("SELECT id,profit_center_code,profit_center_name FROM erp_profit_center WHERE status='Aktif' ORDER BY profit_center_code"));
$taxCodes=vi_rows($db->query("SELECT id,tax_code,tax_name,rate FROM erp_tax_code WHERE status='Aktif' ORDER BY tax_code"));
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.vi-kpi .description-block{margin:8px 0}.vi-kpi .description-header{font-size:20px}.vi-table th,.vi-table td{font-size:12px;vertical-align:middle!important}.vi-toolbar{margin-bottom:14px}.vi-help{background:#f8fbff;border-left:3px solid #3c8dbc;padding:10px 12px;margin-bottom:12px}.required:after{content:" *";color:#dd4b39}
</style>

<section class="content-header">
  <h1><?=fin_h('finance_vendor_invoice', 'Vendor Invoice');?> <small>SAP FI-AP Invoice</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=fin_h('common_home', 'Home');?></a></li><li>Akunting</li><li>Accounts Payable</li><li class="active"><?=fin_h('finance_vendor_invoice', 'Vendor Invoice');?></li></ol>
</section>

<section class="content">
  <div class="row vi-kpi">
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Total Invoice</span><h5 class="description-header text-blue" id="kpi_total">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Open AP</span><h5 class="description-header text-yellow" id="kpi_open">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text"><?=fin_h('finance_posted', 'Posted');?></span><h5 class="description-header text-green" id="kpi_posted">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Documents</span><h5 class="description-header" id="kpi_count">0</h5></div></div></div></div>
  </div>

  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title">Vendor Invoice Cockpit</h3>
      <div class="box-tools">
        <button class="btn btn-primary btn-sm" id="btn_add"><i class="fa fa-plus"></i> Create Vendor Invoice</button>
        <button class="btn btn-success btn-sm" id="btn_excel"><i class="fa fa-file-excel-o"></i> <?=fin_h('common_export_excel', 'Export Excel');?></button>
      </div>
    </div>
    <div class="box-body">
      <form id="vi_filter" class="form-horizontal vi-toolbar">
        <div class="form-group">
          <label class="control-label col-md-1">Tanggal</label>
          <div class="col-md-2"><div class="input-group date"><input id="start_date" class="form-control" value="<?=date('Y-m-01');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <div class="col-md-2"><div class="input-group date"><input id="end_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <label class="control-label col-md-1"><?=fin_h('common_status', 'Status');?></label>
          <div class="col-md-2"><select id="filter_status" class="form-control select2-filter"><option value="">All</option><option value="DRAFT"><?=fin_h('finance_draft', 'Draft');?></option><option value="POSTED"><?=fin_h('finance_posted', 'Posted');?></option><option value="REVERSED">Reversed</option></select></div>
          <label class="control-label col-md-1"><?=fin_h('finance_payment', 'Payment');?></label>
          <div class="col-md-2"><select id="filter_payment_status" class="form-control select2-filter"><option value="">All</option><option value="OPEN">Open</option><option value="PARTIAL">Partial</option><option value="PAID">Paid</option><option value="CANCELLED"><?=fin_h('finance_cancelled', 'Cancelled');?></option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-md-1"><?=fin_h('finance_vendor', 'Vendor');?></label>
          <div class="col-md-4"><select id="filter_vendor_code" class="form-control select2-filter"><option value=""></option><?php foreach($vendors as $v){ ?><option value="<?=vi_h($v->kode_pemasok);?>"><?=vi_h($v->kode_pemasok.' - '.$v->nama);?></option><?php } ?></select></div>
          <div class="col-md-7"><button type="button" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> Tampilkan</button> <button type="button" id="btn_reset" class="btn btn-default"><i class="fa fa-refresh"></i> <?=fin_h('common_reset', 'Reset');?></button></div>
        </div>
      </form>
      <div class="vi-help"><i class="fa fa-info-circle"></i> Posting Vendor Invoice otomatis membuat jurnal <b>Dr Expense/Clearing</b>, <b>Dr Tax Input</b> bila ada, dan <b>Cr AP Vendor</b>. Setelah posted, koreksi dilakukan dengan reversal.</div>
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover vi-table" id="vendor_invoice_table">
          <thead><tr><th><?=fin_h('common_no', 'No');?></th><th>Posting</th><th>Invoice No</th><th>Vendor Ref</th><th><?=fin_h('finance_vendor', 'Vendor');?></th><th><?=fin_h('common_status', 'Status');?></th><th><?=fin_h('finance_payment', 'Payment');?></th><th>PO</th><th>Net</th><th><?=fin_h('finance_tax', 'Tax');?></th><th>Gross</th><th>Curr</th><th><?=fin_h('common_action', 'Action');?></th></tr></thead>
          <tbody><tr><td colspan="13" class="text-center text-muted">Loading...</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="vi_modal"><div class="modal-dialog modal-lg"><div class="modal-content"><form id="vi_form"><input type="hidden" name="id" id="vi_id"><div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Vendor Invoice Entry</h4></div><div class="modal-body">
  <div class="row">
    <div class="col-md-3"><label><?=fin_h('common_no', 'No');?></label><input name="vendor_invoice_no" id="vendor_invoice_no" class="form-control" readonly></div>
    <div class="col-md-4"><label class="required"><?=fin_h('finance_vendor', 'Vendor');?></label><select name="vendor_code" id="vendor_code" class="form-control select2-modal" required><option value=""></option><?php foreach($vendors as $v){ ?><option value="<?=vi_h($v->kode_pemasok);?>"><?=vi_h($v->kode_pemasok.' - '.$v->nama);?></option><?php } ?></select></div>
    <div class="col-md-3"><label class="required">Vendor Invoice Ref</label><input name="vendor_reference_no" id="vendor_reference_no" class="form-control" required></div>
    <div class="col-md-2"><label>Type</label><select name="invoice_type" id="invoice_type" class="form-control select2-modal"><option value="STANDARD">Standard</option><option value="DOWN_PAYMENT">Down Payment</option><option value="CREDIT_MEMO">Credit Memo</option><option value="DEBIT_MEMO">Debit Memo</option><option value="OTHER">Other</option></select></div>
  </div><br>
  <div class="row">
    <div class="col-md-3"><label><?=fin_h('finance_document_date', 'Document Date');?></label><div class="input-group date"><input name="document_date" id="document_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
    <div class="col-md-3"><label><?=fin_h('finance_posting_date', 'Posting Date');?></label><div class="input-group date"><input name="posting_date" id="posting_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
    <div class="col-md-3"><label><?=fin_h('finance_due_date', 'Due Date');?></label><div class="input-group date"><input name="due_date" id="due_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
    <div class="col-md-3"><label>Payment Term</label><input name="payment_term" id="payment_term" class="form-control" placeholder="NET 30"></div>
  </div><br>
  <div class="row">
    <div class="col-md-3"><label>Reference PO</label><input name="reference_po" id="reference_po" class="form-control"></div>
    <div class="col-md-3"><label>Reference GR</label><input name="reference_gr" id="reference_gr" class="form-control"></div>
    <div class="col-md-3"><label><?=fin_h('finance_currency', 'Currency');?></label><select name="currency" id="currency" class="form-control select2-modal"><?php foreach($currencies as $r){ ?><option value="<?=vi_h($r->jenis_valas);?>" <?=$r->jenis_valas==='IDR'?'selected':'';?>><?=vi_h($r->jenis_valas);?></option><?php } ?></select></div>
    <div class="col-md-3"><label>Kurs</label><input name="kurs" id="kurs" type="number" step="0.0001" class="form-control text-right" value="1"></div>
  </div><br>
  <div class="row">
    <div class="col-md-4"><label class="required">Expense / GRIR / Clearing Account</label><select name="expense_account" id="expense_account" class="form-control select2-modal" required><option value=""></option><?php foreach($accounts as $a){ ?><option value="<?=vi_h($a->no_rek);?>"><?=vi_h($a->no_rek.' - '.$a->nama_rek);?></option><?php } ?></select></div>
    <div class="col-md-4"><label class="required">AP Account</label><select name="ap_account" id="ap_account" class="form-control select2-modal" required><option value=""></option><?php foreach($apAccounts as $a){ ?><option value="<?=vi_h($a->no_rek);?>"><?=vi_h($a->no_rek.' - '.$a->nama_rek);?></option><?php } ?></select></div>
    <div class="col-md-4"><label>Tax Account</label><select name="tax_account" id="tax_account" class="form-control select2-modal"><option value=""></option><?php foreach($accounts as $a){ ?><option value="<?=vi_h($a->no_rek);?>"><?=vi_h($a->no_rek.' - '.$a->nama_rek);?></option><?php } ?></select></div>
  </div><br>
  <div class="row">
    <div class="col-md-3"><label class="required">Net Amount</label><input name="net_amount" id="net_amount" type="number" step="0.01" min="0" class="form-control text-right" required></div>
    <div class="col-md-3"><label><?=fin_h('finance_tax_code', 'Tax Code');?></label><select name="tax_code_id" id="tax_code_id" class="form-control select2-modal"><option value=""></option><?php foreach($taxCodes as $t){ ?><option value="<?=$t->id;?>" data-rate="<?=vi_h($t->rate);?>"><?=vi_h($t->tax_code.' - '.$t->tax_name.' '.$t->rate.'%');?></option><?php } ?></select></div>
    <div class="col-md-3"><label>Tax Amount</label><input name="tax_amount" id="tax_amount" type="number" step="0.01" min="0" class="form-control text-right" value="0"></div>
    <div class="col-md-3"><label class="required">Gross Amount</label><input name="gross_amount" id="gross_amount" type="number" step="0.01" min="0" class="form-control text-right" readonly></div>
  </div><br>
  <div class="row">
    <div class="col-md-4"><label>Cost Center</label><select name="cost_center_id" id="cost_center_id" class="form-control select2-modal"><option value=""></option><?php foreach($costCenters as $r){ ?><option value="<?=$r->id;?>"><?=vi_h($r->cost_center_code.' - '.$r->cost_center_name);?></option><?php } ?></select></div>
    <div class="col-md-4"><label>Profit Center</label><select name="profit_center_id" id="profit_center_id" class="form-control select2-modal"><option value=""></option><?php foreach($profitCenters as $r){ ?><option value="<?=$r->id;?>"><?=vi_h($r->profit_center_code.' - '.$r->profit_center_name);?></option><?php } ?></select></div>
    <div class="col-md-4"><label><?=fin_h('finance_description', 'Description');?></label><input name="description" id="description" class="form-control" required></div>
  </div>
</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=fin_h('common_close', 'Close');?></button><button type="button" class="btn btn-warning vi-save" data-act="save"><i class="fa fa-save"></i> Save Draft</button><button type="button" class="btn btn-success vi-save" data-act="post"><i class="fa fa-check"></i> <?=fin_h('common_post', 'Post');?></button></div></form></div></div></div>

<div class="modal fade" id="vi_detail_modal"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Vendor Invoice Detail</h4></div><div class="modal-body" id="vi_detail_body"></div></div></div></div>

<script>
$(function(){
  $('.input-group.date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});
  $('.select2-filter').select2({width:'100%',allowClear:true,placeholder:'All'});
  var vendorInvoiceTable=null;
  function initModalSelect(){ $('.select2-modal').select2({width:'100%',dropdownParent:$('#vi_modal'),allowClear:true}); }
  function params(){return {start_date:$('#start_date').val(),end_date:$('#end_date').val(),status:$('#filter_status').val(),payment_status:$('#filter_payment_status').val(),vendor_code:$('#filter_vendor_code').val()};}
  function calcGross(){var net=parseFloat($('#net_amount').val()||0),tax=parseFloat($('#tax_amount').val()||0);$('#gross_amount').val((net+tax).toFixed(2));}
  function rebuildVendorInvoiceTable(){
    if(vendorInvoiceTable){vendorInvoiceTable.destroy();}
    vendorInvoiceTable=$('#vendor_invoice_table').DataTable({
      pageLength:25,
      order:[[1,'desc']],
      dom:'Bfrtip',
      buttons:[
        {extend:'excelHtml5',text:'<i class="fa fa-file-excel-o"></i> Excel',className:'btn btn-success btn-sm',title:'Vendor Invoice'},
        {extend:'print',text:'<i class="fa fa-print"></i> Print',className:'btn btn-default btn-sm',title:'Vendor Invoice'}
      ],
      columnDefs:[
        {targets:[8,9,10],className:'text-right'},
        {targets:[12],orderable:false,searchable:false,className:'text-center'}
      ]
    });
  }
  function loadData(){
    if(vendorInvoiceTable){vendorInvoiceTable.destroy();vendorInvoiceTable=null;}
    $('#vendor_invoice_table tbody').html('<tr><td colspan="13" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');
    $.post('<?=base_admin();?>modul/vendor_invoice/vendor_invoice_action.php?act=filter',params(),function(res){
      if(res.status==='success'){$('#vendor_invoice_table tbody').html(res.html);$('#kpi_total').text(res.total);$('#kpi_open').text(res.open);$('#kpi_posted').text(res.posted);$('#kpi_count').text(res.count);rebuildVendorInvoiceTable();}
      else{$('#vendor_invoice_table tbody').html('<tr><td colspan="13" class="text-danger text-center">'+res.message+'</td></tr>');}
    },'json');
  }
  $('#btn_filter').on('click',loadData);
  $('#btn_reset').on('click',function(){$('#vi_filter')[0].reset();$('.select2-filter').val('').trigger('change');$('#start_date').val('<?=date('Y-m-01');?>');$('#end_date').val('<?=date('Y-m-d');?>');loadData();});
  $('#btn_add').on('click',function(){ $('#vi_form')[0].reset(); $('#vi_id').val(''); $('#document_date,#posting_date,#due_date').val('<?=date('Y-m-d');?>'); $('#tax_amount').val('0'); $('#kurs').val('1'); calcGross(); $.getJSON('<?=base_admin();?>modul/vendor_invoice/vendor_invoice_action.php?act=get_no',function(res){$('#vendor_invoice_no').val(res.no);}); $('#vi_modal').modal('show'); initModalSelect(); });
  $('#net_amount,#tax_amount').on('keyup change',calcGross);
  $('#tax_code_id').on('change',function(){var rate=parseFloat($(this).find(':selected').data('rate')||0),net=parseFloat($('#net_amount').val()||0); if(rate>0){$('#tax_amount').val((net*rate/100).toFixed(2)); calcGross();}});
  $(document).on('click','.vi-save',function(){var act=$(this).data('act');calcGross();$.post('<?=base_admin();?>modul/vendor_invoice/vendor_invoice_action.php?act='+act,$('#vi_form').serialize(),function(res){if(res.status==='success'){Swal.fire('Berhasil',res.message,'success');$('#vi_modal').modal('hide');loadData();}else Swal.fire('Gagal',res.message,'error');},'json');});
  $(document).on('click','.vi-edit',function(){ $.getJSON('<?=base_admin();?>modul/vendor_invoice/vendor_invoice_action.php?act=get&id='+$(this).data('id'),function(res){ if(res.status!=='success'){Swal.fire('Gagal',res.message,'error');return;} var d=res.data; $('#vi_form')[0].reset(); $.each(d,function(k,v){$('#'+k).val(v);}); $('#vi_id').val(d.id); $('#vi_modal').modal('show'); initModalSelect(); $('.select2-modal').trigger('change'); calcGross(); }); });
  $(document).on('click','.vi-detail',function(){ $('#vi_detail_modal').modal('show'); $('#vi_detail_body').html(<?=fin_js('common_loading', 'Loading...');?>); $.getJSON('<?=base_admin();?>modul/vendor_invoice/vendor_invoice_action.php?act=detail&id='+$(this).data('id'),function(res){$('#vi_detail_body').html(res.status==='success'?res.html:'<div class="alert alert-danger">'+res.message+'</div>');}); });
  $(document).on('click','.vi-post',function(){var id=$(this).data('id');Swal.fire({title:'Post vendor invoice?',icon:'question',showCancelButton:true}).then(function(r){if(r.isConfirmed){$.post('<?=base_admin();?>modul/vendor_invoice/vendor_invoice_action.php?act=post',{id:id},function(res){res.status==='success'?Swal.fire('Berhasil',res.message,'success'):Swal.fire('Gagal',res.message,'error');loadData();},'json');}});});
  $(document).on('click','.vi-reverse',function(){var id=$(this).data('id');Swal.fire({title:'Reversal vendor invoice?',text:'Invoice posted akan dikoreksi dengan jurnal reversal.',icon:'warning',showCancelButton:true}).then(function(r){if(r.isConfirmed){$.post('<?=base_admin();?>modul/vendor_invoice/vendor_invoice_action.php?act=reverse',{id:id},function(res){res.status==='success'?Swal.fire('Berhasil',res.message,'success'):Swal.fire('Gagal',res.message,'error');loadData();},'json');}});});
  $('#btn_excel').on('click',function(){window.open('<?=base_admin();?>modul/vendor_invoice/vendor_invoice_action.php?act=excel&'+$.param(params()));});
  loadData();
});
</script>
