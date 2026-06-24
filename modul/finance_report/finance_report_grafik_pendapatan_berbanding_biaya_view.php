<?php
if (!function_exists('finrep_t')) {
  function finrep_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
}
if (!function_exists('finrep_h')) {
  function finrep_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$title = finrep_t('finance_report_income_vs_expense_chart', 'Grafik Pendapatan berbanding Biaya');
?>
<style>
.gib-toolbar{margin-bottom:14px}.gib-table th,.gib-table td{font-size:12px;vertical-align:middle!important}.gib-chart-wrap{height:360px;position:relative}
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
      <h3 class="box-title"><?=finrep_h(finrep_t('finance_income_vs_expense_chart', 'Pendapatan vs Biaya'));?></h3>
      <div class="box-tools">
        <button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</button>
        <button id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print/PDF</button>
      </div>
    </div>
    <div class="box-body">
      <form id="form_gib" class="form-horizontal gib-toolbar">
        <div class="form-group">
          <label class="control-label col-md-2"><?=finrep_h(finrep_t('finance_start_month', 'Start Month'));?></label>
          <div class="col-md-2"><input type="month" id="start_month" name="start_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <label class="control-label col-md-2"><?=finrep_h(finrep_t('finance_end_month', 'End Month'));?></label>
          <div class="col-md-2"><input type="month" id="end_month" name="end_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <div class="col-md-4"><button type="submit" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> <?=finrep_h(finrep_t('common_show', 'Tampilkan'));?></button></div>
        </div>
      </form>
      <div id="gib_alert" class="alert alert-danger" style="display:none"></div>
      <div class="alert alert-info"><i class="fa fa-info-circle"></i> <?=finrep_h(finrep_t('finance_income_expense_source_summary', "Sumber: jurnal_header/detail POSTED, rekening, dan coa_kategori. Pendapatan dari kategori_akun='pendapatan'; biaya dari kategori_akun='beban'."));?></div>
      <div class="gib-chart-wrap"><canvas id="gib_chart"></canvas></div>
      <div id="gib_result"><div class="text-center text-muted" style="padding:30px"><?=finrep_h(finrep_t('common_loading', 'Loading...'));?></div></div>
    </div>
  </div>
</section>
<script>
$(function(){
  var gibChart=null;
  function qs(){return $('#form_gib').serialize();}
  function legacyDataset(ds, color, fill){
    return {
      label: ds.label,
      fillColor: fill,
      strokeColor: color,
      pointColor: color,
      pointStrokeColor: '#fff',
      pointHighlightFill: '#fff',
      pointHighlightStroke: color,
      data: ds.data || []
    };
  }
  function drawChart(payload){
    var canvas=document.getElementById('gib_chart'), ctx=canvas.getContext('2d'), sets=payload.datasets||[];
    if(typeof Chart==='undefined'){
      $('#gib_alert').text('Chart library tidak tersedia.').show();
      return;
    }
    if(gibChart && gibChart.destroy) gibChart.destroy();
    if(Chart.version){
      gibChart=new Chart(ctx,{
        type:'bar',
        data:{labels:payload.labels||[],datasets:sets},
        options:{
          responsive:true,
          maintainAspectRatio:false,
          interaction:{mode:'index',intersect:false},
          plugins:{legend:{position:'bottom'}},
          scales:{y:{ticks:{callback:function(v){return Number(v).toLocaleString();}}}}
        }
      });
      return;
    }
    var legacyData={labels:payload.labels||[],datasets:[
      legacyDataset(sets[0]||{label:'Pendapatan',data:[]}, '#1d4ed8', 'rgba(29,78,216,0.12)'),
      legacyDataset(sets[1]||{label:'Biaya',data:[]}, '#f97316', 'rgba(249,115,22,0.12)'),
      legacyDataset(sets[2]||{label:'Laba/Rugi',data:[]}, '#0f766e', 'rgba(15,118,110,0.06)')
    ]};
    gibChart=new Chart(ctx).Line(legacyData,{responsive:true,maintainAspectRatio:false,bezierCurve:false,datasetFill:false,scaleLabel:function(v){return Number(v.value).toLocaleString();}});
  }
  function loadReport(){
    $('#gib_alert').hide().text('');
    $('#gib_result').html('<div class="text-center" style="padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#btn_filter').prop('disabled',true);
    $.ajax({url:'<?=base_admin();?>modul/finance_report/finance_report_grafik_pendapatan_berbanding_biaya_action.php?act=filter',type:'POST',dataType:'json',data:qs()}).done(function(res){
      if(res.status==='success'){ drawChart(res.chart); $('#gib_result').html(res.html); }
      else { $('#gib_result').html('<div class="alert alert-danger">'+(res.message||'Laporan gagal diproses.')+'</div>'); $('#gib_alert').text(res.message||'Laporan gagal diproses.').show(); if(gibChart) gibChart.destroy(); }
    }).fail(function(xhr){ $('#gib_result').html('<div class="alert alert-danger">Server error</div>'); $('#gib_alert').text(xhr.responseText||'Server error').show(); }).always(function(){ $('#btn_filter').prop('disabled',false); });
  }
  $('#form_gib').on('submit',function(e){e.preventDefault();loadReport();});
  $('#btn_excel').on('click',function(){ window.location='<?=base_admin();?>modul/finance_report/finance_report_grafik_pendapatan_berbanding_biaya_action.php?act=excel&'+qs(); });
  $('#btn_print').on('click',function(){ window.open('<?=base_admin();?>modul/finance_report/finance_report_grafik_pendapatan_berbanding_biaya_action.php?act=print&'+qs(), '_blank'); });
  loadReport();
});
</script>
