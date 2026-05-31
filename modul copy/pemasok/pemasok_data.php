<?php
include "../../inc/config.php";

$columns = array(
    'pemasok.kode_pemasok',
    'pemasok.npwp',
    'pemasok.nama',
    'pemasok.alamat',
    'pemasok.kode_pemasok',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('status','pemasok.kode_pemasok');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("pemasok.kode_pemasok");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by pemasok.kode_pemasok";

  $query = $datatable->get_custom("select pemasok.kode_pemasok,pemasok.npwp,pemasok.nama,pemasok.alamat from pemasok",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kode_pemasok;
    $ResultData[] = $value->npwp;
    $ResultData[] = $value->nama;
    $ResultData[] = $value->alamat;
    $ResultData[] = $value->kode_pemasok;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>