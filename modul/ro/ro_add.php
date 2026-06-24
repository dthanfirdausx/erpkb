<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<?php $ro_menu_url = uri_segment(1); ?>

<!-- Main content -->
<section class="content">
  <div class="row">
    <div class="col-lg-12">
      <div class="box box-solid box-primary">

        <div class="box-header">
          <h3 class="box-title">Add RO</h3>
          <div class="box-tools pull-right">
            <button class="btn btn-info btn-sm" data-widget="collapse">
              <i class="fa fa-plus"></i>
            </button>
          </div>
        </div>

        <div class="box-body">

          <div class="alert alert-danger error_data" style="display:none">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <span class="isi_warning"></span>
          </div>

          <form id="input_ro" method="post" class="form-horizontal foto_banyak"
                action="<?= base_admin(); ?>modul/ro/ro_action.php?act=in">

            <!-- Tanggal RO -->
            <div class="form-group">
              <label class="control-label col-lg-2">
                Tanggal Request Order <span style="color:#FF0000">*</span>
              </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                  <input type="text" autocomplete="off" class="form-control" name="tgl_ro" required />
                  <span class="input-group-addon">
                    <span class="glyphicon glyphicon-calendar"></span>
                  </span>
                </div>
              </div>
            </div>

            <!-- Sales Order (hidden) -->
            <div class="form-group" style="display:none">
              <label class="control-label col-lg-2">Sales Order</label>
              <div class="col-lg-10">
                <select name="no_so" id="no_so" class="form-control select2">
                  <option value="">Pilih Sales Order</option>
                  <?php
                  $so = $db->query("
                    SELECT so.no_sales_order AS no_so,
                           so.so_date AS tgl_so,
                           COALESCE(p.nama, so.kode_penerima, '') AS customer
                    FROM sales_order so
                    LEFT JOIN penerima p ON p.kode_penerima = so.kode_penerima
                    ORDER BY so.so_date DESC, so.id_sales_order DESC
                  ");
                  foreach ($so as $s) {
                    echo "<option value='$s->no_so'>$s->no_so - $s->customer</option>";
                  }
                  ?>
                </select>
              </div>
            </div>

            <!-- Catatan -->
            <div class="form-group">
              <label class="control-label col-lg-2">Catatan</label>
              <div class="col-lg-10">
                <textarea class="form-control col-xs-12" rows="5" name="catatan"></textarea>
              </div>
            </div>

            <!-- Departemen (hidden) -->
            <div class="form-group" style="display:none">
              <label class="control-label col-lg-2">
                Departemen <span style="color:#FF0000">*</span>
              </label>
              <div class="col-lg-10">
                <select id="dept" name="dept" data-placeholder="Pilih Departemen ..."
                        class="form-control chzn-select" tabindex="2">
                  <option value=""></option>
                  <?php foreach ($db->fetch_all("dept") as $isi) { ?>
                    <option value="<?= $isi->kd_dept ?>"><?= $isi->nm_dept ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>

            <!-- PPC -->
            <div class="form-group">
              <label class="control-label col-lg-2">PPC</label>
              <div class="col-lg-10">
                <input type="text" name="name_ppc" placeholder="PPC" class="form-control">
              </div>
            </div>

            <!-- Metode RO -->
            <div class="form-group">
              <label class="control-label col-lg-2">Metode RO</label>
              <div class="col-lg-2">
                <select name="jenis_ro" id="jenis_ro" class="form-control">
                  <option value="bom">By BOM</option>
                  <option value="manual">Manual</option>
                </select>
              </div>
            </div>

            <!-- Area BOM -->
            <div class="form-group" id="area_bom">
              <label class="control-label col-lg-2">BOM Produksi</label>
              <div class="col-lg-10">
                <table class="table table-bordered">
                  <thead>
                    <tr>
                      <th width="5%">
                        <a onclick="add_bom_row()"><i class="fa fa-plus"></i></a>
                      </th>
                      <th>BOM</th>
                      <th width="15%">Qty Order</th>
                    </tr>
                  </thead>
                  <tbody id="bom_area"></tbody>
                </table>
              </div>
            </div>

            <!-- Area Manual -->
            <div class="form-group" id="area_manual" style="display:none">
              <label class="control-label col-lg-2">Detail Material</label>
              <div class="col-lg-10">
                <table class="table table-bordered">
                  <thead>
                    <tr>
                      <th width="5%">
                        <a onclick="add_manual_row()"><i class="fa fa-plus"></i></a>
                      </th>
                      <th>Kode Barang</th>
                      <th>Qty</th>
                      <th>Keterangan</th>
                    </tr>
                  </thead>
                  <tbody id="manual_area"></tbody>
                </table>
              </div>
            </div>

            <!-- Jumlah Barang Jadi (hidden) -->
            <div class="form-group" style="display:none">
              <label class="control-label col-lg-2">Jumlah Barang Jadi</label>
              <div class="col-lg-10">
                <input type="text" name="jml_brg_jadi" id="jml_brg_jadi"
                       onkeyup="update_detail_bom()" placeholder="Jumlah Barang"
                       class="form-control" required>
              </div>
            </div>

            <!-- Tujuan (hidden) -->
            <div class="form-group" style="display:none">
              <label class="control-label col-lg-2">Tujuan</label>
              <div class="col-lg-10">
                <select name="tujuan" id="tujuan" data-placeholder="Pilih tujuan ..."
                        class="form-control chzn-select" tabindex="2">
                  <option value="1">Praproduksi</option>
                  <option value="2">Produksi</option>
                </select>
              </div>
            </div>

            <!-- Detail BOM (hidden) -->
            <div class="form-group" id="detail_bom" style="display:none">
              <label class="control-label col-lg-2"></label>
              <div class="col-lg-10">
                <table class="table">
                  <thead>
                    <tr>
                      <th style="width:50px; text-align:center">
                        <a style="cursor:pointer" onclick="add_baris()">
                          <i class="fa fa-plus"></i>
                        </a>
                      </th>
                      <th>Kode Bahan Baku</th>
                      <th>Qty</th>
                      <th>Lokasi</th>
                    </tr>
                  </thead>
                  <tbody id="isi_tabel">
                    <?php
                    $no = 1;
                    $qq = $db->query("
                      SELECT b.id, b.kodebj, v.jml, v.nm_barang
                      FROM v_ro_barang v
                      JOIN bom b ON b.kodebj = v.kd_barang
                      WHERE barang_temp = '1'
                      AND user = '" . $_SESSION['username'] . "'
                    ");
                    foreach ($qq as $kk) {
                      $jml = ($kk->jml != '') ? $kk->jml : 1;
                      $q = $db->query("
                        SELECT d.*, b.nm_barang
                        FROM bom_detail d
                        LEFT JOIN barang b ON b.kd_barang = d.kodebb
                        WHERE d.id_bom = '$kk->id'
                      ");
                      foreach ($q as $k) {
                    ?>
                    <tr id="baris_<?= $no ?>">
                      <td style="text-align:center">
                        <a style="cursor:pointer" onclick="hapus_baris('<?= $no ?>')">
                          <i class="fa fa-trash-o" style="font-size:25px"></i>
                        </a>
                      </td>
                      <td>
                        <input type="text" value="<?= $k->kodebb . ' ' . $k->nm_barang ?>"
                               id="form_kode_<?= $no ?>" placeholder="Kode Barang"
                               onclick="cari_kode('<?= $no ?>')"
                               class="form-control" name="kode[]">
                        <input type="hidden" value="<?= $k->kodebb ?>"
                               name="kode_input[]" id="kode_input_<?= $no ?>">
                      </td>
                      <td>
                        <input type="text" id="form_qty_<?= $no ?>"
                               value="<?= ($k->jumlah * $jml) ?>"
                               class="form-control" name="jumlah[]">
                      </td>
                      <td>
                        <input type="text" id="form_ket_<?= $no ?>"
                               class="form-control" name="ket[]">
                      </td>
                    </tr>
                    <?php
                        $no++;
                      }
                    }
                    ?>
                  </tbody>
                </table>
              </div>
              <input type="hidden" id="jml" value="<?= $no ?>">
            </div>

            <!-- Tombol Aksi -->
            <div class="form-group">
              <label class="control-label col-lg-2">&nbsp;</label>
              <div class="col-lg-10">
                <a href="<?= base_index().$ro_menu_url; ?>" class="btn btn-default">
                  <i class="fa fa-step-backward"></i> <?= $lang["back_button"]; ?>
                </a>
                <button type="submit" class="btn btn-primary">
                  <i class="fa fa-save"></i> <?= $lang["submit_button"]; ?>
                </button>
              </div>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Modal Data Material -->
<div id="modal_barang" class="modal fade" role="dialog">
  <div class="modal-dialog modal-lg" style="width:90%">
    <div class="modal-content">

      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Data Material</h4>
      </div>

      <div class="modal-body">
        <table class="table" id="data_barang">
          <thead>
            <tr>
              <th></th>
              <th>Kode Barang</th>
              <th>Nama Barang</th>
              <th>Satuan</th>
            </tr>
          </thead>
        </table>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

<script type="text/javascript">

  // =============================================
  // UTILITY FUNCTIONS
  // =============================================

  function update_jml_order(id, jml) {
    $.ajax({
      type: 'POST',
      url: '<?= base_url() ?>modul/ro/ro_action.php?act=update_jml_order',
      data: { id: id, jml: jml },
      success: function () {
        get_detail_bom(1);
      }
    });
  }

  function hapus_material(id) {
    $.ajax({
      type: 'POST',
      url: '<?= base_url() ?>modul/ro/ro_action.php?act=hapus_material',
      data: { id: id },
      success: function () {
        $.ajax({
          type: 'POST',
          url: '<?= base_url() ?>modul/ro/ro_action.php?act=get_temp_barang',
          success: function (data) {
            $('#barang_pilih').html(data);
            get_detail_bom(1);
          }
        });
      }
    });
  }

  function cek_barang(id) {
    var cek = $('#id_barang_' + id).is(':checked') ? 1 : 0;
    $.ajax({
      type: 'POST',
      url: '<?= base_url() ?>modul/ro/ro_action.php?act=temp_barang',
      data: { id: id, cek: cek },
      success: function () {
        $('#data_barang').DataTable().destroy();
        init_datatable_barang();
      }
    });
  }

  function hapus_baris(id) {
    $('#baris_' + id).remove();
  }

  function hapus_manual(id) {
    $('#manual_' + id).remove();
  }

  function hapus_bom(id) {
    $('#bom_' + id).remove();
  }

  function pilih_material() {
    $('#modal_barang').modal('show');
  }

  // =============================================
  // AUTOCOMPLETE: KODE BARANG (mode detail BOM lama)
  // =============================================

  function cari_kode(id) {
    $('#form_kode_' + id).autocomplete({
      source: function (request, response) {
        $.ajax({
          url: '<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=cari_kode',
          type: 'POST',
          data: { term: request.term },
          dataType: 'json',
          success: function (data) {
            response($.map(data, function (item) {
              return {
                label: item.kd_barang + ' - ' + item.nm_barang,
                value: item.kd_barang + ' - ' + item.nm_barang,
                kd_barang: item.kd_barang,
                nm_barang: item.nm_barang
              };
            }));
          }
        });
      },
      select: function (event, ui) {
        $('#form_kode_' + id).val(ui.item.kd_barang + ' - ' + ui.item.nm_barang);
        $('#kode_input_' + id).val(ui.item.kd_barang);
        $.ajax({
          type: 'POST',
          url: '<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=get_unit',
          data: { id: id, kd_barang: ui.item.kd_barang },
          success: function (data) {
            $('#form_unit_' + id).val(data);
          }
        });
        return false;
      }
    }).data('ui-autocomplete')._renderItem = function (ul, item) {
      return $('<li></li>')
        .data('ui-autocomplete-item', item)
        .append('<a><div class="list_item_container" style="height:20px">' + item.kd_barang + ' , ' + item.nm_barang + '</div></a>')
        .appendTo(ul);
    };
  }

  // =============================================
  // AUTOCOMPLETE: BARANG (mode manual)
  // =============================================

  function autocomplete_barang(id) {
    $('#barang_' + id).autocomplete({
      source: function (request, response) {
        $.ajax({
          url: '<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=cari_kode',
          type: 'POST',
          data: { term: request.term },
          dataType: 'json',
          success: function (data) {
            response($.map(data, function (item) {
              return {
                label: item.kd_barang + ' - ' + item.nm_barang,
                value: item.kd_barang + ' - ' + item.nm_barang,
                kd_barang: item.kd_barang,
                nm_barang: item.nm_barang
              };
            }));
          }
        });
      },
      response: function (event, ui) {
        if (!ui.content || ui.content.length === 0) {
          $('#notfound_' + id).show();
          $('#kode_' + id).val('');
          $('#input_ro').find('button[type=submit]').prop('disabled', true);
        } else {
          $('#notfound_' + id).hide();
          if ($('.notfound_label:visible').length === 0) {
            $('#input_ro').find('button[type=submit]').prop('disabled', false);
          }
        }
      },
      select: function (event, ui) {
        $('#barang_' + id).val(ui.item.kd_barang + ' - ' + ui.item.nm_barang);
        $('#kode_' + id).val(ui.item.kd_barang);
        $('#notfound_' + id).hide();
        if ($('.notfound_label:visible').length === 0) {
          $('#input_ro').find('button[type=submit]').prop('disabled', false);
        }
        return false;
      },
      change: function (event, ui) {
        if ($('#kode_' + id).val() === '') {
          $('#notfound_' + id).show();
          $('#input_ro').find('button[type=submit]').prop('disabled', true);
        } else {
          $('#notfound_' + id).hide();
          if ($('.notfound_label:visible').length === 0) {
            $('#input_ro').find('button[type=submit]').prop('disabled', false);
          }
        }
      }
    }).data('ui-autocomplete')._renderItem = function (ul, item) {
      return $('<li></li>')
        .data('ui-autocomplete-item', item)
        .append('<a><div class="list_item_container" style="height:20px">' + item.kd_barang + ' , ' + item.nm_barang + '</div></a>')
        .appendTo(ul);
    };
  }

  // =============================================
  // AUTOCOMPLETE: BOM
  // =============================================

  function autocomplete_bom(id) {
    $('#bom_nama_' + id).autocomplete({
      source: function (request, response) {
        $.ajax({
          url: '<?= base_url() ?>modul/ro/ro_action.php?act=cari_bom',
          type: 'POST',
          data: { term: request.term },
          dataType: 'json',
          success: function (data) {
            response($.map(data, function (item) {
              return {
                id: item.id,
                label: item.kodebj + ' - ' + item.nm_barang,
                value: item.kodebj + ' - ' + item.nm_barang
              };
            }));
          }
        });
      },
      select: function (event, ui) {
        $('#id_bom_' + id).val(ui.item.id);
        reload_bom(id);
      }
    });
  }

  // =============================================
  // BOM FUNCTIONS
  // =============================================

  function add_bom_row() {
    var no = $('.bom-row').length + 1;
    var html = '';

    html += '<tr class="bom-row" id="bom_' + no + '">';
    html += '  <td>';
    html += '    <a onclick="hapus_bom(' + no + ')"><i class="fa fa-trash"></i></a>';
    html += '  </td>';
    html += '  <td>';
    html += '    <input type="text" class="form-control bom_autocomplete"';
    html += '           id="bom_nama_' + no + '" placeholder="Cari BOM">';
    html += '    <input type="hidden" name="id_bom[]" id="id_bom_' + no + '">';
    html += '    <div id="detail_bom_' + no + '"></div>';
    html += '  </td>';
    html += '  <td>';
    html += '    <input type="number" value="1" class="form-control"';
    html += '           name="jml_bom[]" onkeyup="reload_bom(' + no + ')">';
    html += '  </td>';
    html += '</tr>';

    $('#bom_area').append(html);
    autocomplete_bom(no);
  }

  function reload_bom(id) {
    $.ajax({
      type: 'POST',
      url: '<?= base_url() ?>modul/ro/ro_action.php?act=load_bom',
      data: {
        id_bom: $('#id_bom_' + id).val(),
        qty: $('#bom_' + id).find('input[type=number]').val()
      },
      success: function (data) {
        $('#detail_bom_' + id).html(data);
      }
    });
  }

  // =============================================
  // MANUAL ROW FUNCTIONS
  // =============================================

  function add_manual_row() {
    var no = $('#manual_area tr').length + 1;
    var html = '';

    html += '<tr id="manual_' + no + '">';
    html += '  <td>';
    html += '    <a onclick="hapus_manual(' + no + ')"><i class="fa fa-trash"></i></a>';
    html += '  </td>';
    html += '  <td>';
    html += '    <input type="text" class="form-control"';
    html += '           id="barang_' + no + '" name="barang_manual[]">';
    html += '    <input type="hidden" id="kode_' + no + '" name="kode_manual[]">';
    html += '    <label class="notfound_label text-danger" id="notfound_' + no + '" style="display:none;margin-top:5px">Barang tidak ditemukan</label>';
    html += '  </td>';
    html += '  <td>';
    html += '    <input type="number" step="0.00001" class="form-control" name="qty_manual[]">';
    html += '  </td>';
    html += '  <td>';
    html += '    <input type="text" class="form-control" name="ket_manual[]">';
    html += '  </td>';
    html += '</tr>';

    $('#manual_area').append(html);
    autocomplete_barang(no);
  }

  // =============================================
  // DETAIL BOM FUNCTIONS
  // =============================================

  function add_baris() {
    var id_baris = parseInt($('#jml').val()) + 1;
    var html = '';

    html += '<tr id="baris_' + id_baris + '">';
    html += '  <td style="text-align:center">';
    html += '    <a style="cursor:pointer" onclick="hapus_baris(\'' + id_baris + '\')">';
    html += '      <i class="fa fa-trash-o" style="font-size:25px"></i>';
    html += '    </a>';
    html += '  </td>';
    html += '  <td>';
    html += '    <input type="text" class="form-control" placeholder="Kode Barang"';
    html += '           onclick="cari_kode(\'' + id_baris + '\')"';
    html += '           id="form_kode_' + id_baris + '" name="kode[]">';
    html += '    <input type="hidden" id="kode_input_' + id_baris + '" name="kode_input[]">';
    html += '  </td>';
    html += '  <td>';
    html += '    <input type="number" id="form_qty_' + id_baris + '" class="form-control" name="jumlah[]">';
    html += '  </td>';
    html += '  <td>';
    html += '    <input type="text" id="form_ket_' + id_baris + '" class="form-control" name="ket[]">';
    html += '  </td>';
    html += '</tr>';

    $('#isi_tabel').append(html);
    $('#jml').val(id_baris);
  }

  function get_detail_bom(id) {
    $.ajax({
      type: 'POST',
      url: '<?= base_url() ?>modul/ro/ro_action.php?act=get_detail_bom',
      data: { id: id, jml: $('#jml_brg_jadi').val() },
      success: function (data) {
        $('#detail_bom').html(data);
      }
    });
  }

  function update_detail_bom() {
    $.ajax({
      type: 'POST',
      url: '<?= base_url() ?>modul/ro/ro_action.php?act=get_detail_bom',
      data: { id: $('#id_bom').val(), jml: $('#jml_brg_jadi').val() },
      success: function (data) {
        $('#detail_bom').html(data);
      }
    });
  }

  // =============================================
  // DATATABLE
  // =============================================

  function init_datatable_barang() {
    $('#data_barang').DataTable({
      dom: "<'row'<'col-sm-12'B>>" +
           "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
           "<'row'<'col-sm-12'tr>>" +
           "<'row'<'col-sm-5'i><'col-sm-7'p>>",
      buttons: [{
        extend: 'collection',
        text: 'Export Data',
        buttons: ['pdfHtml5', 'csvHtml5', 'copyHtml5', 'excelHtml5']
      }],
      bProcessing: true,
      bServerSide: true,
      columnDefs: [{
        width: '5%',
        targets: 0,
        orderable: false,
        searchable: false,
        className: 'dt-center'
      }],
      ajax: {
        url: '<?= base_admin(); ?>modul/ro/ro_temp_data.php',
        type: 'POST',
        error: function (xhr, error, thrown) {
          console.log(xhr);
        }
      }
    });
  }

  // =============================================
  // DOCUMENT READY
  // =============================================

  $(document).ready(function () {

    // Init datatable
    init_datatable_barang();

    // Datepicker
    $('#tgl1').datepicker({
      format: 'yyyy-mm-dd',
      autoclose: true,
      todayHighlight: true
    }).on('change', function () {
      $('#tgl1 :input').valid();
    });

    // Trigger validation on select change
    $('select').on('change', function () {
      $(this).valid();
    });

    // Ignore hidden selects in validation
    $.validator.setDefaults({ ignore: ':hidden:not(select)' });

    // Form validation
    $('#input_ro').validate({
      errorClass: 'help-block',
      errorElement: 'span',
      highlight: function (element) {
        $(element).parents('.form-group').removeClass('has-success').addClass('has-error');
      },
      unhighlight: function (element) {
        $(element).parents('.form-group').removeClass('has-error').addClass('has-success');
      },
      errorPlacement: function (error, element) {
        if (element.hasClass('chzn-select')) {
          error.insertAfter('#' + element.attr('id') + '_chosen');
        } else if (element.attr('type') === 'checkbox' || element.attr('type') === 'radio') {
          element.parent().parent().append(error);
        } else {
          error.insertAfter(element);
        }
      },
      rules: {
        tgl_ro: { required: true }
      },
      messages: {
        tgl_ro: { required: 'This field is required' }
      },
      submitHandler: function (form) {
        $('#loadnya').show();
        $(form).ajaxSubmit({
          url: $(this).attr('action'),
          dataType: 'json',
          type: 'post',
          error: function (data) {
            $('#loadnya').hide();
            console.log(data);
          },
          success: function (responseText) {
            $('#loadnya').hide();
            $.each(responseText, function (index) {
              var res = responseText[index];
              if (res.status === 'die') {
                $('#informasi').modal('show');
              } else if (res.status === 'error') {
                $('.isi_warning').text(res.error_message);
                $('.error_data').focus().fadeIn();
              } else if (res.status === 'good') {
                $('.error_data').hide();
                $('.notif_top').fadeIn(1000).fadeOut(1000, function () {
                  window.history.back();
                });
              } else {
                console.log(responseText);
                $('.isi_warning').text(res.error_message);
                $('.error_data').focus().fadeIn();
              }
            });
          }
        });
      }
    });

    // Toggle BOM / Manual area
    $('#jenis_ro').change(function () {
      if ($(this).val() === 'bom') {
        $('#area_bom').show();
        $('#area_manual').hide();
      } else {
        $('#area_bom').hide();
        $('#area_manual').show();
      }
    });

    // Reload detail when modal closed
    $('#modal_barang').on('hide.bs.modal', function () {
      $.ajax({
        type: 'POST',
        url: '<?= base_url() ?>modul/ro/ro_action.php?act=get_temp_barang',
        success: function (data) {
          $('#barang_pilih').html(data);
          get_detail_bom(1);
        }
      });
    });

  });

</script>
