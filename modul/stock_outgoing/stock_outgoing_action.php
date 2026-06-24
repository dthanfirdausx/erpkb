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

function stock_outgoing_h($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function stock_outgoing_locked_action()
{
  action_response('Aksi legacy stock outgoing dikunci. Data stock outgoing sekarang dibaca langsung dari stock_layer.');
}

switch ($_GET["act"]) {
  
  case "sinkron_stock":
   stock_outgoing_locked_action();
  break;

  case "show_detail_stock":
   $kd_barang = $_POST['kd_barang']; 
   //echo "string";
   $q = $db->query(
     "SELECT sl.jenis_dokpab,b.kd_barang,b.nm_barang,sl.no_dokpab,pd.tgl_dokpab,sl.no_aju,pd.tgl_aju,
             sl.tgl_masuk AS tgl_bpb,sl.lokasi,sl.qty_sisa AS stock,b.satuan,pd.no_urut
      FROM stock_layer sl
      INNER JOIN barang b ON b.kd_barang=sl.kode
      LEFT JOIN pemasukan_detail pd ON pd.id=sl.ref_id AND sl.ref_table='pemasukan_detail'
      WHERE sl.qty_sisa>0 AND sl.lokasi='OUTGOING' AND sl.kode=?
      ORDER BY sl.tgl_masuk ASC,sl.id ASC",
     array($kd_barang)
   ); 
  ?>
  <div class="table-responsive">
  <table class="table table-bordered table-striped"> 
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
               <td>".stock_outgoing_h($k->jenis_dokpab)."</td>
               <td>".stock_outgoing_h($k->kd_barang)."</td>
               <td>".stock_outgoing_h($k->nm_barang)."</td>
               <td>".stock_outgoing_h($k->no_dokpab)."</td>
               <td>".stock_outgoing_h($k->tgl_dokpab)."</td>
               <td>".stock_outgoing_h($k->no_aju)."</td>
               <td>".stock_outgoing_h($k->tgl_aju)."</td>
               <td>".stock_outgoing_h($k->tgl_bpb)."</td>
               <td>".stock_outgoing_h($k->lokasi)."</td>
               <td style='text-align:right'>".number_format($k->stock,5,",",".")."</td>
               <td>".stock_outgoing_h($k->satuan)."</td>
               <td>".stock_outgoing_h($k->no_urut)."</td>
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
  </div>

   <?php
     # code...
     break;

  case "in":
    stock_outgoing_locked_action();
    break;
  case "delete":
    stock_outgoing_locked_action();
    break;
   case "del_massal":
    stock_outgoing_locked_action();
    break;
  case "up":
    stock_outgoing_locked_action();
    break;
  default:
    # code...
    break;
}

?>
