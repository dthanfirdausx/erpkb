<?php
error_reporting(0);
 $info = get_info_kb();   
  $q = $db->query("select d.nama_dokumen, h.diskon, h.kodeTujuanTpb,h.nilaiJasa,h.uangMuka, h.kodeKantorTujuan, `h`.`freight` AS `freight`,`p`.`nama_pelabuhan` AS `pel_bongkar`,`tps`.`URAIAN_TPS` AS `nama_tps`,
`pm`.`nama_pelabuhan` AS `pel_muat`,`pt`.`nama_pelabuhan` AS `pel_transit`,`d`.`nama_pendek` AS `nama_pendek`,
`d`.`id_dokumen` AS `id_dokumen`,`k`.`nama_kantor` AS `nama_kantor`,`tp`.`jenis_tpb` AS `jenis_tpb`,
`h`.`id_header` AS `id_header`,`h`.`uuid` AS `uuid`,`h`.`asalData` AS `asalData`,`h`.`asuransi` AS `asuransi`,
`h`.`bruto` AS `bruto`,`h`.`cif` AS `cif`,ifnull(`h`.`fob`,0) AS `fob`,`h`.`hargaPenyerahan` AS `hargaPenyerahan`,
`h`.`jabatanTtd` AS `jabatanTtd`,`h`.`jumlahKontainer` AS `jumlahKontainer`,`h`.`kodeAsuransi` AS `kodeAsuransi`,
`h`.`kodeDokumen` AS `kodeDokumen`,`h`.`tanggalDokumen` AS `tanggalDokumen`,`h`.`kodeIncoterm` AS `kodeIncoterm`,
`h`.`kodeKantor` AS `kodeKantor`,`h`.`kodeKantorBongkar` AS `kodeKantorBongkar`,`h`.`kodePelBongkar` AS `kodePelBongkar`,
`h`.`kodePelMuat` AS `kodePelMuat`,`h`.`kodePelTransit` AS `kodePelTransit`,`h`.`kodeTps` AS `kodeTps`,
`h`.`kodeTujuanTpb` AS `kodeTujuanTpb`,`h`.`kodeTutupPu` AS `kodeTutupPu`,`h`.`kodeValuta` AS `kodeValuta`,
`h`.`kotaTtd` AS `kotaTtd`,`h`.`namaTtd` AS `namaTtd`,`h`.`ndpbm` AS `ndpbm`,`h`.`netto` AS `netto`,
`h`.`nik` AS `nik`,`h`.`nilaiBarang` AS `nilaiBarang`,`h`.`nomorAju` AS `nomorAju`,
`h`.`tanggalAju` AS `tanggalAju`,`h`.`nomorBc11` AS `nomorBc11`,`h`.`posBc11` AS `posBc11`,`h`.`seri` AS `seri`,
`h`.`subposBc11` AS `subposBc11`,`h`.`tanggalBc11` AS `tanggalBc11`,`h`.`tanggalTiba` AS `tanggalTiba`,
`h`.`tanggalTtd` AS `tanggalTtd`,`h`.`biayaTambahan` AS `biayaTambahan`,`h`.`biayaPengurang` AS `biayaPengurang`,
`h`.`kodeKenaPajak` AS `kodeKenaPajak`,`h`.`dateCreated` AS `dateCreated`,`h`.`nomorDokpab` AS `nomorDokpab`,
`h`.`subsubposBc11` AS `subsubposBc11`,`h`.`cifRupiah` AS `cifRupiah` 
from (((((((`ws_header` `h` left join `ref_dokumen` `d` on(`d`.`id_dokumen` = `h`.`kodeDokumen`)) 
left join `ref_pelabuhan` `p` on(`p`.`kode_pelabuhan` = `h`.`kodePelBongkar`)) 
left join `ref_kantor` `k` on(`k`.`id_kantor` = `p`.`kode_kantor`)) 
left join `ref_jenis_tpb` `tp` on(`tp`.`id_jenis_tpb` = `h`.`kodeTujuanTpb`)) 
left join `ref_pelabuhan` `pm` on(`pm`.`kode_pelabuhan` = `h`.`kodePelMuat`)) 
left join `ref_pelabuhan` `pt` on(`pt`.`kode_pelabuhan` = `h`.`kodePelTransit`)) 
left join `referensi_tps` `tps` on(`tps`.`KODE_TPS` = `h`.`kodeTps` and `tps`.`KD_KANTOR` = `h`.`kodeKantorBongkar`))
where   h.id_header='".$_POST['id_header']."' "); 
  foreach ($q as $k) {
     $jenis_dokpab  = $k->id_dokumen;
        $nama_pendek   = $k->nama_pendek; 
        $nama_dokumen  = $k->nama_dokumen;
        $data_header   = $k;   
    $qa = $db->query("select id_entitas from ws_entitas where kodeEntitas='3' and id_header='$data_header->id_header' ");
      if ($qa->rowCount()==0) {
        $data_asal = array('id_header' => $data_header->id_header , 
                           'ket' => 'asal');
       // $db->insert("ws_entitas",$data_asal);
        $qa = $db->query("select * from ws_entitas where kodeEntitas='3' and id_header='$data_header->id_header' ");
        foreach ($qa as $ka) {
           $entitas_asal = $ka;
        }
      }else{
        $qa = $db->query("select * from ws_entitas where kodeEntitas='3' and id_header='$data_header->id_header' ");
        foreach ($qa as $ka) {
           $entitas_asal = $ka;
        }
      }

      $qa = $db->query("select id_entitas from ws_entitas where kodeEntitas='5' and id_header='$data_header->id_header' ");
      if ($qa->rowCount()==0) {
        $data_asal = array('id_header' => $data_header->id_header , 
                           'ket' => 'tujuan');
        //$db->insert("ws_entitas",$data_asal);
        $qa = $db->query("select * from ws_entitas where kodeEntitas='5' and id_header='$data_header->id_header' ");
        foreach ($qa as $ka) {
           $data_tujuan = $ka;
        }
      }else{
        $qa = $db->query("select * from ws_entitas where kodeEntitas='5' and id_header='$data_header->id_header' ");
        foreach ($qa as $ka) {
           $data_tujuan = $ka;
        }
      }

      $qa = $db->query("select id_entitas from ws_entitas where kodeEntitas='7' and id_header='$data_header->id_header' ");
      if ($qa->rowCount()==0) {
        $data_asal = array('id_header' => $data_header->id_header , 
                           'ket' => 'pemilik');
       // $db->insert("ws_entitas",$data_asal);
        $qa = $db->query("select * from ws_entitas where kodeEntitas='7' and id_header='$data_header->id_header' ");
        foreach ($qa as $ka) {
           $entitas_pemilik = $ka;
        }
      }else{
        $qa = $db->query("select * from ws_entitas where kodeEntitas='7' and id_header='$data_header->id_header' ");
        foreach ($qa as $ka) {
           $entitas_pemilik = $ka;
        }
}

$qda = $db->query("select * from ws_pengangkut left join ref_negara on ref_negara.kode_negara=ws_pengangkut.kodeBendera where ws_pengangkut.id_header='$data_header->id_header' ");

   foreach ($qda as $kda) {
     $data_angkut = $kda; 
   }
 $qh = $db->query("select d.*,r.nama_dokumen from ws_dokumen d join ref_dokumen r on r.id_dokumen=d.kodeDokumen where d.id_header='$k->id_header'  ");
 $dok = array();
 foreach ($qh as $kh) {
 
   $dok[] = '{
          "kodeDokumen":"'.$kh->kodeDokumen.'",
          "nomorDokumen":"'.$kh->nomorDokumen.'",
          "seriDokumen":'.$kh->seriDokumen.',
          "tanggalDokumen":"'.date("Y-m-d",strtotime($k->tanggalDokumen)).'"
        }
    ';
 }
 $dokumen = '['.implode(",", $dok).']';
  $kema = array();
  $qkm = $db->query("select d.*,j.kemasan from ws_kemasan d join ws_header h on h.id_header=d.id_header left join ref_jenis_kemasan j on j.id_kemasan=d.kodeJenisKemasan where d.id_header='$data_header->id_header'  ");
  foreach ($qkm as $kem) {  
    //print_r($kem);
    $kema[] = '{
          "jumlahKemasan":'.$kem->jumlahKemasan.',
          "kodeJenisKemasan":"'.$kem->kodeJenisKemasan.'",
          "merkKemasan":"'.$kem->merkKemasan.'",
          "seriKemasan":'.$kem->seriKemasan.'
       }';

  } 

   $kemasan = '['.implode(",", $kema).']';

  //kontainer
 $qh = $db->query("select d.* from ws_kontainer d where d.id_header='$k->id_header'  ");
 $dok = array();
 foreach ($qh as $kh) {

 
   $kon[] = '{ 
          "kodeJenisKontainer": "'.$kh->kodeJenisKontainer.'",
          "kodeTipeKontainer": "'.$kh->kodeTipeKontainer.'",
          "kodeUkuranKontainer": "'.$kh->kodeUkuranKontainer.'",
          "nomorKontainer":"'.$kh->nomorKontainer.'",
          "seriKontainer":'.$kh->seriKontainer.'
        }
    '; 
 }
 $kontainer = '['.implode(",", $kon).']';

 //kontainer
 $qh = $db->query("select d.* from ws_pengangkut d where d.id_header='$k->id_header'  ");
 $dok = array();
 foreach ($qh as $kh) {


   $peng[] = '{
         "kodeBendera": "'.$kh->kodeBendera.'",
         "namaPengangkut": "'.$kh->namaPengangkut.'",
         "nomorPengangkut": "'.$kh->nomorPengangkut.'",
         "kodeCaraAngkut": "'.$kh->kodeCaraAngkut.'",
         "seriPengangkut": '.$kh->seriPengangkut.'
        }
    '; 
 }
 $pengangkut = '['.implode(",", $peng).']';

  $bar = array();
  $qb = $db->query("select * from ws_barang  where id_header='$data_header->id_header'  ");
  foreach ($qb as $kb) { 

      $qbd = $db->query("select * from ws_barang_tarif where idBarang='$kb->idBarang' ");
      foreach ($qbd as $kbd) {
        $id_tarif_barang = $kbd->id_tarif_barang;
        $data_barang_tarif = $kbd;
      //  $tb= "";
        if ($kbd->kodeJenisPungutan=='BM') {
        	 $tb1='{
                "kodeJenisTarif":"'.$data_barang_tarif->kodeJenisTarif.'",
                "jumlahSatuan":'.$kb->jumlahSatuan.', 
                "kodeFasilitasTarif":"3",
                "kodeSatuanBarang":"PCE",
                "nilaiBayar":0.00,
                "nilaiFasilitas":100.00,
                "nilaiSudahDilunasi":0.00,
                "seriBarang":1,
                "tarif":11.00,
                "tarifFasilitas":100.00,
                "kodeJenisPungutan":"'.$kbd->kodeJenisPungutan.'"
             },';      
        }else if ($kbd->kodeJenisPungutan=='PPH') {
        	 $tb2 ='{
                "kodeJenisTarif":"'.$data_barang_tarif->kodeJenisTarif.'",
                "jumlahSatuan":'.$kb->jumlahSatuan.', 
                "kodeFasilitasTarif":"3",
                "kodeSatuanBarang":"PCE",
                "nilaiBayar":0.00,
                "nilaiFasilitas":100.00,
                "nilaiSudahDilunasi":0.00,
                "seriBarang":1,
                "tarif":11.00,
                "tarifFasilitas":100.00,
                "kodeJenisPungutan":"'.$kbd->kodeJenisPungutan.'"
             },';     
        }else if ($kbd->kodeJenisPungutan=='PPN') {
        	 $tb3='{
                "kodeJenisTarif":"'.$data_barang_tarif->kodeJenisTarif.'",
                "jumlahSatuan":'.$kb->jumlahSatuan.', 
                "kodeFasilitasTarif":"3",
                "kodeSatuanBarang":"PCE",
                "nilaiBayar":0.00,
                "nilaiFasilitas":100.00,
                "nilaiSudahDilunasi":0.00,
                "seriBarang":1,
                "tarif":11.00,
                "tarifFasilitas":100.00,
                "kodeJenisPungutan":"'.$kbd->kodeJenisPungutan.'"
             }';     
        }
        
      
    }
    $tarif_barang = '['.$tb1.$tb2.$tb3.']'; 
    $kdb = explode(" - ", $kb->uraian);
    // $.barang[0].nilaiTambah: is missing but it is required [CODE:1028],$.barang[0].barangDokumen: is missing but it is required [CODE:1028],$.barang[0].barangTarif[0].kodeJenisPungutan: must be a constant value BM [CODE:1042],$.kontainer[0].kodeJenisKontainer: does not have a value in the enumeration [4, 7, 8] [CODE:1008],$.kontainer[0].kodeJenisKontainer: integer found, string expected [CODE:1029],$.dokumen[0].kodeDokumen: must be a constant value 380
    $bar[] = ' {
          "asuransi":0.00,
          "bruto":123.4500,
          "cif":0.00,
          "diskon":0.00,
          "hargaEkspor":0.00,
          "hargaPenyerahan":'.$kb->hargaPenyerahan.',
          "nilaiTambah":'.$kb->nilaiTambah.',
          "fob":'.$kb->fob.',
          "freight":'.$kb->freight.', 
          "hargaSatuan":0.00,
          "kodeKategoriBarang" : "'.$kb->freight.'", 
          "isiPerKemasan":0,
          "jumlahKemasan":0.00,
          "jumlahRealisasi":0.00,
          "jumlahSatuan":'.$kb->jumlahSatuan.',
          "kodeBarang":"'.$kdb[0].'",
          "kodeDokumen":"40",
          "kodeJenisKemasan":"'.$kb->kodeJenisKemasan.'",
          "kodePerhitungan":"'.$kb->kodePerhitungan.'",
          "kodeNegaraAsal":"'.$kb->kodeNegaraAsal.'",
          "kodeSatuanBarang":"'.$kb->kodeSatuanBarang.'",
          "merk":"-",
          "netto":'.$kb->netto.',
          "nilaiBarang":0.00,
          "posTarif":"48191000",
          "seriBarang":1,
          "spesifikasiLain":"-",
          "tipe":"TIPE BARANG",
          "ukuran":"",
          "uraian":"'.$kb->uraian.'",
          "cifRupiah":0.00,
          "hargaPerolehan":0.00,
          "kodeAsalBahanBaku":"1",
          "ndpbm":0.00,
          "uangMuka":0.00,
          "nilaiJasa":0,
          "barangTarif":'. $tarif_barang.',
          "barangDokumen":[]
       }';
  }
  $barang = '['.implode(",", $bar).']';
  $kn     = explode(" - ", $info->kantor_pengawas);
  $data = '{
  "asalData": "S",
  "asuransi": 0,
  "bruto": '.$k->bruto.',
  "cif": '.$k->cif.',
  "kodeJenisImpor": "1",
  "fob": '.$k->fob.',
  "freight": '.$k->freight.',
  "hargaPenyerahan": 0,
  "jabatanTtd": "'.$k->jabatanTtd.'",
  "jumlahKontainer": 1,
  "kodeAsuransi": "LN",
  "kodeDokumen": "23",
  "kodeIncoterm": "CIF",
  "kodeKantor": "'.$kn[0].'",
  "kodeKantorBongkar": "'.$k->kodeKantorBongkar.'",
  "kodePelBongkar": "'.$k->kodePelBongkar.'",
  "kodePelMuat": "'.$k->kodePelMuat.'",
  "kodePelTransit": "'.$k->kodePelTransit.'",
  "kodeTps": "'.$k->kodeTps.'",
  "kodeTujuanTpb": "'.$k->kodeTps.'",
  "kodeValuta": "'.$k->kodeValuta.'",
  "kotaTtd": "'.$k->kotaTtd.'",
  "namaTtd": "'.$k->namaTtd.'",
  "ndpbm": '.$k->ndpbm.',
  "netto": '.$k->netto.',
  "nik": "'.$k->nik.'",
  "nilaiBarang": 0,
  "nomorAju": "'.$k->nomorAju.'",
  "nomorBc11": "'.$k->nomorBc11.'",
  "posBc11": "'.$k->posBc11.'",
  "seri": 0,
  "kodeTutupPu" : "'.$k->kodeTutupPu.'",
  "subposBc11": "'.$k->subposBc11.'",
  "tanggalBc11": "'.date("Y-m-d",strtotime($k->tanggalBc11)).'",
  "tanggalTiba": "'.date("Y-m-d",strtotime($k->tanggalTiba)).'",
  "tanggalTtd": "'.date("Y-m-d",strtotime($k->tanggalTtd)).'",
  "biayaTambahan": '.$k->biayaTambahan.',
  "biayaPengurang": '.$k->biayaPengurang.',
  "kodeKenaPajak": "'.$k->kodeKenaPajak.'",
  "barang": '.$barang.',
  "entitas": [
      {
          "alamatEntitas": "'.$info->alamat.'",
          "kodeEntitas": "3",
          "kodeJenisIdentitas": "3",
          "namaEntitas": "'.$info->nama.'",
          "nibEntitas": "'.$entitas_asal->nibEntitas.'",
          "nomorIdentitas": "'.clean($info->npwp).'",
          "seriEntitas": 1,
          "nomorIjinEntitas": "'.$info->skepkb.'",
          "tanggalIjinEntitas": "'.$info->tglskep.'"
       },
    {
      "alamatEntitas": "'.$entitas_pemilik->alamatEntitas.'",
      "kodeEntitas": "5",
      "kodeNegara": "ID",
      "namaEntitas": "PEMILIK",
      "seriEntitas": 2
    },
     {
          "alamatEntitas":"'.$data_tujuan->alamatEntitas.'",
          "kodeEntitas":"7",      
          "kodeJenisIdentitas":"5",
          "kodeStatus":"5",
          "namaEntitas":"'.$data_tujuan->namaEntitas.'",
          "nibEntitas":"'.$data_tujuan->nomorIjinEntitas.'",
          "nomorIdentitas":"'.clean($data_tujuan->nomorIdentitas).'",
          "seriEntitas":3
       }
  ],
  "kemasan":'.$kemasan.',
  "kontainer": '.$kontainer.',
  "dokumen": '.$dokumen.',
  "pengangkut": '.$pengangkut.'
}';
   
//echo $data;    
   error_reporting(0); 
   $res = kirim_dokumen($data);
   echo json_encode($res);   
  } 
?>