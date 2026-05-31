<?php
include "../../inc/config.php"; 
$info_pt = info_pt();
$q = $db->query("select s.*,p.nama as nama_penerima,p.npwp,p.no_izin,p.alamat from sales_order s left join penerima p on p.kode_penerima=s.kode_penerima where s.id_sales_order=?",array($_GET['id']));
foreach ($q as $k) {
  ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Proforma Invoice</title>
<style>
  body {
    font-family: Arial, sans-serif;
    font-size: 13px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
  }
  td, th {
    border: 1px solid black;
    padding: 4px;
    vertical-align: top;
  }
  .no-border td {
    border: none;
  }
  .center {
    text-align: center;
  }
  .right {
    text-align: right;
  }
  .bold {
    font-weight: bold;
  }
  .signature {
    height: 80px;
  }

  #invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
    font-family: Arial, sans-serif;
    font-size: 13px;
  }

  #invoice-table td, 
  #invoice-table th {
    border: none; /* Hilangkan garis */
    padding: 4px;
    vertical-align: top;
  }

  #invoice-table .center {
    text-align: center;
  }

  #invoice-table .right {
    text-align: right;
  }

  #invoice-table .bold {
    font-weight: bold;
  }
</style>

</head>
<body onload="window.print()">

<h2 class="center bold">PROFORMA INVOICE</h2>

<table id="invoice-table">
  <tr>
    <td style="width:10%;">NO.</td>
    <td colspan="7"></td>
  </tr>
  <tr>
    <td rowspan="4" style="width:10%;">MESSR.</td>
    <td colspan="3" rowspan="4"></td>
    <td colspan="4" class="center bold">PT GREEN AND BRIGHT INDONESIA</td>
  </tr>
  <tr><td colspan="4"></td></tr>
  <tr><td colspan="4"></td></tr>
  <tr><td colspan="4"></td></tr>
</table>

<table id="invoice-table">
  <tr>
    <td style="width:25%;">PURCHASE ORDER/REQUEST NO.<br> <?= $k->no_sales_order ?></td>
    <td style="width:25%;"></td>
    <td style="width:15%;">SHIPMENT :</td>
    <td style="width:15%;">TRUCKING</td>
    <td style="width:20%;">REMARK</td>
  </tr>
  <tr>
    <td>PAYMENT TERM<br> <?= $k->term ?></td>
    <td></td>
    <td>DESTINATION<br><?= $k->nama_penerima."<br>".$k->alamat ?></td>
    <td colspan="2"></td>
  </tr>
</table>

<table>
  <tr class="center bold">
    <td style="width:5%;">NUMBER(S)</td>
    <td style="width:45%;">DESCRIPTION OF GOODS</td>
    <td style="width:10%;">QTY ORDER</td>
    <td style="width:10%;">PRICE<br>(S/F)</td>
    <td style="width:15%;">AMOUNT</td>
    <td style="width:15%;">ETD</td>
  </tr>
    <?php  
       $qd = $db->query("select d.kd_barang,b.nm_barang,b.packing_size,packing,satuan,d.nilai,d.price, d.qty from sales_order_detail d join barang b on b.kd_barang=d.kd_barang  where d.id_sales_order=? ",array($_GET['id']));
       $no=1;
       $total = 0;
       foreach ($qd as $kd) {
     
       ?>
       <tr>
           <td class="center"><?= $no ?></td>
           <td><?= $kd->nm_barang ?></td>
           <td><?= $kd->qty ?></td>
           <td><?= $kd->price ?></td>
           <td><?= number_format($kd->price * $kd->qty) ?></td>
           <td></td>
         </tr>
  <?php
  $no++;
   $total = $total + ($kd->price * $kd->qty);
   } ?>
  <tr>
    <td colspan="3" class="right bold">TOTAL :</td>
    <td class="center">- S/F</td>
    <td class="right bold"><?= number_format($total) ?></td>
    <td></td>
  </tr>
</table>

<br>

<table>
  <tr>
    <td style="width:50%;" rowspan="3">
      <b>TERMS & CONDITION :</b><br><br>
     
    </td>
    <td class="center bold">SIGNATURE OF BUYER</td>
    <td class="center bold">SIGNATURE OF SELLER</td>
  </tr>
  <tr class="signature">
    <td></td>
    <td></td>
  </tr>
</table>

<br>

<table class="no-border">
  <tr>
    <td style="width:33%;">DATE :</td>
    <td style="width:33%;">PLACE OF ISSUE</td>
    <td style="width:33%;">DATE :   <?= tgl_indo($k->so_date) ?></td>
  </tr>
</table>

<p class="center"><b>E.&O.E AND SUBJECT TO OUR STANDARD TERMS OF COMMERCIAL PRACTICE</b></p>

</body>
</html>
<?php
}
?>
