<?php
session_start();
include "../../inc/config.php";
   
$columns = array(
    'mutasi_barangjadi.kd_barang',
    'mutasi_barangjadi.nm_barang',
  //  'mutasi_barangjadi.type',
    'mutasi_barangjadi.satuan',
    'mutasi_barangjadi.saldo_awal',
    'mutasi_barangjadi.pemasukan',
    'mutasi_barangjadi.pengeluaran',
    'mutasi_barangjadi.penyesuaian',
    'mutasi_barangjadi.saldo_akhir',
    'mutasi_barangjadi.stock_opname',
    'mutasi_barangjadi.selisih',
    'mutasi_barangjadi.ket',
    'mutasi_barangjadi.userid',
    'mutasi_barangjadi.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('type','mutasi_barangjadi.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("mutasi_barangjadi.id");

  //set order by type 
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by mutasi_barangjadi.id";

  $query = $datatable->get_custom("select mutasi_barangjadi.kd_barang,closing,mutasi_barangjadi.nm_barang,mutasi_barangjadi.satuan,mutasi_barangjadi.saldo_awal,mutasi_barangjadi.pemasukan,mutasi_barangjadi.pengeluaran,mutasi_barangjadi.penyesuaian,mutasi_barangjadi.saldo_akhir,mutasi_barangjadi.stock_opname,mutasi_barangjadi.selisih,mutasi_barangjadi.ket,mutasi_barangjadi.userid,mutasi_barangjadi.id from v_mutasi_barang_jadi mutasi_barangjadi where saldo_awal!='0' and saldo_akhir!='0' and saldo_awal>0 ",$columns);

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
   // $ResultData[] = $value->type;
    $ResultData[] = $value->satuan;
    $ResultData[] = round($saldo_awal,2);
    $ResultData[] = "<a style='cursor:pointer' onclick='info_detail(\"$value->kd_barang\",1)' >".round($value->pemasukan,2)."</a>";
    $ResultData[] = "<a style='cursor:pointer' onclick='info_detail(\"$value->kd_barang\",2)' >".round($value->pengeluaran,2)."</a>";
    $ResultData[] = round($value->penyesuaian,2);
    $ResultData[] = round($saldo_akhir,2); 
    $ResultData[] = round($value->stock_opname,2);
    $ResultData[] = round($value->selisih,2);
    $ResultData[] = $value->ket;
    $ResultData[] = $value->userid;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>