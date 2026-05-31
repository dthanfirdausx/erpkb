<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "kode" => $_POST["kode"],
      "nama" => $_POST["nama"],
      "stock" => $_POST["stock"],
      "satuan" => $_POST["satuan"],
      "kategori" => $_POST["kategori"],
      "posisi" => $_POST["posisi"],
  );
  
  
  
   
    $in = $db->insert("vwip",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("vwip","",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("vwip","",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "kode" => $_POST["kode"],
      "nama" => $_POST["nama"],
      "stock" => $_POST["stock"],
      "satuan" => $_POST["satuan"],
      "kategori" => $_POST["kategori"],
      "posisi" => $_POST["posisi"],
   );
   
   
   

    
    
    $up = $db->update("vwip",$data,"",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>