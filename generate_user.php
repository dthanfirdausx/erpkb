<?php

include 'inc/config.php';

$q = $db->query("select * from mahasiswa ");
$no=1;
foreach ($q as $k) {
	$data = array('first_name' => $k->nama , 
		          'username' => $k->nim,
		          'password' => md5('1234'),
		          'group_level' => '3',
		          'date_created' => date("Y-md"),
		          'aktif' => 'Y ');
	$db->insert("sys_users",$data);
	$id_user = $db->last_insert_id();
	$db->query("update mahasiswa set id_user='".$id_user."'
	            where nim='".$k->nim."' ");
	$no++;
}
echo "$no";

?>