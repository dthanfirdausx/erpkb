<?php
session_start();
include "../../inc/config.php";
include "../../inc/master_data_guard.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    mdg_required(array("satuan_packing"=>erp_t('master_term_satuan_packing','Packing Unit')));
    $_POST["satuan_packing"] = strtoupper(mdg_trim("satuan_packing"));
    if (mdg_exists($db, "satuan_packing", "satuan_packing", $_POST["satuan_packing"])) {
      mdg_error(erp_t('master_term_satuan_packing','Packing Unit')." ".$_POST["satuan_packing"]." ".erp_t('master_already_exists','already exists.'));
    }
  $data = array(
      "satuan_packing" => $_POST["satuan_packing"],
  );
  
  
  
   
    $in = $db->insert("satuan_packing",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    $packing = $db->fetch("SELECT satuan_packing FROM satuan_packing WHERE id=? LIMIT 1", array($_GET["id"]));
    $packingCode = $packing ? $packing->satuan_packing : $_GET["id"];
    mdg_block_delete_if_used($db, erp_t('master_term_satuan_packing','Packing Unit')." ".$packingCode, $packingCode, array(
      array("barang", "packing"),
      array("pemasukan_detail", "jenis_kemasan"),
      array("erp_goods_issue_delivery_detail", "package_type")
    ));
    $db->delete("satuan_packing","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $packing = $db->fetch("SELECT satuan_packing FROM satuan_packing WHERE id=? LIMIT 1", array($id));
          $packingCode = $packing ? $packing->satuan_packing : $id;
          mdg_block_delete_if_used($db, erp_t('master_term_satuan_packing','Packing Unit')." ".$packingCode, $packingCode, array(
            array("barang", "packing"),
            array("pemasukan_detail", "jenis_kemasan"),
            array("erp_goods_issue_delivery_detail", "package_type")
          ));
          $db->delete("satuan_packing","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    mdg_required(array("id"=>"ID ".erp_t('master_term_satuan_packing','Packing Unit'),"satuan_packing"=>erp_t('master_term_satuan_packing','Packing Unit')));
    $_POST["satuan_packing"] = strtoupper(mdg_trim("satuan_packing"));
    if (mdg_exists($db, "satuan_packing", "satuan_packing", $_POST["satuan_packing"], "id", $_POST["id"])) {
      mdg_error(erp_t('master_term_satuan_packing','Packing Unit')." ".$_POST["satuan_packing"]." ".erp_t('master_already_exists','already exists.'));
    }
   $data = array(
      "satuan_packing" => $_POST["satuan_packing"],
   );
   
   
   

    
    
    $up = $db->update("satuan_packing",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>
