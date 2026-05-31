<?php
include "../../inc/config.php";
$data_edit = $db->fetch_single_row("penerima","kode_penerima",$_POST['id_data']);
?>
  <style type="text/css"> .datepicker {z-index: 1200 !important; } </style>
   <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="edit_penerima" method="post" class="form-horizontal" action="<?=base_admin();?>modul/penerima/penerima_action.php?act=up">
                            
              <div class="form-group">
                <label for="kode_penerima" class="control-label col-lg-2">kode_penerima </label>
                <div class="col-lg-10">
                  <input type="text" name="kode_penerima" value="<?=$data_edit->kode_penerima;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="npwp" class="control-label col-lg-2">npwp </label>
                <div class="col-lg-10">
                  <input type="text" name="npwp" value="<?=$data_edit->npwp;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="nama" class="control-label col-lg-2">nama </label>
                <div class="col-lg-10">
                  <input type="text" name="nama" value="<?=$data_edit->nama;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="alamat" class="control-label col-lg-2">alamat </label>
                <div class="col-lg-10">
                  <input type="text" name="alamat" value="<?=$data_edit->alamat;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="kota" class="control-label col-lg-2">kota </label>
                <div class="col-lg-10">
                  <input type="text" name="kota" value="<?=$data_edit->kota;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="negara" class="control-label col-lg-2">negara </label>
                <div class="col-lg-10">
                  <input type="text" name="negara" value="<?=$data_edit->negara;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="notelp" class="control-label col-lg-2">notelp </label>
                <div class="col-lg-10">
                  <input type="text" name="notelp" value="<?=$data_edit->notelp;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="nofax" class="control-label col-lg-2">nofax </label>
                <div class="col-lg-10">
                  <input type="text" name="nofax" value="<?=$data_edit->nofax;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="email" class="control-label col-lg-2">email </label>
                <div class="col-lg-10">
                  <input type="text"  data-rule-email="true" name="email" value="<?=$data_edit->email;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
                <div class="form-group">
                  <label for="status" class="control-label col-lg-2">status </label>
                      <div class="col-lg-10">
                        
                <div class="radio radio-success radio-inline">
                  <input type="radio" name="status"  id="radio1" value="0" <?=($data_edit->status=="0")?"checked":"";?> >
                    <label for="radio1" style="padding-left: 5px;">
                      Tidak Aktif
                    </label>
                </div>
                
                <div class="radio radio-success radio-inline">
                  <input type="radio" name="status"  id="radio2" value="1" <?=($data_edit->status=="1")?"checked":"";?> >
                    <label for="radio2" style="padding-left: 5px;">
                      Aktif
                    </label>
                </div>
                
                      </div>
                </div><!-- /.form-group -->
                
              <div class="form-group">
                <label for="skep" class="control-label col-lg-2">skep </label>
                <div class="col-lg-10">
                  <input type="text" name="skep" value="<?=$data_edit->skep;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <input type="hidden" name="id" value="<?=$data_edit->kode_penerima;?>">

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

    
    
    
    $("#edit_penerima").validate({
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
                            $('#modal_penerima').modal('hide');
                            $(".error_data").hide();
                            $(".notif_top_up").fadeIn(1000);
                            $(".notif_top_up").fadeOut(1000, function() {
                                 dtb_penerima.draw();
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
