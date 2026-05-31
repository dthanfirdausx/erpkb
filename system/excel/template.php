<?php
$download_data = '<?php
session_start();
include "../../inc/config.php";
require_once \'../../inc/lib/Writer.php\';


$writer = new XLSXWriter();
$style =
        array (
            array(
              \'border\' => array(
                \'style\' => \'thin\',
                \'color\' => \'000000\'
                ),
            \'allfilleddata\' => true
            ),
            '.$column_wajib.'
            '.$column_optional.'
            );
//column width
$col_width = array(
'.$width_column.'
  );
$writer->setColWidth($col_width);

$header = array(
	'.$head_label.'
);

$data_rec = array();

'.$var_column.'

  if (isset($_POST[\''.$isset_column.'\'])) {
		'.$filter_column.'
}
        $order_by = "order by your order here";

    
        $temp_rec = $db->query("your query here '.$where_column.' ");
                    foreach ($temp_rec as $key) {

                      $data_rec[] = array(
                      				'.$rec_obj.'
                        );

            }


$filename = \''.$service_name.'.xlsx\';
header(\'Content-disposition: attachment; filename="\'.XLSXWriter::sanitize_filename($filename).\'"\');
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header(\'Content-Transfer-Encoding: binary\');
header(\'Cache-Control: must-revalidate\');
header(\'Pragma: public\');
$writer->writeSheet($data_rec,\'Data Tagihan Mhs\', $header, $style);
$writer->writeToStdOut();
exit(0);
?>';
$filter_page = '
//button import
<a class="btn btn-primary" id="import_data"><i class="fa fa-cloud-upload"></i> Import Excel</a>

    <div class="modal" id="modal_import_data" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"> <div class="modal-dialog modal-lg"> <div class="modal-content"><div class="modal-header"> <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&nbsp;</span></button> <h4 class="modal-title">Import Excel </h4> </div> <div class="modal-body" id="isi_import_data"> </div> </div><!-- /.modal-content --> </div><!-- /.modal-dialog --> </div>

<div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Filter</h3>
            </div>
            <div class="box-body">
           <form class="form-horizontal" id="filter_form" method="post" action="<?=base_admin();?>modul/'.$service_name.'/download_data.php" target="_blank">
                   '.$group_filter.'
                      <div class="form-group">
                        <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                        <div class="col-lg-10">
                          <span id="filter" class="btn btn-primary"><i class="fa fa-refresh"></i> Filter</span>
                        <button type="submit" class="btn btn-success"><i class="fa fa-cloud-download"></i> Download</button>
                      </div><!-- /.form-group -->
                    </div>
                    </form>
            </div>
            <!-- /.box-body -->
          </div>

$(document).ready(function(){

        $("#import_data").click(function() {
          $.ajax({
              url : "<?=base_admin();?>modul/'.$service_name.'/import_data.php",
              type : "GET",
              success: function(data) {
                  $("#isi_import_data").html(data);
              }
          });

      $(\'#modal_import_data\').modal({ keyboard: false,backdrop:\'static\',show:true });

    });



$(\'#filter\').on(\'click\', function() {
  dtb_'.$service_name.' = $("#dtb_'.$service_name.'").DataTable({

            "order": [[1,\'asc\']],  
            destroy : true,
    
            \'ajax\':{
              url :\'<?=base_admin();?>modul/'.$service_name.'/'.$service_name.'_data.php\',
            type: \'post\',  // method  , by default get
            data: function ( d ) {
                //filter variable datatable
                '.$dtable_filter.'
                  },
            error: function (xhr, error, thrown) {
            console.log(xhr);

            }
          },
        });
});

  $("#fakultas_filter").change(function(){

            $.ajax({
            type : "post",
            url : "<?=base_admin();?>modul/mahasiswa/get_jurusan_filter.php",
            data : {fakultas:this.value},
            success : function(data) {
                $("#jurusan_filter").html(data);
                $("#jurusan_filter").trigger("chosen:updated");

            }
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
       $.validator.addMethod("myFunc", function(val) {
        if(val==\'all\'){
          return false;
        } else {
          return true;
        }
      }, "Untuk Cetak Data Silakan Pilih Prodi");
    $("#filter_form").validate({
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
        
        rules: {
            
          jur_filter: {
            myFunc:true
          //minlength: 2
          },
        
          sem_filter: {
          required: true,
          //minlength: 2
          },
        
        },
         messages: {
        
          sem_filter: {
          required: "Untuk Cetak Data Silakan Pilih Semester",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
        }
    });
});
';
$import_page = '<?php
session_start();
//import page
include "../../inc/config.php";
?>
        <div class="alert isi_informasi" style="display:none;margin-bottom: 0px;">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          
        </div>
                     <form id="input_import" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/'.$service_name.'/'.$service_name.'_action.php?act=import">
                     <div class="form-group">
                        <label for="nim" class="control-label col-lg-2">&nbsp;</label>
                        <div class="col-lg-10"> <a href="<?=base_url();?>upload/sample/'.$service_name.'.xlsx" class="btn btn-success btn-flat"><i class="fa fa-cloud-download"></i> Download Contoh Template XLS</a></div>
                      </div><!-- /.form-group -->
                      <div class="form-group">
                        <label for="Upload Data Akm" class="control-label col-lg-2">Upload Data</label>
                        <div class="col-lg-10">
                          <div class="fileinput fileinput-new" data-provides="fileinput">
                            <div class="input-group">
                              <div class="form-control uneditable-input span3" data-trigger="fileinput">
                                <i class="glyphicon glyphicon-file fileinput-exists"></i> <span class="fileinput-filename"></span>
                              </div>
                              <span class="input-group-addon btn btn-default btn-file"><span class="fileinput-new">Select file</span> <span class="fileinput-exists">Change</span>
                                <input type="file" name="semester" required>
                              </span>
                              <a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
                            </div>
                          </div>
                        </div>
                      </div><!-- /.form-group -->

                      <div class="form-group">
                        <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                        <div class="col-lg-10">
                         <a data-dismiss="modal" class="btn btn-default btn-flat"><i class="fa fa-close"></i> Tutup</a>
                          <button type="submit" class="btn btn-primary btn-flat"><i class="fa fa-cloud-upload"></i> Upload Data</button>
                        </div>
                      </div><!-- /.form-group -->
                    </form>
<script type="text/javascript">
  $(document).ready(function(){
   $("#input_import").validate({
        errorClass: \'help-block\',
        errorElement: \'span\',
        highlight: function(element, errorClass, validClass) {
            $(element).parents(\'.form-group\').removeClass(\'has-success\').addClass(\'has-error\');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).parents(\'.form-group\').removeClass(\'has-error\').addClass(\'has-success\');
        },

            submitHandler: function(form) {
               $(\'#loadnya\').show();
                   $(form).ajaxSubmit({
                          type: "post",
                          url: $(this).attr(\'action\'),
                          data: $("#input_import").serialize(),
                       //  enctype:  \'multipart/form-data\'
                        success: function(data){
                          $(\'.fileinput\').fileinput(\'clear\');
                          $(".isi_informasi").show();
                          $(".isi_informasi").html(\'\');
                          $(".isi_informasi").html(data);
                          dtb_'.$service_name.'.draw( false );
                          $(\'#loadnya\').hide();
                     
                      }
                    });
            }

        });  

});
</script>
<?php
//include on top
$time_start = microtime(true); 
require(\'../../inc/lib/SpreadsheetReader.php\');

		//action
 case \'import\':
      if (!is_dir("../../../upload/upload_excel")) {
              mkdir("../../../upload/upload_excel");
            }


   if (!preg_match("/.(xls|xlsx)$/i", $_FILES["semester"]["name"]) ) {

              echo "pastikan file yang anda pilih xls|xlsx";
              exit();

            } else {
              move_uploaded_file($_FILES["semester"]["tmp_name"], "../../../upload/upload_excel/".$_FILES[\'semester\'][\'name\']);
              $semester = array("semester"=>$_FILES["semester"]["name"]);

            }

      $error_count = 0;
      $error = array();
      $sukses = 0;
      $values = "";

  $Reader = new SpreadsheetReader("../../../upload/upload_excel/".$_FILES[\'semester\'][\'name\']);

  foreach ($Reader as $key => $val)
  {

 
    if ($key>0) {

      if ($val[0]!=\'\') {
          $check = $db->check_exist(\''.$main_table.'\',array(\'kode_mk\' => filter_var($val[0], FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH),\'kur_id\'=>$_POST[\'id_kur\']));
          if ($check==true) {
            $error_count++;
            $error[] = $val[0]." Sudah Ada";
          } else {
            $sukses++;
      $values .= \'("\'.
      '.$loop_var.'
        }

      }

    }

      }

if ($values!="") {
  $values = rtrim($values,",");

  $query = "insert into matkul ('.$query.') values ".$values;
  //echo $query;
  $db->query($query);
}

          unlink("../../../upload/upload_excel/".$_FILES[\'semester\'][\'name\']);
          $msg = \'\';
$time_end = microtime(true);
$execution_time = ($time_end - $time_start);

      if (($sukses>0) || ($error_count>0)) {
        $msg =  "<div class=\"alert alert-warning alert-dismissible\" role=\"alert\" style=\"margin-bottom: 0;\" >
        <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">×</button>
            <font color=\"#3c763d\">".$sukses." data Matakuliah baru berhasil di import</font><br />
            <font color=\"#ce4844\" >".$error_count." data tidak bisa ditambahkan </font>";
            if (!$error_count==0) {
              $msg .= "<a data-toggle=\"collapse\" href=\"#collapseExample\" aria-expanded=\"false\" aria-controls=\"collapseExample\">Detail error</a>";
            }
            //echo "<br />Total: ".$i." baris data";
            $msg .= "<div class=\"collapse\" id=\"collapseExample\">";
                $i=1;
                foreach ($error as $pesan) {
                    $msg .= "<div class=\"bs-callout bs-callout-danger\">".$i.". ".$pesan."</div><br />";
                  $i++;
                  }
            $msg .= "</div>";
            $msg .= "<p>Total Waktu Import : ". waktu_import($execution_time);
           $msg .= "</div>";

      }
     echo $msg;
    break;
';