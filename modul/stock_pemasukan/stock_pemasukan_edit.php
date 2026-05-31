<?php
include "../../inc/config.php";
$data_edit = $db->fetch_single_row("vtotalstockpemasukan","",$_POST['id_data']);
?>
  <style type="text/css"> .datepicker {z-index: 1200 !important; } </style>
   <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="edit_stock_pemasukan" method="post" class="form-horizontal" action="<?=base_admin();?>modul/stock_pemasukan/stock_pemasukan_action.php?act=up">
                            
              <div class="form-group">
                <label for="kd_barang" class="control-label col-lg-2">kd_barang </label>
                <div class="col-lg-10">
                  <input type="text" name="kd_barang" value="<?=$data_edit->kd_barang;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="nm_barang" class="control-label col-lg-2">nm_barang </label>
                <div class="col-lg-10">
                  <input type="text" name="nm_barang" value="<?=$data_edit->nm_barang;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="stock" class="control-label col-lg-2">stock </label>
                <div class="col-lg-10">
                  <input type="text" name="stock" value="<?=$data_edit->stock;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="satuan" class="control-label col-lg-2">satuan </label>
                <div class="col-lg-10">
                  <input type="text" name="satuan" value="<?=$data_edit->satuan;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="nm_kategori" class="control-label col-lg-2">nm_kategori </label>
                <div class="col-lg-10">
                  <input type="text" name="nm_kategori" value="<?=$data_edit->nm_kategori;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <input type="hidden" name="id" value="<?=$data_edit->;?>">

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

    
    
    
    $("#edit_stock_pemasukan").validate({
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
                            $('#modal_stock_pemasukan').modal('hide');
                            $(".error_data").hide();
                            $(".notif_top_up").fadeIn(1000);
                            $(".notif_top_up").fadeOut(1000, function() {
                                 dtb_stock_pemasukan.draw();
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
