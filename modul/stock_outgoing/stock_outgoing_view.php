<!-- Content Header (Page header) -->
<section class="content-header">
  <h1>Stock Outgoing</h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Stock Outgoing</li>
  </ol>
</section>

<style>
  .stock-outgoing-filter {
    border: 1px solid #edf1f5;
    border-radius: 10px;
    background: #fff;
    padding: 15px 15px 5px;
    margin-bottom: 15px;
    box-shadow: 0 4px 14px rgba(31, 45, 61, .04);
  }
  .stock-outgoing-filter .form-group {
    margin-bottom: 10px;
  }
  .stock-outgoing-filter label {
    color: #34495e;
    font-weight: 600;
  }
  .stock-outgoing-table thead th {
    background: #f4f7fb;
    color: #2c3e50;
    border-color: #dfe7ef !important;
    vertical-align: middle !important;
  }
  .stock-outgoing-table tbody td {
    border-color: #edf1f5 !important;
    vertical-align: middle !important;
  }
  .stock-outgoing-table .qty-link {
    color: #1f77b4;
    font-weight: 700;
    cursor: pointer;
  }
  .stock-outgoing-table .btn-xs {
    border-radius: 4px;
  }
  .stock-summary-note {
    color: #6c7a89;
    font-size: 12px;
    margin-top: 4px;
  }
  #detail_stock .modal-dialog {
    width: 92%;
  }
  #detail_stock .table thead th {
    background: #f4f7fb;
    border-color: #dfe7ef;
  }
</style>

<!-- Main content -->
<section class="content">
  <div class="row">
    <div class="col-xs-12">
      <div class="box">
        <div class="box-body">
          <div class="stock-outgoing-filter">
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label for="kategori">Kategori</label>
                  <select class="form-control select2-filter" id="kategori" style="width:100%">
                    <option value="">Semua Kategori</option>
                    <?php
                    $q = $db->query("select kd_kategori,nm_kategori from kategori order by nm_kategori asc");
                    foreach ($q as $k) {
                    ?>
                      <option value="<?= htmlspecialchars($k->kd_kategori, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($k->nm_kategori, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php } ?>
                  </select>
                  <div class="stock-summary-note">Data dibaca langsung dari stock layer lokasi outgoing.</div>
                </div>
              </div>
              <div class="col-md-8">
                <div class="form-group" style="padding-top:24px">
                  <button type="button" class="btn btn-primary" id="btn_filter_stock_outgoing">
                    <i class="fa fa-filter"></i> <?=wh_h(wh_t('common_filter', 'Filter'));?>
                  </button>
                  <button type="button" class="btn btn-default" id="btn_reset_stock_outgoing">
                    <i class="fa fa-refresh"></i> <?=wh_h(wh_t('common_reset', 'Reset'));?>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table id="dtb_stock_outgoing" class="table table-bordered table-striped stock-outgoing-table" width="100%">
              <thead>
                <tr>
                  <th><?=wh_h(wh_t('table_no', 'No'));?></th>
                  <th>Kode Barang</th>
                  <th>Nama Barang</th>
                  <th>Stock</th>
                  <th>Satuan</th>
                  <th>Kategori</th>
                  <th><?=wh_h(wh_t('common_action', 'Action'));?></th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal" id="detail_stock" tabindex="-1" role="dialog" aria-labelledby="detailStockLabel">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
          </button>
          <h4 class="modal-title" id="detailStockLabel">Detail Stock Outgoing</h4>
        </div>
        <div class="modal-body" id="isi_detail"></div>
      </div>
    </div>
  </div>
</section>

<script type="text/javascript">
  function get_detail_stock(kd_barang) {
    $("#isi_detail").html("<div class='text-center text-muted' style='padding:30px'><i class='fa fa-spinner fa-spin'></i> Memuat detail stock...</div>");
    $("#detail_stock").modal('show');
    $.ajax({
      url: "<?=base_admin();?>modul/stock_outgoing/stock_outgoing_action.php?act=show_detail_stock",
      type: "POST",
      data: {
        kd_barang: kd_barang
      },
      success: function(data) {
        $("#isi_detail").html(data);
      },
      error: function(xhr) {
        $("#isi_detail").html("<div class='alert alert-danger'>Gagal memuat detail stock outgoing.</div>");
        console.log(xhr);
      }
    });
  }

  if ($.fn.select2) {
    $(".select2-filter").select2({
      width: '100%',
      allowClear: true,
      placeholder: 'Semua Kategori'
    });
  }

  var dtb_stock_outgoing = $("#dtb_stock_outgoing").DataTable({
    "dom": "<'row'<'col-sm-12'B>>" + "<'row'<'col-sm-6'l><'col-sm-6'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons: [{
      extend: 'collection',
      text: '<i class="fa fa-download"></i> Export Data',
      buttons: ['excelHtml5', 'pdfHtml5', 'copyHtml5']
    }],
    'bProcessing': true,
    'bServerSide': true,
    'order': [[1, 'asc']],
    'columnDefs': [{
      'width': '5%',
      'targets': 0,
      'orderable': false,
      'searchable': false,
      'className': 'dt-center'
    }, {
      'targets': 3,
      'className': 'text-right'
    }, {
      'targets': 6,
      'orderable': false,
      'searchable': false,
      'className': 'text-center'
    }],
    'ajax': {
      url: '<?=base_admin();?>modul/stock_outgoing/stock_outgoing_data.php',
      data: function(d) {
        d.kategori = $("#kategori").val();
      },
      type: 'post',
      error: function(xhr) {
        console.log(xhr);
      }
    }
  });

  $("#btn_filter_stock_outgoing").on("click", function() {
    dtb_stock_outgoing.ajax.reload();
  });

  $("#btn_reset_stock_outgoing").on("click", function() {
    $("#kategori").val("").trigger("change");
    dtb_stock_outgoing.ajax.reload();
  });
</script>
