<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {

  case "get_barang":

$term = $_POST['term'];

$q = $db->query("
    SELECT kd_barang, nm_barang, satuan 
    FROM barang
    WHERE kd_barang LIKE '%$term%' 
    OR nm_barang LIKE '%$term%'
    LIMIT 20
");

$data = [];

foreach($q as $k){

    $data[] = [
    "label"  => $k->kd_barang." - ".$k->nm_barang,
    "value"  => $k->kd_barang,
    "nama"   => $k->nm_barang, // 🔥 WAJIB
    "satuan" => $k->satuan
];
}

echo json_encode($data);

break;
  case "in":

$data = array(
    "nomor"   => $_POST["nomor"],
    "no_lap"  => $_POST["no_lap"],
    "tgl_lap" => $_POST["tgl_lap"],
    "name_ppc"=> $_POST["name_ppc"],
    "catatan" => $_POST["catatan"],
);

// ================= SIMPAN HEADER =================
$in = $db->insert("bahan",$data);

// ================= SIMPAN DETAIL =================
$kode   = $_POST['kode_barang'];
$qty    = $_POST['qty'];

$row = 1;

foreach ($kode as $i => $k){

    if($k == '') continue;

    $db->insert("bahan_detail", [
        "nomor"   => $_POST["nomor"],
        "no_lap"  => $_POST["no_lap"],
        "tgl_lap" => $_POST["tgl_lap"],
        "kode"    => $k,
        "jumlah"  => $qty[$i],
        "row_no"  => $row++
    ]);
}

action_response($db->getErrorMessage());
break;
  case "delete":
    
    
    
    $db->delete("bahan","no_lap",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("bahan","no_lap",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "nomor" => $_POST["nomor"],
      "no_lap" => $_POST["no_lap"],
      "tgl_lap" => $_POST["tgl_lap"],
      "name_ppc" => $_POST["name_ppc"],
      "catatan"=>$_POST["catatan"],
   );
   
   
   

    
    
    $up = $db->update("bahan",$data,"no_lap",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>