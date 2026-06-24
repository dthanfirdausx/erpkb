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
require_once "cash_journal_lib.php";
function cj_rows($query) {
    $rows = array();
    foreach ($query as $row) $rows[] = $row;
    return $rows;
}
$cashAccounts = cj_rows($db->query("SELECT r.no_rek,r.nama_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE c.no_rek IS NULL AND (r.no_rek LIKE '110%' OR r.nama_rek LIKE '%Kas%' OR r.nama_rek LIKE '%Cash%') ORDER BY r.no_rek"));
$offsetAccounts = cj_rows($db->query("SELECT r.no_rek,r.nama_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE c.no_rek IS NULL ORDER BY r.no_rek"));
$currencies = cj_rows($db->query("SELECT jenis_valas FROM matauang GROUP BY jenis_valas ORDER BY jenis_valas='IDR' DESC, jenis_valas"));
$costCenters = cj_rows($db->query("SELECT id,cost_center_code,cost_center_name FROM erp_cost_center WHERE status='Aktif' ORDER BY cost_center_code"));
$profitCenters = cj_rows($db->query("SELECT id,profit_center_code,profit_center_name FROM erp_profit_center WHERE status='Aktif' ORDER BY profit_center_code"));
$taxCodes = cj_rows($db->query("SELECT id,tax_code,tax_name,rate FROM erp_tax_code WHERE status='Aktif' ORDER BY tax_code"));
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.cj-kpi .description-block{margin:8px 0}.cj-kpi .description-header{font-size:20px}.cj-table th,.cj-table td{font-size:12px;vertical-align:middle!important}.cj-toolbar{margin-bottom:14px}
</style>

<section class="content-header">
  <h1><?=fin_h('finance_cash_journal', 'Cash Journal');?> <small>SAP FI Cash Journal</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=fin_h('common_home', 'Home');?></a></li><li>Akunting</li><li class="active"><?=fin_h('finance_cash_journal', 'Cash Journal');?></li></ol>
</section>

<section class="content">
  <div class="row cj-kpi">
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Receipt</span><h5 class="description-header text-green" id="kpi_receipt">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text"><?=fin_h('finance_payment', 'Payment');?></span><h5 class="description-header text-red" id="kpi_payment">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Net Movement</span><h5 class="description-header text-blue" id="kpi_balance">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Documents</span><h5 class="description-header" id="kpi_count">0</h5></div></div></div></div>
  </div>

  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title">Cash Journal Cockpit</h3>
      <div class="box-tools">
        <button class="btn btn-primary btn-sm" id="btn_add"><i class="fa fa-plus"></i> Create Cash Journal</button>
        <button class="btn btn-success btn-sm" id="btn_excel"><i class="fa fa-file-excel-o"></i> <?=fin_h('common_export_excel', 'Export Excel');?></button>
      </div>
    </div>
    <div class="box-body">
      <form id="cj_filter" class="form-horizontal cj-toolbar">
        <div class="form-group">
          <label class="control-label col-md-1">Tanggal</label>
          <div class="col-md-2"><div class="input-group date"><input id="start_date" class="form-control" value="<?=date('Y-m-01');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <div class="col-md-2"><div class="input-group date"><input id="end_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <label class="control-label col-md-1"><?=fin_h('common_status', 'Status');?></label>
          <div class="col-md-2"><select id="filter_status" class="form-control select2-filter"><option value="">All</option><option value="DRAFT"><?=fin_h('finance_draft', 'Draft');?></option><option value="POSTED"><?=fin_h('finance_posted', 'Posted');?></option><option value="REVERSED">Reversed</option></select></div>
          <label class="control-label col-md-1">Type</label>
          <div class="col-md-2"><select id="filter_type" class="form-control select2-filter"><option value="">All</option><option value="RECEIPT">Receipt</option><option value="PAYMENT"><?=fin_h('finance_payment', 'Payment');?></option></select></div>
        </div>
        <div class="form-group">
          <label class="control-label col-md-1"><?=fin_h('finance_cash', 'Cash');?></label>
          <div class="col-md-4"><select id="filter_cash_account" class="form-control select2-filter"><option value=""></option><?php foreach($cashAccounts as $r){ ?><option value="<?=cj_h($r->no_rek);?>"><?=cj_h($r->no_rek.' - '.$r->nama_rek);?></option><?php } ?></select></div>
          <div class="col-md-7"><button type="button" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> Tampilkan</button><button type="button" id="btn_reset" class="btn btn-default"><i class="fa fa-refresh"></i> <?=fin_h('common_reset', 'Reset');?></button></div>
        </div>
      </form>
      <p class="text-muted"><i class="fa fa-info-circle"></i> Draft dapat diedit. Setelah diposting, dokumen terkunci dan koreksi dilakukan dengan reversal.</p>
      <div id="cj_alert" class="alert alert-danger" style="display:none"></div>
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover cj-table" id="cash_journal_table">
          <thead><tr><th><?=fin_h('common_no', 'No');?></th><th><?=fin_h('finance_posting_date', 'Posting Date');?></th><th>Cash Journal No</th><th>Type</th><th><?=fin_h('common_status', 'Status');?></th><th>Cash Account</th><th>Offset Account</th><th><?=fin_h('finance_reference', 'Reference');?></th><th><?=fin_h('finance_description', 'Description');?></th><th><?=fin_h('finance_amount', 'Amount');?></th><th>Curr</th><th><?=fin_h('common_action', 'Action');?></th></tr></thead>
          <tbody><tr><td colspan="12" class="text-center text-muted">Klik Tampilkan untuk memuat data.</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="cj_modal"><div class="modal-dialog modal-lg"><div class="modal-content"><form id="cj_form"><input type="hidden" name="id" id="cj_id"><div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Cash Journal Entry</h4></div><div class="modal-body">
  <div class="row">
    <div class="col-md-3"><label><?=fin_h('common_no', 'No');?></label><input name="cash_journal_no" id="cash_journal_no" class="form-control" readonly></div>
    <div class="col-md-3"><label>Type</label><select name="transaction_type" id="transaction_type" class="form-control select2-modal"><option value="RECEIPT">Receipt / Kas Masuk</option><option value="PAYMENT">Payment / Kas Keluar</option></select></div>
    <div class="col-md-3"><label><?=fin_h('finance_document_date', 'Document Date');?></label><div class="input-group date"><input name="document_date" id="document_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
    <div class="col-md-3"><label><?=fin_h('finance_posting_date', 'Posting Date');?></label><div class="input-group date"><input name="posting_date" id="posting_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
  </div><br>
  <div class="row">
    <div class="col-md-6"><label>Cash Account</label><select name="cash_account" id="cash_account" class="form-control select2-modal" required><option value=""></option><?php foreach($cashAccounts as $r){ ?><option value="<?=cj_h($r->no_rek);?>"><?=cj_h($r->no_rek.' - '.$r->nama_rek);?></option><?php } ?></select></div>
    <div class="col-md-6"><label>Offset Account</label><select name="offset_account" id="offset_account" class="form-control select2-modal" required><option value=""></option><?php foreach($offsetAccounts as $r){ ?><option value="<?=cj_h($r->no_rek);?>"><?=cj_h($r->no_rek.' - '.$r->nama_rek);?></option><?php } ?></select></div>
  </div><br>
  <div class="row">
    <div class="col-md-3"><label><?=fin_h('finance_amount', 'Amount');?></label><input name="amount" id="amount" type="number" step="0.01" min="0" class="form-control text-right" required></div>
    <div class="col-md-2"><label><?=fin_h('finance_currency', 'Currency');?></label><select name="currency" id="currency" class="form-control select2-modal"><?php foreach($currencies as $r){ ?><option value="<?=cj_h($r->jenis_valas);?>" <?=$r->jenis_valas==='IDR'?'selected':'';?>><?=cj_h($r->jenis_valas);?></option><?php } ?></select></div>
    <div class="col-md-2"><label>Kurs</label><input name="kurs" id="kurs" type="number" step="0.0001" class="form-control text-right" value="1"></div>
    <div class="col-md-5"><label><?=fin_h('finance_reference', 'Reference');?></label><input name="reference_no" id="reference_no" class="form-control"></div>
  </div><br>
  <div class="row">
    <div class="col-md-4"><label>Cost Center</label><select name="cost_center_id" id="cost_center_id" class="form-control select2-modal"><option value=""></option><?php foreach($costCenters as $r){ ?><option value="<?=$r->id;?>"><?=cj_h($r->cost_center_code.' - '.$r->cost_center_name);?></option><?php } ?></select></div>
    <div class="col-md-4"><label>Profit Center</label><select name="profit_center_id" id="profit_center_id" class="form-control select2-modal"><option value=""></option><?php foreach($profitCenters as $r){ ?><option value="<?=$r->id;?>"><?=cj_h($r->profit_center_code.' - '.$r->profit_center_name);?></option><?php } ?></select></div>
    <div class="col-md-4"><label><?=fin_h('finance_tax_code', 'Tax Code');?></label><select name="tax_code_id" id="tax_code_id" class="form-control select2-modal"><option value=""></option><?php foreach($taxCodes as $r){ ?><option value="<?=$r->id;?>"><?=cj_h($r->tax_code.' - '.$r->tax_name.' '.$r->rate.'%');?></option><?php } ?></select></div>
  </div><br>
  <label><?=fin_h('finance_description', 'Description');?></label><textarea name="description" id="description" class="form-control" required></textarea>
</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=fin_h('common_close', 'Close');?></button><button type="button" class="btn btn-warning cj-save" data-act="save"><i class="fa fa-save"></i> Save Draft</button><button type="button" class="btn btn-success cj-save" data-act="post"><i class="fa fa-check"></i> <?=fin_h('common_post', 'Post');?></button></div></form></div></div></div>

<div class="modal fade" id="cj_detail_modal"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Cash Journal Detail</h4></div><div class="modal-body" id="cj_detail_body"></div></div></div></div>

<script>
$(function(){
  $('.input-group.date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});
  $('.select2-filter').select2({width:'100%',allowClear:true,placeholder:'All'});
  var cashJournalTable=null;
  function initModalSelect(){ $('.select2-modal').select2({width:'100%',dropdownParent:$('#cj_modal'),allowClear:true}); }
  function params(){return {start_date:$('#start_date').val(),end_date:$('#end_date').val(),status:$('#filter_status').val(),transaction_type:$('#filter_type').val(),cash_account:$('#filter_cash_account').val()};}
  function rebuildCashJournalTable(){ if(cashJournalTable){cashJournalTable.destroy();} cashJournalTable=$('#cash_journal_table').DataTable({pageLength:25,order:[[1,'desc']],dom:'Bfrtip',buttons:[{extend:'excelHtml5',text:'<i class="fa fa-file-excel-o"></i> Excel',className:'btn btn-success btn-sm',title:'Cash Journal'},{extend:'print',text:'<i class="fa fa-print"></i> Print',className:'btn btn-default btn-sm',title:'Cash Journal'}],columnDefs:[{targets:[7],className:'text-right'},{targets:[11],orderable:false,searchable:false,className:'text-center'}]});}
  function loadData(){
    if(cashJournalTable){cashJournalTable.destroy();cashJournalTable=null;}
    $('#cash_journal_table tbody').html('<tr><td colspan="12" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');
    $.post('<?=base_admin();?>modul/cash_journal/cash_journal_action.php?act=filter',params(),function(res){
      if(res.status==='success'){$('#cash_journal_table tbody').html(res.html);$('#kpi_receipt').text(res.receipt);$('#kpi_payment').text(res.payment);$('#kpi_balance').text(res.balance);$('#kpi_count').text(res.count);if(parseInt(res.count,10)>0)rebuildCashJournalTable();}
      else{$('#cash_journal_table tbody').html('<tr><td colspan="12" class="text-danger text-center">'+res.message+'</td></tr>');}
    },'json');
  }
  $('#btn_filter').on('click',loadData);
  $('#btn_reset').on('click',function(){$('#cj_filter')[0].reset();$('.select2-filter').val('').trigger('change');$('#start_date').val('<?=date('Y-m-01');?>');$('#end_date').val('<?=date('Y-m-d');?>');loadData();});
  $('#btn_add').on('click',function(){ $('#cj_form')[0].reset(); $('#cj_id').val(''); $('#document_date,#posting_date').val('<?=date('Y-m-d');?>'); $('#kurs').val('1'); $.getJSON('<?=base_admin();?>modul/cash_journal/cash_journal_action.php?act=get_no',function(res){$('#cash_journal_no').val(res.no);}); $('#cj_modal').modal('show'); initModalSelect(); });
  $(document).on('click','.cj-save',function(){ var act=$(this).data('act'); $.post('<?=base_admin();?>modul/cash_journal/cash_journal_action.php?act='+act,$('#cj_form').serialize(),function(res){ if(res.status==='success'){Swal.fire('Berhasil',res.message,'success');$('#cj_modal').modal('hide');loadData();} else Swal.fire('Gagal',res.message,'error');},'json'); });
  $(document).on('click','.cj-edit',function(){ $.getJSON('<?=base_admin();?>modul/cash_journal/cash_journal_action.php?act=get&id='+$(this).data('id'),function(res){ if(res.status!=='success'){Swal.fire('Gagal',res.message,'error');return;} var d=res.data; $('#cj_form')[0].reset(); $('#cj_id').val(d.id); $('#cash_journal_no').val(d.cash_journal_no); $('#transaction_type').val(d.transaction_type); $('#document_date').val(d.document_date); $('#posting_date').val(d.posting_date); $('#cash_account').val(d.cash_account); $('#offset_account').val(d.offset_account); $('#amount').val(d.amount); $('#currency').val(d.currency); $('#kurs').val(d.kurs); $('#cost_center_id').val(d.cost_center_id); $('#profit_center_id').val(d.profit_center_id); $('#tax_code_id').val(d.tax_code_id); $('#reference_no').val(d.reference_no); $('#description').val(d.description); $('#cj_modal').modal('show'); initModalSelect(); $('.select2-modal').trigger('change'); }); });
  $(document).on('click','.cj-detail',function(){ $('#cj_detail_modal').modal('show'); $('#cj_detail_body').html(<?=fin_js('common_loading', 'Loading...');?>); $.getJSON('<?=base_admin();?>modul/cash_journal/cash_journal_action.php?act=detail&id='+$(this).data('id'),function(res){$('#cj_detail_body').html(res.status==='success'?res.html:'<div class="alert alert-danger">'+res.message+'</div>');}); });
  $(document).on('click','.cj-post',function(){ var id=$(this).data('id'); Swal.fire({title:'Post cash journal?',icon:'question',showCancelButton:true}).then(function(r){if(r.isConfirmed){$.post('<?=base_admin();?>modul/cash_journal/cash_journal_action.php?act=post',{id:id},function(res){res.status==='success'?Swal.fire('Berhasil',res.message,'success'):Swal.fire('Gagal',res.message,'error');loadData();},'json');}}); });
  $(document).on('click','.cj-reverse',function(){ var id=$(this).data('id'); Swal.fire({title:'Reversal cash journal?',text:'Dokumen posted akan dikoreksi dengan jurnal reversal.',icon:'warning',showCancelButton:true}).then(function(r){if(r.isConfirmed){$.post('<?=base_admin();?>modul/cash_journal/cash_journal_action.php?act=reverse',{id:id},function(res){res.status==='success'?Swal.fire('Berhasil',res.message,'success'):Swal.fire('Gagal',res.message,'error');loadData();},'json');}}); });
  $('#btn_excel').on('click',function(){window.open('<?=base_admin();?>modul/cash_journal/cash_journal_action.php?act=excel&'+$.param(params()));});
  loadData();
});
</script>
