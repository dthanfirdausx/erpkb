<?php
session_start();
include 'inc/config.php';

$bulan = array('Jan'=>'01','Feb'=>'02',
'Mar'=>'03','Apr'=>'04','May'=>'05','Jun'=>'06','Jul'=>'07','Aug'=>'08','Sep'=>'09','Okt'=>'10','Nov'=>'11',
'Des'=>'12' );

$q = $db->query("select tgl_dokpab,no_aju from pemasukan_temp2 WHERE `tgl_dokpab` LIKE '%--%' and tgl_dokpab is not null group by no_aju "); 

echo "<pre>"; 
foreach ($q as $k) {
    $t = explode("--",$k->tgl_dokpab);
    $bln = $bulan[$t[1]];
    $tgl = $t[0]."-10-".$t[1]; 
   // echo "$tgl";
    $db->query("update pemasukan_temp2 set tgl_dokpab='$tgl' where no_aju='$k->no_aju' "); 
   // print_r($data_detail); 
   // $db->insert("bom_detail",$data_detail); 
   
	 
}
?>