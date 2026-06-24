<?php
if (!function_exists('finrep_t')) {
  function finrep_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
}
if (!function_exists('finrep_h')) {
  function finrep_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$title = finrep_t('finance_report_ratio_monthly', 'Rasio Keuangan (Per Bulan)');
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.frm-table th,.frm-table td{font-size:12px;vertical-align:middle!important;white-space:nowrap}
.frm-group th{background:#1d4ed8!important;color:#fff}
.frm-formula{color:#6b7280;font-size:11px;white-space:normal!important;min-width:260px}
.frm-total th,.frm-total td{background:#f3f4f6!important;font-weight:700}
.frm-toolbar{margin-bottom:14px}
.frm-kpi{border-left:3px solid #1d4ed8;padding-left:12px;margin-bottom:12px}
.frm-kpi .label-text{color:#6b7280;font-size:12px}
.frm-kpi .value-text{font-size:18px;font-weight:700}
</style>
<section class="content-header">
  <h1><?=finrep_h($title);?> <small>Finance Reports</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=finrep_h(finrep_t('nav_home', 'Home'));?></a></li>
    <li><a href="<?=base_index();?>finance-report">Finance Reports</a></li>
    <li class="active"><?=finrep_h($title);?></li>
  </ol>
</section>
<section class="content">
  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title"><?=finrep_h(finrep_t('finance_monthly_ratio_statement', 'Laporan Rasio Keuangan Bulanan'));?></h3>
      <div class="box-tools">
        <button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</button>
        <button id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print/PDF</button>
      </div>
    </div>
    <div class="box-body">
      <form id="form_frm" class="form-horizontal frm-toolbar">
        <div class="form-group">
          <label class="control-label col-md-1"><?=finrep_h(finrep_t('finance_start_month', 'Start Month'));?></label>
          <div class="col-md-2"><input type="month" id="start_month" name="start_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <label class="control-label col-md-1"><?=finrep_h(finrep_t('finance_end_month', 'End Month'));?></label>
          <div class="col-md-2"><input type="month" id="end_month" name="end_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <div class="col-md-6">
            <button type="submit" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> <?=finrep_h(finrep_t('common_show', 'Tampilkan'));?></button>
            <button type="button" id="btn_default" class="btn btn-default"><i class="fa fa-refresh"></i> <?=finrep_h(finrep_t('finance_current_month', 'Bulan Ini'));?></button>
          </div>
        </div>
      </form>
      <div class="row" id="frm_summary" style="display:none">
        <div class="col-md-3"><div class="frm-kpi"><div class="label-text"><?=finrep_h(finrep_t('finance_current_ratio', 'Current Ratio'));?></div><div id="sum_current_ratio" class="value-text">-</div></div></div>
        <div class="col-md-3"><div class="frm-kpi"><div class="label-text"><?=finrep_h(finrep_t('finance_debt_ratio', 'Debt Ratio'));?></div><div id="sum_debt_ratio" class="value-text">-</div></div></div>
        <div class="col-md-3"><div class="frm-kpi"><div class="label-text"><?=finrep_h(finrep_t('finance_net_margin', 'Net Margin'));?></div><div id="sum_net_margin" class="value-text">-</div></div></div>
        <div class="col-md-3"><div class="frm-kpi"><div class="label-text"><?=finrep_h(finrep_t('finance_roe', 'ROE'));?></div><div id="sum_roe" class="value-text">-</div></div></div>
      </div>
      <p class="text-muted"><i class="fa fa-info-circle"></i> <?=finrep_h(finrep_t('finance_ratio_monthly_hint', 'Rasio bulanan: P&L dihitung per bulan, neraca dihitung sampai akhir bulan dari jurnal POSTED dan kategori COA resmi.'));?></p>
      <div id="frm_alert" class="alert alert-danger" style="display:none"></div>
      <div id="frm_result"><div class="text-center text-muted" style="padding:30px"><?=finrep_h(finrep_t('finance_click_show_report', 'Klik Tampilkan untuk memuat laporan.'));?></div></div>
    </div>
  </div>
</section>
<script>
$(function(){
  function setSummary(summary){
    if(!summary){$('#frm_summary').hide();return;}
    $('#sum_current_ratio').text(summary.current_ratio_text);
    $('#sum_debt_ratio').text(summary.debt_ratio_text);
    $('#sum_net_margin').text(summary.net_margin_text);
    $('#sum_roe').text(summary.roe_text);
    $('#frm_summary').show();
  }
  function qs(){return $('#form_frm').serialize();}
  function loadReport(){
    $('#frm_alert').hide().text('');
    $('#frm_result').html('<div class="text-center" style="padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#btn_filter').prop('disabled',true);
    $.ajax({url:'<?=base_admin();?>modul/finance_report/finance_report_rasio_keuangan_per_bulan_action.php?act=filter',type:'POST',dataType:'json',data:qs()}).done(function(res){
      if(res.status==='success'){ $('#frm_result').html(res.html); setSummary(res.summary); }
      else { setSummary(null); $('#frm_result').html('<div class="alert alert-danger">'+(res.message||'Laporan gagal diproses.')+'</div>'); $('#frm_alert').text(res.message||'Laporan gagal diproses.').show(); }
    }).fail(function(xhr){ setSummary(null); $('#frm_result').html('<div class="alert alert-danger">Server error</div>'); $('#frm_alert').text(xhr.responseText||'Server error').show(); }).always(function(){ $('#btn_filter').prop('disabled',false); });
  }
  $('#form_frm').on('submit',function(e){e.preventDefault();loadReport();});
  $('#btn_default').on('click',function(){ $('#start_month,#end_month').val('<?=date('Y-m');?>'); loadReport(); });
  $('#btn_excel').on('click',function(){ window.location='<?=base_admin();?>modul/finance_report/finance_report_rasio_keuangan_per_bulan_action.php?act=excel&'+qs(); });
  $('#btn_print').on('click',function(){ window.open('<?=base_admin();?>modul/finance_report/finance_report_rasio_keuangan_per_bulan_action.php?act=print&'+qs(), '_blank'); });
  loadReport();
});
</script>
