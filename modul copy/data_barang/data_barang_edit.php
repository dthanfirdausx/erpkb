<?php
include "../../inc/config.php";
$data_edit = $db->fetch_single_row("barang","id",$_POST['id_data']);
?>
  <style type="text/css"> .datepicker {z-index: 1200 !important; } </style>
   <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="edit_data_barang" method="post" class="form-horizontal" action="<?=base_admin();?>modul/data_barang/data_barang_action.php?act=up">
                            
              <div class="form-group">
                <label for="Kode Barang" class="control-label col-lg-2">Kode Barang </label>
                <div class="col-lg-10">
                  <input type="text" name="kd_barang" value="<?=$data_edit->kd_barang;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Nama Barang" class="control-label col-lg-2">Nama Barang </label>
                <div class="col-lg-10">
                  <input type="text" name="nm_barang" value="<?=$data_edit->nm_barang;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Type" class="control-label col-lg-2">Type </label>
                <div class="col-lg-10">
                  <input type="text" name="type" value="<?=$data_edit->type;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Spec" class="control-label col-lg-2">Spec </label>
                <div class="col-lg-10">
                  <input type="text" name="spec" value="<?=$data_edit->spec;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Satuan" class="control-label col-lg-2">Satuan </label>
                <div class="col-lg-10">
                  <input type="text" name="satuan" value="<?=$data_edit->satuan;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Ket" class="control-label col-lg-2">Ket </label>
                <div class="col-lg-10">
                  <input type="text" name="ket" value="<?=$data_edit->ket;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Kategori" class="control-label col-lg-2">Kategori </label>
                        <div class="col-lg-10">
              <select  id="kd_kategori" name="kd_kategori" data-placeholder="Pilih Kategori..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("kategori") as $isi) {

                  if ($data_edit->kd_kategori==$isi->kd_kategori) {
                    echo "<option value='$isi->kd_kategori' selected>$isi->nm_kategori</option>";
                  } else {
                  echo "<option value='$isi->kd_kategori'>$isi->nm_kategori</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="berat" class="control-label col-lg-2">berat </label>
                <div class="col-lg-10">
                  <input type="text" name="berat" value="<?=$data_edit->berat;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <input type="hidden" name="id" value="<?=$data_edit->id;?>">

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
      $('select').on('change', function() {
          $(this).valid();
      });
      //hidden validate because we use chosen select
      $.validator.setDefaults({ ignore: ":hidden:not(select)" });

    
    
    
    $("#edit_data_barang").validate({
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
                            $('#modal_data_barang').modal('hide');
                            $(".error_data").hide();
                            $(".notif_top_up").fadeIn(1000);
                            $(".notif_top_up").fadeOut(1000, function() {
                                 dtb_data_barang.draw();
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
