<?php
include "../../inc/config.php";

$columns = array(
    'bom.kodebj',
    'bom.nm_barang',
    'bom.satuan',
    'bom.jumlah',
    'bom.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('jumlah','bom.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("bom.id");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by bom.id";

  $query = $datatable->get_custom("select bom.kodebj,bom.nm_barang,bom.satuan,bom.jumlah,bom.id from bom",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kodebj;
    $ResultData[] = $value->nm_barang;
    $ResultData[] = $value->satuan;
    $ResultData[] = $value->jumlah;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>