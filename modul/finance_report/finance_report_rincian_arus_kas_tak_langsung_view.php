<?php
if (!function_exists('finrep_t')) {
  function finrep_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
}
if (!function_exists('finrep_h')) {
  function finrep_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$reportTitle = finrep_t('finance_report_cash_flow_indirect_detail', 'Rincian Arus Kas (Tak Langsung)');
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.cfi-table th,.cfi-table td{font-size:12px;vertical-align:middle!important}
.cfi-section th{background:#1d4ed8!important;color:#fff}
.cfi-subsection td{background:#eef2ff!important;font-weight:700;color:#1e3a8a}
.cfi-total th,.cfi-total td{background:#f3f4f6!important;font-weight:700}
.cfi-grand th,.cfi-grand td{background:#0f766e!important;color:#fff;font-weight:700}
.cfi-check th,.cfi-check td{background:#fff7ed!important;font-weight:700}
.cfi-account{padding-left:28px!important}
.cfi-toolbar{margin-bottom:14px}
.cfi-kpi{border-left:3px solid #1d4ed8;padding-left:12px;margin-bottom:12px}
.cfi-kpi .label-text{color:#6b7280;font-size:12px}
.cfi-kpi .value-text{font-size:18px;font-weight:700}
</style>
<section class="content-header">
  <h1><?=finrep_h($reportTitle);?> <small>Finance Reports</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=finrep_h(finrep_t('nav_home', 'Home'));?></a></li>
    <li><a href="<?=base_index();?>finance-report">Finance Reports</a></li>
    <li class="active"><?=finrep_h($reportTitle);?></li>
  </ol>
</section>
<section class="content">
  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title"><?=finrep_h(finrep_t('finance_indirect_cash_flow_detail_statement', 'Laporan Rincian Arus Kas Tak Langsung'));?></h3>
      <div class="box-tools">
        <button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</button>
        <button id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print/PDF</button>
      </div>
    </div>
    <div class="box-body">
      <form id="form_cfi_detail" class="form-horizontal cfi-toolbar">
        <div class="form-group">
          <label class="control-label col-md-1"><?=finrep_h(finrep_t('finance_period', 'Periode'));?></label>
          <div class="col-md-2"><input type="date" id="start_date" name="start_date" class="form-control" value="<?=date('Y-m-01');?>"></div>
          <div class="col-md-2"><input type="date" id="end_date" name="end_date" class="form-control" value="<?=date('Y-m-d');?>"></div>
          <div class="col-md-7">
            <button type="submit" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> <?=finrep_h(finrep_t('common_show', 'Tampilkan'));?></button>
          </div>
        </div>
      </form>
      <div class="row" id="cfi_summary" style="display:none">
        <div class="col-md-3"><div class="cfi-kpi"><div class="label-text"><?=finrep_h(finrep_t('finance_opening_cash', 'Saldo Kas Awal'));?></div><div id="sum_opening" class="value-text">0.00</div></div></div>
        <div class="col-md-3"><div class="cfi-kpi"><div class="label-text"><?=finrep_h(finrep_t('finance_net_cash_flow', 'Net Cash Flow'));?></div><div id="sum_net" class="value-text">0.00</div></div></div>
        <div class="col-md-3"><div class="cfi-kpi"><div class="label-text"><?=finrep_h(finrep_t('finance_ending_cash', 'Saldo Kas Akhir'));?></div><div id="sum_ending" class="value-text">0.00</div></div></div>
        <div class="col-md-3"><div class="cfi-kpi"><div class="label-text"><?=finrep_h(finrep_t('finance_cash_reconciliation_diff', 'Selisih Rekonsiliasi Kas'));?></div><div id="sum_diff" class="value-text">0.00</div></div></div>
      </div>
      <p class="text-muted"><i class="fa fa-info-circle"></i> <?=finrep_h(finrep_t('finance_cash_flow_indirect_detail_hint', 'Metode tak langsung: mulai dari laba bersih, adjustment non-cash, perubahan aset/kewajiban operasional, lalu aktivitas investasi dan pendanaan.'));?></p>
      <div id="cfi_alert" class="alert alert-danger" style="display:none"></div>
      <div id="cfi_result"><div class="text-center text-muted" style="padding:30px"><?=finrep_h(finrep_t('finance_click_show_report', 'Klik Tampilkan untuk memuat laporan.'));?></div></div>
    </div>
  </div>
</section>
<script>
$(function(){
  function setSummary(summary){
    if(!summary){$('#cfi_summary').hide();return;}
    $('#sum_opening').text(summary.opening_cash_text);
    $('#sum_net').text(summary.net_cash_flow_text);
    $('#sum_ending').text(summary.ending_cash_text);
    $('#sum_diff').text(summary.reconciliation_diff_text);
    $('#cfi_summary').show();
  }
  function loadReport(){
    $('#cfi_alert').hide().text('');
    $('#cfi_result').html('<div class="text-center" style="padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#btn_filter').prop('disabled',true);
    $.ajax({
      url:'<?=base_admin();?>modul/finance_report/finance_report_rincian_arus_kas_tak_langsung_action.php?act=filter',
      type:'POST',
      dataType:'json',
      data:$('#form_cfi_detail').serialize()
    }).done(function(res){
      if(res.status==='success'){
        $('#cfi_result').html(res.html);
        setSummary(res.summary);
      }else{
        setSummary(null);
        $('#cfi_result').html('<div class="alert alert-danger">'+(res.message||'Laporan gagal diproses.')+'</div>');
        $('#cfi_alert').text(res.message||'Laporan gagal diproses.').show();
      }
    }).fail(function(xhr){
      setSummary(null);
      $('#cfi_result').html('<div class="alert alert-danger">Server error</div>');
      $('#cfi_alert').text(xhr.responseText||'Server error').show();
    }).always(function(){
      $('#btn_filter').prop('disabled',false);
    });
  }
  $('#form_cfi_detail').on('submit',function(e){e.preventDefault();loadReport();});
  $('#btn_excel').on('click',function(){window.location='<?=base_admin();?>modul/finance_report/finance_report_rincian_arus_kas_tak_langsung_action.php?act=excel&'+$('#form_cfi_detail').serialize();});
  $('#btn_print').on('click',function(){window.open('<?=base_admin();?>modul/finance_report/finance_report_rincian_arus_kas_tak_langsung_action.php?act=print&'+$('#form_cfi_detail').serialize(),'_blank');});
  loadReport();
});
</script>
