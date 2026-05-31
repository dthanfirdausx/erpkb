<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                        Sales Invoice
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>sales-invoice">Sales Invoice</a></li>
                        <li class="active">Sales Invoice List</li>
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
                                      <a href="<?=base_index();?>sales-invoice/tambah" class="btn btn-primary "><i class="fa fa-plus"></i> <?php echo $lang["add_button"];?></a>
                                      <?php
                                          }
                                      }
                                  }
                                ?>
                                <form id="form_filter_invoice" class="form-horizontal">

  <!-- Tanggal Invoice -->
  <div class="form-group">
    <label class="control-label col-lg-2">Tanggal Invoice</label>

    <div class="col-lg-2">
      <div class="input-group date" id="tgl1">
        <input type="text"
               class="form-control"
               id="tgl_awal"
               placeholder="tanggal awal"
               autocomplete="off"/>
        <span class="input-group-addon">
          <span class="glyphicon glyphicon-calendar"></span>
        </span>
      </div>
    </div>

    <div class="col-lg-2">
      <div class="input-group date" id="tgl2">
        <input type="text"
               class="form-control"
               id="tgl_akhir"
               placeholder="tanggal akhir"
               autocomplete="off"/>
        <span class="input-group-addon">
          <span class="glyphicon glyphicon-calendar"></span>
        </span>
      </div>
    </div>
  </div>

  <!-- Customer -->
  <div class="form-group">
    <label class="control-label col-lg-2">Customer</label>

    <div class="col-lg-4">
      <select id="customer" class="form-control chzn-select">
        <option value="all">Semua</option>

        <?php
        foreach ($db->fetch_all("penerima") as $isi) {
            echo "<option value='$isi->kode_penerima'>$isi->nama</option>";
        }
        ?>

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
                               <!--   <a style="cursor: pointer;" onclick="sinc_acc()" class="btn btn-success "><i class="fa fa-gear"></i> Sinkron Accurate</a> -->
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
                        <table id="dtb_sales_invoice" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                  <th>No</th>
                                  <th>Bill To</th>
                                  <th>Ship To</th>
                                  <th>Invoice Date</th>
                                  <th>Sales Invoice No</th>
                                  <th>PO No</th>
                                  <th>Term</th>
                                  <th>Currency</th>
                                  <th>Ship Date</th>
                                  <th>No Do</th>
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
                $edit = "<a data-id='+aData[indek]+' href=".base_index()."sales-invoice/edit/'+aData[indek]+' class=\"btn btn-primary btn-sm edit_data \" data-toggle=\"tooltip\" title=\"Edit\"><i class=\"fa fa-pencil\"></i></a>";
              } else {
                  $edit ="";
              }
            if ($role_act['del_act']=='Y') {
                $del = "<button data-id='+aData[indek]+' data-uri=".base_admin()."modul/sales_invoice/sales_invoice_action.php".' class="btn btn-danger hapus_dtb_notif btn-sm" data-toggle="tooltip" title="Hapus" data-variable="dtb_sales_invoice"><i class="fa fa-trash"></i></button>';
            } else {
                $del="";
            }
                             }
            }

        ?>

    </section><!-- /.content -->

     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script type="text/javascript">
     function sinc_acc(){
         $("#loadnya").show();
         $.ajax({
           url : '<?=base_admin();?>modul/sales_invoice/sales_invoice_action.php?act=sync_acc',
           type : 'POST',
           dataType : 'JSON',
           success : function(data){
            $("#loadnya").hide();
            if (data.s) {
               Swal.fire({
                  title: 'Berhasil!',
                  text: 'Data berhasil disimpan!',
                  icon: 'success',
                  confirmButtonText: 'OK'
              }); 
            }else{
              var pesanx = "";
              data.d.forEach((item, index) => {
                console.log(`Item ke-${index + 1}:`);
                item.d.forEach(pesan => {
                  pesanx = pesan;
                });
              });
              Swal.fire({
                icon: "error",
                title: "Oops...",
                text: pesanx
                //footer: '<a href="#">Why do I have this issue?</a>'
              });
            }
            
           } 
         })
      }  
      
      var dtb_sales_invoice = $("#dtb_sales_invoice").DataTable({
           "fnCreatedRow": function( nRow, aData, iDataIndex ) {
            var indek = aData.length-1;
            $('td:eq('+indek+')', nRow).html('<a target="_BLANK" href="<?=base_url();?>modul/sales_invoice/print.php?id='+aData[indek]+'"  class="btn btn-success btn-sm" data-toggle="tooltip" title="Print"><i class="fa fa-print"></i></a> <?=$edit;?> <?=$del;?>');
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
            'targets': [10],
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
              url :'<?=base_admin();?>modul/sales_invoice/sales_invoice_data.php',
              type: 'post',
              data: function(d){

                  d.tgl_awal  = $('#tgl_awal').val();
                  d.tgl_akhir = $('#tgl_akhir').val();
                  d.customer  = $('#customer').val();

              },
              error: function (xhr, error, thrown) {
                  console.log(xhr);
              }
          },
        }); 

      // filter
$('#btn_filter').click(function(){
    dtb_sales_invoice.draw();
});

// reset
$('#btn_reset').click(function(){

    $('#form_filter_invoice')[0].reset();

    $("#customer").val("all").trigger("chosen:updated");

    dtb_sales_invoice.draw();

});

// export excel
$('#btn_excel').click(function(){

    var tgl_awal  = $('#tgl_awal').val();
    var tgl_akhir = $('#tgl_akhir').val();
    var customer  = $('#customer').val();

    window.open(
        "<?=base_admin();?>modul/sales_invoice/sales_invoice_action.php?act=excel"+
        "&tgl_awal="+tgl_awal+
        "&tgl_akhir="+tgl_akhir+
        "&customer="+customer
    );

});

  $('#dtb_sales_invoice').on('draw.dt', function() {
          init_selected()
      });

      $('#select_all').on('click', function() {
          select_deselect('select')
      });
      $('#deselect_all').on('click', function() {
          select_deselect('unselect')
  });



  $(document).on('click', '#dtb_sales_invoice tbody tr td', function(event) {
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
      var table_select = $('#dtb_sales_invoice tbody tr.selected');
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
          $('#dtb_sales_invoice tbody tr').addClass('DTTT_selected selected')
      } else {
          $('#dtb_sales_invoice tbody tr').removeClass('DTTT_selected selected')
      }
      init_selected()
  }




/* Add a click handler for the delete row */
  $('#bulk_delete').click( function() {
    var anSelected = fnGetSelected( dtb_sales_invoice );
    var data_array_id = check_selected();
    var all_ids = data_array_id.toString();
    $('#ucing').modal({ keyboard: false }).one('click', '#delete', function (e) {
        $('#loadnya').show();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '<?=base_admin();?>modul/sales_invoice/sales_invoice_action.php?act=del_massal',
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
                               dtb_sales_invoice.draw();
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
            