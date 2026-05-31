<?php
session_start();
include "../../inc/config.php";

// Ambil parameter PO No dari URL
$po_no = isset($_GET['po_no']) ? $_GET['po_no'] : '';

if (empty($po_no)) {
    die("P/O No tidak ditemukan.");
}

// Ambil data header
$stmt_po = $mysqli->prepare("
    SELECT po_no, season, po_date, supplier, supplier_address, issue_by, trade_terms, payment
    FROM purchase_order
    WHERE id = ?
");
$stmt_po->bind_param("s", $po_no);
$stmt_po->execute();
$stmt_po->bind_result($po_no_db, $season, $po_date, $supplier, $supplier_address, $issue_by, $trade_terms, $payment);
if ($stmt_po->fetch()) {
    $po_header = [
        'po_no' => $po_no_db,
        'season' => $season,
        'po_date' => $po_date,
        'supplier' => $supplier,
        'supplier_address' => $supplier_address,
        'issue_by' => $issue_by,
        'trade_terms' => $trade_terms,
        'payment' => $payment
    ];
} else {
    die("Data Purchase Order tidak ditemukan.");
}
$stmt_po->close();

// Ambil data detail
$stmt_detail = $mysqli->prepare("
    SELECT seq, mat_code, model, article, nqty, shq, tooling_stage, material_name, color,
           emboss, thickness, g, `yield`, component, unit, po_qty, curr, unit_price,
           amount, td_vendor, ta_factor
    FROM purchase_order_detail
    WHERE po_no = ?
");
$stmt_detail->bind_param("s", $po_no_db);
$stmt_detail->execute();
$stmt_detail->bind_result($seq, $mat_code, $model, $article, $nqty, $shq, $tooling_stage, $material_name, $color,
                          $emboss, $thickness, $g, $yield, $component, $unit, $po_qty, $curr, $unit_price,
                          $amount, $td_vendor, $ta_factor);

$details = [];
while ($stmt_detail->fetch()) {
    $details[] = [
        'seq' => $seq,
        'mat_code' => $mat_code,
        'model' => $model,
        'article' => $article,
        'nqty' => $nqty,
        'shq' => $shq,
        'tooling_stage' => $tooling_stage,
        'material_name' => $material_name,
        'color' => $color,
        'emboss' => $emboss,
        'thickness' => $thickness,
        'g' => $g,
        'yield' => $yield,
        'component' => $component,
        'unit' => $unit,
        'po_qty' => $po_qty,
        'curr' => $curr,
        'unit_price' => $unit_price,
        'amount' => $amount,
        'td_vendor' => $td_vendor,
        'ta_factor' => $ta_factor
    ];
}
$stmt_detail->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Purchase Order</title>
<style>
    body { font-family: Arial, sans-serif; font-size: 14px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #000; padding: 5px; text-align: left; }
    .no-border { border: none; }
    .title { text-align: center; font-size: 20px; font-weight: bold; }
    .subtitle { text-align: center; font-size: 14px; }
    .requirements { margin-top: 20px; }
</style>
</head>
<body onload="window.print()">

<table class="no-border">
    <tr>
        <td class="no-border"><img src="logo.png" alt="Logo" height="40"></td>
        <td class="no-border title">Purchase Order</td>
        <td class="no-border" style="text-align: right;"><img src="barcode.png" alt="Barcode" height="40"></td>
    </tr>
    <tr>
        <td colspan="3" class="no-border subtitle">[Sample - Korea]</td>
    </tr>
</table>

<table>
    <tr>
        <td><b>P/O No</b></td><td><?= htmlspecialchars($po_header['po_no']) ?></td>
        <td><b>Season</b></td><td><?= htmlspecialchars($po_header['season']) ?></td>
    </tr>
    <tr>
        <td><b>P/O Date</b></td><td><?= htmlspecialchars($po_header['po_date']) ?></td>
        <td><b>Supplier Address</b></td><td><?= htmlspecialchars($po_header['supplier_address']) ?></td>
    </tr>
    <tr>
        <td><b>Issue by</b></td><td><?= htmlspecialchars($po_header['issue_by']) ?></td>
        <td><b>Trade Terms</b></td><td><?= htmlspecialchars($po_header['trade_terms']) ?></td>
    </tr>
    <tr>
        <td><b>Supplier</b></td><td><?= htmlspecialchars($po_header['supplier']) ?></td>
        <td><b>Payment</b></td><td><?= htmlspecialchars($po_header['payment']) ?></td>
    </tr>
</table>
<br>

<table>
    <tr>
        <th>SEQ</th><th>Mat. Code</th><th>Model</th><th>Article</th><th>NQTY</th>
        <th>SHQ</th><th>Tooling Stage</th><th>Material Name</th><th>Color</th><th>EMBOSS</th>
        <th>Thickness</th><th>G</th><th>YIELD</th><th>Component</th><th>Unit</th>
        <th>PO Qty</th><th>Curr</th><th>Unit Price</th><th>Amount</th><th>TD Vendor</th><th>TA Factor</th>
    </tr>
    <?php foreach ($details as $row): ?>
    <tr>
        <td><?= htmlspecialchars($row['seq']) ?></td>
        <td><?= htmlspecialchars($row['mat_code']) ?></td>
        <td><?= htmlspecialchars($row['model']) ?></td>
        <td><?= htmlspecialchars($row['article']) ?></td>
        <td><?= htmlspecialchars($row['nqty']) ?></td>
        <td><?= htmlspecialchars($row['shq']) ?></td>
        <td><?= htmlspecialchars($row['tooling_stage']) ?></td>
        <td><?= htmlspecialchars($row['material_name']) ?></td>
        <td><?= htmlspecialchars($row['color']) ?></td>
        <td><?= htmlspecialchars($row['emboss']) ?></td>
        <td><?= htmlspecialchars($row['thickness']) ?></td>
        <td><?= htmlspecialchars($row['g']) ?></td>
        <td><?= htmlspecialchars($row['yield']) ?></td>
        <td><?= htmlspecialchars($row['component']) ?></td>
        <td><?= htmlspecialchars($row['unit']) ?></td>
        <td><?= htmlspecialchars($row['po_qty']) ?></td>
        <td><?= htmlspecialchars($row['curr']) ?></td>
        <td><?= htmlspecialchars($row['unit_price']) ?></td>
        <td><?= htmlspecialchars($row['amount']) ?></td>
        <td><?= htmlspecialchars($row['td_vendor']) ?></td>
        <td><?= htmlspecialchars($row['ta_factor']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<div class="requirements">
    <b>REQUIREMENTS:</b>
    <ol>
        <li>PLEASE WRITE PURCHASE ORDER NUMBER ON SHIPPING DOCS OF GOODS/SURAT JALAN</li>
        <li>P/I MUST BE SENT TO US WITHIN 2 WORKING DAYS AFTER RECEIVING THIS ORDER.</li>
        <li>SEND 1 SWATCH BOOK OF SAMPLE MATERIAL FOR THE PAYMENT BY DHL/COURIER...</li>
        <li>IF YOU HAVE LAB-TEST "PASS" REPORT ISSUED BY BUYER, LET US HAVE A COPY...</li>
        <li>ALL MATERIALS SHIPPED MUST COMPLY WITH AN OFFICIAL REPORT...</li>
        <li>ALL MATERIALS SHIPPED MUST ALSO BE FREE OF NICKEL...</li>
        <li>THAT THE SHIPMENT IS "NICKEL FREE".</li>
        <li>IF THER
