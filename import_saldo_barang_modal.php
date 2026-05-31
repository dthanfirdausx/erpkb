<?php

include 'inc/config.php';

// $q = $db->query("select b.id, p.jenis_dokpab,sum(d.jumlah) as jml,d.kode from pemasukan_detail d join pemasukan p on p.no_bpb=d.no_bpb join barang b on b.kd_barang=d.kode
// where d.data_old='1' group by d.kode ");
// $no=1;
// echo "<pre>"; 
// foreach ($q as $k) {  
// 	print_r($k); 
// 	update_stock($k->jml,'plus',$k->jenis_dokpab,'1',$k->id,'admin',NULL,'1');  
// }

$q = $db->query("select b.id, p.jenis_dokpab,sum(d.jumlah) as jml,d.kode from pengeluaran_detail d join pengeluaran p on p.no_sj=d.no_sj join barang b on b.kd_barang=d.kode
where d.data_old='1' group by d.kode "); 
$no=1;
echo "<pre>"; 
foreach ($q as $k) {  
	print_r($k);   
	update_stock($k->jml,'minus',$k->jenis_dokpab,'1',$k->id,'admin',NULL,'1');  
}
echo "$no"; 

?>