<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "catatan" => $_POST["catatan"],
  );
  
  
  
   
    $in = $db->insert("vpengeluaranbyjenisdokpab",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("vpengeluaranbyjenisdokpab","",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("vpengeluaranbyjenisdokpab","",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "catatan" => $_POST["catatan"],
   );
   
   
   

    
    
    $up = $db->update("vpengeluaranbyjenisdokpab",$data,"",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>