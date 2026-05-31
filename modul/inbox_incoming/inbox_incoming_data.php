<?php
include "../../inc/config.php";

$columns = array(
    'v_inbox_incoming.nm_dari',
    'v_inbox_incoming.no_transfer',
    'v_inbox_incoming.tgl_transfer',
    'v_inbox_incoming.dept',
    'v_inbox_incoming.user',
    'v_inbox_incoming.catatan',
   // 'produksi_terima.user_trt',
    'v_inbox_incoming.no_transfer',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('dari','produksi_terima.');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("v_inbox_incoming.tgl_transfer");

  //set order by type
  $datatable->set_order_type("desc"); 

  //set group by column
  //$new_table->group_by = "group by produksi_terima.";

  $query = $datatable->get_custom("select id_transfer as id, jml, nm_dari as dari,no_transfer as no_spb,tgl_transfer as tgl_spb,
  dept,user as name_ppc,ket as catatan from  v_inbox_incoming   ",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {
    $btn_terima = "<button class='btn btn-success' style='font-size:12px' onclick='terima_barang(\"$value->no_spb\",$value->id)'><i class='fa fa-check'></i></button> <button class='btn btn-primary' style='font-size:12px' onclick='detail_barang(\"$value->no_spb\",$value->id)'><i class='badge'>$value->jml</i>  Detail Barang</button>";
    //array data
    $ResultData = array();
    $ResultData[] = $btn_terima;
  
    $ResultData[] = $value->dari;
    $ResultData[] = $value->no_spb;
    $ResultData[] = $value->tgl_spb;
    $ResultData[] = $value->dept;
    $ResultData[] = $value->name_ppc;
    $ResultData[] = $value->catatan;
   // $ResultData[] = $value->user_trt;
    $ResultData[] = $value->no_spb;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>