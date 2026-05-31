<?php
include "../../inc/config.php";

$columns = array(
    'h_shift.namaShift',
    'h_shift.masuk',
    'h_shift.keluar',
    'h_shift.shiftId',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('keluar','h_shift.shiftId');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("h_shift.shiftId");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by h_shift.shiftId";

  $query = $datatable->get_custom("select h_shift.namaShift,h_shift.masuk,h_shift.keluar,h_shift.shiftId from h_shift",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->namaShift;
    $ResultData[] = $value->masuk;
    $ResultData[] = $value->keluar;
    $ResultData[] = $value->shiftId;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>