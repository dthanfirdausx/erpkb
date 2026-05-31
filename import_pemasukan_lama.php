<?php

include 'inc/config.php';

$q = $db->query("select v.*,b.id from v_stock_lama v  join barang b on b.kd_barang=v.kd_barang  ");
$no=1;
echo "<pre>"; 
foreach ($q as $k) { 
	//echo "select * from pemasukan_temp2 where kd_barang='$k->kd_barang' and tgl_dokpab>'2022-08-01' <br>";
	$qq = $db->query("select * from pemasukan_temp2 where kd_barang='".addslashes($k->kd_barang)."' and tgl_dokpab>'2022-08-01' ");
	foreach ($qq as $kk) {
		$qp = $db->query("select no_bpb,nomor from pemasukan where no_aju='$kk->no_aju' ");
		if ($qp->rowCount()==0) {
			$no_bpb = getNoBPB(date("Y",strtotime($kk->tgl_dokpab)));
			 $nomor = get_nomor("pemasukan","id");
			$data = array('no_bpb' => $no_bpb,
				          'nomor' => $nomor,
				          'jenis_dokpab' => 'BC 2.3',
			              'tgl_bpb' => $kk->tgl_dokpab,
			              'no_aju' => $kk->no_aju,
			              'tgl_aju' => $kk->tgl_dokpab,
			              'no_dokpab' => $kk->no_dokpab,
			              'tgl_dokpab' => $kk->tgl_dokpab,
			              'valuta' => 'USD',
			              'baru' => '1' );
			$data_detail = array('no_bpb' => $no_bpb,
								 'nomor' => $nomor,
								 'no_urut' => $kk->seri_barang,
								 'tgl_bpb' => $kk->tgl_dokpab,
								 'kode'=> $kk->kd_barang,
								 'jumlah' => $kk->jml,
								 'berat' => $kk->berat,
								 'harga' => $kk->nilai/$kk->jml,
								 'nilai' => $kk->nilai,
								 'baru' => '1');
			// $db->insert("pemasukan",$data);
			// $db->insert("pemasukan_detail",$data_detail);
			// update_stock($kk->jml,'plus','BC 2.3','1',$k->id,'admin',NULL,'1');   
			// print_r($data);  
			// print_r($data_detail);  
		}else{
			foreach ($qp as $kp) {
				$data_detail = array('no_bpb' => $kp->no_bpb,
								 'nomor' => $kp->nomor,
								  'no_urut' => $kk->seri_barang,
								 'tgl_bpb' => $kk->tgl_dokpab,
								 'jenis_dokpab' => 'BC 2.3',
								 'kode'=> $kk->kd_barang,
								 'jumlah' => $kk->jml,
								 'berat' => $kk->berat,
								 'harga' => $kk->nilai/$kk->jml,
								 'nilai' => $kk->nilai,
								 'baru' => '1');
				// $db->insert("pemasukan_detail",$data_detail); 
				// update_stock($kk->jml,'plus','BC 2.3','1',$k->id,'admin',NULL,'1');  
			} 
		}
		
	}
}
echo "$no";

?>