<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$categories = $db->query("SELECT kd_kategori,nm_kategori FROM kategori ORDER BY nm_kategori");
$storageLocations = $db->query("SELECT s.id,s.storage_code,s.storage_name,p.plant_code FROM erp_storage_location s LEFT JOIN erp_plant p ON p.id=s.plant_id WHERE s.status='Aktif' ORDER BY p.plant_code,s.storage_code");
$storageBins = $db->query("SELECT b.id,b.bin_code,b.bin_name,b.storage_location_id,s.storage_code FROM erp_storage_bin b LEFT JOIN erp_storage_location s ON s.id=b.storage_location_id WHERE b.status='Aktif' ORDER BY s.storage_code,b.bin_code");
$kpi = $db->fetch("
  SELECT COUNT(DISTINCT kd_barang) AS total_material,
         COALESCE(SUM(stock),0) AS total_stock,
         COUNT(DISTINCT kd_kategori) AS total_category,
         COUNT(DISTINCT storage_location_id) AS total_sloc
  FROM v_stock_transaksi
  WHERE stock>=0
");
?>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<style>
  .so-hero{background:linear-gradient(135deg,#0f766e,#2563eb);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,118,110,.18)}
  .so-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.so-hero p{margin:0;opacity:.92}
  .so-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .so-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.so-kpi strong{display:block;font-size:24px;margin-top:6px;color:#111827}.so-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}
  .so-filter .form-group{margin-bottom:12px}.select2-container{width:100%!important}
  #dtb_stock_pemasukan td,#dtb_stock_pemasukan th{font-size:12px;vertical-align:middle}
  #dtb_stock_pemasukan th{white-space:nowrap}.so-action-buttons{white-space:nowrap;min-width:74px}.dt-right{text-align:right!important}.dt-center{text-align:center!important}
  .so-stock-link{font-weight:700;color:#0f766e;cursor:pointer}.so-material strong{font-size:13px}.so-location small{color:#64748b}
  .so-detail-table th,.so-detail-table td{font-size:12px;vertical-align:middle}.so-detail-table th{white-space:nowrap;background:#f8fafc}
</style>

<section class="content-header">
  <h1>Stock Overview <small>SAP MM Inventory Overview</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Stock Overview</li>
  </ol>
</section>

<section class="content">
  <div class="so-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Stock Overview Workbench</h1>
        <p>Monitor stock gudang berdasarkan material, kategori, plant, storage location, dan storage bin. Klik qty stock untuk melihat layer FIFO beserta trace dokumen pabean/BPB.</p>
      </div>
      <div class="col-md-4 text-right">
        <span class="label label-primary"><?=wh_h(wh_t('warehouse_inventory_management', 'Inventory Management'));?></span>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="so-kpi"><i class="fa fa-cubes"></i><span>Total Material</span><strong><?=number_format((float)$kpi->total_material,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="so-kpi"><i class="fa fa-balance-scale"></i><span>Total Stock Qty</span><strong><?=number_format((float)$kpi->total_stock,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="so-kpi"><i class="fa fa-tags"></i><span>Categories</span><strong><?=number_format((float)$kpi->total_category,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="so-kpi"><i class="fa fa-map-marker"></i><span>Storage Locations</span><strong><?=number_format((float)$kpi->total_sloc,0,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?> Stock Overview</h3></div>
    <div class="box-body">
      <form id="filter_stock_overview" class="form-horizontal so-filter" onsubmit="return false;">
        <div class="form-group">
          <label for="kategori" class="control-label col-lg-2">Kategori</label>
          <div class="col-lg-4">
            <select class="form-control select2-filter" id="kategori" data-placeholder="Semua Kategori">
              <option value="">Semua Kategori</option>
              <?php foreach ($categories as $k) { ?>
                <option value="<?=htmlspecialchars($k->kd_kategori,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($k->nm_kategori,ENT_QUOTES,'UTF-8');?></option>
              <?php } ?>
            </select>
          </div>
          <label for="storage_location_id" class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></label>
          <div class="col-lg-4">
            <select class="form-control select2-filter" id="storage_location_id" data-placeholder="Semua Storage Location">
              <option value="">Semua Storage Location</option>
              <?php foreach ($storageLocations as $s) { ?>
                <option value="<?=intval($s->id);?>"><?=htmlspecialchars(trim($s->plant_code.' / '.$s->storage_code.' - '.$s->storage_name, ' -/'),ENT_QUOTES,'UTF-8');?></option>
              <?php } ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label for="storage_bin_id" class="control-label col-lg-2"><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></label>
          <div class="col-lg-4">
            <select class="form-control select2-filter" id="storage_bin_id" data-placeholder="Semua Storage Bin">
              <option value="">Semua Storage Bin</option>
              <?php foreach ($storageBins as $b) { ?>
                <option value="<?=intval($b->id);?>" data-storage-location="<?=intval($b->storage_location_id);?>"><?=htmlspecialchars(trim($b->storage_code.' / '.$b->bin_code.' - '.$b->bin_name, ' -/'),ENT_QUOTES,'UTF-8');?></option>
              <?php } ?>
            </select>
          </div>
          <div class="col-lg-6">
            <button type="button" id="btn_filter_stock" class="btn btn-primary"><i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?></button>
            <button type="button" id="btn_reset_stock" class="btn btn-default"><i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?></button>
            <button type="button" id="btn_excel_stock" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Export Data</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="box">
    <div class="box-body">
      <div class="alert alert-warning error_data_delete" style="display:none">
        <button type="button" class="close hide_alert_notif">&times;</button>
        <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
      </div>
      <div class="table-responsive">
        <table id="dtb_stock_pemasukan" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead>
            <tr>
              <th><?=wh_h(wh_t('table_no', 'No'));?></th>
              <th><?=wh_h(wh_t('common_action', 'Action'));?></th>
              <th>Kode</th>
              <th>Nama Barang</th>
              <th>Stock</th>
              <th>Satuan</th>
              <th>Kategori</th>
              <th><?=wh_h(wh_t('common_plant', 'Plant'));?></th>
              <th><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></th>
              <th><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></th>
              <th>ID</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal" id="detail_stock" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" style="width:96%">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          <h4 class="modal-title">Stock Layer Detail</h4>
        </div>
        <div class="modal-body" id="isi_detail"></div>
        <div class="modal-footer"><button class="btn btn-default" data-dismiss="modal"><?=wh_h(wh_t('common_close', 'Close'));?></button></div>
      </div>
    </div>
  </div>
</section>

<script type="text/javascript">
function showStockError(m){$('.isi_warning_delete').text(m||'Data stock gagal dimuat.');$('.error_data_delete').fadeIn();}
function filter(){ $("#dtb_stock_pemasukan").DataTable().draw(); }
function filterStockBins(){
  var selectedLocation = $('#storage_location_id').val();
  $('#storage_bin_id option').each(function(){
    var optionLocation = $(this).data('storage-location');
    $(this).prop('disabled', optionLocation && selectedLocation && String(optionLocation) !== String(selectedLocation));
  });
  if (selectedLocation) {
    var selectedBinLocation = $('#storage_bin_id option:selected').data('storage-location');
    if (selectedBinLocation && String(selectedBinLocation) !== String(selectedLocation)) {
      $('#storage_bin_id').val('').trigger('change.select2');
    }
  }
  $('#storage_bin_id').trigger('change.select2');
}
function get_detail_stock(kd_barang){
  $.ajax({
    url : "<?=base_admin();?>modul/stock_pemasukan/stock_pemasukan_action.php?act=show_detail_stock",
    type : "POST",
    data : {kd_barang : kd_barang},
    success: function(data) { $("#isi_detail").html(data); $("#detail_stock").modal('show'); },
    error:function(xhr){showStockError(xhr.responseText || 'Detail stock gagal dibuka.');}
  });
}

$(function(){
  if ($.fn.select2) {
    $('.select2-filter').select2({allowClear:true,width:'100%'});
  }

  var dtb_stock_pemasukan = $("#dtb_stock_pemasukan").DataTable({
    fnCreatedRow: function(nRow, aData) {
      var idIndex = aData.length - 1;
      var materialCode = aData[2];
      $('td:eq(1)', nRow).html('<div class="so-action-buttons"><button type="button" class="btn btn-info btn-xs" onclick="get_detail_stock(\''+materialCode+'\')" title="<?=wh_h(wh_t('warehouse_detail_layer', 'Detail Layer'));?>"><i class="fa fa-eye"></i></button></div>');
      $('td:eq(3)', nRow).addClass('so-material');
      $('td:eq(8)', nRow).addClass('so-location');
      $(nRow).attr('id', 'line_'+aData[idIndex]);
    },
    aLengthMenu:[[25,50,100,200,-1],[25,50,100,200,"All"]],
    pageLength:25,
    dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons:[{
      extend:'collection',
      text:<?=json_encode(wh_t('common_export_data', 'Export Data'));?>,
      buttons:[
        {extend:'copyHtml5',title:'Stock Overview',messageTop:'<?=infokb()->nama;?>',exportOptions:{columns:[0,2,3,4,5,6,7,8,9]}},
        {extend:'excelHtml5',title:'Stock Overview',messageTop:'<?=infokb()->nama;?>',exportOptions:{columns:[0,2,3,4,5,6,7,8,9]}},
        {extend:'csvHtml5',title:'Stock Overview',messageTop:'<?=infokb()->nama;?>',exportOptions:{columns:[0,2,3,4,5,6,7,8,9]}},
        {extend:'pdfHtml5',title:'Stock Overview',messageTop:'<?=infokb()->nama;?>',exportOptions:{columns:[0,2,3,4,5,6,7,8,9]}}
      ]
    }],
    bProcessing:true,
    bServerSide:true,
    columnDefs:[
      {targets:[0,1],orderable:false,searchable:false,className:'dt-center'},
      {targets:[4],className:'dt-right'},
      {targets:[10],visible:false},
      {width:'42px',targets:0},
      {width:'72px',targets:1}
    ],
    ajax:{
      url:'<?=base_admin();?>modul/stock_pemasukan/stock_pemasukan_data.php',
      type:'post',
      data:function(d){
        d.kategori = $("#kategori").val();
        d.storage_location_id = $("#storage_location_id").val();
        d.storage_bin_id = $("#storage_bin_id").val();
      },
      error:function(xhr){console.log(xhr);showStockError('Data Stock Overview gagal dimuat.');}
    }
  });

  $('#storage_location_id').on('change',function(){filterStockBins();filter();});
  $('#kategori,#storage_bin_id').on('change',filter);
  $('#btn_filter_stock').on('click',filter);
  $('#btn_reset_stock').on('click',function(){
    $('#kategori,#storage_location_id,#storage_bin_id').val('').trigger('change');
    filterStockBins();
    filter();
  });
  $('#btn_excel_stock').on('click',function(){
    dtb_stock_pemasukan.button('.buttons-excel').trigger();
  });
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
