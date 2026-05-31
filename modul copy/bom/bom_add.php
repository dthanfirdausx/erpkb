<!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>BOM</h1>
        <ol class="breadcrumb">
            <li>
              <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
            </li>
            <li>
              <a href="<?=base_index();?>bom">BOM</a>
            </li>
            <li class="active">Add BOM</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add BOM</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_bom" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/bom/bom_action.php?act=in">
                      <div class="form-group">
                        <label for="Kode Barang" class="control-label col-lg-2">Barang Jadi </label>
                        <div class="col-lg-10">
            <select  id="kodebj" name="kodebj" onchange="get_detail_barang(this.value)" data-placeholder="Pilih Kode Barang ..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->query("select * from barang where kd_kategori='K02'") as $isi) {
                  echo "<option value='$isi->kd_barang'>$isi->kd_barang</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="Nama Barang" class="control-label col-lg-2">Nama Barang </label>
                <div class="col-lg-10">
                  <input type="text" name="nm_barang" id="nm_barang" placeholder="Nama Barang" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="satuan" class="control-label col-lg-2">satuan </label>
                <div class="col-lg-10">
                  <input type="text" name="satuan" id="satuan" placeholder="satuan" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="jumlah" class="control-label col-lg-2">jumlah </label>
                <div class="col-lg-10">
                  <input type="text" name="jumlah" placeholder="jumlah" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group" id="form_ro">
                
                 <div class="col-lg-12">
                <table class="table">
                 <thead>
                   <tr>
                     <th style="width:50px;text-align: center">
                       <a style="cursor: pointer;" onclick="add_baris()" ><i class="fa fa-plus"></i> </a>
                     </th>
                     <th style="width: 400px">Kode Barang</th>
                     <th style="width: 100px">Unit</th>
                    
                     <th>Qty</th>                     
                     <th>Ket</th>
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                   <tr id="baris_1">
                     <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('1')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                     <td><input type="text" id="form_kode_1" placeholder="Kode Barang" onclick="cari_kode('1')" class="form-control" name="kode[]"  >
                      <input type="hidden" name="kode_input[]" id="kode_input_1"> 
                     </td> 
                     <td><input type="text" id="form_unit_1" class="form-control" name="unit[]"  readonly=""></td> 
                    
                     <td><input type="number" id="form_qty_1" class="form-control" name="qty[]" onkeyup="cek_stok('1',this.value)" required></td>
                     <td><input type="text" id="form_ket_1" class="form-control" name="ket[]" ></td>
                   </tr>
                 </tbody>
               </table>
                 </div>
               <input type="hidden" id="jml" value="1">
              
              </div>
                      
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>bom" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
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

   function hapus_baris(id) {

      $("#baris_"+id).remove();
      // var id_baris =  parseInt($("#jml").val())-1;
      //  $("#jml").val(id_baris);
    }


  function get_detail_barang(kd_barang){
    $.ajax({
                            url: "<?= base_url() ?>inc/get_barang_detail.php",
                            data: { kd_barang: kd_barang },
                            type : 'POST',
                            dataType: "json",
                            success: function (data) {
                              $("#nm_barang").val(data.nm_barang);
                              $("#satuan").val(data.satuan);

                            } 
                          })
  }

  function add_baris(id) {
      var id_baris =  parseInt($("#jml").val())+1;
      var baris = '<tr id="baris_'+id_baris+'"><td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris(\''+id_baris+'\')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td><td><input type="text" class="form-control" placeholder="Kode Barang" onclick="cari_kode(\''+id_baris+'\')" id="form_kode_'+id_baris+'" name="kode[]"  > <input type="hidden" id="kode_input_'+id_baris+'" name="kode_input[]" id="kode_input_1"> </td><td><input type="text" class="form-control" id="form_unit_'+id_baris+'" name="unit[]" style="width: 150px" readonly=""></td><td><input type="number"  id="form_qty_'+id_baris+'" class="form-control" name="qty[]"  onkeyup="cek_stok(\''+id_baris+'\',this.value)" required></td><td><input type="text" class="form-control" name="ket[]" id="form_ket_'+id_baris+'"></td></tr>';

        $("#isi_tabel").append(baris);
        $("#jml").val(id_baris);
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



     
    
    
    $("#input_bom").validate({
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
