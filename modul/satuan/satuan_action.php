<?php
session_start();
include "../../inc/config.php";
include "../../inc/master_data_guard.php";
session_check_json();

switch ($_GET["act"]) {
  case "in":
    mdg_required(array(
      "kode" => erp_t('master_term_kode_satuan','UOM Code'),
      "jenis" => erp_t('master_term_jenis_satuan','UOM Type'),
      "nama" => erp_t('master_term_nama_satuan','UOM Name')
    ));
    $_POST["kode"] = strtoupper(mdg_trim("kode"));
    $_POST["jenis"] = strtoupper(mdg_trim("jenis"));
    if (mdg_exists($db, "satuan", "kode", $_POST["kode"])) {
      mdg_error(erp_t('master_term_kode_satuan','UOM Code')." ".$_POST["kode"]." ".erp_t('master_already_exists','already exists.'));
    }
    $data = array(
      "kode" => $_POST["kode"],
      "jenis" => $_POST["jenis"],
      "nama" => mdg_trim("nama"),
    );
    $db->insert("satuan", $data);
    action_response($db->getErrorMessage());
    break;

  case "delete":
    mdg_block_delete_if_used($db, erp_t('master_uom','Unit of Measure')." ".$_GET["id"], $_GET["id"], array(
      array("barang", "satuan"),
      array("pemasukan_detail", "satuan"),
      array("detail_transaksi", "satuan"),
      array("erp_goods_issue_delivery_detail", "uom"),
      array("erp_issue_production_detail", "uom")
    ));
    $db->delete("satuan", "kode", $_GET["id"]);
    action_response($db->getErrorMessage());
    break;

  case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if (!empty($data_id_array)) {
      foreach ($data_id_array as $id) {
        mdg_block_delete_if_used($db, erp_t('master_uom','Unit of Measure')." ".$id, $id, array(
          array("barang", "satuan"),
          array("pemasukan_detail", "satuan"),
          array("detail_transaksi", "satuan"),
          array("erp_goods_issue_delivery_detail", "uom"),
          array("erp_issue_production_detail", "uom")
        ));
        $db->delete("satuan", "kode", $id);
      }
    }
    action_response($db->getErrorMessage());
    break;

  case "up":
    mdg_required(array(
      "id" => "ID ".erp_t('master_uom','Unit of Measure'),
      "kode" => erp_t('master_term_kode_satuan','UOM Code'),
      "jenis" => erp_t('master_term_jenis_satuan','UOM Type'),
      "nama" => erp_t('master_term_nama_satuan','UOM Name')
    ));
    $_POST["kode"] = strtoupper(mdg_trim("kode"));
    $_POST["jenis"] = strtoupper(mdg_trim("jenis"));
    if (mdg_exists($db, "satuan", "kode", $_POST["kode"], "kode", $_POST["id"])) {
      mdg_error(erp_t('master_term_kode_satuan','UOM Code')." ".$_POST["kode"]." ".erp_t('master_already_exists','already exists.'));
    }
    $data = array(
      "kode" => $_POST["kode"],
      "jenis" => $_POST["jenis"],
      "nama" => mdg_trim("nama"),
    );
    $db->update("satuan", $data, "kode", $_POST["id"]);
    action_response($db->getErrorMessage());
    break;
}
?>
