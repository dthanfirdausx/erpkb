<?php
include "../../inc/config.php";
$data_edit = $db->fetch_single_row("h_pegawai","idPegawai",$_POST['id_data']);
?>
  <style type="text/css"> .datepicker {z-index: 1200 !important; } </style>
   <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="edit_data_pegawai" method="post" class="form-horizontal" action="<?=base_admin();?>modul/data_pegawai/data_pegawai_action.php?act=up">
                            
              <div class="form-group">
                <label for="NIK" class="control-label col-lg-2">NIK </label>
                <div class="col-lg-10">
                  <input type="text" name="nik" value="<?=$data_edit->nik;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="NPWP" class="control-label col-lg-2">NPWP </label>
                <div class="col-lg-10">
                  <input type="text" name="npwp" value="<?=$data_edit->npwp;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Nama Pegawai" class="control-label col-lg-2">Nama Pegawai </label>
                <div class="col-lg-10">
                  <input type="text" name="namaPegwai" value="<?=$data_edit->namaPegwai;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
                <div class="form-group">
                  <label for="Jenis Kelamin" class="control-label col-lg-2">Jenis Kelamin </label>
                      <div class="col-lg-10">
                        
                <div class="radio radio-success radio-inline">
                  <input type="radio" name="kelamin"  id="radio1" value="L" <?=($data_edit->kelamin=="L")?"checked":"";?> >
                    <label for="radio1" style="padding-left: 5px;">
                      Laki-laki
                    </label>
                </div>
                
                <div class="radio radio-success radio-inline">
                  <input type="radio" name="kelamin"  id="radio2" value="P" <?=($data_edit->kelamin=="P")?"checked":"";?> >
                    <label for="radio2" style="padding-left: 5px;">
                      Perempuan
                    </label>
                </div>
                
                      </div>
                </div><!-- /.form-group -->
                <div class="form-group">
                        <label for="Agama" class="control-label col-lg-2">Agama </label>
                        <div class="col-lg-10">
              <select  id="agama" name="agama" data-placeholder="Pilih Agama..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("h_agama") as $isi) {

                  if ($data_edit->agama==$isi->idAgama) {
                    echo "<option value='$isi->idAgama' selected>$isi->namaAgama</option>";
                  } else {
                  echo "<option value='$isi->idAgama'>$isi->namaAgama</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="No HP" class="control-label col-lg-2">No HP </label>
                <div class="col-lg-10">
                  <input type="text" name="noHp" value="<?=$data_edit->noHp;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Email" class="control-label col-lg-2">Email </label>
                <div class="col-lg-10">
                  <input type="text"  data-rule-email="true" name="email" value="<?=$data_edit->email;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Alamat" class="control-label col-lg-2">Alamat </label>
                <div class="col-lg-10">
                  <input type="text" name="alamat" value="<?=$data_edit->alamat;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="No Rekening" class="control-label col-lg-2">No Rekening </label>
                <div class="col-lg-10">
                  <input type="text" name="noRek" value="<?=$data_edit->noRek;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Bank" class="control-label col-lg-2">Bank </label>
                <div class="col-lg-10">
                  <input type="text" name="bank" value="<?=$data_edit->bank;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Provinsi" class="control-label col-lg-2">Provinsi </label>
                        <div class="col-lg-10">
              <select  id="idProvinsi" name="idProvinsi" data-placeholder="Pilih Provinsi..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("h_data_wilayah") as $isi) {

                  if ($data_edit->idProvinsi==$isi->id) {
                    echo "<option value='$isi->id' selected>$isi->nm_wil</option>";
                  } else {
                  echo "<option value='$isi->id'>$isi->nm_wil</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="Kota" class="control-label col-lg-2">Kota </label>
                        <div class="col-lg-10">
              <select  id="idKota" name="idKota" data-placeholder="Pilih Kota..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("h_data_wilayah") as $isi) {

                  if ($data_edit->idKota==$isi->id) {
                    echo "<option value='$isi->id' selected>$isi->nm_wil</option>";
                  } else {
                  echo "<option value='$isi->id'>$isi->nm_wil</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="Kecamatan" class="control-label col-lg-2">Kecamatan </label>
                        <div class="col-lg-10">
              <select  id="idKecamatan" name="idKecamatan" data-placeholder="Pilih Kecamatan..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("h_data_wilayah") as $isi) {

                  if ($data_edit->idKecamatan==$isi->id) {
                    echo "<option value='$isi->id' selected>$isi->nm_wil</option>";
                  } else {
                  echo "<option value='$isi->id'>$isi->nm_wil</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="foto" class="control-label col-lg-2">foto </label>
                        <div class="col-lg-10">
                    <div class="fileinput fileinput-new" data-provides="fileinput">
                            <div class="fileinput-new thumbnail" style="width: 200px; height: 150px;">
                             <img src="../upload/data_pegawai/<?=$data_edit->foto?>">
                            </div>
                            <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 200px; max-height: 150px;"></div>
                            <div>
                              <span class="btn btn-default btn-file"><span class="fileinput-new">Select image</span> <span class="fileinput-exists">Change</span>
                                <input type="file" name="foto" accept="image/*">
                              </span>
                              <a href="#" class="btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
                            </div>
                          </div>
                          </div>
                      </div><!-- /.form-group -->

              <input type="hidden" name="id" value="<?=$data_edit->idPegawai;?>">

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

    
    
    
    $("#edit_data_pegawai").validate({
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
                            $('#modal_data_pegawai').modal('hide');
                            $(".error_data").hide();
                            $(".notif_top_up").fadeIn(1000);
                            $(".notif_top_up").fadeOut(1000, function() {
                                 dtb_data_pegawai.draw();
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
