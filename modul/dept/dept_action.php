<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "kd_dept" => $_POST["kd_dept"],
      "nm_dept" => $_POST["nm_dept"],
  );
  
  
  
   
    $in = $db->insert("dept",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("dept","kd_dept",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("dept","kd_dept",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "kd_dept" => $_POST["kd_dept"],
      "nm_dept" => $_POST["nm_dept"],
   );
   
   
   

    
    
    $up = $db->update("dept",$data,"kd_dept",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>