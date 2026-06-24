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
function ar_v_h($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$customers = $db->query("SELECT kode_pemasok,nama FROM customer WHERE kode_pemasok IS NOT NULL ORDER BY nama");
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
  .ar-aging-hero {
    border-radius: 10px;
    background: linear-gradient(135deg, #1b5e8c 0%, #00a65a 100%);
    color: #fff;
    padding: 18px 22px;
    margin-bottom: 15px;
    box-shadow: 0 8px 22px rgba(0,0,0,.12);
  }
  .ar-aging-hero h3 { margin: 0 0 6px; font-weight: 600; }
  .ar-aging-hero p { margin: 0; opacity: .9; }
  .ar-table th,
  .ar-table td {
    font-size: 12px;
    vertical-align: middle !important;
  }
  .ar-kpi .description-header { font-size: 20px; }
  .ar-filter .form-group { margin-bottom: 10px; }
  .ar-detail-table th { width: 36%; background: #f7f9fb; }
</style>

<section class="content-header">
  <h1><?=fin_h('finance_ar_aging', 'AR Aging');?> <small>SAP FI-AR Aging Analysis</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=fin_h('common_home', 'Home');?></a></li>
    <li>Akunting</li>
    <li class="active"><?=fin_h('finance_ar_aging', 'AR Aging');?></li>
  </ol>
</section>

<section class="content">
  <div class="ar-aging-hero">
    <h3><i class="fa fa-clock-o"></i> Receivable Aging Cockpit</h3>
    <p>Monitoring piutang customer berdasarkan due date, outstanding amount, dan bucket umur piutang.</p>
  </div>

  <div class="row ar-kpi">
    <?php foreach (array('total'=>'Total AR','current'=>'Current','d1'=>'1-30','d91'=>'>90') as $id => $label) { ?>
      <div class="col-md-3 col-sm-6">
        <div class="box box-widget">
          <div class="box-body">
            <div class="description-block">
              <span><?=$label;?></span>
              <h5 id="kpi_<?=$id;?>" class="description-header">0.00</h5>
            </div>
          </div>
        </div>
      </div>
    <?php } ?>
  </div>

  <div class="box box-default ar-filter">
    <div class="box-header with-border">
      <h3 class="box-title"><i class="fa fa-filter"></i> Filter AR Aging</h3>
    </div>
    <div class="box-body">
      <form class="form-horizontal" onsubmit="return false;">
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label class="control-label col-sm-4">As Of</label>
              <div class="col-sm-8">
                <div class="input-group date">
                  <input id="as_of_date" class="form-control" value="<?=date('Y-m-d');?>">
                  <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label class="control-label col-sm-3"><?=fin_h('finance_customer', 'Customer');?></label>
              <div class="col-sm-9">
                <select id="customer_code" class="form-control select2">
                  <option value=""></option>
                  <?php foreach ($customers as $c) { ?>
                    <option value="<?=ar_v_h($c->kode_pemasok);?>"><?=ar_v_h($c->kode_pemasok.' - '.$c->nama);?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <button type="button" id="btn_filter" class="btn btn-primary"><i class="fa fa-search"></i> Tampilkan</button>
            <button type="button" id="btn_excel" class="btn btn-success"><i class="fa fa-file-excel-o"></i> <?=fin_h('common_export_excel', 'Export Excel');?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box box-primary">
    <div class="box-header with-border">
      <h3 class="box-title"><i class="fa fa-table"></i> AR Aging Detail</h3>
    </div>
    <div class="box-body">
      <div class="table-responsive">
        <table id="ar_table" class="table table-bordered table-striped table-hover ar-table" style="width:100%">
          <thead>
            <tr>
              <th><?=fin_h('common_no', 'No');?></th>
              <th><?=fin_h('finance_customer', 'Customer');?></th>
              <th><?=fin_h('finance_invoice', 'Invoice');?></th>
              <th>Invoice Date</th>
              <th><?=fin_h('finance_due_date', 'Due Date');?></th>
              <th>Gross</th>
              <th>Paid</th>
              <th>Outstanding</th>
              <th>Age Days</th>
              <th><?=fin_h('common_action', 'Action');?></th>
            </tr>
          </thead>
          <tbody id="ar_body"></tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="modal_ar_detail" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><i class="fa fa-eye"></i> Detail AR Aging</h4>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-condensed ar-detail-table">
          <tbody id="ar_detail_body"></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
$(function() {
  $('.input-group.date').datepicker({format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true});
  $('.select2').select2({width: '100%', allowClear: true, placeholder: 'All Customer'});

  var arTable = null;

  function params() {
    return {
      as_of_date: $('#as_of_date').val(),
      customer_code: $('#customer_code').val()
    };
  }

  function rebuildTable() {
    if (arTable) {
      arTable.destroy();
    }
    arTable = $('#ar_table').DataTable({
      pageLength: 25,
      order: [[8, 'desc']],
      dom: 'Bfrtip',
      buttons: [
        {extend: 'excelHtml5', text: '<i class="fa fa-file-excel-o"></i> Excel', className: 'btn btn-success btn-sm', title: 'AR Aging'},
        {extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'btn btn-default btn-sm', title: 'AR Aging'}
      ],
      columnDefs: [
        {targets: [5, 6, 7, 8], className: 'text-right'},
        {targets: [9], orderable: false, searchable: false, className: 'text-center'}
      ]
    });
  }

  function load() {
    if (arTable) {
      arTable.destroy();
      arTable = null;
    }
    $('#ar_body').html('<tr><td colspan="10" class="text-center">Loading...</td></tr>');
    $.post('<?=base_admin();?>modul/ar_aging/ar_aging_action.php?act=filter', params(), function(response) {
      if (response.status === 'success') {
        $('#ar_body').html(response.html);
        $('#kpi_total').text(response.total);
        $('#kpi_current').text(response.current);
        $('#kpi_d1').text(response.d1);
        $('#kpi_d91').text(response.d91);
        rebuildTable();
      } else {
        $('#ar_body').html('<tr><td colspan="10" class="text-danger text-center">' + response.message + '</td></tr>');
      }
    }, 'json');
  }

  $('#btn_filter').on('click', load);
  $('#btn_excel').on('click', function() {
    window.open('<?=base_admin();?>modul/ar_aging/ar_aging_action.php?act=excel&' + $.param(params()));
  });
  $('#ar_table').on('click', '.btn-detail-ar', function() {
    var button = $(this);
    var rows = [
      ['Customer', button.data('customer')],
      ['Invoice', button.data('invoice')],
      ['Invoice Date', button.data('invoice-date')],
      ['Due Date', button.data('due-date')],
      ['Gross', button.data('gross')],
      ['Paid', button.data('paid')],
      ['Outstanding', button.data('outstanding')],
      ['Age Days', button.data('age')]
    ];
    var html = '';
    $.each(rows, function(_, row) {
      html += '<tr><th>' + $('<div>').text(row[0]).html() + '</th><td>' + $('<div>').text(row[1]).html() + '</td></tr>';
    });
    $('#ar_detail_body').html(html);
    $('#modal_ar_detail').modal('show');
  });

  load();
});
</script>
