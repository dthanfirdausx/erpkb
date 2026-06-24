<?php
include "../../inc/config.php";

$columns = array(
    'jenisbcmasuk.kode',
    'jenisbcmasuk.jenis',
    'jenisbcmasuk.nama',
    'jenisbcmasuk.kode',
  );

  $datatable->set_numbering_status(1);
  $datatable->set_order_by("jenisbcmasuk.kode");
  $datatable->set_order_type("desc");

  $query = $datatable->get_custom("select jenisbcmasuk.kode,jenisbcmasuk.jenis,jenisbcmasuk.nama from jenisbcmasuk",$columns);

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
