<?php
include "../../inc/config.php";
?>
<style type="text/css"> .datepicker {z-index: 1200 !important; } </style>
 <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
      <form id="input_data_user" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/data_user/data_user_action.php?act=in">
                      
              <div class="form-group">
                <label for="first name" class="control-label col-lg-2">first name </label>
                <div class="col-lg-10">
                  <input type="text" name="first_name" placeholder="first name" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="last name" class="control-label col-lg-2">last name </label>
                <div class="col-lg-10">
                  <input type="text" name="last_name" placeholder="last name" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="username" class="control-label col-lg-2">username </label>
                <div class="col-lg-10">
                  <input type="text" name="username" placeholder="username" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="password" class="control-label col-lg-2">password </label>
                <div class="col-lg-10">
                  <input type="text" name="password" placeholder="password" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="email" class="control-label col-lg-2">email </label>
                <div class="col-lg-10">
                  <input type="text" name="email" placeholder="email" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="foto user" class="control-label col-lg-2">foto user </label>
                        <div class="col-lg-10">
                      <div class="fileinput fileinput-new" data-provides="fileinput">
                            <div class="fileinput-new thumbnail" style="width: 200px; height: 150px;">
                              <img data-src="holder.js/100%x100%" alt="...">
                            </div>
                            <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 200px; max-height: 150px;"></div>
                            <div>
                              <span class="btn btn-default btn-file"><span class="fileinput-new">Select image</span> <span class="fileinput-exists">Change</span>
                                <input type="file" name="foto_user" accept="image/*">
                              </span>
                              <a href="#" class="btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
                            </div>
                          </div>
                        </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="group level" class="control-label col-lg-2">group level </label>
                        <div class="col-lg-10">
            <select  id="group_level" name="group_level" data-placeholder="Pilih group level ..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("sys_group_users") as $isi) {
                  echo "<option value='$isi->id'>$isi->level</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

          <div class="form-group">
              <label for="aktif" class="control-label col-lg-2">aktif </label>
              <div class="col-lg-10">
                <input name="aktif" class="make-switch" type="checkbox" checked>
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
      
    
    
    
    $("#input_data_user").validate({
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
                            $('#modal_data_user').modal('hide');
                            $(".error_data").hide();
                            $(".notif_top").fadeIn(1000);
                            $(".notif_top").fadeOut(1000, function() {
                                dtb_data_user.draw();
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
