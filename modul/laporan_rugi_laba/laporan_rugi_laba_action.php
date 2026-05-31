<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "no_jurnal" => $_POST["no_jurnal"],
  );
  
  
  
   
    $in = $db->insert("jurnal_umum",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("jurnal_umum","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("jurnal_umum","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "no_jurnal" => $_POST["no_jurnal"],
   );
   
   
   

    
    
    $up = $db->update("jurnal_umum",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>