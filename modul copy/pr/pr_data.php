<?php
include "../../inc/config.php";

$columns = array(
    'roin.no_ro',
    'roin.tgl_ro',
    'dept.nm_dept',
    'roin.name_ppc',
    'roin.catatan',
    'roin.no_ro',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('catatan','roin.no_ro');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("roin.no_ro");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by roin.no_ro";

  $query = $datatable->get_custom("select * from v_ro roin",$columns); 

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {
    $status = "<span class='label label-warning'>Menunggu ACC</span>";
    if ($value->status=='1') {
       $status = "<span class='label label-warning'>Proses di BOD</span>";
    }else if ($value->status=='2') {
       $status = "<span class='label label-success'>Selesai</span>";
    }else if ($value->status=='3') {
       $status = "<span class='label label-danger'>Di Tolak</span>";
    }
    $btn_price  = "<a href='".base_url()."index.php/pr/price/$value->id'  class='btn btn-primary btn-sm' data-toggle='tooltip' title='Detail'>Price comparison <i class='fa fa-money'></i></a>"; 
    $btn_verif  = "<a href='".base_url()."index.php/pr/verif_price/$value->id'  class='btn btn-success btn-sm' data-toggle='tooltip' title='Detail'>Verifikasi Harga <i class='fa fa-check'></i></a>"; 
    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
    $ResultData[] = "tes";
    $ResultData[] = "<button onclick='detail_ro(\"$value->no_ro\")'  class='btn btn-success btn-sm' data-toggle='tooltip' title='Detail'>Detail Barang <i class='badge'>$value->jml</i></button> $btn_price $btn_verif"; 
    $ResultData[] = $value->no_ro;
    $ResultData[] = $value->tgl_ro;
    $ResultData[] = $value->nm_dept;
    $ResultData[] = $value->name_ppc;
    $ResultData[] = $value->catatan;
    $ResultData[] = $status;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>