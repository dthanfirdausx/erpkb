<?php
include "../../inc/config.php";

$columns = array(
    'tmp_pemasukan1.no_aju',
    'tmp_pemasukan1.tgl_aju',
    'tmp_pemasukan1.jenis_dokpab',
    'tmp_pemasukan1.no_dokpab',
    'tmp_pemasukan1.tgl_dokpab',
    'tmp_pemasukan1.no_bpb',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('tgl_dokpab','tmp_pemasukan1.no_bpb');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("tmp_pemasukan1.tgl_dokpab");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by tmp_pemasukan1.no_bpb";

  $query = $datatable->get_custom("select tmp_pemasukan1.no_aju,tmp_pemasukan1.tgl_aju,tmp_pemasukan1.jenis_dokpab,tmp_pemasukan1.no_dokpab,tmp_pemasukan1.tgl_dokpab,tmp_pemasukan1.no_bpb from tmp_pemasukan1
    left join pemasukan on pemasukan.no_aju=tmp_pemasukan1.no_aju where pemasukan.no_aju is null  ",$columns);

  //buat inisialisasi array data 
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = "<a href='".base_url()."index.php/picking/edit/".$value->no_aju."' class='btn btn-primary'><i class='fa  fa-exchange'></i></a>";
  
    $ResultData[] = $value->no_aju;
    $ResultData[] = $value->tgl_aju;
    $ResultData[] = $value->jenis_dokpab;
    $ResultData[] = $value->no_dokpab;
    $ResultData[] = $value->tgl_dokpab;
    $ResultData[] = $value->no_bpb;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>