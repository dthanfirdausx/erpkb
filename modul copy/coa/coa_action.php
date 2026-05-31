<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "no_rek" => $_POST["no_rek"],
      "induk" => $_POST["induk"],
      "level" => $_POST["level"],
      "kat_coa" => $_POST["kat_coa"],
      "jenis" => $_POST["jenis"],
  );
  
  
  
   
    $in = $db->insert("rekening",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("rekening","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("rekening","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "no_rek" => $_POST["no_rek"],
      "induk" => $_POST["induk"],
      "level" => $_POST["level"],
      "kat_coa" => $_POST["kat_coa"],
      "jenis" => $_POST["jenis"],
   );
   
   
   

    
    
    $up = $db->update("rekening",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>