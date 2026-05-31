<?php
include "../../inc/config.php";

$id = $_GET['id'] ?? '';

$filename = "PACKING_LIST_" . date('Ymd_His') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
 
$q_header = $db->query("
    SELECT 
        p.*,
        pr.nama,
        pr.alamat
    FROM packing_list p
    LEFT JOIN penerima pr 
        ON pr.kode_penerima = p.penerima
    WHERE p.id = '$id'
");

$header = $db->fetch("
    SELECT 

    p.*,

    pr.nama,
    pr.alamat,

    d.kode,
    d.jumlah,
    d.qty_packing,
    d.packing,

    b.nm_barang AS material_name,
    b.satuan,

    sj.no_sales_order,

    so.no_po,

    sod.qty AS qty_po

FROM packing_list p 

LEFT JOIN penerima pr
    ON TRIM(pr.kode_penerima) = TRIM(p.penerima)

LEFT JOIN packing_list_detail d
    ON TRIM(d.no_sj) = TRIM(p.no_sj)

LEFT JOIN barang b
    ON TRIM(b.kd_barang) = TRIM(d.kode)

LEFT JOIN surat_jalan sj
    ON TRIM(sj.no_surat_jalan) = TRIM(p.no_sj)

LEFT JOIN sales_order so
    ON so.no_sales_order = sj.no_sales_order

LEFT JOIN sales_order_detail sod
    ON sod.id_sales_order = so.id_sales_order
    AND TRIM(sod.kd_barang) = TRIM(d.kode)

WHERE p.id = '$id' group by d.kode
");

$q_detail = $db->query("
    SELECT 
        d.*,
        b.nm_barang,
        b.satuan
    FROM packing_list_detail d
    LEFT JOIN barang b 
        ON b.kd_barang = d.kode
    WHERE d.no_sj = '$header->no_sj'
");
?>

<html>
<head>
<meta charset="UTF-8">

<style>

body{
    font-family: Arial;
    font-size: 12pt;
}

table{
    border-collapse: collapse;
    width:100%;
}

td{
    padding:4px;
    vertical-align: top;
}

.judul{
    font-size:24pt;
    font-weight:bold;
    text-align:center;
    border-bottom:2px solid #000;
}

.tbl{
    border-collapse: collapse;
    width:100%;
}

.tbl td,
.tbl th{
    border:1px solid #000;
    padding:5px;
}

.tbl th{
    text-align:center;
    font-weight:bold;
}

.center{
    text-align:center;
}

.right{
    text-align:right;
}

.bold{
    font-weight:bold;
}

.no-border td{
    border:none !important;
    padding:3px;
}

.ttd td{
    border:1px solid #000;
    height:90px;
    vertical-align:top;
}

</style>

</head>

<body>

<table class="no-border">

<tr>
    <td width="20%">
        <img src="<?= base_url()."assets/".infokb()->logo ?>" width="180">
    </td>

    <td width="80%" class="judul">
        PACKING LIST
    </td>
</tr>

</table>

<br>

<table class="no-border">

<tr>
    <td width="15%" class="bold">CUSTOMER</td>
    <td width="1%">:</td>
    <td width="84%"><?= $header->nama ?></td>
</tr>

<tr>
    <td class="bold">MATERIAL NAME</td>
    <td>:</td>
    <td><?= $header->material_name ?></td>
</tr>

<tr>
    <td class="bold">PO#</td>
    <td>:</td>
    <td><?= $header->no_po ?></td>
</tr>

<tr>
    <td class="bold">QTY PO</td>
    <td>:</td>
    <td><?= formatAngka($header->qty_po) ?> M</td>
</tr>

<tr>
    <td class="bold">CUST MATERIAL CODE</td>
    <td>:</td>
    <td><?= $header->kode ?></td>
</tr>

<tr>
    <td class="bold">PACKING LIST#</td>
    <td>:</td>
    <td><?= $header->no_packing_list ?></td>
</tr>

<tr>
    <td class="bold">DELIVERY DATE</td>
    <td>:</td>
    <td><?= strtoupper(date('F j, Y', strtotime($header->tgl_sj))) ?></td>
</tr>

</table>

<br><br>

<table class="tbl">

<tr>
    <th width="10%">ROLL NO#</th>
    <th width="35%">DECRIPTION MATERIAL</th>
    <th width="10%">QTY (M)</th>
    <th width="15%">QTY/ PACKAGE</th>
    <th width="30%">REMARK (CUST MATERIAL NAME)</th>
</tr>

<?php

$no = 1;
$total = 0;

foreach($q_detail as $d){

$total += $d->jumlah;

?>

<tr>

    <td class="center">
        <?= $no ?>
    </td>

    <td class="center">
        <?= $d->nm_barang ?>
    </td>

    <td class="center">
        <?= formatAngka($d->jumlah) ?>
    </td>

    <td class="center">
        <?= $d->qty_packing ?> / <?= strtoupper($d->packing) ?>
    </td>

    <td class="center">
        <?= $header->material_name ?>
    </td>

</tr>

<?php
$no++;
}
?>

<tr class="bold">

    <td colspan="2" class="center">
        TOTAL
    </td>

    <td class="center">
        <?= formatAngka($total) ?>
    </td>

    <td class="center">
        <?= $header->qty_packing ?> PALLET
    </td>

    <td></td>

</tr>

</table>

<br><br>

<table class="ttd">

<tr>

    <td width="33%" class="center bold">
        Prepared By
    </td>

    <td width="33%" class="center bold">
        Approved By
    </td>

    <td width="33%" class="center bold">
        Received By
    </td>

</tr>

<tr>

    <td>
        <br><br><br><br>
        ______________________
        <br>
        Date :
    </td>

    <td>
        <br><br><br><br>
        ______________________
        <br>
        Date :
    </td>

    <td>
        <br><br><br><br>
        ______________________
        <br>
        Date :
    </td>

</tr>

</table>

</body>
</html>