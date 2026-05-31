<?php
include "../../inc/config.php";

$columns = array(
    'mrp.no_order',
    'mrp.style',
    'mrp.order_qty',
    'mrp.term',
    'mrp.delivery',
    'mrp.receipt',
    'mrp.po',
    'penerima.nama',
    'mrp.Id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('buyer','mrp.Id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("mrp.Id");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by mrp.Id";

  $query = $datatable->get_custom("select mrp.no_order,mrp.style,mrp.order_qty,mrp.term,mrp.delivery,mrp.receipt,mrp.po,penerima.nama,mrp.Id from mrp left join penerima on mrp.buyer=penerima.kode_penerima",$columns); 

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->no_order;
    $ResultData[] = $value->style;
    $ResultData[] = $value->order_qty;
    $ResultData[] = $value->term;
    $ResultData[] = $value->delivery;
    $ResultData[] = $value->receipt;
    $ResultData[] = $value->po;
    $ResultData[] = '<a style="cursor:pointer" onclick="detail_bahan('.$value->Id.')" class="btn btn-success btn-sm" data-toggle="tooltip" title="Detail"><i class="fa fa-eye"></i> Detail Bahan</a>';
    $ResultData[] = $value->Id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>