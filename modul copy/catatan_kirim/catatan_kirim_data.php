<?php
include "../../inc/config.php";

$columns = array(
    'catatan.kd_catatan',
    'catatan.nm_catatan',
    'catatan.kd_catatan',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('nm_catatan','catatan.kd_catatan');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("catatan.kd_catatan");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by catatan.kd_catatan";

  $query = $datatable->get_custom("select catatan.kd_catatan,catatan.nm_catatan from catatan",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kd_catatan;
    $ResultData[] = $value->nm_catatan;
    $ResultData[] = $value->kd_catatan;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>