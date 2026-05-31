<!-- Content Header (Page header) -->
              <section class="content-header">
                  <h1>Sales Invoice</h1>
                    <ol class="breadcrumb">
                        <li>
                        <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a>
                        </li>
                        <li>
                        <a href="<?=base_index();?>sales-invoice">Sales Invoice</a>
                        </li>
                        <li class="active">Edit Sales Invoice</li>
                    </ol>
              </section>

              <!-- Main content -->
              <section class="content">
              <div class="row">
                  <div class="col-lg-12">
                      <div class="box box-solid box-primary">
                          <div class="box-header">
                              <h3 class="box-title">Edit Sales Invoice</h3>
                              <div class="box-tools pull-right">
                                  <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-pencil"></i></button>
                              </div>
                          </div>
                      <div class="box-body"> 
                       <div class="alert alert-danger error_data" style="display:none">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <span class="isi_warning"></span>
                      </div>
                          <form id="edit_sales_invoice" method="post" class="form-horizontal" action="<?=base_admin();?>modul/sales_invoice/sales_invoice_action.php?act=up">
                          <input type="hidden" name="id_sales" value="<?= $data_edit->id_sales ?>">
                             <div class="form-group">
                <label for="PO NO" class="control-label col-lg-2">No Sales Invoice </label>
                <div class="col-lg-10">
                  <input type="text" name="no_sales_invoice" id="no_sales_invoice" value="<?= $data_edit->no_sales_invoice ?>" placeholder="No Sales Invoice" class="form-control"  required="">
                </div>
              </div><!-- /.form-group -->
                            <div class="form-group">
                        <label for="Bill To" class="control-label col-lg-2">Bill To <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <select  id="bill_to" name="bill_to" data-placeholder="Pilih Bill To..." class="form-control chzn-select" tabindex="2" required>
               <option value=""></option>
               <?php foreach ($db->fetch_all("penerima") as $isi) {

                  if ($data_edit->bill_to==$isi->kode_penerima) {
                    echo "<option value='$isi->kode_penerima' selected>$isi->nama</option>";
                  } else {
                  echo "<option value='$isi->kode_penerima'>$isi->nama</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="Ship To" class="control-label col-lg-2">Ship To <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <select  id="ship_to" name="ship_to" data-placeholder="Pilih Ship To..." class="form-control chzn-select" tabindex="2" required>
               <option value=""></option>
               <?php foreach ($db->fetch_all("penerima") as $isi) {

                  if ($data_edit->ship_to==$isi->kode_penerima) {
                    echo "<option value='$isi->kode_penerima' selected>$isi->nama</option>";
                  } else {
                  echo "<option value='$isi->kode_penerima'>$isi->nama</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->

              <div class="form-group">
              <label for="Invoice Date" class="control-label col-lg-2">Invoice Date </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" onchange="get_nomor(this.value)" value="<?=$data_edit->invoice_date;?>" name="invoice_date"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group" style="display: none">
                <label for="Invooice No" class="control-label col-lg-2">Invooice No </label>
                <div class="col-lg-10">
                  <input type="text" name="invoice_no" value="<?=$data_edit->invoice_no;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="PO NO" class="control-label col-lg-2">PO NO </label>
                <div class="col-lg-10">
                  <input type="text" name="nopo" value="<?=$data_edit->nopo;?>" class="form-control" >
                </div>
              </div><!-- /.form-group --> 
              <div class="form-group"> 
                <label for="PO NO" class="control-label col-lg-2">No Sales Order</label>
                <div class="col-lg-10">
                    <input type="text" name="no_sales_order" value="<?= $data_edit->no_sales_order ?>" id="no_sales_order" placeholder="No Sales Order" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Term" class="control-label col-lg-2">Term </label>
                <div class="col-lg-10">
                 <select  id="term" name="term" data-placeholder="Pilih Payment Term ..." class="form-control chzn-select" tabindex="2" > 
                   <option value=""></option>
                   <?php foreach ($db->fetch_all("term_payment") as $isi) {
                      if ($data_edit->term==$isi->jenis_term) {
                       echo "<option value='$isi->jenis_term' selected>$isi->jenis_term</option>";
                      }else{
                        echo "<option value='$isi->jenis_term'>$isi->jenis_term</option>";
                      }
                      
                   } 
                   ?>
                  </select>
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Currency" class="control-label col-lg-2">Currency </label>
                        <div class="col-lg-10">
              <select  id="valuta" name="valuta" data-placeholder="Pilih Currency..." class="form-control chzn-select" tabindex="2" >
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
              <label for="Ship Date" class="control-label col-lg-2">Ship Date </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl2">
                    <input type="text" class="form-control" value="<?=$data_edit->ship_date;?>" name="ship_date"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="DO No" class="control-label col-lg-2">DO No</label>
                <div class="col-lg-10">
                   <select  id="no_do"  name="no_do" data-placeholder="Pilih No DO ..." class="form-control chzn-select" tabindex="2">
                       option value=""></option>
                       <?php foreach ($db->fetch_all("surat_jalan") as $isi) {
                        if ($data_edit->no_do) {
                          echo "<option value='$isi->no_surat_jalan' selected>$isi->no_surat_jalan</option>";
                        }
                            echo "<option value='$isi->no_surat_jalan'>$isi->no_surat_jalan</option>";
                        } 
                        ?>

                      </select>
                  
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Bank Detail" class="control-label col-lg-2">Bank Detail </label>
                <div class="col-lg-10">
                   <textarea id="editbox" name="bank_detail" class="editbox"><?= infokb()->bank ?></textarea>
                </div>
              </div><!-- /.form-group -->
              
                <div class="form-group">
                  <label for="Tax" class="control-label col-lg-2">Tax </label>
                      <div class="col-lg-10">
                        
                <div class="radio radio-success radio-inline">
                  <input type="radio" name="tax"  id="radio1" value="1" <?=($data_edit->tax=="1")?"checked":"";?> >
                    <label for="radio1" style="padding-left: 5px;">
                      Yes
                    </label>
                </div>
                
                <div class="radio radio-success radio-inline">
                  <input type="radio" name="tax"  id="radio2" value="0" <?=($data_edit->tax=="0")?"checked":"";?> >
                    <label for="radio2" style="padding-left: 5px;">
                      No
                    </label>
                </div>
                
                      </div>
                </div><!-- /.form-group -->
                  <div class="form-group">
                <label for="DO No" class="control-label col-lg-2">Signed By </label>
                <div class="col-lg-10">
                  <input type="text" name="ttd" placeholder="Signed By" value="<?= $data_edit->ttd ?>" class="form-control" required="" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                <label for="DO No" class="control-label col-lg-2">Catatan </label>
                <div class="col-lg-10">
                  <textarea class="form-control" name="catatan" placeholder="catatan"><?= $data_edit->catatan ?></textarea>
                </div> 
              </div><!-- /.form-group -->
              <div class="form-group" id="detail_pemasukan">              
               <div class="col-lg-12">
                <table class="table" style="font-size: 12px">
                <thead>
                  <tr>
                    <th style="width:50px;text-align: center">
                      <a style="cursor: pointer;" onclick="add_baris()"><i class="fa fa-plus"></i></a>
                    </th>
                    <th style="width: 300px">Kode Barang</th>
                      <th style="width: 70px">Unit</th>
                      <th>Qty</th>
                      <th>Price</th>
                        <th>Amount</th>
                      <th>Material Number</th>
                      <th>Material Description</th>
                  </tr>
                </thead>

                 <tbody id="isi_tabel">
                  <?php
                  $no=1;
                  $total_qty = 0;
                  $total_nilai = 0;
                  $total_tax = 0;

                  $qq = $db->query("select * from sales_invoice_detail where id_sales='$data_edit->id_sales' ");

                  foreach ($qq as $kk) {

                  $nilai = $kk->nilai;
                  $tax = ($data_edit->tax == '1') ? ($nilai * 0.11) : 0;

                  $total_qty += $kk->qty;
                  $total_nilai += $nilai;
                  $total_tax += $tax;
                  ?>
                  <tr id="baris_<?= $no ?>">

                  <td style="text-align:center">
                  <a onclick="hapus_baris('<?= $no ?>')">
                  <i class="fa fa-trash"></i>
                  </a>
                  </td>

                  <td>
                  <input type="text" id="form_kode_<?= $no ?>" value="<?= $kk->kd_barang.' '.$kk->nm_barang ?>" onclick="cari_kode('<?= $no ?>')" name="kode[]" class="form-control">
                  <input type="hidden" name="kode_input[]" value="<?= $kk->kd_barang ?>" id="kode_input_<?= $no ?>">
                  </td>

                  <td><input type="text" id="form_unit_<?= $no ?>" name="unit[]" value="<?= $kk->unit ?>" class="form-control" ></td> 

                  <td>
                  <input type="text" value="<?= formatAngka($kk->qty) ?>"
                  onkeyup="sum_nilai('<?= $no ?>')"
                  id="form_qty_<?= $no ?>"
                  class="form-control text-right"
                  name="jumlah[]">
                  </td>

                  <td>
                  <input type="text" value="<?= formatAngka($kk->harga) ?>"
                  onkeyup="sum_nilai('<?= $no ?>')"
                  id="form_harga_<?= $no ?>"
                  class="form-control text-right"
                  name="harga[]">
                  </td>

                  <td>
                  <input type="text" value="<?= formatAngka($nilai) ?>"
                  id="form_nilai_<?= $no ?>"
                  class="form-control text-right"
                  name="nilai[]" readonly>
                  </td>

                  <td>
    <input type="text"
           id="form_material_1"
           class="form-control"
            value="<?= $kk->material_number ?>" 
           name="material_number[]">
  </td>

  <!-- Material Description -->
  <td>
    <input type="text"
           id="form_material_desc_1"
           class="form-control"
           value="<?= $kk->material_description ?>" 
           name="material_description[]">
  </td>

                 

                  </tr>
                  <?php $no++; } ?>
                  </tbody>
                <tfoot>
<tr style="background:#f2f2f2;font-weight:bold">

<td colspan="3" style="text-align:right">TOTAL</td>

<td>
<input type="text" id="form_total_qty"
value="<?= formatAngka($total_qty) ?>"
readonly class="form-control text-right">
</td>

<td></td>

<td>
<input type="text" id="form_total_nilai"
value="<?= formatAngka($total_nilai) ?>"
readonly class="form-control text-right">
</td>



</tr>
</tfoot>
                 </table>
               </div>
               <input type="hidden" id="jml" value="1">
           <!--     <input type="text" id="total_qty" value="0">
               <input type="text" id="total_harga" value="0">
               <input type="text" id="total_nilai" value="0"> -->

              </div><!-- /.form-group -->
                
                            <input type="hidden" name="id" value="<?=$data_edit->id_sales;?>">
                            <div class="form-group">
                                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                                <div class="col-lg-10">
                                <a href="<?=base_index();?>sales-invoice" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
                                </div>
                            </div><!-- /.form-group -->
                          </form>
                      </div>
                  </div>
              </div>
              </section><!-- /.content -->

<script type="text/javascript">

    function get_nomor(tgl){
       $.ajax({
          url: "<?= base_url() ?>get_nomor.php",
          data: { 
            jenis: 'INV' ,
            tgl : tgl
        },
          type : 'POST',
          dataType : 'JSON',
          success: function (data) {
           $("#no_sales_invoice").val(data.nomor);
           // $("#satuan").val(data.satuan);
          } 
       });
   }

   function formatRibuan(value) { 
    if (value === null || value === undefined) return '';
    const s = String(value).replace(/[^0-9\-]/g, ''); // hanya angka dan minus
    const isNegative = s.startsWith('-');
    const num = isNegative ? s.slice(1) : s;
    const formatted = num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return isNegative ? '-' + formatted : formatted;
  }


 
 function hitung_total(){

  let total_qty = 0;
  let total_nilai = 0;
  let total_tax = 0;

  $("input[name='jumlah[]']").each(function(){
    total_qty += parseFloat($(this).val().replace(/\./g,'')) || 0;
  });

  $("input[name='nilai[]']").each(function(){
    total_nilai += parseFloat($(this).val().replace(/\./g,'')) || 0;
  });

  $("input[name='tax_detail[]']").each(function(){
    total_tax += parseFloat($(this).val().replace(/\./g,'')) || 0;
  });

  $("#form_total_qty").val(formatRibuan(total_qty.toFixed(0)));
  $("#form_total_nilai").val(formatRibuan(total_nilai.toFixed(0)));
  $("#form_total_tax").val(formatRibuan(total_tax.toFixed(0)));
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

    function pilih_so(no_po){
       $.ajax({
          url: "<?= base_url() ?>modul/sales_invoice/sales_invoice_action.php?act=get_so",
          data: { no_po: no_po },
          type : 'POST',
          success: function (data) {
            $("#detail_pemasukan").html(data);
           // $("#satuan").val(data.satuan);
          } 
       });
        $.ajax({
          url: "<?= base_url() ?>modul/pemasukan_hamparan/pemasukan_hamparan_action.php?act=get_pemasok",
          data: { no_po: no_po },
          type : 'POST',
          dataTye : 'JSON',
          success: function (data) { 
            $("#pemasok").val(data.id_customer).trigger('chosen:updated');
            $('#pemasok').val(data.id_customer).trigger('change');
            $("#valuta").val(data.valuta).trigger('chosen:updated');
            $('#valuta').val(data.valuta).trigger('change');
           // $("#satuan").val(data.satuan);
          } 
       });
    }
    
    function hapus_baris(id) {

      $("#baris_"+id).remove();
      // var id_baris =  parseInt($("#jml").val())-1;
      //  $("#jml").val(id_baris);
    }
function add_baris() {
  var id_baris = parseInt($("#jml").val()) + 1;

  var baris = '<tr id="baris_'+id_baris+'">'+
'<td style="text-align: center">'+
'<a style="cursor: pointer;" onclick="hapus_baris(\''+id_baris+'\')" >'+
'<i class="fa fa-trash-o" style="font-size: 25px;"></i></a></td>'+

'<td><input type="text" class="form-control" placeholder="Kode Barang" onclick="cari_kode(\''+id_baris+'\')" id="form_kode_'+id_baris+'" name="kode[]"  >'+
'<input type="hidden" id="kode_input_'+id_baris+'" name="kode_input[]"></td>'+

'<td><input type="text" class="form-control" id="form_unit_'+id_baris+'" name="unit[]" readonly=""></td>'+

'<td><input type="number" onkeyup="sum_nilai(this.value,\''+id_baris+'\')" id="form_qty_'+id_baris+'" class="form-control" name="jumlah[]"  style="text-align:right;" ></td>'+

'<td><input type="number" onkeyup="sum_nilai(this.value,\''+id_baris+'\')" id="form_harga_'+id_baris+'" class="form-control" name="harga[]" style="text-align:right;" ></td>'+


'<td><input type="text" class="form-control" name="nilai[]" id="form_nilai_'+id_baris+'"  style="text-align:right;" readonly=""></td>'+

'<td><input type="text" id="form_material_'+id_baris+'" class="form-control" name="material_number[]"></td>'+

'<td><input type="text" id="form_material_desc_'+id_baris+'" class="form-control" name="material_description[]"></td>'+

'</tr>';


  $("#isi_tabel").append(baris);
  $("#jml").val(id_baris);
}


    function sum_nilai(no){

  let qty   = $("#form_qty_"+no).val().replace(/\./g,'') || 0;
  let harga = $("#form_harga_"+no).val().replace(/\./g,'') || 0;

  qty   = parseFloat(qty) || 0;
  harga = parseFloat(harga) || 0;

  let nilai = qty * harga;

  $("#form_nilai_"+no).val(formatRibuan(nilai.toFixed(0)));

  let taxHeader = $('input[name="tax"]:checked').val();
  let tax = (taxHeader == '1') ? nilai * 0.11 : 0;

  $("#form_tax_"+no).val(formatRibuan(tax.toFixed(0)));

  hitung_total();
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
    
      //trigger validation onchange
      $('select').on('change', function() {
          $(this).valid();
      });
      //hidden validate because we use chosen select
      $.validator.setDefaults({ ignore: ":hidden:not(select)" });
      
    $("#edit_sales_invoice").validate({
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
            
          bill_to: {
          required: true,
          //minlength: 2
          },
        
          ship_to: {
          required: true,
          //minlength: 2
          },
        
        },
         messages: {
            
          bill_to: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          ship_to: {
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
