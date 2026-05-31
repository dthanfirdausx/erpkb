<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {

  case "show_detail":
   $no_spb = $_POST['no_spb'];
  ?>  
  <table class="table">
    <thead>
      <tr>
        <th>No</th>
      <!--   <th>SPB</th>
        <th>Tanggal SPB</th> -->
        <th>Kode Barang</th>
        <th>Jenis Dokpab</th>
        <th>No Dokpab</th>
        <th>No Aju</th>
        <th>Qty RO</th>
        <th>Qty</th>
        <th>Satuan</th>
        <th>Keterangan</th>
      </tr>
    </thead>
    <tbody>
    <?php 
   // echo "string";
    $q = $db->query("select p.id_produksi_detail,p.no_bpb as no_lp, d.no, ifnull(pm.no_dokpab,'-') as no_dokpab,ifnull(pm.no_aju,'-') as no_aju,b.nm_barang, t.no_transfer as no_spb,t.tgl_transfer as tgl_spb,b.kd_barang as kode,ifnull(pm.jenis_dokpab,'-') as jenis_dokpab,ifnull(rd.jumlah,0) as qtyro,
d.jml as jumlah,
b.satuan,t.ket
from transfer t join transfer_detail d on d.id_transfer=t.id_transfer
left join pemasukan_detail dt on dt.id=d.id_incoming_detail
left join barang b on b.id=d.id_barang
left join pemasukan pm on pm.no_bpb=dt.no_bpb
left join ro on ro.no_ro=t.no_ro
left join brgjadi_detail p on p.id_produksi_detail=d.id_produksi_detail
left join ro_detail rd on (rd.no_ro=ro.no_ro and rd.kode=b.kd_barang)
where t.no_transfer='$no_spb'

 ");
    $no = 0;
    foreach ($q as $k) {     
      if ($no!=$k->no) {
         $no = $k->no;
         if ($k->id_produksi_detail!='') {
           $ket = $k->no_lp." ".$k->ket; 
         }else{
           $ket = $k->ket;
         }
          echo "<tr>
             <td>$k->no</td>
           
             <td>$k->kode , $k->nm_barang</td>
             <td>$k->jenis_dokpab</td>
             <td>$k->no_dokpab</td>
             <td>$k->no_aju</td>
             <td>$k->qtyro</td>
             <td>".number_format($k->jumlah,2)."</td>
             <td>$k->satuan</td>
             <td>$ket</td>
            </tr>";
      }else{
         echo "<tr>
             <td></td>
           
             <td></td>
             <td>$k->jenis_dokpab</td>
             <td>$k->no_dokpab</td>
             <td>$k->no_aju</td>
             <td>$k->qtyro</td>
             <td>".number_format($k->jumlah,2)."</td>
             <td>$k->satuan</td>
             <td>$k->ket</td>
            </tr>";
      }
     
    }
    ?>
    </tbody>
  </table>
  <?php
  break;
  case "terima_barang":
  $no_spb = $_POST['no_spb']; 
  $id = $_POST['id'];
  $no_lpb = GetNoTerima($id,5); 
  $db->query("update transfer set status='1',no_terima='$no_lpb' ,tgl_terima='".date("Y-m-d H:i:s")."',user_terima='".$_SESSION['username']."' where no_transfer='$no_spb' ");

  $q = $db->query("select d.id_barang, p.id_produksi_detail,p.no_bpb as no_lp, d.no, ifnull(pm.no_dokpab,'-') as no_dokpab,ifnull(pm.no_aju,'-') as no_aju,b.nm_barang, t.no_transfer as no_spb,t.tgl_transfer as tgl_spb,b.kd_barang as kode,ifnull(pm.jenis_dokpab,'-') as jenis_dokpab,ifnull(rd.jumlah,0) as qtyro,
d.jml as jumlah,
b.satuan,t.ket
from transfer t join transfer_detail d on d.id_transfer=t.id_transfer
left join pemasukan_detail dt on dt.id=d.id_incoming_detail
left join barang b on b.id=d.id_barang
left join pemasukan pm on pm.no_bpb=dt.no_bpb
left join ro on ro.no_ro=t.no_ro
left join brgjadi_detail p on p.id_produksi_detail=d.id_produksi_detail
left join ro_detail rd on (rd.no_ro=ro.no_ro and rd.kode=b.kd_barang)
where t.no_transfer='$no_spb'"); 
  foreach ($q as $k) {      
   // $data = (array)$k; 
    if ($k->jenis_dokpab=='-') { 
       $jenis_dokpab = 'brg_jadi';
    }else{
      $jenis_dokpab = $k->jenis_dokpab; 
    } 
    update_stock($k->jumlah,'plus',$jenis_dokpab,'4',$k->id_barang,$_SESSION['username']);
  }
 
   $q = $db->query("select tr.no_transfer,b.nm_bagian as dari,bb.nm_bagian as tujuan  
    from transfer tr
    join bagian b on b.id_bagian=tr.dari
    join bagian bb on bb.id_bagian=tr.ke where tr.no_transfer='$no_spb'");
   foreach ($q as $k) {
     simpan_log("Terima barang dari $k->dari dengan no Transfer $k->no_transfer",$_SESSION['username']);
   }
  
 //$db->query("update produksi_terima set terima='1',tgl_lpb='".date("Y-m-d")."' where no_spb='$no_spb' ");
    break;

  case "in":
    
  
  
  
  $data = array(
      "nomor" => $_POST["nomor"],
      "dari" => $_POST["dari"],
  );
  
  
  
   
    $in = $db->insert("vproduksiterima",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("vproduksiterima","",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("vproduksiterima","",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "nomor" => $_POST["nomor"],
      "dari" => $_POST["dari"],
   );
   
   
   

    
    
    $up = $db->update("vproduksiterima",$data,"",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>