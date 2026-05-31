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
                        <li class="active">Edit COA</li>
                    </ol>
              </section>

              <!-- Main content -->
              <section class="content">
              <div class="row">
                  <div class="col-lg-12">
                      <div class="box box-solid box-primary">
                          <div class="box-header">
                              <h3 class="box-title">Edit COA</h3>
                              <div class="box-tools pull-right">
                                  <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-pencil"></i></button>
                              </div>
                          </div>
                      <div class="box-body">
                       <div class="alert alert-danger error_data" style="display:none">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <span class="isi_warning"></span>
                      </div>
                          <form id="edit_coa" method="post" class="form-horizontal" action="<?=base_admin();?>modul/coa/coa_action.php?act=up">
                            
              <div class="form-group">
                <label for="Nama Coa" class="control-label col-lg-2">Nama Coa </label>
                <div class="col-lg-10">
                  <input type="text" name="no_rek" value="<?=$data_edit->no_rek;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Induk" class="control-label col-lg-2">Induk </label>
                        <div class="col-lg-10">
              <select  id="induk" name="induk" data-placeholder="Pilih Induk..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("rekening") as $isi) {

                  if ($data_edit->induk==$isi->no_rek) {
                    echo "<option value='$isi->no_rek' selected>$isi->nama_rek</option>";
                  } else {
                  echo "<option value='$isi->no_rek'>$isi->nama_rek</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

            <div class="form-group">
                <label for="Level" class="control-label col-lg-2">Level </label>
                <div class="col-lg-10">
                    <select id="level" name="level" data-placeholder="Pilih Level..." class="form-control chzn-select" tabindex="2" >
                      <option value=""></option>
                     <?php
                     $option = array(
'1' => '1',

'2' => '2',

'3' => '3',

'4' => '4',
);
                     foreach ($option as $isi => $val) {

                        if ($data_edit->level==$isi) {
                          echo "<option value='$data_edit->level' selected>$val</option>";
                        } else {
                       echo "<option value='$isi'>$val</option>";
                          }
                     } ?>
                    </select>
                  </div>
            </div><!-- /.form-group -->
            
              <div class="form-group">
                <label for="Nama COA/Rekening" class="control-label col-lg-2">Nama COA/Rekening </label>
                <div class="col-lg-10">
                  <input type="text" name="nama_rek" value="<?=$data_edit->nama_rek;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="kat_coa" class="control-label col-lg-2">kat_coa </label>
                        <div class="col-lg-10">
              <select  id="kat_coa" name="kat_coa" data-placeholder="Pilih kat_coa..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("coa_kategori") as $isi) {

                  if ($data_edit->kat_coa==$isi->id) {
                    echo "<option value='$isi->id' selected>$isi->kategori</option>";
                  } else {
                  echo "<option value='$isi->id'>$isi->kategori</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

                            <input type="hidden" name="id" value="<?=$data_edit->id;?>">
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
              </section><!-- /.content -->

<script type="text/javascript">
    $(document).ready(function() {
    
    
    $("#edit_coa").validate({
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
