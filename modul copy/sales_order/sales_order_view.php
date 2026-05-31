

                <!-- Main content -->
                <section class="content">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                <div class="box-header">
                                <?php
                                if (!isset($_GET['level'])) {
                                   foreach ($db->fetch_all("sys_menu") as $isi) {
                                      if (uri_segment(1)==$isi->url) {
                                          if ($role_act["insert_act"]=="Y") {
                                      ?>
                                      <a href="<?=base_index();?>sales-order/tambah" class="btn btn-primary "><i class="fa fa-plus"></i> <?php echo $lang["add_button"];?></a>
                                      <?php
                                          }
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
        
                        <table id="dtb_sales_order" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                  <th>No</th>
                                  <th>Sales Order ID</th>
                                  <th>so_date</th>
                                  <th>Customer</th>
                                  <th>PIC Sales</th>
                                  <th>Currency</th>
                                  <th>Entry User</th>
                                  <th>Note</th>
                                 
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
               $edit =""; 
                $del="";
             //  if (!isset($_GET['level'])) {
            //if (uri_segment(1)==$isi->url) {
              //check edit permission
              if ($role_act["up_act"]=="Y") {
                $edit = "<a data-id='+aData[indek]+' href=".base_index()."sales-order/edit/'+aData[indek]+' class=\"btn btn-primary btn-sm edit_data \" data-toggle=\"tooltip\" title=\"Edit\"><i class=\"fa fa-pencil\"></i></a>";
                  } else {
                      $edit ="";
                  }
                if ($role_act['del_act']=='Y') {
                    $del = "<button data-id='+aData[indek]+' data-uri=".base_admin()."modul/sales_order/sales_order_action.php".' class="btn btn-danger hapus_dtb_notif btn-sm" data-toggle="tooltip" title="Hapus" data-variable="dtb_sales_order"><i class="fa fa-trash"></i></button>';
                } else {
                    $del="";
                }
           // }
       //   }
               $print = "<a target=\"_BLANK\" data-id='+aData[indek]+' href=".base_url()."modul/sales_order/cetak.php?id='+aData[indek]+' class=\"btn btn-primary btn-sm edit_data \" data-toggle=\"tooltip\" title=\"Print\"><i class=\"fa fa-print\"></i></a>";
            }
      // echo $edit;
        ?>


    </section><!-- /.content -->

        <script type="text/javascript">
      
      
      var dtb_sales_order = $("#dtb_sales_order").DataTable({ 
           "fnCreatedRow": function( nRow, aData, iDataIndex ) {
            var indek = aData.length-1; 
            if (aData[indek-1]=='Verified') {
               $('td:eq('+indek+')', nRow).html('<a href="<?=base_url();?>modul/sales_order/print_ci.php?id='+aData[indek]+'"  class="btn btn-success btn-sm" data-toggle="tooltip" title="Detail"><i class="fa fa-print"></i> Commercial Invoice</a> <a href="<?=base_url();?>modul/sales_order/print_pi.php?id='+aData[indek]+'"  class="btn btn-success btn-sm" data-toggle="tooltip" title="Detail"><i class="fa fa-print"></i> Proforma Invoice</a> <?= $edit ?> <a href="<?=base_index();?>sales-order/detail/'+aData[indek]+'"  class="btn btn-success btn-sm" data-toggle="tooltip" title="Detail"><i class="fa fa-eye"></i></a>');  
             }else{
               $('td:eq('+indek+')', nRow).html('<a href="<?=base_url();?>modul/sales_order/print_ci.php?id='+aData[indek]+'"  class="btn btn-success btn-sm" data-toggle="tooltip" title="Detail"><i class="fa fa-print"></i> Commercial Invoice</a> <a href="<?=base_url();?>modul/sales_order/print_pi.php?id='+aData[indek]+'"  class="btn btn-success btn-sm" data-toggle="tooltip" title="Detail"><i class="fa fa-print"></i> Proforma Invoice</a>  <?= $edit ?> <a href="<?=base_index();?>sales-order/detail/'+aData[indek]+'"  class="btn btn-success btn-sm" data-toggle="tooltip" title="Detail"><i class="fa fa-eye"></i></a>  <?=$del;?>');
             }
           
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
            'targets': [8],
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
              url :'<?=base_admin();?>modul/sales_order/sales_order_data.php',
            type: 'post',  // method  , by default get
            error: function (xhr, error, thrown) {
            console.log(xhr);

            }
          },
        });

  $('#dtb_sales_order').on('draw.dt', function() {
          init_selected()
      });

      $('#select_all').on('click', function() {
          select_deselect('select')
      });
      $('#deselect_all').on('click', function() {
          select_deselect('unselect')
  });



  $(document).on('click', '#dtb_sales_order tbody tr td', function(event) {
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
      var table_select = $('#dtb_sales_order tbody tr.selected');
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
          $('#dtb_sales_order tbody tr').addClass('DTTT_selected selected')
      } else {
          $('#dtb_sales_order tbody tr').removeClass('DTTT_selected selected')
      }
      init_selected()
  }




/* Add a click handler for the delete row */
  $('#bulk_delete').click( function() {
    var anSelected = fnGetSelected( dtb_sales_order );
    var data_array_id = check_selected();
    var all_ids = data_array_id.toString();
    $('#ucing').modal({ keyboard: false }).one('click', '#delete', function (e) {
        $('#loadnya').show();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '<?=base_admin();?>modul/sales_order/sales_order_action.php?act=del_massal',
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
                               dtb_sales_order.draw();
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
            