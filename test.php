<?php
include "inc/config.php";

$get_krs = $db->query("select * from krs_detail where nilai_angka is not null or nilai_huruf!='' group by nim");

echo "<pre>";
foreach ($get_krs as $nim) {
	$up = upAkm($nim->nim);

	print_r($up);
}


function upAkm($nim){
   global $db;

   $q=$db->query("select s.id_semester from krs_detail k join semester s on k.id_semester=s.id_semester
                  join mahasiswa m on m.nim=k.nim where k.nim='$nim' and s.kode_jur=m.jur_kode group by
                  s.id_semester order by s.id_semester asc ");
   $ipk=0;
   $bobot_ipk=0;
   $sks_ipk=0;
   foreach ($q as $k) {
   	print_r($k);
     $qq = $db->query("select akm_id from akm where mhs_nim='$nim' and sem_id='$k->id_semester' ");
     echo "select akm_id from akm where mhs_nim='$nim' and sem_id='$k->id_semester' ";
     if ($qq->rowCount()==0) {
       $datax = array('sem_id' => $k->id_semester ,
                      'mhs_nim' => $nim);
       $db->insert("akm",$datax);
       echo $db->getErrorMessage();
     }
      $ip=0;
      $ipk=0;
      foreach ($db->query("select sum(k.sks) as jml_sks, sum(k.bobot * k.sks) as jml_bobot from krs_detail k
                           where k.nim='$nim' and k.id_semester='$k->id_semester' and k.batal='0' and k.nilai_huruf!=''
                           group by k.id_semester ")
              as $kk) {
         $bobot_ipk = $bobot_ipk + $kk->jml_bobot;
         $sks_ipk   = $sks_ipk + $kk->jml_sks;
         $ipk       = $bobot_ipk/$sks_ipk;
         $ip        = $kk->jml_bobot/$kk->jml_sks;
         $db->query("update akm set ip='".number_format($ip,2)."',ipk='".number_format($ipk,2)."' where sem_id='$k->id_semester' and mhs_nim='$nim' ");
         //echo "update akm set ip='".number_format($ip,2)."',ipk='".number_format($ipk,2)."' where sem_id='$k->id_semester' and mhs_nim='$nim'  <br>";
      }
   }
}

exit();

$matkul = $db->query("select * from matkul");
$startMemory = memory_get_usage();
$arr = array();
foreach ($matkul as $mt) {
	$arr[$mt->kode_mk] = $mt->id_matkul;
}



function convertToReadableSize($size){
  $base = log($size) / log(1024);
  $suffix = array("", "KB", "MB", "GB", "TB");
  $f_base = floor($base);
  return round(pow(1024, $base - floor($base)), 1) . $suffix[$f_base];
}
print_r($arr);

$bytes = memory_get_usage() - $startMemory;
echo convertToReadableSize($bytes);
?>