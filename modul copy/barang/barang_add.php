<?php
include "../../inc/config.php";
?>
<style type="text/css"> .datepicker {z-index: 1200 !important; } </style>
 <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
      <form id="input_barang" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/barang/barang_action.php?act=in">
                      
              <div class="form-group">
                <label for="Kode Barang" class="control-label col-lg-2">Kode Barang <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="kd_barang" placeholder="Kode Barang" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Nama Barang" class="control-label col-lg-2">Nama Barang <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="nm_barang" placeholder="Nama Barang" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Type" class="control-label col-lg-2">Type </label>
                <div class="col-lg-10">
                  <input type="text" name="type" placeholder="Type" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Spesipikasi" class="control-label col-lg-2">Spesipikasi </label>
                <div class="col-lg-10">
                  <input type="text" name="spec" placeholder="Spesipikasi" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Satuan" class="control-label col-lg-2">Satuan </label>
                <div class="col-lg-10">
                  <input type="text" name="satuan" placeholder="Satuan" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Keterangan" class="control-label col-lg-2">Keterangan </label>
                <div class="col-lg-10">
                  <input type="text" name="ket" placeholder="Keterangan" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Kategori" class="control-label col-lg-2">Kategori <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
            <select  id="kd_kategori" name="kd_kategori" data-placeholder="Pilih Kategori ..." class="form-control chzn-select" tabindex="2" required>
               <option value=""></option>
               <?php foreach ($db->fetch_all("kategori") as $isi) {
                  echo "<option value='$isi->kd_kategori'>$isi->nm_kategori</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

          <div class="form-group">
              <label for="status" class="control-label col-lg-2">status </label>
              <div class="col-lg-10">
                <input name="status" class="make-switch" type="checkbox" checked>
              </div>
          </div><!-- /.form-group -->
          
                      

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
      
    $("#input_barang").validate({
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
            
          kd_barang: {
          required: true,
          //minlength: 2
          },
        
          nm_barang: {
          required: true,
          //minlength: 2
          },
        
          kd_kategori: {
          required: true,
          //minlength: 2
          },
        
        },
         messages: {
            
          kd_barang: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          nm_barang: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          kd_kategori: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
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
                            $('#modal_barang').modal('hide');
                            $(".error_data").hide();
                            $(".notif_top").fadeIn(1000);
                            $(".notif_top").fadeOut(1000, function() {
                                dtb_barang.draw();
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
