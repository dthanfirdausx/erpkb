<?php
include "../../inc/config.php";

$columns = array(
    'vpemasukanbyjenisdokpab.jenis_dokpab',
    'vpemasukanbyjenisdokpab.no_aju',
    'vpemasukanbyjenisdokpab.no_dokpab',
    'vpemasukanbyjenisdokpab.tgl_dokpab',
    'vpemasukanbyjenisdokpab.no_bpb',
    'vpemasukanbyjenisdokpab.tgl_bpb',
    'vpemasukanbyjenisdokpab.efaktur',
    'vpemasukanbyjenisdokpab.tgl_efaktur',
    'vpemasukanbyjenisdokpab.nama',
    'vpemasukanbyjenisdokpab.kategori',
    'vpemasukanbyjenisdokpab.kd_sub_kategori',
    'vpemasukanbyjenisdokpab.sub_kategori',
    'vpemasukanbyjenisdokpab.kode',
    'vpemasukanbyjenisdokpab.nm_barang',
    'vpemasukanbyjenisdokpab.unit',
    'vpemasukanbyjenisdokpab.jumlah',
    'vpemasukanbyjenisdokpab.valuta',
    'vpemasukanbyjenisdokpab.nilai',
    'vpemasukanbyjenisdokpab.berat',
    'vpemasukanbyjenisdokpab.nd_catatan',
    'vpemasukanbyjenisdokpab.nm_kategori',
    'vpemasukanbyjenisdokpab.nomor',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('kd_sub_kategori','vpemasukanbyjenisdokpab.');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("vpemasukanbyjenisdokpab.kd_sub_kategori");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by vpemasukanbyjenisdokpab.";
  $wh = "";
  if ($_POST['tgl_awal']!='' && $_POST['tgl_akhir']=='') {
    $wh = "and vpemasukanbyjenisdokpab.tgl_bpb between  '".$_POST['tgl_awal']."' and '".date("Y-m-d")."' ";
  }else if ($_POST['tgl_awal']!='' && $_POST['tgl_akhir']!='') {
    $wh = "and vpemasukanbyjenisdokpab.tgl_bpb between  '".$_POST['tgl_awal']."' and '".$_POST['tgl_akhir']."' ";
  } 

  if ($_POST['jenisbc']!='' && $_POST['jenisbc']!='all' ) {
    $wh.= " and vpemasukanbyjenisdokpab.jenis_dokpab='".$_POST['jenisbc']."' ";   
  }

  if ($_POST['suplier']!='' && $_POST['suplier']!='all' ) {
    $wh.= " and vpemasukanbyjenisdokpab.nama='".$_POST['suplier']."' ";   
  }

  if ($_POST['tgl_invoice_awal']!='' && $_POST['tgl_invoice_akhir']=='') {    
    $wh .= "and vpemasukanbyjenisdokpab.tgl_invoice between  '".$_POST['tgl_invoice_awal']."' and '".date("Y-m-d")."' ";
  }else if ($_POST['tgl_invoice_awal']!='' && $_POST['tgl_invoice_akhir']!='') { 
    $wh .= "and vpemasukanbyjenisdokpab.tgl_invoice between  '".$_POST['tgl_invoice_awal']."' and '".$_POST['tgl_invoice_akhir']."' ";
  } 
//echo "select vpemasukanbyjenisdokpab.no_invoice,vpemasukanbyjenisdokpab.tgl_invoice, vpemasukanbyjenisdokpab.nomor, vpemasukanbyjenisdokpab.jenis_dokpab,vpemasukanbyjenisdokpab.no_aju,vpemasukanbyjenisdokpab.no_dokpab,vpemasukanbyjenisdokpab.tgl_dokpab,vpemasukanbyjenisdokpab.no_bpb,vpemasukanbyjenisdokpab.tgl_bpb,vpemasukanbyjenisdokpab.efaktur,vpemasukanbyjenisdokpab.tgl_efaktur,vpemasukanbyjenisdokpab.nama,vpemasukanbyjenisdokpab.kategori,vpemasukanbyjenisdokpab.kd_sub_kategori,vpemasukanbyjenisdokpab.sub_kategori,vpemasukanbyjenisdokpab.kode,vpemasukanbyjenisdokpab.nm_barang,vpemasukanbyjenisdokpab.unit,vpemasukanbyjenisdokpab.jumlah,vpemasukanbyjenisdokpab.valuta,vpemasukanbyjenisdokpab.nilai,vpemasukanbyjenisdokpab.berat,vpemasukanbyjenisdokpab.nd_catatan,vpemasukanbyjenisdokpab.nm_kategori,vpemasukanbyjenisdokpab.nomor from vpemasukanbyjenisdokpab where 1=1 $wh";
  $query = $datatable->get_custom("select vpemasukanbyjenisdokpab.no_invoice,vpemasukanbyjenisdokpab.tgl_invoice, vpemasukanbyjenisdokpab.nomor, vpemasukanbyjenisdokpab.jenis_dokpab,vpemasukanbyjenisdokpab.no_aju,vpemasukanbyjenisdokpab.no_dokpab,vpemasukanbyjenisdokpab.tgl_dokpab,vpemasukanbyjenisdokpab.no_bpb,vpemasukanbyjenisdokpab.tgl_bpb,vpemasukanbyjenisdokpab.efaktur,vpemasukanbyjenisdokpab.tgl_efaktur,vpemasukanbyjenisdokpab.nama,vpemasukanbyjenisdokpab.kategori,vpemasukanbyjenisdokpab.kd_sub_kategori,vpemasukanbyjenisdokpab.sub_kategori,vpemasukanbyjenisdokpab.kode,vpemasukanbyjenisdokpab.nm_barang,vpemasukanbyjenisdokpab.unit,vpemasukanbyjenisdokpab.jumlah,vpemasukanbyjenisdokpab.valuta,vpemasukanbyjenisdokpab.nilai,vpemasukanbyjenisdokpab.berat,vpemasukanbyjenisdokpab.nd_catatan,vpemasukanbyjenisdokpab.nm_kategori,vpemasukanbyjenisdokpab.nomor from vpemasukanbyjenisdokpab where 1=1 $wh ",$columns);

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
    $ResultData[] = $value->no_bpb;
    $ResultData[] = $value->tgl_bpb;
    $ResultData[] = $value->no_invoice;
    $ResultData[] = $value->tgl_invoice;
    $ResultData[] = $value->efaktur;
    $ResultData[] = $value->tgl_efaktur;
    $ResultData[] = $value->nama;
    $ResultData[] = $value->kategori;
    $ResultData[] = $value->kd_sub_kategori;
    $ResultData[] = $value->sub_kategori;
    $ResultData[] = $value->kode;
    $ResultData[] = $value->nm_barang;
    $ResultData[] = $value->unit;
    $ResultData[] = $value->jumlah;
    $ResultData[] = $value->valuta;
    $ResultData[] = $value->nilai;
    $ResultData[] = $value->berat;
    $ResultData[] = $value->nd_catatan;
    $ResultData[] = $value->nm_kategori;
    $ResultData[] = $value->nomor;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>