<?php
include "../../inc/config.php";

$columns = array(
    'rekening.no_rek',
    'r.nama_rek',
    'rekening.nama_rek',
    'coa_kategori.kategori_akun',
    'rekening.id',
  ); 

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('kat_coa','rekening.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("rekening.id");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by rekening.id";

  $query = $datatable->get_custom("select rekening.no_rek,r.nama_rek as induk,rekening.nama_rek,coa_kategori.kategori_akun,rekening.id from rekening left join coa_kategori on rekening.kat_coa=coa_kategori.id
    left join rekening r on r.no_rek=rekening.induk",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->no_rek;
    $ResultData[] = $value->induk;
    $ResultData[] = $value->nama_rek;
    $ResultData[] = $value->kategori_akun;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>