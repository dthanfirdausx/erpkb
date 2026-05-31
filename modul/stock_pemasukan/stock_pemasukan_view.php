<!-- Content Header (Page header) -->
<style type="text/css">
  .dt-right {
    text-align: right !important;
}
</style>
                <section class="content-header">
                    <h1>
                        Stock Pemasukan
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>stock-pemasukan">Stock Pemasukan</a></li>
                        <li class="active">Stock Pemasukan List</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                
                            <div class="box-body table-responsive">
                               <form id="input_pemasukan_hamparan" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=in">                   
                              
                                <div class="form-group">
                                    <label for="Tanggal BPB" class="control-label col-lg-2">Kategori </label>
                                    <div class="col-lg-4" style="float: left">
                                      <select class="form-control" id="kategori">
                                         <option value="">Pilih Kategori</option>
                                         <?php
                                         $q= $db->query("select * from kategori");
                                         foreach ($q as $k) {
                                          ?>
                                           <option value="<?= $k->kd_kategori ?>"><?= $k->nm_kategori ?></option>
                                          <?php
                                         }
                                         ?>
                                       
                                      </select>
                                    </div>  
                                
                                    
                                </div><!-- /.form-group -->
                       
                                 <div class="form-group">
                                  <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                                  <div class="col-lg-10">

                                   <a class="btn btn-primary" onclick="filter()"><i class="fa fa-gear"></i> Filter</a>
                             
                                  </div>
                                </div><!-- /.form-group -->

                              </form>
                                <a class="btn btn-primary" style="float: right;display: none;" href="<?= base_url() ?>modul/stock_pemasukan/stock_pemasukan_action.php?act=download_excel"> <i class="fa fa-download"></i> Download</a>
 <div class="alert alert-warning fade in error_data_delete" style="display:none">
          <button type="button" class="close hide_alert_notif">&times;</button>
          <i class="icon fa fa-warning"></i> <span class="isi_warning_delete"></span>
        </div>
                        <table id="dtb_stock_pemasukan" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                  <th>No</th>
                                  <th></th> 
                                  <th>Kode</th>
                                  <th>Nama Barang</th>
                                  <th style="text-align:center">Stock</th>
                                  <th>Satuan</th>
                                  <th>Kategori</th>
                                 
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
                $del = "<button data-id='+aData[indek]+' data-uri=".base_admin()."modul/stock_pemasukan/stock_pemasukan_action.php".' class="btn btn-danger hapus_dtb_notif btn-sm" data-toggle="tooltip" title="Hapus" data-variable="dtb_stock_pemasukan"><i class="fa fa-trash"></i></button>';
            } else {
                $del="";
            }
                             }
            }

        ?>

    <div class="modal" id="modal_stock_pemasukan" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"> <div class="modal-dialog modal-lg"> <div class="modal-content"><div class="modal-header"> <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> <h4 class="modal-title"><?php echo $lang["add_button"];?> Stock Pemasukan</h4> </div> <div class="modal-body" id="isi_stock_pemasukan"> </div> </div><!-- /.modal-content --> </div><!-- /.modal-dialog --> </div>
     <div class="modal" id="detail_stock" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"> 
      <div class="modal-dialog modal-lg" style="width: 90%"> 
        <div class="modal-content">
          <div class="modal-header"> 
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">×</span></button> 
              <h4 class="modal-title">Detail Stock Pemasukan</h4> 
            </div> 
            <div class="modal-body" id="isi_detail">
             </div> 
          </div>
        </div><
      </div>
    
    </section><!-- /.content -->

    <script type="text/javascript">

     
    
      
    $(".table").on('click','.edit_data',function(event) {
        $("#loadnya").show();
        event.preventDefault();
        var currentBtn = $(this);

        id = currentBtn.attr('data-id');

        $.ajax({
            url : "<?=base_admin();?>modul/stock_pemasukan/stock_pemasukan_edit.php",
            type : "post",
            data : {id_data:id},
            success: function(data) {
                $("#isi_stock_pemasukan").html(data);
                $("#loadnya").hide();
          }
        });

    $('#modal_stock_pemasukan').modal({ keyboard: false,backdrop:'static' });

    });
    
      var dtb_stock_pemasukan = $("#dtb_stock_pemasukan").DataTable({
           "fnCreatedRow": function( nRow, aData, iDataIndex ) {
            var indek = aData.length-1;
            $('td:eq('+indek+')', nRow).html('<a href="<?=base_index();?>stock-pemasukan/detail/'+aData[indek]+'"  class="btn btn-success btn-sm" data-toggle="tooltip" title="Detail"><i class="fa fa-eye"></i></a> <?=$edit;?> <?=$del;?>');
              $(nRow).attr('id', 'line_'+aData[indek]);
              },
               aLengthMenu: [
        [25, 50, 100, 200, -1],
        [25, 50, 100, 200, "All"]
    ],
              "dom": "<'row'<'col-sm-12'B>>" + "<'row'<'col-sm-6'l><'col-sm-6'f>>" +"<'row'<'col-sm-12'tr>>" +"<'row'<'col-sm-5'i><'col-sm-7'p>>",

             buttons: [
{
    extend: 'collection',
    text: 'Export Data', 
    buttons: [
 
        {
            extend: 'pdfHtml5',
            title: 'Laporan Stock Pemasukan',
            messageTop: '<?=  infokb()->nama ?>',
            exportOptions: {
                columns: [0,2,3,4,5,6] // ❌ skip kolom 1 (sinkron)
            }
        },

        {
            extend: 'excelHtml5',
            title: 'Laporan Stock Pemasukan',
            messageTop: '<?=  infokb()->nama ?>',
            exportOptions: {
                columns: [0,2,3,4,5,6]
            }
        },

        {
            extend: 'csvHtml5',
            title: 'Laporan Stock Pemasukan',
            messageTop: '<?=  infokb()->nama ?>',
            exportOptions: {
                columns: [0,2,3,4,5,6]
            }
        },

        {
            extend: 'copyHtml5',
            title: 'Laporan Stock Pemasukan',
            messageTop: '<?=  infokb()->nama ?>',
            exportOptions: {
                columns: [0,2,3,4,5,6]
            }
        }

    ]
}
],
           'bProcessing': true,
            'bServerSide': true,
            
           'columnDefs': [ 
          
            {
            'targets': [1],
              'visible': false
            //  'searchable': false
            },
            {
            'targets': [7],
              "visible": false,
            },
                {
            'width': '5%',
            'targets': 0,
            'orderable': false,
            'searchable': false,
            'className': 'dt-center'
          },
            {
            'width': '5%',
            'targets': 1,
            'orderable': false,
            'searchable': false,
            'className': 'dt-center'
          },
           {
                'targets': [4],            // index kolom Stock
                'className': 'dt-right' // rata kanan
            }
             ], 

    
            'ajax':{
              url :'<?=base_admin();?>modul/stock_pemasukan/stock_pemasukan_data.php',
            type: 'post',  // method  , by default get
             data:   function ( d ) {
                    d.kategori = $("#kategori").val();
                   // d.tgl_akhir = $("#tgl_akhir").val();
                //    d.jenisbc = $("#jenisbc").val();
                   // d.ket   = $("#ket").val();
                    
                  },
            error: function (xhr, error, thrown) {
            console.log(xhr);

            }
          },
        });

       function filter() {
      
      $("#dtb_stock_pemasukan").dataTable().fnDraw(); 
  }

     function sinkron_stock(kode,posisi,id){
         $("#btn_"+id).attr("disabled",true);
         $("#btn_"+id).html("<i class='fa fa-gear'></i> Loading... ");
         $.ajax({
              url : "<?=base_admin();?>modul/stock_pemasukan/stock_pemasukan_action.php?act=sinkron_stock",
              type : "POST",
              data : {
                 kd_barang : kode,
                 posisi : posisi,
                 id : id
              },
              success: function(data) {
                  $("#btn_"+id).attr("disabled",false);
                  $("#btn_"+id).html("<i class='fa fa-gear'></i> Sinkron Stock ");
                  dtb_stock_pemasukan.ajax.reload();
              }
          });
      }

     function get_detail_stock(kd_barang){

   $.ajax({
        url : "<?=base_admin();?>modul/stock_pemasukan/stock_pemasukan_action.php?act=show_detail_stock",
        type : "POST",
        data : {
           kd_barang : kd_barang
        },
        success: function(data) {
            $("#isi_detail").html(data);
            $("#detail_stock").modal('show');
        }
    });
}
      
      $("#add_stock_pemasukan").click(function() {
          $.ajax({
              url : "<?=base_admin();?>modul/stock_pemasukan/stock_pemasukan_add.php",
              type : "GET",
              success: function(data) {
                  $("#isi_stock_pemasukan").html(data);
              }
          });

      $('#modal_stock_pemasukan').modal({ keyboard: false,backdrop:'static',show:true });

    });






/* Add a click handler for the delete row */
  $('#bulk_delete').click( function() {
    var anSelected = fnGetSelected( dtb_stock_pemasukan );
    var data_array_id = check_selected();
    var all_ids = data_array_id.toString();
    $('#ucing').modal({ keyboard: false }).one('click', '#delete', function (e) {
        $('#loadnya').show();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '<?=base_admin();?>modul/stock_pemasukan/stock_pemasukan_action.php?act=del_massal',
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
                               dtb_stock_pemasukan.draw();
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
            