<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "namaShift" => $_POST["namaShift"],
      "masuk" => $_POST["masuk"],
      "keluar" => $_POST["keluar"],
  );
  
  
  
   
    $in = $db->insert("h_shift",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("h_shift","shiftId",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("h_shift","shiftId",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "namaShift" => $_POST["namaShift"],
      "masuk" => $_POST["masuk"],
      "keluar" => $_POST["keluar"],
   );
   
   
   

    
    
    $up = $db->update("h_shift",$data,"shiftId",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>