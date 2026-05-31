<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "id" => $_POST["id"],
      "no_bpb" => $_POST["no_bpb"],
      "tgl_bpb" => $_POST["tgl_bpb"],
  );
  
  
  
   
    $in = $db->insert("pemasukan",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("pemasukan","no_bpb",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("pemasukan","no_bpb",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "id" => $_POST["id"],
      "no_bpb" => $_POST["no_bpb"],
      "tgl_bpb" => $_POST["tgl_bpb"],
   );
   
   
   

    
    
    $up = $db->update("pemasukan",$data,"no_bpb",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>