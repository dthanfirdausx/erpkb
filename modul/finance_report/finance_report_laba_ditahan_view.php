<?php
if (!function_exists('finrep_t')) {
  function finrep_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
}
if (!function_exists('finrep_h')) {
  function finrep_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$title = finrep_t('finance_report_retained_earnings', 'Laba Ditahan');
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.fre-card{border:1px solid #d2d6de;border-left:4px solid #1d4ed8;background:#fff;padding:12px;margin-bottom:14px;min-height:86px}
.fre-card.green{border-left-color:#0f766e}.fre-card.orange{border-left-color:#f97316}.fre-card.gray{border-left-color:#64748b}
.fre-card .label-text{font-size:12px;color:#6b7280;text-transform:uppercase}
.fre-card .value-text{font-size:20px;font-weight:700;margin-top:4px}
.fre-table th,.fre-table td{font-size:12px;vertical-align:middle!important}
.fre-section th{background:#1d4ed8!important;color:#fff}
.fre-total th,.fre-total td{background:#f3f4f6!important;font-weight:700}
.fre-grand th,.fre-grand td{background:#0f766e!important;color:#fff;font-weight:700}
.fre-toolbar{margin-bottom:14px}
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
      <h3 class="box-title"><?=finrep_h(finrep_t('finance_retained_earnings_statement', 'Laporan Laba Ditahan'));?></h3>
      <div class="box-tools">
        <button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</button>
        <button id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print/PDF</button>
      </div>
    </div>
    <div class="box-body">
      <form id="form_fre" class="form-horizontal fre-toolbar">
        <div class="form-group">
          <label class="control-label col-md-1"><?=finrep_h(finrep_t('finance_as_of_year', 'As Of Year'));?></label>
          <div class="col-md-2"><input type="number" id="as_of_year" name="as_of_year" class="form-control" value="<?=date('Y');?>" min="2000" max="2100"></div>
          <label class="control-label col-md-1"><?=finrep_h(finrep_t('finance_as_of_date', 'As Of Date'));?></label>
          <div class="col-md-2"><input type="date" id="as_of_date" name="as_of_date" class="form-control" value="<?=date('Y-m-d');?>"></div>
          <div class="col-md-6">
            <button type="submit" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> <?=finrep_h(finrep_t('common_show', 'Tampilkan'));?></button>
            <button type="button" id="btn_year_end" class="btn btn-default"><i class="fa fa-calendar"></i> <?=finrep_h(finrep_t('finance_year_end', 'Akhir Tahun'));?></button>
          </div>
        </div>
      </form>
      <p class="text-muted"><i class="fa fa-info-circle"></i> <?=finrep_h(finrep_t('finance_retained_earnings_hint', 'Laba ditahan dihitung dari akun mapping laba ditahan, saldo_awal, dan akumulasi laba/rugi jurnal POSTED.'));?></p>
      <div id="fre_alert" class="alert alert-danger" style="display:none"></div>
      <div id="fre_result"><div class="text-center text-muted" style="padding:30px"><?=finrep_h(finrep_t('finance_click_show_report', 'Klik Tampilkan untuk memuat laporan.'));?></div></div>
    </div>
  </div>
</section>
<script>
$(function(){
  function qs(){return $('#form_fre').serialize();}
  function loadReport(){
    $('#fre_alert').hide().text('');
    $('#fre_result').html('<div class="text-center" style="padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#btn_filter').prop('disabled',true);
    $.ajax({url:'<?=base_admin();?>modul/finance_report/finance_report_laba_ditahan_action.php?act=filter',type:'POST',dataType:'json',data:qs()}).done(function(res){
      if(res.status==='success') $('#fre_result').html(res.html);
      else { $('#fre_result').html('<div class="alert alert-danger">'+(res.message||'Laporan gagal diproses.')+'</div>'); $('#fre_alert').text(res.message||'Laporan gagal diproses.').show(); }
    }).fail(function(xhr){ $('#fre_result').html('<div class="alert alert-danger">Server error</div>'); $('#fre_alert').text(xhr.responseText||'Server error').show(); }).always(function(){ $('#btn_filter').prop('disabled',false); });
  }
  $('#form_fre').on('submit',function(e){e.preventDefault();loadReport();});
  $('#btn_year_end').on('click',function(){ var y=$('#as_of_year').val()||'<?=date('Y');?>'; $('#as_of_date').val(y+'-12-31'); loadReport(); });
  $('#as_of_year').on('change',function(){ if(!$('#as_of_date').val()) $('#as_of_date').val($(this).val()+'-12-31'); });
  $('#btn_excel').on('click',function(){ window.location='<?=base_admin();?>modul/finance_report/finance_report_laba_ditahan_action.php?act=excel&'+qs(); });
  $('#btn_print').on('click',function(){ window.open('<?=base_admin();?>modul/finance_report/finance_report_laba_ditahan_action.php?act=print&'+qs(), '_blank'); });
  loadReport();
});
</script>
