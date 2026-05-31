<!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>Jurnal Umum</h1>
        <ol class="breadcrumb">
            <li>
              <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
            </li>
            <li>
              <a href="<?=base_index();?>jurnal-umum">Jurnal Umum</a>
            </li>
            <li class="active">Add Jurnal Umum</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add Jurnal Umum</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_jurnal_umum" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=in">
                      
              <div class="form-group">
                <label for="no_jurnal" class="control-label col-lg-2">no_jurnal </label>
                <div class="col-lg-10">
                  <input type="text" name="no_jurnal" placeholder="no_jurnal" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="tgl_jurnal" class="control-label col-lg-2">tgl_jurnal </label>
                <div class="col-lg-10">
                  <input type="text" name="tgl_jurnal" placeholder="tgl_jurnal" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="ket" class="control-label col-lg-2">ket </label>
                <div class="col-lg-10">
                  <input type="text" name="ket" placeholder="ket" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="no_bukti" class="control-label col-lg-2">no_bukti </label>
                <div class="col-lg-10">
                  <input type="text" name="no_bukti" placeholder="no_bukti" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="no_rek" class="control-label col-lg-2">no_rek </label>
                        <div class="col-lg-10">
            <select  id="no_rek" name="no_rek" data-placeholder="Pilih no_rek ..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("rekening") as $isi) {
                  echo "<option value='$isi->no_rek'>$isi->nama_rek</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="debet" class="control-label col-lg-2">debet </label>
                <div class="col-lg-10">
                  <input type="text" name="debet" placeholder="debet" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="debet_usd" class="control-label col-lg-2">debet_usd </label>
                <div class="col-lg-10">
                  <input type="text" name="debet_usd" placeholder="debet_usd" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="kredit" class="control-label col-lg-2">kredit </label>
                <div class="col-lg-10">
                  <input type="text" name="kredit" placeholder="kredit" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="kredit_usd" class="control-label col-lg-2">kredit_usd </label>
                <div class="col-lg-10">
                  <input type="text" name="kredit_usd" placeholder="kredit_usd" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="username" class="control-label col-lg-2">username </label>
                <div class="col-lg-10">
                  <input type="text" name="username" placeholder="username" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="tgl_insert" class="control-label col-lg-2">tgl_insert </label>
                <div class="col-lg-10">
                  <input type="text" name="tgl_insert" placeholder="tgl_insert" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="valuta" class="control-label col-lg-2">valuta </label>
                <div class="col-lg-10">
                  <input type="text" name="valuta" placeholder="valuta" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="kurs" class="control-label col-lg-2">kurs </label>
                <div class="col-lg-10">
                  <input type="text" name="kurs" placeholder="kurs" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
                      
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>jurnal-umum" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
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
     
    
    
    $("#input_jurnal_umum").validate({
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
