<!-- Content Header (Page header) -->
            

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
                                      <a href="<?=base_index();?>purchase-order/tambah" class="btn btn-primary "><i class="fa fa-plus"></i> <?php echo $lang["add_button"];?></a>
                                      <?php
                                          }
                                      }
                                  }
                                ?>
                            </div><!-- /.box-header -->
                            <div class="box-body table-responsive">
                              <form id="form_filter_po" class="form-horizontal">

                                <!-- Tanggal PO -->
                                <div class="form-group">
                                  <label class="control-label col-lg-2">Tanggal PO</label>

                                  <div class="col-lg-2">
                                    <div class="input-group date" id="tgl1">
                                      <input type="text" class="form-control" id="tgl_awal" placeholder="tanggal awal" autocomplete="off"/>
                                      <span class="input-group-addon">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                      </span>
                                    </div>
                                  </div>

                                  <div class="col-lg-2">
                                    <div class="input-group date" id="tgl2">
                                      <input type="text" class="form-control" id="tgl_akhir" placeholder="tanggal akhir" autocomplete="off"/>
                                      <span class="input-group-addon">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                      </span>
                                    </div>
                                  </div>
                                </div>

                                <!-- Supplier -->
                                <div class="form-group">
                                  <label class="control-label col-lg-2">Supplier</label>
                                  <div class="col-lg-4">
                                    <select id="supplier" class="form-control chzn-select">
                                      <option value="all">Semua</option>
                                      <?php foreach ($db->fetch_all("pemasok") as $isi) {
                                        echo "<option value='$isi->nama'>$isi->nama</option>";
                                      } ?>
                                    </select>
                                  </div>
                                </div>

                                <!-- Status -->
                                <div class="form-group">
                                  <label class="control-label col-lg-2">Status PO</label>
                                  <div class="col-lg-4">
                                    <select id="status_po" class="form-control chzn-select ">
                                      <option value="all">Semua</option>
                                      <option value="Draft">Draft</option>
                                      <option value="Approved">Approved</option>
                                      <option value="Closed">Closed</option>
                                    </select>
                                  </div>
                                </div>

                                <!-- Tombol -->
                                <div class="form-group">
                                  <label class="control-label col-lg-2">&nbsp;</label>
                                  <div class="col-lg-10">

                                    <a class="btn btn-primary" id="btn_filter">
                                      <i class="fa fa-search"></i> Filter
                                    </a>

                                    <a class="btn btn-default" id="btn_reset">
                                      <i class="fa fa-refresh"></i> Reset
                                    </a>
                                    <a class="btn btn-success" id="btn_excel">
                                  <i class="fa fa-file-excel"></i> Export Excel
                                </a>

                                  </div>
                                </div>

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
                        <table id="dtb_purchase_order" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                  <th>No</th>
                                  <th>No PO</th>
                                 
                                  <th>Po Date</th>
                                  <th>Supplier</th>
                                  <th>Supplier Address</th>
                                <!--   <th>Issue By</th> -->
                                  <th>Trade Term</th>
                                 <th>Status PO</th>
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
        <?php

            foreach ($db->fetch_all("sys_menu") as $isi) {

            //jika url = url dari table menu
            if (uri_segment(1)==$isi->url) {
              //check edit permission
              if ($role_act["up_act"]=="Y") {
                $edit = "<a data-id='+aData[indek]+' href=".base_index()."purchase-order/edit/'+aData[indek]+' class=\"btn btn-primary btn-sm edit_data \" data-toggle=\"tooltip\" title=\"Edit\"><i class=\"fa fa-pencil\"></i></a>";
              } else {
                  $edit ="";
              }
            if ($role_act['del_act']=='Y') {
                $del = "<button data-id='+aData[indek]+' data-uri=".base_admin()."modul/purchase_order/purchase_order_action.php".' class="btn btn-danger hapus_dtb_notif btn-sm" data-toggle="tooltip" title="Hapus" data-variable="dtb_purchase_order"><i class="fa fa-trash"></i></button>';
            } else {
                $del="";
            }
                             }
            }

        ?> 

    </section><!-- /.content -->

        <script type="text/javascript">
      
      
      var dtb_purchase_order = $("#dtb_purchase_order").DataTable({ 
           "fnCreatedRow": function( nRow, aData, iDataIndex ) {
            var indek = aData.length-1;
            $('td:eq('+indek+')', nRow).html('<a href="<?=base_url();?>modul/purchase_order/cetak_po.php?po_no='+aData[indek]+'"  class="btn btn-success btn-sm" data-toggle="tooltip" title="Detail"><i class="fa fa-print"></i></a> <?=$edit;?> <?=$del;?>');
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
            
           'columnDefs': [ 
           {
            'targets': [7],
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
              url :'<?=base_admin();?>modul/purchase_order/purchase_order_data.php',
            type: 'post',  // method  , by default get
             data: function(d){
            d.tgl_awal  = $('#tgl_awal').val();
            d.tgl_akhir = $('#tgl_akhir').val();
            d.supplier  = $('#supplier').val();
            d.status_po = $('#status_po').val();
          },
            error: function (xhr, error, thrown) {
            console.log(xhr);

            }
          },
        });

    // filter
$('#btn_filter').click(function(){
  dtb_purchase_order.draw();
});

// reset
$('#btn_reset').click(function(){
  $('#form_filter_po')[0].reset();
  dtb_purchase_order.draw();
});

  $('#dtb_purchase_order').on('draw.dt', function() {
          init_selected()
      });

      $('#select_all').on('click', function() {
          select_deselect('select')
      });
      $('#deselect_all').on('click', function() {
          select_deselect('unselect')
  });



  $(document).on('click', '#dtb_purchase_order tbody tr td', function(event) {
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
      var table_select = $('#dtb_purchase_order tbody tr.selected');
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
          $('#dtb_purchase_order tbody tr').addClass('DTTT_selected selected')
      } else {
          $('#dtb_purchase_order tbody tr').removeClass('DTTT_selected selected')
      }
      init_selected()
  }

  $('#btn_excel').click(function(){

  var tgl_awal  = $('#tgl_awal').val();
  var tgl_akhir = $('#tgl_akhir').val();
  var supplier  = $('#supplier').val();
  var status    = $('#status_po').val();

  window.open("<?=base_admin();?>modul/purchase_order/purchase_order_action.php?act=excel"
    +"&tgl_awal="+tgl_awal
    +"&tgl_akhir="+tgl_akhir
    +"&supplier="+supplier
    +"&status="+status
  );

});




/* Add a click handler for the delete row */
  $('#bulk_delete').click( function() {
    var anSelected = fnGetSelected( dtb_purchase_order );
    var data_array_id = check_selected();
    var all_ids = data_array_id.toString();
    $('#ucing').modal({ keyboard: false }).one('click', '#delete', function (e) {
        $('#loadnya').show();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '<?=base_admin();?>modul/purchase_order/purchase_order_action.php?act=del_massal',
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
                               dtb_purchase_order.draw();
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
            