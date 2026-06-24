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
require_once "bank_reconciliation_lib.php";
function brec_rows($query) {
    $rows = array();
    foreach ($query as $row) $rows[] = $row;
    return $rows;
}
$bankAccounts = brec_rows($db->query("SELECT r.no_rek,r.nama_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE c.no_rek IS NULL AND (r.no_rek LIKE '112%' OR r.nama_rek LIKE '%Bank%' OR r.nama_rek LIKE '%Giro%') ORDER BY r.no_rek"));
$currencies = brec_rows($db->query("SELECT jenis_valas FROM matauang GROUP BY jenis_valas ORDER BY jenis_valas='IDR' DESC, jenis_valas"));
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.brec-kpi .description-block{margin:8px 0}.brec-kpi .description-header{font-size:20px}.brec-table th,.brec-table td{font-size:12px;vertical-align:middle!important}.brec-toolbar{margin-bottom:14px}.brec-panel{min-height:360px}.brec-help{background:#f8fbff;border-left:3px solid #3c8dbc;padding:10px 12px;margin-bottom:12px}.brec-selected{background:#fff8d7!important}
</style>

<section class="content-header">
  <h1><?=fin_h('finance_bank_reconciliation', 'Bank Reconciliation');?> <small>SAP FI Bank Reconciliation</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=fin_h('common_home', 'Home');?></a></li><li>Akunting</li><li>Cash and Bank</li><li class="active"><?=fin_h('finance_bank_reconciliation', 'Bank Reconciliation');?></li></ol>
</section>

<section class="content">
  <div class="row brec-kpi">
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Statement Open</span><h5 class="description-header text-yellow" id="kpi_stmt_open">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Statement Matched</span><h5 class="description-header text-green" id="kpi_stmt_matched">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">ERP Open</span><h5 class="description-header text-blue" id="kpi_erp_open">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Open Items</span><h5 class="description-header" id="kpi_count">0 / 0</h5></div></div></div></div>
  </div>

  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title">Reconciliation Cockpit</h3>
      <div class="box-tools">
        <button class="btn btn-primary btn-sm" id="btn_statement"><i class="fa fa-plus"></i> Add Bank Statement</button>
        <button class="btn btn-success btn-sm" id="btn_excel"><i class="fa fa-file-excel-o"></i> <?=fin_h('common_export_excel', 'Export Excel');?></button>
      </div>
    </div>
    <div class="box-body">
      <form id="brec_filter" class="form-horizontal brec-toolbar">
        <div class="form-group">
          <label class="control-label col-md-1">Tanggal</label>
          <div class="col-md-2"><div class="input-group date"><input id="start_date" class="form-control" value="<?=date('Y-m-01');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <div class="col-md-2"><div class="input-group date"><input id="end_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <label class="control-label col-md-1"><?=fin_h('finance_bank', 'Bank');?></label>
          <div class="col-md-3"><select id="filter_bank_account" class="form-control select2-filter"><option value=""></option><?php foreach($bankAccounts as $r){ ?><option value="<?=brec_h($r->no_rek);?>"><?=brec_h($r->no_rek.' - '.$r->nama_rek);?></option><?php } ?></select></div>
          <label class="control-label col-md-1"><?=fin_h('common_status', 'Status');?></label>
          <div class="col-md-2"><select id="filter_status" class="form-control select2-filter"><option value="">All</option><option value="OPEN">Open Statement</option><option value="MATCHED">Matched Statement</option><option value="CANCELLED"><?=fin_h('finance_cancelled', 'Cancelled');?></option></select></div>
        </div>
        <div class="form-group">
          <div class="col-md-12"><button type="button" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> Tampilkan</button> <button type="button" id="btn_reset" class="btn btn-default"><i class="fa fa-refresh"></i> <?=fin_h('common_reset', 'Reset');?></button> <button type="button" id="btn_match" class="btn btn-success"><i class="fa fa-link"></i> Match Selected</button></div>
        </div>
      </form>
      <div class="brec-help"><i class="fa fa-info-circle"></i> Pilih satu baris statement bank dan satu transaksi ERP open dengan arah dan nominal yang sesuai, lalu klik <b>Match Selected</b>. Credit statement cocok dengan receipt; debit statement cocok dengan payment.</div>

      <div class="row">
        <div class="col-md-6">
          <div class="box box-info brec-panel">
            <div class="box-header with-border"><h3 class="box-title">Bank Statement Lines</h3></div>
            <div class="box-body table-responsive">
              <table class="table table-bordered table-striped table-hover brec-table" id="statement_table">
                <thead><tr><th></th><th>Date</th><th>Statement No</th><th>Bank Ref</th><th><?=fin_h('finance_description', 'Description');?></th><th><?=fin_h('finance_debit', 'Debit');?></th><th><?=fin_h('finance_credit', 'Credit');?></th><th><?=fin_h('common_status', 'Status');?></th><th></th></tr></thead>
                <tbody><tr><td colspan="9" class="text-center text-muted">Loading...</td></tr></tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="box box-warning brec-panel">
            <div class="box-header with-border"><h3 class="box-title">ERP Open Bank Transactions</h3></div>
            <div class="box-body table-responsive">
              <table class="table table-bordered table-striped table-hover brec-table" id="erp_table">
                <thead><tr><th></th><th>Date</th><th>Module</th><th>Doc No</th><th>Ref</th><th>Partner</th><th>Dir</th><th><?=fin_h('finance_amount', 'Amount');?></th><th>Curr</th></tr></thead>
                <tbody><tr><td colspan="9" class="text-center text-muted">Loading...</td></tr></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="box box-success">
        <div class="box-header with-border"><h3 class="box-title">Reconciliation History</h3></div>
        <div class="box-body table-responsive">
          <table class="table table-bordered table-striped table-hover brec-table" id="history_table">
            <thead><tr><th><?=fin_h('common_no', 'No');?></th><th>Match Date</th><th>Match No</th><th>Statement</th><th>Module</th><th>Doc No</th><th><?=fin_h('finance_bank', 'Bank');?></th><th>Statement Amt</th><th>ERP Amt</th><th>Diff</th><th><?=fin_h('common_status', 'Status');?></th><th><?=fin_h('common_action', 'Action');?></th></tr></thead>
            <tbody><tr><td colspan="12" class="text-center text-muted">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="statement_modal"><div class="modal-dialog modal-lg"><div class="modal-content"><form id="statement_form"><div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Add Bank Statement Line</h4></div><div class="modal-body">
  <div class="row">
    <div class="col-md-3"><label>Statement No</label><input name="statement_no" id="statement_no" class="form-control" readonly></div>
    <div class="col-md-5"><label>Bank Account</label><select name="bank_account" id="bank_account" class="form-control select2-modal" required><option value=""></option><?php foreach($bankAccounts as $r){ ?><option value="<?=brec_h($r->no_rek);?>"><?=brec_h($r->no_rek.' - '.$r->nama_rek);?></option><?php } ?></select></div>
    <div class="col-md-2"><label>Statement Date</label><div class="input-group date"><input name="statement_date" id="statement_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
    <div class="col-md-2"><label>Value Date</label><div class="input-group date"><input name="value_date" id="value_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
  </div><br>
  <div class="row">
    <div class="col-md-3"><label>Bank Reference</label><input name="bank_reference" id="bank_reference" class="form-control"></div>
    <div class="col-md-5"><label><?=fin_h('finance_description', 'Description');?></label><input name="description" id="description" class="form-control" required></div>
    <div class="col-md-2"><label><?=fin_h('finance_currency', 'Currency');?></label><select name="currency" id="currency" class="form-control select2-modal"><?php foreach($currencies as $r){ ?><option value="<?=brec_h($r->jenis_valas);?>" <?=$r->jenis_valas==='IDR'?'selected':'';?>><?=brec_h($r->jenis_valas);?></option><?php } ?></select></div>
    <div class="col-md-2"><label>Arah</label><select id="stmt_direction" class="form-control select2-modal"><option value="IN">Credit / Masuk</option><option value="OUT">Debit / Keluar</option></select></div>
  </div><br>
  <div class="row">
    <div class="col-md-3"><label>Debit Amount</label><input name="debit_amount" id="debit_amount" type="number" step="0.01" min="0" class="form-control text-right" value="0"></div>
    <div class="col-md-3"><label>Credit Amount</label><input name="credit_amount" id="credit_amount" type="number" step="0.01" min="0" class="form-control text-right" value="0"></div>
    <div class="col-md-6"><p class="text-muted" style="margin-top:25px">Credit statement berarti dana masuk. Debit statement berarti dana keluar.</p></div>
  </div>
</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=fin_h('common_close', 'Close');?></button><button type="button" id="btn_save_statement" class="btn btn-success"><i class="fa fa-save"></i> Save Statement</button></div></form></div></div></div>

<script>
$(function(){
  $('.input-group.date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});
  $('.select2-filter').select2({width:'100%',allowClear:true,placeholder:'All'});
  var statementTable=null, erpTable=null, historyTable=null;
  function initModalSelect(){ $('.select2-modal').select2({width:'100%',dropdownParent:$('#statement_modal'),allowClear:true}); }
  function params(){return {start_date:$('#start_date').val(),end_date:$('#end_date').val(),bank_account:$('#filter_bank_account').val(),status:$('#filter_status').val()};}
  function selectedStatement(){return $('input[name="statement_pick"]:checked').val()||'';}
  function selectedErp(){var v=$('input[name="erp_pick"]:checked').val()||''; var p=v.split('|'); return {module:p[0]||'',id:p[1]||''};}
  function bindRowHighlight(){
    $('#statement_table tbody tr,#erp_table tbody tr').removeClass('brec-selected');
    $('input[name="statement_pick"]:checked').closest('tr').addClass('brec-selected');
    $('input[name="erp_pick"]:checked').closest('tr').addClass('brec-selected');
  }
  function loadHistory(){
    if(historyTable){historyTable.destroy();historyTable=null;}
    $.post('<?=base_admin();?>modul/bank_reconciliation/bank_reconciliation_action.php?act=history',params(),function(res){
      $('#history_table tbody').html(res.status==='success'?res.html:'<tr><td colspan="12" class="text-danger text-center">'+res.message+'</td></tr>');
      if(res.status==='success' && parseInt(res.count||0,10)>0){historyTable=$('#history_table').DataTable({pageLength:10,order:[[1,'desc']],dom:'Bfrtip',buttons:[{extend:'excelHtml5',text:'<i class="fa fa-file-excel-o"></i> Excel',className:'btn btn-success btn-sm',title:'Bank Reconciliation History'},{extend:'print',text:'<i class="fa fa-print"></i> Print',className:'btn btn-default btn-sm',title:'Bank Reconciliation History'}],columnDefs:[{targets:[10,11],orderable:false,searchable:false,className:'text-center'}]});}
    },'json');
  }
  function loadData(){
    if(statementTable){statementTable.destroy();statementTable=null;}
    if(erpTable){erpTable.destroy();erpTable=null;}
    $('#statement_table tbody').html('<tr><td colspan="9" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');
    $('#erp_table tbody').html('<tr><td colspan="9" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');
    $.post('<?=base_admin();?>modul/bank_reconciliation/bank_reconciliation_action.php?act=filter',params(),function(res){
      if(res.status==='success'){
        $('#statement_table tbody').html(res.statement_html);$('#erp_table tbody').html(res.erp_html);
        $('#kpi_stmt_open').text(res.stmt_open);$('#kpi_stmt_matched').text(res.stmt_matched);$('#kpi_erp_open').text(res.erp_open);$('#kpi_count').text(res.stmt_count+' / '+res.erp_count);
        if(parseInt(res.stmt_count,10)>0)statementTable=$('#statement_table').DataTable({pageLength:10,order:[[1,'desc']],dom:'Bfrtip',buttons:[{extend:'excelHtml5',text:'<i class="fa fa-file-excel-o"></i> Excel',className:'btn btn-success btn-sm',title:'Bank Statement Open'},{extend:'print',text:'<i class="fa fa-print"></i> Print',className:'btn btn-default btn-sm',title:'Bank Statement Open'}],columnDefs:[{targets:[0,8],orderable:false,searchable:false,className:'text-center'},{targets:[5,6],className:'text-right'}]});
        if(parseInt(res.erp_count,10)>0)erpTable=$('#erp_table').DataTable({pageLength:10,order:[[1,'desc']],dom:'Bfrtip',buttons:[{extend:'excelHtml5',text:'<i class="fa fa-file-excel-o"></i> Excel',className:'btn btn-success btn-sm',title:'ERP Bank Open Item'},{extend:'print',text:'<i class="fa fa-print"></i> Print',className:'btn btn-default btn-sm',title:'ERP Bank Open Item'}],columnDefs:[{targets:[0,8],orderable:false,searchable:false,className:'text-center'},{targets:[7],className:'text-right'}]});
      } else {
        $('#statement_table tbody').html('<tr><td colspan="9" class="text-danger text-center">'+res.message+'</td></tr>');
        $('#erp_table tbody').html('<tr><td colspan="9" class="text-danger text-center">'+res.message+'</td></tr>');
      }
      loadHistory();
    },'json');
  }
  $('#btn_filter').on('click',loadData);
  $('#btn_reset').on('click',function(){$('#brec_filter')[0].reset();$('.select2-filter').val('').trigger('change');$('#start_date').val('<?=date('Y-m-01');?>');$('#end_date').val('<?=date('Y-m-d');?>');loadData();});
  $(document).on('change','input[name="statement_pick"],input[name="erp_pick"]',bindRowHighlight);
  $('#btn_statement').on('click',function(){ $('#statement_form')[0].reset(); $('#statement_date,#value_date').val('<?=date('Y-m-d');?>'); $('#debit_amount,#credit_amount').val('0'); $.getJSON('<?=base_admin();?>modul/bank_reconciliation/bank_reconciliation_action.php?act=get_statement_no',function(res){$('#statement_no').val(res.no);}); $('#statement_modal').modal('show'); initModalSelect(); });
  $('#stmt_direction').on('change',function(){ if($(this).val()==='IN'){$('#debit_amount').val('0');}else{$('#credit_amount').val('0');} });
  $('#debit_amount').on('keyup change',function(){ if(parseFloat($(this).val()||0)>0) $('#credit_amount').val('0'); });
  $('#credit_amount').on('keyup change',function(){ if(parseFloat($(this).val()||0)>0) $('#debit_amount').val('0'); });
  $('#btn_save_statement').on('click',function(){ $.post('<?=base_admin();?>modul/bank_reconciliation/bank_reconciliation_action.php?act=save_statement',$('#statement_form').serialize(),function(res){ if(res.status==='success'){Swal.fire('Berhasil',res.message,'success');$('#statement_modal').modal('hide');loadData();}else Swal.fire('Gagal',res.message,'error');},'json'); });
  $('#btn_match').on('click',function(){
    var sid=selectedStatement(), erp=selectedErp();
    if(!sid || !erp.module || !erp.id){Swal.fire('Belum lengkap','Pilih satu statement dan satu transaksi ERP.','warning');return;}
    Swal.fire({title:'Match transaksi ini?',input:'text',inputPlaceholder:'Catatan rekonsiliasi (opsional)',icon:'question',showCancelButton:true}).then(function(r){
      if(r.isConfirmed){$.post('<?=base_admin();?>modul/bank_reconciliation/bank_reconciliation_action.php?act=match',{statement_id:sid,source_module:erp.module,source_id:erp.id,notes:r.value||''},function(res){res.status==='success'?Swal.fire('Berhasil',res.message,'success'):Swal.fire('Gagal',res.message,'error');loadData();},'json');}
    });
  });
  $(document).on('click','.brec-unmatch',function(){ var id=$(this).data('id'); Swal.fire({title:'Buka rekonsiliasi?',icon:'warning',showCancelButton:true}).then(function(r){ if(r.isConfirmed){$.post('<?=base_admin();?>modul/bank_reconciliation/bank_reconciliation_action.php?act=unmatch',{id:id},function(res){res.status==='success'?Swal.fire('Berhasil',res.message,'success'):Swal.fire('Gagal',res.message,'error');loadData();},'json');} }); });
  $(document).on('click','.brec-stmt-detail',function(){ $.getJSON('<?=base_admin();?>modul/bank_reconciliation/bank_reconciliation_action.php?act=statement_detail&id='+$(this).data('id'),function(res){ if(res.status==='success') Swal.fire({title:'Statement Detail',html:res.html,width:850}); else Swal.fire('Gagal',res.message,'error'); }); });
  $('#btn_excel').on('click',function(){window.open('<?=base_admin();?>modul/bank_reconciliation/bank_reconciliation_action.php?act=excel&'+$.param(params()));});
  loadData();
});
</script>
