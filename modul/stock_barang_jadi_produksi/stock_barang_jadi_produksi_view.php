<section class="content-header">
  <h1>Stock Barang Jadi Produksi</h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Stock Barang Jadi Produksi</li>
  </ol>
</section>

<section class="content">
  <div class="row">
    <div class="col-xs-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Monitoring Stock Barang Jadi dari GR Production</h3>
          <p class="text-muted" style="margin:6px 0 0">Data ini read-only dan hanya mengambil stock layer hasil <strong>GR from Production Order</strong>.</p>
        </div>
        <div class="box-body table-responsive">
          <table id="dtb_stock_barang_jadi_produksi" class="table table-bordered table-striped table-condensed">
            <thead>
              <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Stock</th>
                <th>Satuan</th>
                <th>Kategori</th>
                <th>Lokasi</th>
                <th>Stock Type</th>
                <th>Trace</th>
                <th>Raw Material</th>
                <th>Last GR</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>

<script type="text/javascript">
  var dtb_stock_barang_jadi_produksi = $("#dtb_stock_barang_jadi_produksi").DataTable({
    "fnCreatedRow": function(nRow, aData) {
      var key = aData[aData.length - 1];
      $('td:eq(11)', nRow).html('<a href="<?=base_index();?>stock-barang-jadi-produksi/detail/' + encodeURIComponent(key) + '" class="btn btn-success btn-sm" data-toggle="tooltip" title="Detail Trace"><i class="fa fa-eye"></i> Detail</a>');
    },
    "dom": "<'row'<'col-sm-12'B>>" + "<'row'<'col-sm-6'l><'col-sm-6'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons: [{
      extend: 'collection',
      text: 'Export Data',
      buttons: ['pdfHtml5', 'csvHtml5', 'copyHtml5', 'excelHtml5']
    }],
    'bProcessing': true,
    'bServerSide': true,
    'columnDefs': [
      {'targets': [11], 'orderable': false, 'searchable': false},
      {'width': '5%', 'targets': 0, 'orderable': false, 'searchable': false, 'className': 'dt-center'},
      {'className': 'text-right', 'targets': [3,9]},
      {'className': 'text-center', 'targets': [8,11]}
    ],
    'ajax': {
      url: '<?=base_admin();?>modul/stock_barang_jadi_produksi/stock_barang_jadi_produksi_data.php',
      type: 'post',
      error: function(xhr) { console.log(xhr); }
    }
  });
</script>
