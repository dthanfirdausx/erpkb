<?php

include 'inc/config.php';

$q = $db->query("select * from brgjadi");

echo "<pre>";
foreach ($q as $k) {
	$tgl_terima = date_create($k->tgl_bpb)->modify('+1 days')->format('Y-m-d H:i:s');
	 $data = array(  
      "tgl_transfer"  => $k->tgl_bpb,
      // "no_ro"         => $_POST["no_request"],
      "is_produksi"        => "1",
      "user"          => $k->userid,
      "ket"           => $k->catatan,
      "kd_dept"       => "DEP-0007",
      "status"        => "1",
      "dari"          => '3',
      "ke"            => '4',
      "tgl_terima"    => $tgl_terima,
      "date_created"  => $tgl_terima,
    );
	 $db->insert("transfer" , $data);
	 $id               = $db->last_insert_id(); 
     $no_transfer      = GetNoTransfer($id,5);
     $db->query("update transfer set no_transfer='$no_transfer' where id_transfer='$id' ");
     $qq = $db->query("select d.id_produksi_detail,ba.id as id_barang, d.kode,ba.nm_barang,ba.satuan,d.jumlah from
			brgjadi_detail d join brgjadi b on b.id_produksi=b.id_produksi
			join barang ba on ba.kd_barang=d.kode where d.id_produksi='$k->id_produksi' group by d.id_produksi_detail ");
     $no=1;
     foreach ($qq as $kk) { 
     	 $data_detail = array(
                                     'id_transfer'        => $id , 
                                     'id_barang'          => $kk->id_barang,
                                     'id_produksi_detail' => $kk->id_produksi_detail,
                                     'no'                 => $no,
                                     'jml'                => $kk->jumlah,
                                     'date_created'       => date("Y-m-d H:i:s")
                                     ); 
     	  $db->insert("transfer_detail",$data_detail);  
     	  $no++;
     }
	// print_r($data);
	// $db->insert("sys_users",$data);
	// $id_user = $db->last_insert_id();
	// $db->query("update mahasiswa set id_user='".$id_user."'
	//             where nim='".$k->nim."' ");
	//$no++;
}
echo "$no";

?>