<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {

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
  
  
  
   
    $in = $db->insert("mutasi_scrap",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("mutasi_scrap","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("mutasi_scrap","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "id" => $_POST["id"],
      "kd_barang" => $_POST["kd_barang"],
   );
   
   
   

    
    
    $up = $db->update("mutasi_scrap",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>