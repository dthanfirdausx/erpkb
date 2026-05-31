<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                        Packing List
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>packing-list">Packing List</a></li>
                        <li class="active">Packing List List</li>
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
                                      <a href="<?=base_index();?>packing-list/tambah" class="btn btn-primary "><i class="fa fa-plus"></i> <?php echo $lang["add_button"];?></a>
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
                        <table id="dtb_packing_list" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                  <th>No</th>
                                  <th>Packing List No</th>
                                  <th>Delivery Order No</th>
                                  <th>Tanggal DO</th>
                                  <th>Penerima</th>
                                <!--   <th>Pemilik</th> -->
                                  <th>No Invoice</th>
                                  <th>No PO</th>
                                  <th>Valuta</th>
                                  <th>Vehicle No</th>
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
           $edit = "<a href='".base_index()."packing-list/edit/\${aData[indek]}' class='edit_data'>
          <i class='fa fa-pencil'></i> Edit
        </a>";



              } else {
                  $edit ="";
              }
            if ($role_act['del_act']=='Y') {
                $del = "<a href='javascript:void(0)' 
        data-id='\${aData[indek]}' 
        data-uri='".base_admin()."modul/packing_list/packing_list_action.php' 
        class='hapus_dtb_notif' 
        data-variable='dtb_packing_list'>
        <i class='fa fa-trash'></i> Hapus
       </a>";
            } else {
                $del="";
            }
                             }
            }

        ?>

    </section><!-- /.content -->

        <script type="text/javascript">
      
      
      var dtb_packing_list = $("#dtb_packing_list").DataTable({ 
           "fnCreatedRow": function( nRow, aData, iDataIndex ) {
            var indek = aData.length-1;
           $('td:eq('+indek+')', nRow).html(`
<div class="btn-group">
  <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown">
    Action <span class="caret"></span>
  </button>
  <ul class="dropdown-menu dropdown-menu-right">
    <li>
      <a target="_BLANK" href="<?=base_url();?>modul/packing_list/download.php?id=${aData[indek]}">
        <i class="fa fa-download"></i> Download
      </a>
    </li>
    <li>
      <a target="_BLANK" href="<?=base_url();?>modul/packing_list/print.php?id=${aData[indek]}">
        <i class="fa fa-print"></i> Print
      </a>
    </li>
    <li>
      <a href="<?=base_index();?>packing-list/detail/${aData[indek]}">
        <i class="fa fa-eye"></i> Detail
      </a>
    </li>
    <?= $edit ? "<li class='divider'></li><li>$edit</li>" : "" ?>
    <?= $del ? "<li>$del</li>" : "" ?>
  </ul>
</div>
`);

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
            'targets': [9],
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
              url :'<?=base_admin();?>modul/packing_list/packing_list_data.php',
            type: 'post',  // method  , by default get
            error: function (xhr, error, thrown) {
            console.log(xhr);

            }
          },
        });

  $('#dtb_packing_list').on('draw.dt', function() {
          init_selected()
      });

      $('#select_all').on('click', function() {
          select_deselect('select')
      });
      $('#deselect_all').on('click', function() {
          select_deselect('unselect')
  });



  $(document).on('click', '#dtb_packing_list tbody tr td', function(event) {
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
      var table_select = $('#dtb_packing_list tbody tr.selected');
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
          $('#dtb_packing_list tbody tr').addClass('DTTT_selected selected')
      } else {
          $('#dtb_packing_list tbody tr').removeClass('DTTT_selected selected')
      }
      init_selected()
  }




/* Add a click handler for the delete row */
  $('#bulk_delete').click( function() {
    var anSelected = fnGetSelected( dtb_packing_list );
    var data_array_id = check_selected();
    var all_ids = data_array_id.toString();
    $('#ucing').modal({ keyboard: false }).one('click', '#delete', function (e) {
        $('#loadnya').show();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '<?=base_admin();?>modul/packing_list/packing_list_action.php?act=del_massal',
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
                               dtb_packing_list.draw();
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
            