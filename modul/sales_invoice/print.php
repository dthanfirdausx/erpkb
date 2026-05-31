<?php
include "../../inc/config.php";
$info_pt = infokb();

$q = $db->query("
SELECT u.nama_valas, so.shipping_address, s.*, 
       p.nama as nama_penerima,p.alamat
FROM sales_invoice s 
LEFT JOIN penerima p ON p.kode_penerima=s.bill_to
LEFT JOIN sales_order so ON so.no_sales_order=s.no_sales_order
LEFT JOIN matauang u ON u.jenis_valas=s.valuta 
WHERE s.id_sales=? LIMIT 1",array($_GET['id'])
);

foreach ($q as $k) { 
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Invoice</title>

<style>
body{
    font-family: Arial;
    font-size:12px;
}
.container{
    width:1000px;
    margin:auto;
    border:2px solid #000;
    padding:10px;
}
.header{
    display:flex;
    justify-content:space-between;
}
.logo{
    font-size:22px;
    font-weight:bold;
    color:#2b7bb9;
}
.title{
    text-align:right;
    font-size:22px;
    font-weight:bold;
}
.info-box{
    border:1px solid #000;
    padding:5px;
    width:300px;
}
.section{
    display:flex;
    margin-top:10px;
}
.box{
    width:50%;
}
table{
    width:100%;
    border-collapse:collapse;
}
td,th{
    border:1px solid black;
    padding:5px;
}
.right{text-align:right;}
.center{text-align:center;}
.bold{font-weight:bold;}
.summary{
    width:300px;
    float:right;
}
</style>

</head>

<body onload="window.print()">

<div class="container">

<!-- HEADER -->
<div class="header">
    <div style="width: 400px">
        <div class="logo"><?= $info_pt->nama ?></div>
        <?= $info_pt->alamat ?>
    </div>

    <div>
        <div class="title">INVOICE</div>
        <div class="info-box">
            Invoice No : <?= $k->no_sales_invoice ?><br>
            Date : <?= tgl_indo($k->invoice_date) ?><br>
            Payment Terms : <?= $k->term ?><br>
            Shipping Date : <?= $k->ship_date ?><br>
            Order No : <?= $k->no_sales_order ?><br>
            PO No : <?= $k->nopo ?><br>
            Currency : <?= $k->valuta ?>
        </div>
    </div>
</div>

<!-- BILL / SHIP -->
<div class="section">
    <div class="box">
        <b>Bill To</b><br>
        <?= $k->nama_penerima ?><br>
        <?= $k->alamat ?>
    </div>

    <div class="box">
        <b>Ship To</b><br>
        <?= $k->nama_penerima ?><br>
        <?= $k->alamat ?>
    </div>
</div>

<!-- TABLE -->
<!-- TABLE -->
<table>
<tr class="bold center">
    <td>Material Code</td>
    <td>Item Description</td>
  
    <td>Unit Price</td>
    <td>Qty</td>
    <td style="width: 30px">UoM</td>
    <td>Amount</td>
    <th style="width: 100px">Material Number</th>
    <th style="width: 150px">Material Description</th>
</tr>

<?php

$total = 0;
$total_qty = 0;

$qd = $db->query("
SELECT d.*, b.nm_barang, b.satuan 
FROM sales_invoice_detail d
JOIN barang b ON b.kd_barang=d.kd_barang
WHERE d.id_sales=?",array($_GET['id'])
);

foreach ($qd as $d){

$total += $d->nilai;
$total_qty += $d->qty;

?>

<tr>
    <td><?= $d->kd_barang ?></td>

    <td><?= $d->nm_barang ?></td>
 
    <td class="right">
        <label style="float:left;">
            <?= $k->valuta ?>
        </label>

        <?= formatAngka($d->harga) ?>
    </td>

    <td class="center">
        <?= formatAngka($d->qty) ?>
    </td>

    <td class="center">
        <?= $d->unit ?>
    </td>

    <td class="right">
        <label style="float:left;">
            <?= $k->valuta ?>
        </label>

        <?= formatAngka($d->nilai) ?>
    </td>

    <td><?= $d->material_number ?></td>

    <td><?= $d->material_description ?></td>
</tr>

<?php } ?>

<!-- TOTAL ROW -->

<tr class="bold">

    <td colspan="3" class="right">
        TOTAL
    </td>

    <td class="center">
        <?= formatAngka($total_qty) ?>
    </td>

    <td></td>

    <td class="right">

        <label style="float:left;">
            <?= $k->valuta ?>
        </label>

        <?= formatAngka($total) ?>

    </td>

    <td colspan="2"></td>

</tr>
<!-- <tr>
    <td colspan="4">
        Note : <br>
        <?= $k->catatan ?>
    </td>
    <td colspan="4">
       
    </td>
</tr> -->

</table>

<?php
$dasar_pengenaan = $total * 11/12;
$pajak = ($k->tax=='1') ? (0.12*$dasar_pengenaan) : 0;
$total_all = $total + $pajak;
?>
 <div class="section">
 <div class="box" style="width: 55%"> 
            <div style="border: 1px solid black;width: 90%;height: 102px">
                 Note : <br>
        <?= $k->catatan ?>
            </div>
            <br>

            Please apply the payment to our bank account :<br><br>

            <?= infokb()->bank ?> 
        </div>
<!-- SUMMARY -->
<div class="summary" style="width: 45%">
<table>

<tr><td style="width: 300px">Sub Total</td><td>:</td><td class="right"><label style="float: left;"><?= $k->valuta ?> </label> <?= formatAngka($total) ?></td></tr>
<tr><td>Dasar Pengenaan Pajak = Jumlah Harga Jual X 11/12 </td><td>:</td><td class="right"><label style="float: left;"><?= $k->valuta ?> </label> <?= formatAngka($dasar_pengenaan) ?></td></tr>
<tr><td>PPN = 12% X Dasar Pengenaan Pajak</td><td>:</td><td class="right"><label style="float: left;"><?= $k->valuta ?> </label> <?= formatAngka($pajak) ?></td></tr>
<tr><td><b>Total</b></td><td>:</td><td class="right"><label style="float: left;"><?= $k->valuta ?> </label> <?= formatAngka($total_all) ?></td></tr>
</table>
</div>
</div>

<!-- FOOTER -->
<div style="margin-top:50px;">
Regards,<br><br><br><br><br><br><br>
<b><?= $k->ttd ?></b>
</div>

</div>

</body>
</html>

<?php } ?>