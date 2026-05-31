<?php
session_start();

include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {

  case "get_pemasok":
    $kode_penerima = $_POST['kode_penerima'];
    $q = $db->query("select   alamat from penerima where kode_penerima='$kode_penerima' ");
    foreach ($q as $k) {
       echo "$k->alamat";
    }
    break;
  case 'get_pr2':
  $no_pr = $_POST['no_pr'];
  $q = $db->query("select no_sales_quotation,  kode_penerima, tgl, currency, rupiah_rate, rupiah_rate_sale,  tax, sales_id , user , term , valid_date  from sales_quotation where id_quotation='$no_pr' ");
  $res = array();
  foreach ($q as $k) {
     $res['no_sales_quotation'] = $k->no_sales_quotation;
     $res['kode_penerima'] = $k->kode_penerima;
     $res['tgl'] = $k->tgl;
     $res['currency'] = $k->currency;
     $res['rupiah_rate'] = $k->rupiah_rate;
     $res['rupiah_rate_sale'] = $k->rupiah_rate_sale;
     $res['tax'] = $k->tax;
     $res['sales_id'] = $k->sales_id;
     $res['user'] = $k->user;
     $res['term'] = $k->term;
     $res['valid_date'] = $k->valid_date;
  } 
  echo json_encode($res);
    break;

  case "get_pr":
   $no_pr = $_POST['no_pr'];
   $data_edit = $db->fetch_single_row("sales_quotation","id_quotation",$no_pr);
   $pajak = array();
   if ($data_edit!='') {
       $pajak = json_decode($data_edit->tax_item);
  }
    //echo count($pajak);
  ?>
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
                     <th>Ket</th>
                   </tr>
                 </thead>
                 <tbody id="isi_tabel">
                 <?php
                 $no=1;
                   $total_qty =0;
                 $total_harga = 0;
                 $total_nilai = 0;
                 $tot_nilai=0;
                 $qd = $db->query("select d.*,b.nm_barang,b.satuan from sales_quotation_detail d join barang b on b.kd_barang=d.kd_barang where id_quotation='$data_edit->id_quotation' ");
                 foreach ($qd as $kd) {
                   ?>
                   <tr id="baris_<?= $no ?>">
                     <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('<?= $no ?>')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                     <td><input type="text" id="form_kode_<?= $no ?>" value="<?= $kd->kd_barang ?>" placeholder="Kode Barang" onclick="cari_kode('<?= $no ?>')" class="form-control" name="kode[]"  >
                      <input type="hidden" name="kode_input[]" value="<?= $kd->kd_barang ?>"  id="kode_input_<?= $no ?>"> 
                     </td> 
                     <td><input type="text" id="form_unit_<?= $no ?>" value="<?= $kd->satuan ?>"  class="form-control" name="unit[]"  readonly=""></td> 
                    
                     <td><input type="text" id="form_qty_<?= $no ?>" value="<?= $kd->qty ?>"  class="form-control" name="qty[]" onkeyup="sum_nilai(this.value,'<?= $no ?>')" style="text-align: right;" required></td>
                     <td><input type="text" id="form_harga_<?= $no ?>" style="text-align: right;" value="<?= $kd->price ?>"  class="form-control" name="harga[]" onkeyup="sum_nilai(this.value,'<?= $no ?>')"  required></td>
                     <td><input type="text" id="form_nilai_<?= $no ?>" style="text-align: right;" value="<?= $kd->nilai ?>"  class="form-control" name="nilai[]"  readonly=""></td>
                     <td><input type="text" id="form_ket_<?= $no ?>" value="<?= $kd->ket ?>"  class="form-control" name="ket[]" ></td>
                   </tr>
                   <?php
                   $no++;
                   $total_qty = $total_qty + $kd->qty;
                   $total_harga = $total_harga + $kd->price; 
                   $tot_nilai = $tot_nilai + ($kd->price * $kd->qty);
                 }
                 ?>
                   
                 </tbody>
                  <tfoot>
                   <tr>
                   <td colspan="3" style="text-align: center;">Total</td>
                   <td><input type="text" id="total_qty" value="<?= $total_qty ?>"  class="form-control" readonly="" style="text-align: right;"></td>
                   <td><input type="text" id="total_harga" value="<?= $total_harga ?>"  class="form-control" readonly="" style="text-align: right;"></td>
                   <td><input type="text" id="nilai_total" value="<?= $tot_nilai ?>"  class="form-control" readonly="" style="text-align: right;"></td>
                 </tr>
                    <tr id="baris_pajak"> 
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                   <td>
                      <?php
                      $j=1;
                  foreach ($db->query("select id_pajak, jenis_pajak,jumlah from pajak") as $p) {
                     if (count($pajak)>0) {
                        for ($i=0; $i <count($pajak) ; $i++) { 
                         if ($pajak[$i]==$p->jenis_pajak) {
                           echo "<label style='margin-top:5px'><input id='pajak_".$j."' onchange='set_pajak(\"$j\",$p->jumlah)' type='checkbox' name='pajak[]'  value='$p->jenis_pajak' checked> $p->jenis_pajak [$p->jumlah%]</label><br>"; 
                         }else{
                          echo "<label style='margin-top:5px'><input id='pajak_".$j."' onchange='set_pajak(\"$j\",$p->jumlah)' type='checkbox' name='pajak[]'  value='$p->jenis_pajak'> $p->jenis_pajak [$p->jumlah%]</label><br>";
                         }
                        }
                     }else{
                       echo "<label style='margin-top:5px'><input id='pajak_".$j."' onchange='set_pajak(\"$j\",$p->jumlah)' type='checkbox' name='pajak[]'  value='$p->jenis_pajak'> $p->jenis_pajak [$p->jumlah%]</label><br>";
                     }
                     $j++;
                  }
                  ?>
                   </td>

                    <td style="text-align: right;">
                      <?php
                      $total_pajak = 0;
                      $j=1;
                  foreach ($db->query("select id_pajak, jenis_pajak,jumlah from pajak") as $p) {
                    
                      if (count($pajak)>0) {
                        for ($i=0; $i <count($pajak) ; $i++) { 
                         if ($pajak[$i]==$p->jenis_pajak) {
                           echo "<input id='nilai_".$j."' type='text' class='form-control' value='".(($p->jumlah/100)*$tot_nilai)."' readonly style='text-align: right;'>  ";
                           $total_pajak = $total_pajak + (($p->jumlah/100)*$tot_nilai);
                         }else{
                           echo "<input id='nilai_".$j."' type='text' style='text-align: right;' class='form-control' readonly>  ";
                         }
                         echo "<input type='hidden' id='nilai_pajak_$j' value='$p->jumlah' >";
                        }
                     }else{
                       echo "<input id='nilai_".$j."' type='text' style='text-align: right;' class='form-control' readonly>  ";
                     }
                     $j++;
                  } 
                  ?>
                   </td>

                   
                 </tr>
                  <tr>
                   <td colspan="5" style="text-align: center;">Grand Total</td>
                   <td>
                     <input type="hidden" id="tmp_total" value="<?= ($total_pajak+$tot_nilai) ?>"> 
                     <input type="hidden" id="tmp_total_pajak" value="<?= $total_pajak ?>"> 
                     <input type="text" id="grand_total" class="form-control" style="text-align: right;" value="<?= ($total_pajak+$tot_nilai) ?>" readonly=""> 
                   </td>

                 </tr>
                 </tfoot> 
               </table>
                </div>
                <input type="hidden" id="jml" value="<?= ($no-1) ?>">
               <input type="hidden" id="jml2" value="<?= ($j) ?>">

              <script type="text/javascript">

                function set_pajak(jenis,jumlah){
                  var total_tmp = 0;
                   var nilai_total = parseFloat($("#tmp_total").val());
                  if ($("#tmp_total").val()=='') {
                      var nilai_total = parseFloat($("#nilai_total").val());
                  }
                 
                  if ($('#pajak_' + jenis).is(":checked")) {
                    var nilai_sum = parseFloat($("#nilai_total").val());
                    var nilai = (jumlah/100) * nilai_sum;
                    nilai_total = nilai_total + nilai;

                   // alert(nilai);
                     $("#nilai_"+jenis).val(nilai.toFixed(3));
                  }else{
                    var nilai_sum = parseFloat($("#nilai_total").val());
                    var nilai = (jumlah/100) * nilai_sum; 
                     nilai_total = nilai_total - nilai; 
                    $("#nilai_"+jenis).val("");
                  }
                  $("#tmp_total").val(nilai_total.toFixed(0));
                  $("#grand_total").val(nilai_total.toFixed(3));
                }

                 function sum_nilai(val,id){
                  var jml = parseFloat($("#jml").val());
                  var jml2 = parseFloat($("#jml2").val());
                  var total = 0;
                  var total_qty = 0;
                  var total_harga = 0;
                  var grand_total = 0;
                  for (var i = 1; i <= jml; i++) {
                     total = total + (parseFloat($("#form_qty_"+i).val()) * parseFloat($("#form_harga_"+i).val()));
                     total_qty = total_qty + parseFloat($("#form_qty_"+i).val()) ;
                     total_harga = total_harga + parseFloat($("#form_harga_"+i).val()) ;

                  }
                  var total_pajak = 0;
                  for (var i = 1; i <= jml2; i++) {
                    if ($('#pajak_'+i).is(":checked")) { 
                      tmp_pajak = parseFloat(($("#nilai_pajak_"+i).val()/100)*total);
                      $("#nilai_"+i).val(tmp_pajak.toFixed(3));
                      total_pajak = total_pajak + parseFloat(($("#nilai_pajak_"+i).val()/100)*total);
                    }
                  }
                  $("#tmp_total_pajak").val(total_pajak); 
                  grand_total = total+total_pajak;
                  $("#nilai_total").val(total); 
                  $("#tmp_total").val(); 
                  $("#total_qty").val(total_qty);
                  $("#grand_total").val(grand_total.toFixed(3));
                  $("#total_harga").val(total_harga);
                  var qty = parseFloat($("#form_qty_"+id).val());
                  var harga = parseFloat($("#form_harga_"+id).val());
                  nilai = qty * harga;
                  $("#form_nilai_"+id).val(nilai); 
                  
                } 
                   
              </script>
  <?php
 
    break;
  case "in":
    
  
  
  
  $data = array(
      "no_sales_order" => get_nomor_transaksi("so"),
      "no_sales_invoice" => get_no_si(),
      "id_quotation" => $_POST["id_quotation"],
      "so_date" => $_POST["so_date"],
      "consignee" => $_POST["consignee"], 
      "currency" => $_POST["currency"],
      "rupiah_rate" => $_POST["rupiah_rate"],
      "delivery_term" => $_POST["delivery_term"],
        "vessel" => $_POST["vessel"],
       "dari" => $_POST["dari"],
        "notify_party" => $_POST["notify_party"],
        "other_reference" => $_POST["other_reference"],
      "ke" => $_POST["ke"],
      "no_store" => $_POST["no_store"],
       "no_po" => $_POST["no_po"],
      "rupiah_rate_sale" => $_POST["rupiah_rate_sale"],
      "kode_penerima" => $_POST["kode_penerima"],
      "tax" => $_POST["tax"],
     // "tax_item" =>
      "status" => "Waiting for Approve",
      "sales_id" => $_POST["sales_id"],
      "purchase_ref" => $_POST["purchase_ref"],
      "user" => $_POST["user"],
      "term" => $_POST["term"],
      "discount" => $_POST["discount"],
      "delivery_date" => $_POST["delivery_date"],
      "shipping_address" => $_POST["shipping_address"],
  );
  
  if (isset($_POST['pajak'])) {
    $data['tax_item'] =  json_encode($_POST["pajak"]);
  }
  
  
   
    $in = $db->insert("sales_order",$data);
    $id_sales_order = $db->last_insert_id();
     foreach ($_POST['kode_input'] as $key => $value) {
         $data_detail = array('id_sales_order' => $id_sales_order, 
                             // 'tglpo' => $_POST["tglpo"],
                              'kd_barang' => $_POST["kode_input"][$key],
                              'qty' => $_POST["qty"][$key],
                              'nilai' => $_POST["qty"][$key] *  $_POST["harga"][$key],
                              'price' => $_POST["harga"][$key],
                              'ket' => $_POST["ket"][$key],
                            );
       ///  print_r($data_detail);
         $db->insert("sales_order_detail",$data_detail);
      }
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("sales_order","id_sales_order",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("sales_order","id_sales_order",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "id_quotation" => $_POST["id_quotation"],
      "so_date" => $_POST["so_date"],
      "no_po" => $_POST["no_po"],
      "currency" => $_POST["currency"],
      "consignee" => $_POST["consignee"],
       "vessel" => $_POST["vessel"], 
       "delivery_term" => $_POST["delivery_term"],
       "dari" => $_POST["dari"],
      "ke" => $_POST["ke"],
       "notify_party" => $_POST["notify_party"],
        "other_reference" => $_POST["other_reference"],
      "rupiah_rate" => $_POST["rupiah_rate"],
      "rupiah_rate_sale" => $_POST["rupiah_rate_sale"],
      "kode_penerima" => $_POST["kode_penerima"],
      "tax" => $_POST["tax"],
      "no_store" => $_POST["no_store"],
   //    "tax_item" => json_encode($_POST["pajak"]),
       "status" => "Waiting for Approve",
      "sales_id" => $_POST["sales_id"],
      "purchase_ref" => $_POST["purchase_ref"],
      "user" => $_POST["user"],
      "term" => $_POST["term"],
      "discount" => $_POST["discount"],
      "delivery_date" => $_POST["delivery_date"],
      "shipping_address" => $_POST["shipping_address"],
   );
   // if ($_POST['"id_quotation']=="") {
   //   unset($data['id_quotation']);
   // }
    $up = $db->update("sales_order",$data,"id_sales_order",$_POST["id"]);
   $db->query("delete from sales_order_detail where id_sales_order='".$_POST["id"]."' "); 
   foreach ($_POST['kode_input'] as $key => $value) {
         $data_detail = array('id_sales_order' => $_POST["id"],  
                             // 'tglpo' => $_POST["tglpo"],
                              'kd_barang' => $_POST["kode_input"][$key],
                              'qty' => $_POST["qty"][$key],
                              'nilai' => $_POST["qty"][$key] *  $_POST["harga"][$key],
                              'price' => $_POST["harga"][$key],
                              'ket' => $_POST["ket"][$key],
                            );
       ///  print_r($data_detail);
         $db->insert("sales_order_detail",$data_detail);
      }
   
   
   

    
    
   
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>