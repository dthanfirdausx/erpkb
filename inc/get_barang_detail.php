<?php
include 'config.php';
$key = $_POST['kd_barang'];
$data = array();
$q = $db->query("select kd_barang,nm_barang,satuan from barang where kd_barang='$key' ");
foreach ($q as $k) {
	$det['kd_barang'] = $k->kd_barang;
	$det['nm_barang'] = $k->nm_barang;
	$det['satuan']    = $k->satuan;
	//$data['results'][] = $det;
}
echo json_encode($det);
?> 