<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "kode" => $_POST["kode"],
      "nama" => $_POST["nama"],
      "alamat" => $_POST["alamat"],
      "prop" => $_POST["prop"],
      "kota" => $_POST["kota"],
      "npwp" => $_POST["npwp"],
      "telp" => $_POST["telp"],
      "fax" => $_POST["fax"],
      "skepkb" => $_POST["skepkb"],
      "tglskep" => $_POST["tglskep"],
  );
  
  
  
   
    $in = $db->insert("infokb",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("infokb","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("infokb","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "kode" => $_POST["kode"],
      "nama" => $_POST["nama"],
      "alamat" => $_POST["alamat"],
      "prop" => $_POST["prop"],
      "kota" => $_POST["kota"],
      "npwp" => $_POST["npwp"],
      "telp" => $_POST["telp"],
      "fax" => $_POST["fax"],
      "skepkb" => $_POST["skepkb"],
      "tglskep" => $_POST["tglskep"],
   );
   
   
   

    
    
    $up = $db->update("infokb",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>