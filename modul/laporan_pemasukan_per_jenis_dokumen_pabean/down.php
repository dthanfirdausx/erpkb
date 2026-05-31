<?php
header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=laporan_pemasukan_per_dokumen_pabean.xls");  //File name extension was wrong
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false);
session_start();
include "../../inc/config.php";
$tgl_awal = $_GET['tgl_awal'];
$tgl_akhir = $_GET['tgl_akhir'];
$jenis_dokpab = $_GET['jenis_dokpab'];
 $wh = "";
  if ($_GET['tgl_awal']!='' && $_GET['tgl_akhir']=='') {
    $wh = "and vpemasukanbyjenisdokpab.tgl_bpb between  '".$_GET['tgl_awal']."' and '".date("Y-m-d")."' ";
  }else if ($_GET['tgl_awal']!='' && $_GET['tgl_akhir']!='') {
    $wh = "and vpemasukanbyjenisdokpab.tgl_bpb between  '".$_GET['tgl_awal']."' and '".$_GET['tgl_akhir']."' ";
  } 

  if ($_GET['jenis_dokpab']!='' && $_GET['jenis_dokpab']!='all' ) {
    $wh.= " and vpemasukanbyjenisdokpab.jenis_dokpab='".$_GET['jenis_dokpab']."' ";   
  }
  ?>
   <h3>Laporan Pemasukan Per Dokumen Pabean<br>PT. <?= shortTittle ?><br>Periode : <?= tgl_indo($_GET['tgl_awal']) ?> sd <?= tgl_indo($_GET['tgl_akhir']) ?></h3>
 
   <table border="1">
                            <thead>
                                <tr>
                                  <th>No</th>
                                  <th>Jenis DokPab</th>
                                  <th>No Aju</th>
                                  <th>No DokPab</th>
                                  <th>Tanggal Dokpab</th>
                                  <th>No BPB</th>
                                  <th>Tanggal BPB</th>
                                  <th>No Invoice</th>
                                  <th>Tgl Invoice</th>
                                  <th>Efaktur</th>
                                  <th>Tanggal E-faktur</th>
                                  <th>Pemasok</th>
                                  <th>Kategori</th>
                                  <th>Kode Sub Kategori</th>
                                  <th>Sub Kategori</th>
                                  <th>Kode Barang</th>
                                  <th>Nama Barang</th>
                                  <th>Satuan</th>
                                  <th>Qty</th>
                                  <th>Valuta</th>
                                  <th>Nilai</th>
                                  <th>Berat</th>
                                  <th>Tujuan Detail</th>
                                  <th>Kategori Barang</th>
                                 <!--  <th>Action</th> -->
                                </tr>
                            </thead>
                            <tbody>
                           
  <?php

  $query = $db->query("select vpemasukanbyjenisdokpab.no_invoice,vpemasukanbyjenisdokpab.tgl_invoice, vpemasukanbyjenisdokpab.nomor, vpemasukanbyjenisdokpab.jenis_dokpab,vpemasukanbyjenisdokpab.no_aju,vpemasukanbyjenisdokpab.no_dokpab,vpemasukanbyjenisdokpab.tgl_dokpab,vpemasukanbyjenisdokpab.no_bpb,vpemasukanbyjenisdokpab.tgl_bpb,vpemasukanbyjenisdokpab.efaktur,vpemasukanbyjenisdokpab.tgl_efaktur,vpemasukanbyjenisdokpab.nama,vpemasukanbyjenisdokpab.kategori,vpemasukanbyjenisdokpab.kd_sub_kategori,vpemasukanbyjenisdokpab.sub_kategori,vpemasukanbyjenisdokpab.kode,vpemasukanbyjenisdokpab.nm_barang,vpemasukanbyjenisdokpab.unit,vpemasukanbyjenisdokpab.jumlah,vpemasukanbyjenisdokpab.valuta,vpemasukanbyjenisdokpab.nilai,vpemasukanbyjenisdokpab.berat,vpemasukanbyjenisdokpab.nd_catatan,vpemasukanbyjenisdokpab.nm_kategori,vpemasukanbyjenisdokpab.nomor from vpemasukanbyjenisdokpab where 1=1 $wh order by tgl_dokpab asc,no_bpb asc,jenis_dokpab asc ");
  $no=1;
  foreach ($query as $k) {
  	echo "<tr>
  	        <td>$no</td>
  	        <td>$k->jenis_dokpab</td>
  	        <td>'$k->no_aju</td>
  	        <td>'$k->no_dokpab</td>
  	        <td>$k->tgl_dokpab</td>
  	        <td>$k->no_bpb</td>
  	        <td>$k->tgl_bpb</td>
  	        <td>$k->no_invoice</td>
  	        <td>$k->tgl_invoice</td>
  	        <td>$k->efaktur</td>
  	        <td>$k->tgl_efaktur</td>
  	        <td>$k->nama</td>
  	        <td>$k->kategori</td>
  	        <td>$k->kd_sub_kategori</td>
  	        <td>$k->sub_kategori</td>
  	        <td>$k->kode</td>
  	        <td>$k->nm_barang</td>
  	        <td>$k->unit</td>
  	        <td>$k->jumlah</td>
  	        <td>$k->valuta</td>
  	        <td>$k->nilai</td>
  	        <td>$k->berat</td>
  	        <td>$k->nd_catatan</td>
  	        <td>$k->nm_kategori</td>

  	      </tr>";
  	      $no++;
  }

  ?>
   </tbody>
  </table>