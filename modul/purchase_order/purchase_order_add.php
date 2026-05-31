<!-- Content Header (Page header) -->
   

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add Purchase Order</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
           <form id="input_purchase_order" method="post" class="form-horizontal" action="<?=base_admin();?>modul/purchase_order/purchase_order_action.php?act=in">
        

          <div class="row">
  <!-- Kiri -->
  <div class="col-md-6">
    <!-- Header PO -->
    <h4  class="text-center"><b>Purchase Order</b></h4> 

    <div class="form-group" style="display: none">
      <label for="purchase_order_no" class="control-label col-lg-3">PO No.</label>
      <div class="col-lg-9">
        <input type="text" name="purchase_order_no"  id="purchase_order_no" value="<?= generate_po_no(date("Y"),date("m")) ?>" placeholder="Purchase Order No." class="form-control" readonly>
      </div>
    </div>

    <div class="form-group">
      <label for="customer_id" class="control-label col-lg-3">Customer ID</label>
      <div class="col-lg-9">
        <input type="text" name="customer_id" id="customer_id"  placeholder="Customer ID" class="form-control" readonly="">
      </div>
    </div>

    <div class="form-group">
      <label for="date" class="control-label col-lg-3">Date</label>
      <div class="col-lg-9">
        <div class="input-group date" id="tgl1">
          <input type="text" class="form-control" name="po_date" onchange="ganti_no_po(this.value)" />
          <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label for="delivery_date" class="control-label col-lg-3">Delivery Date</label>
      <div class="col-lg-9">
        <div class="input-group date" id="tgl2">
          <input type="text" class="form-control" name="delivery_date" />
          <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label for="arrival_date" class="control-label col-lg-3">Arrival Date</label>
      <div class="col-lg-9">
        <div class="input-group date" id="tgl3">
          <input type="text" class="form-control" name="arrival_date" />
          <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label for="shipped_via" class="control-label col-lg-3">Shipped Via</label>
      <div class="col-lg-9">
        <input type="text" name="shipped_via" placeholder="Shipped Via" class="form-control">
      </div>
    </div>

    <div class="form-group">
      <label for="delivery_term" class="control-label col-lg-3">Delivery Term</label>
      <div class="col-lg-9">
        <input type="text" name="delivery_term" placeholder="Delivery Term" class="form-control">
      </div>
    </div>

    <div class="form-group">
      <label for="payment_term" class="control-label col-lg-3">Payment Term</label>
      <div class="col-lg-9">
        <input type="text" name="payment_term" placeholder="Payment Term" class="form-control">
      </div>
    </div>
     <div class="form-group">
      <label for="payment_term" class="control-label col-lg-3">Note</label>
      <div class="col-lg-9">
        <textarea class="form-control" name="catatan" style="height: 100px"></textarea>
      </div>
    </div>
     <div class="form-group">
      <label for="payment_term" class="control-label col-lg-3">Currency</label>
      <div class="col-lg-9">
        <select id="currency" name="currency" data-placeholder="Pilih Currency..." class="form-control chzn-select" tabindex="2" >
          <option value="">-Pilih Currency-</option> 
         <?php
         $q = $db->query("select * from matauang  group by jenis_valas");
         foreach ($q as $k) {
            echo "<option value='$k->jenis_valas'>$k->jenis_valas</option>";
         }
         ?>
       </select>
      </div>
    </div>
      <div class="form-group">
      <label for="payment_term" class="control-label col-lg-3">Include Tax</label>
      <div class="col-lg-9">
        <input type="radio" name="tax" value="ya" style="position: relative;top: 3px">&nbsp;Yes &nbsp; <input type="radio" name="tax" value="no" style="position: relative;top: 3px">&nbsp; No &nbsp;
      </div>
    </div>
  </div>

  <!-- Kanan -->
  <div class="col-md-6">
    <!-- Seller (Vendor) -->
    <h4 class="text-center"><b>Seller (Vendor)</b></h4>

    <div class="form-group">
      <label for="seller_name" class="control-label col-lg-3">Name</label>
      <div class="col-lg-9">
        <select id="seller_code" name="seller_code" data-placeholder="Pilih Supplier..." class="form-control chzn-select" tabindex="2" >
          <option value="">-Pilih Vendor-</option> 
         <?php
         $q = $db->query("select * from pemasok");
         foreach ($q as $k) {
            echo "<option value='$k->nama'>$k->nama</option>";
         }
         ?>
       </select>
      </div>
    </div>

    <div class="form-group">
      <label for="seller_address" class="control-label col-lg-3">Address</label>
      <div class="col-lg-9">
        <textarea name="seller_address" id="seller_address" placeholder="Seller Address" class="form-control"></textarea>
      </div>
    </div>

    <div class="form-group">
      <label for="seller_phone" class="control-label col-lg-3">Phone</label>
      <div class="col-lg-9">
        <input type="text" name="seller_phone" id="seller_phone" placeholder="Phone" class="form-control">
      </div>
    </div>

    <div class="form-group">
      <label for="seller_pic" class="control-label col-lg-3">PIC</label>
      <div class="col-lg-9">
        <input type="text" name="seller_pic" id="seller_pic" placeholder="PIC" class="form-control">
      </div>
    </div>

    <div class="form-group">
      <label for="seller_email" class="control-label col-lg-3">Email</label>
      <div class="col-lg-9">
        <input type="email" name="seller_email" id="seller_email" placeholder="Email" class="form-control">
      </div>
    </div>

    <h4 class="text-center"><b>Ship To (Consignee)</b></h4>

    <div class="form-group">
      <label for="consignee_name" class="control-label col-lg-3">Name</label>
      <div class="col-lg-9">
        <input type="text" name="consignee_name" placeholder="Consignee Name" class="form-control" value="<?= $infokb->nama ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="consignee_address" class="control-label col-lg-3">Address</label>
      <div class="col-lg-9">
        <textarea name="consignee_address" placeholder="Consignee Address" class="form-control"><?= $infokb->alamat ?></textarea>
      </div>
    </div>

    <div class="form-group">
      <label for="consignee_phone" class="control-label col-lg-3">Phone</label>
      <div class="col-lg-9">
        <input type="text" name="consignee_phone" placeholder="Phone" class="form-control" value="<?= $infokb->telp ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="consignee_email" class="control-label col-lg-3">Email</label>
      <div class="col-lg-9">
        <input type="text" name="consignee_email" placeholder="Email" class="form-control" value="<?= $infokb->email ?>">
      </div>
    </div>
  </div>
</div>





                <div class="form-group" id="form_pr">
                
                <div class="col-lg-12">
                <table class="table">
                 <thead>
                   <tr>
                     <th style="width:50px;text-align: center">
                       <a style="cursor: pointer;" onclick="add_baris()" ><i class="fa fa-plus"></i> </a>
                     </th>
                     <th style="width: 300px">Kode Barang</th>
                     <th style="width: 400px">Nama Barang</th>
                     <th style="width: 150px">Spec</th>
                     <th style="width: 100px">Unit</th>
                    
                     <th>Qty</th>  
                     <th>Harga</th>                   
                     <th>Ket</th>
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                   <tr id="baris_1">
                     <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('1')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                     <td><input type="text" id="form_kode_1" placeholder="Kode Barang" onclick="cari_kode('1')" class="form-control" name="kode[]"  >
                      <input type="hidden" name="kode_input[]" id="kode_input_1"> 
                     </td> 
                     <td><input type="text" id="form_name_1" class="form-control" name="name[]"  readonly=""></td> 
                     <td><input type="text" id="form_spec_1" class="form-control" name="spec[]"  readonly=""></td>
                     <td><input type="text" id="form_unit_1" class="form-control" name="unit[]"  readonly=""></td> 
                     <td><input type="text" id="form_qty_1" class="form-control" name="qty[]"  required></td>
                     <td><input type="text" id="form_harga_1" class="form-control harga-input" name="harga[]"  required></td>
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
             <a href="<?=base_index();?>purchase-order" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                 <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
           
                </div>
              </div><!-- /.form-group -->

            </form>

          </div>
        </div>
      </div>
    </div>

    </section><!-- /.content -->

    <script>

      function ganti_no_po(tgl){
        $.ajax({
        url: "<?= base_url() ?>modul/purchase_order/purchase_order_action.php?act=ganti_no_po",
        type: "POST",
        data: {tgl: tgl},
      //  dataType: "json",
        success: function(res){
         $("#purchase_order_no").val(res);
        },
        error: function(xhr, status, error){
          console.log(xhr.responseText);
        }
      });
      }
$(document).ready(function(){
  $("#seller_code").change(function(){
    var kode = $(this).val();
    if(kode != ""){
      $.ajax({
        url: "<?= base_url() ?>modul/purchase_order/purchase_order_action.php?act=cari_vendor",
        type: "POST",
        data: {kode_pemasok: kode},
        dataType: "json",
        success: function(res){
          if(res.success){
            // Isi field otomatis
            $("#seller_address").val(res.data.alamat + ", " + res.data.kota + ", " + res.data.negara);
            $("#customer_id").val(res.data.kode_pemasok);
            $("#seller_phone").val(res.data.notelp);
            //$("#seller_pic").val(res.data.nama);
            $("#seller_email").val(res.data.email);
          } else {
            alert("Data vendor tidak ditemukan!");
          }
        },
        error: function(xhr, status, error){
          console.log(xhr.responseText);
        }
      });
    }
  });
});
</script>

<script type="text/javascript">


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
                                  id_barang : item.id_barang,
                                  spec : item.spec
                                };
                              }))
                            }
                          })
                        },
                        select: function (event, ui) {
                             $('#form_kode_'+id).val(ui.item.kd_barang);
                             $('#form_name_'+id).val(ui.item.nm_barang); 
                             $('#form_spec_'+id).val(ui.item.spec); 
                             $("#kode_input_"+id).val(ui.item.kd_barang);
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

   function hapus_baris(id) {

      $("#baris_"+id).remove();
      // var id_baris =  parseInt($("#jml").val())-1;
      //  $("#jml").val(id_baris);
    } 
 function add_baris(id) {
      var id_baris =  parseInt($("#jml").val())+1;
      var baris = '<tr id="baris_'+id_baris+'"><td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris(\''+id_baris+'\')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td><td><input type="text" class="form-control" placeholder="Kode Barang" onclick="cari_kode(\''+id_baris+'\')" id="form_kode_'+id_baris+'" name="kode[]"  > <input type="hidden" id="kode_input_'+id_baris+'" name="kode_input[]" id="kode_input_'+id_baris+'"></td><td><input type="text" id="form_name_'+id_baris+'" class="form-control" name="name[]"  readonly=""></td><td><input type="text" id="form_spec_'+id_baris+'" class="form-control" name="spec[]"  readonly=""></td> <td><input type="text" class="form-control" id="form_unit_'+id_baris+'" name="unit[]" style="width: 150px" readonly=""></td><td><input type="text"  id="form_qty_'+id_baris+'" class="form-control" name="qty[]" required></td><td><input type="text" id="form_harga_'+id_baris+'" class="form-control harga-input" name="harga[]"  required></td><td><input type="text" class="form-control" name="ket[]" id="form_ket_'+id_baris+'"></td></tr>';

        $("#isi_tabel").append(baris);
        $("#jml").val(id_baris);
    }
  function add_baris_old() {
    var id_baris = parseInt($("#jml").val()) + 1;

    var baris = '<tr id="baris_' + id_baris + '">';
    baris += '<td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris(\'' + id_baris + '\')"><i class="fa fa-trash-o" style="font-size: 25px;"></i></a></td>';
    baris += '<td><input type="number" class="form-control" name="seq[]" id="form_seq_' + id_baris + '"></td>';
    baris += '<td><input type="text" class="form-control" name="mat_code[]" id="form_mat_code_' + id_baris + '"></td>';
    baris += '<td><input type="text" class="form-control" name="model[]" id="form_model_' + id_baris + '"></td>';
    baris += '<td><input type="text" class="form-control" name="article[]" id="form_article_' + id_baris + '"></td>';
    baris += '<td><input type="number" class="form-control" name="nqty[]" id="form_nqty_' + id_baris + '"></td>';
    baris += '<td><input type="number" class="form-control" name="shq[]" id="form_shq_' + id_baris + '"></td>';
    baris += '<td><input type="text" class="form-control" name="tooling_stage[]" id="form_tooling_stage_' + id_baris + '"></td>';
    baris += '<td><input type="text" class="form-control" name="material_name[]" id="form_material_name_' + id_baris + '"></td>';
    baris += '<td><input type="text" class="form-control" name="color[]" id="form_color_' + id_baris + '"></td>';
    baris += '<td><input type="text" class="form-control" name="emboss[]" id="form_emboss_' + id_baris + '"></td>';
    baris += '<td><input type="text" class="form-control" name="thickness[]" id="form_thickness_' + id_baris + '"></td>';
    baris += '<td><input type="text" class="form-control" name="g[]" id="form_g_' + id_baris + '"></td>';
    baris += '<td><input type="text" class="form-control" name="yield[]" id="form_yield_' + id_baris + '"></td>';
    baris += '<td><input type="text" class="form-control" name="component[]" id="form_component_' + id_baris + '"></td>';
    baris += '<td><input type="text" class="form-control" name="unit[]" id="form_unit_' + id_baris + '"></td>';
    baris += '<td><input type="number" class="form-control" name="po_qty[]" id="form_po_qty_' + id_baris + '" onkeyup="hitung_amount(\'' + id_baris + '\')"></td>';
    baris += '<td><input type="text" class="form-control" name="curr[]" id="form_curr_' + id_baris + '"></td>';
    baris += '<td><input type="number" step="0.01" class="form-control" name="unit_price[]" id="form_unit_price_' + id_baris + '" onkeyup="hitung_amount(\'' + id_baris + '\')"></td>';
    baris += '<td><input type="number" step="0.01" class="form-control" name="amount[]" id="form_amount_' + id_baris + '" readonly></td>';
    baris += '<td><input type="text" class="form-control" name="td_vendor[]" id="form_td_vendor_' + id_baris + '"></td>';
    baris += '<td><input type="text" class="form-control" name="ta_factor[]" id="form_ta_factor_' + id_baris + '"></td>';
    baris += '</tr>';

    $("#isi_tabel").append(baris);
    $("#jml").val(id_baris);
}

// Fungsi hitung otomatis Amount
function hitung_amount(id) {
    var qty = parseFloat($("#form_po_qty_" + id).val()) || 0;
    var price = parseFloat($("#form_unit_price_" + id).val()) || 0;
    var total = qty * price;
    $("#form_amount_" + id).val(total.toFixed(2));
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
    
    $("#input_purchase_order").validate({
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
