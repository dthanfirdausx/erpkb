<?php
include "../../inc/config.php";

$columns = array(
    'packing_list.no_packing_list',
    'packing_list.no_sj',
    'packing_list.tgl_sj',
    'penerima.nama',
   // 'pemilik.nama_pemilik',
    'packing_list.no_invoice',
    'packing_list.no_po',
    'packing_list.valuta',
    'packing_list.vehicle_no',
    'packing_list.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('vehicle_no','packing_list.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("packing_list.id");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by packing_list.id";

  $query = $datatable->get_custom("select packing_list.no_packing_list,packing_list.no_sj,packing_list.tgl_sj,penerima.nama,packing_list.no_invoice,packing_list.no_po,packing_list.valuta,packing_list.vehicle_no,packing_list.id from packing_list inner join penerima on packing_list.penerima=penerima.kode_penerima ",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->no_packing_list;
    $ResultData[] = $value->no_sj;
    $ResultData[] = $value->tgl_sj;
    $ResultData[] = $value->nama;
   // $ResultData[] = $value->nama_pemilik;
    $ResultData[] = $value->no_invoice;
    $ResultData[] = $value->no_po;
    $ResultData[] = $value->valuta;
    $ResultData[] = $value->vehicle_no;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>