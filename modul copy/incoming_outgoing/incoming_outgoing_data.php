<?php
include "../../inc/config.php";

$columns = array(
    'incoming_outgoing.nomor',
    'incoming_outgoing.no_spb',
    'incoming_outgoing.tgl_spb',
    'incoming_outgoing.dept',
    'incoming_outgoing.name_ppc',
    'incoming_outgoing.catatan',
    'incoming_outgoing.no_spb',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('catatan','incoming_outgoing.no_spb');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("incoming_outgoing.no_spb");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by incoming_outgoing.no_spb";

  $query = $datatable->get_custom("select incoming_outgoing.id, incoming_outgoing.nomor,incoming_outgoing.no_spb,incoming_outgoing.tgl_spb,incoming_outgoing.dept,incoming_outgoing.name_ppc,incoming_outgoing.catatan from incoming_outgoing",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->nomor;
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