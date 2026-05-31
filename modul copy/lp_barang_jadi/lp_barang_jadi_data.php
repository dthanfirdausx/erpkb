<?php
include "../../inc/config.php";

$columns = array(
    'brgjadi.no_bpb',
    'brgjadi.tgl_bpb',
    'brgjadi.project',
    'brgjadi.dept',
    'brgjadi.name_ppc',
    'brgjadi.catatan',
    'brgjadi.userid',
    'brgjadi.no_bpb',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('catatan','brgjadi.no_bpb');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("brgjadi.no_bpb");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by brgjadi.no_bpb";

  $query = $datatable->get_custom("select id_produksi,brgjadi.no_bpb,brgjadi.tgl_bpb,brgjadi.project,brgjadi.dept,brgjadi.name_ppc,brgjadi.catatan,brgjadi.userid from brgjadi",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {
    $dept = json_decode($value->dept, true);

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->no_bpb;
    $ResultData[] = $value->tgl_bpb;
    $ResultData[] = $value->project;
    $ResultData[] = is_array($dept) ? implode(', ', $dept) : $value->dept;
    $ResultData[] = $value->name_ppc;
    $ResultData[] = $value->catatan;
    $ResultData[] = $value->userid;
    $ResultData[] = $value->id_produksi;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>