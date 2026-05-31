<?php
include "../../inc/config.php";

$columns = array(
    'dept.kd_dept',
    'dept.nm_dept',
    'dept.kd_dept',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('nm_dept','dept.kd_dept');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("dept.kd_dept");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by dept.kd_dept";

  $query = $datatable->get_custom("select dept.kd_dept,dept.nm_dept from dept",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kd_dept;
    $ResultData[] = $value->nm_dept;
    $ResultData[] = $value->kd_dept;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>