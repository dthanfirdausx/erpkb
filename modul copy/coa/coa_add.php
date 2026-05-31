<!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>COA</h1>
        <ol class="breadcrumb">
            <li>
              <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
            </li>
            <li>
              <a href="<?=base_index();?>coa">COA</a>
            </li>
            <li class="active">Add COA</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add COA</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_coa" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/coa/coa_action.php?act=in">
                      
              <div class="form-group">
                <label for="No Rek/COA" class="control-label col-lg-2">No Rek/COA <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="no_rek" placeholder="No Rek/COA" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Induk " class="control-label col-lg-2">Induk  <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
            <select  id="induk" name="induk" data-placeholder="Pilih Induk  ..." class="form-control chzn-select" tabindex="2" required>
               <option value=""></option>
               <?php foreach ($db->fetch_all("rekening") as $isi) {
                  echo "<option value='$isi->no_rek'>$isi->nama_rek</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="level" class="control-label col-lg-2">level </label>
                <div class="col-lg-10">
                  <input type="text" name="level" placeholder="level" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="kat_coa" class="control-label col-lg-2">kat_coa </label>
                        <div class="col-lg-10">
            <select  id="kat_coa" name="kat_coa" data-placeholder="Pilih kat_coa ..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("coa_kategori") as $isi) {
                  echo "<option value='$isi->id'>$isi->kategori</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="jenis" class="control-label col-lg-2">jenis </label>
                <div class="col-lg-10">
                  <input type="text" name="jenis" placeholder="jenis" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
                      
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>coa" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
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
      
    $("#input_coa").validate({
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
            
          no_rek: {
          required: true,
          //minlength: 2
          },
        
          induk: {
          required: true,
          //minlength: 2
          },
        
        },
         messages: {
            
          no_rek: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          induk: {
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
