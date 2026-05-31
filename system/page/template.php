<?php
$gallery_detail = $gallery_detail_top.'<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                     Album Gallery
                    </h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'">Album Gallery</a></li>
                        <li class="active">Detail '.ucwords($_POST['page_name']).'</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
<div class="row">
    <div class="col-lg-12">
        <div class="box box-solid box-primary">
                                   <div class="box-header">
                                    <h3 class="box-title">Detail Album Gallery</h3>
                                    <div class="box-tools pull-right">
                                        <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                        <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                    </div>
                                </div>

'.$button_gallery_detail.'

  <button onclick="Uploader.upload();" class="btn btn-primary">
    <i class=\'fa fa-plus\'></i> Upload Foto
  </button>
  <div id="wait" class="hide">
    <img src="<?=base_admin();?>assets/dist/img/upload-indicator.gif" alt="">
  </div>

<div>
  <iframe name="hidden_iframe" id="hidden_iframe" class="hide">
  </iframe>
</div>

<div id="uploaded_images" align="center">

</div>
                  <div class="page-header text-center">

             '.$album_title.'
            var data = JSON.parse(data);
            if (typeof (data[\'error\']) != "undefined") {
                jQuery(\'#uploaded_images\').html(data[\'error\']);
                jQuery(\'#upload_files\').val("");
                jQuery("#wait").addClass(\'hide\');
                return;
            }
            var divs = [];
            jQuery(\'#uploaded_images\').html(divs.join(""));
            jQuery(\'#upload_files\').val("");
            jQuery("#wait").addClass(\'hide\');
        }

        return {
            upload: fnUpload,
            done: fnDone
        }

    }());
</script>';

$list_gallery = '<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                        '.ucwords($_POST['page_name']).'
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'">'.ucwords($_POST['page_name']).'</a></li>
                        <li class="active">'.ucwords($_POST['page_name']).' List</li>
                    </ol>
                </section>
                <!-- Main content -->
                <section class="content">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="box box-solid box-primary">
                                <div class="box-header">
                                <h3 class="box-title">List '.ucwords($_POST['page_name']).'</h3>
                                    <div class="box-tools pull-right">
                                      <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                      <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                    </div>
                                </div>
                  <div class="box-body">
                      <div class="album-top">
                          <div class="col-md-2 ">
                          <?php
                           foreach ($db->fetch_all("sys_menu") as $isi) {
                                if (uri_segment(1)==$isi->url) {
                                    if ($role_act["insert_act"]=="Y") {
                                ?>
                              <a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'/tambah" class="btn btn-primary "><i class="fa fa-plus"></i> Add Album</a>
                              <?php
                                }
                              }
                            }
                          ?>
                          </div>
                          <div class="col-md-10">
                              <form action="" method="get" class="form_cari">
                                  <div class="input-group col-lg-8">
                                  <span class="input-group-btn">
                                  <button class="btn btn-default" type="button"><?php echo $lang["search"];?> !</button>
                                  </span>
                                  <input type="text" name="q" class="form-control" placeholder="Search..."/>
                                  <span class="input-group-btn">
                                  <button type="submit" id="search-btn" class="btn "><i class="fa fa-search"></i></button>
                                  </span>
                                  </div>
                                </form>
                        </div>
                    </div>
              <div class="row">
                  '.$query_album_on_list.'
              </div>
              <div class="row">
                  <div class="col-xs-6" style="margin-top:10px">
                  Showing <?=$count;?> to <?=$no-1;?> of <?=$pg->total_record;?> entries
                  </div>
                  <div class="col-xs-6">
                      <?php
                        if (isset($_GET[\'q\'])) {
                            $pg->url=base_index()."'.$modul_name.'?q=".$_GET[\'q\']."&page=";
                                  }
                            $pg->setParameter(array(
                              \'range\'=>$limit,
                              ));
                              ?>

                            <div class="dataTables_paginate paging_bootstrap">
                                <ul class="pagination">
                                    <?=$pg->create();?>
                                </ul>
                            </div>
                          </div>
                      </div>
                  </div>
                </div>
            </div>
          </div>
            </section><!-- /.content -->';

$list_table ='<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                        '.ucwords($_POST['page_name']).'
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'">'.ucwords($_POST['page_name']).'</a></li>
                        <li class="active">'.ucwords($_POST['page_name']).' List</li>
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
                                      <a '.$button_modal.' class="btn btn-primary "><i class="fa fa-plus"></i> <?php echo $lang["add_button"];?></a>
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
                        <table id="dtb_'.$modul_name.'" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                  '.$head_no.$table_header.'
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
                $edit = "<a data-id=\'+aData[indek]+\' '.$button_edit_modal.' class=\"btn btn-primary btn-sm edit_data \" data-toggle=\"tooltip\" title=\"Edit\"><i class=\"fa fa-pencil\"></i></a>";
              } else {
                  $edit ="";
              }
            if ($role_act[\'del_act\']==\'Y\') {
                $del = "<button data-id=\'+aData[indek]+\' data-uri=".base_admin()."modul/'.strtolower(str_replace(" ", "_", $_POST['page_name'])).'/'.strtolower(str_replace(" ", "_", $_POST['page_name'])).'_action.php".\' class="btn btn-danger hapus_dtb_notif btn-sm" data-toggle="tooltip" title="Hapus" data-variable="dtb_'.$modul_name.'"><i class="fa fa-trash"></i></button>\';
            } else {
                $del="";
            }
                             }
            }

        ?>
'.$modal_template.'
    </section><!-- /.content -->

        <script type="text/javascript">
      '.$js_modal.'
      '.$js_modal_edit.'
      var dtb_'.$modul_name.' = $("#dtb_'.$modul_name.'").DataTable({
           "fnCreatedRow": function( nRow, aData, iDataIndex ) {
            var indek = aData.length-1;'."
            $('td:eq('+indek+')', nRow).html('<a href=\"<?=base_index();?>".strtolower(str_replace(" ", "-", $_POST['page_name']))."/detail/'+aData[indek]+'\"  class=\"btn btn-success btn-sm\" data-toggle=\"tooltip\" title=\"Detail\"><i class=\"fa fa-eye\"></i></a> ".'<?=$edit;?> <?=$del;?>'."');
              $(nRow).attr('id', 'line_'+aData[indek]);
              },
              \"dom\": \"<'row'<'col-sm-12'B>>\" + \"<'row'<'col-sm-6'l><'col-sm-6'f>>\" +\"<'row'<'col-sm-12'tr>>\" +\"<'row'<'col-sm-5'i><'col-sm-7'p>>\",

              buttons: [
              {
                 extend: 'collection',
                 text: 'Export Data',
                 buttons: [ 'pdfHtml5', 'csvHtml5', 'copyHtml5', 'excelHtml5' ],

              }
              ],
           'bProcessing': true,
            'bServerSide': true,
            ".$column_def."
            'ajax':{
              url :'<?=base_admin();?>modul/".strtolower(str_replace(" ", "_", $_POST['page_name']))."/".strtolower(str_replace(" ", "_", $_POST['page_name']))."_data.php',
            type: 'post',  // method  , by default get
            error: function (xhr, error, thrown) {
            console.log(xhr);

            }
          },
        });

  $('#dtb_".$modul_name."').on('draw.dt', function() {
          init_selected()
      });

      $('#select_all').on('click', function() {
          select_deselect('select')
      });
      $('#deselect_all').on('click', function() {
          select_deselect('unselect')
  });



  $(document).on('click', '#dtb_".$modul_name." tbody tr td', function(event) {
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
      var table_select = $('#dtb_".$modul_name." tbody tr.selected');
      var array_data_delete = [];
      table_select.each(function() {
          var check_data = $(this).find('.hapus_dtb_notif').attr('data-id');
          if (typeof check_data != 'undefined') {
              array_data_delete.push(check_data)
          }
      });
      $('.selected-data').text(array_data_delete.length + ' ".'<?=$lang["selected_data"];?>'."');
      return array_data_delete
  }


  function select_deselect(type) {
      if (type == 'select') {
          $('#dtb_".$modul_name." tbody tr').addClass('DTTT_selected selected')
      } else {
          $('#dtb_".$modul_name." tbody tr').removeClass('DTTT_selected selected')
      }
      init_selected()
  }




/* Add a click handler for the delete row */
  $('#bulk_delete').click( function() {
    var anSelected = fnGetSelected( dtb_$modul_name );
    var data_array_id = check_selected();
    var all_ids = data_array_id.toString();
    $('#ucing').modal({ keyboard: false }).one('click', '#delete', function (e) {
        $('#loadnya').show();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '<?=base_admin();?>modul/".strtolower(str_replace(" ", "_", $_POST['page_name']))."/".strtolower(str_replace(" ", "_", $_POST['page_name']))."_action.php?act=del_massal',
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
                               dtb_$modul_name.draw();
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
            ";
$list_table_off ='<!-- Content Header (Page header) -->
<section class="content-header">
  <h1>'.ucwords($_POST['page_name']).'</h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
    <li><a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'">'.ucwords($_POST['page_name']).'</a></li>
    <li class="active">'.ucwords($_POST['page_name']).' List</li>
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
          <a '.$button_modal.' class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo $lang["add_button"];?></a>
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
            <table id="dtb_manual" class="table table-bordered table-striped">
              <thead>
                <tr>
                  '.$head_no.'
                  '.$table_header.'
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                '.$select_table.'
              </tbody>
            </table>
            </div><!-- /.box-body -->
            </div><!-- /.box -->
          </div>
        </div>
        </section><!-- /.content -->
'.$modal_template.'
    </section><!-- /.content -->
';
    if ($main_modal_status=='yes') {
    $list_table_off.=
    '<script type="text/javascript">
      '.$js_modal.'
      '.$js_modal_edit.'
    </script>';
  }

$modul_data = '<?php
include "../../inc/config.php";

$columns = array('
.$column_head.'
  );

  //if you want to exclude column for searching, put columns name in array
  //'.$disable_search.'
  '.
  $set_numbering.'

  //set order by column
  $datatable->set_order_by("'.$_POST['order_by'].'");

  //set order by type
  $datatable->set_order_type("'.$_POST['order_by_type'].'");

  //set group by column
  //'.$group_by.'

  $query = $datatable->get_custom("select '.$column_head_query.$query_checkbox.$primary_for_query.' from '.$main_table.$join.'",$columns);

  //buat inisialisasi array data
  $data = array();

  '.$i_init.'
  foreach ($query as $value) {

    //array data
    $ResultData = array();
  '.
    $create_number.'
  '.$dtable_array.'

    $data[] = $ResultData;
    '.$i_increment.'
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>';

$main = '<?php
//you can catch url from this file
switch (uri_segment(2)) {
  case "tambah":
          foreach ($db->fetch_all("sys_menu") as $isi) {
               if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah") {
                          if ($role_act["insert_act"]=="Y") {
                             include "'.$modul_name.'_add.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }
    break;
  case "edit":
    $data_edit = $db->fetch_single_row("'.$main_table.'","'.$primary_key.'",uri_segment(3));
        foreach ($db->fetch_all("sys_menu") as $isi) {
                      if (uri_segment(1)==$isi->url&&uri_segment(2)=="edit") {
                          if ($role_act["up_act"]=="Y") {
                             include "'.$modul_name.'_edit.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }

    break;
      case "detail":
    $data_edit = $db->fetch_single_row("'.$main_table.'","'.$primary_key.'",uri_segment(3));
    include "'.$modul_name.'_detail.php";
    break;
  default:
    include "'.$modul_name.'_view.php";
    break;
}

?>';

$modul_add_modal ='<?php
include "../../inc/config.php";
?>
<style type="text/css"> .datepicker {z-index: 1200 !important; } </style>
 <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
      <form id="input_'.$modul_name.'" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/'.$modul_name.'/'.$modul_name.'_action.php?act=in">
                      '.$input_element.'
                      '.$input_gallery.'

              <div class="form-group">
                <div class="col-lg-12">
                  <div class="modal-footer"> <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
                  <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> <?php echo $lang["cancel_button"];?></button>
                  </div>
                </div>
              </div><!-- /.form-group -->

      </form>
<script type="text/javascript">
    $(document).ready(function() {
         $.each($(".make-switch"), function () {
            $(this).bootstrapSwitch({
            onText: $(this).data("onText"),
            offText: $(this).data("offText"),
            onColor: $(this).data("onColor"),
            offColor: $(this).data("offColor"),
            size: $(this).data("size"),
            labelText: $(this).data("labelText")
            });
          });  
          //chosen select
          $(".chzn-select").chosen();
          $(".chzn-select-deselect").chosen({
              allow_single_deselect: true
          });
        
      //trigger validation onchange
      $(\'select\').on(\'change\', function() {
          $(this).valid();
      });
      //hidden validate because we use chosen select
      $.validator.setDefaults({ ignore: ":hidden:not(select)" });
      
    '.$js_choosen.'
    '.$js_date_input.'
    '.$js_validate.'
    $("#input_'.$modul_name.'").validate({
        errorClass: "help-block",
        errorElement: "span",
        highlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-success").addClass("has-error");
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-error").addClass("has-success");
        },
        errorPlacement: function(error, element) {
            if (element.hasClass("chzn-select")) {
                var id = element.attr("id");
                error.insertAfter("#" + id + "_chosen");
            } else if (element.attr("type") == "checkbox") {
                element.parent().parent().append(error);
            } else if (element.attr("type") == "radio") {
                element.parent().parent().append(error);
            } else {
                error.insertAfter(element);
            }
        },
        '.$required_js.'
    submitHandler: function(form) {
            $("#loadnya").show();
            $(form).ajaxSubmit({
                url : $(this).attr("action"),
                dataType: "json",
                type : "post",
                error: function(data ) { 
                  $("#loadnya").hide();
                  console.log(data); 
                },
                success: function(responseText) {
                  $("#loadnya").hide();
                  console.log(responseText);
                      $.each(responseText, function(index) {
                          console.log(responseText[index].status);
                          if (responseText[index].status=="die") {
                            $("#informasi").modal("show");
                          } else if(responseText[index].status=="error") {
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          } else if(responseText[index].status=="good") {
                            $(\'#modal_'.$modul_name.'\').modal(\'hide\');
                            $(".error_data").hide();
                            $(".notif_top").fadeIn(1000);
                            $(".notif_top").fadeOut(1000, function() {
                                dtb_'.$modul_name.'.draw();
                            });
                          } else {
                             console.log(responseText);
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          }
                    });
                }

            });
        }
    });
});
</script>
'.$input_js_chaining;

$modul_edit_modal ='<?php
include "../../inc/config.php";
'.$edit_data_modal.'
?>
  <style type="text/css"> .datepicker {z-index: 1200 !important; } </style>
   <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="edit_'.$modul_name.'" method="post" class="form-horizontal" action="<?=base_admin();?>modul/'.$modul_name.'/'.$modul_name.'_action.php?act=up">
                            '.$update_element.'
              <input type="hidden" name="id" value="<?=$data_edit->'.$primary_key.';?>">

              <div class="form-group">
                <div class="col-lg-12">
                  <div class="modal-footer"> <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
                  <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> <?php echo $lang["cancel_button"];?></button>
                  </div>
                </div>
              </div><!-- /.form-group -->

            </form>

<script type="text/javascript">
    $(document).ready(function() {
            $.each($(".make-switch"), function () {
            $(this).bootstrapSwitch({
            onText: $(this).data("onText"),
            offText: $(this).data("offText"),
            onColor: $(this).data("onColor"),
            offColor: $(this).data("offColor"),
            size: $(this).data("size"),
            labelText: $(this).data("labelText")
            });
          });  
          //chosen select
          $(".chzn-select").chosen();
          $(".chzn-select-deselect").chosen({
              allow_single_deselect: true
          });
        
      //trigger validation onchange
      $(\'select\').on(\'change\', function() {
          $(this).valid();
      });
      //hidden validate because we use chosen select
      $.validator.setDefaults({ ignore: ":hidden:not(select)" });

    '.$js_choosen.'
    '.$js_date_input.'
    '.$js_validate.'
    $("#edit_'.$modul_name.'").validate({
        errorClass: "help-block",
        errorElement: "span",
        highlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-success").addClass("has-error");
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-error").addClass("has-success");
        },
        errorPlacement: function(error, element) {
            if (element.hasClass("chzn-select")) {
                var id = element.attr("id");
                error.insertAfter("#" + id + "_chosen");
            } else if (element.attr("type") == "checkbox") {
                element.parent().parent().append(error);
            } else if (element.attr("type") == "radio") {
                element.parent().parent().append(error);
            } else {
                error.insertAfter(element);
            }
        },
        '.$required_js.'
         submitHandler: function(form) {
            $("#loadnya").show();
            $(form).ajaxSubmit({
                url : $(this).attr("action"),
                dataType: "json",
                type : "post",
                error: function(data ) { 
                  $("#loadnya").hide();
                  console.log(data); 
                },
                success: function(responseText) {
                  $("#loadnya").hide();
                  console.log(responseText);
                      $.each(responseText, function(index) {
                          console.log(responseText[index].status);
                          if (responseText[index].status=="die") {
                            $("#informasi").modal("show");
                          } else if(responseText[index].status=="error") {
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          } else if(responseText[index].status=="good") {
                            $(\'#modal_'.$modul_name.'\').modal(\'hide\');
                            $(".error_data").hide();
                            $(".notif_top_up").fadeIn(1000);
                            $(".notif_top_up").fadeOut(1000, function() {
                                 dtb_'.$modul_name.'.draw();
                            });
                          } else {
                             console.log(responseText);
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          }
                    });
                }

            });
        }
    });
});
</script>
'.$input_js_chaining;

$modul_add = '<!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>'.ucwords($_POST['page_name']).'</h1>
        <ol class="breadcrumb">
            <li>
              <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
            </li>
            <li>
              <a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'">'.ucwords($_POST['page_name']).'</a>
            </li>
            <li class="active">Add '.ucwords($_POST['page_name']).'</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add '.ucwords($_POST['page_name']).'</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_'.$modul_name.'" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/'.$modul_name.'/'.$modul_name.'_action.php?act=in">
                      '.$input_element.'
                      '.$input_gallery.'
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>'.str_replace("_", "-", $modul_name).'" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                 <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
           
                </div>
              </div><!-- /.form-group -->

            </form>

          </div>
        </div>
      </div>
    </div>

    </section><!-- /.content -->

<script type="text/javascript">
    $(document).ready(function() {
     '.$js_choosen.'
    '.$js_date_input.'
    '.$js_validate.'
    $("#input_'.$modul_name.'").validate({
        errorClass: "help-block",
        errorElement: "span",
        highlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-success").addClass("has-error");
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-error").addClass("has-success");
        },
        errorPlacement: function(error, element) {
            if (element.hasClass("chzn-select")) {
                var id = element.attr("id");
                error.insertAfter("#" + id + "_chosen");
            } else if (element.attr("type") == "checkbox") {
                element.parent().parent().append(error);
            } else if (element.attr("type") == "radio") {
                element.parent().parent().append(error);
            } else {
                error.insertAfter(element);
            }
        },
        '.$required_js.'
        submitHandler: function(form) {
            $("#loadnya").show();
            $(form).ajaxSubmit({
                url : $(this).attr("action"),
                dataType: "json",
                type : "post",
                error: function(data ) { 
                  $("#loadnya").hide();
                  console.log(data); 
                },
                success: function(responseText) {
                  $("#loadnya").hide();
                  console.log(responseText);
                      $.each(responseText, function(index) {
                          console.log(responseText[index].status);
                          if (responseText[index].status=="die") {
                            $("#informasi").modal("show");
                          } else if(responseText[index].status=="error") {
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          } else if(responseText[index].status=="good") {
                            $(".error_data").hide();
                            $(".notif_top").fadeIn(1000);
                            $(".notif_top").fadeOut(1000, function() {
                                    window.history.back();
                            });
                          } else {
                             console.log(responseText);
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          }
                    });
                }

            });
        }
    });
});
</script>
'.$input_js_chaining;


$modul_edit= '<!-- Content Header (Page header) -->
              <section class="content-header">
                  <h1>'.ucwords($_POST['page_name']).'</h1>
                    <ol class="breadcrumb">
                        <li>
                        <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
                        </li>
                        <li>
                        <a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'">'.ucwords($_POST['page_name']).'</a>
                        </li>
                        <li class="active">Edit '.ucwords($_POST['page_name']).'</li>
                    </ol>
              </section>

              <!-- Main content -->
              <section class="content">
              <div class="row">
                  <div class="col-lg-12">
                      <div class="box box-solid box-primary">
                          <div class="box-header">
                              <h3 class="box-title">Edit '.ucwords($_POST['page_name']).'</h3>
                              <div class="box-tools pull-right">
                                  <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-pencil"></i></button>
                              </div>
                          </div>
                      <div class="box-body">
                       <div class="alert alert-danger error_data" style="display:none">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <span class="isi_warning"></span>
                      </div>
                          <form id="edit_'.$modul_name.'" method="post" class="form-horizontal" action="<?=base_admin();?>modul/'.$modul_name.'/'.$modul_name.'_action.php?act=up">
                            '.$update_element.'
                            <input type="hidden" name="id" value="<?=$data_edit->'.$primary_key.';?>">
                            <div class="form-group">
                                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                                <div class="col-lg-10">
                                <a href="<?=base_index();?>'.str_replace("_", "-", $modul_name).'" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
                                </div>
                            </div><!-- /.form-group -->
                          </form>
                      </div>
                  </div>
              </div>
              </section><!-- /.content -->

<script type="text/javascript">
    $(document).ready(function() {
    '.$js_date_input.'
    '.$js_validate.'
    $("#edit_'.$modul_name.'").validate({
        errorClass: "help-block",
        errorElement: "span",
        highlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-success").addClass("has-error");
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-error").addClass("has-success");
        },
        errorPlacement: function(error, element) {
            if (element.hasClass("chzn-select")) {
                var id = element.attr("id");
                error.insertAfter("#" + id + "_chosen");
            } else if (element.attr("type") == "checkbox") {
                element.parent().parent().append(error);
            } else if (element.attr("type") == "radio") {
                element.parent().parent().append(error);
            } else {
                error.insertAfter(element);
            }
        },
        '.$required_js.'

        submitHandler: function(form) {
            $("#loadnya").show();
            $(form).ajaxSubmit({
                url : $(this).attr("action"),
                dataType: "json",
                type : "post",
                error: function(data ) { 
                  $("#loadnya").hide();
                  console.log(data); 
                },
                success: function(responseText) {
                  $("#loadnya").hide();
                  console.log(responseText);
                      $.each(responseText, function(index) {
                          console.log(responseText[index].status);
                          if (responseText[index].status=="die") {
                            $("#informasi").modal("show");
                          } else if(responseText[index].status=="error") {
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          } else if(responseText[index].status=="good") {
                            $(".error_data").hide();
                            $(".notif_top_up").fadeIn(1000);
                            $(".notif_top_up").fadeOut(1000, function() {
                                    window.history.back();
                            });
                          } else {
                             console.log(responseText);
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          }
                    });
                }

            });
        }
    });
});
</script>
'.$input_js_chaining;
$modul_detail= '<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>'.ucwords($_POST['page_name']).'</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'">'.ucwords($_POST['page_name']).'</a></li>
                        <li class="active">Detail '.ucwords($_POST['page_name']).'</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail '.ucwords($_POST['page_name']).'</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        '.$detail_element.'
                        '.$input_multi_image_detail.'
                      </form>
                      <a href="<?=base_index();?>'.str_replace("_", "-", $modul_name).'" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
';


$gallery_detail = $gallery_detail_top.'<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                     Album Gallery
                    </h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'">Album Gallery</a></li>
                        <li class="active">Detail '.ucwords($_POST['page_name']).'</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Album Gallery</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

'.$button_gallery_detail.'

  <button onclick="Uploader.upload();" class="btn btn-primary">
    <i class=\'fa fa-plus\'></i> Upload Foto
  </button>
  <div id="wait" class="hide">
    <img src="<?=base_admin();?>assets/dist/img/upload-indicator.gif" alt="">
  </div>

<div>
  <iframe name="hidden_iframe" id="hidden_iframe" class="hide">
  </iframe>
</div>

<div id="uploaded_images" align="center">

</div>
                  <div class="page-header text-center">

             '.$album_title.'
            var data = JSON.parse(data);
            if (typeof (data[\'error\']) != "undefined") {
                jQuery(\'#uploaded_images\').html(data[\'error\']);
                jQuery(\'#upload_files\').val("");
                jQuery("#wait").addClass(\'hide\');
                return;
            }
            var divs = [];
            jQuery(\'#uploaded_images\').html(divs.join(""));
            jQuery(\'#upload_files\').val("");
            jQuery("#wait").addClass(\'hide\');
        }

        return {
            upload: fnUpload,
            done: fnDone
        }

    }());
</script>';

$list_gallery = '<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>'.ucwords($_POST['page_name']).'</h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'">'.ucwords($_POST['page_name']).'</a></li>
                        <li class="active">'.ucwords($_POST['page_name']).' List</li>
                    </ol>
                </section>
                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">List '.ucwords($_POST['page_name']).'</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                        <div class="box-body">
                            <div class="album-top">
                                <div class="col-md-2 ">
                                <?php
                                       foreach ($db->fetch_all("sys_menu") as $isi) {
                                            if (uri_segment(1)==$isi->url) {
                                                if ($role_act["insert_act"]=="Y") {
                                            ?>
                                          <a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'/tambah" class="btn btn-primary "><i class="fa fa-plus"></i> Add Album</a>
                                            <?php
                                            }
                                         }
                                      }
                                      ?>
                                </div>
                                <div class="col-md-10">
                                <form action="" method="get" class="form_cari">
                                      <div class="input-group col-lg-8">
                                      <span class="input-group-btn">
                                      <button class="btn btn-default" type="button"><?php echo $lang["search"];?> !</button>
                                      </span>
                                      <input type="text" name="q" class="form-control" placeholder="Search..."/>
                                      <span class="input-group-btn">
                                      <button type="submit" id="search-btn" class="btn "><i class="fa fa-search"></i></button>
                                      </span>
                                      </div>
                                </form>
                            </div>
                          </div>
                  <div class="row">
'.$query_album_on_list.'
                </div>
                <div class="row">
                    <div class="col-xs-6" style="margin-top:10px">
                Showing <?=$count;?> to <?=$no-1;?> of <?=$pg->total_record;?> entries

                        </div>

                        <div class="col-xs-6">
                                    <?php
                                  if (isset($_GET[\'q\'])) {
$pg->url=base_index()."'.$modul_name.'?q=".$_GET[\'q\']."&page=";
                                  }
                                    $pg->setParameter(array(
                                      \'range\'=>$limit,
                                      ));
                                      ?>

                                    <div class="dataTables_paginate paging_bootstrap">
                                    <ul class="pagination">
                                    <?=$pg->create();?>
                                    </ul>
                                    </div>
                        </div>
                </div>
                  </div>
                  </div>
              </div>
</div>
                </section><!-- /.content -->';


$list_table_manual ='<!-- Content Header (Page header) -->
              <section class="content-header">
                  <h1>'.ucwords($_POST['page_name']).'</h1>
                    <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'">'.ucwords($_POST['page_name']).'</a></li>
                        <li class="active">'.ucwords($_POST['page_name']).' List</li>
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
                                <a '.$button_modal.' class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo $lang["add_button"];?></a>
                                <?php
                                        }
                                    }
                                }
                                ?>
                  <p>&nbsp;</p>

                  <form action="" method="get" class="form_cari">
                      <div class="input-group col-lg-6">
                          <span class="input-group-btn">
                            <button class="btn btn-default" type="button"><?php echo $lang["search"];?> !</button>
                          </span>
                          <input type="text" name="q" class="form-control" placeholder="Search..."/>
                          <span class="input-group-btn">
                               <button type="submit" id="search-btn" class="btn "><i class="fa fa-search"></i></button>
                          </span>
                      </div>
                  </form>
            </div><!-- /.box-header -->
                  <div class="box-body table-responsive">
                      <table  class="table table-bordered table-striped">
                          <thead>
                              <tr>
                                '.$head_no.'
                                '.$table_header.'
                                <th>Action</th>
                              </tr>
                          </thead>
                          <tbody>
                              '.$select_table.'
                          </tbody>
                      </table>
                            '.$bottom_pagination.'
                  </div><!-- /.box-body -->
              </div><!-- /.box -->
          </div>
      </div>
                </section><!-- /.content -->
'.$modal_template.'
    </section><!-- /.content -->
';
    if ($main_modal_status=='yes') {
    $list_table_manual.=
    '<script type="text/javascript">
      '.$js_modal.'
      '.$js_modal_edit.'
    </script>';
  }

$main = '<?php
switch (uri_segment(2)) {';
    if ($main_modal_status=='no') {
    $main.='
    case "tambah":
          foreach ($db->fetch_all("sys_menu") as $isi) {
               if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah") {
                          if ($role_act["insert_act"]=="Y") {
                             include "'.$modul_name.'_add.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }
    break;
  case "edit":
    $data_edit = $db->fetch_single_row("'.$main_table.'","'.$primary_key.'",uri_segment(3));
        foreach ($db->fetch_all("sys_menu") as $isi) {
                      if (uri_segment(1)==$isi->url&&uri_segment(2)=="edit") {
                          if ($role_act["up_act"]=="Y") {
                             include "'.$modul_name.'_edit.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }

    break;';
    }
    $main.='
    case "detail":
    $data_edit = $db->fetch_single_row("'.$main_table.'","'.$primary_key.'",uri_segment(3));
    include "'.$modul_name.'_detail.php";
    break;
    default:
    include "'.$modul_name.'_view.php";
    break;
}

?>';


$modul_action = '<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    '.$for_action_checkbox.'
  '.$if_input_file.'
  '.$if_input_uimager.'
  '.$if_input_uimagef.'
  $data = array('
  .$for_input_action.'
  );
  '.$for_file_in.'
  '.$for_uimager_in.'
  '.$for_uimagef_in.'
   '.$if_boolean.'
    $in = $db->insert("'.$main_table.'",$data);
    '.$for_action_checkbox_normal.'
    '.$input_multi_image_action.'
    action_response($db->getErrorMessage());
    break;
  case "delete":
    '.$for_uimagef_delete.'
    '.$for_uimager_delete.'
    '.$for_file_delete.'
    $db->delete("'.$main_table.'","'.$primary_key.'",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("'.$main_table.'","'.$primary_key.'",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    '.$for_action_checkbox.'
   $data = array('.$for_update_action.'
   );
   '.$for_file.'
   '.$for_uimager.'
   '.$for_uimagef.'

    '.$if_boolean.'
    '.$for_delete_normal.'
    $up = $db->update("'.$main_table.'",$data,"'.$primary_key.'",$_POST["id"]);
    '.$for_action_checkbox_normal_update.'
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>';
?>
