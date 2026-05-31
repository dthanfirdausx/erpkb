<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {

   case "download_excel_brg_jadi":
  header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
  header("Content-Disposition: attachment; filename=rekap_stock_brg_jadi.xls");  //File name extension was wrong
  header("Expires: 0");
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header("Cache-Control: private",false);
  ?>
  <h3 style="text-align: center">Rekap Stock Barang Jadi</h3>
  <table class="table" border="1"> 
     <thead>
       <tr>
      
         <th>Kode Barang</th>
         <th>Nama Barang</th>
         <th>Satuan</th>
        <!--  <th>No Dokpab</th>
         <th>Tanggal Dokpab</th>
         <th>No Aju</th> 
         <th>Tanggal Aju</th>
         <th>Tanggal Masuk</th>
         <th>Lokasi</th> -->
         <th>Stock</th>
        
       </tr>
     </thead>
     <tbody>
  <?php
  $q = $db->query("select `b`.`kd_kategori` AS `kd_kategori`,`b`.`id` AS `id`,`b`.`id` AS `id_barang`,`b`.`kd_barang` AS `kd_barang`,`b`.`nm_barang` AS `nm_barang`,round(ifnull((select sum(`brgjadi_detail`.`jumlah`) from `brgjadi_detail` where `brgjadi_detail`.`kode` = `b`.`kd_barang`),0),2) - round(ifnull((select sum(`pengeluaran_detail`.`jumlah`) from `pengeluaran_detail` where `pengeluaran_detail`.`kode` = `b`.`kd_barang`),0),2) AS `Stock`,`b`.`satuan` AS `satuan`,`k`.`nm_kategori` AS `nm_kategori` from (`barang` `b` join `kategori` `k` on(`k`.`kd_kategori` = `b`.`kd_kategori`)) where (`b`.`kd_kategori` = 'K02' or `b`.`kd_kategori` = 'K07' ) and round(ifnull((select sum(`brgjadi_detail`.`jumlah`) from `brgjadi_detail` where `brgjadi_detail`.`kode` = `b`.`kd_barang`),0),2) - round(ifnull((select sum(`pengeluaran_detail`.`jumlah`) from `pengeluaran_detail` where `pengeluaran_detail`.`kode` = `b`.`kd_barang`),0),2) > 0 ");
  foreach ($q as $kk) {
    // $qq = $db->query("select jenis_dokpab,kd_barang,nm_barang,no_dokpab,tgl_dokpab,no_aju,tgl_aju,tgl_bpb,lokasi,satuan,((jumlah+masuk)-keluar) as stock from v_stock_pemasukan where ((jumlah+masuk)-keluar)>0 and kd_barang='$k->kd_barang' order by tgl_bpb asc");
    // foreach ($qq as $kk) {
        echo "<tr>
            
               <td>$kk->kd_barang</td>
               <td>$kk->nm_barang</td>
                <td>$kk->satuan</td>
              
               <td style='text-align:right'>".number_format($kk->Stock,5,",",".")."</td>
              
             
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

   case "download_excel":
  header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
  header("Content-Disposition: attachment; filename=rekap_stock_bahan_baku_produksi.xls");  //File name extension was wrong
  header("Expires: 0");
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header("Cache-Control: private",false);
  ?>
  <h3 style="text-align: center">Rekap Stock Bahan Baku Produksi</h3>
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
  $q = $db->query("select jenis_dokpab,kd_barang,nm_barang,no_dokpab,tgl_dokpab,no_aju,tgl_aju,tgl_bpb,lokasi,satuan,no_urut ,(masuk-keluar) as stock from v_rekap_stok_produksi where (masuk-keluar)>0  order by kd_barang asc,tgl_bpb asc ");
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
   rekap_stock_produksi($posisi,$kd_barang); 
  break;

  case "show_detail_stock":

$kd_barang = $_POST['kd_barang'];

// 🔥 cek kategori
$kat = $db->query("
    SELECT kd_kategori 
    FROM barang 
    WHERE kd_barang = '$kd_barang'
")->fetch();

if (in_array($kat->kd_kategori, ['K02','K07'])) {

    // ================= PRODUKSI DETAIL =================
   $q = $db->query("
    SELECT 
        h.no_bpb,
        h.tgl_bpb,

        bj.kode as kd_barang_jadi,
        b1.nm_barang as nm_barang_jadi,

        -- 🔥 AMBIL STOCK REAL
        SUM(sl.qty_sisa) as qty_hasil,

        bb.kode as kd_bahan,
        b2.nm_barang as nm_bahan,
        bb.jumlah as qty_bahan,

        bb.no_aju,
        bb.no_dokpab

    FROM brgjadi h

    JOIN brgjadi_detail bj 
        ON bj.id_produksi = h.id_produksi

    -- 🔥 PAKAI STOCK_LAYER
    LEFT JOIN stock_layer sl 
        ON sl.kode = bj.kode
        AND sl.ref_id = h.id_produksi
        AND sl.lokasi = 'PRODUKSI'

    LEFT JOIN bahanbaku_detail bb 
        ON bb.id_produksi_detail = bj.id_produksi_detail

    LEFT JOIN barang b1 
        ON b1.kd_barang = bj.kode

    LEFT JOIN barang b2 
        ON b2.kd_barang = bb.kode

    WHERE bj.kode = '$kd_barang'

    GROUP BY 
        h.no_bpb,
        bj.kode,
        bb.kode,
        bb.no_aju,
        bb.no_dokpab

    HAVING SUM(sl.qty_sisa) > 0

    ORDER BY h.tgl_bpb ASC
");

    $current_bpb = "";
?>

<table class="table table-bordered table-striped">
<thead>
<tr style="background:#f5f5f5">
    <th>No BPB</th>
    <th>Tanggal</th>
    <th>Jenis</th>
    <th>Kode Barang</th>
    <th>Nama Barang</th>
    <th style="text-align:right">Qty</th>
    <th>No Aju</th>
    <th>No Dokpab</th>
</tr>
</thead>
<tbody>

<?php
foreach ($q as $k){

    // 🔥 HEADER HASIL
    if ($current_bpb != $k->no_bpb){

        echo "<tr style='background:#d9edf7;font-weight:bold'>
            <td>$k->no_bpb</td>
            <td>$k->tgl_bpb</td>
            <td>HASIL</td>
            <td>$k->kd_barang_jadi</td>
            <td>$k->nm_barang_jadi</td>
            <td style='text-align:right'>".number_format($k->qty_hasil,2)."</td>
            <td>-</td>
            <td>-</td>
        </tr>";

        $current_bpb = $k->no_bpb;
    }

    // 🔥 DETAIL BAHAN (EXBC)
    if (!empty($k->kd_bahan)){

        echo "<tr>
            <td></td>
            <td></td>
            <td>BAHAN</td>
            <td>$k->kd_bahan</td>
            <td>$k->nm_bahan</td>
            <td style='text-align:right'>".number_format($k->qty_bahan,2)."</td>
            <td>$k->no_aju</td>
            <td>$k->no_dokpab</td>
        </tr>";
    }
}
?>

</tbody>
</table>

<?php

} else {

    // ================= STOCK NORMAL =================
    $q = $db->query("
        SELECT 
            COALESCE(p.jenis_dokpab, '-') as jenis_dokpab,
            dt.kd_barang,
            b.nm_barang,
            dt.no_dokpab,
            dt.posting_date as tgl_dokpab,
            dt.no_aju,
            dt.posting_date as tgl_aju,
            dt.posting_date as tgl_bpb,
            dt.posisi as lokasi,
            b.satuan,
            SUM(dt.qty) as stock

        FROM detail_transaksi dt

        LEFT JOIN pemasukan p 
            ON p.no_aju = dt.no_aju

        LEFT JOIN barang b 
            ON b.kd_barang = dt.kd_barang

        WHERE dt.kd_barang = '$kd_barang'
        AND dt.is_reversal = '0'
        AND dt.posisi='PRODUKSI'

        GROUP BY 
            dt.kd_barang,
            dt.no_aju,
            dt.no_dokpab,
            dt.posisi

        HAVING SUM(dt.qty) > 0

        ORDER BY dt.posting_date ASC
    ");
?>

<table class="table"> 
<thead>
<tr>
    <th>Jenis Dokpab</th>
    <th>Kode Barang</th>
    <th>Nama Barang</th>
    <th>No Dokpab</th>
    <th>Tanggal</th>
    <th>No Aju</th>
    <th>Lokasi</th>
    <th>Stock</th>
</tr>
</thead>
<tbody>

<?php
$total = 0;

foreach ($q as $k){

    echo "<tr>
        <td>$k->jenis_dokpab</td>
        <td>$k->kd_barang</td>
        <td>$k->nm_barang</td>
        <td>$k->no_dokpab</td>
        <td>$k->tgl_dokpab</td>
        <td>$k->no_aju</td>
        <td>$k->lokasi</td>
        <td style='text-align:right'>".number_format($k->stock,2)."</td>
    </tr>";

    $total += $k->stock;
}
?>

<tr>
    <td colspan="7"><b>Total</b></td>
    <td style="text-align:right"><b><?= number_format($total,2) ?></b></td>
</tr>

</tbody>
</table>

<?php
}

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