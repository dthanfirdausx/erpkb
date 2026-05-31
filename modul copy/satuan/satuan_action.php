<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "kode" => $_POST["kode"],
      "jenis" => $_POST["jenis"],
      "nama" => $_POST["nama"],
  );
  
  
  
   
    $in = $db->insert("satuan",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("satuan","kode",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("satuan","kode",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "kode" => $_POST["kode"],
      "jenis" => $_POST["jenis"],
      "nama" => $_POST["nama"],
   );
   
   
   

    
    
    $up = $db->update("satuan",$data,"kode",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>