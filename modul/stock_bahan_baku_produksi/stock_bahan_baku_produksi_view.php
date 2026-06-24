<?php
$categories = $db->query("SELECT kd_kategori,nm_kategori FROM kategori ORDER BY nm_kategori");
$kpi = $db->fetch("
  SELECT COUNT(DISTINCT sl.kode) AS total_material,
         COALESCE(SUM(sl.qty_sisa),0) AS total_stock,
         COUNT(DISTINCT b.kd_kategori) AS total_category,
         COUNT(DISTINCT sl.no_bpb) AS total_batch
  FROM stock_layer sl
  INNER JOIN barang b ON b.kd_barang=sl.kode
  WHERE sl.qty_sisa>0
    AND sl.lokasi='PRODUKSI'
");
?>
<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>
<style>
  .sp-hero{background:linear-gradient(135deg,#334155,#0f766e);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 10px 24px rgba(15,23,42,.16)}
  .sp-hero h1{margin:0 0 6px;font-size:26px;font-weight:700}.sp-hero p{margin:0;opacity:.92}
  .sp-kpi{border-radius:12px;background:#fff;border:1px solid #e5edf5;padding:15px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,23,42,.05)}
  .sp-kpi span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em}.sp-kpi strong{display:block;font-size:24px;margin-top:6px;color:#111827}.sp-kpi i{float:right;font-size:26px;color:#0f766e;opacity:.55}
  .sp-filter .form-group{margin-bottom:12px}.select2-container{width:100%!important}
  #dtb_stock_bahan_baku_produksi td,#dtb_stock_bahan_baku_produksi th{font-size:12px;vertical-align:middle}
  #dtb_stock_bahan_baku_produksi th{white-space:nowrap}.sp-action-buttons{white-space:nowrap;min-width:74px}.dt-right{text-align:right!important}.dt-center{text-align:center!important}
  .sp-stock-link{font-weight:700;color:#0f766e;cursor:pointer}.sp-stock-link:hover{text-decoration:underline}.sp-muted{color:#64748b;font-size:12px}
  #detail_stock .modal-dialog{width:96%}#detail_stock .table th,#detail_stock .table td{font-size:12px;vertical-align:middle}#detail_stock .table th{background:#f8fafc;white-space:nowrap}
</style>

<section class="content-header">
  <h1>Stock Produksi <small>WIP / Production Stock Layer</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Stock Produksi</li>
  </ol>
</section>

<section class="content">
  <div class="sp-hero">
    <div class="row">
      <div class="col-md-8">
        <h1>Production Stock Workbench</h1>
        <p>Monitor stock yang sedang berada di area produksi berdasarkan `stock_layer` lokasi PRODUKSI. Klik qty atau tombol detail untuk melihat layer, lot, BPB, dan dokumen pabean asal.</p>
      </div>
      <div class="col-md-4 text-right">
        <span class="label label-primary">Read Only Report</span>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-sm-3"><div class="sp-kpi"><i class="fa fa-cubes"></i><span>Total Material</span><strong><?=number_format((float)$kpi->total_material,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="sp-kpi"><i class="fa fa-balance-scale"></i><span>Total Stock Qty</span><strong><?=number_format((float)$kpi->total_stock,2,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="sp-kpi"><i class="fa fa-tags"></i><span>Categories</span><strong><?=number_format((float)$kpi->total_category,0,',','.');?></strong></div></div>
    <div class="col-sm-3"><div class="sp-kpi"><i class="fa fa-barcode"></i><span>BPB / Batch Ref</span><strong><?=number_format((float)$kpi->total_batch,0,',','.');?></strong></div></div>
  </div>

  <div class="box">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Filter Stock Produksi</h3></div>
    <div class="box-body">
      <form id="filter_stock_produksi" class="form-horizontal sp-filter" onsubmit="return false;">
        <div class="form-group">
          <label for="kategori" class="control-label col-lg-2">Kategori</label>
          <div class="col-lg-4">
            <select class="form-control select2-filter" id="kategori" data-placeholder="Semua Kategori">
              <option value="">Semua Kategori</option>
              <?php foreach ($categories as $k) { ?>
                <option value="<?=htmlspecialchars($k->kd_kategori,ENT_QUOTES,'UTF-8');?>"><?=htmlspecialchars($k->nm_kategori,ENT_QUOTES,'UTF-8');?></option>
              <?php } ?>
            </select>
            <div class="sp-muted">Filter ini membaca category material dari master barang.</div>
          </div>
          <div class="col-lg-6">
            <button type="button" id="btn_filter_stock_produksi" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
            <button type="button" id="btn_reset_stock_produksi" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</button>
            <a class="btn btn-success" href="<?=base_url();?>modul/stock_bahan_baku_produksi/stock_bahan_baku_produksi_action.php?act=download_excel"><i class="fa fa-file-excel-o"></i> Excel Produksi</a>
            <a class="btn btn-info" href="<?=base_url();?>modul/stock_bahan_baku_produksi/stock_bahan_baku_produksi_action.php?act=download_excel_brg_jadi"><i class="fa fa-download"></i> Excel FG/SFG</a>
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
        <table id="dtb_stock_bahan_baku_produksi" class="table table-bordered table-striped table-condensed" style="width:100%">
          <thead>
            <tr>
              <th>No</th>
              <th>Action</th>
              <th>Kode Barang</th>
              <th>Nama Barang</th>
              <th>Stock</th>
              <th>Satuan</th>
              <th>Kategori</th>
              <th>ID</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal" id="detail_stock" tabindex="-1" role="dialog" aria-labelledby="detailStockProduksiLabel">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          <h4 class="modal-title" id="detailStockProduksiLabel">Detail Stock Produksi</h4>
        </div>
        <div class="modal-body" id="isi_detail"></div>
        <div class="modal-footer"><button class="btn btn-default" data-dismiss="modal">Close</button></div>
      </div>
    </div>
  </div>
</section>

<script type="text/javascript">
function showProductionStockError(message){
  $('.isi_warning_delete').text(message || 'Data stock produksi gagal dimuat.');
  $('.error_data_delete').fadeIn();
}
function get_detail_stock(kd_barang){
  $("#isi_detail").html("<div class='text-center text-muted' style='padding:30px'><i class='fa fa-spinner fa-spin'></i> Memuat detail stock produksi...</div>");
  $("#detail_stock").modal('show');
  $.ajax({
    url : "<?=base_admin();?>modul/stock_bahan_baku_produksi/stock_bahan_baku_produksi_action.php?act=show_detail_stock",
    type : "POST",
    data : {kd_barang : kd_barang},
    success: function(data) { $("#isi_detail").html(data); },
    error:function(xhr){$("#isi_detail").html("<div class='alert alert-danger'>Detail stock produksi gagal dibuka.</div>");console.log(xhr);}
  });
}

$(function(){
  if ($.fn.select2) {
    $('.select2-filter').select2({allowClear:true,width:'100%'});
  }

  var dtb_stock_bahan_baku_produksi = $("#dtb_stock_bahan_baku_produksi").DataTable({
    fnCreatedRow: function(nRow, aData) {
      var idIndex = aData.length - 1;
      var materialCode = aData[2];
      $('td:eq(1)', nRow).html('<div class="sp-action-buttons"><button type="button" class="btn btn-info btn-xs" onclick="get_detail_stock(\''+materialCode+'\')" title="Detail Layer"><i class="fa fa-eye"></i></button></div>');
      $(nRow).attr('id', 'line_'+aData[idIndex]);
    },
    aLengthMenu:[[25,50,100,200,-1],[25,50,100,200,"All"]],
    pageLength:25,
    dom:"<'row'<'col-sm-12'B>>"+"<'row'<'col-sm-6'l><'col-sm-6'f>>"+"<'row'<'col-sm-12'tr>>"+"<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons:[{
      extend:'collection',
      text:'Export Data',
      buttons:[
        {extend:'copyHtml5',title:'Stock Produksi',messageTop:'<?=infokb()->nama;?>',exportOptions:{columns:[0,2,3,4,5,6]}},
        {extend:'excelHtml5',title:'Stock Produksi',messageTop:'<?=infokb()->nama;?>',exportOptions:{columns:[0,2,3,4,5,6]}},
        {extend:'csvHtml5',title:'Stock Produksi',messageTop:'<?=infokb()->nama;?>',exportOptions:{columns:[0,2,3,4,5,6]}},
        {extend:'pdfHtml5',title:'Stock Produksi',messageTop:'<?=infokb()->nama;?>',exportOptions:{columns:[0,2,3,4,5,6]}}
      ]
    }],
    bProcessing:true,
    bServerSide:true,
    order:[[2,'asc']],
    columnDefs:[
      {targets:[0,1],orderable:false,searchable:false,className:'dt-center'},
      {targets:[4],className:'dt-right'},
      {targets:[7],visible:false},
      {width:'42px',targets:0},
      {width:'72px',targets:1}
    ],
    ajax:{
      url:'<?=base_admin();?>modul/stock_bahan_baku_produksi/stock_bahan_baku_produksi_data.php',
      type:'post',
      data:function(d){ d.kategori = $("#kategori").val(); },
      error:function(xhr){console.log(xhr);showProductionStockError('Data Stock Produksi gagal dimuat.');}
    }
  });

  $('#btn_filter_stock_produksi,#kategori').on('click change',function(){
    dtb_stock_bahan_baku_produksi.draw();
  });
  $('#btn_reset_stock_produksi').on('click',function(){
    $('#kategori').val('').trigger('change');
    dtb_stock_bahan_baku_produksi.draw();
  });
  $(document).on('click','.hide_alert_notif',function(){$('.error_data_delete').hide();});
});
</script>
