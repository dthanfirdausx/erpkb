<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
include "../../inc/config.php";

$columns = array(
   'vst.nm_barang',
    'vst.kd_barang',
    'vst.nm_barang',
    'vst.stock',
    'vst.satuan',
    'vst.nm_kategori',
    'vst.plant_code',
    'vst.storage_location',
    'vst.storage_bin',
    'vst.kd_barang',
  );

  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("vst.nm_barang");

  //set order by type
  $datatable->set_order_type("asc");

   $wh="";
  $params = array();

  if (isset($_POST['kategori']) && $_POST['kategori']!='') {
    $wh .= " and vst.kd_kategori = ? ";
    $params[] = $_POST['kategori'];
  }

  if (isset($_POST['storage_location_id']) && $_POST['storage_location_id']!='') {
    $wh .= " and vst.storage_location_id = ? ";
    $params[] = (int) $_POST['storage_location_id'];
  }

  if (isset($_POST['storage_bin_id']) && $_POST['storage_bin_id']!='') {
    $wh .= " and vst.storage_bin_id = ? ";
    $params[] = (int) $_POST['storage_bin_id'];
  }

  $query = $datatable->get_custom("select vst.id, id_barang, vst.kd_barang,vst.nm_barang,vst.stock,vst.satuan,vst.nm_kategori,vst.plant_code,vst.storage_location,vst.storage_bin,vst.kd_barang from v_stock_transaksi vst where stock>=0 $wh",$columns,$params);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) { 

    //array data 
    $ResultData = array();
    $ResultData[] = $datatable->number($i); 
    $ResultData[] = "";
    $ResultData[] = htmlspecialchars($value->kd_barang, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->nm_barang, ENT_QUOTES, 'UTF-8');
    $ResultData[] = "<a class='so-stock-link' onclick='get_detail_stock(\"".htmlspecialchars($value->kd_barang, ENT_QUOTES, 'UTF-8')."\")'>".number_format((float)$value->stock,2,",",".")."</a>";
    $ResultData[] = htmlspecialchars($value->satuan, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->nm_kategori, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->plant_code, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->storage_location, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->storage_bin, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->kd_barang, ENT_QUOTES, 'UTF-8');

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>
