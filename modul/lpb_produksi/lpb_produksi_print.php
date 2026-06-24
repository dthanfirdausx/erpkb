<?php

include "../../inc/config.php";
require_once __DIR__ . "/../print_pdf_helper.php";

$id = $_GET['id'];

$header = $db->fetch("

SELECT 
    t.user_terima,
    dp.nm_dept,
    t.id_transfer,
    t.no_transfer,
    t.no_terima,

    (
        SELECT COUNT(td.id_barang)
        FROM transfer_detail td
        WHERE td.id_transfer = t.id_transfer
    ) AS jml,

    t.dari,
    t.ke,
    t.tgl_transfer,
    t.tgl_terima,
    t.no_ro,
    t.tgl_ro,
    t.user,
    t.ket,
    t.status,
    t.date_created,

    b.nm_bagian AS nm_dari,
    bb.nm_bagian AS nm_ke

FROM transfer t

LEFT JOIN bagian b
    ON b.id_bagian = t.dari

LEFT JOIN bagian bb
    ON bb.id_bagian = t.ke

LEFT JOIN dept dp
    ON dp.kd_dept = t.kd_dept

WHERE t.id_transfer = '$id'

");

if (!$header) {
    http_response_code(404);
    echo erp_t('export_document_not_found','Dokumen tidak ditemukan.');
    exit;
}
ob_start();

?>

<!DOCTYPE html>
<html>
<head>

<title><?=erp_export_title('Print LPB Produksi');?></title>

<style>
<style>
 
@page{
    size: A4;
    margin: 15mm;
}

body{
    font-family: Arial;
    font-size: 12px;
}

table{
    width:100%;
    border-collapse: collapse;
}

table th,
table td{
    border:1px solid #000;
    padding:5px;
}

.no-border td{
    border:none;
    padding:3px;
}

.text-center{
    text-align:center;
}

.text-right{
    text-align:right;
}

</style>
</style>

</head>

<body onload="window.print()">

<h2 style="text-align:center">
    <?=erp_export_title('LPB PRODUKSI');?>
</h2>

<br>

<table class="no-border">

<tr>
    <td width="200"><?=erp_export_label('No LPB');?></td>
    <td width="10">:</td>
    <td><?= $header->no_terima ?></td>
</tr>

<tr>
    <td><?=erp_export_label('No SPB');?></td>
    <td>:</td>
    <td><?= $header->no_transfer ?></td>
</tr>

<tr>
    <td>Tanggal SPB</td>
    <td>:</td>
    <td><?= $header->tgl_transfer ?></td>
</tr>

<tr>
    <td><?=erp_export_label('No RO');?></td>
    <td>:</td>
    <td><?= $header->no_ro ?></td>
</tr>

<tr>
    <td>Tanggal RO</td>
    <td>:</td>
    <td><?= $header->tgl_ro ?></td>
</tr>


 
<tr>
    <td>Dari</td>
    <td>:</td>
    <td><?= $header->nm_dari ?></td>
</tr>



<tr>
    <td>User Terima</td>
    <td>:</td>
    <td><?= $header->user_terima ?></td>
</tr>

<tr>
    <td>Keterangan</td>
    <td>:</td>
    <td><?= $header->ket ?></td>
</tr>

</table>

<br><br>

<table>

<thead>

<tr>

    <th width="50">No</th>
  <!--   <th>No AJU</th>
    <th>No Dokpab</th> -->
    <th>Kode Barang</th>
    <th>Nama Barang</th>
  
    <th><?=erp_export_label('Qty RO');?></th>
    <th>Qty</th>
    <th>Satuan</th>

</tr>

</thead>

<tbody>

<?php

$no = 1;

$q = $db->query("

SELECT 

    d.no,

    dt.no_dokpab,

    dt.no_aju,

    b.nm_barang,

    t.no_transfer as no_spb,

    t.tgl_transfer as tgl_spb,

    b.kd_barang as kode,

    MAX(p.jenis_dokpab) as jenis_dokpab,

    IFNULL(MAX(rd.jumlah),0) as qtyro,

    ABS(dt.qty) as jumlah,

    b.satuan,

    t.ket

FROM transfer t

JOIN transfer_detail d 
    ON d.id_transfer = t.id_transfer

JOIN barang b 
    ON b.id = d.id_barang

JOIN detail_transaksi dt 
    ON dt.no_ref = t.no_transfer
    AND dt.kd_barang = b.kd_barang
    AND dt.posisi = 'GUDANG'
    AND dt.is_reversal='0'

LEFT JOIN pemasukan p 
    ON p.no_aju = dt.no_aju

LEFT JOIN ro 
    ON ro.no_ro = t.no_ro

LEFT JOIN ro_detail rd 
    ON rd.no_ro = ro.no_ro 
    AND rd.kode = b.kd_barang

WHERE t.id_transfer = '$id'

GROUP BY 

    d.no,
    b.kd_barang,
    dt.no_aju,
    dt.no_dokpab

ORDER BY 

    d.no,
    b.kd_barang,
    dt.no_aju

");

foreach($q as $r){

?>

<tr>

    <td class="text-center">
        <?= $no++ ?>
    </td>

   
    <td>
        <?= $r->kode ?>
    </td>

    <td>
        <?= $r->nm_barang ?>
    </td>

 

    <td class="text-right">
        <?= number_format($r->qtyro,2) ?>
    </td>

    <td class="text-right">
        <?= number_format($r->jumlah,2) ?>
    </td>

    <td>
        <?= $r->satuan ?>
    </td>

</tr>

<?php } ?>

</tbody>

</table>

<br><br><br>

<table class="no-border">

<tr>

    <td width="33%" align="center">

        Dibuat Oleh

        <br><br><br><br>

        (...........................)

    </td>

    <td width="33%" align="center">

        Dikirim Oleh

        <br><br><br><br>

        (...........................)

    </td>

    <td width="33%" align="center">

        Diterima Oleh

        <br><br><br><br>

        (<?= $header->user_terima ?>)

    </td>

</tr>

</table>

</body>
</html>
<?php
$html = ob_get_clean();
erpkb_pdf_output($html, 'lpb_produksi_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)$header->no_terima).'.pdf', 'P');
?>
