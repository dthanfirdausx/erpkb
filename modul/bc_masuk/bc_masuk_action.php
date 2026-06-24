<?php
session_start();
include "../../inc/config.php";
include "../../inc/master_data_guard.php";
session_check_json();

switch ($_GET["act"]) {
  case "in":
    mdg_required(array(
      "kode" => erp_t('master_term_kode_bc','BC Code'),
      "jenis" => erp_t('master_term_jenis_dokumen','Document Type'),
      "nama" => erp_t('master_term_nama','Name')
    ));
    $_POST["kode"] = strtoupper(mdg_trim("kode"));
    if (mdg_exists($db, "jenisbcmasuk", "kode", $_POST["kode"])) {
      mdg_error(erp_t('master_term_kode_bc','BC Code')." ".$_POST["kode"]." ".erp_t('master_already_exists','already exists.'));
    }
    $data = array(
      "kode" => $_POST["kode"],
      "jenis" => mdg_trim("jenis"),
      "nama" => mdg_trim("nama"),
    );
    $db->insert("jenisbcmasuk", $data);
    action_response($db->getErrorMessage());
    break;

  case "delete":
    mdg_block_delete_if_used($db, erp_t('master_inbound_customs_type','Inbound Customs Type')." ".$_GET["id"], $_GET["id"], array(
      array("pemasukan", "jenis_dokpab"),
      array("pemasukan_detail", "jenis_dokpab"),
      array("stock_layer", "jenis_dokpab"),
      array("detail_transaksi", "jenis_dokpab")
    ));
    $db->delete("jenisbcmasuk", "kode", $_GET["id"]);
    action_response($db->getErrorMessage());
    break;

  case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if (!empty($data_id_array)) {
      foreach ($data_id_array as $id) {
        mdg_block_delete_if_used($db, erp_t('master_inbound_customs_type','Inbound Customs Type')." ".$id, $id, array(
          array("pemasukan", "jenis_dokpab"),
          array("pemasukan_detail", "jenis_dokpab"),
          array("stock_layer", "jenis_dokpab"),
          array("detail_transaksi", "jenis_dokpab")
        ));
        $db->delete("jenisbcmasuk", "kode", $id);
      }
    }
    action_response($db->getErrorMessage());
    break;

  case "up":
    mdg_required(array(
      "id" => "ID ".erp_t('master_inbound_customs_type','Inbound Customs Type'),
      "kode" => erp_t('master_term_kode_bc','BC Code'),
      "jenis" => erp_t('master_term_jenis_dokumen','Document Type'),
      "nama" => erp_t('master_term_nama','Name')
    ));
    $_POST["kode"] = strtoupper(mdg_trim("kode"));
    if (mdg_exists($db, "jenisbcmasuk", "kode", $_POST["kode"], "kode", $_POST["id"])) {
      mdg_error(erp_t('master_term_kode_bc','BC Code')." ".$_POST["kode"]." ".erp_t('master_already_exists','already exists.'));
    }
    $data = array(
      "kode" => $_POST["kode"],
      "jenis" => mdg_trim("jenis"),
      "nama" => mdg_trim("nama"),
    );
    $db->update("jenisbcmasuk", $data, "kode", $_POST["id"]);
    action_response($db->getErrorMessage());
    break;
}
?>
