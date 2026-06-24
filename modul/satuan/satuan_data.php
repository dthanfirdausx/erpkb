<?php
include "../../inc/config.php";

$columns = array(
    'satuan.kode',
    'satuan.jenis',
    'satuan.nama',
    'satuan.kode',
  );

  $datatable->set_numbering_status(1);
  $datatable->set_order_by("satuan.kode");
  $datatable->set_order_type("desc");

  $query = $datatable->get_custom("select satuan.kode,satuan.jenis,satuan.nama from satuan",$columns);

  $data = array();
  $i=1;
  foreach ($query as $value) {
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
    $ResultData[] = $value->kode;
    $ResultData[] = $value->jenis;
    $ResultData[] = $value->nama;
    $ResultData[] = $value->kode;
    $data[] = $ResultData;
    $i++;
  }

$datatable->set_data($data);
$datatable->create_data();
?>
