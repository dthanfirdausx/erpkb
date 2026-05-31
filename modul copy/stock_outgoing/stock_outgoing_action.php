<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  
  case "sinkron_stock":
   $id = $_POST['id'];
   $kd_barang = $_POST['kd_barang'];
   $posisi = $_POST['posisi']; 
   rekap_stock_outgoing($posisi,$kd_barang); 
  break;

  case "show_detail_stock":
   $kd_barang = $_POST['kd_barang']; 
   //echo "string";
   $q = $db->query("select *,((masuk)-(keluar+keluar2)) as stock from v_rekap_stok_outgoing2 where ((jumlah+masuk)-(keluar+keluar2))>0 and kd_barang='$kd_barang' order by tgl_bpb asc"); 
  ?>
  <table class="table"> 
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
         <th>No Urut</th> 
       </tr>
     </thead>
     <tbody>
       <?php
       $total = 0;
       foreach ($q as $k) {
      //  $stock = $k->qtyin - $k->qtyout;
       // $stock = number_format(($k->qtyin - $k->qtyout),5,",",".");

        if ($k->stock>0) {
           echo "<tr>
               <td>$k->jenis_dokpab</td>
               <td>$k->kd_barang</td>
               <td>$k->nm_barang</td>
               <td>$k->no_dokpab</td>
               <td>$k->tgl_dokpab</td>
               <td>$k->no_aju</td>
               <td>$k->tgl_aju</td>
               <td>$k->tgl_bpb</td>
               <td>$k->lokasi</td>
               <td style='text-align:right'>".number_format($k->stock,5,",",".")."</td>
               <td>$k->satuan</td>
               <td>$k->no_urut</td>
             </tr>";
             $total = $total + $k->stock;
        }
     
      }
     ?>
     <tr>
       <td colspan="9">Total</td>
       <td style='text-align:right'><?= number_format($total,5,",",".") ?></td>
       <td></td><td></td>
     </tr>
     </tbody>
  </table>

   <?php
     # code...
     break;

  case "in":

  
  $data = array(
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => $_POST["nm_barang"],
      "Stock" => $_POST["Stock"],
      "satuan" => $_POST["satuan"],
      "nm_kategori" => $_POST["nm_kategori"],
      "kd_kategori" => $_POST["kd_kategori"],
  );
  
  
  
   
    $in = $db->insert("vtotalstockprodbb",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("vtotalstockprodbb","",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("vtotalstockprodbb","",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "kd_barang" => $_POST["kd_barang"],
      "nm_barang" => $_POST["nm_barang"],
      "Stock" => $_POST["Stock"],
      "satuan" => $_POST["satuan"],
      "nm_kategori" => $_POST["nm_kategori"],
      "kd_kategori" => $_POST["kd_kategori"],
   );
   
   
   

    
    
    $up = $db->update("vtotalstockprodbb",$data,"",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>