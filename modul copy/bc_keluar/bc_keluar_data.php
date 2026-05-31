<?php
include "../../inc/config.php";

$columns = array(
    'jenisbckeluar.kode',
    'jenisbckeluar.jenis',
    'jenisbckeluar.nama',
    'jenisbckeluar.kode',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('nama','jenisbckeluar.kode');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("jenisbckeluar.kode");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by jenisbckeluar.kode";

  $query = $datatable->get_custom("select jenisbckeluar.kode,jenisbckeluar.jenis,jenisbckeluar.nama from jenisbckeluar",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kode;
    $ResultData[] = $value->jenis;
    $ResultData[] = $value->nama;
    $ResultData[] = $value->kode;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>