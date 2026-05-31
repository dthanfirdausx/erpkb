<?php
include 'inc/config.php';
$q = $db->query("select mhs_id, nim,nama,no_pendaftaran from mahasiswa where status='CM'  group by no_pendaftaran having count(*)>1");
foreach ($q as $k) {
	//$n = explode('-' , $k->nim);
	//$nim = $n[0].$n[1].$n[2];
	//echo "update mahasiswa set no_pendaftaran='$nim', nim=NULL, status='CM' where mhs_id='$k->mhs_id' <br> ";
	$db->query("delete from mahasiswa where mhs_id='$k->mhs_id' ");
}
?>