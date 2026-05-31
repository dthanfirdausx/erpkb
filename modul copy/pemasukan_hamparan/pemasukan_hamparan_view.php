<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                        Pemasukan 
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>pemasukan-hamparan">Pemasukan </a></li>
                        <li class="active">Pemasukan List</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                <div class="box-header">
                                <a style="font-size: 14px" class="btn btn-primary  btn-sm" title="" data-caption="Add" href="<?=base_index();?>pemasukan-hamparan/tambah" data-original-title="Add"><span class="glyphicon glyphicon-plus ewIcon"></span>Tambah Data</a>
                                <div class="btn-group ewButtonGroup">
                                  <a class="btn btn-default ewAddEdit ewAdd btn-sm" title="Import From CEISA" data-caption="Import From CEISA" href="<?=base_url();?>index.php/pemasukan-hamparan/upload_tpb"><span class="glyphicon glyphicon-link" style="font-size:16px;"></span>Import</a></div>
                            </div><!-- /.box-header -->
                            <div class="box-body table-responsive">
                              <form id="input_pemasukan_hamparan" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=in">                   
                              
                                <div class="form-group">
                                    <label for="Tanggal BPB" class="control-label col-lg-2">Tanggal BPB </label>
                                    <div class="col-lg-2" style="float: left">
                                      <div class="input-group date" id="tgl1">
                                          <input type="text" class="form-control" id="tgl_awal" placeholder="tanggal awal" name="tgl1" autocomplete="off"  />
                                          <span class="input-group-addon">
                                              <span class="glyphicon glyphicon-calendar"></span>
                                          </span>
                                      </div> 
                                    </div>  
                                
                                     <div class="col-lg-2">
                                      <div class="input-group date" id="tgl2">
                                          <input type="text" class="form-control" id="tgl_akhir" placeholder="tanggal akhir" name="tgl2" autocomplete="off"  />
                                          <span class="input-group-addon">
                                              <span class="glyphicon glyphicon-calendar"></span>
                                          </span>
                                      </div>
                                    </div>
                                </div><!-- /.form-group -->
                       
                                 <div class="form-group">
                                  <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                                  <div class="col-lg-10">

                                   <a class="btn btn-primary" onclick="filter()"><i class="fa fa-gear"></i> Filter</a>
                             
                                  </div>
                                </div><!-- /.form-group -->

                              </form>
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
                        <table id="dtb_pemasukan_hamparan" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                  <th></th>
                                  <th></th>
                                  <th>No BPB</th>
                                  <th>Tgl BPB</th>
                                  <th>No PO</th>
                                  <th>Pemasok</th>
                                  <th>No Invoice</th>
                                  <th>Jenis Dokumen</th>
                                  <th>Nomor Dokumen</th>
                                  <th>Nomor Aju</th>
                                  <th>E-Faktur</th>
                                  <th>Tanggal E-Faktur</th>
                                  <th>Valuta</th>
                                  <th>Keterangan</th>
                                 <!--  <th>Action</th> -->
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
                    <h4 class="modal-title">Detail Pemasukan</h4>
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
                $edit = "<a data-id='+aData[indek]+' href=".base_index()."pemasukan-hamparan/edit/'+aData[indek]+' class=\"btn btn-primary btn-sm edit_data \" data-toggle=\"tooltip\" title=\"Edit\"><i class=\"fa fa-pencil\"></i></a>";
                $reversal = "<button data-id='+aData[indek]+' class=\"btn btn-warning btn-sm btn-reversal\" title=\"Reversal\"><i class=\"fa fa-undo\"></i> Reversal</button>";
              } else {
                  $edit ="";
                  $reversal="";
              }
            if ($role_act['del_act']=='Y') {
                $del = "<button data-id='+aData[indek]+' data-uri=".base_admin()."modul/pemasukan_hamparan/pemasukan_hamparan_action.php".' class="btn btn-danger hapus_dtb_notif btn-sm" data-toggle="tooltip" title="Hapus" data-variable="dtb_pemasukan_hamparan"><i class="fa fa-trash"></i></button>';
            } else {
                $del="";
            }
                             }
            }

        ?>

    </section><!-- /.content --> 
  

        <script type="text/javascript">


     $(document).on('click', '.btn-reversal', function(){

    var id = $(this).data('id');

    Swal.fire({
        title: 'Reversal Transaksi',
        html: `
            <textarea id="reason" class="swal2-textarea" placeholder="Masukkan alasan reversal..." required></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: 'Reversal',
        preConfirm: () => {
            const reason = document.getElementById('reason').value;
            if (!reason) {
                Swal.showValidationMessage('Reason wajib diisi!');
            }
            return reason;
        }
    }).then((result) => {

        if (result.isConfirmed) {

            $("#loadnya").show();

            $.ajax({
                url: "<?=base_admin();?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=reversal",
                type: "POST",
                dataType: "json",
                data: { 
                    id: id,
                    reason: result.value // 🔥 kirim reason
                },

                success: function(res){

                    $("#loadnya").hide();

                    if(res[0].status == 'good'){
                        Swal.fire('Berhasil!', 'Reversal sukses', 'success');
                        $("#dtb_pemasukan_hamparan").DataTable().ajax.reload();
                    }else{
                        Swal.fire('Error!', res[0].error_message, 'error');
                    }

                },
                error: function(){
                    $("#loadnya").hide();
                    Swal.fire('Error!', 'Server error', 'error');
                }
            });

        }

    });

});
      
    $("#dtb_pemasukan_hamparan").DataTable({ 
          "fnCreatedRow": function( nRow, aData, iDataIndex ) {

    var indek = aData.length-1;
    $(nRow).attr('id', 'line_'+aData[indek]);

    // 🔥 ambil kolom keterangan (index ke-13)
    var keterangan = aData[13];

    if (keterangan && keterangan.toString().toUpperCase().includes('REVERSED')) {
        $(nRow).css({
            "background-color": "#f2dede",  // merah soft
            "color": "#a94442"
        });
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
            
           'columnDefs': [ 
                {
            'width': '10%',
            'targets': 0,
            "searchable": false, "orderable": false,
            'orderable': false,
            'searchable': false,
            'className': 'dt-center'
          },
           {
        "targets": [10,11,12], // 🔥 kolom EFaktur & Tanggal EFaktur
        "visible": false
    }
             ],

    
            'ajax':{
              url :'<?=base_admin();?>modul/pemasukan_hamparan/pemasukan_hamparan_data.php',
                 data:   function ( d ) {
                    d.tgl_awal = $("#tgl_awal").val();
                    d.tgl_akhir = $("#tgl_akhir").val();
                //    d.jenisbc = $("#jenisbc").val();
                   // d.ket   = $("#ket").val();
                    
                  },
            type: 'post',  // method  , by default get
            error: function (xhr, error, thrown) {
            console.log(xhr);

            }
          },
        });

  $('#dtb_pemasukan_hamparan').on('draw.dt', function() {
          init_selected()
      });

      $('#select_all').on('click', function() {
          select_deselect('select')
      });
      $('#deselect_all').on('click', function() {
          select_deselect('unselect')
  });

function filter() {
      $("#dtb_pemasukan_hamparan").dataTable().fnDraw(); 
  } 


  // $(document).on('click', '#dtb_pemasukan_hamparan tbody tr td', function(event) {
  //     var btn = $(this).find('button');
  //     if (btn.length == 0) {
  //         $(this).parents('tr').toggleClass('DTTT_selected selected');
  //         var selected = check_selected();
  //         init_selected();

  //     }
  // });

  function show_detail(id) {
    $('#loadnya').show();
        $.ajax({
            type: 'POST',
           // dataType: 'json',
            url: '<?=base_admin();?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=show_detail',
            data: {id:id}, 
               success: function(data) {
                  $('#loadnya').hide();
                  $("#isi_detail").html(data);
                  $("#modal_detail").modal("show");
                } 
            //async:false
        });
  }



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
      var table_select = $('#dtb_pemasukan_hamparan tbody tr.selected');
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
          $('#dtb_pemasukan_hamparan tbody tr').addClass('DTTT_selected selected')
      } else {
          $('#dtb_pemasukan_hamparan tbody tr').removeClass('DTTT_selected selected')
      }
      init_selected()
  }

 


/* Add a click handler for the delete row */
  $('#bulk_delete').click( function() {
    var anSelected = fnGetSelected( dtb_pemasukan_hamparan );
    var data_array_id = check_selected();
    var all_ids = data_array_id.toString();
    $('#ucing').modal({ keyboard: false }).one('click', '#delete', function (e) {
        $('#loadnya').show();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '<?=base_admin();?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=del_massal',
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
                               dtb_pemasukan_hamparan.draw();
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
            