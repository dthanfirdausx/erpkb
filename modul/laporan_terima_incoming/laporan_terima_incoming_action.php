<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "ket" => $_POST["ket"],
      "nomor" => $_POST["nomor"],
      "no_lpb" => $_POST["no_lpb"],
      "tgl_lpb" => $_POST["tgl_lpb"],
      "dari" => $_POST["dari"],
  );
  
  
  
   
    $in = $db->insert("v_incoming_terima_detail",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("v_incoming_terima_detail","",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("v_incoming_terima_detail","",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "ket" => $_POST["ket"],
      "nomor" => $_POST["nomor"],
      "no_lpb" => $_POST["no_lpb"],
      "tgl_lpb" => $_POST["tgl_lpb"],
      "dari" => $_POST["dari"],
   );
   
   
   

    
    
    $up = $db->update("v_incoming_terima_detail",$data,"",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>