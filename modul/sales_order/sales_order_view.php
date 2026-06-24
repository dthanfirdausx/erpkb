<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
function so_view_h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$canInsert = false;
foreach ($db->fetch_all("sys_menu") as $isi) {
  if (uri_segment(1) == $isi->url && $role_act["insert_act"] == "Y") {
    $canInsert = true;
    break;
  }
}

$summary = $db->fetch(
  "SELECT COUNT(*) AS total_docs,
          COALESCE(SUM(qty_so),0) AS total_qty,
          COALESCE(SUM(qty_kirim),0) AS shipped_qty,
          SUM(CASE WHEN status_so='SUDAH DIKIRIM' THEN 1 ELSE 0 END) AS delivered_docs
   FROM v_sales_status
   WHERE so_date BETWEEN ? AND ?",
  array($defaultFrom, $defaultTo)
);
$customers = $db->query("SELECT kode_penerima,nama FROM penerima ORDER BY nama");
?>
<style>
.so-hero{background:linear-gradient(135deg,#0f766e,#1d4ed8);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.18)}
.so-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.so-hero p{margin:0;opacity:.92}
.so-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
.so-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.so-kpi strong{display:block;font-size:23px;margin-top:6px;color:#111827}
.so-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}.so-filter .form-group{margin-bottom:12px}
#dtb_sales_order th,#dtb_sales_order td{font-size:12px;vertical-align:middle}.select2-container{width:100%!important}.so-action .btn{margin-right:3px;margin-bottom:3px}
</style>

<section class="content-header">
  <h1><?=sd_h('sales_order', 'Sales Order');?> <small>SAP SD Order Management</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a></li>
    <li><a href="#">Sales & Distribution</a></li>
    <li class="active"><?=sd_h('sales_order', 'Sales Order');?></li>
  </ol>
</section>

<section class="content">
  <div class="so-hero">
    <div class="row">
      <div class="col-md-8">
        <h1><?=sd_h('sales_order', 'Sales Order');?></h1>
        <p>Monitoring sales order dari quotation/customer PO sampai produksi dan pengiriman. Filter dibuat konsisten dengan modul Sales & Distribution lain.</p>
      </div>
      <div class="col-md-4 text-right">
        <?php if ($canInsert) { ?>
          <a href="<?=base_index();?>sales-order/tambah" class="btn btn-success"><i class="fa fa-plus"></i> Add Sales Order</a>
        <?php } ?>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="so-kpi"><i class="fa fa-file-text-o"></i><span>Total SO</span><strong><?=number_format((float)$summary->total_docs,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="so-kpi"><i class="fa fa-cubes"></i><span>SO Qty</span><strong><?=number_format((float)$summary->total_qty,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="so-kpi"><i class="fa fa-truck"></i><span>Shipped Qty</span><strong><?=number_format((float)$summary->shipped_qty,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="so-kpi"><i class="fa fa-check-circle"></i><span>Delivered SO</span><strong><?=number_format((float)$summary->delivered_docs,0,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Sales Order</h3></div>
    <div class="box-body">
      <form id="form_filter_so" class="form-horizontal so-filter" onsubmit="return false;">
        <div class="form-group">
          <label class="control-label col-lg-2">SO Date</label>
          <div class="col-lg-2"><div class="input-group date so-date"><input type="text" class="form-control" id="tgl_awal" value="<?=$defaultFrom;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <div class="col-lg-2"><div class="input-group date so-date"><input type="text" class="form-control" id="tgl_akhir" value="<?=$defaultTo;?>" autocomplete="off"><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>
          <label class="control-label col-lg-1"><?=sd_h('sales_customer', 'Customer');?></label>
          <div class="col-lg-5">
            <select id="customer" class="form-control">
              <option value="all"><?=sd_h('sales_all_customer', 'All Customer');?></option>
              <?php foreach ($customers as $isi) { ?>
                <option value="<?=so_view_h($isi->kode_penerima);?>"><?=so_view_h($isi->kode_penerima.' - '.$isi->nama);?></option>
              <?php } ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="control-label col-lg-2">Status SO</label>
          <div class="col-lg-3">
            <select id="status_so" class="form-control">
              <option value="all"><?=sd_h('sales_all_status', 'All Status');?></option>
              <option value="BELUM PRODUKSI">BELUM PRODUKSI</option>
              <option value="PRODUKSI BELUM FULL">PRODUKSI BELUM FULL</option>
              <option value="PROSES PRODUKSI">PROSES PRODUKSI</option>
              <option value="DIKIRIM SEBAGIAN">DIKIRIM SEBAGIAN</option>
              <option value="SUDAH DIKIRIM">SUDAH DIKIRIM</option>
            </select>
          </div>
          <label class="control-label col-lg-1"><?=sd_h('common_search', 'Search');?></label>
          <div class="col-lg-3"><input id="filter_keyword_hint" class="form-control" placeholder="Gunakan search tabel untuk SO/PO/customer"></div>
          <div class="col-lg-3">
            <button type="button" class="btn btn-primary" id="btn_filter"><i class="fa fa-filter"></i> <?=sd_h('common_filter', 'Filter');?></button>
            <button type="button" class="btn btn-default" id="btn_reset"><i class="fa fa-refresh"></i> <?=sd_h('common_reset', 'Reset');?></button>
            <button type="button" class="btn btn-success" id="btn_excel"><i class="fa fa-file-excel-o"></i> <?=sd_h('common_export_excel', 'Export Excel');?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="alert alert-warning fade in error_data_delete" style="display:none">
        <button type="button" class="close hide_alert_notif">&times;</button>
        <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
      </div>
      <div class="table-responsive">
        <table id="dtb_sales_order" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead>
            <tr>
              <th><?=sd_h('common_no', 'No');?></th>
              <th><?=sd_h('sales_order', 'Sales Order');?></th>
              <th>SO Date</th>
              <th><?=sd_h('sales_customer', 'Customer');?></th>
              <th><?=sd_h('sales_customer_po', 'Customer PO');?></th>
              <th><?=sd_h('sales_currency', 'Currency');?></th>
              <th>Entry User</th>
              <th>Note</th>
              <th><?=sd_h('common_status', 'Status');?></th>
              <th><?=sd_h('common_action', 'Action');?></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<script type="text/javascript">
function soFilters(){
  return {
    tgl_awal: $('#tgl_awal').val(),
    tgl_akhir: $('#tgl_akhir').val(),
    customer: $('#customer').val() || 'all',
    status_so: $('#status_so').val() || 'all'
  };
}
function soShowError(message){
  $('.isi_warning_delete').text(message || <?=sd_js('sales_order_process_failed', 'Sales Order data failed to process.');?>);
  $('.error_data_delete').fadeIn();
}

$(function(){
  if ($.fn.datepicker) {
    $('.so-date').datepicker({format:'yyyy-mm-dd',autoclose:true,todayHighlight:true});
  }
  if ($.fn.select2) {
    $('#customer,#status_so').select2({width:'100%',allowClear:false});
  }

  var dtb_sales_order = $("#dtb_sales_order").DataTable({
    bProcessing: true,
    bServerSide: true,
    pageLength: 25,
    ordering: false,
    dom: "<'row'<'col-sm-12'B>>" + "<'row'<'col-sm-6'l><'col-sm-6'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons: [{extend:'collection',text:<?=sd_js('common_export_data', 'Export Data');?>,buttons:['copyHtml5','excelHtml5','csvHtml5','pdfHtml5']}],
    fnCreatedRow: function(nRow, aData) {
      var indexId = aData.length - 1;
      var id = aData[indexId];
      var action = '<div class="so-action">'
        + '<a href="<?=base_index();?>sales-order/detail/'+id+'" class="btn btn-info btn-xs" title="<?=sd_h('common_detail', 'Detail');?>"><i class="fa fa-eye"></i></a>'
        <?php if ($role_act["up_act"]=="Y") { ?>
        + '<a href="<?=base_index();?>sales-order/edit/'+id+'" class="btn btn-warning btn-xs" title="<?=sd_h('common_edit', 'Edit');?>"><i class="fa fa-pencil"></i></a>'
        <?php } ?>
        + '<a target="_blank" href="<?=base_url();?>modul/sales_order/print_pi.php?id='+id+'" class="btn btn-primary btn-xs" title="<?=sd_h('sales_proforma_invoice', 'Proforma Invoice');?>"><i class="fa fa-file-text-o"></i> PI</a>'
        + '<a target="_blank" href="<?=base_url();?>modul/sales_order/print_ci.php?id='+id+'" class="btn btn-success btn-xs" title="Commercial Invoice"><i class="fa fa-print"></i> CI</a>'
        <?php if ($role_act['del_act']=='Y') { ?>
        + '<button type="button" class="btn btn-danger btn-xs hapus_dtb_notif" data-id="'+id+'" data-uri="<?=base_admin();?>modul/sales_order/sales_order_action.php" data-variable="dtb_sales_order" title="Hapus"><i class="fa fa-trash"></i></button>'
        <?php } ?>
        + '</div>';
      $('td:eq('+indexId+')', nRow).html(action);
      $('td:eq(1)', nRow).addClass('text-bold');
      $(nRow).attr('id', 'line_' + id);
    },
    columnDefs: [
      {targets:[0,9],orderable:false,searchable:false},
      {targets:[0],width:'42px',className:'text-center'},
      {targets:[8],width:'150px'},
      {targets:[9],width:'180px'}
    ],
    ajax: {
      url: '<?=base_admin();?>modul/sales_order/sales_order_data.php',
      type: 'post',
      data: function(d){ $.extend(d, soFilters()); },
      error: function(xhr){ console.log(xhr); soShowError(<?=sd_js('sales_order_load_failed', 'Sales Order data failed to load.');?>); }
    }
  });

  $('#btn_filter').on('click', function(){ dtb_sales_order.draw(); });
  $('#filter_keyword_hint').on('keyup', function(e){
    dtb_sales_order.search(this.value);
    if (e.keyCode === 13) dtb_sales_order.draw();
  });
  $('#btn_reset').on('click', function(){
    $('#tgl_awal').val('<?=$defaultFrom;?>');
    $('#tgl_akhir').val('<?=$defaultTo;?>');
    $('#filter_keyword_hint').val('');
    $('#customer').val('all').trigger('change');
    $('#status_so').val('all').trigger('change');
    dtb_sales_order.search('').draw();
  });
  $('#btn_excel').on('click', function(){
    var f = soFilters();
    window.open('<?=base_admin();?>modul/sales_order/sales_order_action.php?act=excel'
      + '&tgl_awal=' + encodeURIComponent(f.tgl_awal)
      + '&tgl_akhir=' + encodeURIComponent(f.tgl_akhir)
      + '&customer=' + encodeURIComponent(f.customer)
      + '&status_so=' + encodeURIComponent(f.status_so));
  });
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
