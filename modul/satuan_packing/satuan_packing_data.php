<?php
include "../../inc/config.php";

$columns = array(
    'satuan_packing.satuan_packing',
    'satuan_packing.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('satuan_packing','satuan_packing.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("satuan_packing.id");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by satuan_packing.id";

  $query = $datatable->get_custom("select satuan_packing.satuan_packing,satuan_packing.id from satuan_packing",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->satuan_packing;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>