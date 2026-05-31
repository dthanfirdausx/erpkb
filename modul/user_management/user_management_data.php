<?php
include "../../inc/config.php";

$columns = array(
    'kategori.kd_kategori',
    'kategori.nm_kategori',
    'kategori.kd_kategori',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('nm_kategori','kategori.kd_kategori');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("kategori.kd_kategori");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by kategori.kd_kategori";

  $query = $datatable->get_custom("select kategori.kd_kategori,kategori.nm_kategori from kategori",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kd_kategori;
    $ResultData[] = $value->nm_kategori;
    $ResultData[] = $value->0;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>