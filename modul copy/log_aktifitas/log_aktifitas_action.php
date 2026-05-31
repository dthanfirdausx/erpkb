<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "deskripsi" => $_POST["deskripsi"],
      "user" => $_POST["user"],
      "tgl" => $_POST["tgl"],
  );
  
  
  
   
    $in = $db->insert("log_aktifitas",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("log_aktifitas","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("log_aktifitas","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "deskripsi" => $_POST["deskripsi"],
      "user" => $_POST["user"],
      "tgl" => $_POST["tgl"],
   );
   
   
   

    
    
    $up = $db->update("log_aktifitas",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>