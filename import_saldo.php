<?php
session_start();
include 'inc/config.php';
$tgl_bpb = "2022-12-31"; 
  $no_bpb = getNoBPB("2022");
  $nomor = get_nomor("pemasukan","id");
   $data = array(
      "no_bpb" => $no_bpb,
      "nomor" => $nomor,
      "tgl_bpb" => $tgl_bpb,
     // "nopo" => $_POST["nopo"],
     // "pemasok" => $_POST["pemasok"],
      // "no_invoice" => $_POST["no_invoice"],
      // "tgl_invoice" => $_POST["tgl_invoice"],
      // "no_do" => $_POST["no_do"],
       "no_dokpab" => "-",
      // "tgl_dokpab" => $_POST["tgl_dokpab"],
      // "catatan" => $_POST["catatan"],
      "jenis_dokpab" => "Saldo Awal",
      //"kd_catdet" => $_POST["kd_catdet"],
      "no_aju" => "-",
      "saldo_awal" => '1',
      // "efaktur" => $_POST["efaktur"],
      // "tgl_efaktur" => $_POST["tgl_efaktur"],
      // "valuta" => $_POST["valuta"],
      // "kurs" => $_POST["kurs"],
       'userid' => $_SESSION['username'],   
  ); 
   $db->insert("pemasukan",$data);
$q = $db->query("select closing.kd_barang,b.kd_barang as kd_barang2,ifnull(closing.stock,0) as stock,b.id,b.satuan from closing left join barang b on b.kd_barang=closing.kd_barang 
where stock is not null and b.kd_barang is not null and saldo_awal='1' "); 

echo "<pre>"; 
foreach ($q as $k) {

	  $data_detail = array('nomor' => $nomor ,  
                    'no_bpb' => $no_bpb,
                    // 'tgl_bpb' => $_POST["tgl_bpb"],
                     'kode' => $k->kd_barang, 
                    'jumlah' => $k->stock,
                    "saldo_awal" => '1',
                     'no_urut' => '1',
                   // 'nilai' => $_POST['nilai'][$key],
                     'unit' => $k->satuan,
                    // 'berat' => $_POST['berat'][$key], 
                    // 'no_urut' => $no,                    
                    // 'no_aju' => $_POST['no_aju'],
                    // 'tgl_aju' => $_POST['tgl_aju'],
                    // 'tgl_masuk' => $_POST['tgl_aju'],
                    // 'jenis_dokpab' => $_POST['jenisbcmasuk_jenis_dokumen'],
                    // 'no_dokpab' => $_POST['no_dokpab'],
                    // 'tgl_dokpab' => $_POST['tgl_dokpab'],
                     'date_created' => date("Y-m-d H:i:s")
                  );
	
	 $db->insert("pemasukan_detail",$data_detail);
	 update_stock($k->stock,'plus',"Saldo Awal",'1',$k->id,"admin","1"); 
}
?>