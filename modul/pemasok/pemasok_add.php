<?php
include "../../inc/config.php";
?>
<style type="text/css"> .datepicker {z-index: 1200 !important; } </style>
 <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
      <form id="input_pemasok" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/pemasok/pemasok_action.php?act=in">
                      
              <div class="form-group">
                <label for="Kode Pemasok" class="control-label col-lg-2"><?=erp_h('master_term_kode_pemasok','Vendor Code');?> </label>
                <div class="col-lg-10">
                  <input type="text" name="kode_pemasok" readonly="" value="<?= GetNextPemasokNo() ?>" placeholder="<?=erp_attr('master_term_kode_pemasok','Vendor Code');?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="NPWP" class="control-label col-lg-2"><?=erp_h('master_term_npwp','Tax ID');?> </label>
                <div class="col-lg-10">
                  <input type="text" name="npwp" placeholder="<?=erp_attr('master_term_npwp','Tax ID');?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Nama Pemasok" class="control-label col-lg-2"><?=erp_h('master_term_nama_pemasok','Vendor Name');?> </label>
                <div class="col-lg-10">
                  <input type="text" name="nama" placeholder="<?=erp_attr('master_term_nama_pemasok','Vendor Name');?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Alamat" class="control-label col-lg-2"><?=erp_h('master_term_alamat','Address');?> </label>
                <div class="col-lg-10">
                  <input type="text" name="alamat" placeholder="<?=erp_attr('master_term_alamat','Address');?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Kota" class="control-label col-lg-2"><?=erp_h('master_term_kota','City');?> </label>
                <div class="col-lg-10">
                  <input type="text" name="kota" placeholder="<?=erp_attr('master_term_kota','City');?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Negara" class="control-label col-lg-2"><?=erp_h('master_term_negara','Country');?> </label>
                <div class="col-lg-10">
                  <input type="text" name="negara" placeholder="<?=erp_attr('master_term_negara','Country');?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Telp" class="control-label col-lg-2"><?=erp_h('master_term_telepon','Phone');?> </label>
                <div class="col-lg-10">
                  <input type="text" name="notelp" placeholder="<?=erp_attr('master_term_telepon','Phone');?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Fax" class="control-label col-lg-2"><?=erp_h('master_term_fax','Fax');?> </label>
                <div class="col-lg-10">
                  <input type="text" name="nofax" placeholder="<?=erp_attr('master_term_fax','Fax');?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Email" class="control-label col-lg-2"><?=erp_h('master_term_email','Email');?> </label>
                <div class="col-lg-10">
                  <input type="text" name="email" placeholder="<?=erp_attr('master_term_email','Email');?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Status" class="control-label col-lg-2"><?=erp_h('common_status','Status');?> </label>
              <div class="col-lg-10">
                <input name="status" class="make-switch" type="checkbox" checked>
              </div>
          </div><!-- /.form-group -->
          
                      

              <div class="form-group">
                <div class="col-lg-12">
                  <div class="modal-footer"> <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?=erp_h('common_save','Save');?></button>
                  <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> <?=erp_h('common_cancel','Cancel');?></button>
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
      
    
    
    
    $("#input_pemasok").validate({
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
                            $('#modal_pemasok').modal('hide');
                            $(".error_data").hide();
                            $(".notif_top").fadeIn(1000);
                            $(".notif_top").fadeOut(1000, function() {
                                dtb_pemasok.draw();
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
