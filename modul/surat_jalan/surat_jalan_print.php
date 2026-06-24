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
session_start();
include "../../inc/config.php";
require_once __DIR__ . "/../print_pdf_helper.php";

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

if ($surat_jalan) {
    $printedBy = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
    $db->query("UPDATE surat_jalan SET print_count=print_count+1,last_printed_at=?,last_printed_by=? WHERE id=?", array(date('Y-m-d H:i:s'), $printedBy, $id));
} else {
    http_response_code(404);
    echo erp_t('export_document_not_found','Dokumen tidak ditemukan.');
    exit;
}

$q_detail = $db->query("
SELECT * FROM surat_jalan_detail 
WHERE surat_jalan_id=? ORDER BY row_no
", [$id]);
ob_start();
?>

<!DOCTYPE html>
<html>
<head>
<title><?=erp_export_title('Delivery Note');?></title>
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
<div class="title"><?=erp_export_title('DELIVERY NOTE');?></div>

<strong><?=erp_export_label('TO');?> :</strong><br>
<?=$surat_jalan->nama_penerima_lengkap?><br>
<?=$surat_jalan->alamat_pengiriman?><br>
Telp: <?=$surat_jalan->telp_penerima?>
</div>

</div>

<!-- INFO -->
<table width="100%">
<tr>
<td width="150"><?=erp_export_label('Delivery Note No');?></td>
<td>: <?=$surat_jalan->no_surat_jalan?></td>
</tr>

<tr>
<td><?=sd_h('sales_delivery_date', 'Delivery Date');?></td>
<td>: <?=date('d-M-Y', strtotime($surat_jalan->tgl_surat_jalan))?></td>
</tr>

<tr>
<td><?=sd_h('sales_so_no', 'SO No');?></td>
<td>: <?=$surat_jalan->no_sales_order?></td>
</tr>

<tr>
<td><?=erp_export_label('PO No');?></td>
<td>: <?=$surat_jalan->no_po?></td>
</tr>

<tr>
<td><?=erp_export_label('ATTN');?></td>
<td>: <?=$surat_jalan->attn?></td>
</tr>
</table> 

<br>

<!-- TABLE -->
<table class="table-main" width="100%">
<tr>
<th width="5%"><?=sd_h('common_no', 'No');?></th>
<th width="40%"><?=erp_export_label('Product Description');?></th>
<th width="10%"><?=sd_h('sales_qty', 'Qty');?></th>
<th width="10%"><?=erp_export_label('UOM');?></th>
<th width="15%"><?=erp_export_label('Packing');?></th>
<th><?=erp_export_label('Remark');?></th>
</tr>

<?php 
$no=1;
$total=0;

foreach($q_detail as $d){

$total += $d->qty_kirim;

echo "<tr>
<td align='center'>$no</td>
<td>$d->nama_barang</td>
<td align='center'>".erp_format_qty($d->qty_kirim,2)."</td>
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
<b><?=erp_export_label('WE CONFIRM THAT ALL GOODS RECEIVED ARE IN GOOD ORDER AND CONDITION');?></b>

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
<?php
$html = ob_get_clean();
erpkb_pdf_output($html, 'surat_jalan_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)$surat_jalan->no_surat_jalan).'.pdf', 'P');
?>
