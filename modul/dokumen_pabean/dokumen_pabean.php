<?php
switch (uri_segment(2)) {
    
    case "buat_dokumen": 
      $uuid = uri_segment(3);
      
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
where   h.uuid='$uuid' ");    
      foreach ($q as $k) {   
        $jenis_dokpab  = $k->id_dokumen;
        $nama_pendek   = $k->nama_pendek; 
        $nama_dokumen  = $k->nama_dokumen;
        $data_header   = $k;   
      } 
      buat_entitas($data_header->id_header,$jenis_dokpab);
      $info = get_info_kb(); 
      include "$jenis_dokpab/dokumen_pabean_add.php";
    break;

    case "tambah":
          foreach ($db->fetch_all("sys_menu") as $isi) {
               if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah") {
                          if ($role_act["insert_act"]=="Y") {
                             include "dokumen_pabean_add.php";
                          } else {
                            echo customs_t("permission_denied", "permission denied");
                          }
                       }

      }
    break;
  case "edit":
    $data_edit = $db->fetch_single_row("bahan","no_lap",uri_segment(3));
        foreach ($db->fetch_all("sys_menu") as $isi) {
                      if (uri_segment(1)==$isi->url&&uri_segment(2)=="edit") {
                          if ($role_act["up_act"]=="Y") {
                             include "dokumen_pabean_edit.php";
                          } else {
                            echo customs_t("permission_denied", "permission denied");
                          }
                       }

      }

    break;
    case "detail":
    $data_edit = $db->fetch_single_row("bahan","no_lap",uri_segment(3));
    include "dokumen_pabean_detail.php";
    break;
    default:
    include "dokumen_pabean_view.php";
    break;
}

?>