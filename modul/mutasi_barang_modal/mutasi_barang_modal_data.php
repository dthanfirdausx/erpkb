<?php
session_start();
include "../../inc/config.php";
     
$columns = array(  
    'mutasi_barangmodal.kd_barang',
    'mutasi_barangmodal.nm_barang', 
   // 'mutasi_barangmodal.type',
    'mutasi_barangmodal.satuan',
    'mutasi_barangmodal.saldo_awal',
    'mutasi_barangmodal.pemasukan',
    'mutasi_barangmodal.pengeluaran',
    'mutasi_barangmodal.penyesuaian',
    'mutasi_barangmodal.saldo_akhir',
    'mutasi_barangmodal.stock_opname',
    'mutasi_barangmodal.selisih',
    'mutasi_barangmodal.ket',
    'mutasi_barangmodal.userid',
    'mutasi_barangmodal.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('type','mutasi_barangmodal.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("mutasi_barangmodal.id");

  //set order by type 
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by mutasi_barangmodal.id";

  $query = $datatable->get_custom("select mutasi_barangmodal.kd_barang,mutasi_barangmodal.nm_barang,mutasi_barangmodal.satuan,mutasi_barangmodal.saldo_awal,mutasi_barangmodal.pemasukan,mutasi_barangmodal.pengeluaran,mutasi_barangmodal.penyesuaian,mutasi_barangmodal.saldo_akhir,mutasi_barangmodal.stock_opname,mutasi_barangmodal.selisih,mutasi_barangmodal.ket,mutasi_barangmodal.userid,mutasi_barangmodal.id from v_mutasi_barang_modal mutasi_barangmodal where saldo_awal!='0' and saldo_akhir!='0'",$columns); 

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kd_barang;
    $ResultData[] = $value->nm_barang;
   // $ResultData[] = $value->type;
    $ResultData[] = $value->satuan;
    $ResultData[] = number_format($value->saldo_awal,2,",",".");
    $ResultData[] = "<a style='cursor:pointer' onclick='info_detail(\"$value->kd_barang\",1)' >".number_format($value->pemasukan,2,",",".")."</a>";
    $ResultData[] = "<a style='cursor:pointer' onclick='info_detail(\"$value->kd_barang\",2)' >".number_format($value->pengeluaran,2,",",".")."</a>";
    $ResultData[] = number_format($value->penyesuaian,2,",",".");
    $ResultData[] = number_format($value->saldo_akhir,2,",",".");
    $ResultData[] = number_format($value->stock_opname,2,",",".");
    $ResultData[] = number_format($value->selisih,2,",",".");
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