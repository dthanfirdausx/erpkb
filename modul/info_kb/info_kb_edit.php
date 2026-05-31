<!-- Content Header (Page header) -->
<!-- Summernote -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css">

<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
              <section class="content-header">
                  <h1>Info KB</h1>
                    <ol class="breadcrumb">
                        <li>
                        <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
                        </li>
                        <li>
                        <a href="<?=base_index();?>info-kb">Info KB</a>
                        </li>
                        <li class="active">Edit Info KB</li>
                    </ol>
              </section>

              <!-- Main content -->
              <section class="content">
              <div class="row">
                  <div class="col-lg-12">
                      <div class="box box-solid box-primary">
                          <div class="box-header">
                              <h3 class="box-title">Edit Info KB</h3>
                              <div class="box-tools pull-right">
                                  <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-pencil"></i></button>
                              </div>
                          </div>
                      <div class="box-body">
                       <div class="alert alert-danger error_data" style="display:none">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <span class="isi_warning"></span>
                      </div>
                          <form id="edit_info_kb" method="post" class="form-horizontal" action="<?=base_admin();?>modul/info_kb/info_kb_action.php?act=up">
                            
              <div class="form-group">
                <label for="Kode" class="control-label col-lg-2">Kode </label>
                <div class="col-lg-10">
                  <input type="text" name="kode" value="<?=$data_edit->kode;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Nama" class="control-label col-lg-2">Nama </label>
                <div class="col-lg-10">
                  <input type="text" name="nama" value="<?=$data_edit->nama;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Alamat" class="control-label col-lg-2">Alamat </label>
                <div class="col-lg-10">
                  <input type="text" name="alamat" value="<?=$data_edit->alamat;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Provinsi" class="control-label col-lg-2">Provinsi </label>
                <div class="col-lg-10">
                  <input type="text" name="prop" value="<?=$data_edit->prop;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Kota" class="control-label col-lg-2">Kota </label>
                <div class="col-lg-10">
                  <input type="text" name="kota" value="<?=$data_edit->kota;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="NPWP" class="control-label col-lg-2">NPWP </label>
                <div class="col-lg-10">
                  <input type="text" name="npwp" value="<?=$data_edit->npwp;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Telp" class="control-label col-lg-2">Telp </label>
                <div class="col-lg-10">
                  <input type="text" name="telp" value="<?=$data_edit->telp;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Fax" class="control-label col-lg-2">Fax </label>
                <div class="col-lg-10">
                  <input type="text" name="fax" value="<?=$data_edit->fax;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Skep KB" class="control-label col-lg-2">Skep KB </label>
                <div class="col-lg-10">
                  <input type="text" name="skepkb" value="<?=$data_edit->skepkb;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
            <div class="form-group">
    <label class="control-label col-lg-2">Bank</label>
    <div class="col-lg-10">
        <textarea 
            name="bank"
            id="bank"
            class="form-control summernote"
        ><?= $data_edit->bank ?></textarea>
    </div>
</div>
               <div class="form-group">
                <label class="control-label col-lg-2">Rek 1</label>
                <div class="col-lg-10">
                  <input type="text" name="rek1" value="<?= $data_edit->rek1 ?>" placeholder="Rekening 1" class="form-control">
                </div>
              </div>

              <div class="form-group">
                <label class="control-label col-lg-2">Rek 2</label>
                <div class="col-lg-10">
                  <input type="text" name="rek2" value="<?= $data_edit->rek2 ?>" placeholder="Rekening 2" class="form-control">
                </div>
              </div>
              
              <div class="form-group">
              <label for="Tgl Skep KB" class="control-label col-lg-2">Tgl Skep KB </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" value="<?=$data_edit->tglskep;?>" name="tglskep"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
                            <input type="hidden" name="id" value="<?=$data_edit->id;?>">
                            <div class="form-group">
                                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                                <div class="col-lg-10">
                                <a href="<?=base_index();?>info-kb" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
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

      $('.summernote').summernote({
    height: 200,
    toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'clear']],
        ['fontname', ['fontname']],
        ['fontsize', ['fontsize']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['table', ['table']],
        ['insert', ['link']],
        ['view', ['fullscreen', 'codeview']]
    ]
});
    
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    
    $("#edit_info_kb").validate({
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
