<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
session_start();
include "../../inc/config.php";
session_check_json();

function stock_pemasukan_h($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function stock_pemasukan_locked_action()
{
  action_response('Aksi legacy stock pemasukan dikunci. Stock overview sekarang dibaca langsung dari v_stock_transaksi/stock_layer.');
}

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
  $q = $db->query("
    SELECT sl.jenis_dokpab,sl.kode kd_barang,b.nm_barang,sl.no_dokpab,pd.tgl_dokpab,sl.no_aju,pd.tgl_aju,
           sl.tgl_masuk tgl_bpb,sl.lokasi,b.satuan,sl.qty_sisa stock
    FROM stock_layer sl
    LEFT JOIN barang b ON b.kd_barang=sl.kode
    LEFT JOIN pemasukan_detail pd ON pd.id=sl.ref_id AND sl.ref_table='pemasukan_detail'
    WHERE sl.qty_sisa>0 AND sl.lokasi='GUDANG'
    ORDER BY sl.kode ASC, sl.tgl_masuk ASC, sl.jenis_dokpab ASC
  ");
  foreach ($q as $kk) {
        echo "<tr>
               <td>".stock_pemasukan_h($kk->jenis_dokpab)."</td>
               <td>".stock_pemasukan_h($kk->kd_barang)."</td>
               <td>".stock_pemasukan_h($kk->nm_barang)."</td>
               <td>'".stock_pemasukan_h($kk->no_dokpab)."</td>
               <td>".stock_pemasukan_h($kk->tgl_dokpab)."</td>
               <td>'".stock_pemasukan_h($kk->no_aju)."</td>
               <td>".stock_pemasukan_h($kk->tgl_aju)."</td>
               <td>".stock_pemasukan_h($kk->tgl_bpb)."</td>
               <td>".stock_pemasukan_h($kk->lokasi)."</td>
               <td style='text-align:right'>".number_format($kk->stock,5,",",".")."</td>
               <td>".stock_pemasukan_h($kk->satuan)."</td>
             
             </tr>"; 
  }
   ?>
    
     </tbody> 
   </table>
   <?php
    break;

   case "sinkron_stock":
   stock_pemasukan_locked_action();
     break;

     case "show_detail_stock":

$kd_barang = $_POST['kd_barang'];

?>
<div class="table-responsive">
<table class="table table-bordered table-striped so-detail-table">
    <thead>
        <tr>
            <th><?=wh_h(wh_t('table_no', 'No'));?></th>
            <th>Kode</th>
            <th>No Aju</th>
            <th>No Dokpab</th>
            <th>Jenis Dok</th>
            <th>Area</th>
            <th><?=wh_h(wh_t('common_plant', 'Plant'));?></th>
            <th><?=wh_h(wh_t('warehouse_storage_location', 'Storage Location'));?></th>
            <th><?=wh_h(wh_t('warehouse_storage_bin', 'Storage Bin'));?></th>
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
        b.satuan,
        p.plant_code,
        s.storage_code,
        s.storage_name,
        bin.bin_code,
        bin.bin_name
    FROM stock_layer sl
    LEFT JOIN barang b
        ON b.kd_barang = sl.kode
    LEFT JOIN erp_plant p
        ON p.id = sl.plant_id
    LEFT JOIN erp_storage_location s
        ON s.id = sl.storage_location_id
    LEFT JOIN erp_storage_bin bin
        ON bin.id = sl.storage_bin_id
    WHERE
        sl.kode = ?
        AND sl.lokasi = 'GUDANG'

    ORDER BY
        sl.tgl_masuk ASC,
        sl.id ASC
", array('kd_barang' => $kd_barang));

$no = 1;
$total_masuk = 0;
$total_sisa  = 0;

foreach ($q as $k){

    echo "<tr>
        <td>".(int)$no."</td>
        <td>".stock_pemasukan_h($k->kode.' , '.$k->nm_barang)."</td>
        <td>".stock_pemasukan_h($k->no_aju)."</td>
        <td>".stock_pemasukan_h($k->no_dokpab)."</td>
        <td>".stock_pemasukan_h($k->jenis_dokpab)."</td>
        <td>".stock_pemasukan_h($k->lokasi)."</td>
        <td>".stock_pemasukan_h($k->plant_code)."</td>
        <td>".stock_pemasukan_h(trim($k->storage_code.' - '.$k->storage_name, ' -'))."</td>
        <td>".stock_pemasukan_h(trim($k->bin_code.' - '.$k->bin_name, ' -'))."</td>
        <td>".stock_pemasukan_h($k->tgl_masuk)."</td>
        <td style='text-align:right'>".number_format($k->qty_masuk,4)."</td>
        <td style='text-align:right'>".number_format($k->qty_sisa,4)."</td>
      
    </tr>";

    $total_masuk += $k->qty_masuk;
    $total_sisa  += $k->qty_sisa;

    $no++;
}
?>

<tr style="font-weight:bold;background:#f1f1f1">
    <td colspan="10" style="text-align:center">TOTAL</td>
    <td style="text-align:right"><?= number_format($total_masuk,4) ?></td>
    <td style="text-align:right"><?= number_format($total_sisa,4) ?></td>
</tr>

    </tbody>
</table>
</div>
<?php

break;

  
  case "in":
    stock_pemasukan_locked_action();
    break;
  case "delete":
    stock_pemasukan_locked_action();
    break;
   case "del_massal":
    stock_pemasukan_locked_action();
    break;
  case "up":
    stock_pemasukan_locked_action();
    break;
  default:
    # code...
    break;
}

?>
