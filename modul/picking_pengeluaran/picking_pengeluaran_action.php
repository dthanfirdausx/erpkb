<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "no_sj" => $_POST["no_sj"],
      "penerima" => $_POST["penerima"],
      "no_aju" => $_POST["no_aju"],
      "tgl_aju" => $_POST["tgl_aju"],
      "jenis_dokpab" => $_POST["jenis_dokpab"],
      "no_dokpab" => $_POST["no_dokpab"],
      "tgl_dokpab" => $_POST["tgl_dokpab"],
  );
  
  
  
   
    $in = $db->insert("tmp_pengeluaran2",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("tmp_pengeluaran2","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("tmp_pengeluaran2","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "no_sj" => $_POST["no_sj"],
      "penerima" => $_POST["penerima"],
      "no_aju" => $_POST["no_aju"],
      "tgl_aju" => $_POST["tgl_aju"],
      "jenis_dokpab" => $_POST["jenis_dokpab"],
      "no_dokpab" => $_POST["no_dokpab"],
      "tgl_dokpab" => $_POST["tgl_dokpab"],
   );
   
   
   

    
    
    $up = $db->update("tmp_pengeluaran2",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>