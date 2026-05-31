<?php
include "../../inc/config.php";

$columns = array(
    'pengeluaran.no_sj',
    'pengeluaran.tgl_sj',
    'penerima.nama',
    'pengeluaran.no_invoice',
    'pengeluaran.no_do',
    'pengeluaran.jenis_dokpab',
    'pengeluaran.no_dokpab',
    'pengeluaran.no_aju',
    'pengeluaran.efaktur',
    'pengeluaran.tgl_efaktur',
    'pengeluaran.no_sj',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('tgl_efaktur','pengeluaran.no_sj');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("pengeluaran.no_sj");

  //set order by type
  $datatable->set_order_type("desc");
 
  //set group by column
  //$new_table->group_by = "group by pengeluaran.no_sj";

  $query = $datatable->get_custom("select pengeluaran.id, pengeluaran.no_sj,pengeluaran.tgl_sj,penerima.nama,pengeluaran.no_invoice,pengeluaran.no_do,pengeluaran.jenis_dokpab,pengeluaran.no_dokpab,pengeluaran.no_aju,pengeluaran.efaktur,pengeluaran.tgl_efaktur from pengeluaran inner join penerima on pengeluaran.penerima=penerima.kode_penerima",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->no_sj;
    $ResultData[] = $value->tgl_sj;
    $ResultData[] = $value->nama;
    $ResultData[] = $value->no_invoice;
    $ResultData[] = $value->no_do;
    $ResultData[] = $value->jenis_dokpab;
    $ResultData[] = $value->no_dokpab;
    $ResultData[] = $value->no_aju;
    $ResultData[] = $value->efaktur;
    $ResultData[] = $value->tgl_efaktur;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>