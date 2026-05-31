<?php
include "../../inc/config.php";

$columns = array(
    'jurnal_umum.no_jurnal',
    'jurnal_umum.tgl_jurnal',
    'jurnal_umum.ket',
    'jurnal_umum.no_rek',
    'jurnal_umum.debet',
    'jurnal_umum.kredit',
    'jurnal_umum.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('kurs','jurnal_umum.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("jurnal_umum.id");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by jurnal_umum.id";

  $query = $datatable->get_custom("select jurnal_umum.no_jurnal,jurnal_umum.tgl_jurnal,jurnal_umum.ket,jurnal_umum.no_rek,jurnal_umum.debet,jurnal_umum.kredit,jurnal_umum.id from jurnal_umum",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->no_jurnal;
    $ResultData[] = $value->tgl_jurnal;
    $ResultData[] = $value->ket;
    $ResultData[] = $value->no_rek;
    $ResultData[] = $value->debet;
    $ResultData[] = $value->kredit;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>