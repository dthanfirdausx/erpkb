<?php
include 'config.php';
echo "<pre>";
$q = $db->query("select * from transfer_temp 
where tujuan='produksi' group by tgl_spb ");
foreach ($q as $k) {
	$nomor = get_nomor("produksi","nomor");
	$nomor2 = get_nomor("produksi_terima","nomor");
	$spb = getNoSPBProduksi(date("Y",strtotime($k->tgl_spb)));
	$lpb = GetNextLPBProdNo(date("Y",strtotime($k->tgl_spb)));
	$data_spb = array('nomor' => $nomor , 
                  'no_spb' => $spb,
                  'tgl_spb' => $k->tgl_spb,
                  'dept' => $k->dept,
                  'name_ppc' => $k->pcc,
                  'userid' => $k->pcc,
                  'catatan' => $k->catatan,
                  'generate' => '1' );

  $data_lpb = array('nomor' => $nomor2 , 
                  'no_lpb' => $lpb, 
                  'tgl_lpb' => $k->tgl_spb,
                  'dept' => $k->catatan,
                  'name_ppc' => $k->pcc,
                  'user_trt' => $k->pcc,
                  'generate' => '1' );
  // print_r($data_spb);
  // print_r($data_lpb);
  $db->insert("produksi",$data_spb);
  $db->insert("produksi_terima",$data_lpb);
  $qq = $db->query("select * from transfer_temp where tgl_spb='$k->tgl_spb' and tujuan='produksi' "); 
  $no=1; 
  foreach ($qq as $kk) {
     $data_detail = array('nomor' => $nomor , 
                          'no_spb' => $spb,
                          'tgl_spb' => $kk->tgl_spb,
                          'jenis_dokpab' => $kk->jenis_dokpab,
                          'kode' => $kk->kd_barang,
                          'qtyro' => $kk->jumlah,
                          'jumlah' => $kk->jumlah,
                          'generate' => '1',
                          'row_no' => $no);
     $data_detail2 = array('nomor' => $nomor2 , 
                          'no_lpb' => $lpb, 
                          'dari' => 'INCOMING',
                          'tgl_lpb' => $kk->tgl_spb, 
                          'kode' => $kk->kd_barang, 
                          'generate' => '1',
                          'jumlah' => $kk->jumlah,
                          'row_no' => $no);
     $db->insert("produksi_detail",$data_detail);
     $db->insert("produksi_terima_detail",$data_detail2);  
     // print_r($data_detail); 
     // print_r($data_detail2); 
     $no++;
  }
}
?>