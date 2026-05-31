<?php
include "../../inc/config.php";

$columns = array(
    'vtotalstockprodbsj.kd_barang',
    'vtotalstockprodbsj.nm_barang',
    'vtotalstockprodbsj.stock',
    'vtotalstockprodbsj.satuan',
    'vtotalstockprodbsj.nm_kategori',
    'vtotalstockprodbsj.kd_barang',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('kd_kategori','vtotalstockprodbsj.');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("vtotalstockprodbsj.kd_barang");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by vtotalstockprodbsj.";

  $query = $datatable->get_custom("select vtotalstockprodbsj.kd_barang,vtotalstockprodbsj.nm_barang,vtotalstockprodbsj.stock,vtotalstockprodbsj.satuan,vtotalstockprodbsj.nm_kategori,vtotalstockprodbsj.kd_barang from vtotalstockprodbsj",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kd_barang;
    $ResultData[] = $value->nm_barang;
    $ResultData[] = $value->stock;
    $ResultData[] = $value->satuan;
    $ResultData[] = $value->nm_kategori;
    $ResultData[] = $value->kd_barang;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>