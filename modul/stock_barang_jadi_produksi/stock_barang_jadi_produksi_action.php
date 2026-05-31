<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
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
    
    
    
    $db->delete("vtotalstockprodbj","",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
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