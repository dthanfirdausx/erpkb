<?php
include "../../inc/config.php";

$columns = array(
    'bahan.nomor',
    'bahan.no_lap',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('nomor','bahan.no_lap');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("bahan.no_lap");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by bahan.no_lap";

  $query = $datatable->get_custom("select bahan.nomor,bahan.no_lap from bahan",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->nomor;
    $ResultData[] = $value->no_lap;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>