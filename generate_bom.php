<?php
session_start();
include 'inc/config.php';

$q = $db->query("select id,kodebj from bom where baru='1'  "); 

echo "<pre>"; 
foreach ($q as $k) {
   $qq = $db->query("select b.kode_bahan,b.jml,ba.nm_barang,ba.satuan 
from bom_temp b join barang ba on ba.kd_barang=b.kode_bahan where kode_bj='$k->kodebj' ");
   foreach ($qq as $kk) {
      $data_detail = array('id_bom' => $k->id ,  
                        'kodebb' => $kk->kode_bahan,
                        'nm_barang' => $kk->nm_barang,
                        'satuan' => $kk->satuan,
                        'jumlah' => $kk->jml,
                        'baru'   => '1'

                  );
  
   // print_r($data_detail); 
    $db->insert("bom_detail",$data_detail); 
   }
	 
}
?>