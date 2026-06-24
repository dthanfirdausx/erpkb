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

$info_pt = info_pt();
$id = $_GET['id'];

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

WHERE p.id = '$id'

GROUP BY d.kode

");

if (!$header) {
    http_response_code(404);
    echo erp_t('export_document_not_found','Dokumen tidak ditemukan.');
    exit;
}

$q = $db->query("

SELECT
    d.*,
    b.nm_barang,
    b.satuan

FROM packing_list_detail d

JOIN barang b
ON b.kd_barang = d.kode

WHERE d.no_sj = '$header->no_sj'

");
ob_start();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title><?=sd_h('sales_packing_list', 'Packing List');?></title>

<style>

body{
    font-family: Arial, sans-serif;
    font-size:11px;
    padding:20px;
}

@page{
    size:F4 landscape;
}

table{
    border-collapse: collapse;
    width:100%;
}

th{
    background:#d9d9d9;
    text-align:center;
}

th,td{
    border:1px solid #000;
    padding:5px;
}

.center{
    text-align:center;
}

.bold{
    font-weight:bold;
}

.logo{
    width:160px;
}

.footer-sign td{
    border:none !important;
    padding-top:40px;
    text-align:center;
}

.header-table td{
    border:1px solid #fff;
    padding:4px 8px;
    font-size:12px;
}

.title{
    text-align:center;
    font-size:24px;
    font-weight:bold;
    margin-bottom:10px;
}

</style>

<script>
window.print();
</script>

</head>

<body>

<!-- TITLE -->

<div class="title">
    PACKING LIST
</div>

<!-- HEADER -->

<table style="width:100%; border:none; margin-bottom:20px">

<tr>

   

    <td style="border:none">

        <table class="header-table">

            <tr>
                <td width="150"><b>CUSTOMER</b></td>
                <td>: <?= $header->nama ?></td>
            </tr>

            <tr>
                <td><b>MATERIAL NAME</b></td>
                <td>: <?= $header->material_name ?></td>
            </tr>

            <tr>
                <td><b>PO#</b></td>
                <td>: <?= $header->no_po ?></td>
            </tr>

            <tr>
                <td><b>QTY PO</b></td>
                <td>: <?= formatAngka($header->qty_po) ?> M</td>
            </tr>

            <tr>
                <td><b>CUST MATERIAL CODE</b></td>
                <td>: <?= $header->kode ?></td>
            </tr>

            <tr>
                <td><b>PACKING LIST#</b></td>
                <td>: <?= $header->no_packing_list ?></td>
            </tr>

            <tr>
                <td><b>DELIVERY <?=erp_export_label('DATE');?></b></td>
                <td>: <?= date('d-m-Y', strtotime($header->tgl_sj)) ?></td>
            </tr>

        </table>

    </td>

</tr>

</table>

<!-- DETAIL TABLE -->

<table>

<thead>

<tr>
    <th width="80">ROLL NO#</th>
    <th>DESCRIPTION MATERIAL</th>
    <th width="120">QTY</th>
    <th width="150">QTY / PACKAGE</th>
    <th>REMARK</th>
</tr>

</thead>

<tbody>

<?php

$no = 1;
$total = 0;

foreach ($q as $row) {

$total += $row->jumlah;

?>

<tr>

    <td class="center">
        <?= $no ?>
    </td>

    <td>
        <?= $row->nm_barang ?>
    </td>

    <td class="center">
        <?= formatAngka($row->jumlah) ?>
    </td>

    <td class="center">
        <?= $row->qty_packing ?> / <?= $row->packing ?>
    </td>

    <td>
        <?= $row->nm_barang ?>
    </td>

</tr>

<?php
$no++;
}
?>

</tbody>

<tfoot>

<tr class="bold">

    <td colspan="2" class="center">
        <?=erp_export_label('TOTAL');?>
    </td>

    <td class="center">
        <?= formatAngka($total) ?>
    </td>

    <td class="center">
        <?= $header->qty_packing ?> PALLET
    </td>

    <td></td>

</tr> 

</tfoot>

</table>

<!-- SIGNATURE -->

<table class="footer-sign" style="margin-top:50px">

<tr>

    <td>
        <?=erp_export_label('Prepared By');?>
        <br><br><br><br>
        __________________
    </td>

    <td>
        <?=erp_export_label('Approved By');?>
        <br><br><br><br>
        __________________
    </td>

    <td>
        Received By
        <br><br><br><br>
        __________________
    </td>

</tr>

</table>

</body>
</html>
<?php
$html = ob_get_clean();
erpkb_pdf_output($html, 'packing_list_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)$header->no_packing_list).'.pdf', 'L');
?>
