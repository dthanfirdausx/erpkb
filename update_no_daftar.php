<?php
include 'inc/config.php';
$q = $db->query("select * from mahasiswa where no_pendaftaran is null and nim!='' and mulai_smt='20191' ");
foreach ($q as $k) {
	//$n = explode('-' , $k->nim);
	//$nim = $n[0].$n[1].$n[2];
	echo "update mahasiswa set no_pendaftaran='$k->nim', nim=NULL, status='CM' where mhs_id='$k->mhs_id' <br> ";
	$db->query("update mahasiswa set no_pendaftaran='$k->nim', nim=NULL, status='CM' where mhs_id='$k->mhs_id' ");
}
?>