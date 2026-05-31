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
       <!--  <th>Jenis Dokpab</th> -->
       <!--  <th>No Dokpab</th>
        <th>No Aju</th>
        <th>Qty RO</th> -->
        <th>Qty</th>
        <th>Satuan</th>
        <th>Keterangan</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $q = $db->query("select d.no, pd.jumlah as jml_produksi,pd.id_produksi_detail, pm.no_dokpab,pm.no_aju,b.nm_barang, t.no_transfer as no_spb,t.tgl_transfer as tgl_spb,b.kd_barang as kode,pm.jenis_dokpab,ifnull(rd.jumlah,0) as qtyro,
d.jml as jumlah,
b.satuan,t.ket
from transfer t join transfer_detail d on d.id_transfer=t.id_transfer
left join pemasukan_detail dt on dt.id=d.id_incoming_detail
left join brgjadi_detail pd on pd.id_produksi_detail=d.id_produksi_detail
left join barang b on b.id=d.id_barang
left join pemasukan pm on pm.no_bpb=dt.no_bpb
left join ro on ro.no_ro=t.no_ro
left join ro_detail rd on (rd.no_ro=ro.no_ro and rd.kode=b.kd_barang)
where t.no_transfer='$no_spb'

 ");
    $no = 0;
    foreach ($q as $k) {  
      $jumlah = $k->jumlah;
      if ($k->jml_produksi!='') {
           $jumlah = $k->jml_produksi;
         }   
      if ($no!=$k->no) {
         $no = $k->no;
          echo "<tr>
             <td>$k->no</td>
           
             <td>$k->kode , $k->nm_barang</td>
            
             <td>".number_format($jumlah,2)."</td>
             <td>$k->satuan</td>
             <td>$k->ket</td>
            </tr>";
      }else{
         echo "<tr>
             <td></td>
           
             <td></td>
             
             <td>".number_format($jumlah,2)."</td>
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
  case "in":
    
  
  
  
  $data = array(
      "nomor" => $_POST["nomor"],
      "no_lpb" => $_POST["no_lpb"],
      "tgl_lpb" => $_POST["tgl_lpb"],
      "dari" => $_POST["dari"],
      "no_spb" => $_POST["no_spb"],
      "tgl_spb" => $_POST["tgl_spb"],
      "dept" => $_POST["dept"],
      "name_ppc" => $_POST["name_ppc"],
      "catatan" => $_POST["catatan"],
      "user_trt" => $_POST["user_trt"],
      "userid" => $_POST["userid"],
  );
  
  
  
   
    $in = $db->insert("produksi_terima",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("produksi_terima","no_lpb",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("produksi_terima","no_lpb",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "nomor" => $_POST["nomor"],
      "no_lpb" => $_POST["no_lpb"],
      "tgl_lpb" => $_POST["tgl_lpb"],
      "dari" => $_POST["dari"],
      "no_spb" => $_POST["no_spb"],
      "tgl_spb" => $_POST["tgl_spb"],
      "dept" => $_POST["dept"],
      "name_ppc" => $_POST["name_ppc"],
      "catatan" => $_POST["catatan"],
      "user_trt" => $_POST["user_trt"],
      "userid" => $_POST["userid"],
   );
   
   
   

    
    
    $up = $db->update("produksi_terima",$data,"no_lpb",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>