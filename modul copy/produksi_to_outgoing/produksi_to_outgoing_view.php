<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                        Transfer Barang Dari Produksi
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>transfer-produksi">Transfer Barang</a></li>
                        <li class="active">Transfer barang List</li>
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
                                      <a href="<?=base_index();?>produksi-to-outgoing/tambah" class="btn btn-primary "><i class="fa fa-plus"></i> <?php echo $lang["add_button"];?></a>
                                      <?php
                                          }
                                      }
                                  }
                                ?>
                            </div><!-- /.box-header -->
                            <div class="box-body table-responsive">
                               <form id="input_pemasukan_hamparan" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=in">                   
                              
                                <div class="form-group">
                                    <label for="Tanggal BPB" class="control-label col-lg-2">Tanggal  </label>
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
                                   <a class="btn btn-primary" onclick="download_data()"><i class="fa fa-download"></i> Download</a> 
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
                        <table id="dtb_transfer_produksi" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                  <th style="width: 100px"></th>
                                  <th></th>
                                <!--   <th>Nomor</th> -->
                                  <th>No SPB</th>
                                  <th>Tanggal SPB</th>
                                  <th>Tujuan</th>
                                <!--   <th>Tanggal Request</th> -->
                                 <!--  <th>Departemen</th> -->
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

            function download_data(){
         var tgl_awal = $("#tgl_awal").val();
         var tgl_akhir = $("#tgl_akhir").val();
         if (tgl_awal=='' && tgl_akhir!='') {
           alert("isi tanggal awal");
         }else if (tgl_awal!='' && tgl_akhir=='') {
           alert("isi tanggal akhir");
         }else if(tgl_awal=='' && tgl_akhir==''){
             alert("isi tanggal awal dan akhir");
         }else{
           document.location="<?= base_url() ?>modul/produksi_to_outgoing/produksi_to_outgoing_action.php?act=down_excel&tgl_awal="+tgl_awal+"&tgl_akhir="+tgl_akhir;
         }
      } 

      function show_detail(no_spb){
        $.ajax({
          url : '<?= base_url() ?>modul/produksi_to_outgoing/produksi_to_outgoing_action.php?act=show_detail',
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
            var indek = aData.length-1;
            $('td:eq('+indek+')', nRow).html('<a href="<?=base_index();?>transfer-produksi/detail/'+aData[indek]+'"  class="btn btn-success btn-sm" data-toggle="tooltip" title="Detail"><i class="fa fa-eye"></i></a> <?=$edit;?> <?=$del;?>');
              $(nRow).attr('id', 'line_'+aData[indek]);
              },
              "dom": "<'row'<'col-sm-12'B>>" + "<'row'<'col-sm-6'l><'col-sm-6'f>>" +"<'row'<'col-sm-12'tr>>" +"<'row'<'col-sm-5'i><'col-sm-7'p>>",
 "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
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
              url :'<?=base_admin();?>modul/produksi_to_outgoing/produksi_to_outgoing_data.php',
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

    function filter() {
      $("#dtb_transfer_produksi").dataTable().fnDraw(); 
  }
 

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
            