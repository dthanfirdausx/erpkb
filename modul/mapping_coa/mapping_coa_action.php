<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "id" => $_POST["id"],
      "transaksi" => $_POST["transaksi"],
      "bc" => $_POST["bc"],
  );
  
  
  
   
    $in = $db->insert("coamap",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("coamap","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("coamap","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "id" => $_POST["id"],
      "transaksi" => $_POST["transaksi"],
      "bc" => $_POST["bc"],
   );
   
   
   

    
    
    $up = $db->update("coamap",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>