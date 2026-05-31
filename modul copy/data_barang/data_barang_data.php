<?php
include "../../inc/config.php";

$columns = array(
    'barang.kd_barang',
    'barang.nm_barang',
    'barang.type',
    'barang.satuan',
    'kategori.nm_kategori',
    'barang.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('berat','barang.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("barang.id");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by barang.id";

  $query = $datatable->get_custom("select barang.kd_barang,barang.nm_barang,barang.type,barang.satuan,kategori.nm_kategori,barang.id from barang inner join kategori on barang.kd_kategori=kategori.kd_kategori",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kd_barang;
    $ResultData[] = $value->nm_barang;
    $ResultData[] = $value->type;
    $ResultData[] = $value->satuan;
    $ResultData[] = $value->nm_kategori;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>