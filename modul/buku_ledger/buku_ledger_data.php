<?php
include "../../inc/config.php";

$columns = array(
    'jurnal_header.id',
    'jurnal_header.no_jurnal',
    'jurnal_header.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('no_jurnal','jurnal_header.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("jurnal_header.id");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by jurnal_header.id";

  $query = $datatable->get_custom("select jurnal_header.id,jurnal_header.no_jurnal from jurnal_header",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->id;
    $ResultData[] = $value->no_jurnal;
    $ResultData[] = $value->0;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>