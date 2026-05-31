<?php
include "../../inc/config.php";

$id = $_GET['id'];

$header = $db->query("

SELECT *
FROM jurnal_header
WHERE id = '$id'

")->fetch();

?>

<div class="row">

    <div class="col-md-4">
        <b>No Jurnal</b><br>
        <?= $header->no_jurnal ?>
    </div>

    <div class="col-md-4">
        <b>Tanggal</b><br>
        <?= $header->tgl_jurnal ?>
    </div>

    <div class="col-md-4">
        <b>No Bukti</b><br>
        <?= $header->no_bukti ?>
    </div>

</div>

<hr>

<table class="table table-bordered">

    <thead>

        <tr>

            <th>COA</th>
            <th>Nama Rekening</th>
            <th>Debet</th>
            <th>Kredit</th>

        </tr>

    </thead>

    <tbody>

<?php

$total_debet = 0;
$total_kredit = 0;

$detail = $db->query("

SELECT 
    a.*,
    b.nama_rek

FROM jurnal_detail a

LEFT JOIN rekening b
    ON b.no_rek = a.no_rek

WHERE a.id_header = '$id'

");

foreach($detail as $row){

$total_debet += $row->debet;
$total_kredit += $row->kredit;

?>

<tr>

    <td><?= $row->no_rek ?></td>

    <td><?= $row->nama_rek ?></td>

    <td align="right">
        <?= number_format($row->debet,2) ?>
    </td>

    <td align="right">
        <?= number_format($row->kredit,2) ?>
    </td>

</tr>

<?php } ?>

    </tbody>

    <tfoot>

        <tr>

            <th colspan="2" style="text-align:right">
                TOTAL
            </th>

            <th style="text-align:right">
                <?= number_format($total_debet,2) ?>
            </th>

            <th style="text-align:right">
                <?= number_format($total_kredit,2) ?>
            </th>

        </tr>

    </tfoot>

</table>