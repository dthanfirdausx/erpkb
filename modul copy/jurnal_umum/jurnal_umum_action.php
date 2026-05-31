<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
  
  
  
  $data = array(
      "no_jurnal" => $_POST["no_jurnal"],
      "tgl_jurnal" => $_POST["tgl_jurnal"],
      "ket" => $_POST["ket"],
      "no_bukti" => $_POST["no_bukti"],
      "no_rek" => $_POST["no_rek"],
      "debet" => $_POST["debet"],
      "debet_usd" => $_POST["debet_usd"],
      "kredit" => $_POST["kredit"],
      "kredit_usd" => $_POST["kredit_usd"],
      "username" => $_POST["username"],
      "tgl_insert" => $_POST["tgl_insert"],
      "valuta" => $_POST["valuta"],
      "kurs" => $_POST["kurs"],
  );
  
  
  
   
    $in = $db->insert("jurnal_umum",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("jurnal_umum","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("jurnal_umum","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "no_jurnal" => $_POST["no_jurnal"],
      "tgl_jurnal" => $_POST["tgl_jurnal"],
      "ket" => $_POST["ket"],
      "no_bukti" => $_POST["no_bukti"],
      "no_rek" => $_POST["no_rek"],
      "debet" => $_POST["debet"],
      "debet_usd" => $_POST["debet_usd"],
      "kredit" => $_POST["kredit"],
      "kredit_usd" => $_POST["kredit_usd"],
      "username" => $_POST["username"],
      "tgl_insert" => $_POST["tgl_insert"],
      "valuta" => $_POST["valuta"],
      "kurs" => $_POST["kurs"],
   );
   
   
   

    
    
    $up = $db->update("jurnal_umum",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>