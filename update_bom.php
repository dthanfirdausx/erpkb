<?php
session_start();
include 'inc/config.php';

$q = $db->query("select b.id,b.kodebj
from bom b join bom_temp2 bb on bb.brg_jadi=b.kodebj group by b.kodebj "); 

echo "<pre>"; 
foreach ($q as $k) {
  print_r($k);
   $db->query("delete from bom_detail where id_bom='$k->id'  "); 
   $qq = $db->query("select b.kd_bahan_baku,b.qty,ba.nm_barang,ba.satuan 
from bom_temp2 b join barang ba on ba.kd_barang=b.kd_bahan_baku  where brg_jadi='$k->kodebj' ");
   foreach ($qq as $kk) {
    //  $qb = $db->query("select * from bom_detail id_bom='$k->id_bom' and and kodebb='$kk->kd_bahan_baku'  ")
      $data_detail = array('id_bom' => $k->id ,  
                        'kodebb' => $kk->kd_bahan_baku,
                        'nm_barang' => $kk->nm_barang,
                        'satuan' => $kk->satuan,
                        'jumlah' => $kk->qty,
                        'baru'   => '1'

                  ); 
  
   // print_r($data_detail); 
    $db->insert("bom_detail",$data_detail); 
   }
	 
}
?>