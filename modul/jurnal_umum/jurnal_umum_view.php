<!-- Content Header (Page header) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script> 
                <section class="content-header">
                    <h1>
                        Jurnal Umum
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>jurnal-umum">Jurnal Umum</a></li>
                        <li class="active">Jurnal Umum List</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                <div class="box-header">
                                <?php
                                  foreach ($db->fetch_all("sys_menu") as $isi) {
                                      if (uri_segment(1)==$isi->url) {
                                          if ($role_act["insert_act"]=="Y") {
                                      ?>
                                      <a href="javascript:void(0)" 
   class="btn btn-primary"
   data-toggle="modal"
   data-target="#modal_jurnal">

   <i class="fa fa-plus"></i> Tambah
</a>
                                      <?php
                                          }
                                      }
                                  }
                                ?>
                            </div><!-- /.box-header -->
                            <div class="box-body table-responsive">
                                <div class="row">
                                    
                            </div>
 <div class="alert alert-warning fade in error_data_delete" style="display:none">
          <button type="button" class="close hide_alert_notif">&times;</button>
          <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
        </div>
        <form id="form_filter_jurnal" class="form-horizontal">

    <!-- Tanggal -->
    <div class="form-group">

        <label class="control-label col-lg-2">
            Tanggal Jurnal
        </label>

        <div class="col-lg-2">

            <div class="input-group date" id="tgl1">

                <input type="text"
                       class="form-control"
                       id="start_date"
                       placeholder="tanggal awal"
                       autocomplete="off"/>

                <span class="input-group-addon">
                    <span class="glyphicon glyphicon-calendar"></span>
                </span>

            </div>

        </div>

        <div class="col-lg-2">

            <div class="input-group date" id="tgl2">

                <input type="text"
                       class="form-control"
                       id="end_date"
                       placeholder="tanggal akhir"
                       autocomplete="off"/>

                <span class="input-group-addon">
                    <span class="glyphicon glyphicon-calendar"></span>
                </span>

            </div>

        </div>

    </div>

    <!-- No Jurnal -->
    <div class="form-group">

        <label class="control-label col-lg-2">
            No Jurnal
        </label>

        <div class="col-lg-4">

            <input type="text"
                   id="filter_no_jurnal"
                   class="form-control"
                   placeholder="Cari nomor jurnal">

        </div>

    </div>

    <!-- Tombol -->
    <div class="form-group">

        <label class="control-label col-lg-2">
            &nbsp;
        </label>

        <div class="col-lg-10">

            <a class="btn btn-primary"
               id="btn_filter">

                <i class="fa fa-search"></i>
                Filter
            </a>

            <a class="btn btn-default"
               id="btn_reset">

                <i class="fa fa-refresh"></i>
                Reset
            </a>

            <a class="btn btn-success"
               id="btn_excel">

                <i class="fa fa-file-excel-o"></i>
                Export Excel
            </a>

            <a class="btn btn-info"
               id="btn_import">

                <i class="fa fa-upload"></i>
                Import Excel
            </a>

        </div>

    </div>

</form>
                        <table id="dtb_jurnal_umum" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                  <th>No</th>
                                  <th>Nomor Jurnal</th>
                                  <th>Tanggal</th>
                                  <th>Nomor Bukti</th>
                                  <th>Debet</th>
                                  <th>Kredit</th>
                                  <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div><!-- /.box-body -->
                  </div><!-- /.box -->
                </div>
              </div>
              <div class="modal fade" id="modal_jurnal">
    <div class="modal-dialog modal-lg" style="width: 80%">
        <div class="modal-content">

            <form id="input_jurnal_umum"
                  method="post"
                  action="<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=in">

                <div class="modal-header bg-primary">
                    <button type="button" class="close" data-dismiss="modal">
                        &times;
                    </button>

                    <h4 class="modal-title">
                        Tambah Jurnal Umum
                    </h4>
                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-4">
                            <label>No Jurnal</label>
                            <input type="text"
                                   name="no_jurnal"
                                   value="<?= generate_no_jurnal() ?>"
                                   class="form-control"
                                   readonly>
                        </div>

                        <div class="col-md-4">
                            <label>Tanggal</label>

                            <div class="input-group date" id="tgl4">
                                <input type="text"
                                       class="form-control"
                                       name="tgl_jurnal" autocomplete="off">

                                <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label>No Bukti</label>

                            <input type="text"
                                   name="no_bukti"
                                   class="form-control">
                        </div>

                    </div>

                    <br>

                    <div class="form-group">
                        <label>Keterangan</label>

                        <textarea 
                               name="ket"
                               class="form-control"></textarea>
                    </div>

                    <hr>

                    <h4>Detail Jurnal</h4>

                    <table class="table table-bordered" id="table_detail">

                        <thead>
                            <tr>
                                <th width="35%">COA</th>
                                <th width="20%">Debet</th>
                                <th width="20%">Kredit</th>
                                <th width="15%">Valuta</th>
                                <th width="5%" style="text-align: center;">
                                    <button type="button"
                                            class="btn btn-success btn-xs"
                                            id="add_row">

                                        <i class="fa fa-plus"></i>
                                    </button>
                                </th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr>

                              <td>
    <select style="width: 100px" 
            name="no_rek[]" 
            class="form-control select2"
            data-placeholder="Pilih COA ...">

        <option value="">Pilih COA</option>

        <?php
        foreach ($db->fetch_all("rekening") as $isi) {

            echo "
            <option value='$isi->no_rek'>
                $isi->no_rek - $isi->nama_rek
            </option>";
        }
        ?>

    </select>
</td>
                                <td>
                                    <input type="text"
                                           name="debet[]"
                                           class="form-control debet">
                                </td>

                                <td>
                                    <input type="text"
                                           name="kredit[]"
                                           class="form-control kredit">
                                </td>

                                <td>
                                    <select name="valuta[]"
                                            class="form-control select2">

                                        <?php
                                        foreach ($db->query("select * from matauang group by jenis_valas") as $isi) {

                                          if ($isi->jenis_valas=='IDR') {
                                             echo "
                                            <option value='$isi->jenis_valas' selected>
                                                $isi->jenis_valas
                                            </option>";
                                          }else{
                                             echo "
                                            <option value='$isi->jenis_valas'>
                                                $isi->jenis_valas
                                            </option>";
                                          }

                                           
                                        }
                                        ?>
                                    </select>
                                </td>

                                <td align="center">

                                    <button type="button"
                                            class="btn btn-danger btn-xs remove_row">

                                        <i class="fa fa-trash"></i>
                                    </button>

                                </td>

                            </tr>

                        </tbody>

                        <tfoot>

                            <tr>

                                <th align="right">TOTAL</th>

                                <th>
                                    <input type="text"
                                           id="total_debet"
                                           class="form-control"
                                           readonly>
                                </th>

                                <th>
                                    <input type="text"
                                           id="total_kredit"
                                           class="form-control"
                                           readonly>
                                </th>

                                <th colspan="2"></th>

                            </tr>

                        </tfoot>

                    </table>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-default"
                            data-dismiss="modal">

                        Close
                    </button>

                    <button type="submit"
                            class="btn btn-primary">

                        <i class="fa fa-save"></i>
                        Simpan
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>
<div class="modal fade" id="modal_detail_jurnal">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <div class="modal-header bg-primary">

                <button type="button"
                        class="close"
                        data-dismiss="modal">

                    &times;

                </button>

                <h4 class="modal-title">
                    Detail Jurnal
                </h4>

            </div>

            <div class="modal-body" id="detail_jurnal_body">

            </div>

        </div>

    </div>

</div>

<div class="modal fade" id="modal_import">

    <div class="modal-dialog">

        <div class="modal-content">

            <form id="form_import_excel"
                  enctype="multipart/form-data">

                <div class="modal-header bg-success">

                    <button type="button"
                            class="close"
                            data-dismiss="modal">

                        &times;

                    </button>

                    <h4 class="modal-title">
                        Import Excel Jurnal
                    </h4>

                </div>

                <div class="modal-body">

                    <div class="alert alert-info">

                        <i class="fa fa-info-circle"></i>

                        Format file harus sesuai template.

                        <br><br>

                        <a href="<?= base_url() ?>upload/template/template_jurnal.xlsx"
                           class="btn btn-success btn-sm"
                           target="_BLANK">

                            <i class="fa fa-download"></i>
                            Download Template Excel

                        </a>

                    </div>

                    <div class="form-group">

                        <label>File Excel</label>

                        <input type="file"
                               name="file_excel"
                               class="form-control"
                               accept=".xls,.xlsx"
                               required>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-default"
                            data-dismiss="modal">

                        Close

                    </button>

                    <button type="submit"
                            class="btn btn-success">

                        <i class="fa fa-upload"></i>
                        Import

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<div class="modal fade" id="modal_edit_jurnal">

    <div class="modal-dialog modal-lg" style="width:80%">

        <div class="modal-content">

            <div id="edit_jurnal_body">

            </div>

        </div>

    </div>

</div>
        <?php

            foreach ($db->fetch_all("sys_menu") as $isi) {

            //jika url = url dari table menu
            if (uri_segment(1)==$isi->url) {
              //check edit permission
              if ($role_act["up_act"]=="Y") {
              $edit = "<button data-id='+aData[indek]+' class=\"btn btn-primary btn-sm edit_jurnal\" data-toggle=\"tooltip\" title=\"Edit\"><i class=\"fa fa-pencil\"></i></button>";
              } else {
                  $edit ="";
              }
            if ($role_act['del_act']=='Y') {
                $del = "<button data-id='+aData[indek]+' data-uri=".base_admin()."modul/jurnal_umum/jurnal_umum_action.php".' class="btn btn-danger hapus_dtb_notif btn-sm" data-toggle="tooltip" title="Hapus" data-variable="dtb_jurnal_umum"><i class="fa fa-trash"></i></button>';
            } else {
                $del="";
            }
                             }
            } 

        ?>

    </section><!-- /.content -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script type="text/javascript"> 

        
$(document).ready(function() {

  $('#tgl4').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });


   $('.select2').select2({
      //  placeholder: "Pilih COA ...",
        allowClear: true,
        width: '100%',
        dropdownParent: $('#modal_jurnal')
    });
});

$(document).on('click','.edit_jurnal',function(){

    var id = $(this).data('id');

    $('#modal_edit_jurnal').modal('show');

    $('#edit_jurnal_body').html(`

        <div style="padding:20px;text-align:center;">

            <i class="fa fa-spinner fa-spin"></i>
            Loading...

        </div>

    `);

    $.ajax({

        url : '<?=base_admin();?>modul/jurnal_umum/edit_jurnal.php?id='+id,

        success:function(result){

            $('#edit_jurnal_body').html(result);

        }

    });

});

$(document).on('submit','#form_edit_jurnal',function(e){

    e.preventDefault();

    $.ajax({

        url : $(this).attr('action'),
        type:'POST',
        data:$(this).serialize(),
        dataType:'json',

        beforeSend:function(){

            Swal.fire({
                title:'Updating...',
                text:'Sedang proses update jurnal',
                allowOutsideClick:false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

        },

        success:function(response){

            Swal.close();

            if(response.status == 'success'){

                Swal.fire({
                    icon:'success',
                    title:'Berhasil',
                    text:response.message
                });

                $('#modal_edit_jurnal').modal('hide');

                dtb_jurnal_umum.ajax.reload(null,false);

            }else{

                Swal.fire({
                    icon:'error',
                    title:'Gagal',
                    text:response.message
                });

            }

        }

    });

});


$('#btn_excel').click(function(){

    var tgl_awal  = $('#start_date').val();
    var tgl_akhir = $('#end_date').val();
    var no_jurnal = $('#filter_no_jurnal').val();

    window.open(

        "<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=excel"+

        "&tgl_awal="+tgl_awal+
        "&tgl_akhir="+tgl_akhir+
        "&no_jurnal="+no_jurnal

    );

});

         

          $(document).on('click','.detail_jurnal',function(){

    var id = $(this).data('id');

    $('#modal_detail_jurnal').modal('show');

    $('#detail_jurnal_body').html('Loading...');

    $.ajax({

        url : '<?=base_admin();?>modul/jurnal_umum/detail_jurnal.php?id='+id,

        success:function(result){

            $('#detail_jurnal_body').html(result);

        }

    });

});

          $(document).on('click','#add_row',function(){



    var html = `
    <tr>

         <td>
            <select style="width: 100px" 
                    name="no_rek[]" 
                    class="form-control select2"
                    data-placeholder="Pilih COA ...">

                <option value="">Pilih COA</option>

                <?php
                foreach ($db->fetch_all("rekening") as $isi) {

                    echo "
                    <option value='$isi->no_rek'>
                        $isi->no_rek - $isi->nama_rek
                    </option>";
                }
                ?>

            </select>
        </td>

        <td>
            <input type="text"
                   name="debet[]"
                   class="form-control debet">
        </td>

        <td>
            <input type="text"
                   name="kredit[]"
                   class="form-control kredit">
        </td>

        <td>
            <select name="valuta[]" class="form-control select2">

                <?php
                foreach ($db->query("select * from matauang group by jenis_valas") as $isi) {

                   if ($isi->jenis_valas=='IDR') {
                                             echo "
                                            <option value='$isi->jenis_valas' selected>
                                                $isi->jenis_valas
                                            </option>";
                                          }else{
                                             echo "
                                            <option value='$isi->jenis_valas'>
                                                $isi->jenis_valas
                                            </option>";
                                          }
                }
                ?>

            </select> 
        </td>

        <td align="center">

            <button type="button"
                    class="btn btn-danger btn-xs remove_row">

                <i class="fa fa-trash"></i>

            </button>

        </td>

    </tr>
    `;

    $('#table_detail tbody').append(html);
     // INIT SELECT2 BARIS TERBARU
   $('#table_detail tbody tr:last .select2').select2({
    placeholder: "Pilih COA ...",
    allowClear: true,
    width: '100%',
    dropdownParent: $('#modal_jurnal')
});

});

          $(document).on('click','.remove_row',function(){

    $(this).closest('tr').remove();

    hitung_total();

});

    $(document).on('keyup','.debet,.kredit',function(){

    hitung_total();

});

function hitung_total(){

    var total_debet = 0;
    var total_kredit = 0;

    $('.debet').each(function(){ 

        total_debet += parseFloat($(this).val()) || 0;

    });

    $('.kredit').each(function(){

        total_kredit += parseFloat($(this).val()) || 0;

    });

    $('#total_debet').val(total_debet);
    $('#total_kredit').val(total_kredit);

}

$('#form_import_excel').submit(function(e){

    e.preventDefault();

    var formData = new FormData(this);

    $.ajax({

        url : '<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=import',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',

        beforeSend:function(){

            Swal.fire({
                title: 'Importing...',
                text: 'Sedang upload excel',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

        },

        success:function(response){

            Swal.close();

            if(response.status == 'success'){

                Swal.fire({
                    icon:'success',
                    title:'Berhasil',
                    text:response.message
                });

                $('#modal_import').modal('hide');

                dtb_jurnal_umum.ajax.reload(null,false);

            }else{

                Swal.fire({
                    icon:'error',
                    title:'Gagal',
                    text:response.message
                });

            }

        },

        error:function(xhr){

            Swal.close();

            Swal.fire({
                icon:'error',
                title:'Server Error',
                text:'Terjadi kesalahan server'
            });

            console.log(xhr.responseText);

        }

    });

});

$('#input_jurnal_umum').submit(function(e){

    e.preventDefault();

    var debet  = parseFloat($('#total_debet').val()) || 0;
    var kredit = parseFloat($('#total_kredit').val()) || 0;

    if(debet != kredit){

        Swal.fire({
            icon: 'warning',
            title: 'Oops...',
            text: 'Total Debet dan Kredit harus sama'
        });

        return false;

    }

    var formData = $(this).serialize();

    $.ajax({

        url: $(this).attr('action'),
        type: 'POST',
        data: formData,
        dataType: 'json',

        beforeSend:function(){

            Swal.fire({
                title: 'Menyimpan...',
                text: 'Sedang proses simpan jurnal',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

        },

        success:function(response){

            Swal.close();

            if(response.status == 'success'){

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: response.message
                });

                $('#modal_jurnal').modal('hide'); 

                $('#input_jurnal_umum')[0].reset();

                $('#table_detail tbody').html('');

                $('#add_row').click();

                dtb_jurnal_umum.ajax.reload(null,false);

            }else{

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: response.message
                });

            }

        },

        error:function(xhr){

            Swal.close();

            Swal.fire({
                icon: 'error',
                title: 'Server Error',
                text: 'Terjadi kesalahan server'
            });

            console.log(xhr.responseText);

        }

    });

});
      
      var dtb_jurnal_umum = $("#dtb_jurnal_umum").DataTable({
           "fnCreatedRow": function( nRow, aData, iDataIndex ) {
            var indek = aData.length-1;
            $('td:eq('+indek+')', nRow).html(' <?=$edit;?> <?=$del;?>');
              $(nRow).attr('id', 'line_'+aData[indek]);
              },
              "dom": "<'row'<'col-sm-12'B>>" + "<'row'<'col-sm-6'l><'col-sm-6'f>>" +"<'row'<'col-sm-12'tr>>" +"<'row'<'col-sm-5'i><'col-sm-7'p>>",

              buttons: [
              {
                 extend: 'collection',
                 text: 'Export Data',
                 buttons: [ 'pdfHtml5', 'csvHtml5', 'copyHtml5', 'excelHtml5' ],

              }
              ],
           'bProcessing': true,
            'bServerSide': true,
            
           'columnDefs': [ {
            'targets': [6],
              'orderable': false,
              'searchable': false
            },
                {
            'width': '5%',
            'targets': 0,
            'orderable': false,
            'searchable': false,
            'className': 'dt-center'
          }
             ],

    
            'ajax':{
              url :'<?=base_admin();?>modul/jurnal_umum/jurnal_umum_data.php',
              type: 'post',

              data:function(d){

                  d.start_date = $('#start_date').val();
                  d.end_date   = $('#end_date').val();

              },

              error: function (xhr, error, thrown) {

                  console.log(xhr);

              }
},
        });

      $('#btn_filter').click(function(){

    dtb_jurnal_umum.draw();

});

$('#btn_reset').click(function(){

    $('#start_date').val('');
    $('#end_date').val('');

    dtb_jurnal_umum.draw();

});

$('#btn_import').click(function(){

    $('#modal_import').modal('show');

});

  $('#dtb_jurnal_umum').on('draw.dt', function() {
          init_selected()
      });

      $('#select_all').on('click', function() {
          select_deselect('select')
      });
      $('#deselect_all').on('click', function() {
          select_deselect('unselect')
  });



  $(document).on('click', '#dtb_jurnal_umum tbody tr td', function(event) {
      var btn = $(this).find('button');
      if (btn.length == 0) {
          $(this).parents('tr').toggleClass('DTTT_selected selected');
          var selected = check_selected();
          init_selected();

      }
  });



  function init_selected() {
      var selected = check_selected();
      var btn_hide = $('#select_all, #deselect_all, #bulk_delete, .selected-data');
      if (selected.length > 0) {
          btn_hide.show()
      } else {
          btn_hide.hide()
      }
  }


  function check_selected() {
      var table_select = $('#dtb_jurnal_umum tbody tr.selected');
      var array_data_delete = [];
      table_select.each(function() {
          var check_data = $(this).find('.hapus_dtb_notif').attr('data-id');
          if (typeof check_data != 'undefined') {
              array_data_delete.push(check_data)
          }
      });
      $('.selected-data').text(array_data_delete.length + ' <?=$lang["selected_data"];?>');
      return array_data_delete
  }


  function select_deselect(type) {
      if (type == 'select') {
          $('#dtb_jurnal_umum tbody tr').addClass('DTTT_selected selected')
      } else {
          $('#dtb_jurnal_umum tbody tr').removeClass('DTTT_selected selected')
      }
      init_selected()
  }




/* Add a click handler for the delete row */
  $('#bulk_delete').click( function() {
    var anSelected = fnGetSelected( dtb_jurnal_umum );
    var data_array_id = check_selected();
    var all_ids = data_array_id.toString();
    $('#ucing').modal({ keyboard: false }).one('click', '#delete', function (e) {
        $('#loadnya').show();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=del_massal',
            data: {data_ids:all_ids},
               success: function(responseText) {
                  $('#loadnya').hide();
                  console.log(responseText);
                      $.each(responseText, function(index) {
                          console.log(responseText[index].status);
                          if (responseText[index].status=='die') {
                            $('#informasi').modal('show');
                          } else if(responseText[index].status=='error') {
                             $('.isi_warning_delete').text(responseText[index].error_message);
                             $('.error_data_delete').fadeIn();
                             $('html, body').animate({
                                scrollTop: ($('.error_data_delete').first().offset().top)
                            },500);
                          } else if(responseText[index].status=='good') {
                            $('.error_data_delete').hide();
                               $('#loadnya').hide();
                               $(anSelected).remove();
                               dtb_jurnal_umum.draw();
                          } else {
                             $('.isi_warning_delete').text(responseText[index].error_message);
                             $('.error_data_delete').fadeIn();
                             $('html, body').animate({
                                scrollTop: ($('.error_data_delete').first().offset().top)
                            },500);
                          }
                    });
                }
            //async:false
        });

        $('#ucing').modal('hide');

    });

  });

  /* Get the rows which are currently selected */
  function fnGetSelected( oTableLocal )
  {
      return oTableLocal.$('tr.selected');
  }
</script>
            