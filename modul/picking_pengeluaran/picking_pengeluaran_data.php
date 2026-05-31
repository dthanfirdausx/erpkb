<?php
include "../../inc/config.php";

$columns = array(
    'tmp_pengeluaran2.no_sj',
    'tmp_pengeluaran2.penerima',
    'tmp_pengeluaran2.no_aju',
    'tmp_pengeluaran2.tgl_aju',
    'tmp_pengeluaran2.no_dokpab',
    'tmp_pengeluaran2.tgl_dokpab',
   // 'tmp_pengeluaran2.valuta',
    'tmp_pengeluaran2.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('tgl_dokpab','tmp_pengeluaran2.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("tmp_pengeluaran2.tgl_dokpab");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by tmp_pengeluaran2.id";

  $query = $datatable->get_custom("select tmp_pengeluaran2.jenis_dokpab, tmp_pengeluaran2.no_sj,tmp_pengeluaran2.penerima,tmp_pengeluaran2.no_aju,tmp_pengeluaran2.tgl_aju,tmp_pengeluaran2.no_dokpab,tmp_pengeluaran2.tgl_dokpab,tmp_pengeluaran2.valuta,tmp_pengeluaran2.id from tmp_pengeluaran2
    left join pengeluaran on pengeluaran.no_aju=tmp_pengeluaran2.no_aju where pengeluaran.no_aju is null  ",$columns);

  //buat inisialisasi array data
  $data = array(); 

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
     $ResultData[] = "<a href='".base_url()."index.php/picking-pengeluaran/edit/".$value->no_aju."' class='btn btn-primary'><i class='fa  fa-exchange'></i></a>";
  
    $ResultData[] = $value->jenis_dokpab;
    $ResultData[] = $value->penerima;
    $ResultData[] = $value->no_aju;
    $ResultData[] = $value->tgl_aju;
    $ResultData[] = $value->no_dokpab;
    $ResultData[] = $value->tgl_dokpab;
    // $ResultData[] = $value->valuta;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>