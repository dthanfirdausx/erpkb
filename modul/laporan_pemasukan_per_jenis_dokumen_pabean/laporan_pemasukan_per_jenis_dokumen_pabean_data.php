<?php
include "../../inc/config.php";

$columns = array(
    'vpemasukanbyjenisdokpab.jenis_dokpab',
    'vpemasukanbyjenisdokpab.no_dokpab',
    'vpemasukanbyjenisdokpab.tgl_dokpab',
    'vpemasukanbyjenisdokpab.no_bpb',
    'vpemasukanbyjenisdokpab.tgl_bpb',
    'vpemasukanbyjenisdokpab.nama',
    'vpemasukanbyjenisdokpab.kode',
    'vpemasukanbyjenisdokpab.nm_barang',
    'vpemasukanbyjenisdokpab.unit',
    'vpemasukanbyjenisdokpab.jumlah',
    'vpemasukanbyjenisdokpab.nilai',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('kd_sub_kategori','vpemasukanbyjenisdokpab.');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("vpemasukanbyjenisdokpab.tgl_bpb");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by vpemasukanbyjenisdokpab.";
  $wh = "";
  $params = array();
  $tgl_awal = isset($_POST['tgl_awal']) ? trim($_POST['tgl_awal']) : '';
  $tgl_akhir = isset($_POST['tgl_akhir']) ? trim($_POST['tgl_akhir']) : '';
  $jenisbc = isset($_POST['jenisbc']) ? trim($_POST['jenisbc']) : '';
  if ($tgl_awal!='' && $tgl_akhir=='') {
    $wh = "and vpemasukanbyjenisdokpab.tgl_bpb between ? and ? ";
    $params[] = $tgl_awal;
    $params[] = date("Y-m-d");
  }else if ($tgl_awal!='' && $tgl_akhir!='') {
    $wh = "and vpemasukanbyjenisdokpab.tgl_bpb between ? and ? ";
    $params[] = $tgl_awal;
    $params[] = $tgl_akhir;
  }

  if ($jenisbc!='' && $jenisbc!='all' ) {
    $wh.= " and vpemasukanbyjenisdokpab.jenis_dokpab=? ";
    $params[] = $jenisbc;
  }

  $query = $datatable->get_custom("select vpemasukanbyjenisdokpab.jenis_dokpab,vpemasukanbyjenisdokpab.no_dokpab,vpemasukanbyjenisdokpab.tgl_dokpab,vpemasukanbyjenisdokpab.no_bpb,vpemasukanbyjenisdokpab.tgl_bpb,vpemasukanbyjenisdokpab.nama,vpemasukanbyjenisdokpab.kode,vpemasukanbyjenisdokpab.nm_barang,vpemasukanbyjenisdokpab.unit,vpemasukanbyjenisdokpab.jumlah,vpemasukanbyjenisdokpab.nilai from vpemasukanbyjenisdokpab where 1=1 $wh ",$columns,$params);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
    $ResultData[] = $value->jenis_dokpab;
    $ResultData[] = $value->no_dokpab;
    $ResultData[] = $value->tgl_dokpab;
    $ResultData[] = $value->no_bpb;
    $ResultData[] = $value->tgl_bpb;
    $ResultData[] = $value->nama;
    $ResultData[] = $value->kode;
    $ResultData[] = $value->nm_barang;
    $ResultData[] = $value->unit;
    $ResultData[] = number_format((float)$value->jumlah, 2, '.', ',');
    $ResultData[] = number_format((float)$value->nilai, 2, '.', ',');

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>
