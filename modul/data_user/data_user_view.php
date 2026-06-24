<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                        Data User
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>data-user">Data User</a></li>
                        <li class="active">Data User List</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
<?php
$mdtActionsHtml = '';
if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") {
  $mdtActionsHtml = '<a id="add_data_user" class="btn btn-warning"><i class="fa fa-plus"></i> '.$lang["add_button"].'</a>';
}
include __DIR__ . "/../master_data_toolbar.php";
?>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
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
                        <table id="dtb_data_user" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                  <th>No</th>
                                  <th>first_name</th>
                                  <th>username</th>
                                  <th>email</th>
                                  <th>level</th>
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
                $edit = "<a data-id=\"'+aData[indek]+'\" class=\"btn btn-primary btn-sm du-action-btn edit_data\" data-toggle=\"tooltip\" title=\"Edit\"><i class=\"fa fa-pencil\"></i></a>";
              } else {
                  $edit ="";
              }
            if ($role_act['del_act']=='Y') {
                $del = "<button data-id='+aData[indek]+' data-uri=".base_admin()."modul/data_user/data_user_action.php".' class="btn btn-danger btn-sm du-action-btn hapus_dtb_notif" data-toggle="tooltip" title="Hapus" data-variable="dtb_data_user"><i class="fa fa-trash"></i></button>';
            } else {
                $del="";
            }
            if (isset($_SESSION['group_level']) && in_array($_SESSION['group_level'], array('admin','system_administrator'), true)) {
                $login_as = "<button data-id=\"'+aData[indek]+'\" data-username=\"'+aData[2]+'\" class=\"btn btn-warning btn-sm du-action-btn login_as_user\" data-toggle=\"tooltip\" title=\"Login As\"><i class=\"fa fa-sign-in\"></i></button>";
            } else {
                $login_as = "";
            }
                             }
            }

        ?>

    <div class="modal" id="modal_data_user" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"> <div class="modal-dialog modal-lg"> <div class="modal-content"><div class="modal-header"> <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> <h4 class="modal-title"><?php echo $lang["add_button"];?> Data User</h4> </div> <div class="modal-body" id="isi_data_user"> </div> </div><!-- /.modal-content --> </div><!-- /.modal-dialog --> </div>
    
    </section><!-- /.content -->

        <style>
          #dtb_data_user th:last-child,
          #dtb_data_user td:last-child {
            width: 172px;
            min-width: 172px;
            text-align: center;
            white-space: nowrap;
          }
          .du-action-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 2px 0;
          }
          .du-action-wrap .du-action-btn {
            width: 31px;
            height: 30px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px !important;
            box-shadow: 0 1px 2px rgba(0,0,0,.08);
          }
          .du-action-wrap .du-action-btn i {
            margin: 0;
            line-height: 1;
          }
        </style>

        <script type="text/javascript">
      
      $("#add_data_user").click(function() {
          $.ajax({
              url : "<?=base_admin();?>modul/data_user/data_user_add.php",
              type : "GET",
              success: function(data) {
                  $("#isi_data_user").html(data);
              }
          });

      $('#modal_data_user').modal({ keyboard: false,backdrop:'static',show:true });

    });
    
      
    $(".table").on('click','.edit_data',function(event) {
        $("#loadnya").show();
        event.preventDefault();
        var currentBtn = $(this);

        id = currentBtn.attr('data-id');

        $.ajax({
            url : "<?=base_admin();?>modul/data_user/data_user_edit.php",
            type : "post",
            data : {id_data:id},
            success: function(data) {
                $("#isi_data_user").html(data);
                $("#loadnya").hide();
          }
        });

    $('#modal_data_user').modal({ keyboard: false,backdrop:'static' });

    });
    
      var dtb_data_user = $("#dtb_data_user").DataTable({
           "fnCreatedRow": function( nRow, aData, iDataIndex ) {
            var indek = aData.length-1;
            $('td:eq('+indek+')', nRow).html('<div class="du-action-wrap"><a href="<?=base_index();?>data-user/detail/'+aData[indek]+'" class="btn btn-success btn-sm du-action-btn" data-toggle="tooltip" title="Detail"><i class="fa fa-eye"></i></a><?=$edit;?><?=$login_as;?><?=$del;?></div>');
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
            'targets': [5],
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
              url :'<?=base_admin();?>modul/data_user/data_user_data.php',
            type: 'post',  // method  , by default get
            error: function (xhr, error, thrown) {
            console.log(xhr);

            }
          },
        });

  $(document).on('click', '.login_as_user', function(event) {
      event.preventDefault();
      event.stopPropagation();
      var id = $(this).data('id');
      var username = $(this).data('username') || 'user ini';
      if (!confirm('Login sebagai '+username+'? Session super admin akan disimpan dan bisa dikembalikan dari top bar.')) {
          return;
      }
      $('#loadnya').show();
      $.ajax({
          url: '<?=base_admin();?>modul/data_user/data_user_action.php?act=login_as',
          type: 'POST',
          dataType: 'json',
          data: {id: id},
          success: function(response) {
              $('#loadnya').hide();
              var result = response[0] || {};
              if (result.status === 'good') {
                  window.location.href = result.redirect || '<?=base_index();?>';
                  return;
              }
              $('.isi_warning_delete').text(result.error_message || 'Login As gagal diproses.');
              $('.error_data_delete').fadeIn();
          },
          error: function(xhr) {
              $('#loadnya').hide();
              $('.isi_warning_delete').text(xhr.responseText || 'Login As gagal diproses.');
              $('.error_data_delete').fadeIn();
          }
      });
  });

  $('#dtb_data_user').on('draw.dt', function() {
          init_selected()
      });

      $('#select_all').on('click', function() {
          select_deselect('select')
      });
      $('#deselect_all').on('click', function() {
          select_deselect('unselect')
  });



  $(document).on('click', '#dtb_data_user tbody tr td', function(event) {
      if ($(event.target).closest('.du-action-wrap').length > 0) {
          return;
      }
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
      var table_select = $('#dtb_data_user tbody tr.selected');
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
          $('#dtb_data_user tbody tr').addClass('DTTT_selected selected')
      } else {
          $('#dtb_data_user tbody tr').removeClass('DTTT_selected selected')
      }
      init_selected()
  }




/* Add a click handler for the delete row */
  $('#bulk_delete').click( function() {
    var anSelected = fnGetSelected( dtb_data_user );
    var data_array_id = check_selected();
    var all_ids = data_array_id.toString();
    $('#ucing').modal({ keyboard: false }).one('click', '#delete', function (e) {
        $('#loadnya').show();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '<?=base_admin();?>modul/data_user/data_user_action.php?act=del_massal',
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
                               dtb_data_user.draw();
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
