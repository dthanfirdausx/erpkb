<?php
error_reporting(0);
session_start();
include "inc/config.php";
include "inc/excel/php-excel-reader/excel_reader2.php";
include "inc/excel/SpreadsheetReader.php";
switch ($_GET["act"]) { 

  case "get_barang":
    $kd_barang = $_POST['kd_barang'];
    $q = $db->query("select kd_barang,nm_barang,satuan from barang where kd_barang='$kd_barang' ");
    $res = array();
   foreach ($q as $k) {
      $res['satuan'] = $k->satuan;
      $res['nm_barang'] = $k->nm_barang;
   }
   echo json_encode($res); 

   break;

	case "get_unit":
    $kd_barang = $_POST['kd_barang'];
    $q = $db->query("select kd_barang,nm_barang,satuan from barang where kd_barang='$kd_barang' ");
    $res = array();
   foreach ($q as $k) {
      echo "$k->satuan";
   }
 //  echo json_encode($res);
     break;
   case "get_unit":
    $kd_barang = $_POST['kd_barang'];
    $q = $db->query("select kd_barang,nm_barang,satuan from barang where kd_barang='$kd_barang' ");
    $res = array();
   foreach ($q as $k) {
      echo "$k->satuan";
   }
 //  echo json_encode($res);
     break;

  case "cari_kode":
    $kode = trim($_POST['term']);
    $q = $db->query("select id, kd_barang,nm_barang,ifnull(packing_size,'') as packing_size from barang where kd_barang like '%$kode%' or nm_barang like '%$kode%' limit 5 ");
    $res = array();
   foreach ($q as $k) { 
      $h['kd_barang']    = $k->kd_barang;
      $h['nm_barang']    = $k->nm_barang;
      $h['id_barang']    = $k->id;
      $h['packing_size'] = $k->packing_size;
      $res[] = $h;
   }
   echo json_encode($res);
    break;

     default:
    # code...
    break;

  }

?>