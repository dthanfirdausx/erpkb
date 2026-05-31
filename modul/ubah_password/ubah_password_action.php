<?php
session_start();
include "../../inc/config.php";
session_check();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "email" => $_POST["email"],
  );
  
  
  
   
    $in = $db->insert("sys_users",$data);
    
    
    if ($in=true) {
      echo "good";
    } else {
      return false;
    }
    break;
  case "delete":
    
    
    
    $db->delete("sys_users","id",$_GET["id"]);
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("sys_users","id",$id);
         }
    }
    break;
  case "up":
    
   $data = array(
      "email" => $_POST["email"],
   );
   
   
   

    
    
    $up = $db->update("sys_users",$data,"id",$_POST["id"]);
    
    if ($up=true) {
      echo "good";
    } else {
      return false;
    }
    break;
  default:
    # code...
    break;
}

?>