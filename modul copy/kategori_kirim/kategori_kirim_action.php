<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "kd_catatan" => $_POST["kd_catatan"],
      "nm_catatan" => $_POST["nm_catatan"],
  );
  
  
  
   
    $in = $db->insert("catatan",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("catatan","kd_catatan",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("catatan","kd_catatan",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "kd_catatan" => $_POST["kd_catatan"],
      "nm_catatan" => $_POST["nm_catatan"],
   );
   
   
   

    
    
    $up = $db->update("catatan",$data,"kd_catatan",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>