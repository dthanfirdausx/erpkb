<?php
if (!function_exists('finrep_t')) {
  function finrep_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
}
if (!function_exists('finrep_h')) {
  function finrep_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.cfd-table th,.cfd-table td{font-size:12px;vertical-align:middle!important}.cfd-section th{background:#1d4ed8!important;color:#fff}.cfd-total th,.cfd-total td{background:#f3f4f6!important;font-weight:700}.cfd-grand th,.cfd-grand td{background:#0f766e!important;color:#fff;font-weight:700}.cfd-check th,.cfd-check td{background:#fff7ed!important;font-weight:700}.cfd-account{padding-left:28px!important}.cfd-toolbar{margin-bottom:14px}.cfd-kpi .description-block{margin:8px 0}.cfd-kpi .description-header{font-size:20px}
</style>
<section class="content-header">
  <h1><?=finrep_h(finrep_t('finance_report_cash_flow_direct', 'Arus Kas (Langsung)'));?> <small>Finance Reports</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=finrep_h(finrep_t('common_home', 'Home'));?></a></li>
    <li><a href="<?=base_index();?>finance-report">Finance Reports</a></li>
    <li class="active"><?=finrep_h(finrep_t('finance_report_cash_flow_direct', 'Arus Kas (Langsung)'));?></li>
  </ol>
</section>
<section class="content">
  <div class="row cfd-kpi">
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text"><?=finrep_h(finrep_t('finance_opening_cash', 'Saldo Kas Awal'));?></span><h5 class="description-header" id="cfd_opening">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text"><?=finrep_h(finrep_t('finance_cash_inflow', 'Penerimaan Kas'));?></span><h5 class="description-header text-green" id="cfd_inflow">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text"><?=finrep_h(finrep_t('finance_cash_outflow', 'Pengeluaran Kas'));?></span><h5 class="description-header text-red" id="cfd_outflow">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text"><?=finrep_h(finrep_t('finance_ending_cash', 'Saldo Kas Akhir'));?></span><h5 class="description-header" id="cfd_ending">0.00</h5></div></div></div></div>
  </div>
  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title"><?=finrep_h(finrep_t('finance_direct_cash_flow_statement', 'Direct Cash Flow Statement'));?></h3>
      <div class="box-tools">
        <button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> <?=finrep_h(finrep_t('common_export_excel', 'Export Excel'));?></button>
        <button id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> <?=finrep_h(finrep_t('common_print', 'Print/PDF'));?></button>
      </div>
    </div>
    <div class="box-body">
      <form id="form_cfd" class="form-horizontal cfd-toolbar">
        <div class="form-group">
          <label class="control-label col-md-1"><?=finrep_h(finrep_t('finance_period', 'Periode'));?></label>
          <div class="col-md-2"><input type="date" id="start_date" name="start_date" class="form-control" value="<?=date('Y-m-01');?>"></div>
          <div class="col-md-2"><input type="date" id="end_date" name="end_date" class="form-control" value="<?=date('Y-m-d');?>"></div>
          <label class="control-label col-md-2"><?=finrep_h(finrep_t('finance_cash_bank_account', 'Akun Kas/Bank'));?></label>
          <div class="col-md-3">
            <select id="cash_account" name="cash_account" class="form-control">
              <option value=""><?=finrep_h(finrep_t('common_all', 'Semua'));?></option>
            </select>
          </div>
          <div class="col-md-2"><button type="submit" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> <?=finrep_h(finrep_t('common_show', 'Tampilkan'));?></button></div>
        </div>
      </form>
      <p class="text-muted"><i class="fa fa-info-circle"></i> <?=finrep_h(finrep_t('finance_cash_flow_direct_hint', 'Metode langsung: jurnal POSTED yang menyentuh akun kas/bank dikelompokkan berdasarkan akun lawan. Akun kas/bank ditentukan dari cash_flow_mapping atau kategori COA Kas & Bank.'));?></p>
      <div id="cfd_alert" class="alert alert-danger" style="display:none"></div>
      <div id="cfd_result"><div class="text-center text-muted" style="padding:30px"><?=finrep_h(finrep_t('finance_click_show_report', 'Klik Tampilkan untuk memuat laporan.'));?></div></div>
    </div>
  </div>
</section>
<script>
$(function(){
  function num(v){v=parseFloat(v||0);return v.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});}
  function qs(){return $('#form_cfd').serialize();}
  function loadAccounts(){
    $.getJSON('<?=base_admin();?>modul/finance_report/finance_report_arus_kas_langsung_action.php?act=accounts', function(res){
      if(res.status!=='success') return;
      var current=$('#cash_account').val();
      $.each(res.accounts, function(_, acc){
        $('#cash_account').append($('<option>').val(acc.no_rek).text(acc.no_rek+' - '+acc.nama_rek));
      });
      $('#cash_account').val(current);
    });
  }
  function loadReport(){
    $('#cfd_alert').hide().text('');
    $('#cfd_result').html('<div class="text-center" style="padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#btn_filter').prop('disabled',true);
    $.ajax({url:'<?=base_admin();?>modul/finance_report/finance_report_arus_kas_langsung_action.php?act=filter',type:'POST',dataType:'json',data:qs()})
      .done(function(res){
        if(res.status==='success'){
          $('#cfd_result').html(res.html);
          $('#cfd_opening').text(num(res.summary.opening_cash));
          $('#cfd_inflow').text(num(res.summary.total_inflow));
          $('#cfd_outflow').text(num(res.summary.total_outflow));
          $('#cfd_ending').text(num(res.summary.ending_cash));
        } else {
          $('#cfd_result').html('<div class="alert alert-danger">'+(res.message||'Laporan gagal diproses.')+'</div>');
          $('#cfd_alert').text(res.message||'Laporan gagal diproses.').show();
        }
      })
      .fail(function(xhr){$('#cfd_result').html('<div class="alert alert-danger">Server error</div>');$('#cfd_alert').text(xhr.responseText||'Server error').show();})
      .always(function(){$('#btn_filter').prop('disabled',false);});
  }
  $('#form_cfd').on('submit',function(e){e.preventDefault();loadReport();});
  $('#btn_excel').on('click',function(){window.location='<?=base_admin();?>modul/finance_report/finance_report_arus_kas_langsung_action.php?act=excel&'+qs();});
  $('#btn_print').on('click',function(){window.open('<?=base_admin();?>modul/finance_report/finance_report_arus_kas_langsung_action.php?act=print&'+qs(),'_blank');});
  loadAccounts();
  loadReport();
});
</script>
