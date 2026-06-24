<?php
include 'config.php';
$key = isset($_POST['kd_barang']) ? trim($_POST['kd_barang']) : '';
$data = array();
$det = array('status' => '0', 'kd_barang' => '', 'nm_barang' => '', 'satuan' => '');
$q = $db->query("select kd_barang,nm_barang,satuan from barang where kd_barang=? and COALESCE(status,1)=1 limit 1", array($key));
foreach ($q as $k) {
	$det['status'] = '1';
	$det['kd_barang'] = $k->kd_barang;
	$det['nm_barang'] = $k->nm_barang;
	$det['satuan']    = $k->satuan;
	//$data['results'][] = $det;
}
echo json_encode($det);
?> 
