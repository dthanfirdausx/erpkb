<?php
session_start();
include "../../inc/config.php";

$columns = array(
    'kd_barang',
    'kd_barang',
    'nm_barang',
    'satuan',
  
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('catatan','ro.');
  
  //set numbering is true
  //$datatable->set_numbering_status(1);

  //set order by column 
  $datatable->set_order_by("barang_temp");

  //set order by type
  $datatable->set_order_type("desc");    

  //set group by column
  //$new_table->group_by = "group by ro.";

  $query = $datatable->get_custom("select * from v_ro_barang  " ,$columns);

  //buat inisialisasi array data
  $data = array(); 

  $i=1;
  foreach ($query as $value) { 

    //array data
    $checked = "";
    if ($value->barang_temp=='1' && $value->user==$_SESSION['username']) {
      $checked = " checked "; 
    }
    $ResultData = array();

    $ResultData[] = "<input id='id_barang_$value->id_bom' type='checkbox' style='width:20px;height:20px' $checked onchange='cek_barang($value->id_bom)'>"; 
  
    $ResultData[] = $value->kd_barang;
    $ResultData[] = $value->nm_barang;
    $ResultData[] = $value->satuan;
   // $ResultData[] = "<input type='text' class='form-control' onkeyup='update_jml_order($value->id_barang,this.value)'>";
    $ResultData[] = $value->kd_barang;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>