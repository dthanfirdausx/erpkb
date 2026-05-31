<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "nomor" => $_POST["nomor"],
      "no_lap" => $_POST["no_lap"],
      "tgl_lap" => $_POST["tgl_lap"],
      "name_ppc" => $_POST["name_ppc"],
      "catatan" => $_POST["catatan"],
  );
  
  
  
   
    $in = $db->insert("bahan",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("bahan","no_lap",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("bahan","no_lap",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "nomor" => $_POST["nomor"],
      "no_lap" => $_POST["no_lap"],
      "tgl_lap" => $_POST["tgl_lap"],
      "name_ppc" => $_POST["name_ppc"],
      "catatan"=>$_POST["catatan"],
   );
   
   
   

    
    
    $up = $db->update("bahan",$data,"no_lap",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>