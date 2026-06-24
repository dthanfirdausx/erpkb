<script src="<?=base_admin();?>assets/plugins/select2/select2.min.js"></script>

<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                        Log Aktifitas
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>log-aktifitas">Log Aktifitas</a></li>
                        <li class="active">Log Aktifitas List</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                <div class="box-header">
                               
                            </div><!-- /.box-header -->
                            <div class="box-body table-responsive">
                                <form id="form_filter_log" class="form-horizontal">
                                  <div class="form-group">
                                    <label class="control-label col-lg-2">Tanggal Aktivitas</label>
                                    <div class="col-lg-2">
                                      <div class="input-group date">
                                        <input type="text" id="start_date" class="form-control" placeholder="Tanggal mulai" autocomplete="off">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                      </div>
                                    </div>
                                    <div class="col-lg-2">
                                      <div class="input-group date">
                                        <input type="text" id="end_date" class="form-control" placeholder="Tanggal selesai" autocomplete="off">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                      </div>
                                    </div>
                                  </div>
                                  <div class="form-group">
                                    <label for="filter_user" class="control-label col-lg-2">User</label>
                                    <div class="col-lg-4">
                                      <select id="filter_user" class="form-control select2" data-placeholder="Semua User">
                                        <option value="">Semua User</option>
                                        <?php
                                        $logUsers = $db->query(
                                          "select distinct user from log_aktifitas
                                           where lower(trim(coalesce(user, ''))) != 'guest'
                                             and trim(coalesce(user, '')) != ''
                                           order by user asc"
                                        );
                                        foreach ($logUsers as $logUser) {
                                        ?>
                                          <option value="<?= htmlspecialchars($logUser->user, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($logUser->user, ENT_QUOTES, 'UTF-8'); ?>
                                          </option>
                                        <?php } ?>
                                      </select>
                                    </div>
                                  </div>
                                  <div class="form-group">
                                    <label class="control-label col-lg-2">&nbsp;</label>
                                    <div class="col-lg-10">
                                      <button type="button" id="btn_filter_log" class="btn btn-primary">
                                        <i class="fa fa-search"></i> Tampilkan
                                      </button>
                                      <button type="button" id="btn_reset_log" class="btn btn-default">
                                        <i class="fa fa-refresh"></i> Reset
                                      </button>
                                      <button type="button" id="btn_excel_log" class="btn btn-success">
                                        <i class="fa fa-file-excel-o"></i> Excel
                                      </button>
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
                        <table id="dtb_log_aktifitas" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                  <th>No</th>
                                  <th>Deskripsi</th>
                                  <th>User</th>
                                  <th>Tanggal</th>
                                
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
                $edit = "<a data-id='+aData[indek]+' href=".base_index()."log-aktifitas/edit/'+aData[indek]+' class=\"btn btn-primary btn-sm edit_data \" data-toggle=\"tooltip\" title=\"Edit\"><i class=\"fa fa-pencil\"></i></a>";
              } else {
                  $edit ="";
              }
            if ($role_act['del_act']=='Y') {
                $del = "<button data-id='+aData[indek]+' data-uri=".base_admin()."modul/log_aktifitas/log_aktifitas_action.php".' class="btn btn-danger hapus_dtb_notif btn-sm" data-toggle="tooltip" title="Hapus" data-variable="dtb_log_aktifitas"><i class="fa fa-trash"></i></button>';
            } else {
                $del="";
            }
                             }
            }

        ?>

    </section><!-- /.content -->

        <script type="text/javascript">
      $('.input-group.date').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
      });

      $('#filter_user').select2({
        width: '100%',
        placeholder: 'Semua User',
        allowClear: true
      });

      var dtb_log_aktifitas = $("#dtb_log_aktifitas").DataTable({
           // "fnCreatedRow": function( nRow, aData, iDataIndex ) {
           //  var indek = aData.length-1;
           //  $('td:eq('+indek+')', nRow).html('<a href="<?=base_index();?>log-aktifitas/detail/'+aData[indek]+'"  class="btn btn-success btn-sm" data-toggle="tooltip" title="Detail"><i class="fa fa-eye"></i></a> <?=$edit;?> <?=$del;?>');
           //    $(nRow).attr('id', 'line_'+aData[indek]);
           //    },
              "dom": "<'row'<'col-sm-6'l><'col-sm-6'f>>" +"<'row'<'col-sm-12'tr>>" +"<'row'<'col-sm-5'i><'col-sm-7'p>>",
           'bProcessing': true,
            'bServerSide': true,
            
           'order': [[3, 'desc']],
           'columnDefs': [ {
            'width': '70%',
            'targets': 1
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
              url :'<?=base_admin();?>modul/log_aktifitas/log_aktifitas_data.php',
            type: 'post',  // method  , by default get
            data: function (d) {
              d.start_date = $('#start_date').val();
              d.end_date = $('#end_date').val();
              d.filter_user = $('#filter_user').val();
            },
            error: function (xhr, error, thrown) {
            console.log(xhr);

            }
          },
        });

  $('#btn_filter_log').on('click', function () {
    dtb_log_aktifitas.ajax.reload();
  });

  $('#btn_reset_log').on('click', function () {
    $('#start_date, #end_date').val('');
    $('#filter_user').val('').trigger('change');
    dtb_log_aktifitas.ajax.reload();
  });

  $('#btn_excel_log').on('click', function () {
    var url = '<?=base_admin();?>modul/log_aktifitas/log_aktifitas_action.php?act=excel';
    url += '&start_date=' + encodeURIComponent($('#start_date').val());
    url += '&end_date=' + encodeURIComponent($('#end_date').val());
    url += '&filter_user=' + encodeURIComponent($('#filter_user').val());
    window.location = url;
  });

  $('#dtb_log_aktifitas').on('draw.dt', function() {
          init_selected()
      });

      $('#select_all').on('click', function() {
          select_deselect('select')
      });
      $('#deselect_all').on('click', function() {
          select_deselect('unselect')
  });



  $(document).on('click', '#dtb_log_aktifitas tbody tr td', function(event) {
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
      var table_select = $('#dtb_log_aktifitas tbody tr.selected');
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
          $('#dtb_log_aktifitas tbody tr').addClass('DTTT_selected selected')
      } else {
          $('#dtb_log_aktifitas tbody tr').removeClass('DTTT_selected selected')
      }
      init_selected()
  }




/* Add a click handler for the delete row */
  $('#bulk_delete').click( function() {
    var anSelected = fnGetSelected( dtb_log_aktifitas );
    var data_array_id = check_selected();
    var all_ids = data_array_id.toString();
    $('#ucing').modal({ keyboard: false }).one('click', '#delete', function (e) {
        $('#loadnya').show();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '<?=base_admin();?>modul/log_aktifitas/log_aktifitas_action.php?act=del_massal',
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
                               dtb_log_aktifitas.draw();
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
