<?php
error_reporting(0);
include "../../inc/config.php";

$columns = array(
    'nama_pendek',
    'nomorAju',
    'nomorDokpab',
   // 'status', 
    'tanggalDokumen',
    'tanggalTtd',
  ); 

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('no_lap','bahan.no_lap');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("tanggalDokumen");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by bahan.no_lap";

  $query = $datatable->get_custom("select statusDokumen, id_header,uuid, nama_pendek as 'jenis_dokpab',nomorAju,nomorDokpab,tanggalDokumen,tanggalTtd  from v_wheader_tabel",$columns); 

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {
    $no_aju = str_replace("-", "", $value->nomorAju);
   // $status = get_status_dokumen($no_aju);
    //print_r($status); 
    $status_data =  "<i class='label label-primary'>$value->statusDokumen</i>";
    // if ($status->status=='Success') {
    //   if ($status->dataStatus[0]->kodeProses=='800') {
    //     $status_data = "<i class='label label-success'>".$status->dataStatus[0]->keterangan."</i>";
    //   }else if ($status->dataStatus[0]->kodeProses=='100'){
    //     $status_data = "<i class='label label-success'>".$status->dataStatus[0]->keterangan."</i>";
    //   }else{
    //     $status_data = "<i class='label label-primary'>".$status->dataStatus[0]->keterangan."</i>";
    //   }
      
    // }
    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->jenis_dokpab;
    $ResultData[] = $no_aju;
    $ResultData[] = $value->nomorDokpab;
    //$ResultData[] = $value->status;
    $ResultData[] = $value->tanggalDokumen;
    $ResultData[] = $value->tanggalTtd;
    $ResultData[] = $status_data;
    $ResultData[] = $value->uuid;

    $data[] = $ResultData;
    $i++;
  } 

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>