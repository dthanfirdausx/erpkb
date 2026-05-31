<?php
session_start();
include 'inc/config.php';
echo "<pre>";
// echo "select s.no_aju,s.kd_barang,s.no_ref,s.tgl_masuk,p.userid,p.catatan,d.kd_dept,p.tgl_spb
// 	from stock_incoming s join incoming_outgoing p  on p.no_spb=s.no_ref join dept d on d.nm_dept=p.dept  where s.status='20' group by s.no_ref limit 10";
echo "<pre>";
$q = $dbo->query("select s.no_aju,s.kd_barang,s.no_ref,s.tgl_masuk,p.userid,p.catatan,d.kd_dept,p.tgl_spb
	from stock_incoming s join incoming_outgoing p  on p.no_spb=s.no_ref join dept d on d.nm_dept=p.dept  where s.status='20' group by s.no_ref ");
   $dari             = '1';
    $ke               = '4';
    if ($ke == '2') {
      $tujuan = "Pra Produksi";
    }elseif ($ke == '3') {
      $tujuan = "Produksi";
    }else{
       $tujuan = "Outgoing";
    }
    foreach ($q as $k) {
    	//echo "string";
    	 $data = array(  
	      "tgl_transfer"  => $k->tgl_masuk,
	      "user"          => $k->userid,
	      "ket"           => "".$k->no_ref." <br> ".$k->catatan."", 
	      "kd_dept"       => $k->kd_dept,
	      "status"        => '1',
	      "dari"          => $dari,
	      "ke"            => '4',
	      "date_created"  => $k->tgl_spb
	    );
        $in               = $db->insert("transfer",$data);
	    $id               = $db->last_insert_id(); 
	    $no_transfer      = GetNoTransfer($id,5);
	    $db->query("update transfer set no_transfer='$no_transfer' where id_transfer='$id' ");
	    $log_transfer = "Transfer dari Incoming tujuan $tujuan dengan No Transfer $no_transfer";
    	//print_r($data); 
    	$qq = $dbo->query("select d.id as id_incoming_detail,b.id as id_barang,s.jumlah,s.kd_barang,d.no_urut from stock_incoming s join pemasukan p on p.no_aju=s.no_aju 
join pemasukan_detail d on (d.no_bpb=p.no_bpb and s.kd_barang=d.kode ) join barang b on b.kd_barang=s.kd_barang where s.no_ref='$k->no_ref'"); 
    	foreach ($qq as $kk) {
    		  $data_detail = array(
                                     'id_transfer'        => $id , 
                                     'id_barang'          => $kk->id_barang,
                                     'id_incoming_detail' => $kk->id_incoming_detail,
                                     'no'                 => $kk->no_urut,
                                     'jml'                => $kk->jumlah,
                                     'date_created'       => $k->tgl_spb
                                     ); 
    		  $db->insert("transfer_detail",$data_detail); 
              //  update_stock($jml_terpakai,'minus',$kc->jenis_dokpab,'1',$_POST['id_input'][$key],$_SESSION['username']);    
    	}
    }
?>