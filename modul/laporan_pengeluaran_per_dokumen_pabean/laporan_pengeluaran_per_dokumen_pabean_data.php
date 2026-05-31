<?php
include "../../inc/config.php";

$columns = array(
    'vpengeluaranbyjenisdokpab.jenis_dokpab',
    'vpengeluaranbyjenisdokpab.no_aju',
    'vpengeluaranbyjenisdokpab.no_dokpab',
    'vpengeluaranbyjenisdokpab.tgl_dokpab',
    'vpengeluaranbyjenisdokpab.no_sj',
    'vpengeluaranbyjenisdokpab.tgl_sj',
    'vpengeluaranbyjenisdokpab.no_invoice',
    'vpengeluaranbyjenisdokpab.tgl_invoice',
    'vpengeluaranbyjenisdokpab.efaktur',
    'vpengeluaranbyjenisdokpab.tgl_efaktur',
    'vpengeluaranbyjenisdokpab.nama',
    // 'vpengeluaranbyjenisdokpab.kategori',
    // 'vpengeluaranbyjenisdokpab.kd_sub_kategori',
    // 'vpengeluaranbyjenisdokpab.sub_kategori',
    'vpengeluaranbyjenisdokpab.kode',
    'vpengeluaranbyjenisdokpab.nm_barang',
    'vpengeluaranbyjenisdokpab.satuan',
    'vpengeluaranbyjenisdokpab.jumlah',
    'vpengeluaranbyjenisdokpab.valuta',
    'vpengeluaranbyjenisdokpab.nilai',
    'vpengeluaranbyjenisdokpab.berat',
    'vpengeluaranbyjenisdokpab.nd_catatan',
    'vpengeluaranbyjenisdokpab.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('catatan','vpengeluaranbyjenisdokpab.');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("vpengeluaranbyjenisdokpab.tgl_sj");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by vpengeluaranbyjenisdokpab.";

  $wh = "";
  if ($_POST['tgl_awal']!='' && $_POST['tgl_akhir']=='') {
    $wh = "and vpengeluaranbyjenisdokpab.tgl_sj between  '".$_POST['tgl_awal']."' and '".date("Y-m-d")."' ";
  }else if ($_POST['tgl_awal']!='' && $_POST['tgl_akhir']!='') {
    $wh = "and vpengeluaranbyjenisdokpab.tgl_sj between  '".$_POST['tgl_awal']."' and '".$_POST['tgl_akhir']."' ";
  }  

  $query = $datatable->get_custom("select vpengeluaranbyjenisdokpab.id, vpengeluaranbyjenisdokpab.jenis_dokpab,vpengeluaranbyjenisdokpab.no_aju,vpengeluaranbyjenisdokpab.no_dokpab,vpengeluaranbyjenisdokpab.tgl_dokpab,vpengeluaranbyjenisdokpab.no_sj,vpengeluaranbyjenisdokpab.tgl_sj,vpengeluaranbyjenisdokpab.no_invoice,vpengeluaranbyjenisdokpab.tgl_invoice,vpengeluaranbyjenisdokpab.efaktur,vpengeluaranbyjenisdokpab.tgl_efaktur,vpengeluaranbyjenisdokpab.nama,vpengeluaranbyjenisdokpab.kategori,vpengeluaranbyjenisdokpab.kd_sub_kategori,vpengeluaranbyjenisdokpab.sub_kategori,vpengeluaranbyjenisdokpab.kode,vpengeluaranbyjenisdokpab.nm_barang,vpengeluaranbyjenisdokpab.satuan,vpengeluaranbyjenisdokpab.jumlah,vpengeluaranbyjenisdokpab.valuta,vpengeluaranbyjenisdokpab.nilai,vpengeluaranbyjenisdokpab.berat,vpengeluaranbyjenisdokpab.nd_catatan,vpengeluaranbyjenisdokpab.id from vpengeluaranbyjenisdokpab where 1=1 $wh",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->jenis_dokpab;
    $ResultData[] = $value->no_aju;
    $ResultData[] = $value->no_dokpab;
    $ResultData[] = $value->tgl_dokpab;
    $ResultData[] = $value->no_sj;
    $ResultData[] = $value->tgl_sj;
    $ResultData[] = $value->no_invoice;
    $ResultData[] = $value->tgl_invoice;
    $ResultData[] = $value->efaktur;
    $ResultData[] = $value->tgl_efaktur;
    $ResultData[] = $value->nama;
    // $ResultData[] = $value->kategori;
    // $ResultData[] = $value->kd_sub_kategori;
    // $ResultData[] = $value->sub_kategori;
    $ResultData[] = $value->kode;
    $ResultData[] = $value->nm_barang;
    $ResultData[] = $value->satuan;
    $ResultData[] = "<a style='cursor:pointer' onclick='detail_bahan_baku($value->id,\"$value->tgl_sj\",\"$value->kode\")'>$value->jumlah</a>";
    $ResultData[] = $value->valuta;
    $ResultData[] = $value->nilai;  
    $ResultData[] = $value->berat;
    $ResultData[] = $value->nd_catatan;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>