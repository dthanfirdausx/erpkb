<!-- Content Header (Page header) -->
 <link rel="stylesheet" href="<?= base_url() ?>assets/css/jquery-ui.css"> 
<style type="text/css">
     .ui-autocomplete { 
  z-index:2147483647;
}
</style>
    <section class="content-header">
        <h1>Purchase Request</h1>
        
    </section>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add Purchase Request</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_pr" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/pr/pr_action.php?act=in"> 
                      
     
              
          <div class="form-group">
              <label for="Tanggal RO" class="control-label col-lg-2">Tanggal PR <span style="color:#FF0000">*</span></label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" name="tgl_ro" required autocomplete="off" />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Departemen/Bagian" class="control-label col-lg-2">Departemen/Bagian <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
            <select  id="dept" name="dept" data-placeholder="Pilih Departemen/Bagian ..." class="form-control chzn-select" tabindex="2" required>
               <option value=""></option>
               <?php foreach ($db->fetch_all("dept") as $isi) {
                  echo "<option value='$isi->kd_dept'>$isi->nm_dept</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="PPC" class="control-label col-lg-2">PPC </label>
                <div class="col-lg-10">
                  <input type="text" name="name_ppc" placeholder="PPC" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="catatan" class="control-label col-lg-2">catatan </label>
              <div class="col-lg-10">
                <textarea class="form-control col-xs-12" rows="5" name="catatan" ></textarea>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                 
                 <div class="col-lg-12">
                   <table class="table">
                 <thead>
                   <tr>
                     <th style="width:50px;text-align: center">
                       <a style="cursor: pointer;" onclick="add_baris()" ><i class="fa fa-plus"></i> </a>
                     </th>
                     <th style="width: 40%">Kode Barang/Nama Barang</th>
                     <th style="width: 5%">Unit</th>
                     <th>Qty</th>
          <!--            <th>Harga</th>
                     <th>Nilai</th>
                     <th>Berat</th> -->
                     <th>Ket</th>
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                   <tr id="baris_1">
                     <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('1')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                     <td><input type="text" id="form_kode_1" placeholder="Kode Barang" onclick="cari_kode('1')" class="form-control" name="kode[]"  >
                      <input type="hidden" name="kode_input[]" id="kode_input_1"> 
                     </td> 
                     <td><input type="text" id="form_unit_1" class="form-control" name="unit[]" readonly=""></td> 
                     <td><input type="text"  id="form_qty_1" class="form-control" name="jumlah[]" ></td>
                 <!--     <td><input type="number" onkeyup="sum_nilai(this.value,'1')" id="form_harga_1" class="form-control" name="harga[]" ></td>
                     <td><input type="text" id="form_nilai_1" class="form-control" name="nilai[]" readonly=""></td>
                     <td><input type="number" id="form_berat_1" class="form-control" name="berat[]" ></td> -->
                     <td><input type="text" id="form_ket_1" class="form-control" name="ket[]" ></td>
                   </tr>
                 </tbody>
               </table>
                 </div>
               <input type="hidden" id="jml" value="1">
              
              </div><!-- /.form-group -->
          
                      
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>pr" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                 <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
           
                </div>
              </div><!-- /.form-group -->

            </form>

          </div>
        </div>
      </div>
    </div>

    </section><!-- /.content -->
 <script src="<?= base_url() ?>assets/js/jquery-ui.js"></script> 
<script type="text/javascript">

    function hapus_baris(id) {
      $("#baris_"+id).remove();
    }

    function add_baris(id) {
      var id_baris =  parseInt($("#jml").val())+1;
      var baris = '<tr id="baris_'+id_baris+'"><td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris(\''+id_baris+'\')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td><td><input type="text" class="form-control" placeholder="Kode Barang" onclick="cari_kode(\''+id_baris+'\')" id="form_kode_'+id_baris+'" name="kode[]"  > <input type="hidden" id="kode_input_'+id_baris+'" name="kode_input[]" id="kode_input_1"> </td><td><input type="text" class="form-control" id="form_unit_'+id_baris+'" name="unit[]" readonly=""></td><td><input type="text" onkeyup="sum_nilai(this.value,\''+id_baris+'\')" id="form_qty_'+id_baris+'" class="form-control" name="jumlah[]" ></td><td><input type="text" class="form-control" id="form_ket_'+id_baris+'" name="ket[]" ></td></tr>';
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
    
      //trigger validation onchange
      $('select').on('change', function() {
          $(this).valid();
      });
      //hidden validate because we use chosen select
      $.validator.setDefaults({ ignore: ":hidden:not(select)" });
      
    $("#input_pr").validate({
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
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          dept: {
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
