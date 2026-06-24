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
require_once "bank_payment_lib.php";
function bp_rows($query) {
    $rows = array();
    foreach ($query as $row) $rows[] = $row;
    return $rows;
}
$bankAccounts = bp_rows($db->query("SELECT r.no_rek,r.nama_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE c.no_rek IS NULL AND (r.no_rek LIKE '112%' OR r.nama_rek LIKE '%Bank%' OR r.nama_rek LIKE '%Giro%') ORDER BY r.no_rek"));
$offsetAccounts = bp_rows($db->query("SELECT r.no_rek,r.nama_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE c.no_rek IS NULL ORDER BY r.no_rek"));
$currencies = bp_rows($db->query("SELECT jenis_valas FROM matauang GROUP BY jenis_valas ORDER BY jenis_valas='IDR' DESC, jenis_valas"));
$costCenters = bp_rows($db->query("SELECT id,cost_center_code,cost_center_name FROM erp_cost_center WHERE status='Aktif' ORDER BY cost_center_code"));
$profitCenters = bp_rows($db->query("SELECT id,profit_center_code,profit_center_name FROM erp_profit_center WHERE status='Aktif' ORDER BY profit_center_code"));
$taxCodes = bp_rows($db->query("SELECT id,tax_code,tax_name,rate FROM erp_tax_code WHERE status='Aktif' ORDER BY tax_code"));
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.bp-kpi .description-block{margin:8px 0}.bp-kpi .description-header{font-size:20px}.bp-table th,.bp-table td{font-size:12px;vertical-align:middle!important}.bp-toolbar{margin-bottom:14px}.bp-help{background:#fffaf3;border-left:3px solid #f39c12;padding:10px 12px;margin-bottom:12px}
</style>

<section class="content-header">
  <h1><?=fin_h('finance_bank_payment', 'Bank Payment');?> <small>SAP FI Outgoing Bank</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=fin_h('common_home', 'Home');?></a></li><li>Akunting</li><li>Cash and Bank</li><li class="active"><?=fin_h('finance_bank_payment', 'Bank Payment');?></li></ol>
</section>

<section class="content">
  <div class="row bp-kpi">
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Total Payment</span><h5 class="description-header text-red" id="kpi_total">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text"><?=fin_h('finance_posted', 'Posted');?></span><h5 class="description-header text-blue" id="kpi_posted">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text"><?=fin_h('finance_draft', 'Draft');?></span><h5 class="description-header text-yellow" id="kpi_draft">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Documents</span><h5 class="description-header" id="kpi_count">0</h5></div></div></div></div>
  </div>

  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title">Outgoing Bank Payment Cockpit</h3>
      <div class="box-tools">
        <button class="btn btn-primary btn-sm" id="btn_add"><i class="fa fa-plus"></i> Create Bank Payment</button>
        <button class="btn btn-success btn-sm" id="btn_excel"><i class="fa fa-file-excel-o"></i> <?=fin_h('common_export_excel', 'Export Excel');?></button>
      </div>
    </div>
    <div class="box-body">
      <form id="bp_filter" class="form-horizontal bp-toolbar">
        <div class="form-group">
          <label class="control-label col-md-1">Tanggal</label>
          <div class="col-md-2"><div class="input-group date"><input id="start_date" class="form-control" value="<?=date('Y-m-01');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <div class="col-md-2"><div class="input-group date"><input id="end_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <label class="control-label col-md-1"><?=fin_h('common_status', 'Status');?></label>
          <div class="col-md-2"><select id="filter_status" class="form-control select2-filter"><option value="">All</option><option value="DRAFT"><?=fin_h('finance_draft', 'Draft');?></option><option value="POSTED"><?=fin_h('finance_posted', 'Posted');?></option><option value="REVERSED">Reversed</option></select></div>
          <label class="control-label col-md-1">Category</label>
          <div class="col-md-2"><select id="filter_category" class="form-control select2-filter"><option value="">All</option><option value="VENDOR"><?=fin_h('finance_vendor', 'Vendor');?></option><option value="EXPENSE">Expense</option><option value="TAX"><?=fin_h('finance_tax', 'Tax');?></option><option value="INTERCOMPANY">Intercompany</option><option value="OTHER">Other</option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-md-1"><?=fin_h('finance_bank', 'Bank');?></label>
          <div class="col-md-4"><select id="filter_bank_account" class="form-control select2-filter"><option value=""></option><?php foreach($bankAccounts as $r){ ?><option value="<?=bp_h($r->no_rek);?>"><?=bp_h($r->no_rek.' - '.$r->nama_rek);?></option><?php } ?></select></div>
          <div class="col-md-7"><button type="button" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> Tampilkan</button> <button type="button" id="btn_reset" class="btn btn-default"><i class="fa fa-refresh"></i> <?=fin_h('common_reset', 'Reset');?></button></div>
        </div>
      </form>
      <div class="bp-help"><i class="fa fa-info-circle"></i> Bank Payment digunakan untuk pembayaran keluar via bank. Posting otomatis membuat jurnal: <b>Dr Offset Account</b> dan <b>Cr Bank</b>. Koreksi dokumen posted dilakukan dengan reversal.</div>
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover bp-table" id="bank_payment_table">
          <thead><tr><th><?=fin_h('common_no', 'No');?></th><th><?=fin_h('finance_posting_date', 'Posting Date');?></th><th>Payment No</th><th>Category</th><th><?=fin_h('common_status', 'Status');?></th><th>Bank Account</th><th>Payee</th><th>Method</th><th>Bank Ref</th><th><?=fin_h('finance_amount', 'Amount');?></th><th>Curr</th><th><?=fin_h('common_action', 'Action');?></th></tr></thead>
          <tbody><tr><td colspan="12" class="text-center text-muted">Klik Tampilkan untuk memuat data.</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="bp_modal"><div class="modal-dialog modal-lg"><div class="modal-content"><form id="bp_form"><input type="hidden" name="id" id="bp_id"><div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Bank Payment Entry</h4></div><div class="modal-body">
  <div class="row">
    <div class="col-md-3"><label><?=fin_h('common_no', 'No');?></label><input name="bank_payment_no" id="bank_payment_no" class="form-control" readonly></div>
    <div class="col-md-3"><label>Category</label><select name="payment_category" id="payment_category" class="form-control select2-modal"><option value="VENDOR"><?=fin_h('finance_vendor_payment', 'Vendor Payment');?></option><option value="EXPENSE">Expense Payment</option><option value="TAX">Tax Payment</option><option value="INTERCOMPANY">Intercompany</option><option value="OTHER">Other Payment</option></select></div>
    <div class="col-md-2"><label><?=fin_h('finance_document_date', 'Document Date');?></label><div class="input-group date"><input name="document_date" id="document_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
    <div class="col-md-2"><label><?=fin_h('finance_posting_date', 'Posting Date');?></label><div class="input-group date"><input name="posting_date" id="posting_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
    <div class="col-md-2"><label>Value Date</label><div class="input-group date"><input name="value_date" id="value_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
  </div><br>
  <div class="row">
    <div class="col-md-6"><label>Bank Account</label><select name="bank_account" id="bank_account" class="form-control select2-modal" required><option value=""></option><?php foreach($bankAccounts as $r){ ?><option value="<?=bp_h($r->no_rek);?>"><?=bp_h($r->no_rek.' - '.$r->nama_rek);?></option><?php } ?></select></div>
    <div class="col-md-6"><label>Offset Account</label><select name="offset_account" id="offset_account" class="form-control select2-modal" required><option value=""></option><?php foreach($offsetAccounts as $r){ ?><option value="<?=bp_h($r->no_rek);?>"><?=bp_h($r->no_rek.' - '.$r->nama_rek);?></option><?php } ?></select></div>
  </div><br>
  <div class="row">
    <div class="col-md-4"><label>Payee / Penerima Dana</label><input name="payee_name" id="payee_name" class="form-control" required></div>
    <div class="col-md-3"><label>Payment Method</label><select name="payment_method" id="payment_method" class="form-control select2-modal"><option value="TRANSFER">Transfer</option><option value="GIRO">Giro</option><option value="CHEQUE">Cheque</option><option value="VIRTUAL_ACCOUNT">Virtual Account</option><option value="OTHER">Other</option></select></div>
    <div class="col-md-3"><label>Bank Reference</label><input name="bank_reference" id="bank_reference" class="form-control" placeholder="No transfer / giro"></div>
    <div class="col-md-2"><label>External Ref</label><input name="external_reference" id="external_reference" class="form-control"></div>
  </div><br>
  <div class="row">
    <div class="col-md-3"><label><?=fin_h('finance_amount', 'Amount');?></label><input name="amount" id="amount" type="number" step="0.01" min="0" class="form-control text-right" required></div>
    <div class="col-md-2"><label><?=fin_h('finance_currency', 'Currency');?></label><select name="currency" id="currency" class="form-control select2-modal"><?php foreach($currencies as $r){ ?><option value="<?=bp_h($r->jenis_valas);?>" <?=$r->jenis_valas==='IDR'?'selected':'';?>><?=bp_h($r->jenis_valas);?></option><?php } ?></select></div>
    <div class="col-md-2"><label>Kurs</label><input name="kurs" id="kurs" type="number" step="0.0001" class="form-control text-right" value="1"></div>
    <div class="col-md-5"><label><?=fin_h('finance_description', 'Description');?></label><input name="description" id="description" class="form-control" required></div>
  </div><br>
  <div class="row">
    <div class="col-md-4"><label>Cost Center</label><select name="cost_center_id" id="cost_center_id" class="form-control select2-modal"><option value=""></option><?php foreach($costCenters as $r){ ?><option value="<?=$r->id;?>"><?=bp_h($r->cost_center_code.' - '.$r->cost_center_name);?></option><?php } ?></select></div>
    <div class="col-md-4"><label>Profit Center</label><select name="profit_center_id" id="profit_center_id" class="form-control select2-modal"><option value=""></option><?php foreach($profitCenters as $r){ ?><option value="<?=$r->id;?>"><?=bp_h($r->profit_center_code.' - '.$r->profit_center_name);?></option><?php } ?></select></div>
    <div class="col-md-4"><label><?=fin_h('finance_tax_code', 'Tax Code');?></label><select name="tax_code_id" id="tax_code_id" class="form-control select2-modal"><option value=""></option><?php foreach($taxCodes as $r){ ?><option value="<?=$r->id;?>"><?=bp_h($r->tax_code.' - '.$r->tax_name.' '.$r->rate.'%');?></option><?php } ?></select></div>
  </div>
</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=fin_h('common_close', 'Close');?></button><button type="button" class="btn btn-warning bp-save" data-act="save"><i class="fa fa-save"></i> Save Draft</button><button type="button" class="btn btn-success bp-save" data-act="post"><i class="fa fa-check"></i> <?=fin_h('common_post', 'Post');?></button></div></form></div></div></div>

<div class="modal fade" id="bp_detail_modal"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Bank Payment Detail</h4></div><div class="modal-body" id="bp_detail_body"></div></div></div></div>

<script>
$(function(){
  $('.input-group.date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});
  $('.select2-filter').select2({width:'100%',allowClear:true,placeholder:'All'});
  var bankPaymentTable=null;
  function initModalSelect(){ $('.select2-modal').select2({width:'100%',dropdownParent:$('#bp_modal'),allowClear:true}); }
  function params(){return {start_date:$('#start_date').val(),end_date:$('#end_date').val(),status:$('#filter_status').val(),payment_category:$('#filter_category').val(),bank_account:$('#filter_bank_account').val()};}
  function rebuildBankPaymentTable(){ if(bankPaymentTable){bankPaymentTable.destroy();} bankPaymentTable=$('#bank_payment_table').DataTable({pageLength:25,order:[[1,'desc']],dom:'Bfrtip',buttons:[{extend:'excelHtml5',text:'<i class="fa fa-file-excel-o"></i> Excel',className:'btn btn-success btn-sm',title:'Bank Payment'},{extend:'print',text:'<i class="fa fa-print"></i> Print',className:'btn btn-default btn-sm',title:'Bank Payment'}],columnDefs:[{targets:[9],className:'text-right'},{targets:[11],orderable:false,searchable:false,className:'text-center'}]});}
  function loadData(){
    if(bankPaymentTable){bankPaymentTable.destroy();bankPaymentTable=null;}
    $('#bank_payment_table tbody').html('<tr><td colspan="12" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');
    $.post('<?=base_admin();?>modul/bank_payment/bank_payment_action.php?act=filter',params(),function(res){
      if(res.status==='success'){$('#bank_payment_table tbody').html(res.html);$('#kpi_total').text(res.total);$('#kpi_posted').text(res.posted);$('#kpi_draft').text(res.draft);$('#kpi_count').text(res.count);if(parseInt(res.count,10)>0)rebuildBankPaymentTable();}
      else{$('#bank_payment_table tbody').html('<tr><td colspan="12" class="text-danger text-center">'+res.message+'</td></tr>');}
    },'json');
  }
  $('#btn_filter').on('click',loadData);
  $('#btn_reset').on('click',function(){$('#bp_filter')[0].reset();$('.select2-filter').val('').trigger('change');$('#start_date').val('<?=date('Y-m-01');?>');$('#end_date').val('<?=date('Y-m-d');?>');loadData();});
  $('#btn_add').on('click',function(){ $('#bp_form')[0].reset(); $('#bp_id').val(''); $('#document_date,#posting_date,#value_date').val('<?=date('Y-m-d');?>'); $('#kurs').val('1'); $.getJSON('<?=base_admin();?>modul/bank_payment/bank_payment_action.php?act=get_no',function(res){$('#bank_payment_no').val(res.no);}); $('#bp_modal').modal('show'); initModalSelect(); });
  $(document).on('click','.bp-save',function(){ var act=$(this).data('act'); $.post('<?=base_admin();?>modul/bank_payment/bank_payment_action.php?act='+act,$('#bp_form').serialize(),function(res){ if(res.status==='success'){Swal.fire('Berhasil',res.message,'success');$('#bp_modal').modal('hide');loadData();} else Swal.fire('Gagal',res.message,'error');},'json'); });
  $(document).on('click','.bp-edit',function(){ $.getJSON('<?=base_admin();?>modul/bank_payment/bank_payment_action.php?act=get&id='+$(this).data('id'),function(res){ if(res.status!=='success'){Swal.fire('Gagal',res.message,'error');return;} var d=res.data; $('#bp_form')[0].reset(); $('#bp_id').val(d.id); $('#bank_payment_no').val(d.bank_payment_no); $('#payment_category').val(d.payment_category); $('#document_date').val(d.document_date); $('#posting_date').val(d.posting_date); $('#value_date').val(d.value_date); $('#bank_account').val(d.bank_account); $('#offset_account').val(d.offset_account); $('#payee_name').val(d.payee_name); $('#payment_method').val(d.payment_method); $('#bank_reference').val(d.bank_reference); $('#external_reference').val(d.external_reference); $('#amount').val(d.amount); $('#currency').val(d.currency); $('#kurs').val(d.kurs); $('#cost_center_id').val(d.cost_center_id); $('#profit_center_id').val(d.profit_center_id); $('#tax_code_id').val(d.tax_code_id); $('#description').val(d.description); $('#bp_modal').modal('show'); initModalSelect(); $('.select2-modal').trigger('change'); }); });
  $(document).on('click','.bp-detail',function(){ $('#bp_detail_modal').modal('show'); $('#bp_detail_body').html(<?=fin_js('common_loading', 'Loading...');?>); $.getJSON('<?=base_admin();?>modul/bank_payment/bank_payment_action.php?act=detail&id='+$(this).data('id'),function(res){$('#bp_detail_body').html(res.status==='success'?res.html:'<div class="alert alert-danger">'+res.message+'</div>');}); });
  $(document).on('click','.bp-post',function(){ var id=$(this).data('id'); Swal.fire({title:'Post bank payment?',icon:'question',showCancelButton:true}).then(function(r){if(r.isConfirmed){$.post('<?=base_admin();?>modul/bank_payment/bank_payment_action.php?act=post',{id:id},function(res){res.status==='success'?Swal.fire('Berhasil',res.message,'success'):Swal.fire('Gagal',res.message,'error');loadData();},'json');}}); });
  $(document).on('click','.bp-reverse',function(){ var id=$(this).data('id'); Swal.fire({title:'Reversal bank payment?',text:'Dokumen posted akan dikoreksi dengan jurnal reversal.',icon:'warning',showCancelButton:true}).then(function(r){if(r.isConfirmed){$.post('<?=base_admin();?>modul/bank_payment/bank_payment_action.php?act=reverse',{id:id},function(res){res.status==='success'?Swal.fire('Berhasil',res.message,'success'):Swal.fire('Gagal',res.message,'error');loadData();},'json');}}); });
  $('#btn_excel').on('click',function(){window.open('<?=base_admin();?>modul/bank_payment/bank_payment_action.php?act=excel&'+$.param(params()));});
  loadData();
});
</script>
