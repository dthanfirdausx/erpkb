<!-- Content Header (Page header) -->
              <section class="content-header">
                  <h1>Laporan Terima Incoming</h1>
                    <ol class="breadcrumb">
                        <li>
                        <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
                        </li>
                        <li>
                        <a href="<?=base_index();?>laporan-terima-incoming">Laporan Terima Incoming</a>
                        </li>
                        <li class="active">Edit Laporan Terima Incoming</li>
                    </ol>
              </section>

              <!-- Main content -->
              <section class="content">
              <div class="row">
                  <div class="col-lg-12">
                      <div class="box box-solid box-primary">
                          <div class="box-header">
                              <h3 class="box-title">Edit Laporan Terima Incoming</h3>
                              <div class="box-tools pull-right">
                                  <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-pencil"></i></button>
                              </div>
                          </div>
                      <div class="box-body">
                       <div class="alert alert-danger error_data" style="display:none">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <span class="isi_warning"></span>
                      </div>
                          <form id="edit_laporan_terima_incoming" method="post" class="form-horizontal" action="<?=base_admin();?>modul/laporan_terima_incoming/laporan_terima_incoming_action.php?act=up">
                            
              <div class="form-group">
                <label for="ket" class="control-label col-lg-2">ket </label>
                <div class="col-lg-10">
                  <input type="text" name="ket" value="<?=$data_edit->ket;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="nomor" class="control-label col-lg-2">nomor </label>
                <div class="col-lg-10">
                  <input type="text" name="nomor" value="<?=$data_edit->nomor;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="no_lpb" class="control-label col-lg-2">no_lpb </label>
                <div class="col-lg-10">
                  <input type="text" name="no_lpb" value="<?=$data_edit->no_lpb;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="tgl_lpb" class="control-label col-lg-2">tgl_lpb </label>
                <div class="col-lg-10">
                  <input type="text" name="tgl_lpb" value="<?=$data_edit->tgl_lpb;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="dari" class="control-label col-lg-2">dari </label>
                <div class="col-lg-10">
                  <input type="text" name="dari" value="<?=$data_edit->dari;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
                            <input type="hidden" name="id" value="<?=$data_edit->;?>">
                            <div class="form-group">
                                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                                <div class="col-lg-10">
                                <a href="<?=base_index();?>laporan-terima-incoming" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
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
    
    
    $("#edit_laporan_terima_incoming").validate({
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
