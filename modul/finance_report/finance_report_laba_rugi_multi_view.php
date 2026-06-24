<?php
if (!function_exists('finrep_h')) {
  function finrep_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$costCenters = $db->query("SELECT id,cost_center_code,cost_center_name FROM erp_cost_center WHERE status='Aktif' ORDER BY cost_center_code");
$profitCenters = $db->query("SELECT id,profit_center_code,profit_center_name FROM erp_profit_center WHERE status='Aktif' ORDER BY profit_center_code");
function lrm_opt($value, $label) {
  return '<option value="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</option>';
}
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.lrm-kpi .description-block{margin:8px 0}.lrm-kpi .description-header{font-size:18px}.lrm-toolbar{margin-bottom:14px}.lrm-toolbar .select2-container{width:100%!important}.lrm-toolbar .select2-selection--single{height:34px;border-color:#d2d6de;border-radius:0}.lrm-toolbar .select2-selection__rendered{line-height:32px}.lrm-toolbar .select2-selection__arrow{height:32px}.lrm-table th,.lrm-table td{font-size:12px;vertical-align:middle!important;white-space:nowrap}.lrm-group th{background:#1d4ed8!important;color:#fff}.lrm-category th,.lrm-category td{background:#e0f2fe!important;font-weight:700}.lrm-total th,.lrm-total td{background:#f3f4f6!important;font-weight:700}.lrm-account{padding-left:18px!important}.lrm-level-3{padding-left:34px!important}.lrm-net th,.lrm-net td{font-size:13px;font-weight:700}
</style>

<section class="content-header">
  <h1>Laba/Rugi (Multi Periode) <small>Finance Reports</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="<?=base_index();?>finance-report">Finance Reports</a></li>
    <li class="active">Laba/Rugi (Multi Periode)</li>
  </ol>
</section>

<section class="content">
  <div class="row lrm-kpi">
    <div class="col-md-4"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Jumlah Periode</span><h5 class="description-header" id="kpi_period_count">0</h5></div></div></div></div>
    <div class="col-md-4"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Total Laba/Rugi</span><h5 class="description-header" id="kpi_net_total">0.00</h5></div></div></div></div>
    <div class="col-md-4"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Status</span><h5 class="description-header text-green">POSTED</h5></div></div></div></div>
  </div>

  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title">Multi Period Profit & Loss</h3>
      <div class="box-tools">
        <button type="button" id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</button>
        <button type="button" id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print/PDF</button>
      </div>
    </div>
    <div class="box-body">
      <form id="form_lrm" class="form-horizontal lrm-toolbar">
        <div class="form-group">
          <label class="control-label col-md-1">Start</label>
          <div class="col-md-2"><input type="month" id="start_month" name="start_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <label class="control-label col-md-1">End</label>
          <div class="col-md-2"><input type="month" id="end_month" name="end_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <label class="control-label col-md-1">Cost Ctr</label>
          <div class="col-md-2"><select id="cost_center" name="cost_center" class="form-control select2-filter"><option value="">All</option><?php foreach($costCenters as $r){ echo lrm_opt($r->id, $r->cost_center_code.' - '.$r->cost_center_name); } ?></select></div>
          <label class="control-label col-md-1">Profit Ctr</label>
          <div class="col-md-2"><select id="profit_center" name="profit_center" class="form-control select2-filter"><option value="">All</option><?php foreach($profitCenters as $r){ echo lrm_opt($r->id, $r->profit_center_code.' - '.$r->profit_center_name); } ?></select></div>
        </div>
        <div class="form-group">
          <div class="col-md-offset-1 col-md-11">
            <button type="submit" class="btn btn-primary" id="btn_filter"><i class="fa fa-search"></i> Tampilkan</button>
            <button type="button" class="btn btn-default" id="btn_reset"><i class="fa fa-refresh"></i> Reset</button>
          </div>
        </div>
      </form>
      <p class="text-muted"><i class="fa fa-info-circle"></i> Maksimal 12 bulan. Nilai diambil dari jurnal POSTED dan kategori P&L resmi di <code>coa_kategori.kategori_akun</code>.</p>
      <div id="lrm_alert" class="alert alert-danger" style="display:none"></div>
      <div id="lrm_result"><div class="text-center text-muted" style="padding:30px">Klik Tampilkan untuk memuat laporan.</div></div>
    </div>
  </div>
</section>

<script>
$(function(){
  $('.select2-filter').select2({width:'100%',allowClear:false});
  function loadReport(){
    $('#lrm_alert').hide().text('');
    $('#lrm_result').html('<div class="text-center" style="padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#btn_filter').prop('disabled', true);
    $.ajax({
      url:'<?=base_admin();?>modul/finance_report/finance_report_laba_rugi_multi_action.php?act=filter',
      type:'POST',
      dataType:'json',
      data:$('#form_lrm').serialize()
    }).done(function(res){
      if(res.status === 'success'){
        $('#lrm_result').html(res.html);
        $('#kpi_period_count').text(res.period_count);
        $('#kpi_net_total').text(res.net_total).toggleClass('text-green', parseFloat(String(res.net_total).replace(/,/g,'')) >= 0).toggleClass('text-red', parseFloat(String(res.net_total).replace(/,/g,'')) < 0);
      } else {
        $('#lrm_result').html('<div class="alert alert-danger">'+(res.message || 'Laporan gagal diproses.')+'</div>');
        $('#lrm_alert').text(res.message || 'Laporan gagal diproses.').show();
      }
    }).fail(function(xhr){
      $('#lrm_result').html('<div class="alert alert-danger">Server error</div>');
      $('#lrm_alert').text(xhr.responseText || 'Server error').show();
    }).always(function(){
      $('#btn_filter').prop('disabled', false);
    });
  }
  $('#form_lrm').on('submit', function(e){ e.preventDefault(); loadReport(); });
  $('#btn_reset').on('click', function(){
    $('#start_month,#end_month').val('<?=date('Y-m');?>');
    $('#cost_center,#profit_center').val('').trigger('change');
    $('#kpi_period_count').text('0');
    $('#kpi_net_total').text('0.00').removeClass('text-green text-red');
    $('#lrm_result').html('<div class="text-center text-muted" style="padding:30px">Klik Tampilkan untuk memuat laporan.</div>');
  });
  $('#btn_excel').on('click', function(){ window.location='<?=base_admin();?>modul/finance_report/finance_report_laba_rugi_multi_action.php?act=excel&'+$('#form_lrm').serialize(); });
  $('#btn_print').on('click', function(){ window.open('<?=base_admin();?>modul/finance_report/finance_report_laba_rugi_multi_action.php?act=print&'+$('#form_lrm').serialize(), '_blank'); });
  loadReport();
});
</script>
