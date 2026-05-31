<!-- Content Header (Page header) -->
<style type="text/css">
  .row-reversal {
    background-color: #f2dede !important;
    color: #a94442 !important;
}
</style>
                <section class="content-header">
                    <h1>
                        Transfer Posting
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>transfer-produksi">Transfer Posting</a></li>
                        <li class="active">Transfer Posting List</li>
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
                                      <a href="<?=base_index();?>transfer-produksi/tambah" class="btn btn-primary "><i class="fa fa-plus"></i> <?php echo $lang["add_button"];?></a>
                                      <?php
                                          }
                                      }
                                  }
                                ?>
                            </div><!-- /.box-header -->
                            <div class="box-body table-responsive">
                                <div class="row">
                                    <div class="col-sm-12" style="text-align: right;margin-bottom: 10px">
                                    <button id="select_all" class="btn btn-primary btn-xs"><i class="fa fa-check-square-o"></i> <?php echo $lang["select_all"];?></button>
                                    <button id="deselect_all" class="btn btn-primary btn-xs"><i class="fa fa-remove"></i> <?php echo $lang["deselect_all"];?></button>
                                    <button id="bulk_delete" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> <?php echo $lang["delete_selected"];?></button> <span class="selected-data"></span>
                            </div>
                            </div>
 <div class="alert alert-warning fade in error_data_delete" style="display:none">
          <button type="button" class="close hide_alert_notif">&times;</button>
          <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
        </div>
                        <table id="dtb_transfer_produksi" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                  <th style="width: 100px"></th>
                                  <th></th>
                                <!--   <th>Nomor</th> -->
                                  <th>No SPB</th>
                                  <th>Tanggal SPB</th>
                                  <th>No Request</th>
                                  <th>Tanggal Request</th>
                                  <th>Tujuan</th>
                                  <th>Nama PPC</th>
                                  <th>Catatan</th>
                                  <th>Status</th>
                                  
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div><!-- /.box-body -->
                  </div><!-- /.box -->
                </div>
              </div>
              <div id="modal_detail" class="modal fade" role="dialog">
                <div class="modal-dialog modal-lg" style="width: 90%">

                  <!-- Modal content-->
                  <div class="modal-content">
                    <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal">&times;</button>
                      <h4 class="modal-title">Detail Barang</h4>
                    </div>
                    <div class="modal-body" id="isi_detail">
                      
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
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
                $edit = "<a data-id='+aData[indek]+' href=".base_index()."transfer-produksi/edit/'+aData[indek]+' class=\"btn btn-primary btn-sm edit_data \" data-toggle=\"tooltip\" title=\"Edit\"><i class=\"fa fa-pencil\"></i></a>";
              } else {
                  $edit ="";
              }
            if ($role_act['del_act']=='Y') {
                $del = "<button data-id='+aData[indek]+' data-uri=".base_admin()."modul/transfer_produksi/transfer_produksi_action.php".' class="btn btn-danger hapus_dtb_notif btn-sm" data-toggle="tooltip" title="Hapus" data-variable="dtb_transfer_produksi"><i class="fa fa-trash"></i></button>';
            } else {
                $del="";
            }
                             }
            }

        ?>

    </section><!-- /.content -->

        <script type="text/javascript">

          function reversal(no_spb){
    Swal.fire({
        title: 'Reversal transaksi?',
        text: "Stock akan dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, reversal!',
    }).then((result) => {
        if (result.isConfirmed) {

            $.ajax({
                url: "<?= base_url() ?>modul/transfer_produksi/transfer_produksi_action.php?act=reversal",
                type: "POST",
                data: { no_spb: no_spb },
                dataType: "json",
                success: function(res){
                    if(res.status == 'good'){
                        Swal.fire('Success','Reversal berhasil','success');
                        dtb_transfer_produksi.draw();
                    }else{
                        Swal.fire('Error',res.error_message,'error');
                    }
                }
            });

        }
    });
}

      function show_detail(no_spb){
        $.ajax({
          url : '<?= base_url() ?>modul/transfer_produksi/transfer_produksi_action.php?act=show_detail',
          data : {
            no_spb : no_spb
          },
          type : 'POST',
          success : function(data){
            $("#isi_detail").html(data);
            $("#modal_detail").modal('show');
          }
        })
      }
      
      
      var dtb_transfer_produksi = $("#dtb_transfer_produksi").DataTable({
         "fnCreatedRow": function( nRow, aData, iDataIndex ) {

    var indek   = aData.length-1;   // no_spb
    var no_spb  = aData[2];
    var status  = aData[10];         // kolom status

    // 🔥 WARNA BARIS REVERSAL
    if(status == '9'){
        $(nRow).addClass('row-reversal');
    }

    // 🔥 DROPDOWN BUTTON
    var btn = `
    <div class="btn-group">
    <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown">
        Action <span class="caret"></span>
    </button>
    <ul class="dropdown-menu">

    <li>
        <a href="javascript:void(0)" onclick="show_detail('${no_spb}')">
            <i class="fa fa-eye"></i> Detail
        </a>
    </li>
    `;

    // ✅ EDIT hanya kalau REVERSAL
    if(status == '9'){
        btn += `
        <li>
            <a href="<?=base_index();?>transfer-produksi/edit/${no_spb}">
                <i class="fa fa-pencil"></i> Edit
            </a>
        </li>
        `;
    } 

    // ✅ REVERSAL hanya kalau BELUM reversal
    if(status != '9'){
        btn += `
        <li class="divider"></li>
        <li>
            <a href="javascript:void(0)" onclick="reversal('${no_spb}')">
                <i class="fa fa-refresh"></i> Reversal
            </a>
        </li>
        `;
    }

    // ✅ DELETE hanya draft
    if(status == '0'){
        btn += `
        <li class="divider"></li>
      
        `;
    }

    btn += `</ul></div>`;

    $('td:eq(0)', nRow).html(btn);

    // 🔥 OPTIONAL: LABEL STATUS
    if(status == '9'){
        $('td:eq(9)', nRow).html('<span class="label label-danger">Reversal</span>');
    }
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
            'targets': [7],
              'orderable': false,
              'searchable': false
            },
            {
            'targets': [0,1],
              'orderable': false,
              'searchable': false
            },

                {
            'width': '7%',
            'targets': 0,
            'orderable': false,
            'searchable': false,
            'className': 'dt-center'
          }
             ],

    
            'ajax':{
              url :'<?=base_admin();?>modul/transfer_produksi/transfer_produksi_data.php',
            type: 'post',  // method  , by default get
            error: function (xhr, error, thrown) {
            console.log(xhr);

            }
          },
        });

  $('#dtb_transfer_produksi').on('draw.dt', function() {
          init_selected()
      });

      $('#select_all').on('click', function() {
          select_deselect('select')
      });
      $('#deselect_all').on('click', function() {
          select_deselect('unselect')
  });



  $(document).on('click', '#dtb_transfer_produksi tbody tr td', function(event) {
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
      var table_select = $('#dtb_transfer_produksi tbody tr.selected');
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
          $('#dtb_transfer_produksi tbody tr').addClass('DTTT_selected selected')
      } else {
          $('#dtb_transfer_produksi tbody tr').removeClass('DTTT_selected selected')
      }
      init_selected()
  }




/* Add a click handler for the delete row */
  $('#bulk_delete').click( function() {
    var anSelected = fnGetSelected( dtb_transfer_produksi );
    var data_array_id = check_selected();
    var all_ids = data_array_id.toString();
    $('#ucing').modal({ keyboard: false }).one('click', '#delete', function (e) {
        $('#loadnya').show();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '<?=base_admin();?>modul/transfer_produksi/transfer_produksi_action.php?act=del_massal',
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
                               dtb_transfer_produksi.draw();
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
            