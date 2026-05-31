<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "kd_kategori" => $_POST["kd_kategori"],
      "nm_kategori" => $_POST["nm_kategori"],
  );
  
  
  
   
    $in = $db->insert("kategori",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("kategori","kd_kategori",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("kategori","kd_kategori",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "kd_kategori" => $_POST["kd_kategori"],
      "nm_kategori" => $_POST["nm_kategori"],
   );
   
   
   

    
    
    $up = $db->update("kategori",$data,"kd_kategori",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>