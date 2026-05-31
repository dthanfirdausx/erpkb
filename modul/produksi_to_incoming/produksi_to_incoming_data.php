<?php
include "../../inc/config.php";

$columns = array(
    'produksi_incoming.no_spb',
    'produksi_incoming.tgl_spb',
    'produksi_incoming.dept',
    'produksi_incoming.name_ppc',
    'produksi_incoming.catatan',
    'produksi_incoming.userid',
    'produksi_incoming.no_spb',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('catatan','produksi_incoming.no_spb');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("produksi_incoming.no_spb");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by produksi_incoming.no_spb";

  $query = $datatable->get_custom("select produksi_incoming.no_spb,produksi_incoming.tgl_spb,produksi_incoming.dept,produksi_incoming.name_ppc,produksi_incoming.catatan,produksi_incoming.userid from produksi_incoming",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->no_spb;
    $ResultData[] = $value->tgl_spb;
    $ResultData[] = $value->dept;
    $ResultData[] = $value->name_ppc;
    $ResultData[] = $value->catatan;
    $ResultData[] = $value->userid;
    $ResultData[] = $value->0;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>