<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {


  case "buat_view":
   $tgl_awal = $_POST['tgl_awal']; 
   $tgl_akhir = $_POST['tgl_akhir'];
   $db->query("drop view v_hitung_mutasi_barang_jadi"); 
   $db->query("drop view v_mutasi_barang_jadi"); 
   $db->query("create view v_hitung_mutasi_barang_jadi as select b.id,ifnull(c.jml,0) as closing, b.satuan, b.kd_barang,ifnull(pr.jml,0) as pr1,ifnull(pr2.jml,0) as pr2, b.nm_barang,ifnull(p1.jml,0) as p1,ifnull(p2.jml,0) as p2,ifnull(p3.jml,0) as p3,ifnull(p4.jml,0) as p4,ifnull(k.jml,0) as k1,
ifnull(k2.jml,0) as k2,ifnull(k3.jml,0) as k3,ifnull(k4.jml,0) as k4
from
barang b 
left join 
(select kd_barang, ifnull(stock,0) as jml from closing where month(tgl_closing)='$bln' and year(tgl_closing)='$thn'  ) as c on c.kd_barang=b.kd_barang
left join
(select ifnull(sum(jumlah),0) as jml,kode from brgjadi_detail where tgl_bpb<'$tgl_awal' group by kode ) as pr on pr.kode=b.kd_barang
left join
(select ifnull(sum(jumlah),0) as jml,kode from brgjadi_detail where tgl_bpb between '$tgl_awal' and '$tgl_akhir' group by kode ) as pr2 on pr2.kode=b.kd_barang
left join 
(select kode,ifnull(sum(jumlah),0) as jml from pemasukan_detail  join pemasukan  on pemasukan.no_bpb=pemasukan_detail.no_bpb 
where pemasukan.tgl_bpb<'$tgl_awal' group by kode ) as p1 on p1.kode=b.kd_barang
left join 
(select kode,ifnull(sum(jumlah),0) as jml from pemasukan_detail  join pemasukan  on pemasukan.no_bpb=pemasukan_detail.no_bpb 
where pemasukan.tgl_bpb between '$tgl_awal' and '$tgl_akhir' group by kode ) as p2 on p2.kode=b.kd_barang
left join
(select ifnull(sum(transfer_detail.jml),0) as jml,transfer_detail.id_barang from transfer_detail join transfer on transfer.id_transfer=transfer_detail.id_transfer 
where transfer.ke='1' and transfer.tgl_transfer<'$tgl_awal' group by transfer_detail.id_barang) as p3 on p3.id_barang=b.id
left join
(select ifnull(sum(transfer_detail.jml),0) as jml,transfer_detail.id_barang from transfer_detail join transfer on transfer.id_transfer=transfer_detail.id_transfer 
where transfer.ke='1' and transfer.tgl_transfer between '$tgl_awal' and '$tgl_akhir' group by transfer_detail.id_barang) as p4 on p4.id_barang=b.id
left join
(select ifnull(sum(transfer_detail.jml),0) as jml,transfer_detail.id_barang from transfer_detail join transfer on transfer.id_transfer=transfer_detail.id_transfer 
where transfer.dari='1' and transfer.ke!='4' and transfer.tgl_transfer<'$tgl_awal' group by transfer_detail.id_barang) as k on k.id_barang=b.id
left join
(select ifnull(sum(transfer_detail.jml),0) as jml,transfer_detail.id_barang from transfer_detail join transfer on transfer.id_transfer=transfer_detail.id_transfer 
where transfer.dari='1' and transfer.ke!='4' and transfer.tgl_transfer between '$tgl_awal' and '$tgl_akhir' group by transfer_detail.id_barang) as k3 on k3.id_barang=b.id
left join
(select kode,ifnull(sum(jumlah),0) as jml from pengeluaran_detail  join pengeluaran  on pengeluaran.no_sj=pengeluaran_detail.no_sj 
where pengeluaran.tgl_sj<'$tgl_awal' group by kode ) as k2 on k2.kode=b.kd_barang
left join
(select kode,ifnull(sum(jumlah),0) as jml from pengeluaran_detail  join pengeluaran  on pengeluaran.no_sj=pengeluaran_detail.no_sj 
where pengeluaran.tgl_sj between '$tgl_awal' and '$tgl_akhir' group by kode ) as k4 on k4.kode=b.kd_barang
where b.kd_kategori='K02'
");  

   $db->query(" create view v_mutasi_barang_jadi as
    select  id, `v_hitung_mutasi_barang_jadi`.`kd_barang` AS `kd_barang`,`v_hitung_mutasi_barang_jadi`.`nm_barang` AS `nm_barang`,`v_hitung_mutasi_barang_jadi`.`satuan` AS `satuan`,closing,(`v_hitung_mutasi_barang_jadi`.`p1` + `v_hitung_mutasi_barang_jadi`.`p3`+v_hitung_mutasi_barang_jadi.pr1) - (`v_hitung_mutasi_barang_jadi`.`k2` + `v_hitung_mutasi_barang_jadi`.`k1`) AS `saldo_awal`,(v_hitung_mutasi_barang_jadi.pr2+ `v_hitung_mutasi_barang_jadi`.`p2` + `v_hitung_mutasi_barang_jadi`.`p4`) AS `pemasukan`,`v_hitung_mutasi_barang_jadi`.`k3` + `v_hitung_mutasi_barang_jadi`.`k4` AS `pengeluaran`,`v_hitung_mutasi_barang_jadi`.`p1` + `v_hitung_mutasi_barang_jadi`.`p3` - (`v_hitung_mutasi_barang_jadi`.`k2` + `v_hitung_mutasi_barang_jadi`.`k1`) + (`v_hitung_mutasi_barang_jadi`.`p2` + `v_hitung_mutasi_barang_jadi`.`p4`) - (`v_hitung_mutasi_barang_jadi`.`k3` + `v_hitung_mutasi_barang_jadi`.`k4`) AS `saldo_akhir`,'0' AS `penyesuaian`,'0' AS `stock_opname`,'0' AS `selisih`,'' AS `ket`,'".$_SESSION['username']."' as userid from `v_hitung_mutasi_barang_jadi`");   

    break;

   case "show_detail_pemasukan": 
      $tgl_awal = "1970-01-01";
      $tgl_akhir = date("Y-m-d");
      if ($_POST['tgl_awal']!='') {
        $tgl_awal = $_POST['tgl_awal'];
      }
      if ($_POST['tgl_akhir']!='') {
         $tgl_akhir = $_POST['tgl_akhir']; 
      } 
    $kd_barang = $_POST['kd_barang'];
   
   
    $tabel = $_POST['tabel'];
    $q = $db->query("select * from $tabel where tgl_dokumen between '$tgl_awal' and '$tgl_akhir'
    and kode='$kd_barang' group by no_dokumen "); 
    ?>
    <table class="table">
      <thead>
        <tr>
          <th>No</th>
          <th>Kode/Nama Brang</th>
          <th>Satuan</th>
          <th>No Dokumen</th>
          <th>Tanggal Dokumen</th>
          <th>Jenis Dokpab</th>
          <th>No Dokpab</th>
          <th>Tanggal Dokpab</th>
          <th>No Aju</th>
          <th>Tanggal Aju</th>
          <th>Jumlah</th>
        </tr>
        <tbody>
          <?php
          $no=1;
          $jml = 0;
          foreach ($q as $k) {
            echo "<tr>
              <td>$no</td>
              <td>$k->kode / $k->nm_barang</td>
              <td>$k->satuan</td>
              <td>$k->no_dokumen</td>
              <td>$k->tgl_dokumen</td>
              <td>$k->jenis_dokpab</td>
              <td>$k->no_dokpab</td>
              <td>$k->tgl_dokpab</td>
              <td>$k->no_aju</td>
              <td>$k->tgl_aju</td>
              <td>".number_format($k->jumlah,2,",",".")."</td>
             
            </tr>";
            $jml = $jml+$k->jumlah;
            $no++;
          }
          ?>
          <tr>
            <td colspan="10">Total</td>
            <td><?= number_format($jml,2,",",".") ?></td>
          </tr>
        </tbody>
      </thead>
    </table>
    <?php
  // action_response($db->getErrorMessage()); 
  break; 
  case "in":
    
  
  
  
  $data = array(
      "id" => $_POST["id"],
      "kd_barang" => $_POST["kd_barang"],
  );
  
  
  
   
    $in = $db->insert("mutasi_barangjadi",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("mutasi_barangjadi","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("mutasi_barangjadi","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "id" => $_POST["id"],
      "kd_barang" => $_POST["kd_barang"],
   );
   
   
   

    
    
    $up = $db->update("mutasi_barangjadi",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>