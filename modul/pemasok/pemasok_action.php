<?php
session_start();
include "../../inc/config.php";
include "../../inc/master_data_guard.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    mdg_required(array("kode_pemasok"=>erp_t('master_term_kode_pemasok','Vendor Code'),"nama"=>erp_t('master_term_nama_pemasok','Vendor Name')));
    $_POST["kode_pemasok"] = strtoupper(mdg_trim("kode_pemasok"));
    if (mdg_exists($db, "pemasok", "kode_pemasok", $_POST["kode_pemasok"])) {
      mdg_error(erp_t('master_term_kode_pemasok','Vendor Code')." ".$_POST["kode_pemasok"]." ".erp_t('master_already_exists','already exists.'));
    }
  $data = array(
      "kode_pemasok" => $_POST["kode_pemasok"],
      "npwp" => mdg_trim("npwp"),
      "nama" => mdg_trim("nama"),
      "alamat" => mdg_trim("alamat"),
      "kota" => mdg_trim("kota"),
      "negara" => mdg_trim("negara"),
      "notelp" => mdg_trim("notelp"),
      "nofax" => mdg_trim("nofax"),
      "email" => mdg_trim("email"),
  );
  
  
  
   
          if(isset($_POST["status"]) && $_POST["status"]=="on")
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
    mdg_block_delete_if_used($db, "Vendor ".$_GET["id"], $_GET["id"], array(
      array("pemasukan", "pemasok"),
      array("purchase_order", "vendor_code"),
      array("po", "pemasok"),
      array("erp_rfq_vendor", "vendor_code"),
      array("erp_vendor_invoice", "vendor_code")
    ));
    $db->delete("pemasok","kode_pemasok",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          mdg_block_delete_if_used($db, "Vendor ".$id, $id, array(
            array("pemasukan", "pemasok"),
            array("purchase_order", "vendor_code"),
            array("po", "pemasok"),
            array("erp_rfq_vendor", "vendor_code"),
            array("erp_vendor_invoice", "vendor_code")
          ));
          $db->delete("pemasok","kode_pemasok",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    mdg_required(array("id"=>"ID ".erp_t('master_vendor_master','Vendor Master'),"kode_pemasok"=>erp_t('master_term_kode_pemasok','Vendor Code'),"nama"=>erp_t('master_term_nama_pemasok','Vendor Name')));
    $_POST["kode_pemasok"] = strtoupper(mdg_trim("kode_pemasok"));
    if (mdg_exists($db, "pemasok", "kode_pemasok", $_POST["kode_pemasok"], "kode_pemasok", $_POST["id"])) {
      mdg_error(erp_t('master_term_kode_pemasok','Vendor Code')." ".$_POST["kode_pemasok"]." ".erp_t('master_already_exists','already exists.'));
    }
   $data = array(
      "kode_pemasok" => $_POST["kode_pemasok"],
      "npwp" => mdg_trim("npwp"),
      "nama" => mdg_trim("nama"),
      "alamat" => mdg_trim("alamat"),
      "kota" => mdg_trim("kota"),
      "negara" => mdg_trim("negara"),
      "notelp" => mdg_trim("notelp"),
      "nofax" => mdg_trim("nofax"),
      "email" => mdg_trim("email"),
   );
   
   
   

    
          if(isset($_POST["status"]) && $_POST["status"]=="on")
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
