<?php
session_start();
include "../../inc/config.php";
session_check_json();
function sbjp_traceability_locked_response() {
  echo json_encode(array(
    'status' => 'error',
    'error_message' => 'Input/Edit manual Stock Barang Jadi Produksi dikunci. Gunakan GR from Production Order agar setiap barang jadi bisa ditrace sampai bahan baku asal dan dokumen BC.'
  ));
  exit;
}
switch ($_GET["act"]) {
  case "in":
    sbjp_traceability_locked_response();
    
  
  
  
  $data = array(
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => $_POST["nm_barang"],
      "Stock" => $_POST["Stock"],
      "satuan" => $_POST["satuan"],
      "nm_kategori" => $_POST["nm_kategori"],
      "kd_kategori" => $_POST["kd_kategori"],
  );
  
  
  
   
    $in = $db->insert("vtotalstockprodbj",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    sbjp_traceability_locked_response();
    
    
    
    $db->delete("vtotalstockprodbj","",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    sbjp_traceability_locked_response();
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("vtotalstockprodbj","",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    sbjp_traceability_locked_response();
    
   $data = array(
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => $_POST["nm_barang"],
      "Stock" => $_POST["Stock"],
      "satuan" => $_POST["satuan"],
      "nm_kategori" => $_POST["nm_kategori"],
      "kd_kategori" => $_POST["kd_kategori"],
   );
   
   
   

    
    
    $up = $db->update("vtotalstockprodbj",$data,"",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>
