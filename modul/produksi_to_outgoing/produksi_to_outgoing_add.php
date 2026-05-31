<!-- Content Header (Page header) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <section class="content-header">
                    <h1>
                        Transfer Barang Dari Produksi
                    </h1>
                        <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>transfer-produksi">Transfer Barang</a></li>
                        <li class="active">Transfer barang List</li>
                    </ol>
                </section>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add Transfer Barang</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_produksi_to_outgoing" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/produksi_to_outgoing/produksi_to_outgoing_action.php?act=in">
               <div class="form-group" style="display: none;">
                <label for="Nomor" class="control-label col-lg-2">Asal Stok </label>
                <div class="col-lg-10">
                  <label><input type="radio" name="jenis_barang" onchange="show_panel()" value="1" style="position: relative;top: 3px"> Hasil Produksi </label>
                  <label><input type="radio" name="jenis_barang" onchange="show_panel()" value="0" style="position: relative;top: 3px"> Bukan Hasil Produksi </label>
                </div>
              </div> 

              <div class="form-group">
  <label class="control-label col-lg-2">Pilih Material</label>
  <div class="col-lg-10">

    <select id="ref_produksi" class="form-control select2" multiple>
  <?php
  $q = $db->query("
    SELECT kd_barang, SUM(qty) as stock
    FROM detail_transaksi
    WHERE posisi='PRODUKSI'
    GROUP BY kd_barang
    HAVING SUM(qty) > 0
  ");

  foreach($q as $k){
    echo "<option value='$k->kd_barang'>
      $k->kd_barang - Stock: ".number_format($k->stock,4,",",".")."
    </option>";
  }
  ?>
</select>

  </div>
</div>
                       
              <div class="form-group" style="display: none">
                <label for="Nomor" class="control-label col-lg-2">Nomor </label>
                <div class="col-lg-10">
                  <input type="text" name="nomor" placeholder="Nomor" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group" style="display: none" >
                <label for="No SPB" class="control-label col-lg-2">No SPB </label>
                <div class="col-lg-10">
                  <input type="text" name="no_spb" placeholder="No SPB" class="form-control" >
                </div>
              </div><!-- /.form-group -->
               <div class="form-group" style="display: none">
                        <label for="No Request" class="control-label col-lg-2">Tujuan Transfer  <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
            <select  id="tujuan_transfer" name="tujuan_transfer" data-placeholder="Pilih Tujuan Transfer ..." class="form-control chzn-select" tabindex="2" required="" >
               <option value="1">gudang</option>
                
              </select>
            </div>
           </div>    
              
          <div class="form-group">
              <label for="Tanggal SPB" class="control-label col-lg-2">Tanggal SPB </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" name="tgl_spb" autocomplete="off"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group" style="display: none">
                <label for="No BPB" class="control-label col-lg-2">No BPB </label>
                <div class="col-lg-10">
                  <input type="text" name="no_bpb" placeholder="No BPB" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group" style="display: none">
                        <label for="Departemen" class="control-label col-lg-2">Departemen <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
            <select  id="dept" name="dept" data-placeholder="Pilih Departemen ..." class="form-control chzn-select" tabindex="2" >
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
              <label for="Catatan" class="control-label col-lg-2">Catatan </label>
              <div class="col-lg-10">
                <textarea class="form-control col-xs-12" rows="5" name="catatan" ></textarea>
              </div>
          </div><!-- /.form-group -->
              <div class="form-group" id="form_ro" >
                
                 <div class="col-lg-12">
                   <table class="table">
                 <thead>
                   <tr>
                     <th style="width:50px;text-align: center">
                       <a style="cursor: pointer;" onclick="add_baris()" ><i class="fa fa-plus"></i> </a>
                     </th>
                     <th style="width: 300px">Kode Barang</th>
                   <!--   <th style="width: 150px">Jenis Dokpab</th> -->
                     <th style="width: 100px">Unit</th>
                    <!--  <th>Qty RO</th> -->
                     <th>Stock</th>
                     <th>Qty</th>                     
                     <th>Ket</th>
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                 
                 </tbody>
               </table>
                 </div>
               <input type="hidden" id="jml" value="1">
              
              </div>
                      
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>produksi-to-outgoing" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
               <button type="submit" id="btn_simpan" class="btn btn-primary">
  <i class="fa fa-save"></i> <?php echo $lang["submit_button"];?>
</button>
           
                </div>
              </div><!-- /.form-group -->

            </form>

          </div>
        </div>
      </div>
    </div>

    </section><!-- /.content -->

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script type="text/javascript">

 function cek_stok(id, jumlah){

    var stock = parseFloat($("#form_stock_"+id).val()) || 0;
    var qty   = parseFloat(jumlah) || 0;

    if(qty > stock){

        $("#error_stock_"+id)
            .html("Qty melebihi stock ("+stock+")")
            .show();

        $("#form_qty_"+id).addClass("is-invalid");

    } else {

        $("#error_stock_"+id).html("").hide();
        $("#form_qty_"+id).removeClass("is-invalid");
    }

    cek_semua_stok();
}


function cek_semua_stok(){

    var disable = false;

    $("input[name='qty[]']").each(function(){

        var id = $(this).attr("id").split("_")[2] || $(this).attr("id").split("_")[1];

        var qty   = parseFloat($(this).val()) || 0;
        var stock = parseFloat($("#form_stock_"+id).val() || $("#stock_"+id).val()) || 0;

        // 🔥 HANYA disable kalau qty > stock
        if(qty > stock){
            disable = true;
        }

    });

    // 🔥 INI YANG PENTING
    $("#btn_simpan").prop("disabled", disable);

}

function cek_stok_multi(id){

    var stock = parseFloat($("#stock_"+id).val()) || 0;
    var qty   = parseFloat($("#qty_"+id).val()) || 0;

    // hapus dulu warning lama
    $("#warning_"+id).remove();

    if(qty > stock){

        $("#qty_"+id).addClass("is-invalid");

        // 🔥 tampilkan label warning
        $("#qty_"+id).after(`
            <small id="warning_${id}" style="color:red;">
                Qty melebihi stock (Stock: ${stock})
            </small>
        `);

    } else {

        $("#qty_"+id).removeClass("is-invalid");

    }

    cek_semua_stok();
}
  $(document).ready(function(){

    $('#ref_produksi').select2({
        placeholder: "-- Pilih Barang Produksi --",
        allowClear: true,
        width: '100%'
    });

});

 $("#ref_produksi").change(function(){

    var selected = $(this).val();

    if(!selected || selected.length == 0){
        return;
    }

    $.ajax({
        url: "<?=base_admin()?>modul/produksi_to_outgoing/produksi_to_outgoing_action.php?act=get_stock_produksi_multi",
        type: "POST",
        data: {kode: selected},
        dataType: "json",
        success: function(res){

            var no = $("#isi_tabel tr").length + 1;

            $.each(res, function(i, item){

                // 🔥 CEK SUDAH ADA BELUM
                var sudah_ada = false;

                $("input[name='kode_input[]']").each(function(){
                    if($(this).val() == item.kode){
                        sudah_ada = true;
                    }
                });

                // 🔥 SKIP kalau sudah ada
                if(sudah_ada){
                    return;
                }

                var row = `
                <tr id="baris_${no}">
                    <td>${no}</td>

                    <td>
                        ${item.kode}
                        <input type="hidden" name="kode_input[]" value="${item.kode}">
                    </td>

                    <td>${item.satuan}</td>

                    <td>
                        ${item.stock}
                        <input type="hidden" id="stock_${no}" value="${item.stock}">
                    </td>

                    <td>
                        <input type="number" 
                            name="qty[]" 
                            id="qty_${no}" 
                            class="form-control"
                            onkeyup="cek_stok_multi(${no})">
                    </td>

                    <td>
                        <input type="text" name="ket[]" class="form-control">
                    </td>
                </tr>
                `;

                $("#isi_tabel").append(row);
                no++;

            });

        }
    });

});

  function cek_stok_row(id){

    var stock = parseFloat($("#stock_"+id).val()) || 0;
    var qty   = parseFloat($("#qty_"+id).val()) || 0;

    if(qty > stock){
        $("#qty_"+id).val(stock);
    }
}

  function show_panel() {
    $("#form_ro").show();
  }

  function cek_stok(id,jumlah){
    var kode  = $("#kode_input_"+id).val();
    var jml   = parseFloat(jumlah);
    var stock = parseFloat($("#form_stock_"+id).val());
    if (jml>stock) {
        // alert("Inputan melebihi stock");
        $("#error_stock_"+id).html("Inputan melebihi stock");
        $("#error_stock_"+id).show();
        $("#form_qty_"+id).val('');
        $("#form_qty_"+id).focus();
    }else{
      $("#error_stock_"+id).hide();
    }
 
  }

  function get_detail_ro(no_ro){
   $.ajax({
          url: "<?= base_url() ?>modul/produksi_to_outgoing/produksi_to_outgoing_action.php?act=get_detail_ro",
          data: { 
            no_ro: no_ro
          },
          type : 'POST',
          success: function (data) {
             $("#form_ro").html(data);
          }
        })
  }

  function hapus_baris(id) {

      $("#baris_"+id).remove();
      // var id_baris =  parseInt($("#jml").val())-1;
      //  $("#jml").val(id_baris);
    }

    function add_baris(id) {
      var id_baris =  parseInt($("#jml").val())+1;
      var baris = '<tr id="baris_'+id_baris+'"><td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris(\''+id_baris+'\')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td><td><input type="text" class="form-control" placeholder="Kode Barang" onclick="cari_kode(\''+id_baris+'\')" id="form_kode_'+id_baris+'" name="kode[]"  > <input type="hidden" id="kode_input_'+id_baris+'" name="kode_input[]" id="kode_input_1"> </td><td><input type="text" class="form-control" id="form_unit_'+id_baris+'" name="unit[]" style="width: 150px" readonly=""></td><td><input type="text" id="form_stock_'+id_baris+'" class="form-control" name="stock[]" readonly="" ></td><td><input type="text"  id="form_qty_'+id_baris+'" class="form-control" name="qty[]"  onkeyup="cek_stok(\''+id_baris+'\',this.value)" required></td><td><input type="text" class="form-control" name="ket[]" id="form_ket_'+id_baris+'"></td></tr>';

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
                                  nm_barang: item.nm_barang,
                                  id_barang : item.id_barang
                                };
                              }))
                            }
                          })
                        },
                        select: function (event, ui) {
                             $('#form_kode_'+id).val(ui.item.kd_barang+" - "+ui.item.nm_barang); 
                             $("#kode_input_"+id).val(ui.item.kd_barang);
                             $("#id_input_"+id).val(ui.item.id_barang);

                              $.ajax({
                                url: "<?= base_url() ?>get_stock.php?act=get_stock_produksi",
                                data: { 
                                  kode   :  ui.item.kd_barang,
                                  jumlah : '0'
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
      
    $("#input_produksi_to_outgoing").validate({
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
            
          // dept: {
          // required: true,
          // //minlength: 2
          // },
        
        },
         messages: {
            
          // dept: {
          // required: "This field is required",
          // //minlength: "Your username must consist of at least 2 characters"
          // },
        
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
