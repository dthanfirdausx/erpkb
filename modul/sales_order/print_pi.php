<?php
include "../../inc/config.php"; 
$info_pt = info_pt();

$q = $db->query("
SELECT s.*,p.nama as nama_penerima,p.npwp,p.no_izin,p.alamat 
FROM sales_order s 
LEFT JOIN penerima p ON p.kode_penerima=s.kode_penerima 
WHERE s.id_sales_order=?",array($_GET['id']));

foreach ($q as $k) {
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Proforma Invoice</title>

<style>
body{
    font-family: Arial, sans-serif;
    font-size:12px;
    margin:0;
    background:#fff;
}
.container{
    width:190mm;
    margin:auto;
    border:2px solid #000;
}
.header{
    display:flex;
    align-items:center;
    padding:10px;
}
.logo{
    font-size:22px;
    font-weight:bold;
    color:#2b7bb9;
}
.title{
    flex:1;
    text-align:center;
    font-size:28px;
    font-weight:bold;
}
table{
    width:100%;
    border-collapse:collapse;
}
td, th{
    border:1px solid black;
    padding:5px;
    vertical-align:top;
}
.no-border td{
    border:none;
}
.center{text-align:center;}
.right{text-align:right;}
.bold{font-weight:bold;}
.big-row{height:70px;}
.signature{height:120px;}
</style>

</head>

<body onload="window.print()">

<div class="container">

<!-- HEADER -->
<div class="header">
    <div class="logo">GBLIGHT</div>
    <div class="title">PROFORMA INVOICE</div>
</div>

<!-- NO -->
<table>
<tr>
<td class="bold">NO.</td>
<td colspan="5"><?= $k->no_sales_order ?? '-' ?></td>
</tr>
</table>

<!-- ADDRESS -->
<table>
<tr>
<td width="50%">
<b>TO:</b><br><br>
<?= $k->nama_penerima ?><br>
<?= $k->alamat ?><br>
NPWP : <?= $k->npwp ?? '-' ?>
</td>

<td width="50%">
<b>PT. GREEN AND BRIGHT INDONESIA</b><br>
<?= $info_pt->alamat ?><br>
Karawang - Indonesia<br>
NPWP : 0609792072016000
</td>
</tr>
</table>

<!-- INFO -->
<table>
<tr>
<td>PURCHASE ORDER NO.</td>
<td>SHIPMENT MODE</td>
<td>REMARK</td>
</tr>

<tr class="big-row">
<td><?= $k->no_po ?></td>
<td>TRUCKING</td>
<td><?= $k->catatan ?? '' ?></td>
</tr>

<tr>
<td>PAYMENT TERM</td>
<td colspan="2">SHIPPING ADDRESS</td>
</tr>

<tr class="big-row">
<td><?= $k->term ?> Days</td>
<td colspan="2"><?= $k->alamat ?></td>
</tr>
</table>

<!-- ITEM -->
<table>
<tr class="center bold">
<td width="5%">NO</td>
<td width="40%">ITEM DESCRIPTION</td>
<td width="10%">QTY</td>
<td width="15%">PRICE</td>
<td width="15%">AMOUNT</td>
<td width="13%">ETD</td>
</tr>

<?php  
$qd = $db->query("
SELECT d.*,b.nm_barang,b.satuan 
FROM sales_order_detail d 
JOIN barang b ON b.kd_barang=d.kd_barang  
WHERE d.id_sales_order=?",array($_GET['id']));

$no=1;
$total=0;
$total_qty = 0;

foreach ($qd as $kd){

$amount = $kd->price * $kd->qty;
$total += $amount;
$total_qty += $kd->qty;
?>

<tr>
<td class="center"><?= $no ?></td>
<td><?= $kd->nm_barang ?></td>
<td class="right"><?= number_format($kd->qty,2) ?></td>
<td class="right"><label style="float: left;"><?php echo ($k->currency == 'USD') ? "$ " : "Rp."; ?></label><?= number_format($kd->price,2) ?></td>
<td class="right"><label style="float: left;"><?php echo ($k->currency == 'USD') ? "$ " : "Rp."; ?></label><?= number_format($amount,2) ?></td>
<td><?= $k->delivery_date ?></td>
</tr>
<?php 
$no++;
}

// ==================
// PPN LOGIC
// ==================
$ppn = 0;

if(strtolower($k->tax) == 'exclude'){
    $ppn = $total * 0.11;
}
?>

<!-- TOTAL -->
<!-- TOTAL -->
<tr class="bold">
<td colspan="2">TOTAL :</td>
<td class="right"><?= number_format($total_qty,2) ?></td>
<td></td>
<td class="right"><label style="float: left;"><?php echo ($k->currency == 'USD') ? "$ " : "Rp."; ?></label><?= number_format($total,2) ?></td>
<td></td>
</tr>

<?php if($ppn > 0){ ?>
<tr class="bold">
<td colspan="4">PPN 11%</td>
<td class="right"><label style="float: left;"><?php echo ($k->currency == 'USD') ? "$ " : "Rp."; ?></label><?= number_format($ppn,2) ?></td>
<td></td>
</tr>

<tr class="bold">
<td colspan="4">GRAND TOTAL</td>
<td class="right"><label style="float: left;"><?php echo ($k->currency == 'USD') ? "$ " : "Rp."; ?></label><?= number_format($total + $ppn,2) ?></td>
<td></td>
</tr>
<?php } ?>
</table>

<!-- TERBILANG -->
<table>
<tr>
<td width="30%">The Sum of (In words)</td>
<td><?php
echo ($k->currency == 'USD') ? penyebut_en($total + $ppn) : penyebut_id($total + $ppn);
 ?></td>
</tr>
</table>

<!-- SIGNATURE -->
<table>
<tr>
<td width="30%">SIGNATURE OF BUYER</td>

<td width="40%"> 
<b>Payment details :</b><br>
<?= $info_pt->bank ?>
<!-- <?= $info_pt->rek1 ?><br>
<?= $info_pt->rek2 ?><br> -->
</td>

<td width="30%">SIGNATURE OF SELLER</td>
</tr>

<tr>
<td class="signature">
Name:<br><br>
Title:<br><br>
DATE :
</td>

<td></td>

<td class="signature">
<b>SUNG KYEU SIN</b><br>
<b>DIRECTOR</b><br><br>
DATE : <?= tgl_indo($k->so_date) ?>
</td>
</tr>
</table>

</div>

</body>
</html>

<?php } ?>