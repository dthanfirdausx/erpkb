<!-- Content Header (Page header) -->
              <section class="content-header">
                  <h1>Pengeluaran </h1>
                    <ol class="breadcrumb">
                       
                        <li>
                        <a href="<?=base_index();?>pengeluaran-hamparan">Pengeluaran </a>
                        </li>
                        <li class="active">Edit Pengeluaran </li>
                    </ol>
              </section>

              <!-- Main content -->
              <section class="content">
              <div class="row">
                  <div class="col-lg-12">
                      <div class="box box-solid box-primary">
                          <div class="box-header">
                              <h3 class="box-title">Edit Pengeluaran </h3>
                              <div class="box-tools pull-right">
                                  <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-pencil"></i></button>
                              </div>
                          </div>
                      <div class="box-body">
                       <div class="alert alert-danger error_data" style="display:none">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <span class="isi_warning"></span>
                      </div>
                          <form id="edit_pengeluaran_hamparan" method="post" class="form-horizontal" action="<?=base_admin();?>modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php?act=up">
                  <div class="form-group">
                <label for="No Invoice/Kontrak" class="control-label col-lg-2">No SJ <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="no_sj" value="<?=$data_edit->no_sj;?>" class="form-control" readonly>
                  <input type="hidden" name="nomor" value="<?=$data_edit->nomor;?>" class="form-control" readonly>
                </div>
              </div><!-- /.form-group --> 
              
              <div class="form-group">
              <label for="Tanggal Pengeluaran" class="control-label col-lg-2">Tanggal Pengeluaran <span style="color:#FF0000">*</span></label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" value="<?=$data_edit->tgl_sj;?>" name="tgl_sj" required />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Penerima" class="control-label col-lg-2">Penerima <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <select  id="penerima" name="penerima" data-placeholder="Pilih Penerima..." class="form-control chzn-select" tabindex="2" required>
               <option value=""></option>
               <?php foreach ($db->fetch_all("penerima") as $isi) {

                  if ($data_edit->penerima==$isi->kode_penerima) {
                    echo "<option value='$isi->kode_penerima' selected>$isi->nama</option>";
                  } else {
                  echo "<option value='$isi->kode_penerima'>$isi->nama</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="No Invoice/Kontrak" class="control-label col-lg-2">No Invoice/Kontrak <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="no_invoice" value="<?=$data_edit->no_invoice;?>" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
              <label for="Tanggal Invoice" class="control-label col-lg-2">Tanggal Invoice </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl2">
                    <input type="text" class="form-control" value="<?=$data_edit->tgl_invoice;?>" name="tgl_invoice"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="No Po" class="control-label col-lg-2">No Po </label>
                <div class="col-lg-10">
                  <input type="text" name="no_do" value="<?=$data_edit->no_do;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              

          <?php
          $jenisbckeluar = $db->fetch_custom_single("select jenisbckeluar.jenis from jenisbckeluar
                    inner join detail_catatan on jenisbckeluar.jenis=detail_catatan.jenis_dokpab
           where detail_catatan.kdd_catatan='$data_edit->kd_catdet'");
          ?>
            <div class="form-group">
                <label for="Jenis Dokpab" class="control-label col-lg-2">Jenis Dokpab <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                    <select name="jenisbckeluar_jenis_dokpab"  id="jenisbckeluar_jenis_dokpab" data-placeholder="Pilih Jenis Dokpab..." class="form-control chzn-select" tabindex="2" required>
                    <option value=""></option>
                     <?php
                     foreach ($db->fetch_all("jenisbckeluar") as $isi) {
                      if ($jenisbckeluar->jenis==$isi->jenis) {
                        echo "<option value='$isi->jenis' selected>$isi->jenis</option>";
                        } else {
                        echo "<option value='$isi->jenis'>$isi->jenis</option>";
                        }

                        } ?>
                      </select>
                  </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                  <label for="kd_catdet" class="control-label col-lg-2">Catatan Detail <span style="color:#FF0000">*</span></label>
                  <div class="col-lg-10">
                  <?php
                  $detail_catatan = $db->fetch_custom_single("select detail_catatan.kdd_catatan from detail_catatan
                   where detail_catatan.kdd_catatan='$data_edit->kd_catdet'");
                  ?>
                  <select name="kd_catdet" id="detail_catatan_kd_catdet" data-placeholder="Pilih kd_catdet ..." class="form-control chzn-select" tabindex="2" required>
                    <option value=""></option>
                   <?php

                   foreach ($db->query("select * from detail_catatan where jenis_dokpab='$jenisbckeluar->jenis'") as $isi) {
                            if ($detail_catatan->kdd_catatan==$isi->kdd_catatan) {
                        echo "<option value='$isi->kdd_catatan' selected>$isi->nd_catatan</option>";
                      } else {
                        echo "<option value='$isi->kdd_catatan'>$isi->nd_catatan</option>";
                        }

                   } ?>
                    </select>
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
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" value="<?=$data_edit->tgl_dokpab;?>" name="tgl_dokpab"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Tujuan Pengiriman" class="control-label col-lg-2">Tujuan Pengiriman </label>
                        <div class="col-lg-10">
              <select  id="catatan" name="catatan" data-placeholder="Pilih Tujuan Pengiriman..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("catatan") as $isi) {

                  if ($data_edit->catatan==$isi->kd_catatan) {
                    echo "<option value='$isi->kd_catatan' selected>$isi->nm_catatan</option>";
                  } else {
                  echo "<option value='$isi->kd_catatan'>$isi->nm_catatan</option>";
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
                <div class="input-group date" id="tgl2">
                    <input type="text" class="form-control" value="<?=$data_edit->tgl_aju;?>" name="tgl_aju"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="No Efaktur" class="control-label col-lg-2">No Efaktur </label>
                <div class="col-lg-10">
                  <input type="text" name="efaktur" value="<?=$data_edit->efaktur;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
              <label for="Tgl Efaktur" class="control-label col-lg-2">Tgl Efaktur </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl3">
                    <input type="text" class="form-control" value="<?=$data_edit->tgl_efaktur;?>" name="tgl_efaktur"  />
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
               <?php foreach ($db->query("select * from matauang group by jenis_valas") as $isi) {

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
                 <label for="Kurs" class="control-label col-lg-2"> </label>
                 <div class="col-lg-10">
                   <table class="table">
                 <thead>
                   <tr>
                     <th style="width:50px;text-align: center">
                       <a style="cursor: pointer;" onclick="add_baris()" ><i class="fa fa-plus"></i> </a>
                     </th>
                     <th>Kode Barang</th>
                     <th>Unit</th>
                     <th>Qty</th>
                     <th>Harga</th>
                     <th>Nilai</th>
                 <!--     <th>Berat</th>
                     <th>Lokasi</th> -->
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                 <?php
                 $q = $db->query("select d.*,ba.nm_barang
                              from pengeluaran_detail d 
                             
                              join barang ba on ba.kd_barang=d.kode where d.no_sj='$data_edit->no_sj'  "); 
                 $no=1;
                 foreach ($q as $k) {
                  ?>
                  <tr id="baris_<?= $no ?>">
                     <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('<?= $no ?>')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                     <td><input type="text" id="form_kode_<?= $no ?>" value="<?= $k->kode." , ".$k->nm_barang ?>" placeholder="Kode Barang" onclick="cari_kode('<?= $no ?>')" class="form-control" name="kode[]" style="width: 300px" >
                      <input type="hidden" name="kode_input[]" value="<?= $k->kode ?>" id="kode_input_1"> 
                     </td>  
                     <td><input type="text" id="form_unit_<?= $no ?>" value="<?= $k->unit ?>"  class="form-control" name="unit[]" style="width: 150px" readonly=""></td> 
                     <td><input type="number" onkeyup="sum_nilai(this.value,'<?= $no ?>')" value="<?= $k->jumlah ?>" id="form_qty_<?= $no ?>" class="form-control" name="jumlah[]" ></td>
                     <td><input type="number" onkeyup="sum_nilai(this.value,'<?= $no ?>')" value="<?= $k->harga ?>" id="form_harga_<?= $no ?>" class="form-control" name="harga[]" ></td>
                     <td><input type="text" id="form_nilai_<?= $no ?>" value="<?= $k->nilai ?>" class="form-control" name="nilai[]" readonly=""></td>
                    
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
          
                            <input type="hidden" name="id" value="<?=$data_edit->no_sj;?>">
                            <div class="form-group">
                                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                                <div class="col-lg-10">
                                <a href="<?=base_index();?>pengeluaran-hamparan" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
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
      var baris = '<tr id="baris_'+id_baris+'"><td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris(\''+id_baris+'\')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td><td><input type="text" class="form-control" placeholder="Kode Barang" onclick="cari_kode(\''+id_baris+'\')" id="form_kode_'+id_baris+'" name="kode[]" style="width: 300px" > <input type="hidden" id="kode_input_'+id_baris+'" name="kode_input[]" id="kode_input_1"> </td><td><input type="text" class="form-control" id="form_unit_'+id_baris+'" name="unit[]" style="width: 150px" readonly=""></td><td><input type="number" onkeyup="sum_nilai(this.value,\''+id_baris+'\')" id="form_qty_'+id_baris+'" class="form-control" name="jumlah[]" ></td><td><input type="number" onkeyup="sum_nilai(this.value,\''+id_baris+'\')" id="form_harga_'+id_baris+'" class="form-control" name="harga[]" ></td><td><input type="text" class="form-control" name="nilai[]" id="form_nilai_'+id_baris+'" readonly=""></td></tr>';

      

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
      $("#tgl1 :input").valid();
    });
   
    
      //trigger validation onchange
      $('select').on('change', function() {
          $(this).valid();
      });
      //hidden validate because we use chosen select
      $.validator.setDefaults({ ignore: ":hidden:not(select)" });
      
    $("#edit_pengeluaran_hamparan").validate({
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
            
          tgl_sj: {
          required: true,
          //minlength: 2
          },
        
          penerima: {
          required: true,
          //minlength: 2
          },
        
          no_invoice: {
          required: true,
          //minlength: 2
          },
        
          kd_catdet: {
          required: true,
          //minlength: 2
          },
        
        },
         messages: {
            
          tgl_sj: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          penerima: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          no_invoice: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
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
                  $("#jenisbckeluar_jenis_dokpab").change(function(){

                        $.ajax({
                        type : "post",
                        url : "<?=base_admin();?>modul/pengeluaran_hamparan/get_kd_catdet.php",
                        data : {jenis_dokpab:this.value},
                        success : function(data) {
                            $("#detail_catatan_kd_catdet").html(data);
                            $("#detail_catatan_kd_catdet").trigger("chosen:updated");

                        }
                    });

                  });

                  
                  </script>