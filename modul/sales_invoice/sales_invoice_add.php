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
<style>
  .si-form-page .box {
    border-radius: 10px;
    border-top: 0;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
  }
  .si-form-hero {
    background: linear-gradient(135deg, #1f4e79 0%, #2f80ed 100%);
    color: #fff;
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 18px;
    box-shadow: 0 10px 24px rgba(31, 78, 121, .22);
  }
  .si-form-hero h3 {
    margin: 0 0 6px;
    font-weight: 700;
  }
  .si-form-hero p {
    margin: 0;
    opacity: .92;
  }
  .si-section-title {
    margin: 18px 0 14px;
    padding: 10px 12px;
    border-left: 4px solid #2f80ed;
    background: #f7fbff;
    color: #1f2d3d;
    font-weight: 700;
    border-radius: 6px;
  }
  .si-billing-card {
    border: 1px solid #e8edf3;
    border-radius: 10px;
    padding: 16px 16px 4px;
    margin-bottom: 15px;
    background: #fff;
  }
  #detail_pemasukan table {
    font-size: 12px;
  }
  #detail_pemasukan th {
    white-space: nowrap;
    background: #f8fafc;
  }
  .si-action-bar {
    position: sticky;
    bottom: 0;
    z-index: 5;
    background: #fff;
    border-top: 1px solid #e8edf3;
    margin: 18px -16px -4px;
    padding: 12px 16px;
    box-shadow: 0 -6px 14px rgba(15, 23, 42, .06);
  }
</style>

<!-- Content Header (Page header) -->
    <section class="content-header">
        <h1><?=sd_h('sales_invoice', 'Sales Invoice');?></h1>
        <ol class="breadcrumb">
            <li>
              <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=sd_h('common_home', 'Home');?></a>
            </li>
            <li>
              <a href="<?=base_index();?>sales-invoice"><?=sd_h('sales_invoice', 'Sales Invoice');?></a>
            </li>
            <li class="active">Add Sales Invoice</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content si-form-page">
    <div class="si-form-hero">
      <h3>Create Billing Document</h3>
      <p>Pilih Surat Jalan dari Billing Due List. Item invoice akan mengikuti dokumen sumber agar nilai billing, pajak, dan jurnal tetap konsisten.</p>
    </div>
    <div class="row">
      <div class="col-lg-12">
        <div class="box">
          <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-file-text-o"></i> Add Sales Invoice</h3>
            <div class="box-tools pull-right">
              <a href="<?=base_index();?>sales-invoice" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> <?php echo $lang["back_button"];?></a>
            </div>
          </div>
          <div class="box-body">
           <div class="alert alert-danger error_data" style="display:none">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <span class="isi_warning"></span>
        </div>
            <form id="input_sales_invoice" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/sales_invoice/sales_invoice_action.php?act=in">
              <div class="si-billing-card">
              <div class="si-section-title"><i class="fa fa-list-alt"></i> Billing Source</div>
              <div class="form-group">
                <label class="control-label col-lg-2">Billing Type</label>
                <div class="col-lg-10">
                  <select name="billing_type" id="billing_type" class="form-control chzn-select" required>
                    <option value="F2" selected>F2 - Customer Invoice</option>
                    <option value="L2">L2 - Debit Memo</option>
                    <option value="G2">G2 - Credit Memo</option>
                  </select>
                </div>
              </div>
                <div class="form-group">
                <label for="PO NO" class="control-label col-lg-2">No Sales Invoice </label>
                <div class="col-lg-10">
                  <input type="text" name="no_sales_invoice" id="no_sales_invoice" placeholder="No Sales Invoice" class="form-control" value="<?= generate_no_sales_infoice(date("Y"),date("m")) ?>"  required="">
                </div>
              </div><!-- /.form-group -->
               <div class="form-group">
                <label for="DO No" class="control-label col-lg-2">DO No </label>
                <div class="col-lg-10">
                  <select  id="no_do" name="no_do" onchange="pilih_so(this.value)" data-placeholder="Pilih No DO ..." class="form-control chzn-select" tabindex="2" required="">
                       <option value=""></option>
                       <?php
                       $eligibleDo = $db->query("
                         SELECT sj.id,sj.no_surat_jalan,sj.posting_date,sj.tgl_surat_jalan,sj.no_sales_order,COALESCE(p.nama,sj.kode_penerima) customer_name
                         FROM surat_jalan sj
                         LEFT JOIN penerima p ON p.kode_penerima=COALESCE(NULLIF(sj.bill_to_party,''),NULLIF(sj.ship_to_party,''),NULLIF(sj.kode_penerima,''))
                         LEFT JOIN sales_invoice si ON si.no_do=sj.no_surat_jalan AND si.billing_status<>'CANCELLED'
                         WHERE sj.status<>'dibatalkan' AND si.id_sales IS NULL
                         ORDER BY COALESCE(sj.posting_date,sj.tgl_surat_jalan) DESC,sj.id DESC
                       ");
                       foreach ($eligibleDo as $isi) {
                         echo "<option value='".(int)$isi->id."'>".$isi->no_surat_jalan." | ".$isi->no_sales_order." | ".$isi->customer_name."</option>";
                       } ?>
                      </select>
                  <p class="help-block">Billing Due List: hanya Surat Jalan yang belum dibilling dan tidak dibatalkan.</p>
                </div>
              </div><!-- /.form-group -->
              </div>

              <div class="si-billing-card">
              <div class="si-section-title"><i class="fa fa-users"></i> Business Partner & Commercial</div>
                      <div class="form-group">
                        <label for="Bill To" class="control-label col-lg-2">Bill To <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
            <select  id="bill_to" name="bill_to" data-placeholder="Pilih Bill To ..." class="form-control chzn-select" tabindex="2" required>
               <option value=""></option>
               <?php foreach ($db->fetch_all("penerima") as $isi) {
                  echo "<option value='$isi->kode_penerima'>$isi->nama</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->
<div class="form-group">
                        <label for="Ship To" class="control-label col-lg-2">Ship To <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
            <select  id="ship_to" name="ship_to" data-placeholder="Pilih Ship To ..." class="form-control chzn-select" tabindex="2" required>
               <option value=""></option>
               <?php foreach ($db->fetch_all("penerima") as $isi) {
                  echo "<option value='$isi->kode_penerima'>$isi->nama</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

          <div class="form-group">
              <label for="Invoice Date" class="control-label col-lg-2">Invoice Date </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" onchange="get_nomor(this.value)" name="invoice_date"  id="invoice_date" autocomplete="off"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->

              <div class="form-group" style="display: none">
                <label for="Invooice No" class="control-label col-lg-2"><?=sd_h('sales_invoice_no', 'Invoice No');?> </label>
                <div class="col-lg-10">
                  <input type="text" name="invoice_no" id="no_invoice" placeholder="<?=sd_h('sales_invoice_no', 'Invoice No');?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->

              <div class="form-group">
                <label for="PO NO" class="control-label col-lg-2">PO NO </label>
                <div class="col-lg-10">
                  <input type="text" name="nopo" id="nopo" placeholder="PO NO" class="form-control" >
                </div>
              </div><!-- /.form-group -->
               <div class="form-group" >
                <label for="PO NO" class="control-label col-lg-2">No Sales Order</label>
                <div class="col-lg-10">
                  <input type="text" name="no_sales_order" id="no_sales_order" placeholder="No Sales Order" class="form-control" >

                </div>
              </div><!-- /.form-group -->

              <div class="form-group">
                <label for="Term" class="control-label col-lg-2">Term </label>
                <div class="col-lg-10">

                  <select  id="term" name="term" data-placeholder="Pilih Payment Term ..." class="form-control chzn-select" tabindex="2" >
                   <option value=""></option>
                   <?php foreach ($db->fetch_all("term_payment") as $isi) {
                      // if ($data_edit->termin==$isi->jenis_term) {
                      //  echo "<option value='$isi->jenis_term' selected>$isi->jenis_term</option>";
                      // }else{
                        echo "<option value='$isi->jenis_term'>$isi->jenis_term</option>";
                     // }

                   }
                   ?>
                  </select>
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Currency" class="control-label col-lg-2"><?=sd_h('sales_currency', 'Currency');?> </label>
                        <div class="col-lg-10">
            <select  id="valuta" name="valuta" data-placeholder="Pilih Currency ..." class="form-control chzn-select" tabindex="2" >
               <option value=""></option>
               <?php foreach ($db->query("select jenis_valas from matauang group by jenis_valas") as $isi) {
                  echo "<option value='$isi->jenis_valas'>$isi->jenis_valas</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->

          <div class="form-group">
              <label for="Ship Date" class="control-label col-lg-2">Ship Date </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl2">
                   <input type="text" class="form-control" name="ship_date" id="ship_date" onchange="get_nomor_ship(this.value)" />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
              </div>

              <div class="si-billing-card">
              <div class="si-section-title"><i class="fa fa-percent"></i> Tax, Bank & Signature</div>


              <div class="form-group">
                <label for="Bank Detail" class="control-label col-lg-2">Bank Detail </label>
                <div class="col-lg-10">


                <textarea id="editbox" name="bank_detail" class="editbox"><?= infokb()->bank ?></textarea>


                </div>
              </div><!-- /.form-group -->

                <div class="form-group">
                  <label for="Tax" class="control-label col-lg-2"><?=sd_h('sales_tax', 'Tax');?> </label>
                  <div class="col-lg-10">

                <div class="radio radio-success radio-inline">
                  <input type="radio" name="tax"  id="radio1" value="1" required="" >
                    <label for="radio1" style="padding-left: 5px;">
                      Yes
                    </label>
                </div>

                <div class="radio radio-success radio-inline">
                  <input type="radio" name="tax"  id="radio2" value="0" required="">
                    <label for="radio2" style="padding-left: 5px;">
                      <?=sd_h('common_no', 'No');?>
                    </label>
                </div>

                  </div>
                </div><!-- /.form-group -->
                 <div class="form-group">
                <label for="DO No" class="control-label col-lg-2">Signed By </label>
                <div class="col-lg-10">
                  <input type="text" name="ttd" placeholder="Signed By" class="form-control" required="" >
                </div>
              </div><!-- /.form-group -->
               <div class="form-group">
                <label for="DO No" class="control-label col-lg-2">Catatan </label>
                <div class="col-lg-10">
                  <textarea class="form-control" name="catatan" placeholder="catatan"></textarea>
                </div>
              </div><!-- /.form-group -->
              </div>

              <div class="si-billing-card">
              <div class="si-section-title"><i class="fa fa-cubes"></i> Billing Items</div>
              <div class="form-group" id="detail_pemasukan">
               <div class="col-lg-12">
                <table class="table">
                 <thead>
                  <tr>
                      <th style="width:50px;text-align: center">
                        <a style="cursor: pointer;" onclick="add_baris()" >
                          <i class="fa fa-plus"></i>
                        </a>
                      </th>

                      <th style="width: 300px">Kode Barang</th>
                      <th style="width: 70px">Unit</th>
                      <th><?=sd_h('sales_qty', 'Qty');?></th>
                      <th><?=sd_h('sales_price', 'Price');?></th>
                        <th><?=sd_h('sales_amount', 'Amount');?></th>
                      <th>Material Number</th>
                      <th>Material Description</th>

                    </tr>
                 </thead>
                 <tbody id="isi_tabel">
                   <tr id="baris_1">

  <td style="text-align: center">
    <a style="cursor: pointer;" onclick="hapus_baris('1')" >
      <i class="fa fa-trash-o" style="font-size: 25px;"></i>
    </a>
  </td>

  <td>
    <input type="text"
           id="form_kode_1"
           placeholder="Kode Barang"
           onclick="cari_kode('1')"
           class="form-control"
           name="kode[]">

    <input type="hidden"
           name="kode_input[]"
           id="kode_input_1">
  </td>

  <td>
    <input type="text"
           id="form_unit_1"
           class="form-control"
           name="unit[]"
          >
  </td>

  <td>
    <input type="number"
           onkeyup="sum_nilai(this.value,'1')"
           id="form_qty_1"
           class="form-control"
           name="jumlah[]"
           style="text-align: right;">
  </td>

  <td>
    <input type="number"
           onkeyup="sum_nilai(this.value,'1')"
           id="form_harga_1"
           class="form-control"
           name="harga[]"
           style="text-align: right;">
  </td>
  <td>
    <input type="text"
           id="form_nilai_1"
           class="form-control"
           name="nilai[]"
           readonly="">
  </td>

  <!-- Material Number -->
  <td>
    <input type="text"
           id="form_material_1"
           class="form-control"
           name="material_number[]">
  </td>

  <!-- Material Description -->
  <td>
    <input type="text"
           id="form_material_desc_1"
           class="form-control"
           name="material_description[]">
  </td>



</tr>
                 </tbody>
                 <tfoot>
                   <tr>
                     <td colspan="3"><?=sd_h('sales_total', 'Total');?></td>

                     <td>
                       <input type="text" id="form_total_qty" readonly="" class="form-control" style="text-align: right;">
                     </td>
                     <td >
                       <input type="text" id="form_total_harga" readonly=""  class="form-control" style="text-align: right;">
                     </td>
                     <td>
                       <input type="text" id="form_total_nilai" readonly=""  class="form-control" style="text-align: right;">
                     </td>
                     <td></td>
                     <td></td>
                   </tr>
                 </tfoot>
                 </table>
               </div>
               <input type="hidden" id="jml" value="1">
           <!--     <input type="text" id="total_qty" value="0">
               <input type="text" id="total_harga" value="0">
               <input type="text" id="total_nilai" value="0"> -->

              </div><!-- /.form-group -->
              </div>

              <div class="form-group si-action-bar">
                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                <div class="col-lg-10">
             <a href="<?=base_index();?>sales-invoice" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                 <button type="submit" id="btn_save_invoice" class="btn btn-primary" disabled><i class="fa fa-save"></i> Post Billing</button>

                </div>
              </div><!-- /.form-group -->

            </form>

          </div>
        </div>
      </div>
    </div>

    </section><!-- /.content -->

<script type="text/javascript">

  function get_nomor_ship(tgl){

   if(tgl == '') return;

   $.ajax({
      url: "<?= base_url() ?>modul/sales_invoice/sales_invoice_action.php?act=get_nomor",
      type: 'POST',
      dataType: 'JSON',
      data: { tgl : tgl },
      success: function (data) {
        $("#no_sales_invoice").val(data.nomor);
      }
   });

}

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

   get_nomor("<?= date("Y-m-d") ?>");
   $("#invoice_date").val("<?= date("Y-m-d") ?>");
   $("#detail_pemasukan").html('<div class="col-lg-12"><div class="alert alert-info"><i class="fa fa-info-circle"></i> Pilih DO/Surat Jalan dari Billing Due List untuk memuat item invoice. Item akan dikunci sesuai dokumen sumber.</div></div>');

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

    function pilih_so(id_sj){

    $.ajax({
      url: "<?= base_url() ?>modul/sales_invoice/sales_invoice_action.php?act=get_so",
      data: { id_sj: id_sj },
      type : 'POST',
      success: function (data) {
        $("#detail_pemasukan").html(data);
        $("#btn_save_invoice").prop("disabled", false);
      }
    });

    $.ajax({
      url: "<?= base_url() ?>modul/sales_invoice/sales_invoice_action.php?act=get_detail_do",
      data: { id_sj: id_sj },
      type : 'POST',
      dataType : 'JSON',
      success: function (data) {

        $("#bill_to").val(data.kode_penerima).trigger('chosen:updated');
        $("#ship_to").val(data.kode_penerima).trigger('chosen:updated');
        $("#valuta").val(data.currency).trigger('chosen:updated');

        if(data.status === 'error'){ $(".isi_warning").text(data.error_message); $(".error_data").fadeIn(); return; }
        $("#nopo").val(data.no_po);
        $("#no_sales_order").val(data.no_sales_order);
        $("#ship_date").val(data.tgl_surat_jalan);
        $("#term").val(data.term).trigger('chosen:updated');
        if (data.tax=='1') {
          $("input[name='tax'][value='1']").prop("checked", true);
        }else{
          $("input[name='tax'][value='0']").prop("checked", true);
        }

      }
    });
}
    function hapus_baris(id) {

      $("#baris_"+id).remove();
      // var id_baris =  parseInt($("#jml").val())-1;
      //  $("#jml").val(id_baris);
    }

    function add_baris(id) {
      var id_baris =  parseInt($("#jml").val())+1;
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

    function sum_nilai(val,id) {
       var a = $("#form_qty_"+id).val();
       var b = $("#form_harga_"+id).val();
       var jml = parseFloat($("#jml").val());
       var i = parseFloat('1');
       // var total_qty   = parseFloat($("#total_qty").val());
       // var total_harga = parseFloat($("#total_harga").val());
       // var total_nilai = parseFloat($("#total_nilai").val());
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
       $("#form_nilai_"+id).val(c.toFixed(2));
       var total =0 ;
       var total_qty = 0;
       var total_harga = 0;
       for (i = 1; i <= jml; i++) {
           total = total + parseFloat($("#form_nilai_"+i).val());
           total_qty = total_qty + parseFloat($("#form_qty_"+i).val());
           if ($("#form_harga_"+i).val()=='') {
             total_harga = total_harga + 0;
           }else{
            total_harga = total_harga + parseFloat($("#form_harga_"+i).val());
           }

       }

       //total_nilai = total_nilai + c;
      // alert(total);
       $("#form_total_nilai").val(total.toFixed(2));
       $("#form_total_qty").val(total_qty.toFixed(4));
       $("#form_total_harga").val(total_harga.toFixed(2));
       //$("#total_nilai").val(total);
      // total_nilai = total_nilai - c;

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

    $("#input_sales_invoice").validate({
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
