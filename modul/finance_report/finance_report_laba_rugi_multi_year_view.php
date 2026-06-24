<?php if (!function_exists('finrep_h')) { function finrep_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); } } ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.lrmy-table th,.lrmy-table td{font-size:12px;vertical-align:middle!important;white-space:nowrap}.lrmy-group th{background:#1d4ed8!important;color:#fff}.lrmy-category th,.lrmy-category td{background:#e0f2fe!important;font-weight:700}.lrmy-total th,.lrmy-total td{background:#f3f4f6!important;font-weight:700}.lrmy-account{padding-left:18px!important}.lrmy-level-3{padding-left:34px!important}.lrmy-toolbar{margin-bottom:14px}
</style>
<section class="content-header">
  <h1>Laba/Rugi (Multi Year) <small>Finance Reports</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li><li><a href="<?=base_index();?>finance-report">Finance Reports</a></li><li class="active">Laba/Rugi (Multi Year)</li></ol>
</section>
<section class="content">
  <div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Comparative Annual Profit & Loss</h3><div class="box-tools"><button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</button> <button id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print/PDF</button></div></div>
    <div class="box-body">
      <form id="form_lrmy" class="form-horizontal lrmy-toolbar">
        <div class="form-group">
          <label class="control-label col-md-1">Start Year</label><div class="col-md-2"><input type="number" id="start_year" name="start_year" class="form-control" value="<?=(int)date('Y')-2;?>" min="2000" max="2100"></div>
          <label class="control-label col-md-1">End Year</label><div class="col-md-2"><input type="number" id="end_year" name="end_year" class="form-control" value="<?=date('Y');?>" min="2000" max="2100"></div>
          <div class="col-md-6"><button type="submit" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> Tampilkan</button> <button type="button" id="btn_default" class="btn btn-default"><i class="fa fa-refresh"></i> Default 3 Tahun</button></div>
        </div>
      </form>
      <p class="text-muted"><i class="fa fa-info-circle"></i> Default 3 tahun terakhir. Boundary tahun fiskal memakai <code>erp_financial_period</code> jika tersedia; fallback calendar year untuk variant K4.</p>
      <div id="lrmy_alert" class="alert alert-danger" style="display:none"></div>
      <div id="lrmy_result"><div class="text-center text-muted" style="padding:30px">Klik Tampilkan untuk memuat laporan.</div></div>
    </div>
  </div>
</section>
<script>
$(function(){
  function loadReport(){
    $('#lrmy_alert').hide().text('');
    $('#lrmy_result').html('<div class="text-center" style="padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#btn_filter').prop('disabled',true);
    $.ajax({url:'<?=base_admin();?>modul/finance_report/finance_report_laba_rugi_multi_year_action.php?act=filter',type:'POST',dataType:'json',data:$('#form_lrmy').serialize()}).done(function(res){
      if(res.status==='success') $('#lrmy_result').html(res.html);
      else { $('#lrmy_result').html('<div class="alert alert-danger">'+(res.message||'Laporan gagal diproses.')+'</div>'); $('#lrmy_alert').text(res.message||'Laporan gagal diproses.').show(); }
    }).fail(function(xhr){ $('#lrmy_result').html('<div class="alert alert-danger">Server error</div>'); $('#lrmy_alert').text(xhr.responseText||'Server error').show(); }).always(function(){ $('#btn_filter').prop('disabled',false); });
  }
  $('#form_lrmy').on('submit',function(e){e.preventDefault();loadReport();});
  $('#btn_default').on('click',function(){ $('#end_year').val('<?=date('Y');?>'); $('#start_year').val('<?=(int)date('Y')-2;?>'); loadReport(); });
  $('#btn_excel').on('click',function(){ window.location='<?=base_admin();?>modul/finance_report/finance_report_laba_rugi_multi_year_action.php?act=excel&'+$('#form_lrmy').serialize(); });
  $('#btn_print').on('click',function(){ window.open('<?=base_admin();?>modul/finance_report/finance_report_laba_rugi_multi_year_action.php?act=print&'+$('#form_lrmy').serialize(), '_blank'); });
  loadReport();
});
</script>
