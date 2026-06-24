<?php
include "../../inc/config.php";

$columns = array(
    'vpemasukantoout.no_spb',
    'vpemasukantoout.tgl_spb',
    'vpemasukantoout.name_ppc',
    'vpemasukantoout.kode',
    'vpemasukantoout.nm_barang',
    'vpemasukantoout.satuan',
    'vpemasukantoout.jumlah',
    'vpemasukantoout.no_spb',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('jumlah','vpemasukantoout.');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("vpemasukantoout.no_spb");

  //set order by type
  $datatable->set_order_type("desc");

  $wh = "";
  if ($_POST['tgl_awal']!='' && $_POST['tgl_akhir']=='') {
    $wh = "and vpemasukantoout.tgl_spb between  '".$_POST['tgl_awal']."' and '".date("Y-m-d")."' ";
  }else if ($_POST['tgl_awal']!='' && $_POST['tgl_akhir']!='') {
    $wh = "and vpemasukantoout.tgl_spb between  '".$_POST['tgl_awal']."' and '".$_POST['tgl_akhir']."' ";
  } 
  
  if ($_POST['kd_barang']!='' && $_POST['kd_barang']!='all' ) {
    $wh.= " and vpemasukantoout.kode='".$_POST['kd_barang']."' ";    
  } 

  if ($_POST['tujuan']!='' && $_POST['tujuan']!='all' ) { 
    $wh.= " and vpemasukantoout.tujuan='".$_POST['tujuan']."' ";     
  }  
   
  //set group by column
  //$new_table->group_by = "group by vpemasukantoout.";

 $source = "SELECT
    CASE
      WHEN t.ke=4 THEN 'outgoing'
      WHEN t.ke=3 THEN 'produksi'
      WHEN t.ke=5 THEN 'praproduksi'
      ELSE COALESCE(dest.nm_bagian,'-')
    END tujuan,
    t.no_transfer no_spb,
    t.tgl_transfer tgl_spb,
    COALESCE(dest.nm_bagian,'-') name_ppc,
    b.kd_barang kode,
    b.nm_barang,
    b.satuan,
    COALESCE(td.jml,0) jumlah
  FROM transfer t
  INNER JOIN transfer_detail td ON td.id_transfer=t.id_transfer
  LEFT JOIN barang b ON b.id=td.id_barang
  LEFT JOIN bagian dest ON dest.id_bagian=t.ke";
 $q1 = "select vpemasukantoout.tujuan, vpemasukantoout.no_spb,vpemasukantoout.tgl_spb,vpemasukantoout.name_ppc,vpemasukantoout.kode,vpemasukantoout.nm_barang,vpemasukantoout.satuan,vpemasukantoout.jumlah,vpemasukantoout.no_spb from ($source) vpemasukantoout where 1=1 $wh";
  $query = $datatable->get_custom("$q1",$columns);
 
  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->no_spb;
    $ResultData[] = $value->tgl_spb;
    $ResultData[] = $value->name_ppc;
    $ResultData[] = $value->kode;
    $ResultData[] = $value->nm_barang;
    $ResultData[] = $value->satuan;
    $ResultData[] = $value->jumlah;
    $ResultData[] = $value->tujuan;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>
