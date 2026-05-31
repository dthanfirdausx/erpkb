<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "kategori" => $_POST["kategori"],
      "kd_sub_kategori" => $_POST["kd_sub_kategori"],
  );
  
  
  
   
    $in = $db->insert("vpemasukanbyjenisdokpab",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("vpemasukanbyjenisdokpab","",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("vpemasukanbyjenisdokpab","",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "kategori" => $_POST["kategori"],
      "kd_sub_kategori" => $_POST["kd_sub_kategori"],
   );
   
   
   

    
    
    $up = $db->update("vpemasukanbyjenisdokpab",$data,"",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>