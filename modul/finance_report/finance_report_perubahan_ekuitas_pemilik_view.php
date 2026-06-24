<?php
if (!function_exists('finrep_t')) {
  function finrep_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
}
if (!function_exists('finrep_h')) {
  function finrep_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$title = finrep_t('finance_report_owner_equity_changes', 'Perubahan Ekuitas Pemilik');
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.fec-card{border:1px solid #d2d6de;border-left:4px solid #1d4ed8;background:#fff;padding:12px;margin-bottom:14px;min-height:86px}
.fec-card.green{border-left-color:#0f766e}.fec-card.orange{border-left-color:#f97316}.fec-card.red{border-left-color:#dc2626}.fec-card.gray{border-left-color:#64748b}
.fec-card .label-text{font-size:12px;color:#6b7280;text-transform:uppercase}.fec-card .value-text{font-size:20px;font-weight:700;margin-top:4px}
.fec-table th,.fec-table td{font-size:12px;vertical-align:middle!important}.fec-section th{background:#1d4ed8!important;color:#fff}.fec-total th,.fec-total td{background:#f3f4f6!important;font-weight:700}.fec-grand th,.fec-grand td{background:#0f766e!important;color:#fff;font-weight:700}.fec-toolbar{margin-bottom:14px}
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
      <h3 class="box-title"><?=finrep_h(finrep_t('finance_owner_equity_statement', 'Laporan Perubahan Ekuitas Pemilik'));?></h3>
      <div class="box-tools">
        <button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</button>
        <button id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print/PDF</button>
      </div>
    </div>
    <div class="box-body">
      <form id="form_fec" class="form-horizontal fec-toolbar">
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
      <p class="text-muted"><i class="fa fa-info-circle"></i> <?=finrep_h(finrep_t('finance_owner_equity_hint', 'Perubahan ekuitas dihitung dari akun kategori modal, laba/rugi bersih periode, dan saldo awal ekuitas dari GL POSTED.'));?></p>
      <div id="fec_alert" class="alert alert-danger" style="display:none"></div>
      <div id="fec_result"><div class="text-center text-muted" style="padding:30px"><?=finrep_h(finrep_t('finance_click_show_report', 'Klik Tampilkan untuk memuat laporan.'));?></div></div>
    </div>
  </div>
</section>
<script>
$(function(){
  function qs(){return $('#form_fec').serialize();}
  function loadReport(){
    $('#fec_alert').hide().text('');
    $('#fec_result').html('<div class="text-center" style="padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#btn_filter').prop('disabled',true);
    $.ajax({url:'<?=base_admin();?>modul/finance_report/finance_report_perubahan_ekuitas_pemilik_action.php?act=filter',type:'POST',dataType:'json',data:qs()}).done(function(res){
      if(res.status==='success') $('#fec_result').html(res.html);
      else { $('#fec_result').html('<div class="alert alert-danger">'+(res.message||'Laporan gagal diproses.')+'</div>'); $('#fec_alert').text(res.message||'Laporan gagal diproses.').show(); }
    }).fail(function(xhr){ $('#fec_result').html('<div class="alert alert-danger">Server error</div>'); $('#fec_alert').text(xhr.responseText||'Server error').show(); }).always(function(){ $('#btn_filter').prop('disabled',false); });
  }
  $('#form_fec').on('submit',function(e){e.preventDefault();loadReport();});
  $('#btn_default').on('click',function(){ $('#start_date').val('<?=date('Y-m-01');?>'); $('#end_date').val('<?=date('Y-m-d');?>'); loadReport(); });
  $('#btn_excel').on('click',function(){ window.location='<?=base_admin();?>modul/finance_report/finance_report_perubahan_ekuitas_pemilik_action.php?act=excel&'+qs(); });
  $('#btn_print').on('click',function(){ window.open('<?=base_admin();?>modul/finance_report/finance_report_perubahan_ekuitas_pemilik_action.php?act=print&'+qs(), '_blank'); });
  loadReport();
});
</script>
