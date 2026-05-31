<?php
include "../../inc/config.php";

$columns = array(
    'incoming_terima.nomor',
    'incoming_terima.no_lpb',
    'incoming_terima.tgl_lpb',
    'incoming_terima.dari',
    'incoming_terima.no_spb',
    'incoming_terima.tgl_spb',
    'incoming_terima.dept',
    'incoming_terima.name_ppc',
    'incoming_terima.catatan',
    'incoming_terima.no_lpb',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('catatan','incoming_terima.no_lpb');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("incoming_terima.no_lpb");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by incoming_terima.no_lpb";

  $query = $datatable->get_custom("select id, incoming_terima.nomor,incoming_terima.no_lpb,incoming_terima.tgl_lpb,incoming_terima.dari,incoming_terima.no_spb,incoming_terima.tgl_spb,incoming_terima.kd_dept as dept,incoming_terima.name_ppc,incoming_terima.catatan from v_transfer_gudang incoming_terima",$columns); 

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->nomor;
    $ResultData[] = $value->no_lpb;
    $ResultData[] = $value->tgl_lpb;
    $ResultData[] = $value->dari;
    $ResultData[] = $value->no_spb;
    $ResultData[] = $value->tgl_spb;
    $ResultData[] = $value->dept;
    $ResultData[] = $value->name_ppc;
    $ResultData[] = $value->catatan;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>