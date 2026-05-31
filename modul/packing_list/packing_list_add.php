<!-- Content Header (Page header) -->

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-results__option {
    white-space: pre-line;
}
</style> 
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <section class="content-header">
        <h1>Packing List</h1>
        <ol class="breadcrumb">
            <li>
              <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
            </li>
            <li>
              <a href="<?=base_index();?>packing-list">Packing List</a>
            </li>
            <li class="active">Add Packing List</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add Packing List</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_packing_list" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/packing_list/packing_list_action.php?act=in">
                      
              <div class="form-group"> 
                <label for="Packing List Number" class="control-label col-lg-2">Packing List Number <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" value="<?= generate_no_packing_list(date("Y"),date("m")) ?>" name="no_packing_list" placeholder="Packing List Number" class="form-control" readonly required>
                </div>
              </div><!-- /.form-group -->
           <div class="form-group">
    <label for="Delivery Order No" class="control-label col-lg-2">
        No Surat Jalan <span style="color:#FF0000">*</span>
    </label>

    <div class="col-lg-10">

        <select id="no_sj"
                name="no_sj"
                class="form-control"
                required
                style="width:100%">
        </select>

    </div>
</div>

          <div class="form-group">
              <label for="Tanggal DO" class="control-label col-lg-2">Tanggal DO </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" name="tgl_sj" readonly />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Penerima" class="control-label col-lg-2">Penerima <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
            <select  id="penerima" name="penerima" data-placeholder="Pilih Penerima ..." class="form-control chzn-select" tabindex="2" required readonly>
               <option value=""></option>
               <?php foreach ($db->fetch_all("penerima") as $isi) {
                  echo "<option value='$isi->kode_penerima'>$isi->nama</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->


              <div class="form-group">
                <label for="Invoice No" class="control-label col-lg-2">Invoice No </label>
                <div class="col-lg-10">
                  <input type="text" name="no_invoice" placeholder="Invoice No" class="form-control"  >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="No PO" class="control-label col-lg-2">No PO </label> 
                <div class="col-lg-10">
                  <input type="text" name="no_po" placeholder="No PO" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group" style="display: none">
                        <label for="Valuta" class="control-label col-lg-2">Valuta </label>
                        <div class="col-lg-10">
            <select  id="valuta" name="valuta" data-placeholder="Pilih Valuta ..." class="form-control chzn-select" tabindex="2" readonly >
               <option value=""></option>
               <?php foreach ($db->fetch_all("matauang") as $isi) {
                  echo "<option value='$isi->jenis_valas'>$isi->jenis_valas</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

              <div class="form-group" style="display: none">
                <label for="Kurs" class="control-label col-lg-2">Kurs </label>
                <div class="col-lg-10">
                  <input type="text" name="kurs" id="kurs" placeholder="Kurs" class="form-control" readonly>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Vehicle No" class="control-label col-lg-2">No Kendaraan <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="vehicle_no" placeholder="Vehicle No" class="form-control" required="">
                </div>
              </div><!-- /.form-group -->

              <div class="form-group" id="panel_barang">
                
                 <div class="col-lg-12" style="overflow: scroll;">
                  <table class="table">
                <thead>
                    <tr>

                        <th style="width:50px;text-align: center">
                            <a style="cursor: pointer;" onclick="add_baris()">
                                <i class="fa fa-plus"></i>
                            </a>
                        </th>

                        <th style="width: 300px">Kode Barang</th>
                        <th style="width: 200px">Unit</th>
                        <th style="width: 200px">Qty Barang</th>
                      
                        <th style="width: 200px">Packing</th>
                        <th style="width: 200px">Remark</th>

                    </tr>
                    </thead>
                 <tbody id="isi_tabel">
                   <?php

$no = 1;


   // $nilai = $k->qty_kirim * $k->harga_jual;

?>

<tr id="baris_<?= $no ?>">

    <td style="text-align: center">
        <a style="cursor: pointer;" onclick="hapus_baris('<?= $no ?>')">
            <i class="fa fa-trash" style="font-size: 25px;"></i>
        </a>
    </td>

    <td>
        <input
            type="text"
            id="form_kode_<?= $no ?>"
            class="form-control"
            name="kode[]"
            style="width: 300px"
            readonly
        >

        <input
            type="hidden"
            name="kode_input[]"
            id="kode_input_<?= $no ?>"
        >
    </td>

    <td>
        <input
            type="text"
            id="form_unit_<?= $no ?>"
            class="form-control"
            name="unit[]"
            style="width: 150px"
            readonly
        >
    </td>

    <td>
        <input
            type="text"
            id="form_qty_<?= $no ?>"
            class="form-control"
            name="jumlah_<?= $no ?>"
            style="width: 150px"
        >
    </td>

   

    <td>
        <input
            type="text"
            id="form_packing_<?= $no ?>"
            class="form-control"
            name="packing[]"
            style="width: 150px"
        >
    </td>

    <td>
        <input
            type="text"
            id="form_remark_<?= $no ?>"
            class="form-control"
            name="remark[]"
            style="width: 150px"
        >
    </td>

</tr>


                 </tbody>
                 
               </table>
              </div>
               <input type="hidden" id="jml" value="1">
              
              </div><!-- /.form-group -->
              
                      
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>packing-list" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
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

  $(document).ready(function () {

    $('#no_sj').select2({

      //  placeholder: 'Pilih Delivery Order ...',

        minimumInputLength: 1,

        ajax: {

            url: '<?= base_url() ?>modul/packing_list/packing_list_action.php?act=get_surat_jalan',

            type: 'POST',

            dataType: 'json',

            delay: 250,

            data: function (params) {

                return {
                    search: params.term
                };

            },

            processResults: function (data) {

                return {
                    results: data
                };

            },

            cache: true
        }

    });

    $('#no_sj').on('change', function () {

        pilih_do(this.value);

    });

});

  function pilih_do(no_sj) {
    if (no_sj === "") return;

    $.ajax({
        url: "<?= base_url() ?>modul/packing_list/packing_list_action.php?act=get_detail",
        type: "POST",
        dataType: "json",
        data: { no_sj: no_sj },
        success: function (res) {

            // Pastikan respon valid
            if (!res) {
                alert("Data DO tidak ditemukan!");
                return;
            }

            // Isi field tanggal DO
            $("input[name='tgl_sj']").val(res.tgl_sj);

            // Isi field penerima
            $("#penerima").val(res.penerima).trigger("chosen:updated");

            // Isi field pemilik
            $("#pemilik").val(res.pemilik).trigger("chosen:updated");

            // Isi Invoice No
            $("input[name='no_invoice']").val(res.no_invoice);

            // Isi No PO
            $("input[name='no_po']").val(res.no_po);

            // Isi Valuta
            $("#valuta").val(res.valuta).trigger("chosen:updated");

            // Isi Kurs (jika ada field)
            if ($("#kurs").length > 0) {
                $("#kurs").val(res.kurs);
            }
        },
        error: function (xhr, status, error) {
            alert("Gagal mengambil data DO. Error: " + error);
        }
    });
    $.ajax({
        url: "<?= base_url() ?>modul/packing_list/packing_list_action.php?act=get_detail_barang",
        type: "POST",
     //   dataType: "json",
        data: { no_sj: no_sj }, 
        success: function (res) {
          $("#panel_barang").html(res);
        },
        error: function (xhr, status, error) {
            alert("Gagal mengambil data DO. Error: " + error);
        }
    });
}




  function check_stock(val,no){
    $.ajax({
       url  : "<?= base_url() ?>modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php?act=cek_stock",
       type : "POST",
       data : {
         kode : $("#kode_input_"+no).val(), 
         jenis_dokpab : val
       },
       success : function(data){ 
         $("#form_stock_"+no).val(data);
       }
    });
  }

  function cek_valuta(kode){ 
  //  var kode = $("#KODE_VALUTA").val();
  $("#kurs").attr('readonly',true);
  $("#kurs").val('get data ...');
    $.ajax({
       url : "<?= base_url() ?>modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php?act=get_currency",
       type : "POST",
       data : {
         kode : kode, 
         //d_header : $("#ID").val()
       },
      // dataTye : 'JSON',
       success : function(data){ 
          $("#kurs").val(data);
          $("#kurs").attr('readonly',false);
        // save_data(data,'NDPBM',$('#ID').val(),'ws_header','id_header');
        // $("#kantor_pabean_pengawas").val(data);
       }
    });
  }

    function pilih_po(no_po){
       $.ajax({
          url: "<?= base_url() ?>modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php?act=get_so",
          data: { no_po: no_po },
          type : 'POST',
          success: function (data) {
            $("#panel_barang").html(data);
           // $("#satuan").val(data.satuan); 
          } 
       });
        $.ajax({
          url: "<?= base_url() ?>modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php?act=get_pemasok",
          data: { no_po: no_po },
          type : 'POST',
          dataTye : 'JSON',
          success: function (data) { 
            $("#penerima").val(data.id_customer).trigger('chosen:updated');
            $('#penerima').val(data.id_customer).trigger('change');
            $("#valuta").val(data.valuta).trigger('chosen:updated');
            $('#valuta').val(data.valuta).trigger('change');
            $("#nopo").val(data.no_po);  
          } 
       });
    }

    function show_panel(val){
      // alert(val);
       $("#panel_barang").show();
    }
    function hapus_baris(id) {

      $("#baris_"+id).remove();
      // var id_baris =  parseInt($("#jml").val())-1;
      //  $("#jml").val(id_baris);
    }

   function sum_nilai_cek(val,id) { 
      var kode  = $("#kode_input_"+id).val();
      var jml   = parseFloat(formatNumber(val));
      var stock = parseFloat(formatNumber($("#form_stock_"+id).val()));
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
         $("#form_nilai_"+id).val(formatAngka(c));
        $("#error_stock_"+id).hide();
      }
       

    }

     function add_baris() {
    var id_baris = parseInt($("#jml").val()) + 1;

    var baris = `
    <tr id="baris_${id_baris}">
        <td style="text-align: center">
            <a style="cursor: pointer;" onclick="hapus_baris('${id_baris}')">
                <i class="fa fa-trash-o" style="font-size: 25px;"></i>
            </a>
        </td>

        <td>
            <input type="text" id="form_kode_${id_baris}" placeholder="Kode Barang"
                onclick="cari_kode('${id_baris}')"
                class="form-control" name="kode[]" style="width: 300px">
            <input type="hidden" name="kode_input[]" id="kode_input_${id_baris}">
        </td>

      

        <td><input type="text" id="form_unit_${id_baris}" class="form-control" name="unit[]" style="width: 150px" readonly=""></td>
     

        <td>
            <input type="text" onkeyup="sum_nilai_cek(this.value,'${id_baris}')"
                id="form_qty_${id_baris}" class="form-control" name="jumlah_${id_baris}"
                required="" style="width: 150px">
            <i id="error_stock_${id_baris}" style="color: red"></i>
        </td>

       

      
        <td><input type="text" id="form_packing_${id_baris}" class="form-control" name="packing[]" style="width: 150px"></td>

      

        <td><input type="text" id="form_remark_${id_baris}" class="form-control" name="remark[]" style="width: 150px"></td>
    </tr>
    `;

    $("#isi_tabel").append(baris);
    $("#jml").val(id_baris);

    // Refresh chosen-select jika digunakan
    if ($(".chzn-select").length > 0) {
        $(".chzn-select").chosen();
    }
}


    function sum_nilai(val,id) {
       var a = formatNumber($("#form_qty_"+id).val());
       var b = formatNumber($("#form_harga_"+id).val());
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
       $("#form_nilai_"+id).val(formatAngka(c));

    }

      function cari_kode(id) {   
       if ($("#jenisbckeluar_jenis_dokpab").val()=='') {
         alert("Jenis Dokumen Pabean Belum Dipilih");
       }else{
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
                                url: "<?= base_url() ?>get_stock.php?act=get_stock_incoming2",
                                data: { 
                                  kode   :  ui.item.kd_barang, 
                                  jumlah : '0',
                                  jenis_dokpab : $("#jenisbckeluar_jenis_dokpab").val(),
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
      
    $("#input_packing_list").validate({
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
            
          no_packing_list: {
          required: true,
          //minlength: 2
          }
        
         
        
        },
         messages: {
            
          no_packing_list: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
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
