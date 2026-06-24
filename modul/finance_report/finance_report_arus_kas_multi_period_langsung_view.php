<?php
if (!function_exists('finrep_t')) {
  function finrep_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
}
if (!function_exists('finrep_h')) {
  function finrep_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$title = finrep_t('finance_report_cash_flow_direct_multi_period', 'Arus Kas Multi Period (Langsung)');
?>
<style>
.cfmp-toolbar{margin-bottom:14px}.cfmp-table th,.cfmp-table td{font-size:12px;vertical-align:middle!important;white-space:nowrap}.cfmp-section th{background:#1d4ed8!important;color:#fff}.cfmp-total th,.cfmp-total td{background:#f3f4f6!important;font-weight:700}.cfmp-grand th,.cfmp-grand td{background:#0f766e!important;color:#fff;font-weight:700}.cfmp-account{padding-left:24px!important;white-space:normal!important;min-width:260px}
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
      <h3 class="box-title"><?=finrep_h(finrep_t('finance_direct_cash_flow_multi_period_statement', 'Laporan Arus Kas Langsung Multi Period'));?></h3>
      <div class="box-tools">
        <button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</button>
        <button id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print/PDF</button>
      </div>
    </div>
    <div class="box-body">
      <form id="form_cfmp" class="form-horizontal cfmp-toolbar">
        <div class="form-group">
          <label class="control-label col-md-2"><?=finrep_h(finrep_t('finance_start_month', 'Start Month'));?></label>
          <div class="col-md-2"><input type="month" id="start_month" name="start_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <label class="control-label col-md-2"><?=finrep_h(finrep_t('finance_end_month', 'End Month'));?></label>
          <div class="col-md-2"><input type="month" id="end_month" name="end_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <div class="col-md-4"><button type="submit" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> <?=finrep_h(finrep_t('common_show', 'Tampilkan'));?></button></div>
        </div>
      </form>
      <p class="text-muted"><i class="fa fa-info-circle"></i> <?=finrep_h(finrep_t('finance_cash_flow_direct_multi_period_hint', 'Metode langsung per bulan: jurnal POSTED yang menyentuh akun kas/bank dikelompokkan berdasarkan akun lawan. Kas/bank ditentukan dari cash_flow_mapping atau kategori COA Kas & Bank.'));?></p>
      <div id="cfmp_alert" class="alert alert-danger" style="display:none"></div>
      <div id="cfmp_result"><div class="text-center text-muted" style="padding:30px"><?=finrep_h(finrep_t('common_loading', 'Loading...'));?></div></div>
    </div>
  </div>
</section>
<script>
$(function(){
  function qs(){return $('#form_cfmp').serialize();}
  function loadReport(){
    $('#cfmp_alert').hide().text('');
    $('#cfmp_result').html('<div class="text-center" style="padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#btn_filter').prop('disabled',true);
    $.ajax({url:'<?=base_admin();?>modul/finance_report/finance_report_arus_kas_multi_period_langsung_action.php?act=filter',type:'POST',dataType:'json',data:qs()}).done(function(res){
      if(res.status==='success') $('#cfmp_result').html(res.html);
      else { $('#cfmp_result').html('<div class="alert alert-danger">'+(res.message||'Laporan gagal diproses.')+'</div>'); $('#cfmp_alert').text(res.message||'Laporan gagal diproses.').show(); }
    }).fail(function(xhr){ $('#cfmp_result').html('<div class="alert alert-danger">Server error</div>'); $('#cfmp_alert').text(xhr.responseText||'Server error').show(); }).always(function(){ $('#btn_filter').prop('disabled',false); });
  }
  $('#form_cfmp').on('submit',function(e){e.preventDefault();loadReport();});
  $('#btn_excel').on('click',function(){ window.location='<?=base_admin();?>modul/finance_report/finance_report_arus_kas_multi_period_langsung_action.php?act=excel&'+qs(); });
  $('#btn_print').on('click',function(){ window.open('<?=base_admin();?>modul/finance_report/finance_report_arus_kas_multi_period_langsung_action.php?act=print&'+qs(), '_blank'); });
  loadReport();
});
</script>
