<?php
include "../../inc/config.php";

$columns = array(
    'v_neraca.kategori_akun',
    'v_neraca.kategori',
    'v_neraca.no_rek',
    'v_neraca.nama_rek',
    'v_neraca.total_debet',
    'v_neraca.total_kredit',
    'v_neraca.',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('nama_rek','v_neraca.');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("v_neraca.kategori_akun");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by v_neraca.";

  $query = $datatable->get_custom("select v_neraca.kategori_akun,v_neraca.kategori,v_neraca.no_rek,v_neraca.nama_rek,v_neraca.total_debet,v_neraca.total_kredit,v_neraca. from v_neraca",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kategori_akun;
    $ResultData[] = $value->kategori;
    $ResultData[] = $value->no_rek;
    $ResultData[] = $value->nama_rek;
    $ResultData[] = $value->total_debet;
    $ResultData[] = $value->total_kredit;
    $ResultData[] = $value->;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>