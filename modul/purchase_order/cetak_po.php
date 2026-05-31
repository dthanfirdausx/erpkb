<?php
session_start();
include "../../inc/config.php"; 

// ambil id PO dari URL
$id = isset($_GET['po_no']) ? intval($_GET['po_no']) : 0;

// ambil header PO
$po = $db->fetch_single_row("purchase_order", "id", $id);
//print_r($po);

// ambil detail PO
$details = $db->query("SELECT * FROM purchase_order_detail WHERE  po_no = ?", array($po->purchase_order_no));

?>
<!DOCTYPE html>
<html lang="id"> 
<head>
  <meta charset="UTF-8">
  <title>Cetak Purchase Order</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
    .po-container { width: 100%; border-collapse: collapse; border: 1px solid #333; }
    .po-container th, .po-container td { border: 1px solid #999; padding: 6px; vertical-align: top; }
    .po-container2 th, .po-container2 td { border: 0px; padding: 1px; vertical-align: top; }
    .header { text-align: center; font-weight: bold; font-size: 18px; padding: 0px; position: relative;left: -5%}
    .section-title { background: #f0f0f0; font-weight: bold; }
    .right { text-align: right; }
    .center { text-align: center; }
  </style>
   <script>
     window.onload = function() {
       window.print();
     };
  </script> 
</head>
<body>

<table class="po-container">
  <tr>
    <td colspan="2" valign="center">
      <img src="../../../../inkaber/logogblight.jpeg" alt="GBLIGHT" style="width:100px;float: left;"><br>
      <div class="header">Purchase Order</div>
    </td>
  </tr>
  <tr>
    <td style="width: 50%">
      <table class="po-container2">
        <tr><td>Address</td><td>Jl. TB. Simatupang no Kav. 88 Kebagusan, Pasar Minggu</td></tr>
        <tr><td></td><td>Jakarta - Selatan Indonesia</td></tr>
        <tr><td>PHONE</td><td></td></tr>
        <tr><td>EMAIL</td><td>rita@gblight.com , sk.shin@gblight.com</td></tr>
      </table>
    </td>
    <td>
      <table class="po-container2">
        <tr><td><b>DATE</b></td><td><?= htmlspecialchars(tgl_indo($po->po_date)) ?></td></tr>
        <tr><td><b>PURCHASE ORDER NO.</b></td><td><?= htmlspecialchars($po->purchase_order_no) ?></td></tr>
        <tr><td><b>CUSTOMER ID</b></td><td><?= htmlspecialchars($po->customer_id) ?></td></tr>
        <tr><td><b>DELIVERY DATE (ETD)</b></td><td><?= htmlspecialchars(tgl_indo($po->delivery_date)) ?></td></tr>
        <tr><td><b>ARRIVAL DATE (ETA)</b></td><td><?= htmlspecialchars(tgl_indo($po->arrival_date)) ?></td></tr>
        <tr><td><b>Shipped Via</b></td><td><?= htmlspecialchars($po->shipped_via) ?></td></tr>
        <tr><td><b>Delivery Term</b></td><td><?= htmlspecialchars($po->delivery_term) ?></td></tr>
        <tr><td><b>Payment Term</b></td><td><?= htmlspecialchars($po->payment_term) ?></td></tr>
        <tr><td><b>Currency</b></td><td><?= htmlspecialchars($po->currency) ?></td></tr>
      </table>
    </td>
  </tr>
  <tr>
    <td>
      <div class="section-title">VENDOR</div>
      <table class="po-container2">
        <tr><td><b>Seller Name</b></td><td><?= htmlspecialchars($po->seller_name) ?></td></tr>
        <tr><td><b>Address</b></td><td><?= htmlspecialchars($po->seller_address) ?></td></tr>
        <tr><td><b>Phone</b></td><td><?= htmlspecialchars($po->seller_phone) ?></td></tr>
        <tr><td><b>PIC</b></td><td><?= htmlspecialchars($po->seller_pic) ?></td></tr>
        <tr><td><b>Email</b></td><td><?= htmlspecialchars($po->seller_email) ?></td></tr>
      </table>
    </td>
    <td>
      <div class="section-title">SHIP TO</div>
      <table class="po-container2">
        <tr><td><b>Consignee</b></td><td><?= htmlspecialchars($po->consignee_name) ?></td></tr>
        <tr><td><b>ADDRESS</b></td><td><?= htmlspecialchars($po->consignee_address) ?></td></tr>
        <tr><td><b>PHONE</b></td><td><?= htmlspecialchars($po->consignee_phone) ?></td></tr>
        <tr><td><b>EMAIL</b></td><td><?= htmlspecialchars($po->consignee_email) ?></td></tr>
      </table>
    </td>
  </tr>
</table>

<br>

<table class="po-container table-items">
  <tr>
    <th>ITEM NO.</th>
    <th>DESCRIPTION</th>
    <th>SPEC</th>
    <th>QTY</th>
    <th>UOM</th>
    <th>UNIT PRICE</th>
    <th>TOTAL</th>
  </tr>
  <?php 
  $no = 1; $subtotal = 0;
  foreach ($details as $d): 
    $total = $d->qty * $d->harga;
    $subtotal += $total;
 
  ?>
  <tr>
    <td class="center"><?= $no++ ?></td>
    <td><?= htmlspecialchars($d->nama_barang) ?></td>
    <td><?= htmlspecialchars($d->spec) ?></td>
    <td class="center"><?= number_format($d->qty,0,",",".") ?></td>
    <td class="center"><?= htmlspecialchars($d->unit) ?></td>
    <td class="right"><?= number_format($d->harga,2,",",".") ?></td>
    <td class="right"><?= number_format($total,2,",",".") ?></td>
  </tr>
  <?php endforeach; 
   $nilai_pajak = 0;
   $nilai_pajak2 = 1;
  if ($po->pajak=='ya') { 
    $nilai_pajak = 0.11;
    $nilai_pajak2 = 1.11;
  }
  ?>
  <tr>
    <td colspan="5" style="text-align: left">SUBTOTAL</td>
    <td colspan="2" class="right"><?= number_format($subtotal,2,",",".") ?></td>
  </tr>
  <tr>
    <td colspan="5" style="text-align: left">TAX (11%)</td>
    <td colspan="2" class="right"><?= number_format($subtotal*$nilai_pajak,2,",",".") ?></td>
  </tr>
  <tr>
    <td colspan="5" style="text-align: left"><b>TOTAL</b></td>
    <td colspan="2" class="right"><b><?= number_format($subtotal*$nilai_pajak2,2,",",".") ?></b></td>
  </tr>
  <tr>
    <td colspan="7" style="height: 80px">
      Note:<br>
      <?= $po->catatan ?>
    </td>
  </tr>
</table>

<br>

<table class="po-container">
  <tr>
    <td class="center"><b>ORDER APPROVED BY</b><br><br><br><br><br><br><br>Signature</td>
    <td class="center"><b>REQUESTED BY</b><br><br><br><br><br><br><br>Signature</td>
  </tr>
</table>

</body>
</html>
