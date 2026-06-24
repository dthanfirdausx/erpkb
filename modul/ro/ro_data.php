<?php
error_reporting(0);
include "../../inc/config.php";

$columns = array(
    'ro.no_ro',
    'ro.tgl_ro',
    'ro.name_ppc',
    'ro.catatan'
);

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('catatan','ro.');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("ro.no_ro");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by ro.";

  $query = $datatable->get_custom("select * from v_ro_new ro ",$columns);

  //buat inisialisasi array data
  $data = array();

  $ii=1;
  foreach ($query as $value) {
   // $qq = $db->query("select")

    //array data
    $kd = explode(",", $value->kd_barang);
    $nm = explode(",", $value->nm_barang);
    $jml = explode(",", $value->jml_brg);
    $nm_brg = "";
    $jml_brg = "";
    // for ($i=0; $i < count($kd) ; $i++) { 
    //   $nm_brg .= $kd[$i]." ".$nm[$i]."<br>";
    //   $jml_brg .= $jml[$i]."<br>";
    // }
    $ResultData = array();
  $ResultData[] = '';
$ResultData[] = $datatable->number($ii);

$ResultData[] = $value->no_ro;
$ResultData[] = date("d-m-Y",strtotime($value->tgl_ro));
$ResultData[] = $value->name_ppc;
$ResultData[] = $value->catatan;
$ResultData[] = $value->id;

    $data[] = $ResultData;
    $ii++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>