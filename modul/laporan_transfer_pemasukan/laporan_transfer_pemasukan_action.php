<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "no_spb" => $_POST["no_spb"],
      "tgl_spb" => $_POST["tgl_spb"],
      "name_ppc" => $_POST["name_ppc"],
      "kode" => $_POST["kode"],
      "nm_barang" => $_POST["nm_barang"],
      "satuan" => $_POST["satuan"],
      "jumlah" => $_POST["jumlah"],
  );
  
  
  
   
    $in = $db->insert("vpemasukantoout",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("vpemasukantoout","",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("vpemasukantoout","",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "no_spb" => $_POST["no_spb"],
      "tgl_spb" => $_POST["tgl_spb"],
      "name_ppc" => $_POST["name_ppc"],
      "kode" => $_POST["kode"],
      "nm_barang" => $_POST["nm_barang"],
      "satuan" => $_POST["satuan"],
      "jumlah" => $_POST["jumlah"],
   );
   
   
   

    
    
    $up = $db->update("vpemasukantoout",$data,"",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>