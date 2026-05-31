<?php
include "../../inc/config.php";

$columns = array(
    'v_outgoing_terima.no_transfer',
    'v_outgoing_terima.tgl_transfer',
    'v_outgoing_terima.nm_dari',
    'v_outgoing_terima.no_terima',
    'v_outgoing_terima.tgl_terima',
    'v_outgoing_terima.nm_dept',
    'v_outgoing_terima.user',
    'v_outgoing_terima.ket',
    'v_outgoing_terima.user_terima',
    'v_outgoing_terima.user',
    'v_outgoing_terima.id_transfer',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('userid','produksi_terima.no_lpb');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("v_outgoing_terima.tgl_terima");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by produksi_terima.no_lpb";

  $query = $datatable->get_custom("select id_transfer, jml, no_terima as no_lpb,tgl_terima as tgl_lpb,nm_dari as dari,
  no_transfer as no_spb,tgl_transfer as tgl_spb,nm_dept as dept,user as name_ppc,ket as catatan,user_terima as user_trt,user as userid from v_outgoing_terima ",$columns);

  //buat inisialisasi array data
  $data = array();
 
  $i=1;
  foreach ($query as $value) {
    $btn_detail = " <button class='btn btn-primary' style='font-size:12px' onclick='detail_barang(\"$value->no_spb\",$value->id_transfer)'><i class='badge'>$value->jml</i>  Detail Barang</button>";
    //array data
    $ResultData = array();
    $ResultData[] = $btn_detail;
  
    $ResultData[] = $value->no_lpb;
    $ResultData[] = $value->tgl_lpb;
    $ResultData[] = $value->dari;
    $ResultData[] = $value->no_spb;
    $ResultData[] = $value->tgl_spb;
    $ResultData[] = $value->dept;
    $ResultData[] = $value->name_ppc;
    $ResultData[] = $value->catatan;
    $ResultData[] = $value->user_trt;
    $ResultData[] = $value->userid;
    $ResultData[] = $value->id_transfer;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>