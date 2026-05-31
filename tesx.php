<?php
include 'inc/config.php';
         $kode = "QCNWAA004VDKA";
         $jumlah = 10;

         $res= array();
         $q = $db->query("select d.jumlah,d.kodebb from bom b join bom_detail d on d.id_bom=b.id where b.kodebj='$kode' ");
         $qty_bom = array();
         $qty_stock = array();
         $jml_produksi = array();
         foreach ($q as $k) {
            $qty_bom[] = $k->jumlah;
            $qq = $db->query("select ifnull(sum(s.stock),0) as stock from stock_barang s join barang b on b.id=s.id_barang where b.kd_barang='$k->kodebb' and id_bagian='3' ");
            if ($qq->rowCount()>0) {
            	foreach ($qq as $kk) {
	              $qty_stock[] = $kk->stock;
	              $jml_produksi[] = $kk->stock/$k->jumlah;
	            }
            }else{
            	$qty_stock[] = 0;
	            $jml_produksi[] = 0;
            }
            

         }
         // echo "<pre>";
         // print_r($qty_bom);
         // print_r($qty_stock);
         sort($jml_produksi);
         // print_r($jml_produksi);
         $stock_tersedia = $jml_produksi[0];
         echo $stock_tersedia;

?>