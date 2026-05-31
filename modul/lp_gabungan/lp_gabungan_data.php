<?php
include "../../inc/config.php";

$columns = array(
    'vbrgjadi.no_bpb',
    'vbrgjadi.tgl_bpb',
    'vbrgjadi.project',
    'vbrgjadi.dept',
    'vbrgjadi.name_ppc',
    'vbrgjadi.catatan',
    'vbrgjadi.userid',
    'vbrgjadi.no_bpb',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('catatan','vbrgjadi.');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("vbrgjadi.no_bpb");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by vbrgjadi.";

  $query = $datatable->get_custom("select vbrgjadi.no_bpb,vbrgjadi.tgl_bpb,vbrgjadi.project,vbrgjadi.dept,vbrgjadi.name_ppc,vbrgjadi.catatan,vbrgjadi.userid,vbrgjadi.no_bpb from vbrgjadi",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
    $ResultData[] = $value->no_bpb;
    $ResultData[] = $value->tgl_bpb;
    $ResultData[] = $value->project;
    $ResultData[] = $value->dept;
    $ResultData[] = $value->name_ppc;
    $ResultData[] = $value->catatan;
    $ResultData[] = $value->userid;
    $ResultData[] = $value->no_bpb;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>