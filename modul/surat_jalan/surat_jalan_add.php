

<!-- Main content -->
<section class="content">
<div class="row">
  <div class="col-lg-12">
    <div class="box box-solid box-primary">
      <div class="box-header">
        <h3 class="box-title">Tambah Surat Jalan</h3>
        <div class="box-tools pull-right">
          <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
      </div>
      <div class="box-body">
       <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
        
        <form id="input_surat_jalan" method="post" class="form-horizontal" 
              action="<?=base_admin();?>modul/surat_jalan/surat_jalan_action.php?act=in"
              enctype="multipart/form-data">
          
          <input type="hidden" id="id_sales_order" name="id_sales_order">
          <div class="form-group">
              <label class="control-label col-lg-2">No Surat Jalan</label>
              <div class="col-lg-4">
                <input type="text" id="no_surat_jalan" name="no_surat_jalan" value="<?= "SJ-" . date('ymd') . "-" . str_pad(get_nomor('surat_jalan','id'), 5, '0', STR_PAD_LEFT); ?>" class="form-control" readonly>
              </div>
            </div>
          
          
          
          <div class="form-group">
            <label for="Sales Order" class="control-label col-lg-2">Sales Order <span style="color:#FF0000">*</span></label>
            <div class="col-lg-10">
              <input type="text" id="no_sales_order_search" name="no_sales_order_search" 
                     placeholder="Ketik No Sales Order / No PO / Nama Penerima" class="form-control" required>
            </div>
          </div>
          
          <div id="info_sales_order" style="display:none;">
            <div class="form-group">
              <label class="control-label col-lg-2">No Sales Order</label>
              <div class="col-lg-4">
                <input type="text" id="no_sales_order" class="form-control" readonly>
              </div>
              <label class="control-label col-lg-2">Tanggal SO</label>
              <div class="col-lg-4">
                <input type="text" id="so_date" class="form-control" readonly>
              </div>
            </div>
            
            <div class="form-group" style="display: none">
              <label class="control-label col-lg-2">No Invoice</label>
              <div class="col-lg-4">
                <input type="text" id="no_sales_invoice" class="form-control" readonly>
              </div>
              <label class="control-label col-lg-2">No PO Customer</label>
              <div class="col-lg-4">
                <input type="text" id="no_po" class="form-control" readonly>
              </div>
            </div>
            
            <div class="form-group">
              <label class="control-label col-lg-2">Penerima</label>
              <div class="col-lg-10">
                <input type="text" id="nama_penerima" class="form-control" readonly>
                <input type="hidden" id="kode_penerima" name="kode_penerima">
              </div>
            </div>
            
            <div class="form-group" style="display: none">
              <label class="control-label col-lg-2">No Polisi</label>
              <div class="col-lg-4">
                <input type="text" id="no_polisi" class="form-control">
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label for="Tanggal Surat Jalan" class="control-label col-lg-2">Tanggal Surat Jalan <span style="color:#FF0000">*</span></label>
            <div class="col-lg-3">
              <div class="input-group date" id="tgl_surat_jalan">
                <input type="text" class="form-control" name="tgl_surat_jalan" autocomplete="off" required 
                       value="<?=date('Y-m-d')?>">
                <span class="input-group-addon">
                  <span class="glyphicon glyphicon-calendar"></span>
                </span>
              </div> 
            </div>
          </div>
          
          <div class="form-group">
            <label for="Alamat Pengiriman" class="control-label col-lg-2">Alamat Pengiriman <span style="color:#FF0000">*</span></label>
            <div class="col-lg-10">
              <textarea name="alamat_pengiriman" id="alamat_pengiriman" class="form-control" rows="3" required></textarea>
            </div>
          </div>
          
          <div class="form-group" style="display: none">
            <label for="Sopir" class="control-label col-lg-2">Sopir <span style="color:#FF0000">*</span></label>
            <div class="col-lg-10">
              <input type="text" name="sopir" class="form-control" >
            </div>
          </div>
          
          <div class="form-group">
            <label for="No Kendaraan" class="control-label col-lg-2">No Kendaraan <span style="color:#FF0000">*</span></label>
            <div class="col-lg-10">
              <input type="text" name="no_kendaraan" class="form-control" required>
            </div>
          </div>
          <div class="form-group">
            <label for="No Kendaraan" class="control-label col-lg-2">Attn <span style="color:#FF0000">*</span></label>
            <div class="col-lg-10">
              <input type="text" name="attn" class="form-control" required>
            </div>
          </div>
          
          <div class="form-group">
            <label for="Keterangan" class="control-label col-lg-2">Keterangan</label>
            <div class="col-lg-10">
              <textarea name="keterangan" class="form-control" rows="2"></textarea>
            </div>
          </div>
          
          <div class="form-group" id="panel_barang" style="display:none">
            <div class="col-lg-12">
              <div class="alert alert-info">
                <strong>Note:</strong> Masukkan jumlah yang akan dikirim untuk setiap barang.
              </div>
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th style="width:30px;text-align: center">No</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Packing</th>
                    <th>Satuan Packing</th>
                    
                    <th>Qty Kirim</th>
                    <th>Satuan</th>
                    <th>Keterangan</th>
                  </tr>
                </thead>
                <tbody id="isi_tabel">
                  <!-- Baris detail akan ditambahkan via JS -->
                </tbody>
              </table>
            </div>
          </div>
          
          <div class="form-group">
            <label for="tags" class="control-label col-lg-2">&nbsp;</label>
            <div class="col-lg-10">
              <a href="<?=base_index();?>surat-jalan" class="btn btn-default">
                <i class="fa fa-step-backward"></i> Kembali
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> Buat Surat Jalan
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</section>

<link rel="stylesheet" href="<?= base_url() ?>assets/css/jquery-ui.css">
<style type="text/css">
  .ui-autocomplete { 
    z-index:2147483647;
    max-height: 200px;
    overflow-y: auto;
    overflow-x: hidden;
  }
</style>
<script src="<?= base_url() ?>assets/js/jquery-ui.js"></script>

<script type="text/javascript">
  var option_satuan_packing = '';
$(document).ready(function() {

  $.ajax({
    url: "<?= base_url() ?>modul/surat_jalan/surat_jalan_action.php?act=get_satuan_packing",
    type: "GET",
    dataType: "json",
    success: function(res){
        var opt = '<option value="">-- Pilih --</option>';
        $.each(res, function(i, v){
            opt += '<option value="'+v.satuan_packing+'">'+v.satuan_packing+'</option>';
        });
        window.option_satuan_packing = opt;
    }
});
  // Autocomplete untuk Sales Order
  $("#no_sales_order_search").autocomplete({
    source: function(request, response) {
      $.ajax({
        url: "<?= base_url() ?>modul/surat_jalan/surat_jalan_action.php?act=get_sales_order",
        data: { term: request.term },
        type: 'POST',
        dataType: "json",
        success: function(data) {
          response($.map(data, function(item) {
            return {
              label: item.label,
              value: item.value,
              id: item.id,
              no_sales_order: item.no_sales_order,
              no_sales_invoice: item.no_sales_invoice,
              no_po: item.no_po,
              kode_penerima: item.kode_penerima,
              nama_penerima: item.nama_penerima,
              shipping_address: item.shipping_address,
              no_polisi: item.no_polisi,
              so_date: item.so_date
            };
          }))
        }
      });
    },
    select: function(event, ui) {
      $("#no_sales_order_search").val(ui.item.label);
      $("#id_sales_order").val(ui.item.id);
      $("#no_sales_order").val(ui.item.no_sales_order);
      $("#no_sales_invoice").val(ui.item.no_sales_invoice);
      $("#no_po").val(ui.item.no_po);
      $("#kode_penerima").val(ui.item.kode_penerima);
      $("#nama_penerima").val(ui.item.nama_penerima);
      $("#alamat_pengiriman").val(ui.item.shipping_address);
      $("#no_polisi").val(ui.item.no_polisi);
      $("#so_date").val(ui.item.so_date);
      
      $("#info_sales_order").show();
      
      // Load detail barang dari sales order
      $.ajax({
        url: "<?= base_url() ?>modul/surat_jalan/surat_jalan_action.php?act=get_detail_sales_order",
        type: 'POST',
        data: { id_sales_order: ui.item.id },
        dataType: "json",
        success: function(data) {
          var tbody = $("#isi_tabel");
          tbody.empty();
          
          if (data.length === 0) {
            tbody.append(
              '<tr><td colspan="9" class="text-center">' +
              '<div class="alert alert-warning">Tidak ada barang yang bisa dikirim. Semua barang sudah terkirim.</div>' +
              '</td></tr>'
            );
            $("#panel_barang").show();
            return;
          }
          
          $.each(data, function(index, item) {
            var no = index + 1;
            tbody.append(
              '<tr>' +
              '<td style="text-align: center">' + no + '</td>' +
              '<td><input type="text" class="form-control" value="' + item.kode_barang + '" readonly>' +
                '<input type="hidden" name="id_detail[]" value="' + item.id_detail + '">' +
                '<input type="hidden" name="kode_barang[]" value="' + item.kode_barang + '"></td>' +
              '<td><input type="text" class="form-control" value="' + item.nama_barang + '" readonly>' +
              '<input type="hidden" name="nama_barang[]" value="' + item.nama_barang + '"></td>' +

              '<td>' +
                  '<input type="text" name="packing[] class="form-control" >' +
              '</td>' +

              '<td>' +
                  '<select name="satuan_packing[]" class="form-control">' +
                      window.option_satuan_packing +
                  '</select>' +
              '</td>' +            
              '<td><input type="number" class="form-control text-right qty-kirim" value="' + item.qty_order + '" name="qty_kirim[]" ' +
                'value="' + item.sisa_qty + '" min="0" max="' + item.sisa_qty + '" step="0.01" required ' +
                'onchange="validateQty(this, ' + item.sisa_qty + ')"></td>' +
              '<td><input type="text" class="form-control" value="' + item.satuan + '" readonly>' +
                '<input type="hidden" name="satuan[]" value="' + item.satuan + '"></td>' +
              '<td><input type="text" class="form-control" name="keterangan_barang[]"></td>' +
              '</tr>'
            );
          });
          $("#panel_barang").show();
        }
      });
      return false;
    }
  }).data("ui-autocomplete")._renderItem = function(ul, item) {
    return $("<li></li>")
      .data("ui-autocomplete-item", item)
      .append('<a><div class="list_item_container">' + item.label + '</div></a>')
      .appendTo(ul);
  };
  
  // Datepicker
  $(".date").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
  });
  
  // Validasi jumlah kirim
  window.validateQty = function(input, max) {
    // var value = parseFloat(input.value);
    // if (value > max) {
    //   alert('Jumlah kirim tidak boleh melebihi sisa yang tersedia (' + max + ')');
    //   input.value = max;
    // }
    // if (value < 0) {
    //   input.value = 0;
    // }
  };
  
  // Validation
  $("#input_surat_jalan").validate({
    errorClass: "help-block",
    errorElement: "span",
    highlight: function(element, errorClass, validClass) {
      $(element).parents(".form-group").removeClass("has-success").addClass("has-error");
    },
    unhighlight: function(element, errorClass, validClass) {
      $(element).parents(".form-group").removeClass("has-error").addClass("has-success");
    },
    rules: {
      id_sales_order: {
        required: true
      },
      tgl_surat_jalan: {
        required: true
      },
      alamat_pengiriman: {
        required: true
      },
      sopir: {
        required: true
      },
      no_kendaraan: {
        required: true
      }
    },
    messages: {
      id_sales_order: "Pilih sales order terlebih dahulu",
      tgl_surat_jalan: "Tanggal surat jalan harus diisi",
      alamat_pengiriman: "Alamat pengiriman harus diisi",
      sopir: "Nama sopir harus diisi",
      no_kendaraan: "Nomor kendaraan harus diisi"
    },
    submitHandler: function(form) {
      // Validasi apakah ada barang yang akan dikirim
      var totalQtyKirim = 0;
      $('.qty-kirim').each(function() {
        totalQtyKirim += parseFloat($(this).val()) || 0;
      });
      
      if (totalQtyKirim <= 0) {
        alert('Minimal ada 1 barang dengan jumlah kirim lebih dari 0');
        return false;
      }
      
      if (confirm("Buat surat jalan baru?") == true) {
        $("#loadnya").show();
        $(form).ajaxSubmit({
          url: $(form).attr("action"),
          dataType: "json",
          type: "post",
          error: function(data) { 
            $("#loadnya").hide();
            console.log(data); 
          },
          success: function(responseText) {
            $("#loadnya").hide();
            console.log(responseText);
            $.each(responseText, function(index) {
              if (responseText[index].status == "die") {
                $("#informasi").modal("show");
              } else if(responseText[index].status == "error") {
                $(".isi_warning").text(responseText[index].error_message);
                $(".error_data").focus()
                $(".error_data").fadeIn();
              } else if(responseText[index].status == "good") {
                $(".error_data").hide();
                alert("Surat Jalan berhasil dibuat!");
                window.location.href = "<?=base_index();?>surat-jalan";
              } else {
                console.log(responseText);
                $(".isi_warning").text(responseText[index].error_message);
                $(".error_data").focus()
                $(".error_data").fadeIn();
              }
            });
          }
        });
      }
      return false;
    }
  });
});
</script>