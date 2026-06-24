<?php
include "../../inc/config.php";
$data_edit = $db->fetch_single_row("barang","id",$_POST['id_data']);
?>
  <style type="text/css"> .datepicker {z-index: 1200 !important; } </style>
   <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="edit_barang" method="post" class="form-horizontal" action="<?=base_admin();?>modul/barang/barang_action.php?act=up">
                            
              <div class="form-group">
                <label for="Kode Barang" class="control-label col-lg-2"><?=erp_h('master_term_kode_barang', 'Material Code');?> <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="kd_barang" value="<?=$data_edit->kd_barang;?>" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Nama Barang" class="control-label col-lg-2"><?=erp_h('master_term_nama_barang', 'Material Name');?> <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="nm_barang" value="<?=$data_edit->nm_barang;?>" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Type" class="control-label col-lg-2"><?=erp_h('common_type', 'Type');?> </label>
                <div class="col-lg-10">
                  <input type="text" name="type" value="<?=$data_edit->type;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->

              <div class="form-group">
                <label class="control-label col-lg-2"><?=erp_h('master_term_material_type', 'Material Type');?> <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10"><select name="material_type_id" class="form-control chzn-select" required><option value=""></option>
                  <?php foreach ($db->query("select id,type_code,type_name from erp_material_type where status='Aktif' order by type_code") as $isi) { ?><option value="<?=$isi->id;?>" <?=$data_edit->material_type_id==$isi->id?'selected':'';?>><?=$isi->type_code;?> - <?=$isi->type_name;?></option><?php } ?>
                </select></div>
              </div>

              <div class="form-group">
                <label class="control-label col-lg-2"><?=erp_h('master_term_material_group', 'Material Group');?> <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10"><select name="material_group_id" class="form-control chzn-select" required><option value=""></option>
                  <?php foreach ($db->query("select id,group_code,group_name from erp_material_group where status='Aktif' order by group_code") as $isi) { ?><option value="<?=$isi->id;?>" <?=$data_edit->material_group_id==$isi->id?'selected':'';?>><?=$isi->group_code;?> - <?=$isi->group_name;?></option><?php } ?>
                </select></div>
              </div>

              <div class="form-group">
                <label class="control-label col-lg-2"><?=erp_h('master_term_plant', 'Plant');?> <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10"><select name="plant_id" class="form-control chzn-select" required><option value=""></option>
                  <?php foreach ($db->query("select id,plant_code,plant_name from erp_plant where status='Aktif' order by plant_code") as $isi) { ?><option value="<?=$isi->id;?>" <?=$data_edit->plant_id==$isi->id?'selected':'';?>><?=$isi->plant_code;?> - <?=$isi->plant_name;?></option><?php } ?>
                </select></div>
              </div>

              <div class="form-group">
                <label class="control-label col-lg-2"><?=erp_h('master_default_storage_location', 'Default Storage Location');?></label>
                <div class="col-lg-10"><select name="default_storage_location_id" class="form-control chzn-select"><option value=""></option>
                  <?php foreach ($db->query("select s.id,s.storage_code,s.storage_name,p.plant_code from erp_storage_location s join erp_plant p on p.id=s.plant_id where s.status='Aktif' order by p.plant_code,s.storage_code") as $isi) { ?><option value="<?=$isi->id;?>" <?=$data_edit->default_storage_location_id==$isi->id?'selected':'';?>><?=$isi->plant_code;?> / <?=$isi->storage_code;?> - <?=$isi->storage_name;?></option><?php } ?>
                </select></div>
              </div>
              
              <div class="form-group">
                <label for="Spesipikasi" class="control-label col-lg-2"><?=erp_h('common_spec', 'Spec');?> </label>
                <div class="col-lg-10">
                  <input type="text" name="spec" value="<?=$data_edit->spec;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Satuan" class="control-label col-lg-2"><?=erp_h('master_term_satuan', 'Unit');?> </label>
                <div class="col-lg-10">
                  <select name="satuan" class="form-control chzn-select" required><option value=""></option>
                    <?php foreach ($db->query("select jenis,max(nama) nama from satuan group by jenis order by jenis") as $isi) { ?><option value="<?=$isi->jenis;?>" <?=$data_edit->satuan==$isi->jenis?'selected':'';?>><?=$isi->jenis;?> - <?=$isi->nama;?></option><?php } ?>
                  </select>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Keterangan" class="control-label col-lg-2"><?=erp_h('master_term_keterangan', 'Remarks');?> </label>
                <div class="col-lg-10">
                  <input type="text" name="ket" value="<?=$data_edit->ket;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Kategori" class="control-label col-lg-2"><?=erp_h('master_term_kategori', 'Category');?> <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <select  id="kd_kategori" name="kd_kategori" data-placeholder="<?=erp_attr('master_select_category', 'Select Category');?>..." class="form-control chzn-select" tabindex="2" required>
               <option value=""></option>
               <?php foreach ($db->fetch_all("kategori") as $isi) {

                  if ($data_edit->kd_kategori==$isi->kd_kategori) {
                    echo "<option value='$isi->kd_kategori' selected>$isi->nm_kategori</option>";
                  } else {
                  echo "<option value='$isi->kd_kategori'>$isi->nm_kategori</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

            <div class="form-group">
                <label for="status" class="control-label col-lg-2"><?=erp_h('common_status', 'Status');?> </label>
                <div class="col-lg-10">
                <?php if ($data_edit->status=="1") {
                ?>
                  <input name="status" class="make-switch" type="checkbox" checked>
                <?php
              } else {
                ?>
                  <input name="status" class="make-switch" type="checkbox">
                <?php
              }?>

                </div>
            </div><!-- /.form-group -->
            
              <input type="hidden" name="id" value="<?=$data_edit->id;?>">

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
      
    $("#edit_barang").validate({
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
            
          kd_barang: {
          required: true,
          //minlength: 2
          },
        
          nm_barang: {
          required: true,
          //minlength: 2
          },
        
          kd_kategori: {
          required: true,
          //minlength: 2
          },
        
        },
         messages: {
            
          kd_barang: {
          required: (window.ERPKB_LANG && ERPKB_LANG.validation_required) || "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          nm_barang: {
          required: (window.ERPKB_LANG && ERPKB_LANG.validation_required) || "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          kd_kategori: {
          required: (window.ERPKB_LANG && ERPKB_LANG.validation_required) || "This field is required",
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
                            $('#modal_barang').modal('hide');
                            $(".error_data").hide();
                            $(".notif_top_up").fadeIn(1000);
                            $(".notif_top_up").fadeOut(1000, function() {
                                 dtb_barang.draw();
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
