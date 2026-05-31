<?php
include "../../inc/config.php";

$columns = array(
    'v_incoming_terima_detail.nomor',
    'v_incoming_terima_detail.no_lpb',
    'v_incoming_terima_detail.tgl_lpb',
    'v_incoming_terima_detail.no_spb',
    'v_incoming_terima_detail.tgl_spb',
    'v_incoming_terima_detail.dari',
    'v_incoming_terima_detail.kategori',
    'v_incoming_terima_detail.kd_sub_kategori',
    'v_incoming_terima_detail.sub_kategori',
    'v_incoming_terima_detail.kd_barang',
    'v_incoming_terima_detail.jumlah',
    'v_incoming_terima_detail.satuan',
    'v_incoming_terima_detail.ket',
    'v_incoming_terima_detail.catatan',
    'v_incoming_terima_detail.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('dari','v_incoming_terima_detail.');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("v_incoming_terima_detail.nomor");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by v_incoming_terima_detail.";
 $wh = "";
  if ($_POST['tgl_awal']!='' && $_POST['tgl_akhir']=='') {
    $wh = "and v_incoming_terima_detail.tgl_lpb between  '".$_POST['tgl_awal']."' and '".date("Y-m-d")."' ";
  }else if ($_POST['tgl_awal']!='' && $_POST['tgl_akhir']!='') {
    $wh = "and v_incoming_terima_detail.tgl_lpb between  '".$_POST['tgl_awal']."' and '".$_POST['tgl_akhir']."' ";
  }  
 
  // echo "select v_incoming_terima_detail.nomor,v_incoming_terima_detail.no_lpb,v_incoming_terima_detail.tgl_lpb,v_incoming_terima_detail.no_spb,v_incoming_terima_detail.tgl_spb,v_incoming_terima_detail.dari,v_incoming_terima_detail.kategori,v_incoming_terima_detail.kd_sub_kategori,v_incoming_terima_detail.sub_kategori,v_incoming_terima_detail.kd_barang,v_incoming_terima_detail.jumlah,v_incoming_terima_detail.satuan,v_incoming_terima_detail.ket,v_incoming_terima_detail.catatan,v_incoming_terima_detail.id from v_incoming_terima_detail where 1=1 $wh";
  $query = $datatable->get_custom("select v_incoming_terima_detail.nomor,v_incoming_terima_detail.no_lpb,v_incoming_terima_detail.tgl_lpb,v_incoming_terima_detail.no_spb,v_incoming_terima_detail.tgl_spb,v_incoming_terima_detail.dari,v_incoming_terima_detail.kategori,v_incoming_terima_detail.kd_sub_kategori,v_incoming_terima_detail.sub_kategori,v_incoming_terima_detail.kd_barang,v_incoming_terima_detail.jumlah,v_incoming_terima_detail.satuan,v_incoming_terima_detail.ket,v_incoming_terima_detail.catatan,v_incoming_terima_detail.id from v_incoming_terima_detail where 1=1 $wh ",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->nomor;
    $ResultData[] = $value->no_lpb;
    $ResultData[] = $value->tgl_lpb;
    $ResultData[] = $value->no_spb;
    $ResultData[] = $value->tgl_spb;
    $ResultData[] = $value->dari;
    $ResultData[] = $value->kategori;
    $ResultData[] = $value->kd_sub_kategori;
    $ResultData[] = $value->sub_kategori;
    $ResultData[] = $value->kd_barang;
    $ResultData[] = $value->jumlah;
    $ResultData[] = $value->satuan;
    $ResultData[] = $value->ket;
    $ResultData[] = $value->catatan;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>