<?php
if (!function_exists('finrep_t')) {
  function finrep_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
}
if (!function_exists('finrep_h')) {
  function finrep_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$title = finrep_t('finance_report_roe_chart', 'Grafik Pengembalian pada Modal');
?>
<style>
.groe-toolbar{margin-bottom:14px}.groe-table th,.groe-table td{font-size:12px;vertical-align:middle!important}.groe-chart-wrap{height:360px;position:relative}
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
      <h3 class="box-title"><?=finrep_h(finrep_t('finance_roe_monthly_chart', 'ROE Bulanan'));?></h3>
      <div class="box-tools">
        <button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</button>
        <button id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print/PDF</button>
      </div>
    </div>
    <div class="box-body">
      <form id="form_groe" class="form-horizontal groe-toolbar">
        <div class="form-group">
          <label class="control-label col-md-2"><?=finrep_h(finrep_t('finance_start_month', 'Start Month'));?></label>
          <div class="col-md-2"><input type="month" id="start_month" name="start_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <label class="control-label col-md-2"><?=finrep_h(finrep_t('finance_end_month', 'End Month'));?></label>
          <div class="col-md-2"><input type="month" id="end_month" name="end_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <div class="col-md-4"><button type="submit" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> <?=finrep_h(finrep_t('common_show', 'Tampilkan'));?></button></div>
        </div>
      </form>
      <div id="groe_alert" class="alert alert-danger" style="display:none"></div>
      <div class="alert alert-info"><i class="fa fa-info-circle"></i> <?=finrep_h(finrep_t('finance_roe_source_summary', "Sumber: jurnal_header/detail POSTED, saldo_awal, rekening, dan coa_kategori. ROE = laba bersih / (modal akhir bulan + laba berjalan YTD)."));?></div>
      <div class="groe-chart-wrap"><canvas id="groe_chart"></canvas></div>
      <div id="groe_result"><div class="text-center text-muted" style="padding:30px"><?=finrep_h(finrep_t('common_loading', 'Loading...'));?></div></div>
    </div>
  </div>
</section>
<script>
$(function(){
  var groeChart=null;
  function qs(){return $('#form_groe').serialize();}
  function legacyDatasets(sets){
    return $.map(sets||[],function(ds){
      var color=ds.borderColor||ds.backgroundColor||'#7c3aed';
      return {label:ds.label,fillColor:'rgba(255,255,255,0)',strokeColor:color,pointColor:color,pointStrokeColor:'#fff',pointHighlightFill:'#fff',pointHighlightStroke:color,data:ds.data||[]};
    });
  }
  function drawChart(payload){
    var ctx=document.getElementById('groe_chart').getContext('2d'), sets=payload.datasets||[];
    if(typeof Chart==='undefined'){ $('#groe_alert').text('Chart library tidak tersedia.').show(); return; }
    if(groeChart && groeChart.destroy) groeChart.destroy();
    if(Chart.version){
      groeChart=new Chart(ctx,{
        type:'line',
        data:{labels:payload.labels||[],datasets:sets},
        options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{position:'bottom'}},scales:{y:{ticks:{callback:function(v){return Number(v).toLocaleString()+'%';}}}}}
      });
      return;
    }
    groeChart=new Chart(ctx).Line({labels:payload.labels||[],datasets:legacyDatasets(sets)},{responsive:true,maintainAspectRatio:false,bezierCurve:false,datasetFill:false,scaleLabel:function(v){return Number(v.value).toLocaleString()+'%';}});
  }
  function loadReport(){
    $('#groe_alert').hide().text('');
    $('#groe_result').html('<div class="text-center" style="padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#btn_filter').prop('disabled',true);
    $.ajax({url:'<?=base_admin();?>modul/finance_report/finance_report_grafik_pengembalian_pada_modal_action.php?act=filter',type:'POST',dataType:'json',data:qs()}).done(function(res){
      if(res.status==='success'){ drawChart(res.chart); $('#groe_result').html(res.html); }
      else { $('#groe_result').html('<div class="alert alert-danger">'+(res.message||'Laporan gagal diproses.')+'</div>'); $('#groe_alert').text(res.message||'Laporan gagal diproses.').show(); if(groeChart) groeChart.destroy(); }
    }).fail(function(xhr){ $('#groe_result').html('<div class="alert alert-danger">Server error</div>'); $('#groe_alert').text(xhr.responseText||'Server error').show(); }).always(function(){ $('#btn_filter').prop('disabled',false); });
  }
  $('#form_groe').on('submit',function(e){e.preventDefault();loadReport();});
  $('#btn_excel').on('click',function(){ window.location='<?=base_admin();?>modul/finance_report/finance_report_grafik_pengembalian_pada_modal_action.php?act=excel&'+qs(); });
  $('#btn_print').on('click',function(){ window.open('<?=base_admin();?>modul/finance_report/finance_report_grafik_pengembalian_pada_modal_action.php?act=print&'+qs(), '_blank'); });
  loadReport();
});
</script>
