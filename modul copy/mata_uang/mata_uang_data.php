<?php
include "../../inc/config.php";

$columns = array(
    'matauang.kd_valas',
    'matauang.jenis_valas',
    'matauang.nama_valas',
    'matauang.negara_valas',
    'matauang.kd_valas',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('negara_valas','matauang.kd_valas');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("matauang.kd_valas");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by matauang.kd_valas";

  $query = $datatable->get_custom("select matauang.kd_valas,matauang.jenis_valas,matauang.nama_valas,matauang.negara_valas from matauang",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kd_valas;
    $ResultData[] = $value->jenis_valas;
    $ResultData[] = $value->nama_valas;
    $ResultData[] = $value->negara_valas;
    $ResultData[] = $value->kd_valas;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>