<?php
include "../../inc/config.php";

$columns = array(
    'h_pegawai.nik',
    'h_pegawai.npwp',
    'h_pegawai.namaPegwai',
    'h_pegawai.kelamin',
    'h_agama.namaAgama',
    'h_pegawai.noHp',
    'h_pegawai.email',
    'h_pegawai.alamat',
    'h_pegawai.idPegawai',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('foto','h_pegawai.idPegawai');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("h_pegawai.idPegawai");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by h_pegawai.idPegawai";

  $query = $datatable->get_custom("select h_pegawai.nik,h_pegawai.npwp,h_pegawai.namaPegwai,h_pegawai.kelamin,h_agama.namaAgama,h_pegawai.noHp,h_pegawai.email,h_pegawai.alamat,h_pegawai.idPegawai from h_pegawai inner join h_agama on h_pegawai.agama=h_agama.idAgama",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->nik;
    $ResultData[] = $value->npwp;
    $ResultData[] = $value->namaPegwai;
    $ResultData[] = $value->kelamin;
    $ResultData[] = $value->namaAgama;
    $ResultData[] = $value->noHp;
    $ResultData[] = $value->email;
    $ResultData[] = $value->alamat;
    $ResultData[] = $value->idPegawai;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>