<?php
if (!function_exists('finrep_h')) {
  function finrep_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$costCenters = $db->query("SELECT id,cost_center_code,cost_center_name FROM erp_cost_center WHERE status='Aktif' ORDER BY cost_center_code");
$profitCenters = $db->query("SELECT id,profit_center_code,profit_center_name FROM erp_profit_center WHERE status='Aktif' ORDER BY profit_center_code");
function nrm_opt($value, $label) { return '<option value="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</option>'; }
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.nrm-toolbar{margin-bottom:14px}.nrm-toolbar .select2-container{width:100%!important}.nrm-toolbar .select2-selection--single{height:34px;border-color:#d2d6de;border-radius:0}.nrm-toolbar .select2-selection__rendered{line-height:32px}.nrm-toolbar .select2-selection__arrow{height:32px}.nrm-table th,.nrm-table td{font-size:12px;vertical-align:middle!important;white-space:nowrap}.nrm-group th{background:#1d4ed8!important;color:#fff}.nrm-category th,.nrm-category td{background:#e0f2fe!important;font-weight:700}.nrm-total th,.nrm-total td{background:#f3f4f6!important;font-weight:700}.nrm-grand th,.nrm-grand td{background:#0f766e!important;color:#fff;font-weight:700}.nrm-account{padding-left:18px!important}.nrm-level-3{padding-left:34px!important}
</style>
<section class="content-header">
  <h1>Neraca (Multi Periode) <small>Finance Reports</small></h1>
  <ol class="breadcrumb"><li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li><li><a href="<?=base_index();?>finance-report">Finance Reports</a></li><li class="active">Neraca (Multi Periode)</li></ol>
</section>
<section class="content">
  <div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Multi Period Balance Sheet</h3><div class="box-tools"><button id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</button> <button id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print/PDF</button></div></div>
    <div class="box-body">
      <form id="form_nrm" class="form-horizontal nrm-toolbar">
        <div class="form-group">
          <label class="control-label col-md-1">Start</label><div class="col-md-2"><input type="month" id="start_month" name="start_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <label class="control-label col-md-1">End</label><div class="col-md-2"><input type="month" id="end_month" name="end_month" class="form-control" value="<?=date('Y-m');?>"></div>
          <label class="control-label col-md-1">Cost Ctr</label><div class="col-md-2"><select id="cost_center" name="cost_center" class="form-control select2-filter"><option value="">All</option><?php foreach($costCenters as $r){ echo nrm_opt($r->id, $r->cost_center_code.' - '.$r->cost_center_name); } ?></select></div>
          <label class="control-label col-md-1">Profit Ctr</label><div class="col-md-2"><select id="profit_center" name="profit_center" class="form-control select2-filter"><option value="">All</option><?php foreach($profitCenters as $r){ echo nrm_opt($r->id, $r->profit_center_code.' - '.$r->profit_center_name); } ?></select></div>
        </div>
        <div class="form-group"><div class="col-md-offset-1 col-md-11"><button type="submit" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> Tampilkan</button> <button type="button" id="btn_reset" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</button></div></div>
      </form>
      <p class="text-muted"><i class="fa fa-info-circle"></i> Saldo tiap kolom adalah saldo sampai akhir bulan. Maksimal 12 bulan untuk menjaga performa.</p>
      <div id="nrm_alert" class="alert alert-danger" style="display:none"></div>
      <div id="nrm_result"><div class="text-center text-muted" style="padding:30px">Klik Tampilkan untuk memuat laporan.</div></div>
    </div>
  </div>
</section>
<script>
$(function(){
  $('.select2-filter').select2({width:'100%',allowClear:false});
  function loadReport(){
    $('#nrm_alert').hide().text('');
    $('#nrm_result').html('<div class="text-center" style="padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#btn_filter').prop('disabled',true);
    $.ajax({url:'<?=base_admin();?>modul/finance_report/finance_report_neraca_multi_action.php?act=filter',type:'POST',dataType:'json',data:$('#form_nrm').serialize()}).done(function(res){
      if(res.status==='success') $('#nrm_result').html(res.html);
      else { $('#nrm_result').html('<div class="alert alert-danger">'+(res.message||'Laporan gagal diproses.')+'</div>'); $('#nrm_alert').text(res.message||'Laporan gagal diproses.').show(); }
    }).fail(function(xhr){ $('#nrm_result').html('<div class="alert alert-danger">Server error</div>'); $('#nrm_alert').text(xhr.responseText||'Server error').show(); }).always(function(){ $('#btn_filter').prop('disabled',false); });
  }
  $('#form_nrm').on('submit',function(e){e.preventDefault();loadReport();});
  $('#btn_reset').on('click',function(){ $('#start_month,#end_month').val('<?=date('Y-m');?>'); $('#cost_center,#profit_center').val('').trigger('change'); $('#nrm_result').html('<div class="text-center text-muted" style="padding:30px">Klik Tampilkan untuk memuat laporan.</div>'); });
  $('#btn_excel').on('click',function(){ window.location='<?=base_admin();?>modul/finance_report/finance_report_neraca_multi_action.php?act=excel&'+$('#form_nrm').serialize(); });
  $('#btn_print').on('click',function(){ window.open('<?=base_admin();?>modul/finance_report/finance_report_neraca_multi_action.php?act=print&'+$('#form_nrm').serialize(), '_blank'); });
  loadReport();
});
</script>
