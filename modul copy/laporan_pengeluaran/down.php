<?php
header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=laporan_pengeluaran_per_dokumen_pabean.xls");  //File name extension was wrong
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
    $wh = "and vpengeluaranbyjenisdokpab.tgl_sj between  '".$_GET['tgl_awal']."' and '".date("Y-m-d")."' ";
  }else if ($_GET['tgl_awal']!='' && $_GET['tgl_akhir']!='') {
    $wh = "and vpengeluaranbyjenisdokpab.tgl_sj between  '".$_GET['tgl_awal']."' and '".$_GET['tgl_akhir']."' ";
  } 

  if ($_GET['jenis_dokpab']!='' && $_GET['jenis_dokpab']!='all' ) {
    $wh.= " and vpengeluaranbyjenisdokpab.jenis_dokpab='".$_GET['jenis_dokpab']."' ";   
  }
  ?>
   <h3>Laporan Pengeluaran Per Dokumen Pabean<br>PT. <?= shortTittle ?><br>Periode : <?= tgl_indo($_GET['tgl_awal']) ?> sd <?= tgl_indo($_GET['tgl_akhir']) ?></h3>
 
   <table border="1">
                            <thead>
                                <tr>
                                  <th>No</th>
                                  <th>Jenis DokPab</th>
                                  <th>No Aju</th>
                                  <th>No DokPab</th>
                                  <th>Tanggal Dokpab</th>
                                  <th>No sj</th>
                                  <th>Tanggal sj</th>
                                  <th>No Invoice</th>
                                  <th>Tgl Invoice</th>
                                  <th>Efaktur</th>
                                  <th>Tanggal E-faktur</th>
                                  <th>Penerima</th>
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

  // echo "select vpengeluaranbyjenisdokpab.no_invoice,vpengeluaranbyjenisdokpab.tgl_invoice, vpengeluaranbyjenisdokpab.nomor, vpengeluaranbyjenisdokpab.jenis_dokpab,vpengeluaranbyjenisdokpab.no_aju,vpengeluaranbyjenisdokpab.no_dokpab,vpengeluaranbyjenisdokpab.tgl_dokpab,vpengeluaranbyjenisdokpab.no_sj,vpengeluaranbyjenisdokpab.tgl_sj,vpengeluaranbyjenisdokpab.efaktur,vpengeluaranbyjenisdokpab.tgl_efaktur,vpengeluaranbyjenisdokpab.nama,vpengeluaranbyjenisdokpab.kategori,vpengeluaranbyjenisdokpab.kd_sub_kategori,vpengeluaranbyjenisdokpab.sub_kategori,vpengeluaranbyjenisdokpab.kode,vpengeluaranbyjenisdokpab.nm_barang,vpengeluaranbyjenisdokpab.unit,vpengeluaranbyjenisdokpab.jumlah,vpengeluaranbyjenisdokpab.valuta,vpengeluaranbyjenisdokpab.nilai,vpengeluaranbyjenisdokpab.berat,vpengeluaranbyjenisdokpab.nd_catatan,vpengeluaranbyjenisdokpab.kategori,vpengeluaranbyjenisdokpab.nomor from vpengeluaranbyjenisdokpab where 1=1 $wh order by tgl_dokpab asc,no_sj asc,jenis_dokpab asc";

  $query = $db->query("select vpengeluaranbyjenisdokpab.no_invoice,vpengeluaranbyjenisdokpab.tgl_invoice, vpengeluaranbyjenisdokpab.jenis_dokpab,vpengeluaranbyjenisdokpab.no_aju,vpengeluaranbyjenisdokpab.no_dokpab,vpengeluaranbyjenisdokpab.tgl_dokpab,vpengeluaranbyjenisdokpab.no_sj,vpengeluaranbyjenisdokpab.tgl_sj,vpengeluaranbyjenisdokpab.efaktur,vpengeluaranbyjenisdokpab.tgl_efaktur,vpengeluaranbyjenisdokpab.nama,vpengeluaranbyjenisdokpab.kategori,vpengeluaranbyjenisdokpab.kd_sub_kategori,vpengeluaranbyjenisdokpab.sub_kategori,vpengeluaranbyjenisdokpab.kode,vpengeluaranbyjenisdokpab.nm_barang,vpengeluaranbyjenisdokpab.unit,vpengeluaranbyjenisdokpab.jumlah,vpengeluaranbyjenisdokpab.valuta,vpengeluaranbyjenisdokpab.nilai,vpengeluaranbyjenisdokpab.berat,vpengeluaranbyjenisdokpab.nd_catatan,vpengeluaranbyjenisdokpab.kategori from vpengeluaranbyjenisdokpab where 1=1 $wh order by tgl_dokpab asc,no_sj asc,jenis_dokpab asc ");
  $no=1;
  foreach ($query as $k) { 
  	echo "<tr>
  	        <td>$no</td>
  	        <td>$k->jenis_dokpab</td>
  	        <td>'$k->no_aju</td>
  	        <td>'$k->no_dokpab</td>
  	        <td>$k->tgl_dokpab</td>
  	        <td>$k->no_sj</td>
  	        <td>$k->tgl_sj</td>
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
  	        <td>$k->kategori</td>

  	      </tr>";
  	      $no++;
  }

  ?>
   </tbody>
  </table>