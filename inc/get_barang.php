<?php
include 'config.php';
$key = isset($_GET['search']) ? trim($_GET['search']) : '';
$data = array();
$q = $db->query(
	"select kd_barang,nm_barang,satuan from barang where COALESCE(status,1)=1 and (kd_barang like ? or nm_barang like ?) order by kd_barang limit 50",
	array('%'.$key.'%', '%'.$key.'%')
);
foreach ($q as $k) {
	$det['id'] = $k->kd_barang;
	$det['text'] = $k->kd_barang.' - '.$k->nm_barang;
	$det['uom'] = $k->satuan;
	$data['results'][] = $det;
}
echo json_encode($data);
?> 
