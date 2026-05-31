<?php
error_reporting(0); 
session_start();
include "../../inc/config.php";
session_check_json(); 
switch ($_GET["act"]) {

  case "ganti_no_po":
   $t = explode("-", $_POST['tgl']); 

   echo generate_po_no($t[0],$t[1]); 
    break;

  case "cari_vendor":
    $kode = $_POST['kode_pemasok'];
    $vendor = $db->fetch_single_row("pemasok","nama",$kode);

    if ($vendor) {
        echo json_encode([
            "success" => true,
            "data" => array( "kode_pemasok" => $vendor->kode_pemasok,
            "nama"    => $vendor->nama,
            "alamat"  => $vendor->alamat,
            "kota"    => $vendor->kota,
            "negara"  => $vendor->negara,
            "notelp"  => $vendor->notelp,
            "nofax"   => $vendor->nofax,
            "email"   => $vendor->email)
           
        ]);
    } else {
        echo json_encode(["success" => false]);
    }
      break;


  case "in":
     $tg = explode("-", $_POST["po_date"]);

     $no_po = generate_po_no($tg[0],$tg[1]);
        $data = array(
      "purchase_order_no" => $no_po,
      "customer_id"       => $_POST["customer_id"],
      "po_date"              => $_POST["po_date"], 
      "delivery_date"     => $_POST["delivery_date"],
      "arrival_date"      => $_POST["arrival_date"],
      "shipped_via"       => $_POST["shipped_via"],
      "delivery_term"     => $_POST["delivery_term"],
      "payment_term"      => $_POST["payment_term"],
      "catatan"      => $_POST["catatan"],
      "currency"      => $_POST["currency"],
      "seller_code"       => $_POST["customer_id"],
      "seller_name"       => $_POST["seller_code"],
      "seller_address"    => $_POST["seller_address"],
      "seller_phone"      => $_POST["seller_phone"],
      "seller_pic"        => $_POST["seller_pic"],
      "seller_email"      => $_POST["seller_email"],
       "pajak"      => $_POST["tax"],
      "consignee_name"    => $_POST["consignee_name"],
      "consignee_address" => $_POST["consignee_address"],
      "consignee_phone"   => $_POST["consignee_phone"],
      "consignee_email"   => $_POST["consignee_email"],
   );

   // print_r($data);
   // die();

   $db->insert("purchase_order", $data);

    $data2 = array('nopo' => $no_po, 
                  'typepo' => '1',
                  'paymentstatus' => '0',
                  'valuta' => $_POST["currency"],
                  'tglpo' => $_POST["date"] ,
                  'kode_pemasok' => $_POST["customer_id"],
                  'pic' => $_POST["seller_pic"]);
   // $db->insert("po",$data2);
    
  // $po_id = $db->lastInsertId();

   // --- SIMPAN DETAIL ---
   if (!empty($_POST["kode"])) {
      foreach ($_POST["kode"] as $i => $kode) {
         $detail = array(
            "po_no"       => $no_po, 
            "kode_barang" => $_POST["kode"][$i],
            "nama_barang" => $_POST["name"][$i],
            "spec"        => $_POST["spec"][$i],
            "unit"        => $_POST["unit"][$i],
            "qty"         => str_replace(",", ".", str_replace("", "", $_POST["qty"][$i])),
            "harga"       => str_replace(",", ".", str_replace("", "", $_POST["harga"][$i])),
            "ket"         => $_POST["ket"][$i],
         );
        // nopo tglpo kode  jumlah  unit  harga nilai ket 
         $detail2 = array(
              "nopo"       => $no_po, 
              "tglpo"       => $_POST["date"], 
              "kode"       => $_POST["kode"][$i], 
              "jumlah"       => str_replace(",", ".", str_replace("", "", $_POST["qty"][$i])),
              "unit"       => $_POST["unit"][$i],
              "nilai" => str_replace(",", ".", str_replace("", "", $_POST["qty"][$i])) * str_replace(",", ".", str_replace("", "", $_POST["harga"][$i])),
              "harga"       => str_replace(",", ".", str_replace("", "", $_POST["harga"][$i])), 
             // "ket" => 
         );
        //  $db->insert("po_detail", $detail2);
         $db->insert("purchase_order_detail", $detail);
      } 
   }
    action_response($db->getErrorMessage());
   // echo "string";
  break;
  case "delete":
    
    
    
    $db->delete("purchase_order","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal": 
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("purchase_order","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "purchase_order_no" => $_POST["purchase_order_no"],
      "customer_id"       => $_POST["customer_id"],
      "po_date"              => $_POST["po_date"], 
      "delivery_date"     => $_POST["delivery_date"],
      "arrival_date"      => $_POST["arrival_date"],
      "shipped_via"       => $_POST["shipped_via"],
      "delivery_term"     => $_POST["delivery_term"],
      "payment_term"      => $_POST["payment_term"],
      "catatan"      => $_POST["catatan"],
      "currency"      => $_POST["currency"],

      "seller_code"       => $_POST["customer_id"],
      "seller_name"       => $_POST["seller_code"],
      "seller_address"    => $_POST["seller_address"],
      "seller_phone"      => $_POST["seller_phone"],
      "seller_pic"        => $_POST["seller_pic"],
      "seller_email"      => $_POST["seller_email"],
      "pajak"      => $_POST["tax"],

      "consignee_name"    => $_POST["consignee_name"],
      "consignee_address" => $_POST["consignee_address"],
      "consignee_phone"   => $_POST["consignee_phone"],
      "consignee_email"   => $_POST["consignee_email"],
   );


   
    $data2 = array('nopo' => $_POST["purchase_order_no"], 
                  'tglpo' => $_POST["date"] ,
                  'typepo' => '1',
                  'valuta' => $_POST["currency"],
                  'paymentstatus' => '0',
                  'kode_pemasok' => $_POST["customer_id"],
                  'pic' => $_POST["seller_pic"]);
    

    
    
    $up = $db->update("purchase_order",$data,"purchase_order_no",$_POST["purchase_order_no"]);
   
    $db->query("delete from purchase_order_detail where po_no='".$_POST["purchase_order_no"]."'");
    // $db->query("delete from po_detail where nopo='".$_POST["purchase_order_no"]."'");
    // $db->query("delete from po where nopo='".$_POST["purchase_order_no"]."'");
   // $db->insert("po",$data2); 

     if (!empty($_POST["kode"])) {  
      foreach ($_POST["kode"] as $i => $kode) {
         $detail = array(
            "po_no"       => $_POST["purchase_order_no"], 
            "kode_barang" => $_POST["kode"][$i],
            "nama_barang" => $_POST["name"][$i],
            "spec"        => $_POST["spec"][$i],
            "unit"        => $_POST["unit"][$i],
            "qty"         => str_replace(",", ".", str_replace("", "", $_POST["qty"][$i])),
            "harga"       => str_replace(",", ".", str_replace("", "", $_POST["harga"][$i])),
            "ket"         => $_POST["ket"][$i], 
         );
         $detail2 = array(
              "nopo"       => $_POST["purchase_order_no"], 
              "tglpo"       => $_POST["date"], 
              "kode"       => $_POST["kode"][$i], 
              "jumlah"       => str_replace(",", ".", str_replace("", "", $_POST["qty"][$i])),
              "unit"       => $_POST["unit"][$i],
               "nilai" => str_replace(",", ".", str_replace("", "", $_POST["qty"][$i])) * str_replace(",", ".", str_replace("", "", $_POST["harga"][$i])),
              "harga"       => str_replace(",", ".", str_replace("", "", $_POST["harga"][$i])), 
             // "ket" => 
         );
       //   $db->insert("po_detail", $detail2);
         $db->insert("purchase_order_detail", $detail); 
      } 
   }
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>