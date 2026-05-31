<?php
include "../../inc/config.php";

$columns = array(
    'vwip.kode',
    'vwip.nama',
    'vwip.stock',
    'vwip.satuan',
    'vwip.kategori',
    'vwip.posisi',
    'vwip.userid',
    'vwip.kode',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('posisi','vwip.');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("vwip.kode");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by vwip.";

  $query = $datatable->get_custom("select vwip.kode,vwip.nama,vwip.stock,vwip.satuan,vwip.kategori,vwip.posisi,vwip.userid,vwip.kode from vwip",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kode;
    $ResultData[] = $value->nama;
    $ResultData[] = $value->stock;
    $ResultData[] = $value->satuan;
    $ResultData[] = $value->kategori;
    $ResultData[] = $value->posisi;
    $ResultData[] = $value->userid;
    $ResultData[] = $value->kode;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>