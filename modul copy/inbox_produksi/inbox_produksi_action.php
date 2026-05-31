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
    $q = $db->query("
SELECT 
    d.no,
    dt.no_dokpab,
    dt.no_aju,
    b.nm_barang,
    t.no_transfer as no_spb,
    t.tgl_transfer as tgl_spb,
    b.kd_barang as kode,
    MAX(p.jenis_dokpab) as jenis_dokpab,
    IFNULL(MAX(rd.jumlah),0) as qtyro,
    abs(dt.qty) as jumlah,
    b.satuan,
    t.ket

FROM transfer t
JOIN transfer_detail d 
    ON d.id_transfer = t.id_transfer

JOIN barang b 
    ON b.id = d.id_barang

JOIN detail_transaksi dt 
    ON dt.no_ref = t.no_transfer
    AND dt.kd_barang = b.kd_barang
    AND dt.posisi = 'GUDANG' and dt.is_reversal='0'

LEFT JOIN pemasukan p 
    ON p.no_aju = dt.no_aju

LEFT JOIN ro 
    ON ro.no_ro = t.no_ro

LEFT JOIN ro_detail rd 
    ON rd.no_ro = ro.no_ro 
    AND rd.kode = b.kd_barang

WHERE t.no_transfer = '$no_spb'

GROUP BY 
    d.no,
    b.kd_barang,
    dt.no_aju,
    dt.no_dokpab

ORDER BY d.no, dt.no_aju
");
    $no = 0;
    foreach ($q as $k) {     
      if ($no!=$k->no) {
         $no = $k->no;
          echo "<tr>
             <td>$k->no</td>
           
             <td>$k->kode , $k->nm_barang</td>
             <td>$k->jenis_dokpab</td>
             <td>$k->no_dokpab</td>
             <td>$k->no_aju</td>
             <td>$k->qtyro</td>
             <td>".number_format($k->jumlah,2)."</td>
             <td>$k->satuan</td>
             <td>$k->ket</td>
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
$id     = $_POST['id'];

$no_lpb = GetNoTerima($id,5);

// ================= UPDATE HEADER =================
$db->query("
    UPDATE transfer 
    SET 
        status       = '1',
        no_terima    = '$no_lpb',
        tgl_terima   = '".date("Y-m-d H:i:s")."',
        user_terima  = '".$_SESSION['username']."'
    WHERE no_transfer = '$no_spb'
"); 

// ================= AMBIL DATA DARI LEDGER =================
$q = $db->query("
    SELECT 
        dt.kd_barang as kode,
        dt.no_dokpab,
        dt.no_aju,
        ABS(dt.qty) as jumlah
    FROM detail_transaksi dt
    WHERE dt.no_ref = '$no_spb'
      AND dt.posisi = 'GUDANG'
");

/// 🔥 ambil no_urut terakhir
$cek_urut = $db->query("
    SELECT IFNULL(MAX(no_urut),0) as urut
    FROM detail_transaksi
    WHERE no_ref = '$no_spb'
")->fetch();

$no_urut = $cek_urut->urut + 1; 

// ================= INSERT KE PRODUKSI =================
foreach ($q as $k) {

    // ================= LEDGER =================
    $db->insert("detail_transaksi", [
        'kd_barang'     => $k->kode,
        'qty'           => $k->jumlah,
        'posisi'        => 'PRODUKSI',
        'no_ref'        => $no_spb,
        'no_urut'       => $no_urut++, // 🔥 tambahan
        'move_code'     => '101',
        'no_dokpab'     => $k->no_dokpab,
        'no_aju'        => $k->no_aju,
        'remark'        => 'Terima dari transfer '.$no_spb,
        'posting_date'  => date("Y-m-d"),
        'document_date' => date("Y-m-d"),
        'created_by'    => $_SESSION['username'],
        'date_created'  => date("Y-m-d H:i:s")
    ]);

    // ================= 🔥 INSERT STOCK_LAYER =================
    $db->insert("stock_layer", [
        'kode'         => $k->kode,
        'qty_masuk'    => $k->jumlah,
        'qty_sisa'     => $k->jumlah,

        'no_aju'       => $k->no_aju, 
        'no_dokpab'    => $k->no_dokpab,
        'jenis_dokpab' => null,

        'lokasi'       => 'PRODUKSI', // 🔥 INI PENTING

        'ref_table'    => 'transfer',
        'ref_id'       => $id,

        'tgl_masuk'    => date("Y-m-d"),
        'created_at'   => date("Y-m-d H:i:s")
    ]);
   // echo $db->getErrorMessage();
}

// ================= LOG =================
$qlog = $db->query("
    SELECT tr.no_transfer, b.nm_bagian as dari
    FROM transfer tr
    JOIN bagian b ON b.id_bagian = tr.dari
    WHERE tr.no_transfer = '$no_spb'
");

foreach ($qlog as $k) {
    simpan_log(
        "Terima barang dari $k->dari dengan no Transfer $k->no_transfer",
        $_SESSION['username']
    );
}

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