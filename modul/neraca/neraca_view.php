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
function nr_view_opt($value, $label) {
  return '<option value="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</option>';
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.nr-kpi .description-block{margin:8px 0}.nr-kpi .description-header{font-size:18px}.nr-table th,.nr-table td{font-size:12px;vertical-align:middle!important}.nr-group th{background:#1d4ed8!important;color:#fff}.nr-category th{background:#e0f2fe!important;color:#0f172a}.nr-total th{background:#f3f4f6}.nr-grand th{background:#0f766e!important;color:#fff}.nr-subtotal th{font-size:12px}.nr-toolbar{margin-bottom:14px}.nr-account-name{padding-left:16px!important}.nr-level-2{padding-left:24px!important}.nr-level-3{padding-left:36px!important}.nr-level-4,.nr-level-5,.nr-level-6{padding-left:48px!important}.nr-toolbar .select2-container{width:100%!important}.nr-toolbar .select2-selection--single{height:34px;border-color:#d2d6de;border-radius:0}.nr-toolbar .select2-selection__rendered{line-height:32px}.nr-toolbar .select2-selection__arrow{height:32px}
</style>

<section class="content-header">
  <h1>Neraca (Standar) <small>Finance Reports</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=fin_h('common_home', 'Home');?></a></li>
    <li>Finance Reports</li>
    <li class="active">Neraca (Standar)</li>
  </ol>
</section>

<section class="content">
  <div class="row nr-kpi">
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Total Aset</span><h5 class="description-header text-blue" id="kpi_aset">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Total Kewajiban</span><h5 class="description-header text-red" id="kpi_kewajiban">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Total Modal</span><h5 class="description-header text-green" id="kpi_modal">0.00</h5></div></div></div></div>
    <div class="col-md-3"><div class="box box-widget"><div class="box-body"><div class="description-block"><span class="description-text">Selisih Balance</span><h5 class="description-header" id="kpi_diff">0.00</h5></div></div></div></div>
  </div>

  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title">Balance Sheet Cockpit</h3>
      <div class="box-tools">
        <button type="button" id="btn_excel" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> <?=fin_h('common_export_excel', 'Export Excel');?></button>
        <button type="button" id="btn_print" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print/PDF</button>
      </div>
    </div>
    <div class="box-body">
      <form id="form_filter_neraca" class="form-horizontal nr-toolbar">
        <div class="form-group">
          <label class="control-label col-md-1">As Of</label>
          <div class="col-md-2">
            <div class="input-group date">
              <input type="text" id="as_of_date" name="as_of_date" class="form-control" value="<?=date('Y-m-d');?>" autocomplete="off">
              <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
            </div>
          </div>
          <label class="control-label col-md-1">Cost Ctr</label>
          <div class="col-md-3"><select id="cost_center" name="cost_center" class="form-control select2-filter"><option value="">All</option><?php foreach($costCenters as $r){ echo nr_view_opt($r->id, $r->cost_center_code.' - '.$r->cost_center_name); } ?></select></div>
          <label class="control-label col-md-1">Profit Ctr</label>
          <div class="col-md-3"><select id="profit_center" name="profit_center" class="form-control select2-filter"><option value="">All</option><?php foreach($profitCenters as $r){ echo nr_view_opt($r->id, $r->profit_center_code.' - '.$r->profit_center_name); } ?></select></div>
        </div>
        <div class="form-group">
          <div class="col-md-offset-1 col-md-11">
            <button type="submit" class="btn btn-primary" id="btn_filter"><i class="fa fa-search"></i> Tampilkan</button>
            <button type="button" class="btn btn-default" id="btn_reset"><i class="fa fa-refresh"></i> <?=fin_h('common_reset', 'Reset');?></button>
          </div>
        </div>
      </form>
      <p class="text-muted"><i class="fa fa-info-circle"></i> Neraca dihitung dari <code>saldo_awal</code> tahun fiskal + jurnal FI POSTED sampai as-of date, dengan kategori resmi dari <code>coa_kategori.kategori_akun</code>.</p>
      <div id="neraca_alert" class="alert alert-danger" style="display:none"></div>
      <div id="result_neraca">
        <div class="text-center text-muted" style="padding:30px">Klik Tampilkan untuk memuat Neraca.</div>
      </div>
    </div>
  </div>
</section>

<script>
$(function(){
  $('.input-group.date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});
  $('.select2-filter').select2({width:'100%',allowClear:false});

  function setDiff(value, balanced){
    $('#kpi_diff').text(value).removeClass('text-green text-red').addClass(balanced === 'Y' ? 'text-green' : 'text-red');
  }

  function loadNeraca(){
    $('#neraca_alert').hide().text('');
    $('#result_neraca').html('<div class="text-center" style="padding:30px"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#btn_filter').prop('disabled', true);
    $.ajax({
      url: '<?=base_admin();?>modul/neraca/neraca_action.php?act=filter',
      type: 'POST',
      dataType: 'json',
      data: $('#form_filter_neraca').serialize()
    }).done(function(res){
      if(res.status === 'success'){
        $('#result_neraca').html(res.html);
        $('#kpi_aset').text(res.total_aset);
        $('#kpi_kewajiban').text(res.total_kewajiban);
        $('#kpi_modal').text(res.total_modal);
        setDiff(res.difference, res.balanced);
      } else {
        $('#result_neraca').html('<div class="alert alert-danger">'+(res.message || 'Neraca gagal diproses.')+'</div>');
        $('#neraca_alert').text(res.message || 'Neraca gagal diproses.').show();
      }
    }).fail(function(xhr){
      $('#result_neraca').html('<div class="alert alert-danger">Server error</div>');
      $('#neraca_alert').text(xhr.responseText || 'Server error').show();
    }).always(function(){
      $('#btn_filter').prop('disabled', false);
    });
  }

  $('#form_filter_neraca').on('submit', function(e){ e.preventDefault(); loadNeraca(); });
  $('#btn_reset').on('click', function(){
    $('#as_of_date').val('<?=date('Y-m-d');?>');
    $('#cost_center').val('').trigger('change');
    $('#profit_center').val('').trigger('change');
    $('#result_neraca').html('<div class="text-center text-muted" style="padding:30px">Klik Tampilkan untuk memuat Neraca.</div>');
    $('#kpi_aset,#kpi_kewajiban,#kpi_modal,#kpi_diff').text('0.00').removeClass('text-green text-red');
  });
  $('#btn_excel').on('click', function(){
    window.location = '<?=base_admin();?>modul/neraca/neraca_action.php?act=excel&' + $('#form_filter_neraca').serialize();
  });
  $('#btn_print').on('click', function(){
    window.open('<?=base_admin();?>modul/neraca/neraca_action.php?act=print&' + $('#form_filter_neraca').serialize(), '_blank');
  });
  loadNeraca();
});
</script>
