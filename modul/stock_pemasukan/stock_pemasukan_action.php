<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {

  case "download_excel":
  header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
  header("Content-Disposition: attachment; filename=rekap_stock_pemasukan.xls");  //File name extension was wrong
  header("Expires: 0");
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header("Cache-Control: private",false);
  ?>
  <h3 style="text-align: center">Rekap Stock Pemasukan</h3>
  <table class="table" border="1"> 
     <thead>
       <tr>
         <th>Jenis Dokpab</th>
         <th>Kode Barang</th>
         <th>Nama Barang</th>
         <th>No Dokpab</th>
         <th>Tanggal Dokpab</th>
         <th>No Aju</th> 
         <th>Tanggal Aju</th>
         <th>Tanggal Masuk</th>
         <th>Lokasi</th>
         <th>Stock</th>
         <th>Satuan</th>
       </tr>
     </thead>
     <tbody>
  <?php
  $q = $db->query("select jenis_dokpab,kd_barang,nm_barang,no_dokpab,tgl_dokpab,no_aju,tgl_aju,
tgl_bpb,lokasi,satuan,((jumlah+masuk)-keluar) as stock 
from v_stock_pemasukan where ((jumlah+masuk)-keluar)>0 order by kd_barang asc, tgl_bpb asc,jenis_dokpab asc ");
  foreach ($q as $kk) {
    // $qq = $db->query("select jenis_dokpab,kd_barang,nm_barang,no_dokpab,tgl_dokpab,no_aju,tgl_aju,tgl_bpb,lokasi,satuan,((jumlah+masuk)-keluar) as stock from v_stock_pemasukan where ((jumlah+masuk)-keluar)>0 and kd_barang='$k->kd_barang' order by tgl_bpb asc");
    // foreach ($qq as $kk) {
        echo "<tr>
               <td>$kk->jenis_dokpab</td>
               <td>$kk->kd_barang</td>
               <td>$kk->nm_barang</td>
               <td>'$kk->no_dokpab</td>
               <td>$kk->tgl_dokpab</td>
               <td>'$kk->no_aju</td>
               <td>$kk->tgl_aju</td>
               <td>$kk->tgl_bpb</td>
               <td>$kk->lokasi</td>
               <td style='text-align:right'>".number_format($kk->stock,5,",",".")."</td>
               <td>$kk->satuan</td>
             
             </tr>"; 
   //  }
   // echo "<tr>
   //             <td colspan='8'>Jumlah</td>
               
   //             <td style='text-align:right'>".number_format($k->stock,5,",",".")."</td>
   //             <td>$k->satuan</td>
             
   //           </tr>";
  }
   ?>
    
     </tbody> 
   </table>
   <?php
    break;

   case "sinkron_stock":
   $id = $_POST['id'];
   $kd_barang = $_POST['kd_barang'];
   $posisi = $_POST['posisi'];
   rekap_stock($posisi,$kd_barang); 
     break;

     case "show_detail_stock":

$kd_barang = $_POST['kd_barang'];

?>
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>No</th>
            <th>Kode</th>
            <th>No Aju</th>
            <th>No Dokpab</th>
            <th>Jenis Dok</th>
            <th>Tgl Masuk</th>
            <th>Qty Masuk</th>
           
            <th>Sisa</th>
        </tr>
    </thead>
    <tbody>
<?php

$q = $db->query("
    SELECT 
        sl.*,
        b.nm_barang,
        b.satuan
    FROM stock_layer sl
    LEFT JOIN barang b 
        ON b.kd_barang = sl.kode
    WHERE 
        sl.kode = '$kd_barang' 
        AND sl.lokasi = 'GUDANG'
       
    ORDER BY 
        sl.tgl_masuk ASC, 
        sl.id ASC
");

$no = 1;
$total_masuk = 0;
$total_sisa  = 0;

foreach ($q as $k){

    echo "<tr>
        <td>$no</td>
        <td>$k->kode , $k->nm_barang</td>
        <td>$k->no_aju</td>
        <td>$k->no_dokpab</td>
        <td>$k->jenis_dokpab</td>
        <td>$k->tgl_masuk</td>
        <td style='text-align:right'>".number_format($k->qty_masuk,4)."</td>
        <td style='text-align:right'>".number_format($k->qty_sisa,4)."</td>
      
    </tr>";

    $total_masuk += $k->qty_masuk;
    $total_sisa  += $k->qty_sisa;

    $no++;
}
?>

<tr style="font-weight:bold;background:#f1f1f1">
    <td colspan="6" style="text-align:center">TOTAL</td>
    <td style="text-align:right"><?= number_format($total_masuk,4) ?></td>
    <td style="text-align:right"><?= number_format($total_sisa,4) ?></td>
</tr>

    </tbody>
</table>
<?php

break;

  
  case "in":
    
  
  
  
  $data = array(
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => $_POST["nm_barang"],
      "stock" => $_POST["stock"],
      "satuan" => $_POST["satuan"],
      "nm_kategori" => $_POST["nm_kategori"],
  );
  
  
  
   
    $in = $db->insert("vtotalstockpemasukan",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("vtotalstockpemasukan","",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("vtotalstockpemasukan","",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => $_POST["nm_barang"],
      "stock" => $_POST["stock"],
      "satuan" => $_POST["satuan"],
      "nm_kategori" => $_POST["nm_kategori"],
   );
   
   
   

    
    
    $up = $db->update("vtotalstockpemasukan",$data,"",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>