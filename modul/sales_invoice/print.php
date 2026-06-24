<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
include "../../inc/config.php";
require_once __DIR__ . "/../print_pdf_helper.php";
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

ob_start();
foreach ($q as $k) { 
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?=erp_export_title('Invoice');?></title>

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
        <div class="title"><?=erp_export_title('INVOICE');?></div>
        <div class="info-box">
            <?=erp_export_label('Invoice No');?> : <?= $k->no_sales_invoice ?><br>
            <?=erp_export_label('Date');?> : <?= tgl_indo($k->invoice_date) ?><br>
            <?=erp_export_label('Payment Terms');?> : <?= $k->term ?><br>
            Shipping <?=erp_export_label('Date');?> : <?= $k->ship_date ?><br>
            <?=erp_export_label('Order No');?> : <?= $k->no_sales_order ?><br>
            <?=erp_export_label('PO No');?> : <?= $k->nopo ?><br>
            <?=erp_export_label('Currency');?> : <?= $k->valuta ?>
        </div>
    </div>
</div>

<!-- BILL / SHIP -->
<div class="section">
    <div class="box">
        <b><?=erp_export_label('Bill To');?></b><br>
        <?= $k->nama_penerima ?><br>
        <?= $k->alamat ?>
    </div>

    <div class="box">
        <b><?=erp_export_label('Ship To');?></b><br>
        <?= $k->nama_penerima ?><br>
        <?= $k->alamat ?>
    </div>
</div>

<!-- TABLE -->
<!-- TABLE -->
<table>
<tr class="bold center">
    <td>Material Code</td>
    <td><?=erp_export_label('Item Description');?></td>
  
    <td>Unit Price</td>
    <td><?=sd_h('sales_qty', 'Qty');?></td>
    <td style="width: 30px"><?=erp_export_label('UOM');?></td>
    <td><?=sd_h('sales_amount', 'Amount');?></td>
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

<!-- <?=erp_export_label('TOTAL');?> ROW -->

<tr class="bold">

    <td colspan="3" class="right">
        <?=erp_export_label('TOTAL');?>
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
        <?=erp_export_label('Note');?> : <br>
        <?= $k->catatan ?>
    </td>
    <td colspan="4">
       
    </td>
</tr> -->

</table>

<?php
$dasar_pengenaan = isset($k->net_amount) && (float)$k->net_amount > 0 ? (float)$k->net_amount : $total;
$pajak = isset($k->tax_amount) && (float)$k->tax_amount > 0 ? (float)$k->tax_amount : (($k->tax=='1') ? round($dasar_pengenaan * 0.11, 2) : 0);
$total_all = isset($k->gross_amount) && (float)$k->gross_amount > 0 ? (float)$k->gross_amount : ($dasar_pengenaan + $pajak);
?>
 <div class="section">
 <div class="box" style="width: 55%"> 
            <div style="border: 1px solid black;width: 90%;height: 102px">
                 <?=erp_export_label('Note');?> : <br>
        <?= $k->catatan ?>
            </div>
            <br>

            <?=erp_export_label('Please apply the payment to our bank account');?> :<br><br>

            <?= infokb()->bank ?> 
        </div>
<!-- SUMMARY -->
<div class="summary" style="width: 45%">
<table>

<tr><td style="width: 300px">Sub Total</td><td>:</td><td class="right"><label style="float: left;"><?= $k->valuta ?> </label> <?= formatAngka($total) ?></td></tr>
<tr><td>Dasar Pengenaan Pajak</td><td>:</td><td class="right"><label style="float: left;"><?= $k->valuta ?> </label> <?= formatAngka($dasar_pengenaan) ?></td></tr>
<tr><td><?=erp_export_label('PPN');?> <?= isset($k->tax_rate) ? formatAngka($k->tax_rate) : '11' ?>%</td><td>:</td><td class="right"><label style="float: left;"><?= $k->valuta ?> </label> <?= formatAngka($pajak) ?></td></tr>
<tr><td><b><?=sd_h('sales_total', 'Total');?></b></td><td>:</td><td class="right"><label style="float: left;"><?= $k->valuta ?> </label> <?= formatAngka($total_all) ?></td></tr>
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

<?php }
$html = ob_get_clean();
$invoiceNo = isset($k) ? $k->no_sales_invoice : ('sales_invoice_'.$id);
erpkb_pdf_output($html, 'sales_invoice_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)$invoiceNo).'.pdf', 'L');
?>
