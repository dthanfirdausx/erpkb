<?php
session_start();
include "../../inc/config.php";
include "../../inc/master_data_guard.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    mdg_required(array("kd_kategori"=>erp_t('master_term_kode_kategori','Category Code'),"nm_kategori"=>erp_t('master_term_nama_kategori','Category Name')));
    $_POST["kd_kategori"] = strtoupper(mdg_trim("kd_kategori"));
    if (mdg_exists($db, "kategori", "kd_kategori", $_POST["kd_kategori"])) {
      mdg_error(erp_t('master_term_kode_kategori','Category Code')." ".$_POST["kd_kategori"]." ".erp_t('master_already_exists','already exists.'));
    }
  $data = array(
      "kd_kategori" => $_POST["kd_kategori"],
      "nm_kategori" => mdg_trim("nm_kategori"),
  );
  
  
  
   
    $in = $db->insert("kategori",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    mdg_block_delete_if_used($db, erp_t('master_material_category','Material Category')." ".$_GET["id"], $_GET["id"], array(
      array("barang", "kd_kategori"),
      array("stock_layer", "kat_barang"),
      array("detail_transaksi", "kat_barang")
    ));
    $db->delete("kategori","kd_kategori",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          mdg_block_delete_if_used($db, erp_t('master_material_category','Material Category')." ".$id, $id, array(
            array("barang", "kd_kategori"),
            array("stock_layer", "kat_barang"),
            array("detail_transaksi", "kat_barang")
          ));
          $db->delete("kategori","kd_kategori",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    mdg_required(array("id"=>"ID ".erp_t('master_material_category','Material Category'),"kd_kategori"=>erp_t('master_term_kode_kategori','Category Code'),"nm_kategori"=>erp_t('master_term_nama_kategori','Category Name')));
    $_POST["kd_kategori"] = strtoupper(mdg_trim("kd_kategori"));
    if (mdg_exists($db, "kategori", "kd_kategori", $_POST["kd_kategori"], "kd_kategori", $_POST["id"])) {
      mdg_error(erp_t('master_term_kode_kategori','Category Code')." ".$_POST["kd_kategori"]." ".erp_t('master_already_exists','already exists.'));
    }
   $data = array(
      "kd_kategori" => $_POST["kd_kategori"],
      "nm_kategori" => mdg_trim("nm_kategori"),
   );
   
   
   

    
    
    $up = $db->update("kategori",$data,"kd_kategori",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>
