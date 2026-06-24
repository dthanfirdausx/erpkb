<?php
if (!function_exists('prod_t')) {
  function prod_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('prod_h')) {
  function prod_h($key, $fallback = '') { return htmlspecialchars((string) prod_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('prod_js')) {
  function prod_js($key, $fallback = '') { return json_encode(prod_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
?>
<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                        Import BOM
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>pemasukan-hamparan">Import BOM </a></li>
                        <li class="active">Bom  List</li>
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
                              <form id="upload_tpb_formx" method="post" enctype="multipart/form-data" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/bom/bom_action.php?act=import_bom">  
                              <input type="hidden" name="tujuan" value="1" id="tujuan">                 
                              
                                <div class="form-group">
                                    <label for="Tanggal BPB" class="control-label col-lg-2">Pilih File </label>
                                    <div class="col-lg-10" style="float: left">
                                      <div class="input-group" >
                                          <input type="file" class="form-control" id="file_tpb" placeholder="File TPB" name="file_tpb" /> 
                                          <a href="<?= base_url() ?>upload/template/template_bom.xls"><i class='fa fa-download'></i> Template Import</a>
                                          
                                      </div> 
                                    </div>  
                                
                                     
                                </div><!-- /.form-group -->
                       
                                 <div class="form-group">
                                  <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                                  <div class="col-lg-10">

                                   <a class="btn btn-primary" onclick="upload_tpb()"><i class="fa fa-upload"></i> Upload</a>
                                    <!-- <input type="submit" name="upload"> -->
                                  </div>
                                </div><!-- /.form-group -->

                              </form>
                               
                        <div class="row">
                           <div class="col-md-12" id="pesan_sukses">
                             
                           </div> 
                           <div class="col-md-12" id="pesan_gagal">
                             
                           </div> 
                        </div>
                        
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
                    <h4 class="modal-title">Detail Pemasukan</h4>
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
                $edit = "<a data-id='+aData[indek]+' href=".base_index()."pemasukan-hamparan/edit/'+aData[indek]+' class=\"btn btn-primary btn-sm edit_data \" data-toggle=\"tooltip\" title=\"Edit\"><i class=\"fa fa-pencil\"></i></a>";
              } else {
                  $edit ="";
              }
            if ($role_act['del_act']=='Y') {
                $del = "<button data-id='+aData[indek]+' data-uri=".base_admin()."modul/pemasukan_hamparan/pemasukan_hamparan_action.php".' class="btn btn-danger hapus_dtb_notif btn-sm" data-toggle="tooltip" title="Hapus" data-variable="dtb_pemasukan_hamparan"><i class="fa fa-trash"></i></button>';
            } else {
                $del="";
            }
                             }
            }

        ?>

    </section><!-- /.content --> 
  
       <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script type="text/javascript">

          function upload_tpb() {
             $('#loadnya').show();
             //let myForm = document.getElementById('upload_tpb_form');
               // var form = $('#upload_tpb_form')[0]; // You need to use standard javascript object here
               //var formname = $('#upload_tpb_form').attr('name');
               //   var form = $('#upload_tpb_form').serialize();                
          //var FormData = new FormData($('#upload_tpb_form').serialize()[1]); 
          var formData = new FormData();
          formData.append('tujuan', $("#tujuan").val());
         // formData.append('action', 'previewImg');
          // Attach file
          formData.append('fileupload', $('input[type=file]')[0].files[0]); 
              $.ajax({
            type: 'POST',  
            dataType: 'json',
            url: '<?=base_admin();?>modul/bom/bom_action.php?act=import_bom',
            data :formData,
            type : 'POST', 
            processData: false, 
            contentType: false,
               success: function(data) {
                   $('#loadnya').hide();
                   Swal.fire({
  title: data.pesan,
  showDenyButton: false,
  showCancelButton: false,
  confirmButtonText: 'Oke',
//  denyButtonText: `Don't save`,
}).then((result) => {
  /* Read more about isConfirmed, isDenied below */
  if (result.isConfirmed) {
    //Swal.fire('Saved!', '', 'success')
    document.location='<?= base_url() ?>index.php/bom';
  } else if (result.isDenied) {
   // Swal.fire('Changes are not saved', '', 'info')
  }
})
                } 
            //async:false
            });
          }
      
      
    $("#dtb_pemasukan_hamparan").DataTable({
           "fnCreatedRow": function( nRow, aData, iDataIndex ) {
            var indek = aData.length-1;
            var kolom = aData.length-14;  
            $('td:eq('+kolom+')', nRow).html('<?=$edit;?> <?=$del;?>');
              $(nRow).attr('id', 'line_'+aData[indek]);
              },
              "dom": "<'row'<'col-sm-12'B>>" + "<'row'<'col-sm-6'l><'col-sm-6'f>>" +"<'row'<'col-sm-12'tr>>" +"<'row'<'col-sm-5'i><'col-sm-7'p>>",

              buttons: [
              {
                 extend: 'collection',
                 text:<?=prod_js('common_export_data', 'Export Data');?>,
                 buttons: [ 'pdfHtml5', 'csvHtml5', 'copyHtml5', 'excelHtml5' ],

              }
              ], 
           'bProcessing': true,
            'bServerSide': true,
            
           'columnDefs': [ 
                {
            'width': '10%',
            'targets': 0,
            "searchable": false, "orderable": false,
            'orderable': false,
            'searchable': false,
            'className': 'dt-center'
          }
             ],

    
            'ajax':{
              url :'<?=base_admin();?>modul/pemasukan_hamparan/pemasukan_hamparan_data.php',
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

  $('#dtb_pemasukan_hamparan').on('draw.dt', function() {
          init_selected()
      });

      $('#select_all').on('click', function() {
          select_deselect('select')
      });
      $('#deselect_all').on('click', function() {
          select_deselect('unselect')
  });

function filter() {
      $("#dtb_pemasukan_hamparan").dataTable().fnDraw(); 
  } 


  // $(document).on('click', '#dtb_pemasukan_hamparan tbody tr td', function(event) {
  //     var btn = $(this).find('button');
  //     if (btn.length == 0) {
  //         $(this).parents('tr').toggleClass('DTTT_selected selected');
  //         var selected = check_selected();
  //         init_selected();

  //     }
  // });

  function show_detail(id) {
    $('#loadnya').show();
        $.ajax({
            type: 'POST',
           // dataType: 'json',
            url: '<?=base_admin();?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=show_detail',
            data: {id:id}, 
               success: function(data) {
                  $('#loadnya').hide();
                  $("#isi_detail").html(data);
                  $("#modal_detail").modal("show");
                } 
            //async:false
        });
  }



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
      var table_select = $('#dtb_pemasukan_hamparan tbody tr.selected');
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
          $('#dtb_pemasukan_hamparan tbody tr').addClass('DTTT_selected selected')
      } else {
          $('#dtb_pemasukan_hamparan tbody tr').removeClass('DTTT_selected selected')
      }
      init_selected()
  }

 


/* Add a click handler for the delete row */
  $('#bulk_delete').click( function() {
    var anSelected = fnGetSelected( dtb_pemasukan_hamparan );
    var data_array_id = check_selected();
    var all_ids = data_array_id.toString();
    $('#ucing').modal({ keyboard: false }).one('click', '#delete', function (e) {
        $('#loadnya').show();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '<?=base_admin();?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=del_massal',
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
                               dtb_pemasukan_hamparan.draw();
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
            