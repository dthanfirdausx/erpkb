<?php
include 'config.php';
$key = $_GET['search'];
$data = array();
$q = $db->query("select kd_barang,nm_barang from barang where kd_barang like '%$key%' or nm_barang like '%$key%' ");
foreach ($q as $k) {
	$det['id'] = $k->kd_barang;
	$det['text'] = $k->nm_barang;
	$data['results'][] = $det;
}
echo json_encode($data);
?> 