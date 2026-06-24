<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
?>
<!-- Content Header (Page header) -->
              <section class="content-header">
                  <h1>Pemasukan </h1>
                    <ol class="breadcrumb">
                        <li>
                        <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a>
                        </li>
                        <li>
                        <a href="<?=base_index();?>pemasukan-">Pemasukan </a>
                        </li>
                        <li class="active">Edit Pemasukan </li>
                    </ol>
              </section>

              <!-- Main content -->
              <section class="content"> 
              <div class="row">
                  <div class="col-lg-12">
                      <div class="box box-solid box-primary">
                          <div class="box-header">
                              <h3 class="box-title">Edit Pemasukan </h3>
                              <div class="box-tools pull-right">
                                  <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-pencil"></i></button>
                              </div>
                          </div>
                      <div class="box-body">
                       <div class="alert alert-danger error_data" style="display:none">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <span class="isi_warning"></span>
                      </div>
                          <form id="edit_pemasukan_" method="post" class="form-horizontal" action="<?=base_admin();?>modul/picking/picking_action.php?act=up">
                            
            
              
              <div class="form-group">
              <label for="Tanggal BPB" class="control-label col-lg-2">Tanggal BPB </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" value="<?=$data_edit->tgl_bpb;?>" name="tgl_bpb" autocomplete="off" />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="No PO" class="control-label col-lg-2">No PO </label>
                        <div class="col-lg-10">
              <select  id="nopo" name="nopo" data-placeholder="Pilih No PO..." class="form-control chzn-select" tabindex="2" onchange="pilih_po(this.value)"  >
               <option value=""></option>
               <?php foreach ($db->query("select `po`.`nopo` AS `nopo`,`p`.`nopo` AS `nopo_pemasukan` from (`po` left join `pemasukan` `p` on(`p`.`nopo` = `po`.`nopo`))
 where `p`.`nopo` is null or po.nopo='$data_edit->nopo' ") as $isi) { 

                  if ($data_edit->nopo==$isi->nopo) {
                    echo "<option value='$isi->nopo' selected>$isi->nopo</option>";
                  } else {
                  echo "<option value='$isi->nopo'>$isi->nopo</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="Pemasok" class="control-label col-lg-2">Pemasok </label>
                        <div class="col-lg-10">
              <select  id="pemasok" name="pemasok" data-placeholder="Pilih Pemasok..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("pemasok") as $isi) {

                  if ($data_edit->pemasok==$isi->kode_pemasok) {
                    echo "<option value='$isi->kode_pemasok' selected>$isi->nama</option>";
                  } else {
                  echo "<option value='$isi->kode_pemasok'>$isi->nama</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="No Invoice" class="control-label col-lg-2">No Invoice </label>
                <div class="col-lg-10">
                  <input type="text" name="no_invoice" value="<?=$data_edit->no_invoice;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
              <label for="Tanggal Invoice" class="control-label col-lg-2">Tanggal Invoice </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl2">
                    <input type="text" class="form-control" value="<?=$data_edit->tgl_kontrak;?>" name="tgl_invoice" autocomplete="off"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="No DO" class="control-label col-lg-2">No DO </label>
                <div class="col-lg-10">
                  <input type="text" name="no_do" value="<?=$data_edit->no_do;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="No Dokpab" class="control-label col-lg-2">No Dokpab </label>
                <div class="col-lg-10">
                  <input type="text" name="no_dokpab" value="<?=$data_edit->no_dokpab;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
              <label for="Tanggal Dokpab" class="control-label col-lg-2">Tanggal Dokpab </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl3">
                    <input type="text" class="form-control" value="<?=$data_edit->tgl_dokpab;?>" name="tgl_dokpab"  autocomplete="off"/>
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="catatan" class="control-label col-lg-2">catatan </label>
                        <div class="col-lg-10">
              <select  id="catatan" name="catatan" data-placeholder="Pilih catatan..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("catatan") as $isi) {

                  if ($data_edit->catatan==$isi->nm_catatan) {
                    echo "<option value='$isi->nm_catatan' selected>$isi->nm_catatan</option>";
                  } else {
                  echo "<option value='$isi->nm_catatan'>$isi->nm_catatan</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->


          <?php
          // $jenisbcmasuk = $db->fetch_custom_single("select jenisbcmasuk.jenis from jenisbcmasuk
          //           jenis='$data_edit->jenis_dokpab' ");
          ?>
            <div class="form-group">
                <label for="Jenis Dokumen" class="control-label col-lg-2">Jenis Dokumen <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                    <select name="jenisbcmasuk_jenis_dokumen"  id="jenisbcmasuk_jenis_dokumen" data-placeholder="Pilih Jenis Dokumen..." class="form-control chzn-select" tabindex="2" >
                    <option value=""></option>
                     <?php
                     foreach ($db->fetch_all("jenisbcmasuk") as $isi) {
                      if ($data_edit->jenis_dokpab==$isi->jenis) {
                        echo "<option value='$isi->jenis' selected>$isi->jenis</option>";
                        } else {
                        echo "<option value='$isi->jenis'>$isi->jenis</option>";
                        }

                        } ?>
                      </select>
                  </div>
              </div><!-- /.form-group -->
              
              <div class="form-group"> 
                  <label for="kd_catdet" class="control-label col-lg-2">Tujuan Detail
                 <span style="color:#FF0000">*</span></label>
                  <div class="col-lg-10">
                  <?php
                  $detail_catatan = $db->fetch_custom_single("select detail_catatan.kdd_catatan from detail_catatan
                   where detail_catatan.kdd_catatan='$data_edit->kd_catdet' ");
                  
                  ?>
                  <select name="kd_catdet" id="detail_catatan_kd_catdet" data-placeholder="Pilih kd_catdet ..." class="form-control chzn-select" tabindex="2" >
                    <option value=""></option>
                   <?php

                   foreach ($db->query("select * from detail_catatan  ") as $isi) {
                            if ($data_edit->kd_catdet==$isi->kdd_catatan) {
                        echo "<option value='$isi->kdd_catatan' selected>$isi->nd_catatan</option>";
                      } else {
                        echo "<option value='$isi->kdd_catatan'>$isi->nd_catatan</option>";
                        }

                   } ?>
                    </select>
                  </div>
              </div><!-- /.form-group -->
            
              <div class="form-group">
                <label for="No Aju" class="control-label col-lg-2">No Aju </label>
                <div class="col-lg-10">
                  <input type="text" name="no_aju" value="<?=$data_edit->no_aju;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
              <label for="Tanggal Aju" class="control-label col-lg-2">Tanggal Aju </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" value="<?=$data_edit->tgl_aju;?>" name="tgl_aju" autocomplete="off" />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="No E-faktur" class="control-label col-lg-2">No E-faktur </label>
                <div class="col-lg-10">
                  <input type="text" name="efaktur" value="<?=$data_edit->efaktur;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
              <label for="Tanggal E-Faktur" class="control-label col-lg-2">Tanggal E-Faktur </label>
              <div class="col-lg-3">
               <div class="input-group date" id="tgl4">
                    <input type="text" class="form-control"  name="tgl_efaktur" autocomplete="off"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Valuta" class="control-label col-lg-2">Valuta </label>
                        <div class="col-lg-10">
              <select  id="valuta" name="valuta" data-placeholder="Pilih Valuta..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("matauang") as $isi) {

                  if ($data_edit->valuta==$isi->jenis_valas) {
                    echo "<option value='$isi->jenis_valas' selected>$isi->jenis_valas</option>";
                  } else {
                  echo "<option value='$isi->jenis_valas'>$isi->jenis_valas</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="Kurs" class="control-label col-lg-2">Kurs </label>
                <div class="col-lg-10">
                  <input type="text" name="kurs" value="<?=$data_edit->kurs;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <!--  <label for="Kurs" class="control-label col-lg-2"> </label> -->
                 <div class="col-lg-12">
                   <table class="table">
                 <thead>
                   <tr>
                     <th style="width:50px;text-align: center">
                       <a style="cursor: pointer;" onclick="add_baris()" ><i class="fa fa-plus"></i> </a>
                     </th>
                     <th style="width: 300px">Kode Barang</th>
                     <th style="width: 70px">Unit</th>
                     <th><?=sd_h('sales_qty', 'Qty');?></th>
                     <th>Harga</th>
                     <th>Nilai</th>
                     <th>Berat</th>
                     <th>Lokasi</th>
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                 <?php
                 // echo "select d.*,ba.nm_barang
                 //              from tmp_pemasukan_detail2 d 
                             
                 //              join barang ba on ba.kd_barang=d.kode where d.no_bpb='$data_edit->no_aju' ";
                 $q = $db->query("select d.*,ba.nm_barang,ba.satuan
                              from tmp_pemasukan_detail1 d join barang ba on ba.kd_barang=d.kode where d.no_bpb='$data_edit->no_aju'  "); 
                 $no=1;
                 foreach ($q as $k) {
                  ?>
                  <tr id="baris_<?= $no ?>">
                     <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('<?= $no ?>')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                     <td><input type="text" id="form_kode_<?= $no ?>" value="<?= $k->kode." , ".$k->nm_barang ?>" placeholder="Kode Barang" onclick="cari_kode('<?= $no ?>')" class="form-control" name="kode[]" style="width: 300px" >
                      <input type="hidden" name="kode_input[]" value="<?= $k->kode ?>" id="kode_input_1"> 
                     </td>  
                     <td><input type="text" id="form_unit_<?= $no ?>" value="<?= $k->satuan ?>"  class="form-control" name="unit[]"  readonly=""></td> 
                     <td><input type="number" onkeyup="sum_nilai(this.value,'<?= $no ?>')" value="<?= $k->jumlah ?>" id="form_qty_<?= $no ?>" class="form-control" name="jumlah[]" >
                      <input type="hidden" onkeyup="sum_nilai(this.value,'<?= $no ?>')" value="<?= $k->jumlah ?>" id="form_qty_lama_<?= $no ?>" class="form-control" name="jumlah_lama[]" ></td>
                     <td><input type="number" onkeyup="sum_nilai(this.value,'<?= $no ?>')" value="<?= $k->harga ?>" id="form_harga_<?= $no ?>" class="form-control" name="harga[]" ></td>
                     <td><input type="text" id="form_nilai_<?= $no ?>" value="<?= $k->nilai ?>" class="form-control" name="nilai[]" readonly=""></td>
                     <td><input type="number" id="form_berat_<?= $no ?>" value="<?= $k->berat ?>" class="form-control" name="berat[]" ></td>
                     <td><input type="text" id="form_lokasi_<?= $no ?>" value="<?= $k->lokasi ?>" class="form-control" name="lokasi[]" ></td>
                   </tr>
                  <?php
                  $no++;
                 } 
                 ?>
                   
                 </tbody>
               </table>
                 </div>
               <input type="hidden" id="jml" value="<?= ($no-1) ?>">
              
              </div><!-- /.form-group -->
              
                            <input type="hidden" name="id" value="<?=$data_edit->no_bpb;?>">
                            <div class="form-group">
                                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                                <div class="col-lg-10">
                                <a href="<?=base_index();?>pemasukan-" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
                                </div>
                            </div><!-- /.form-group -->
                          </form>
                      </div>
                  </div>
              </div>
              </section><!-- /.content -->

<script type="text/javascript">
    function hapus_baris(id) {

      $("#baris_"+id).remove();
      // var id_baris =  parseInt($("#jml").val())-1;
      //  $("#jml").val(id_baris);
    }

    function add_baris(id) {
      var id_baris =  parseInt($("#jml").val())+1;
      var baris = '<tr id="baris_'+id_baris+'"><td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris(\''+id_baris+'\')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td><td><input type="text" class="form-control" placeholder="Kode Barang" onclick="cari_kode(\''+id_baris+'\')" id="form_kode_'+id_baris+'" name="kode[]" style="width: 300px" > <input type="hidden" id="kode_input_'+id_baris+'" name="kode_input[]" id="kode_input_1"> </td><td><input type="text" class="form-control" id="form_unit_'+id_baris+'" name="unit[]" readonly=""></td><td><input type="number" onkeyup="sum_nilai(this.value,\''+id_baris+'\')" id="form_qty_'+id_baris+'" class="form-control" name="jumlah[]" ></td><td><input type="number" onkeyup="sum_nilai(this.value,\''+id_baris+'\')" id="form_harga_'+id_baris+'" class="form-control" name="harga[]" ></td><td><input type="text" class="form-control" name="nilai[]" id="form_nilai_'+id_baris+'" readonly=""></td><td><input type="number" class="form-control" id="form_berat_'+id_baris+'" name="berat[]" ></td><td><input type="text" class="form-control" id="form_lokasi_'+id_baris+'" name="lokasi[]" ></td></tr>';

      

        $("#isi_tabel").append(baris);
        $("#jml").val(id_baris);
    }

    function sum_nilai(val,id) {
       var a = $("#form_qty_"+id).val();
       var b = $("#form_harga_"+id).val();
       if (a=='') {
        a=0;
       }else{
        a = parseFloat(a);
       }
       if (b=='') {
        b=0;
       }else{
        b=parseFloat(b);
       }
       c = a*b;
       $("#form_nilai_"+id).val(c);

    }

      function cari_kode(id) {   
    
                      $('#form_kode_'+id).autocomplete({
                        source: function (request, response) {
                          $.ajax({
                            url: "<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=cari_kode",
                            data: { term: request.term },
                            type : 'POST',
                            dataType: "json",
                            success: function (data) {

                              response($.map(data, function (item) {
                                return {
                                  kd_barang: item.kd_barang,
                                  nm_barang: item.nm_barang
                                };
                              }))
                            }
                          })
                        },
                        select: function (event, ui) {
                             $('#form_kode_'+id).val(ui.item.kd_barang+" - "+ui.item.nm_barang); 
                             $("#kode_input_"+id).val(ui.item.kd_barang);
                            $.ajax({
                              type : 'POST',
                              data : {
                                id:id,
                                kd_barang : ui.item.kd_barang 
                              },
                              url : "<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=get_unit",
                              success:function(data){
                                   $("#form_unit_"+id).val(data);
                              }
                            });

                                               return false;
                         }
                                           }).data("ui-autocomplete")._renderItem = function (ul, item) {
                        var inner_html = '<a><div class="list_item_container" style="height:20px">' + item.kd_barang + ' , ' +item.nm_barang+'</div></a>';
                        return $("<li></li>")
                        .data("ui-autocomplete-item", item)
                        .append(inner_html)
                        .appendTo(ul);
                       };
  }
    $(document).ready(function() {
    
         $(".date").datepicker({  
          format: "yyyy-mm-dd",
          autoclose: true, 
          todayHighlight: true
          }).on("change",function(){
            $(".date :input").valid();
          }); 
      //trigger validation onchange
      $('select').on('change', function() {
          $(this).valid();
      });
      //hidden validate because we use chosen select
      $.validator.setDefaults({ ignore: ":hidden:not(select)" });
      
    $("#edit_pemasukan_").validate({
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
        
       
         messages: {
            
          kd_catdet: {
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

                  <script type="text/javascript">
                  $("#jenisbcmasuk_jenis_dokumen").change(function(){ 

                        $.ajax({
                        type : "post",
                        url : "<?=base_admin();?>modul/pemasukan_hamparan/get_kd_catdet.php",
                        data : {jenis_dokumen:this.value},
                        success : function(data) {
                            $("#detail_catatan_kd_catdet").html(data);
                            $("#detail_catatan_kd_catdet").trigger("chosen:updated");

                        }
                    });

                  });

                  
                  </script>