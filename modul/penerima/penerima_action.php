<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "kode_penerima" => $_POST["kode_penerima"],
      "npwp" => $_POST["npwp"],
      "nama" => $_POST["nama"],
      "alamat" => $_POST["alamat"],
      "kota" => $_POST["kota"],
      "negara" => $_POST["negara"],
      "notelp" => $_POST["notelp"],
      "nofax" => $_POST["nofax"],
      "email" => $_POST["email"],
      "status" => $_POST["status"],
      "skep" => $_POST["skep"],
  );
  
  
  
   
    $in = $db->insert("penerima",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("penerima","kode_penerima",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("penerima","kode_penerima",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "kode_penerima" => $_POST["kode_penerima"],
      "npwp" => $_POST["npwp"],
      "nama" => $_POST["nama"],
      "alamat" => $_POST["alamat"],
      "kota" => $_POST["kota"],
      "negara" => $_POST["negara"],
      "notelp" => $_POST["notelp"],
      "nofax" => $_POST["nofax"],
      "email" => $_POST["email"],
      "status" => $_POST["status"],
      "skep" => $_POST["skep"],
   );
   
   
   

    
    
    $up = $db->update("penerima",$data,"kode_penerima",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>