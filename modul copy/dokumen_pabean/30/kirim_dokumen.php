<?php
 $info = get_info_kb();    
  $q = $db->query("select kk.nama_kantor as namaKantorPeriksa, h.lokasiPeriksa,h.kantorPeriksa, h.tanggalPerkiraanEkspor,h.tanggalPeriksa, h.kodeNegaraTujuan,rn.negara as negaraTujuan, h.jenisEkspor,h.caraDagang,h.kategoriEkspor,h.komoditi,h.curah,h.caraBayar, d.nama_dokumen,k.nama_kantor, h.diskon, h.kodeTujuanTpb,h.nilaiJasa,h.uangMuka, h.kodeKantorTujuan, `h`.`freight` AS `freight`,`p`.`nama_pelabuhan` AS `pel_bongkar`,`tps`.`URAIAN_TPS` AS `nama_tps`,
`pm`.`nama_pelabuhan` AS `pel_muat`,`pt`.`nama_pelabuhan` AS `pel_transit`,`d`.`nama_pendek` AS `nama_pendek`,
`d`.`id_dokumen` AS `id_dokumen`,`k`.`nama_kantor` AS `nama_kantor`,`tp`.`jenis_tpb` AS `jenis_tpb`,
`h`.`id_header` AS `id_header`,`h`.`uuid` AS `uuid`,`h`.`asalData` AS `asalData`,`h`.`asuransi` AS `asuransi`,
`h`.`bruto` AS `bruto`,`h`.`cif` AS `cif`,`h`.`fob` AS `fob`,`h`.`hargaPenyerahan` AS `hargaPenyerahan`,
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
from (((((((((`ws_header` `h` left join `ref_dokumen` `d` on(`d`.`id_dokumen` = `h`.`kodeDokumen`)) 
left join `ref_pelabuhan` `p` on(`p`.`kode_pelabuhan` = `h`.`kodePelBongkar`)) 
left join `ref_kantor` `k` on(`k`.`id_kantor` = `p`.`kode_kantor`)) 
left join `ref_kantor` `kk` on(`kk`.`id_kantor` = `h`.`kantorPeriksa`)) 
left join `ref_jenis_tpb` `tp` on(`tp`.`id_jenis_tpb` = `h`.`kodeTujuanTpb`)) 
left join `ref_pelabuhan` `pm` on(`pm`.`kode_pelabuhan` = `h`.`kodePelMuat`)) 
left join `ref_pelabuhan` `pt` on(`pt`.`kode_pelabuhan` = `h`.`kodePelTransit`)) 
left join `referensi_tps` `tps` on(`tps`.`KODE_TPS` = `h`.`kodeTps` and `tps`.`KD_KANTOR` = `h`.`kodeKantorBongkar`))
left join `ref_negara` `rn` on(`rn`.`kode_negara` = `h`.`kodeNegaraTujuan`))
where   h.id_header='".$_POST['id_header']."' "); 
  foreach ($q as $k) {
     $jenis_dokpab  = $k->id_dokumen;
        $nama_pendek   = $k->nama_pendek; 
        $nama_dokumen  = $k->nama_dokumen;
        $data_header   = $k;   
    $qa = $db->query("select id_entitas from ws_entitas where kodeEntitas='2' and id_header='$data_header->id_header' ");
      foreach ($qa as $ka) {
           $entitas1 = $ka;
      }
      

      $qa = $db->query("select id_entitas from ws_entitas where kodeEntitas='7' and id_header='$data_header->id_header' ");
     
        foreach ($qa as $ka) {
           $entitas2 = $ka;
        }
      

      $qa = $db->query("select id_entitas from ws_entitas where kodeEntitas='8' and id_header='$data_header->id_header' ");
    
        foreach ($qa as $ka) {
           $entitas3 = $ka;
        }
         $qa = $db->query("select id_entitas from ws_entitas where kodeEntitas='6' and id_header='$data_header->id_header' ");
    
        foreach ($qa as $ka) { 
           $entitas4 = $ka;
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

    $bar[] = '
      "cif": 0,
      "cifRupiah": 0,
      "fob": '.$kb->fob.',
      "hargaEkspor": '.$kb->fob.',
      "hargaPatokan": 0,
      "hargaPerolehan": '.$kb->hargaPerolehan.',
      "hargaSatuan": '.$kb->hargaSatuan.',
      "jumlahKemasan": 1,
      "jumlahSatuan": 12,
      "kodeAsalBahanBaku": "0",
      "kodeBarang": "'.$kb->kodeBarang.'",
      "kodeDaerahAsal": "3175",
      "kodeDokumen": "30",
      "kodeJenisKemasan": "'.$kb->kodeJenisKemasan.'",
      "kodeNegaraAsal": "ID",
      "kodeSatuanBarang": "KGM",
      "merk": "'.$kb->merk.'",
      "ndpbm": '.$kb->ndpbm.',
      "netto": '.$kb->netto.',
      "nilaiBarang": 0,
      "nilaiDanaSawit": 0,
      "posTarif": "'.$kb->posTarif.'",
      "seriBarang": '.$kb->seriBarang.',
      "spesifikasiLain": "'.$kb->spesifikasiLain.'",
      "tipe": "'.$kb->tipe.'",
      "ukuran": "'.$kb->ukuran.'",
      "uraian": "URAIAN BARANG 1",
      "volume": 388.5,
      "barangDokumen": [
        {
          "seriDokumen": 1
        }
      ]';  
  }
  $barang = '['.implode(",", $bar).']';
  $kn     = explode(" - ", $info->kantor_pengawas);
  $data = '{
  "asalData": "S",
  "asuransi": 0,
  "bruto": '.$k->bruto.',
  "cif": '.$k->cif.',
  "disclaimer": "1",
  "flagCurah": "'.$k->curah.'",
  "flagMigas": "'.$k->komoditi.'",
  "fob": '.$k->fob.',
  "freight": '.$k->freight.',
  "idPengguna": "'.user_ws.'",
  "jabatanTtd": "'.$k->jabatanTtd.'",
  "jumlahKontainer": 1,
  "kodeAsuransi": "LN",
  "kodeCaraBayar": "9",
  "kodeCaraDagang": "1",
  "kodeDokumen": "30",
  "kodeIncoterm": "FOB",
  "kodeJenisEkspor": "1", 
  "kodeJenisNilai": "",
  "kodeJenisProsedur": "",
  "kodeKantor": "'.$k->kodeKantorBongkar.'",
  "kodeKantorEkspor": "'.$k->kodeKantorBongkar.'",
  "kodeKantorMuat": "'.$k->kodeKantorBongkar.'",
  "kodeKantorPeriksa": "'.$k->kantorPeriksa.'",
  "kodeKategoriEkspor": "'.$k->kategoriEkspor.'",
  "kodeLokasi": "2",
  "kodeNegaraTujuan": "'.$k->kodeNegara.'",
  "kodePelBongkar": "'.$k->kodePelBongkar.'",
  "kodePelEkspor": "'.$k->kodePelBongkar.'",
  "kodePelMuat": "'.$k->kodePelMuat.'",
  "kodePelTujuan": "'.$k->kodePelBongkar.'",
  "kodePembayar": "'.$k->caraBayar.'",
  "kodeTps": "'.$k->kodeTps.'",
  "kodeValuta": "'.$k->kodeValuta.'",
  "kotaTtd": "'.$k->kotaTtd.'",
  "namaTtd": "'.$k->namaTtd.'",
  "ndpbm": '.$k->ndpbm.',
  "netto": '.$k->netto.',
  "nilaiMaklon": 242.5,
  "nomorAju": "'.$k->nomorAju.'",
  "seri": 1,
  "tanggalAju": "'.date("Y-m-d",strtotime($k->tanggalAju)).'",
  "tanggalEkspor": "'.date("Y-m-d",strtotime($k->tanggalTtd)).'",
  "tanggalPeriksa": "'.date("Y-m-d",strtotime($k->tanggalPeriksa)).'",
  "tanggalTtd": "'.date("Y-m-d",strtotime($k->tanggalTtd)).'",
  "totalDanaSawit": 0,
  "entitas": [
       {
      "alamatEntitas": "'.$entitas1->alamatEntitas.'",
      "kodeEntitas": "2",
      "kodeJenisIdentitas": "5",
      "namaEntitas": "'.$entitas1->namaEntitas.'",
      "nibEntitas": "1111111111",
      "nomorIdentitas": "123456789",
      "seriEntitas": 2
      },
      {
        "alamatEntitas": "'.$entitas2->alamatEntitas.'",
        "kodeEntitas": "7",
        "kodeJenisIdentitas": "5",
        "namaEntitas": "'.$entitas2->namaEntitas.'",
        "nibEntitas": "1111111111",
        "nomorIdentitas": "123456789",
        "seriEntitas": 13
      },
      {
        "alamatEntitas": "'.$entitas3->alamatEntitas.'",
        "kodeEntitas": "8",
        "kodeNegara": "SA",
        "namaEntitas": "'.$entitas3->namaEntitas.'",
        "seriEntitas": 8
      },
      {
        "alamatEntitas": "'.$entitas4->alamatEntitas.'",
        "kodeEntitas": "6",
        "kodeNegara": "'.$entitas4->kodeNegara.'",
        "namaEntitas": "'.$entitas4->namaEntitas.'",
        "seriEntitas": 6
      }
  ],
  "kemasan":'.$kemasan.',
  "kontainer": '.$kontainer.',
  "dokumen": '.$dokumen.',
  "pengangkut": '.$pengangkut.',
   "bankDevisa": [
    {
      "kodeBank": "9",
      "seriBank": 1
    }
  ],
  "kesiapanBarang": [
    {
      "kodeJenisBarang": "1",
      "kodeJenisGudang": "2",
      "namaPic": "'.$k->namaTtd.'",
      "alamat": "'.$k->kotaTtd.'",
      "nomorTelpPic": "081111111111",
      "lokasiSiapPeriksa": "JAKARTA",
      "kodeCaraStuffing": "7",
      "kodeJenisPartOf": "2",
      "tanggalPkb": "'.date("Y-m-d",strtotime($k->tanggalPeriksa)).'",
      "waktuSiapPeriksa": "'.date("Y-m-d",strtotime($k->tanggalPeriksa)).'T11:00:00.000Z",
      "jumlahContainer20": 1,
      "jumlahContainer40": 1
    }
  ] 
}';
   
//echo $data;    
   error_reporting(0); 
   $res = kirim_dokumen($data);
   echo json_encode($res);   
  } 
?>