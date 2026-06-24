<?php
if (!function_exists('pr_legacy_t')) {
  function pr_legacy_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('pr_legacy_h')) {
  function pr_legacy_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
$prLegacyJs = array(
  'required' => pr_legacy_t('validation_required', 'This field is required'),
  'vendorPlaceholder' => pr_legacy_t('purchase_requisition_vendor_placeholder', 'input pemasok'),
  'pricePlaceholder' => pr_legacy_t('purchase_requisition_price_placeholder', 'input harga'),
  'notePlaceholder' => pr_legacy_t('purchase_requisition_note_placeholder', 'input keterangan')
);
?>
<!-- Content Header (Page header) -->
<style>
.switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 34px;
}

.switch input { 
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  -webkit-transition: .4s;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
}

input:checked + .slider {
  background-color: #2196F3;
}

input:focus + .slider {
  box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
  -webkit-transform: translateX(26px);
  -ms-transform: translateX(26px);
  transform: translateX(26px);
}

/* Rounded sliders */
.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}
</style>
             

              <!-- Main content -->
              <section class="content">
              <div class="row">
                  <div class="col-lg-12">
                      <div class="box box-solid box-primary">
                          <div class="box-header">
                              <h3 class="box-title"><?=pr_legacy_h(pr_legacy_t('purchase_requisition_verify_title', 'Verifikasi Purchase Request'));?></h3>
                              <div class="box-tools pull-right">
                                  <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-pencil"></i></button>
                              </div>
                          </div>
                      <div class="box-body">
                       <div class="alert alert-danger error_data" style="display:none">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <span class="isi_warning"></span>
                      </div>
                          <form id="edit_pr" method="post" class="form-horizontal" action="<?=base_admin();?>modul/pr/pr_action.php?act=verifikasi">
                            
              <div class="form-group">
                <label for="No RO" class="control-label col-lg-2"><?=pr_legacy_h(pr_legacy_t('purchase_requisition_no_ro', 'No RO'));?> </label>
                <div class="col-lg-10">
                  <input type="text" name="no_ro" value="<?=$data_edit->no_ro;?>" class="form-control" readonly>
                  <input type="hidden" name="nomor" value="<?=$data_edit->nomor;?>" class="form-control" readonly>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
              <label for="Tanggal RO" class="control-label col-lg-2"><?=pr_legacy_h(pr_legacy_t('purchase_requisition_ro_date', 'Tanggal RO'));?> <span style="color:#FF0000">*</span></label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" value="<?=$data_edit->tgl_ro;?>" name="tgl_ro"  readonly />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Departemen/Bagian" class="control-label col-lg-2"><?=pr_legacy_h(pr_legacy_t('common_department', 'Departemen/Bagian'));?> <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <select  id="dept" name="dept" data-placeholder="<?=pr_legacy_h(pr_legacy_t('purchase_requisition_select_department', 'Pilih Departemen/Bagian...'));?>" class="form-control chzn-select" tabindex="2" readonly>
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
                <label for="PPC" class="control-label col-lg-2">PPC </label>
                <div class="col-lg-10">
                  <input type="text" name="name_ppc" value="<?=$data_edit->name_ppc;?>" class="form-control" readonly>
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="catatan" class="control-label col-lg-2"><?=pr_legacy_h(pr_legacy_t('vendor_evaluation_notes', 'Catatan'));?> </label>
              <div class="col-lg-10">
              <textarea class="form-control col-xs-12" rows="5" name="catatan" readonly><?=$data_edit->catatan;?> </textarea>
              </div>
          </div>
          <div class="form-group">
               
                 <div class="col-lg-12">
                   <table class="table">
                   <thead>
                   <tr> 
                     
                     <th style="width: 30%" class="text-center"><?=pr_legacy_h(pr_legacy_t('purchase_order_material_description', 'Nama Barang'));?></th>
                   
                     <th class="text-center"><?=pr_legacy_h(pr_legacy_t('purchase_requisition_price_comparison', 'Price Comparison'));?></th>
                   </tr>
                <!--    <tr>
                     <th class="text-center">Vendor</th>
                     <th class="text-center">Harga</th>
                   </tr> -->
                 </thead>
                 <tbody id="isi_tabel">
                 <?php
                 $q = $db->query("select d.*,ba.nm_barang, ba.satuan as unit
                              from roin_detail d                              
                              left join barang ba on ba.kd_barang=d.kode where d.no_ro='$data_edit->no_ro'  "); 
                 $no=1;
                 foreach ($q as $k) {
                  ?>
                  <tr>
                    <td><b><?= $k->kode." ".$k->nm_barang."</b><br>Jumlah : ".$k->jumlah."<br>ket : ".$k->ket ?></td>
                    <td>
                      <table class="table" id="tabel_<?= $k->id ?>" >
                        <thead>
                          <tr>
                            <th></th>
                            <th style="width: 40%"><?=pr_legacy_h(pr_legacy_t('vendor_evaluation_vendor', 'Pemasok'));?></th>
                            <th class="text-right"><?=pr_legacy_h(pr_legacy_t('purchase_requisition_offer_price', 'Harga Penawaran'));?></th>
                            <th class="text-center"><?=pr_legacy_h(pr_legacy_t('vendor_evaluation_notes', 'Catatan'));?></th>
                          </tr>
                        </thead>
                        <tbody>
                      <?php
                      $qc = $db->query("select c.*,p.nama from roin_detail_compare c left join pemasok p on 
                                    p.kode_pemasok=c.pemasok where c.id_detail='$k->id' order by harga asc ");
                      if ($qc->rowCount()>0) {
                         foreach ($qc as $kc) { 
                           $checked = "";
                           if ($kc->acc=='1') {
                              $checked = "checked";
                           }
                           ?> 
                           <tr id="baris_<?= $no ?>"> 
                            <td style="width: 40px">
                             <!--  <input type="radio" style="width: 25px;height: 25px" class="form-control" name="barang_<?= $k->id ?>"> -->
                              <label class="switch">
                                <input type="radio" value="<?= $kc->id ?>" name="barang_<?= $k->id ?>" <?= $checked ?>>
                                <span class="slider round"></span>
                              </label>
                            </td>
                            <td><b><?= $kc->nama  ?></b></td>
                            <td style="text-align: right;width: 30%"><?= number_format($kc->harga) ?></td>
                            <td><?= $kc->ket ?></td> 
                          </tr> 
                           <?php
                           $no++;
                         }
                      }else{
                        ?>
                        <tr id="baris_<?= $no ?>"> 
                           <td colspan="4" class="text-center"><?=pr_legacy_h(pr_legacy_t('purchase_requisition_no_vendor_offer', 'Belum ada data penawaran dari pemasok'));?></td>
                          
                        </tr>
                        <?php
                      }
                      ?>
                        </tbody>
                      </table>
                    </td>                  
                  </tr>
                <!--   <tr>
                    <td><input placeholder="input pemasok" type="" name="" class="form-control"></td>
                    <td><input placeholder="input harga" type="" name="" class="form-control"></td>
                  </tr> 
                  <tr>
                    <td><input placeholder="input pemasok" type="" name="" class="form-control"></td>
                    <td><input placeholder="input harga" type="" name="" class="form-control"></td>
                  </tr>  -->
                  <?php
                  $no++;
                 } 
                 ?>
                   
                 </tbody>
               </table>
                 </div>
               <input type="hidden" id="jml" value="<?= ($no-1) ?>">
              
              </div>
          
                            <input type="hidden" name="id" value="<?=$data_edit->no_ro;?>">
                            <div class="form-group">
                              
                                <div class="col-lg-12">
                                <a href="<?=base_index();?>pr" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                                <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> <?=pr_legacy_h(pr_legacy_t('purchase_requisition_verify', 'Verifikasi'));?></button>
                                </div>
                            </div><!-- /.form-group -->
                          </form>
                      </div>
                  </div>
              </div>
              </section><!-- /.content -->

<script type="text/javascript">
   var prLegacyLang = <?=json_encode($prLegacyJs);?>;
   function min_row(id,baris) {
      $("#baris_"+baris).remove();
    }

    function add_row(id,no) {
      var id_baris =  parseInt($("#jml").val())+1;
      var baris = '<tr id="baris_'+id_baris+'"><td style="width: 100px"><a class="btn btn-primary" onclick="add_row(\''+id+'\',\''+id_baris+'\')"><i class="fa fa-plus"></i></a> <a class="btn btn-danger"  onclick="min_row(\''+id+'\',\''+id_baris+'\')"> <i class="fa fa-minus"></i> </a></td><td><input id="form_kode_'+id_baris+'" onkeyup="cari_vendor(\''+id_baris+'\')" type="text" name="vendor['+id+'][]" class="form-control" placeholder="'+prLegacyLang.vendorPlaceholder+'"></td><td><input type="text" name="harga['+id+'][]" class="form-control" placeholder="'+prLegacyLang.pricePlaceholder+'"></td><td><input  type="text" name="ket['+id+'][]" class="form-control" placeholder="'+prLegacyLang.notePlaceholder+'"></td></tr>'; 
      $("#tabel_"+id).append(baris);
        $("#jml").val(id_baris);
   }

    function cari_vendor(id) {    
    
                      $('#form_kode_'+id).autocomplete({
                        source: function (request, response) { 
                          $.ajax({
                            url: "<?= base_url() ?>modul/pr/pr_action.php?act=cari_vendor",
                            data: { term: request.term },
                            type : 'POST',
                            dataType: "json",
                            success: function (data) {

                              response($.map(data, function (item) {
                                return {
                                  kode_pemasok: item.kode_pemasok,
                                  nama: item.nama
                                };
                              }))
                            }
                          })
                        },
                        select: function (event, ui) {
                             $('#form_kode_'+id).val(ui.item.kode_pemasok+"-"+ui.item.nama); 
                          
                                               return false;
                         }
                                           }).data("ui-autocomplete")._renderItem = function (ul, item) {
                        var inner_html = '<a><div class="list_item_container" style="height:20px">' + item.kode_pemasok + ' - ' +item.nama+'</div></a>';
                        return $("<li></li>")
                        .data("ui-autocomplete-item", item)
                        .append(inner_html)
                        .appendTo(ul);
                       };
  }
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
      
    $("#edit_pr").validate({
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
            
          tgl_ro: {
          required: true,
          //minlength: 2
          },
        
          dept: {
          required: true,
          //minlength: 2
          },
        
        },
         messages: {
            
          tgl_ro: {
          required: prLegacyLang.required,
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          dept: {
          required: prLegacyLang.required,
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
