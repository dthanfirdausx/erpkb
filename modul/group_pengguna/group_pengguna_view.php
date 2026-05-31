<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                        Group Pengguna
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>group-pengguna">Group Pengguna</a></li>
                        <li class="active">Group Pengguna List</li>
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
                                      <a id="add_group_pengguna" class="btn btn-primary "><i class="fa fa-plus"></i> <?php echo $lang["add_button"];?></a>
                                      <?php
                                          }
                                      }
                                  }
                                ?>
                            </div><!-- /.box-header -->
                            <div class="box-body table-responsive">
 <div class="alert alert-warning fade in error_data_delete" style="display:none">
          <button type="button" class="close hide_alert_notif">&times;</button>
          <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
        </div>
                        <table id="dtb_group_pengguna" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                  <th>No</th>
                                  <th>Nama Group Pengguna</th>
                                  <th>Deskripsi </th>
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
                $edit = "<a data-id='+aData[indek]+'  class=\"btn btn-primary btn-sm edit_data \" data-toggle=\"tooltip\" title=\"Edit\"><i class=\"fa fa-pencil\"></i></a>";
              } else {
                  $edit ="";
              }
            if ($role_act['del_act']=='Y') {
                $del = "<button data-id='+aData[indek]+' data-uri=".base_admin()."modul/group_pengguna/group_pengguna_action.php".' class="btn btn-danger hapus_dtb_notif btn-sm" data-toggle="tooltip" title="Hapus" data-variable="dtb_group_pengguna"><i class="fa fa-trash"></i></button>';
            } else {
                $del="";
            }
                             }
            }

        ?>

    <div class="modal" id="modal_group_pengguna" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"> <div class="modal-dialog modal-lg"> <div class="modal-content"><div class="modal-header"> <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> <h4 class="modal-title"><?php echo $lang["add_button"];?> Group Pengguna</h4> </div> <div class="modal-body" id="isi_group_pengguna"> </div> </div><!-- /.modal-content --> </div><!-- /.modal-dialog --> </div>
    
    </section><!-- /.content -->

        <script type="text/javascript">
      
      $("#add_group_pengguna").click(function() {
          $.ajax({
              url : "<?=base_admin();?>modul/group_pengguna/group_pengguna_add.php",
              type : "GET",
              success: function(data) {
                  $("#isi_group_pengguna").html(data);
              }
          });

      $('#modal_group_pengguna').modal({ keyboard: false,backdrop:'static',show:true });

    });
    
      
    $(".table").on('click','.edit_data',function(event) {
        $("#loadnya").show();
        event.preventDefault();
        var currentBtn = $(this);

        id = currentBtn.attr('data-id');

        $.ajax({
            url : "<?=base_admin();?>modul/group_pengguna/group_pengguna_edit.php",
            type : "post",
            data : {id_data:id},
            success: function(data) {
                $("#isi_group_pengguna").html(data);
                $("#loadnya").hide();
          }
        });

    $('#modal_group_pengguna').modal({ keyboard: false,backdrop:'static' });

    });
    
      var dtb_group_pengguna = $("#dtb_group_pengguna").DataTable({
           "fnCreatedRow": function( nRow, aData, iDataIndex ) {
            var indek = aData.length-1;
            $('td:eq('+indek+')', nRow).html('<?=$edit;?> <?=$del;?>');
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
            'targets': [3],
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
              url :'<?=base_admin();?>modul/group_pengguna/group_pengguna_data.php',
            type: 'post',  // method  , by default get
            error: function (xhr, error, thrown) {
            console.log(xhr);

            }
          },
        });
</script>
            