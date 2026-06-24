<?php
if (!function_exists('finrep_t')) {
  function finrep_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
}
if (!function_exists('finrep_h')) {
  function finrep_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$title = finrep_t('finance_report_focus', 'Fokus Keuangan');
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.ffocus-card{border-left:4px solid #1d4ed8;background:#fff;border:1px solid #d2d6de;border-left-width:4px;padding:12px;margin-bottom:14px;min-height:92px}
.ffocus-card.orange{border-left-color:#f97316}.ffocus-card.green{border-left-color:#0f766e}.ffocus-card.red{border-left-color:#dc2626}.ffocus-card.gray{border-left-color:#64748b}
.ffocus-card .label-text{font-size:12px;color:#6b7280;text-transform:uppercase}
.ffocus-card .value-text{font-size:20px;font-weight:700;margin:4px 0}
.ffocus-card .trend-text{font-size:12px;color:#6b7280}
.ffocus-table th,.ffocus-table td{font-size:12px;vertical-align:middle!important}
.ffocus-section th{background:#1d4ed8!important;color:#fff}
.ffocus-total th,.ffocus-total td{background:#f3f4f6!important;font-weight:700}
.ffocus-grand th,.ffocus-grand td{background:#0f766e!important;color:#fff;font-weight:700}
.ffocus-toolbar{margin-bottom:14px}
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
      <h3 class="box-title"><?=finrep_h(finrep_t('finance_focus_statement', 'Ringkasan Fokus Keuangan'));?></h3>
      <div class="box-tools">
        <button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</button>
        <button id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print/PDF</button>
      </div>
    </div>
    <div class="box-body">
      <form id="form_ffocus" class="form-horizontal ffocus-toolbar">
        <div class="form-group">
          <label class="control-label col-md-1"><?=finrep_h(finrep_t('finance_start_date', 'Start Date'));?></label>
          <div class="col-md-2"><input type="date" id="start_date" name="start_date" class="form-control" value="<?=date('Y-m-01');?>"></div>
          <label class="control-label col-md-1"><?=finrep_h(finrep_t('finance_end_date', 'End Date'));?></label>
          <div class="col-md-2"><input type="date" id="end_date" name="end_date" class="form-control" value="<?=date('Y-m-d');?>"></div>
          <div class="col-md-6">
            <button type="submit" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> <?=finrep_h(finrep_t('common_show', 'Tampilkan'));?></button>
            <button type="button" id="btn_default" class="btn btn-default"><i class="fa fa-refresh"></i> <?=finrep_h(finrep_t('finance_current_month', 'Bulan Ini'));?></button>
          </div>
        </div>
      </form>
      <p class="text-muted"><i class="fa fa-info-circle"></i> <?=finrep_h(finrep_t('finance_focus_hint', 'Dashboard ringkas dari jurnal POSTED dan kategori COA resmi; tren dibandingkan periode sebelumnya dengan durasi yang sama.'));?></p>
      <div id="ffocus_alert" class="alert alert-danger" style="display:none"></div>
      <div id="ffocus_result"><div class="text-center text-muted" style="padding:30px"><?=finrep_h(finrep_t('finance_click_show_report', 'Klik Tampilkan untuk memuat laporan.'));?></div></div>
    </div>
  </div>
</section>
<script>
$(function(){
  function qs(){return $('#form_ffocus').serialize();}
  function loadReport(){
    $('#ffocus_alert').hide().text('');
    $('#ffocus_result').html('<div class="text-center" style="padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#btn_filter').prop('disabled',true);
    $.ajax({url:'<?=base_admin();?>modul/finance_report/finance_report_fokus_keuangan_action.php?act=filter',type:'POST',dataType:'json',data:qs()}).done(function(res){
      if(res.status==='success') $('#ffocus_result').html(res.html);
      else { $('#ffocus_result').html('<div class="alert alert-danger">'+(res.message||'Laporan gagal diproses.')+'</div>'); $('#ffocus_alert').text(res.message||'Laporan gagal diproses.').show(); }
    }).fail(function(xhr){ $('#ffocus_result').html('<div class="alert alert-danger">Server error</div>'); $('#ffocus_alert').text(xhr.responseText||'Server error').show(); }).always(function(){ $('#btn_filter').prop('disabled',false); });
  }
  $('#form_ffocus').on('submit',function(e){e.preventDefault();loadReport();});
  $('#btn_default').on('click',function(){ $('#start_date').val('<?=date('Y-m-01');?>'); $('#end_date').val('<?=date('Y-m-d');?>'); loadReport(); });
  $('#btn_excel').on('click',function(){ window.location='<?=base_admin();?>modul/finance_report/finance_report_fokus_keuangan_action.php?act=excel&'+qs(); });
  $('#btn_print').on('click',function(){ window.open('<?=base_admin();?>modul/finance_report/finance_report_fokus_keuangan_action.php?act=print&'+qs(), '_blank'); });
  loadReport();
});
</script>
