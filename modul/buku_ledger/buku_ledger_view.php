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
$accounts = $db->query("SELECT r.no_rek,r.nama_rek FROM rekening r LEFT JOIN rekening c ON c.induk=r.no_rek WHERE c.no_rek IS NULL ORDER BY r.no_rek");
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.ledger-filter .form-group{margin-bottom:12px}.ledger-table th,.ledger-table td{font-size:12px;vertical-align:middle!important}.ledger-kpi .description-block{margin:8px 0}.ledger-kpi .description-header{font-size:20px}.ledger-help{color:#6b7280}
</style>

<section class="content-header">
  <h1><?=fin_h('finance_general_ledger', 'General Ledger');?> <small>SAP FI General Ledger</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=fin_h('common_home', 'Home');?></a></li>
    <li>Akunting</li>
    <li class="active"><?=fin_h('finance_general_ledger', 'General Ledger');?></li>
  </ol>
</section>

<section class="content">
  <div class="row ledger-kpi">
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Opening Balance</span><h5 class="description-header" id="kpi_opening">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Total Debit</span><h5 class="description-header text-green" id="kpi_debit">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Total Credit</span><h5 class="description-header text-red" id="kpi_credit">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Ending Balance</span><h5 class="description-header text-blue" id="kpi_ending">0.00</h5></div></div></div></div>
  </div>

  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title">Ledger Account Line Items</h3>
      <div class="box-tools">
        <button type="button" class="btn btn-success btn-sm" id="btn_excel"><i class="fa fa-file-excel-o"></i> <?=fin_h('common_export_excel', 'Export Excel');?></button>
      </div>
    </div>
    <div class="box-body">
      <form id="ledger_filter" class="form-horizontal ledger-filter">
        <div class="form-group">
          <label class="control-label col-md-1">Tanggal</label>
          <div class="col-md-2"><div class="input-group date"><input type="text" id="start_date" class="form-control" value="<?=date('Y-m-01');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <div class="col-md-2"><div class="input-group date"><input type="text" id="end_date" class="form-control" value="<?=date('Y-m-d');?>"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <label class="control-label col-md-1"><?=fin_h('finance_coa', 'COA');?></label>
          <div class="col-md-5">
            <select id="no_rek" class="form-control select2-filter">
              <option value=""></option>
              <?php foreach($accounts as $acc){ ?>
                <option value="<?=htmlspecialchars($acc->no_rek,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($acc->no_rek.' - '.$acc->nama_rek,ENT_QUOTES,'UTF-8');?></option>
              <?php } ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="control-label col-md-1"><?=fin_h('common_status', 'Status');?></label>
          <div class="col-md-2"><select id="posting_status" class="form-control select2-filter"><option value="">Posted + Reversed</option><option value="POSTED">Posted Only</option><option value="REVERSED">Reversed Only</option><option value="DRAFT"><?=fin_h('finance_draft', 'Draft');?></option></select></div>
          <label class="control-label col-md-1">Doc Type</label>
          <div class="col-md-2"><select id="document_type" class="form-control select2-filter"><option value="">All</option><option value="SA">SA</option><option value="AJE">AJE</option><option value="DR">DR</option><option value="KR">KR</option><option value="CM">CM</option><option value="DM">DM</option><option value="KZ">KZ</option><option value="DZ">DZ</option><option value="RV">RV</option></select></div>
          <label class="control-label col-md-1">Source</label>
          <div class="col-md-2"><input id="source_module" class="form-control" placeholder="SALES, GR, IMPORT_GL"></div>
          <div class="col-md-3">
            <button type="button" class="btn btn-primary" id="btn_filter"><i class="fa fa-search"></i> Tampilkan</button>
            <button type="button" class="btn btn-default" id="btn_reset"><i class="fa fa-refresh"></i> <?=fin_h('common_reset', 'Reset');?></button>
          </div>
        </div>
      </form>
      <p class="ledger-help"><i class="fa fa-info-circle"></i> Buku besar hanya membaca jurnal FI. Default menampilkan jurnal `POSTED` dan `REVERSED`; draft bisa dipilih khusus untuk review.</p>
      <div id="ledger_alert" class="alert alert-danger" style="display:none"></div>
      <div class="table-responsive">
        <table id="dtb_buku_ledger" class="table table-bordered table-striped table-hover ledger-table">
          <thead>
            <tr>
              <th><?=fin_h('common_no', 'No');?></th><th>Tanggal</th><th>No Jurnal</th><th><?=fin_h('common_status', 'Status');?></th><th>Doc Type</th><th>No Bukti</th><th>Keterangan</th><th>Source</th><th>Cost Ctr</th><th>Profit Ctr</th><th><?=fin_h('finance_debit', 'Debit');?></th><th><?=fin_h('finance_credit', 'Credit');?></th><th>Saldo</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="13" class="text-center text-muted">Pilih COA lalu klik Tampilkan.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="modal_detail_jurnal"><div class="modal-dialog modal-lg" style="width:92%"><div class="modal-content"><div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Journal Detail</h4></div><div class="modal-body" id="detail_jurnal_body"></div></div></div></div>

<script>
$(function(){
  $('.input-group.date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});
  $('.select2-filter').select2({width:'100%',placeholder:'Pilih data',allowClear:true});

  function params(){
    return {
      start_date: $('#start_date').val(),
      end_date: $('#end_date').val(),
      no_rek: $('#no_rek').val(),
      posting_status: $('#posting_status').val(),
      document_type: $('#document_type').val(),
      source_module: $('#source_module').val()
    };
  }

  function loadLedger(){
    $('#ledger_alert').hide().text('');
    if(!$('#no_rek').val()){
      Swal.fire('COA wajib dipilih','Pilih akun terlebih dahulu untuk menampilkan buku besar.','warning');
      return;
    }
    $('#dtb_buku_ledger tbody').html('<tr><td colspan="13" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');
    $.post('<?=base_admin();?>modul/buku_ledger/buku_ledger_action.php?act=filter', params(), function(res){
      if(res.status === 'success'){
        $('#dtb_buku_ledger tbody').html(res.html);
        $('#kpi_opening').text(res.opening_balance);
        $('#kpi_debit').text(res.total_debet);
        $('#kpi_credit').text(res.total_kredit);
        $('#kpi_ending').text(res.ending_balance);
      } else {
        $('#dtb_buku_ledger tbody').html('<tr><td colspan="13" class="text-center text-danger">'+res.message+'</td></tr>');
        $('#ledger_alert').text(res.message).show();
      }
    }, 'json').fail(function(xhr){
      $('#dtb_buku_ledger tbody').html('<tr><td colspan="13" class="text-center text-danger">Server error</td></tr>');
      $('#ledger_alert').text(xhr.responseText).show();
    });
  }

  $('#btn_filter').on('click', loadLedger);
  $('#btn_reset').on('click', function(){
    $('#ledger_filter')[0].reset();
    $('.select2-filter').val('').trigger('change');
    $('#start_date').val('<?=date('Y-m-01');?>');
    $('#end_date').val('<?=date('Y-m-d');?>');
    $('#dtb_buku_ledger tbody').html('<tr><td colspan="13" class="text-center text-muted">Pilih COA lalu klik Tampilkan.</td></tr>');
    $('#kpi_opening,#kpi_debit,#kpi_credit,#kpi_ending').text('0.00');
  });
  $('#btn_excel').on('click', function(){
    if(!$('#no_rek').val()){
      Swal.fire('COA wajib dipilih','Pilih akun terlebih dahulu untuk export buku besar.','warning');
      return;
    }
    var p = params();
    var qs = $.param(p);
    window.open('<?=base_admin();?>modul/buku_ledger/buku_ledger_action.php?act=excel&'+qs);
  });
  $(document).on('click','.ledger-detail',function(){
    $('#modal_detail_jurnal').modal('show');
    $('#detail_jurnal_body').html(<?=fin_js('common_loading', 'Loading...');?>);
    $('#detail_jurnal_body').load('<?=base_admin();?>modul/jurnal_umum/detail_jurnal.php?id='+$(this).data('id'));
  });
});
</script>
