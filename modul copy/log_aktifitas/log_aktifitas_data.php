<?php
include "../../inc/config.php";

$columns = array(
    'log_aktifitas.deskripsi',
    'log_aktifitas.user',
    'log_aktifitas.tgl',
    'log_aktifitas.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('tgl','log_aktifitas.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("log_aktifitas.id");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by log_aktifitas.id";

  $query = $datatable->get_custom("select log_aktifitas.deskripsi,log_aktifitas.user,log_aktifitas.tgl,log_aktifitas.id from log_aktifitas",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->deskripsi;
    $ResultData[] = $value->user;
    $ResultData[] = $value->tgl;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>