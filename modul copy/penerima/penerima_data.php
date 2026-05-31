<?php
include "../../inc/config.php";

$columns = array(
    'penerima.kode_penerima',
    'penerima.npwp',
    'penerima.nama',
    'penerima.alamat',
    'penerima.kota',
    'penerima.negara',
    'penerima.notelp',
    'penerima.email',
    'penerima.kode_penerima',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('skep','penerima.kode_penerima');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("penerima.kode_penerima");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by penerima.kode_penerima";

  $query = $datatable->get_custom("select penerima.kode_penerima,penerima.npwp,penerima.nama,penerima.alamat,penerima.kota,penerima.negara,penerima.notelp,penerima.email from penerima",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kode_penerima;
    $ResultData[] = $value->npwp;
    $ResultData[] = $value->nama;
    $ResultData[] = $value->alamat;
    $ResultData[] = $value->kota;
    $ResultData[] = $value->negara;
    $ResultData[] = $value->notelp;
    $ResultData[] = $value->email;
    $ResultData[] = $value->kode_penerima;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>