<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                        Neraca
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>neraca">Neraca</a></li>
                        <li class="active">Neraca List</li>
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
                                <div class="row">
                                  <div class="row" style="margin-bottom:15px;">

    <div class="col-md-12">

       <form id="form_filter_neraca" class="form-horizontal">

    <!-- Tanggal -->
    <div class="form-group">

        <label class="control-label col-lg-2">
           Tanggal
        </label>

        <div class="col-lg-2">

            <div class="input-group date" id="tgl1">

                <input type="text"
                       class="form-control"
                       id="start_date"
                       placeholder="tanggal"
                       autocomplete="off"/>

                <span class="input-group-addon">
                    <span class="glyphicon glyphicon-calendar"></span>
                </span>

            </div>

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
                Tampilkan

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

        </div>

    </div>

</form>

<hr>



    </div>

</div>
                            </div>
 <div class="alert alert-warning fade in error_data_delete" style="display:none">
          <button type="button" class="close hide_alert_notif">&times;</button>
          <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
        </div>
                      <table id="table_neraca"
       class="table table-bordered table-striped">

   <!--  <thead>

        <tr>

            <th width="5%">No</th>
            <th>Kategori Akun</th>
            <th>Kategori</th>
            <th>COA</th>
            <th>Nama COA</th>
            <th>Debet</th>
            <th>Kredit</th>
            <th>Saldo Akhir</th>

        </tr>

    </thead> -->

  <tbody id="result_neraca">

    <tr>

        <td colspan="8" align="center">

            Silahkan filter tanggal dahulu

        </td>

    </tr>

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
                $edit = "<a data-id='+aData[indek]+' href=".base_index()."neraca/edit/'+aData[indek]+' class=\"btn btn-primary btn-sm edit_data \" data-toggle=\"tooltip\" title=\"Edit\"><i class=\"fa fa-pencil\"></i></a>";
              } else {
                  $edit ="";
              }
            if ($role_act['del_act']=='Y') {
                $del = "<button data-id='+aData[indek]+' data-uri=".base_admin()."modul/neraca/neraca_action.php".' class="btn btn-danger hapus_dtb_notif btn-sm" data-toggle="tooltip" title="Hapus" data-variable="dtb_neraca"><i class="fa fa-trash"></i></button>';
            } else {
                $del="";
            }
                             }
            }

        ?>

    </section><!-- /.content -->
 
        <script type="text/javascript">
          $('.datepicker').datepicker({

    format:'yyyy-mm-dd',
    autoclose:true,
    todayHighlight:true

});

          function load_neraca(){

    var start_date = $('#start_date').val();
    var end_date   = $('#end_date').val();

    $.ajax({

        url  : '<?=base_admin();?>modul/neraca/neraca_action.php?act=filter',
        type : 'POST',

        data : {

            start_date : start_date
          //  end_date   : end_date

        },

        dataType:'json',

        beforeSend:function(){

            $('#result_neraca').html(`

                <tr>

                    <td colspan="8" align="center">

                        <i class="fa fa-spinner fa-spin"></i>
                        Loading...

                    </td>

                </tr>

            `);

        },

        success:function(response){

            $('#result_neraca').html(response.html);

        }

    });

}

          $('#btn_filter').click(function(){

    load_neraca();

});
          $('#btn_excel').click(function(){

    var start_date = $('#start_date').val();
   // var end_date   = $('#end_date').val();

    window.open(

        "<?=base_admin();?>modul/neraca/neraca_action.php?act=excel"+

        "&start_date="+start_date

    );

});


      
      
      var dtb_neraca = $("#dtb_neraca").DataTable({
           "fnCreatedRow": function( nRow, aData, iDataIndex ) {
            var indek = aData.length-1;
            $('td:eq('+indek+')', nRow).html('<a href="<?=base_index();?>neraca/detail/'+aData[indek]+'"  class="btn btn-success btn-sm" data-toggle="tooltip" title="Detail"><i class="fa fa-eye"></i></a> <?=$edit;?> <?=$del;?>');
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
              url :'<?=base_admin();?>modul/neraca/neraca_data.php',
            type: 'post',  // method  , by default get
            error: function (xhr, error, thrown) {
            console.log(xhr);

            }
          },
        });

  $('#dtb_neraca').on('draw.dt', function() {
          init_selected()
      });

      $('#select_all').on('click', function() {
          select_deselect('select')
      });
      $('#deselect_all').on('click', function() {
          select_deselect('unselect')
  });



  $(document).on('click', '#dtb_neraca tbody tr td', function(event) {
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
      var table_select = $('#dtb_neraca tbody tr.selected');
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
          $('#dtb_neraca tbody tr').addClass('DTTT_selected selected')
      } else {
          $('#dtb_neraca tbody tr').removeClass('DTTT_selected selected')
      }
      init_selected()
  }




/* Add a click handler for the delete row */
  $('#bulk_delete').click( function() {
    var anSelected = fnGetSelected( dtb_neraca );
    var data_array_id = check_selected();
    var all_ids = data_array_id.toString();
    $('#ucing').modal({ keyboard: false }).one('click', '#delete', function (e) {
        $('#loadnya').show();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '<?=base_admin();?>modul/neraca/neraca_action.php?act=del_massal',
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
                               dtb_neraca.draw();
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
            