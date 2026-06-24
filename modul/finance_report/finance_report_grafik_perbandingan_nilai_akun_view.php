<?php
if (!function_exists('finrep_t')) {
  function finrep_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
}
if (!function_exists('finrep_h')) {
  function finrep_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$title = finrep_t('finance_report_account_value_chart', 'Grafik Perbandingan Nilai Akun');
?>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<style>
.gav-toolbar{margin-bottom:14px}.gav-table th,.gav-table td{font-size:12px;vertical-align:middle!important}.gav-chart-wrap{height:360px;position:relative}.select2-container{width:100%!important}
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
      <h3 class="box-title"><?=finrep_h(finrep_t('finance_account_value_comparison', 'Perbandingan Nilai Akun'));?></h3>
      <div class="box-tools">
        <button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</button>
        <button id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print/PDF</button>
      </div>
    </div>
    <div class="box-body">
      <form id="form_gav" class="form-horizontal gav-toolbar">
        <div class="form-group">
          <label class="control-label col-md-1"><?=finrep_h(finrep_t('finance_start_month', 'Start Month'));?></label>
          <div class="col-md-2"><input type="month" id="start_month" name="start_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <label class="control-label col-md-1"><?=finrep_h(finrep_t('finance_end_month', 'End Month'));?></label>
          <div class="col-md-2"><input type="month" id="end_month" name="end_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <label class="control-label col-md-1"><?=finrep_h(finrep_t('finance_value_mode', 'Mode Nilai'));?></label>
          <div class="col-md-2">
            <select id="value_mode" name="value_mode" class="form-control">
              <option value="ending"><?=finrep_h(finrep_t('finance_month_end_value', 'Nilai Akhir Bulan'));?></option>
              <option value="movement"><?=finrep_h(finrep_t('finance_monthly_movement', 'Mutasi Bulanan'));?></option>
            </select>
          </div>
          <div class="col-md-3"><button type="submit" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> <?=finrep_h(finrep_t('common_show', 'Tampilkan'));?></button></div>
        </div>
        <div class="form-group">
          <label class="control-label col-md-1"><?=finrep_h(finrep_t('finance_account', 'Akun'));?></label>
          <div class="col-md-8"><select id="accounts" name="accounts[]" class="form-control" multiple></select></div>
        </div>
      </form>
      <div id="gav_alert" class="alert alert-danger" style="display:none"></div>
      <div class="gav-chart-wrap"><canvas id="gav_chart"></canvas></div>
      <div id="gav_result"><div class="text-center text-muted" style="padding:30px"><?=finrep_h(finrep_t('finance_select_account_warning', 'Pilih minimal satu akun untuk memuat laporan.'));?></div></div>
    </div>
  </div>
</section>
<script>
$(function(){
  var gavChart=null;
  if($.fn.select2){
    $('#accounts').select2({
      width:'100%',allowClear:true,placeholder:<?=json_encode(finrep_t('finance_search_account', 'Cari akun...'));?>,minimumInputLength:0,
      ajax:{url:'<?=base_admin();?>modul/finance_report/finance_report_grafik_perbandingan_nilai_akun_action.php?act=accounts',type:'POST',dataType:'json',delay:250,data:function(p){return{term:p.term||''};},processResults:function(d){return{results:d.results||[]};}}
    });
  }
  function qs(){return $('#form_gav').serialize();}
  function legacyDatasets(sets){
    return $.map(sets||[],function(ds){
      var color=ds.borderColor||ds.backgroundColor||'#1d4ed8';
      return {label:ds.label,fillColor:'rgba(255,255,255,0)',strokeColor:color,pointColor:color,pointStrokeColor:'#fff',pointHighlightFill:'#fff',pointHighlightStroke:color,data:ds.data||[]};
    });
  }
  function drawChart(payload){
    var ctx=document.getElementById('gav_chart').getContext('2d'), sets=payload.datasets||[];
    if(typeof Chart==='undefined'){ $('#gav_alert').text('Chart library tidak tersedia.').show(); return; }
    if(gavChart && gavChart.destroy) gavChart.destroy();
    if(Chart.version){
      gavChart=new Chart(ctx,{
        type:'line',
        data:{labels:payload.labels||[],datasets:sets},
        options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{position:'bottom'}},scales:{y:{ticks:{callback:function(v){return Number(v).toLocaleString();}}}}}
      });
      return;
    }
    gavChart=new Chart(ctx).Line({labels:payload.labels||[],datasets:legacyDatasets(sets)},{responsive:true,maintainAspectRatio:false,bezierCurve:false,datasetFill:false,scaleLabel:function(v){return Number(v.value).toLocaleString();}});
  }
  function loadReport(){
    $('#gav_alert').hide().text('');
    $('#gav_result').html('<div class="text-center" style="padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#btn_filter').prop('disabled',true);
    $.ajax({url:'<?=base_admin();?>modul/finance_report/finance_report_grafik_perbandingan_nilai_akun_action.php?act=filter',type:'POST',dataType:'json',data:qs()}).done(function(res){
      if(res.status==='success'){ drawChart(res.chart); $('#gav_result').html(res.html); }
      else { $('#gav_result').html('<div class="alert alert-danger">'+(res.message||'Laporan gagal diproses.')+'</div>'); $('#gav_alert').text(res.message||'Laporan gagal diproses.').show(); if(gavChart) gavChart.destroy(); }
    }).fail(function(xhr){ $('#gav_result').html('<div class="alert alert-danger">Server error</div>'); $('#gav_alert').text(xhr.responseText||'Server error').show(); }).always(function(){ $('#btn_filter').prop('disabled',false); });
  }
  $('#form_gav').on('submit',function(e){e.preventDefault();loadReport();});
  $('#btn_excel').on('click',function(){ window.location='<?=base_admin();?>modul/finance_report/finance_report_grafik_perbandingan_nilai_akun_action.php?act=excel&'+qs(); });
  $('#btn_print').on('click',function(){ window.open('<?=base_admin();?>modul/finance_report/finance_report_grafik_perbandingan_nilai_akun_action.php?act=print&'+qs(), '_blank'); });
});
</script>
