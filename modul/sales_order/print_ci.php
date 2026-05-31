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
  <title>Commercial Invoice</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
      margin: 20px;
    }
    table {
      border-collapse: collapse;
      width: 100%;
    }
    th, td {
      border: 1px solid #000;
      padding: 6px;
      vertical-align: top;
    }
    .no-border td {
      border: none;
    }
    .header {
      font-weight: bold;
      font-size: 16px;
      text-align: left;
    }
    .section-title {
      background: #f0f0f0;
      font-weight: bold;
      text-align: center;
    }
    .right {
      text-align: right;
    }
  </style>
  <script type="text/javascript">
    window.onload = function() {
      window.print(); // otomatis jalankan print saat halaman load
    }
  </script>
</head>
<body>

<table>
  <tr>
    <td colspan="2" class="header">COMMERCIAL INVOICE</td>
  </tr>
  <tr>
    <td>
      <b>Shipper/Exporter</b><br>
      <b>PT GREEN AND BRIGHT INDONESIA</b><br>
      Kawasan Artha Industrial Hill Blok E no.8, Wanajaya, Kec. Telukjambe Bar.,<br>
      Karawang, Jawa Barat 41361<br>
      TEL: ___________ &nbsp; FAX: ___________
    </td>
    <td>
      <b>Invoice No. and Date</b><br> 
      <?= $k->no_sales_order ?>
      <br>
      <?= tgl_indo($k->so_date) ?>
    </td>
  </tr>
  <tr>
    <td>
      <b>Consignee</b><br>
      <?=  $k->consignee ?><br><br><br><br>
    </td>
    <td>
      <b>Buyer (if other than consignee)</b><br>
      <?=  $k->nama_penerima."<br>".$k->alamat ?>
      <br><br><br><br>
    </td>
  </tr>
  <tr>
    <td>
      <b>Notify Party</b><br><?=  $k->notify_party ?><br><br>
    </td>
    <td>
      <b>Other Reference</b><br><?=  $k->other_reference ?><br><br>
    </td>
  </tr>
  <tr>
    <td>
      <b>Departure Date</b>: <?=  tgl_indo($k->delivery_date) ?> <br>
      <b>Vessel or Flight</b>: <?=  $k->vessel ?><br>
      <b>From</b>: <?=  $k->dari ?><br>
      <b>To</b>: <?=  $k->ke ?>
    </td>
    <td>
      <b>Delivery Term</b>: <?= $k->delivery_term ?><br>
      <b>Payment Term</b>: <?= $k->term ?>
    </td>
  </tr>
</table>

<br>

<table>
  <tr class="section-title">
    <td style="width: 15%">MATERIAL CODE</td>
    <td style="width: 35%">DESCRIPTION</td>
    <td style="width: 10%">QTY</td>
    <td style="width: 10%">UNIT</td>
    <td style="width: 15%">PRICE</td>
    <td style="width: 15%">AMOUNT</td>
  </tr>
  <?php  
       $qd = $db->query("select d.ket, d.kd_barang,b.nm_barang,b.packing_size,packing,satuan,d.nilai,d.price, d.qty from sales_order_detail d join barang b on b.kd_barang=d.kd_barang  where d.id_sales_order=? ",array($_GET['id']));
       $no=1;
       $total = 0;
       foreach ($qd as $kd) {
     
       ?>
  <tr>
    <td><?= $kd->ket ?></td>
    <td><?= $kd->nm_barang ?></td>
    <td style="text-align: right;"><?= number_format($kd->qty,4) ?></td>
    <td><?= $kd->satuan ?></td>
    <td><label style="float: left;"><?= $k->currency ?></label><label style="float: right;"><?= number_format($kd->price,2) ?></label></td>
    <td><label style="float: left;"><?= $k->currency ?></label><label style="float: right;"><?= number_format(($kd->price * $kd->qty),2) ?></label></td>
  </tr>
  <?php
  $total = $total + ($kd->price * $kd->qty);
}
if ($k->tax=='exclude') {
 ?>
 <tr>
    <td colspan="2" class="right"><b>TAX (11%)</b></td>
    <td></td>
    <td></td>
    <td></td>
    <td><label style="float: left;"><?= $k->currency ?></label><label style="float: right;"><?= number_format(($total*0.11),2) ?></label></td>
  </tr>
 <?php
 $total  = $total + ($total*0.11);
}
  ?>
  <tr>
    <td colspan="2" class="right"><b>TOTAL</b></td>
    <td></td>
    <td></td>
    <td></td>
    <td><label style="float: left;"><?= $k->currency ?></label><label style="float: right;"><?= number_format($total,2) ?></label></td>
  </tr>
</table>

<br><br>

<table class="no-border">
  <tr>
    <td style="border: none;">Signed by :</td>
  </tr>
</table>

</body>
</html>
<?php
}
?>