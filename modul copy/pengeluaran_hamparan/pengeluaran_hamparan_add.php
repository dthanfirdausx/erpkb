<!-- Content Header (Page header) -->
   <link rel="stylesheet" href="<?= base_url() ?>assets/css/jquery-ui.css"> 
<style type="text/css">
     .ui-autocomplete { 
  z-index:2147483647;
}
</style>
    <section class="content-header">
        <h1>Pengeluaran </h1>
        <ol class="breadcrumb">
            <li>
              <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
            </li>
            <li>
              <a href="<?=base_index();?>pengeluaran-hamparan">Pengeluaran </a>
            </li>
            <li class="active">Add Pengeluaran </li>
        </ol>
    </section>

    <!-- Main content --> 
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add Pengeluaran </h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_pengeluaran_hamparan" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php?act=in">

               <div class="form-group" style="display: none">
                <label for="No Po" class="control-label col-lg-2">Jenis Barang <span style="color:#FF0000">*</span> </label>
                <div class="col-lg-10">
                   <label><input onclick="show_panel(this.value)" type="radio" name="dari" value="1" style="position: relative;top: 3px" required=""> Hasil Produksi </label> &nbsp;&nbsp;
                   <label><input onclick="show_panel(this.value)" type="radio" name="dari" value="0" style="position: relative;top: 3px" required=""> Bukan Hasil Produksi </label>
                </div>
              </div><!-- /.form-group -->
          <div class="form-group">
  <label class="control-label col-lg-2">Sales Order</label>
  <div class="col-lg-10">
    <select id="no_sales_order" class="form-control chzn-select">
      <option value="">-- Pilih Sales Order --</option>
      <?php
      $q = $db->query("SELECT no_sales_order FROM sales_order ORDER BY no_sales_order DESC");
      foreach ($q as $k) {
          echo "<option value='$k->no_sales_order'>$k->no_sales_order</option>";
      }
      ?>
    </select>
  </div>
</div>
                      
          <div class="form-group">
              <label for="Tanggal Pengeluaran" class="control-label col-lg-2">Tanggal Pengeluaran <span style="color:#FF0000">*</span></label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" name="tgl_sj"  autocomplete="off" required />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Penerima" class="control-label col-lg-2">Penerima <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
            <select  id="penerima" name="penerima" data-placeholder="Pilih Penerima ..." class="form-control chzn-select" tabindex="2" required>
               <option value=""></option>
               <?php foreach ($db->fetch_all("penerima") as $isi) {
                  echo "<option value='$isi->kode_penerima'>$isi->nama</option>";
               } ?>
              </select>
            </div>
          </div><!-- /.form-group -->

           <div class="form-group">
                  <label for="Jenis Dokpab" class="control-label col-lg-2">Jenis Dokpab <span style="color:#FF0000">*</span></label>
                  <div class="col-lg-10">
                      <select name="jenisbckeluar_jenis_dokpab" id="jenisbckeluar_jenis_dokpab" data-placeholder="Pilih Jenis Dokpab ..." class="form-control chzn-select" tabindex="2" required>
                        <option value="">Pilih Jenis Dokpab</option>
                        <?php foreach ($db->fetch_all("jenisbckeluar") as $isi) {
                        echo "<option value='$isi->jenis'>$isi->jenis</option>";
                        } ?>
                      </select>
                  </div>
              </div><!-- /.form-group -->

              <div class="form-group">
                <label for="No Invoice/Kontrak" class="control-label col-lg-2">No Invoice/Kontrak <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="no_invoice" placeholder="No Invoice/Kontrak" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal Invoice" class="control-label col-lg-2">Tanggal Invoice </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl2">
                    <input type="text" class="form-control"  autocomplete="off" name="tgl_invoice"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="No Po" class="control-label col-lg-2">No PO </label>
                <div class="col-lg-10">
                  <input type="text" name="no_do" placeholder="No Po" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
             
                      
            <div class="form-group">
                <label for="kd_catdet" class="control-label col-lg-2">Catatan Detail <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <select name="kd_catdet" id="detail_catatan_kd_catdet" data-placeholder="Pilih Catatan Detail ..." class="form-control chzn-select" tabindex="2" required>
                  </select>
                </div>
            </div><!-- /.form-group -->
            
              <div class="form-group">
                <label for="No Dokpab" class="control-label col-lg-2">No Dokpab </label>
                <div class="col-lg-10">
                  <input type="text" name="no_dokpab" placeholder="No Dokpab" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal Dokpab" class="control-label col-lg-2">Tanggal Dokpab </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control"  autocomplete="off" name="tgl_dokpab"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Tujuan Pengiriman" class="control-label col-lg-2">Tujuan Pengiriman </label>
                        <div class="col-lg-10">
            <select  id="catatan" name="catatan" data-placeholder="Pilih Tujuan Pengiriman ..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("catatan") as $isi) {
                  echo "<option value='$isi->kd_catatan'>$isi->nm_catatan</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
                <label for="No Aju" class="control-label col-lg-2">No Aju </label>
                <div class="col-lg-10">
                  <input type="text" name="no_aju" placeholder="No Aju" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tanggal Aju" class="control-label col-lg-2">Tanggal Aju </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl2">
                    <input type="text" class="form-control"  autocomplete="off" name="tgl_aju"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="No Efaktur" class="control-label col-lg-2">No Efaktur </label>
                <div class="col-lg-10">
                  <input type="text" name="efaktur" placeholder="No Efaktur" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Tgl Efaktur" class="control-label col-lg-2">Tgl Efaktur </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl3">
                    <input type="text" class="form-control"  autocomplete="off" name="tgl_efaktur"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
           <div class="form-group">
                        <label for="Valuta" class="control-label col-lg-2">Valuta </label>
                        <div class="col-lg-10">
            <select  id="valuta" name="valuta" data-placeholder="Pilih Valuta ..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("matauang") as $isi) {
                  echo "<option value='$isi->jenis_valas'>$isi->jenis_valas</option>";
               } ?>
              </select>
            </div>
             </div><!-- /.form-group -->

              <div class="form-group">
                <label for="Kurs" class="control-label col-lg-2">Kurs </label>
                <div class="col-lg-10">
                  <input type="text" name="kurs" placeholder="Kurs" class="form-control" >
                </div>
              </div><!-- /.form-group -->
          
            <div class="form-group"  id="panel_barang">
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
                     <th>Stock</th>
                     <th>Qty</th>
                     <th>Harga</th>
                     <th>Nilai</th>
                    <!--  <th>Berat</th>
                     <th>Lokasi</th> -->
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                   <tr id="baris_1">
                     <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('1')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                     <td><input type="text" id="form_kode_1" placeholder="Kode Barang" onclick="cari_kode('1')" class="form-control" name="kode[]" style="width: 300px" >
                      <input type="hidden" name="kode_input[]" id="kode_input_1"> 
                     </td> 
                     <td><input type="text" id="form_unit_1" class="form-control" name="unit[]" style="width: 150px" readonly=""></td> 
                     <td><input type="text" id="form_stock_1" class="form-control" name="stock[]" style="width: 150px" readonly="">  </td>  
                     <td><input type="number" onkeyup="sum_nilai_cek(this.value,'1')"  id="form_qty_1" class="form-control" name="jumlah_1" name="jumlah[]" required="" ><i id="error_stock_1" style="color: red"></i></td>
                     <td><input type="number" onkeyup="sum_nilai(this.value,'1')" id="form_harga_1" class="form-control" name="harga[]" ></td>
                     <td><input type="text" id="form_nilai_1" class="form-control" name="nilai[]" readonly=""></td>
                   <!--   <td><input type="number" id="form_berat_1" class="form-control" name="berat[]" ></td>
                     <td><input type="text" id="form_lokasi_1" class="form-control" name="lokasi[]" ></td> -->
                   </tr>
                 </tbody>
                 <tfoot>
                    <tr>
                     <th style="width:50px;text-align: center">
                       <a style="cursor: pointer;" onclick="add_baris()" ><i class="fa fa-plus"></i> </a>
                     </th>
                     <th>Kode Barang</th>
                     <th>Unit</th>
                     <th>Stock</th>
                     <th>Qty</th>
                     <th>Harga</th>
                     <th>Nilai</th>
                    <!--  <th>Berat</th>
                     <th>Lokasi</th> -->
                   </tr>
                 </tfoot>
               </table>
                 </div>
               <input type="hidden" id="jml" value="1">
              
              </div><!-- /.form-group -->
                      
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>pengeluaran-hamparan" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                 <button type="submit" id="btn_simpan" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
           
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

  function cek_semua_stock(){

    var disable = false;

    $("[id^=form_qty_]").each(function(){

        var id = $(this).attr("id").split("_")[2];
        var qty = parseFloat($(this).val()) || 0;
        var stock = parseFloat($("#form_stock_"+id).val()) || 0;

        if(stock <= 0 || qty > stock){
            disable = true;

            $(this).css({
                "background":"#ffb3b3",
                "border":"1px solid red"
            });

            $("#form_kode_"+id).css("color","red");

        } else {

            $(this).css({
                "background":"",
                "border":""
            });

            $("#form_kode_"+id).css("color","");
        }

    });

    $("#btn_simpan").prop("disabled", disable);
}

    function show_panel(val){
      // alert(val);
       $("#panel_barang").show();
    }
   function hapus_baris(id) {

    // hapus baris
    $("#baris_"+id).remove(); 

    // 🔥 RE-CHECK SEMUA STOCK
    cek_semua_stock();
}

   function sum_nilai_cek(val,id) { 
      var kode  = $("#kode_input_"+id).val();
      var jml   = parseFloat(val);
      var stock = parseFloat($("#form_stock_"+id).val());
      if (jml>stock) {
          // alert("Inputan melebihi stock");
          $("#error_stock_"+id).html("Inputan melebihi stock");
          $("#error_stock_"+id).show();
          $("#form_qty_"+id).val('');
          $("#form_qty_"+id).focus();
      }else{ 
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
        $("#error_stock_"+id).hide();
      }
       

    }

    function add_baris(id) {
      var id_baris =  parseInt($("#jml").val())+1;
      var baris = '<tr id="baris_'+id_baris+'"><td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris(\''+id_baris+'\')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td><td><input type="text" class="form-control" placeholder="Kode Barang" onclick="cari_kode(\''+id_baris+'\')" id="form_kode_'+id_baris+'" name="kode[]" style="width: 300px" > <input type="hidden" id="kode_input_'+id_baris+'" name="kode_input[]" id="kode_input_1"> </td><td><input type="text" class="form-control" id="form_unit_'+id_baris+'" name="unit[]" style="width: 150px" readonly=""></td><td><input type="text" id="form_stock_'+id_baris+'" class="form-control" name="stock[]" style="width: 150px" readonly=""></td> <td><input type="number" onkeyup="sum_nilai_cek(this.value,\''+id_baris+'\')" id="form_qty_'+id_baris+'" name="jumlah[]" class="form-control" name="jumlah_'+id_baris+'" required="" ><i id="error_stock_'+id_baris+'" style="color: red"></i></td><td><input type="number" onkeyup="sum_nilai(this.value,\''+id_baris+'\')" onkeyup="cek_stok(\''+id_baris+'\',this.value)" id="form_harga_'+id_baris+'" class="form-control" name="harga[]" ></td><td><input type="text" class="form-control" name="nilai[]" id="form_nilai_'+id_baris+'" readonly=""></td></tr>';

      

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
                                url: "<?= base_url() ?>get_stock.php?act=get_stock_outgoing",
                                data: { 
                                  kode   :  ui.item.kd_barang,
                                  jumlah : '0',
                                  jenis : $('input[name="dari"]:checked').val()
                                }, 
                                type : 'POST',
                                dataType : 'JSON',
                                success: function (data) {
                                   $("#form_stock_"+id).val(data.stock); 
                                }
                              });
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

  $(document).on("keyup change", "[id^=form_qty_]", function(){
    cek_semua_stock();
});
    $(document).ready(function() {

    $("#no_sales_order").change(function(){

    var no_so = $(this).val();
    if(no_so == '') return;

    $("#isi_tabel").html('');
    $("#jml").val(0);

    var ada_kurang = false;

    $.ajax({
        url: "<?=base_admin();?>modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php?act=get_sales_order_detail",
        type: "POST",
        data: {no_sales_order: no_so},
        dataType: "json",
        success: function(res){

            // reset flag
            ada_kurang = false;

            // 🔥 AUTO SET PENERIMA
            if(res.penerima){
                $("#penerima").val(res.penerima).trigger("chosen:updated");
            }

            var no = 0;

            $.each(res.detail, function(i, item){

//               if(item.stock_gudang <= 0){
//     return; // skip item (tidak dimasukkan ke tabel)
// }

                no++;
                add_baris();

                $("#form_kode_"+no).val(item.kd_barang+" - "+item.nm_barang);
                $("#kode_input_"+no).val(item.kd_barang);
                $("#form_unit_"+no).val(item.satuan);
                $("#form_qty_"+no).val(item.qty);
                $("#form_stock_"+no).val(item.stock_gudang);

                // 🔥 VALIDASI STOCK (0 ATAU KURANG)
                if(item.stock_gudang <= 0 || item.qty > item.stock_gudang){

                    ada_kurang = true;

                    $("#form_qty_"+no).css({
                        "background":"#ffb3b3",
                        "border":"1px solid red"
                    });

                    $("#form_kode_"+no).css("color","red");

                } else {

                    $("#form_qty_"+no).css({
                        "background":"",
                        "border":""
                    });

                    $("#form_kode_"+no).css("color","");
                }

            });

            // 🔥 CONTROL TOMBOL SIMPAN (INI YANG KAMU MAU)
           cek_semua_stock();

            $("#panel_barang").show();
        }
    });

});
     
          //chosen select
          $(".chzn-select").chosen();
          $(".chzn-select-deselect").chosen({
              allow_single_deselect: true
          });
        
    
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
      
    $("#input_pengeluaran_hamparan").validate({
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
           
            var text;
            if (confirm("Add new record ?") == true) {
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
            } else{
               $("#loadnya").hide();
            }
           
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