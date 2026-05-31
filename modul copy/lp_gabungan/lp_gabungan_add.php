<!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>LP Gabungan</h1>
        <ol class="breadcrumb">
            <li>
              <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
            </li>
            <li>
              <a href="<?=base_index();?>lp-gabungan">LP Gabungan</a>
            </li>
            <li class="active">Add LP Gabungan</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add LP Gabungan</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_lp_gabungan" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/lp_gabungan/lp_gabungan_action.php?act=in">
                      
              <div class="form-group">
                <label for="Nomor" class="control-label col-lg-2">Nomor </label>
                <div class="col-lg-10">
                  <input type="text" name="nomor" placeholder="Nomor" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="No LP" class="control-label col-lg-2">No LP <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="no_bpb" placeholder="No LP" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal LP" class="control-label col-lg-2">Tanggal LP <span style="color:#FF0000">*</span></label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" name="tgl_bpb" required />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="Project" class="control-label col-lg-2">Project <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="project" placeholder="Project" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Departemen" class="control-label col-lg-2">Departemen <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
            <select  id="dept" name="dept" data-placeholder="Pilih Departemen ..." class="form-control chzn-select" tabindex="2" required>
               <option value=""></option>
               <?php foreach ($db->fetch_all("dept") as $isi) {
                  echo "<option value='$isi->nm_dept'>$isi->nm_dept</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="PPC" class="control-label col-lg-2">PPC <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="name_ppc" placeholder="PPC" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Catatan" class="control-label col-lg-2">Catatan </label>
                <div class="col-lg-10">
                  <input type="text" name="catatan" placeholder="Catatan" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
                      
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>lp-gabungan" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
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
        
    
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    
      //trigger validation onchange
      $('select').on('change', function() {
          $(this).valid();
      });
      //hidden validate because we use chosen select
      $.validator.setDefaults({ ignore: ":hidden:not(select)" });
      
    $("#input_lp_gabungan").validate({
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
            
          no_bpb: {
          required: true,
          //minlength: 2
          },
        
          tgl_bpb: {
          required: true,
          //minlength: 2
          },
        
          project: {
          required: true,
          //minlength: 2
          },
        
          dept: {
          required: true,
          //minlength: 2
          },
        
          name_ppc: {
          required: true,
          //minlength: 2
          },
        
        },
         messages: {
            
          no_bpb: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          tgl_bpb: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          project: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          dept: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          name_ppc: {
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
