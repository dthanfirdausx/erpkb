<!-- Content Header (Page header) -->
              <section class="content-header">
                  <h1>Inbox Incoming</h1>
                    <ol class="breadcrumb">
                        <li>
                        <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
                        </li>
                        <li>
                        <a href="<?=base_index();?>inbox-incoming">Inbox Incoming</a>
                        </li>
                        <li class="active">Edit Inbox Incoming</li>
                    </ol>
              </section>

              <!-- Main content -->
              <section class="content">
              <div class="row">
                  <div class="col-lg-12">
                      <div class="box box-solid box-primary">
                          <div class="box-header">
                              <h3 class="box-title">Edit Inbox Incoming</h3>
                              <div class="box-tools pull-right">
                                  <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-pencil"></i></button>
                              </div>
                          </div>
                      <div class="box-body">
                       <div class="alert alert-danger error_data" style="display:none">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <span class="isi_warning"></span>
                      </div>
                          <form id="edit_inbox_incoming" method="post" class="form-horizontal" action="<?=base_admin();?>modul/inbox_incoming/inbox_incoming_action.php?act=up">
                            
              <div class="form-group">
                <label for="Nomor" class="control-label col-lg-2">Nomor <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="nomor" value="<?=$data_edit->nomor;?>" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Dari" class="control-label col-lg-2">Dari <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="dari" value="<?=$data_edit->dari;?>" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="No SPB" class="control-label col-lg-2">No SPB <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="no_spb" value="<?=$data_edit->no_spb;?>" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
              <label for="Tanggal SPB" class="control-label col-lg-2">Tanggal SPB <span style="color:#FF0000">*</span></label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" value="<?=$data_edit->tgl_spb;?>" name="tgl_spb" required />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Departemen" class="control-label col-lg-2">Departemen <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <select  id="dept" name="dept" data-placeholder="Pilih Departemen..." class="form-control chzn-select" tabindex="2" required>
               <option value=""></option>
               <?php foreach ($db->fetch_all("dept") as $isi) {

                  if ($data_edit->dept==$isi->kd_dept) {
                    echo "<option value='$isi->kd_dept' selected>$isi->nm_dept</option>";
                  } else {
                  echo "<option value='$isi->kd_dept'>$isi->nm_dept</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="Nama PPC" class="control-label col-lg-2">Nama PPC <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="name_ppc" value="<?=$data_edit->name_ppc;?>" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Catatan" class="control-label col-lg-2">Catatan <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="catatan" value="<?=$data_edit->catatan;?>" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              
                            <input type="hidden" name="id" value="<?=$data_edit->;?>">
                            <div class="form-group">
                                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                                <div class="col-lg-10">
                                <a href="<?=base_index();?>inbox-incoming" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
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
      
    $("#edit_inbox_incoming").validate({
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
            
          nomor: {
          required: true,
          //minlength: 2
          },
        
          dari: {
          required: true,
          //minlength: 2
          },
        
          no_spb: {
          required: true,
          //minlength: 2
          },
        
          tgl_spb: {
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
        
          catatan: {
          required: true,
          //minlength: 2
          },
        
        },
         messages: {
            
          nomor: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          dari: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          no_spb: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          tgl_spb: {
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
        
          catatan: {
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
