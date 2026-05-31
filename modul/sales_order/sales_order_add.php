

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-lg-12">
        <div class="box box-solid box-primary">
          <div class="box-header">
            <h3 class="box-title">Add Sales Order</h3>
            <div class="box-tools pull-right">
              <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_sales_order" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/sales_order/sales_order_action.php?act=in">
          <div class="row">
            <div class="col-md-6"> 
               <div class="form-group" style="display: none;">
                <label for="Rupiah Rate" class="control-label col-lg-2">Nomor </label>
                <div class="col-lg-10">
                  <input type="text" name="no_sales_order" value="<?= get_nomor_transaksi("so") ?>" placeholder="Rupiah Rate" class="form-control" readonly="" >
                </div>
              </div>
                      <div class="form-group">
                        <label for="Quotation" class="control-label col-lg-2">Quotation </label>
                        <div class="col-lg-10">
            <select  id="id_quotation" name="id_quotation" onchange="pilih_pr(this.value)" data-placeholder="Pilih Quotation ..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->fetch_all("sales_quotation") as $isi) {
                  echo "<option value='$isi->id_quotation'>$isi->no_sales_quotation</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

          <div class="form-group">
              <label for="Saler Order Date" class="control-label col-lg-2">Saler Order Date </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" name="so_date" required="" autocomplete="off"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          <div class="form-group" style="display: none;">
              <label for="Currency" class="control-label col-lg-2">Gudang </label>
           <div class="col-lg-10">
            <select  id="no_store"  name="no_store" data-placeholder="Pilih Gudang ..." class="form-control" tabindex="2" required  >
               <option value=""></option>
               <?php foreach ($db->query("select id_store,nama_store from store_location ") as $isi) {
                  echo "<option value='$isi->id_store'>$isi->nama_store</option>";
               } ?>
              </select>
            </div>
          </div><!-- /.form-group -->
          <div class="form-group">
                        <label for="Currency" class="control-label col-lg-2">Currency </label>
                        <div class="col-lg-10">
            <select  id="currency" onchange="get_currency(this.value)" name="currency" data-placeholder="Pilih Currency ..." class="form-control chzn-select" tabindex="2" required=""  >
               <option value=""></option>
               <?php foreach ($db->query("select jenis_valas from matauang group by jenis_valas") as $isi) {
                  echo "<option value='$isi->jenis_valas'>$isi->jenis_valas</option>";
               } ?>
              </select>
            </div>
          </div><!-- /.form-group -->

              <div class="form-group">
                <label for="Rupiah Rate" class="control-label col-lg-2">Rupiah Rate </label>
                <div class="col-lg-10">
                  <input type="text" id="rupiah_rate" name="rupiah_rate" placeholder="Rupiah Rate" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group" style="display: none">
                <label for="Kur TT Counter Sale" class="control-label col-lg-2">Kur TT Counter Sale </label>
                <div class="col-lg-10">
                  <input type="text" id="rupiah_rate_sale" name="rupiah_rate_sale" placeholder="Kur TT Counter Sale" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Company Name" class="control-label col-lg-2">Company Name </label>
                        <div class="col-lg-10">
            <select  id="kode_penerima" name="kode_penerima" onchange="get_pemasok(this.value)" data-placeholder="Pilih Company Name ..." class="form-control chzn-select" tabindex="2" required=""  >
               <option value=""></option>
               <?php foreach ($db->fetch_all("penerima") as $isi) {
                  echo "<option value='$isi->kode_penerima'>$isi->nama</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->
              <div class="form-group">
                <label for="Entry User" class="control-label col-lg-2">Consignee </label>
                <div class="col-lg-10">
                  <input type="text" name="consignee" id="consignee"  placeholder="Consignee" class="form-control" >
                </div>
              </div><!-- /.form-group -->
             <div class="form-group">
                <label for="Entry User" class="control-label col-lg-2">Notify Party </label>
                <div class="col-lg-10">
                  <input type="text" name="notify_party" id="notify_party"  placeholder="Notify Party" class="form-control" >
                </div>
              </div><!-- /.form-group -->

               <div class="form-group">
                <label for="Entry User" class="control-label col-lg-2">Other Reference </label>
                <div class="col-lg-10">
                  <input type="text" name="other_reference" id="other_reference"  placeholder="Other Reference" class="form-control" >
                </div>
              </div>
               <div class="form-group">
                <label for="Entry User" class="control-label col-lg-2">Notee </label>
                <div class="col-lg-10">
                  <textarea class="form-control" name="catatan" placeholder="Note"></textarea>
                </div>
              </div>
              
            </div>
            <div class="col-md-6">
                <div class="form-group">
                  <label for="Tax" class="control-label col-lg-2">Tax </label>
                  <div class="col-lg-10"> 
                    
                <div class="radio radio-success radio-inline">
                  <input type="radio" name="tax"  id="radio1" value="include"  onchange="set_panel_pajak()">
                    <label for="radio1" style="padding-left: 5px;">
                      Exclude
                    </label>
                </div>
                
                <div class="radio radio-success radio-inline">
                  <input type="radio" name="tax"  id="radio2" value="exclude"  onchange="set_panel_pajak2()" >
                    <label for="radio2" style="padding-left: 5px;">
                      Include
                    </label>
                </div>
                
                  </div>
                </div><!-- /.form-group -->
                
              <div class="form-group">
                <label for="Sales ID" class="control-label col-lg-2">Sales ID </label>
                <div class="col-lg-10">
                  <input type="text" name="sales_id" id="sales_id" placeholder="Sales ID" class="form-control" >
                </div>
              </div><!-- /.form-group -->

               <div class="form-group">
                <label for="Purchase Ref" class="control-label col-lg-2">No PO </label>
                <div class="col-lg-10">
                  <input type="text" name="no_po" placeholder="No PO" class="form-control" >
                </div>
              </div>
              
              <div class="form-group">
                <label for="Purchase Ref" class="control-label col-lg-2">Purchase Ref </label>
                <div class="col-lg-10">
                  <input type="text" name="purchase_ref" placeholder="Purchase Ref" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Entry User" class="control-label col-lg-2">Entry User </label>
                <div class="col-lg-10">
                  <input type="text" name="user" id="user" readonly="" value="<?= $_SESSION['username'] ?>" placeholder="Entry User" class="form-control" >
                </div>
              </div><!-- /.form-group -->

              <div class="form-group">
                <label for="delivery_term" class="control-label col-lg-2">Delivery Term </label>
                <div class="col-lg-10">
                  <input type="text" name="delivery_term" id="delivery_term" placeholder="Delivery Term" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Term (Days)" class="control-label col-lg-2">Term (Days) </label>
                <div class="col-lg-10">
                  <input type="text" name="term" id="term" placeholder="Term (Days)" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group" style="display: none">
                <label for="Discount" class="control-label col-lg-2">Discount </label>
                <div class="col-lg-10">
                  <input type="text" name="discount" id="discount" placeholder="Discount" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
          <div class="form-group">
              <label for="Delivery Date" class="control-label col-lg-2">Delivery Date </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl2">
                    <input type="text" class="form-control" name="delivery_date" required=""   />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div> 
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="Shipping Address" class="control-label col-lg-2">Shipping Address </label>
                <div class="col-lg-10">
                  <textarea  id="shipping_address" name="shipping_address" placeholder="Shipping Address" class="form-control"></textarea> 
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                <label for="Discount" class="control-label col-lg-2">Transport</label>
                <div class="col-lg-10">
                  <input type="text" name="vessel" class="form-control" >
                   From
                    <input type="text" name="dari" class="form-control" placeholder="From" value="<?= infokb()->nama.", ".infokb()->kota ?>, INDONESIA" ><br>
                    to <br>
                    <input type="text" id="ke" name="ke" class="form-control" placeholder="To" >
                </div>

              </div><!-- /.form-group -->
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
                     <th style="width: 400px">Kode Barang</th>
                     <th style="width: 100px">Unit</th>
                    
                     <th>Qty</th>  
                     <th>Harga</th>    
                     <th>Nilai</th>   
                     
                     <th>Material Code</th>
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                   <tr id="baris_1">
                     <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('1')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                     <td><input type="text" id="form_kode_1" placeholder="Kode Barang" onclick="cari_kode('1')" class="form-control" name="kode[]"  >
                      <input type="hidden" name="kode_input[]" id="kode_input_1"> 
                     </td> 
                     <td><input type="text" id="form_unit_1" class="form-control" name="unit[]"  readonly=""></td> 
                    
                     <td><input type="number" id="form_qty_1" class="form-control" name="qty[]" onkeyup="sum_nilai(this.value,1)"  required></td>
                     <td><input type="number" id="form_harga_1" class="form-control" name="harga[]" onkeyup="sum_nilai(this.value,1)"  required></td>
                      <td><input type="number" id="form_nilai_1" class="form-control" name="nilai[]"  readonly=""></td>
                     
                     <td><input type="text" id="form_ket_1" class="form-control" name="ket[]" ></td>
                   </tr>
                 </tbody>
                 <tfoot>
                  <tr>
                    <td colspan="5" style="text-align:right"><b>Total</b></td>
                    <td>
                      <input type="text" id="grand_total" class="form-control" readonly>
                    </td>
                    <td></td>
                  </tr>
                </tfoot>
               </table>
                </div>
               <input type="hidden" id="jml" value="1">
              
              </div> 
              
                      
              <div class="form-group">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>sales-order" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
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

    function get_currency(kode){ 
  //  var kode = $("#KODE_VALUTA").val();
//  $("#rupiah_rate").attr('readonly',true);
  $("#rupiah_rate").val('get data ...');
  $("#rupiah_rate_sale").val('get data ...');
    $.ajax({
       url : "<?= base_url() ?>get_kurs.php",
       type : "POST",
       data : {
         kode : kode,  
         //d_header : $("#ID").val()
       },
      // dataTye : 'JSON',
       success : function(data){ 
          $("#rupiah_rate").val(data);
          $("#rupiah_rate_sale").val(data);
          //$("#rupiah_rate").attr('readonly',false);
        // save_data(data,'NDPBM',$('#ID').val(),'ws_header','id_header');
        // $("#kantor_pabean_pengawas").val(data);
       }
    });
  }


 function hapus_baris(id){ 

    $("#baris_"+id).remove();

  }
 

   function set_panel_pajak(){
   if($('#radio1').is(':checked')) { 
     
         $("#baris_pajak").show();
      
       
      
   
  }else{
      $("#baris_pajak").hide();
  }
 }

   function set_panel_pajak2(){
   if($('#radio2').is(':checked')) { 
     
         $("#baris_pajak").hide();
      
       
      
   
  }else{
      $("#baris_pajak").show();
  }
 }

 function get_pemasok(val){
   $.ajax({
          url: "<?= base_url() ?>modul/sales_order/sales_order_action.php?act=get_pemasok",
          data: { kode_penerima: val },
          type : 'POST',
          success: function (data) {
            $("#shipping_address").val(data);
           // $("#satuan").val(data.satuan);
          } 
       });

   
 }

 



   function pilih_pr(no_pr){
       $.ajax({
          url: "<?= base_url() ?>modul/sales_order/sales_order_action.php?act=get_pr",
          data: { no_pr: no_pr },
          type : 'POST',
          success: function (data) {
            $("#form_pr").html(data);
           // $("#satuan").val(data.satuan);
          } 
       });

 

       $.ajax({
          url: "<?= base_url() ?>modul/sales_order/sales_order_action.php?act=get_pr2",
          data: { no_pr: no_pr },
          type : 'POST',
          dataType : 'JSON',
          success: function (data) {
           // $('.chzn-select').select2(); 
             $('#kode_penerima').val(data.kode_penerima).trigger('chosen:updated');
              $('#currency').val(data.currency).trigger('chosen:updated');
             $('#term').val(data.term);
             $('#sales_id').val(data.sales_id);
             $('#rupiah_rate').val(data.rupiah_rate);
              $('#rupiah_rate_sale').val(data.rupiah_rate_sale);
             $('#discount').val(data.sales_id);
             if (data.tax=='include') {
               $("#radio1").prop('checked', true);
               $("#radio2").prop('checked', false);
             }else{
               $("#radio2").prop('checked', true);
               $("#radio1").prop('checked', false);
             }
              $.ajax({
              url: "<?= base_url() ?>modul/sales_order/sales_order_action.php?act=get_pemasok",
              data: { kode_penerima: data.kode_penerima },
              type : 'POST',
              success: function (data) {
                $("#shipping_address").val(data);
               // $("#satuan").val(data.satuan);
              } 
           });
           // $("#satuan").val(data.satuan);
          } 
       });
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
                          });
  }

  function hitung_total() {
  var total = 0;

  $("input[name='nilai[]']").each(function(){
    var val = parseFloat($(this).val()) || 0;
    total += val;
  });

  $("#grand_total").val(total);
}

 function sum_nilai(val, id) {
  var qty   = parseFloat($("#form_qty_" + id).val()) || 0;
  var harga = parseFloat($("#form_harga_" + id).val()) || 0;

  var nilai = qty * harga;

  $("#form_nilai_" + id).val(nilai);

  hitung_total(); // 🔥 tambahkan ini
}



  function add_baris(id) { 
      var id_baris =  parseInt($("#jml").val())+1;
      var baris = '<tr id="baris_'+id_baris+'"><td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris(\''+id_baris+'\')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td><td><input type="text" class="form-control" placeholder="Kode Barang" onclick="cari_kode(\''+id_baris+'\')" id="form_kode_'+id_baris+'" name="kode[]"  > <input type="hidden" id="kode_input_'+id_baris+'" name="kode_input[]" id="kode_input_1"> </td><td><input type="text" class="form-control" id="form_unit_'+id_baris+'" name="unit[]" style="width: 150px" readonly=""></td><td><input type="number" onkeyup="sum_nilai(this.value,'+id_baris+')"  id="form_qty_'+id_baris+'" class="form-control" name="qty[]" required></td><td><input type="number" id="form_harga_'+id_baris+'" class="form-control" name="harga[]" onkeyup="sum_nilai(this.value,'+id_baris+')"  required></td> <td><input type="number" id="form_nilai_'+id_baris+'" class="form-control" name="nilai[]"  readonly=""></td> <td><input type="text" class="form-control" name="ket[]" id="form_ket_'+id_baris+'"></td></tr>';

        $("#isi_tabel").append(baris);
        $("#jml").val(id_baris);
    }

     function cari_kode(id) {   
    
                      $('#form_kode_'+id).autocomplete({
                        source: function (request, response) {
                          $.ajax({
                            url: "<?= base_url() ?>cari_kode.php?act=cari_kode",
                            data: { term: request.term },
                            type : 'POST',
                            dataType: "json", 
                            success: function (data) {
                               if(!data.length){
                                  alert("data tidak ditemukan"); 
                                  $("#form_kode_"+id).val(''); 
                                  // var result = [
                                  //     {
                                  //         kd_barang: 'No matches found', 
                                  //         nm_barang: response.term
                                  //     }
                                  // ];
                                  // response(result);
                              }else{
                                response($.map(data, function (item) {
                                return {
                                  kd_barang: item.kd_barang,
                                  nm_barang: item.nm_barang,
                                  packing_size: item.packing_size
                                };
                              }))

                              }

                              
                            }
                          })
                        },
                        select: function (event, ui) {
                          // if (!ui.content.length) {
                          //    alert("data tidak ditemukan");
                          // }
                             $('#form_kode_'+id).val(ui.item.kd_barang+" - "+ui.item.nm_barang); 
                             $("#kode_input_"+id).val(ui.item.kd_barang);
                            $.ajax({
                              type : 'POST',
                              data : {
                                id:id,
                                kd_barang : ui.item.kd_barang 
                              },
                              url : "<?= base_url() ?>cari_kode.php?act=get_unit",
                              success:function(data){
                                   $("#form_unit_"+id).val(data);
                              }
                            });
                             return false;
                         }
                         // , 
                         // response: function(event, ui) { 
                         //        if (!ui.content.length) {
                         //            var noResult = { value:"",label:"No results found" };
                         //            ui.content.push(noResult);
                         //        }
                         //    }
                                           }).data("ui-autocomplete")._renderItem = function (ul, item) {
                        var inner_html = '<a><div class="list_item_container" style="height:20px">' + item.kd_barang + ' , ' +item.nm_barang+' '+item.packing_size+'</div></a>';
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
    $("#tgl2").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl2 :input").valid();
    });
    $("#tgl2").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl2 :input").valid();
    });
    
    $("#input_sales_order").validate({
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
