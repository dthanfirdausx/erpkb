<?php
session_start();
include "../../inc/config.php";
     

$columns = array(
    'mutasi_bahanbaku.kd_barang',
    'mutasi_bahanbaku.nm_barang',
    'mutasi_bahanbaku.satuan',
    'mutasi_bahanbaku.saldo_awal',
    'mutasi_bahanbaku.pemasukan',
    'mutasi_bahanbaku.pengeluaran',
    'mutasi_bahanbaku.penyesuaian',
    'mutasi_bahanbaku.saldo_akhir',
    'mutasi_bahanbaku.stock_opname',
    'mutasi_bahanbaku.selisih',
    'mutasi_bahanbaku.ket', 
    'mutasi_bahanbaku.userid', 
    'mutasi_bahanbaku.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('type','mutasi_bahanbaku.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("mutasi_bahanbaku.id");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by mutasi_bahanbaku.id";

  $query = $datatable->get_custom("select closing, mutasi_bahanbaku.kd_barang,mutasi_bahanbaku.nm_barang,mutasi_bahanbaku.satuan,mutasi_bahanbaku.saldo_awal,mutasi_bahanbaku.pemasukan,mutasi_bahanbaku.pengeluaran,mutasi_bahanbaku.penyesuaian,mutasi_bahanbaku.saldo_akhir,mutasi_bahanbaku.stock_opname,mutasi_bahanbaku.selisih,mutasi_bahanbaku.ket,mutasi_bahanbaku.userid,mutasi_bahanbaku.id from v_mutasi_bahan_baku mutasi_bahanbaku where saldo_awal!='0' and saldo_akhir!='0'  ",$columns); 

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {
      $saldo_awal = $value->saldo_awal;
    if ($value->closing!='0') {
       $saldo_awal = $value->closing;
    }
    $saldo_akhir = ($saldo_awal + $value->pemasukan) -  $value->pengeluaran;

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kd_barang;
    $ResultData[] = $value->nm_barang;
    //$ResultData[] = $value->type;
    $ResultData[] = $value->satuan;
    $ResultData[] = round($saldo_awal,2);
    $ResultData[] = "<a style='cursor:pointer' onclick='info_detail(\"$value->kd_barang\",1)' >".round($value->pemasukan,2)."</a>";
    $ResultData[] = "<a style='cursor:pointer' onclick='info_detail(\"$value->kd_barang\",2)' >".round($value->pengeluaran,2)."</a>";
    $ResultData[] = round($value->penyesuaian,2);  
    $ResultData[] = round($saldo_akhir,2);
    $ResultData[] = round($value->stock_opname,2);
    $ResultData[] = round($value->selisih,2);
    $ResultData[] = $value->ket;
    // $ResultData[] = $value->userid;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>