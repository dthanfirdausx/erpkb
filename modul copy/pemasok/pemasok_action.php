<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "kode_pemasok" => $_POST["kode_pemasok"],
      "npwp" => $_POST["npwp"],
      "nama" => $_POST["nama"],
      "alamat" => $_POST["alamat"],
      "kota" => $_POST["kota"],
      "negara" => $_POST["negara"],
      "notelp" => $_POST["notelp"],
      "nofax" => $_POST["nofax"],
      "email" => $_POST["email"],
  );
  
  
  
   
          if(isset($_POST["status"])=="on")
          {
            $status = array("status"=>"1");
            $data=array_merge($data,$status);
          } else {
            $status = array("status"=>"0");
            $data=array_merge($data,$status);
          }
    $in = $db->insert("pemasok",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("pemasok","kode_pemasok",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("pemasok","kode_pemasok",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "kode_pemasok" => $_POST["kode_pemasok"],
      "npwp" => $_POST["npwp"],
      "nama" => $_POST["nama"],
      "alamat" => $_POST["alamat"],
      "kota" => $_POST["kota"],
      "negara" => $_POST["negara"],
      "notelp" => $_POST["notelp"],
      "nofax" => $_POST["nofax"],
      "email" => $_POST["email"],
   );
   
   
   

    
          if(isset($_POST["status"])=="on")
          {
            $status = array("status"=>"1");
            $data=array_merge($data,$status);
          } else {
            $status = array("status"=>"0");
            $data=array_merge($data,$status);
          }
    
    $up = $db->update("pemasok",$data,"kode_pemasok",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>