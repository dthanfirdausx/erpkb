<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "kd_valas" => $_POST["kd_valas"],
      "jenis_valas" => $_POST["jenis_valas"],
      "nama_valas" => $_POST["nama_valas"],
      "negara_valas" => $_POST["negara_valas"],
  );
  
  
  
   
    $in = $db->insert("matauang",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("matauang","kd_valas",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("matauang","kd_valas",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "kd_valas" => $_POST["kd_valas"],
      "jenis_valas" => $_POST["jenis_valas"],
      "nama_valas" => $_POST["nama_valas"],
      "negara_valas" => $_POST["negara_valas"],
   );
   
   
   

    
    
    $up = $db->update("matauang",$data,"kd_valas",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>