<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => $_POST["nm_barang"],
      "type" => $_POST["type"],
      "spec" => $_POST["spec"],
      "satuan" => $_POST["satuan"],
      "ket" => $_POST["ket"],
      "kd_kategori" => $_POST["kd_kategori"],
  );
  
  
  
   
          if(isset($_POST["status"])=="on")
          {
            $status = array("status"=>"1");
            $data=array_merge($data,$status);
          } else {
            $status = array("status"=>"0");
            $data=array_merge($data,$status);
          }
    $in = $db->insert("barang",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("barang","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("barang","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => $_POST["nm_barang"],
      "type" => $_POST["type"],
      "spec" => $_POST["spec"],
      "satuan" => $_POST["satuan"],
      "ket" => $_POST["ket"],
      "kd_kategori" => $_POST["kd_kategori"],
   );
   
   
   

    
          if(isset($_POST["status"])=="on")
          {
            $status = array("status"=>"1");
            $data=array_merge($data,$status);
          } else {
            $status = array("status"=>"0");
            $data=array_merge($data,$status);
          }
    
    $up = $db->update("barang",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>