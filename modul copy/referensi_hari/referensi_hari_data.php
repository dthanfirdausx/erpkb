<?php
include "../../inc/config.php";

$columns = array(
    'h_hari.hari',
    'h_hari.hari_id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('hari','h_hari.hari_id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("h_hari.hari_id");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by h_hari.hari_id";

  $query = $datatable->get_custom("select h_hari.hari,h_hari.hari_id from h_hari",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->hari;
    $ResultData[] = $value->hari_id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>