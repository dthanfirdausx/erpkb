<?php
session_start();
include "../../inc/config.php";

$id = $_GET['id'];
$infokb = infokb();

// $q = $db->query("
// SELECT sj.*, pen.nama as nama_penerima_lengkap, 
// pen.alamat as alamat_penerima, pen.notelp as telp_penerima,
// so.so_date, so.currency, so.delivery_term
// FROM surat_jalan sj
// LEFT JOIN penerima pen ON sj.kode_penerima = pen.kode_penerima
// LEFT JOIN sales_order so ON sj.id_sales_order = so.id_sales_order
// WHERE sj.id=?", [$id]);

$surat_jalan = $db->fetch("SELECT sj.*, pen.nama as nama_penerima_lengkap, 
pen.alamat as alamat_penerima, pen.notelp as telp_penerima,
so.so_date, so.currency, so.delivery_term
FROM surat_jalan sj
LEFT JOIN penerima pen ON sj.kode_penerima = pen.kode_penerima
LEFT JOIN sales_order so ON sj.id_sales_order = so.id_sales_order
WHERE sj.id=?", [$id]);

$q_detail = $db->query("
SELECT * FROM surat_jalan_detail 
WHERE surat_jalan_id=? ORDER BY row_no
", [$id]);
?>

<!DOCTYPE html>
<html>
<head>
<title>Delivery Note</title>
<style>

/* 🔥 GLOBAL FIX */
*{
    box-sizing:border-box;
}

body{
    font-family:Arial;
    font-size:12px;
    margin:0;
}

/* 🔥 CONTAINER (ANTI KEPOOTONG) */
.container{
    width:190mm; /* 🔥 FIX A4 AREA */
    margin:auto;
    padding:10px;
}

/* 🔥 HEADER */
.header{
    display:flex;
    justify-content:space-between;
}

.title{
    text-align:center;
    font-size:24px;
    font-weight:bold;
}

/* 🔥 TABLE UTAMA (SUPER RAPI) */
.table-main{
    width:100%;
    border-collapse:collapse;
    table-layout:fixed; /* 🔥 penting */
    border:2px solid #000;
}

.table-main th,
.table-main td{
    border:1px solid #000;
    padding:6px 5px;
    line-height:1.3;
    word-wrap:break-word;
}

.table-main th{
    text-align:center;
    background:#f2f2f2;
}

/* 🔥 SIGNATURE */
.sign-box{
    width:50%;
    margin-left:auto;
    border-collapse:collapse;
}

.sign-box td{
    border:1px solid #000;
   
    text-align:center;
    vertical-align:top;
    font-weight:bold;
}

/* 🔥 PRINT FIX */
@media print{

    body{
        margin:0;
    }

    .container{
        width:190mm;
        padding:5mm;
    }

    table{
        page-break-inside:auto;
    }

    tr{
        page-break-inside:avoid;
    }

    thead{
        display:table-header-group;
    }

}

/* 🔥 PAGE SETTING */
@page{
    size:A4 portrait;
    margin:10mm;
}

</style>
<script>
window.onload = function() {
    window.print();
};
</script>
</head>

<body>
<div class="container">

<!-- HEADER -->
<div class="header">

<div>
<strong><?=$infokb->nama?></strong><br>
<?=$infokb->alamat?><br>
Telp: <?=$infokb->telp?>
</div>

<div>
<div class="title">DELIVERY NOTE</div>

<strong>TO :</strong><br>
<?=$surat_jalan->nama_penerima_lengkap?><br>
<?=$surat_jalan->alamat_pengiriman?><br>
Telp: <?=$surat_jalan->telp_penerima?>
</div>

</div>

<!-- INFO -->
<table width="100%">
<tr>
<td width="150">Delivery Note No</td>
<td>: <?=$surat_jalan->no_surat_jalan?></td>
</tr>

<tr>
<td>Delivery Date</td>
<td>: <?=date('d-M-Y', strtotime($surat_jalan->tgl_surat_jalan))?></td>
</tr>

<tr>
<td>SO No</td>
<td>: <?=$surat_jalan->no_sales_order?></td>
</tr>

<tr>
<td>PO No</td>
<td>: <?=$surat_jalan->no_po?></td>
</tr>

<tr>
<td>ATTN</td>
<td>: <?=$surat_jalan->attn?></td>
</tr>
</table> 

<br>

<!-- TABLE -->
<table class="table-main" width="100%">
<tr>
<th width="5%">No</th>
<th width="40%">Product Description</th>
<th width="10%">Qty</th>
<th width="10%">UoM</th>
<th width="15%">Packing</th>
<th>Remark</th>
</tr>

<?php 
$no=1;
$total=0;

foreach($q_detail as $d){

$total += $d->qty_kirim;

echo "<tr>
<td align='center'>$no</td>
<td>$d->nama_barang</td>
<td align='center'>".number_format($d->qty_kirim,2)."</td>
<td align='center'>$d->satuan</td>
<td align='center'>$d->packing $d->satuan_packing</td>
<td>$d->keterangan</td>
</tr>";

$no++;
}
?>

<tr>
<td></td>
<td style="height:150px"></td>
<td></td>
<td></td>
<td></td>
<td></td>
</tr>

</table>

<br>

<!-- FOOTER -->
<b>WE CONFIRM THAT ALL GOODS RECEIVED ARE IN GOOD ORDER AND CONDITION</b>

<br><br>

<div>
CONSIGNEE BY<br><br><br><br><br>
(............................)
</div>

<br><br>

<div style="text-align:right"> Karawang, 
<?=date('d F Y')?>
</div>

<br>

<!-- SIGN -->
<table class="sign-box" style="width:50%; margin-left:auto;">
<tr>
<td>WAREHOUSE</td>
<td>SECURITY</td>
<td>MARKETING</td>
</tr>
<tr>
<td style="height: 100px">&nbsp;<br><br></td>
<td></td>
<td></td>
</tr>
</table>
</div>
</body>
</html>