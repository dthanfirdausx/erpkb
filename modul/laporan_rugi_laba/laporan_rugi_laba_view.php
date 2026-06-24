<?php
if (!function_exists('fin_t')) {
  function fin_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('fin_h')) {
  function fin_h($key, $fallback = '') { return htmlspecialchars((string) fin_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('fin_js')) {
  function fin_js($key, $fallback = '') { return json_encode(fin_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
$costCenters = $db->query("SELECT id,cost_center_code,cost_center_name FROM erp_cost_center WHERE status='Aktif' ORDER BY cost_center_code");
$profitCenters = $db->query("SELECT id,profit_center_code,profit_center_name FROM erp_profit_center WHERE status='Aktif' ORDER BY profit_center_code");
function lr_view_opt($value, $label) {
  return '<option value="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</option>';
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.lr-kpi .description-block{margin:8px 0}.lr-kpi .description-header{font-size:18px}.lr-table th,.lr-table td{font-size:12px;vertical-align:middle!important}.lr-section th{background:#1d4ed8!important;color:#fff}.lr-category th{background:#f3f4f6!important;color:#374151}.lr-account-name{padding-left:16px!important}.lr-level-2{padding-left:24px!important}.lr-level-3{padding-left:36px!important}.lr-level-4,.lr-level-5,.lr-level-6{padding-left:48px!important}.lr-toolbar{margin-bottom:14px}.lr-result th{font-size:13px}.lr-subtotal th{font-size:12px}.lr-toolbar .select2-container{width:100%!important}.lr-toolbar .select2-selection--single{height:34px;border-color:#d2d6de;border-radius:0}.lr-toolbar .select2-selection__rendered{line-height:32px}.lr-toolbar .select2-selection__arrow{height:32px}
</style>

<section class="content-header">
  <h1>Laba/Rugi (Standar) <small>Finance Reports</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?= base_index(); ?>"><i class="fa fa-dashboard"></i> <?=fin_h('common_home', 'Home');?></a></li>
    <li>Finance Reports</li>
    <li class="active">Laba/Rugi (Standar)</li>
  </ol>
</section>

<section class="content">
  <div class="row lr-kpi">
    <div class="col-md-2"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Pendapatan</span><h5 class="description-header text-blue" id="kpi_pendapatan">0.00</h5></div></div></div></div>
    <div class="col-md-2"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Laba Kotor</span><h5 class="description-header" id="kpi_gross">0.00</h5></div></div></div></div>
    <div class="col-md-2"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Laba Operasi</span><h5 class="description-header" id="kpi_operating">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Laba Sebelum Pajak</span><h5 class="description-header" id="kpi_before_tax">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Laba Bersih</span><h5 class="description-header" id="kpi_net">0.00</h5></div></div></div></div>
  </div>

  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title">Profit & Loss Cockpit</h3>
      <div class="box-tools">
        <button type="button" id="btn_export" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> <?=fin_h('common_export_excel', 'Export Excel');?></button>
        <button type="button" id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print/PDF</button>
      </div>
    </div>
    <div class="box-body">
      <form id="form_laba_rugi" class="form-horizontal lr-toolbar">
        <div class="form-group">
          <label class="control-label col-md-1">Periode</label>
          <div class="col-md-2"><div class="input-group date"><input type="text" class="form-control" id="start_date" name="start_date" value="<?= date('Y-m-01'); ?>" autocomplete="off" required><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <div class="col-md-2"><div class="input-group date"><input type="text" class="form-control" id="end_date" name="end_date" value="<?= date('Y-m-d'); ?>" autocomplete="off" required><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div></div>
          <label class="control-label col-md-1">Doc Type</label>
          <div class="col-md-2"><select id="document_type" name="document_type" class="form-control select2-filter"><option value="">All</option><option value="SA">SA - General Ledger</option><option value="AJE">AJE - Adjustment</option><option value="DR">DR - Customer Invoice</option><option value="KR">KR - Vendor Invoice</option><option value="KZ">KZ - Vendor Payment</option><option value="DZ">DZ - Incoming Payment</option><option value="CM">CM - Credit Memo</option><option value="DM">DM - Debit Memo</option><option value="RV">RV - Reversal</option></select></div>
          <label class="control-label col-md-1">Source</label>
          <div class="col-md-2"><input id="source_module" name="source_module" class="form-control" placeholder="SALES, GI, IMPORT_GL"></div>
        </div>
        <div class="form-group">
          <label class="control-label col-md-1">Cost Ctr</label>
          <div class="col-md-3"><select id="cost_center" name="cost_center" class="form-control select2-filter"><option value="">All</option><?php foreach($costCenters as $r){ echo lr_view_opt($r->id, $r->cost_center_code.' - '.$r->cost_center_name); } ?></select></div>
          <label class="control-label col-md-1">Profit Ctr</label>
          <div class="col-md-3"><select id="profit_center" name="profit_center" class="form-control select2-filter"><option value="">All</option><?php foreach($profitCenters as $r){ echo lr_view_opt($r->id, $r->profit_center_code.' - '.$r->profit_center_name); } ?></select></div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-primary" id="btn_tampilkan"><i class="fa fa-search"></i> Tampilkan</button>
            <button type="button" class="btn btn-default" id="btn_reset"><i class="fa fa-refresh"></i> <?=fin_h('common_reset', 'Reset');?></button>
          </div>
        </div>
      </form>
      <p class="text-muted"><i class="fa fa-info-circle"></i> Laporan membaca akun pendapatan dan beban dari jurnal FI POSTED. Kategori resmi diambil dari <code>coa_kategori.kategori_akun</code>; draft dan header REVERSED tidak masuk laba/rugi.</p>
      <div id="laporan_error" class="alert alert-danger" style="display:none"></div>
      <div id="laporan_loading" class="text-center" style="display:none;padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Memuat laporan...</p></div>
      <div id="hasil_laba_rugi"><div class="text-center text-muted" style="padding:30px">Klik Tampilkan untuk memuat laporan.</div></div>
    </div>
  </div>
</section>

<script>
$(function () {
  $('.input-group.date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});
  $('.select2-filter').select2({width:'100%',allowClear:false});
  function setKpi(selector, value){
    var num = parseFloat(String(value).replace(/,/g,'')) || 0;
    $(selector).text(value).toggleClass('text-green', num >= 0).toggleClass('text-red', num < 0);
  }
  function loadLaporan() {
    $('#laporan_error').hide().html('');
    $('#hasil_laba_rugi').html('');
    $('#laporan_loading').show();
    $('#btn_tampilkan').prop('disabled', true);
    $.ajax({
      url: '<?= base_admin(); ?>modul/laporan_rugi_laba/laporan_rugi_laba_action.php?act=filter',
      type: 'POST',
      dataType: 'json',
      data: $('#form_laba_rugi').serialize()
    }).done(function (response) {
      if (response.status === 'success') {
        $('#hasil_laba_rugi').html(response.html);
        $('#kpi_pendapatan').text(response.pendapatan);
        setKpi('#kpi_gross', response.gross);
        setKpi('#kpi_operating', response.operating);
        setKpi('#kpi_before_tax', response.before_tax);
        setKpi('#kpi_net', response.net);
        return;
      }
      $('#laporan_error').html(response.message || 'Laporan tidak dapat diproses.').show();
      $('#hasil_laba_rugi').html('<div class="alert alert-danger">'+(response.message || 'Laporan tidak dapat diproses.')+'</div>');
    }).fail(function (xhr) {
      $('#laporan_error').html(xhr.responseText || 'Terjadi kesalahan saat mengambil laporan.').show();
    }).always(function () {
      $('#laporan_loading').hide();
      $('#btn_tampilkan').prop('disabled', false);
    });
  }
  $('#form_laba_rugi').on('submit', function (event) { event.preventDefault(); loadLaporan(); });
  $('#btn_export').on('click', function () {
    var qs = $('#form_laba_rugi').serialize();
    window.location = '<?= base_admin(); ?>modul/laporan_rugi_laba/laporan_rugi_laba_action.php?act=excel&' + qs;
  });
  $('#btn_print').on('click', function () {
    var qs = $('#form_laba_rugi').serialize();
    window.open('<?= base_admin(); ?>modul/laporan_rugi_laba/laporan_rugi_laba_action.php?act=print&' + qs, '_blank');
  });
  $('#btn_reset').on('click', function(){
    $('#start_date').val('<?=date('Y-m-01');?>');
    $('#end_date').val('<?=date('Y-m-d');?>');
    $('#document_type').val('').trigger('change');
    $('#source_module').val('');
    $('#cost_center').val('').trigger('change');
    $('#profit_center').val('').trigger('change');
    $('#hasil_laba_rugi').html('<div class="text-center text-muted" style="padding:30px">Klik Tampilkan untuk memuat laporan.</div>');
    $('#kpi_pendapatan,#kpi_gross,#kpi_operating,#kpi_before_tax,#kpi_net').text('0.00').removeClass('text-green text-red');
  });
  loadLaporan();
});
</script>
