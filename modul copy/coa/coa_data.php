<?php
include "../../inc/config.php";

$columns = array(
    'rekening.no_rek',
    'rekening.induk',
    'rekening.nama_rek',
    'coa_kategori.kategori',
    'rekening.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('jenis','rekening.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("rekening.no_rek");

  //set order by type
  $datatable->set_order_type("asc");

  //set group by column
  //$new_table->group_by = "group by rekening.id";

  $query = $datatable->get_custom("select rekening.no_rek,rekening.induk,rekening.nama_rek,coa_kategori.kategori,rekening.id from rekening inner join coa_kategori on rekening.kat_coa=coa_kategori.id",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->no_rek;
    $ResultData[] = $value->induk;
    $ResultData[] = $value->nama_rek;
    $ResultData[] = $value->kategori;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>