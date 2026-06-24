<?php
session_start();
include "../../inc/config.php";
session_check_json();
function sbsjp_traceability_locked_response() {
  echo json_encode(array(
    'status' => 'error',
    'error_message' => 'Input/Edit manual Stock Barang Setengah Jadi Produksi dikunci. Gunakan GR from Production Order agar setiap barang setengah jadi bisa ditrace sampai bahan baku asal dan dokumen BC.'
  ));
  exit;
}
switch ($_GET["act"]) {
  case "in":
    sbsjp_traceability_locked_response();
    
  
  
  
  $data = array(
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => $_POST["nm_barang"],
      "stock" => $_POST["stock"],
      "satuan" => $_POST["satuan"],
      "nm_kategori" => $_POST["nm_kategori"],
      "kd_kategori" => $_POST["kd_kategori"],
  );
  
  
  
   
    $in = $db->insert("vtotalstockprodbsj",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    sbsjp_traceability_locked_response();
    
    
    
    $db->delete("vtotalstockprodbsj","",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    sbsjp_traceability_locked_response();
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("vtotalstockprodbsj","",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    sbsjp_traceability_locked_response();
    
   $data = array(
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => $_POST["nm_barang"],
      "stock" => $_POST["stock"],
      "satuan" => $_POST["satuan"],
      "nm_kategori" => $_POST["nm_kategori"],
      "kd_kategori" => $_POST["kd_kategori"],
   );
   
   
   

    
    
    $up = $db->update("vtotalstockprodbsj",$data,"",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>
