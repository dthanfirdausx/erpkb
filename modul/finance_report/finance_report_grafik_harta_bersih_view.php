<?php
if (!function_exists('finrep_t')) {
  function finrep_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
}
if (!function_exists('finrep_h')) {
  function finrep_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$title = finrep_t('finance_report_net_assets_chart', 'Grafik Harta Bersih');
?>
<style>
.gnb-toolbar{margin-bottom:14px}.gnb-table th,.gnb-table td{font-size:12px;vertical-align:middle!important}.gnb-chart-wrap{height:360px;position:relative}
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
      <h3 class="box-title"><?=finrep_h(finrep_t('finance_net_assets_chart', 'Harta Bersih Bulanan'));?></h3>
      <div class="box-tools">
        <button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</button>
        <button id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print/PDF</button>
      </div>
    </div>
    <div class="box-body">
      <form id="form_gnb" class="form-horizontal gnb-toolbar">
        <div class="form-group">
          <label class="control-label col-md-2"><?=finrep_h(finrep_t('finance_start_month', 'Start Month'));?></label>
          <div class="col-md-2"><input type="month" id="start_month" name="start_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <label class="control-label col-md-2"><?=finrep_h(finrep_t('finance_end_month', 'End Month'));?></label>
          <div class="col-md-2"><input type="month" id="end_month" name="end_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <div class="col-md-4"><button type="submit" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> <?=finrep_h(finrep_t('common_show', 'Tampilkan'));?></button></div>
        </div>
      </form>
      <div id="gnb_alert" class="alert alert-danger" style="display:none"></div>
      <div class="alert alert-info"><i class="fa fa-info-circle"></i> <?=finrep_h(finrep_t('finance_net_assets_source_summary', "Sumber: saldo_awal, jurnal_header/detail POSTED, rekening, dan coa_kategori. Harta bersih = aset - kewajiban."));?></div>
      <div class="gnb-chart-wrap"><canvas id="gnb_chart"></canvas></div>
      <div id="gnb_result"><div class="text-center text-muted" style="padding:30px"><?=finrep_h(finrep_t('common_loading', 'Loading...'));?></div></div>
    </div>
  </div>
</section>
<script>
$(function(){
  var gnbChart=null;
  function qs(){return $('#form_gnb').serialize();}
  function legacyDatasets(sets){
    return $.map(sets||[],function(ds){
      var color=ds.borderColor||ds.backgroundColor||'#1d4ed8';
      return {label:ds.label,fillColor:'rgba(255,255,255,0)',strokeColor:color,pointColor:color,pointStrokeColor:'#fff',pointHighlightFill:'#fff',pointHighlightStroke:color,data:ds.data||[]};
    });
  }
  function drawChart(payload){
    var ctx=document.getElementById('gnb_chart').getContext('2d'), sets=payload.datasets||[];
    if(typeof Chart==='undefined'){ $('#gnb_alert').text('Chart library tidak tersedia.').show(); return; }
    if(gnbChart && gnbChart.destroy) gnbChart.destroy();
    if(Chart.version){
      gnbChart=new Chart(ctx,{
        type:'line',
        data:{labels:payload.labels||[],datasets:sets},
        options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{position:'bottom'}},scales:{y:{ticks:{callback:function(v){return Number(v).toLocaleString();}}}}}
      });
      return;
    }
    gnbChart=new Chart(ctx).Line({labels:payload.labels||[],datasets:legacyDatasets(sets)},{responsive:true,maintainAspectRatio:false,bezierCurve:false,datasetFill:false,scaleLabel:function(v){return Number(v.value).toLocaleString();}});
  }
  function loadReport(){
    $('#gnb_alert').hide().text('');
    $('#gnb_result').html('<div class="text-center" style="padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#btn_filter').prop('disabled',true);
    $.ajax({url:'<?=base_admin();?>modul/finance_report/finance_report_grafik_harta_bersih_action.php?act=filter',type:'POST',dataType:'json',data:qs()}).done(function(res){
      if(res.status==='success'){ drawChart(res.chart); $('#gnb_result').html(res.html); }
      else { $('#gnb_result').html('<div class="alert alert-danger">'+(res.message||'Laporan gagal diproses.')+'</div>'); $('#gnb_alert').text(res.message||'Laporan gagal diproses.').show(); if(gnbChart) gnbChart.destroy(); }
    }).fail(function(xhr){ $('#gnb_result').html('<div class="alert alert-danger">Server error</div>'); $('#gnb_alert').text(xhr.responseText||'Server error').show(); }).always(function(){ $('#btn_filter').prop('disabled',false); });
  }
  $('#form_gnb').on('submit',function(e){e.preventDefault();loadReport();});
  $('#btn_excel').on('click',function(){ window.location='<?=base_admin();?>modul/finance_report/finance_report_grafik_harta_bersih_action.php?act=excel&'+qs(); });
  $('#btn_print').on('click',function(){ window.open('<?=base_admin();?>modul/finance_report/finance_report_grafik_harta_bersih_action.php?act=print&'+qs(), '_blank'); });
  loadReport();
});
</script>
