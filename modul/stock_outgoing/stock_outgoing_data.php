<?php
include "../../inc/config.php";

$columns = array(
    'vtotalstockprodbb.kd_barang',
    'vtotalstockprodbb.nm_barang',
    'vtotalstockprodbb.Stock',
    'vtotalstockprodbb.satuan',
    'vtotalstockprodbb.nm_kategori',
    'vtotalstockprodbb.kd_barang',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('kd_kategori','vtotalstockprodbb.');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("vtotalstockprodbb.kd_barang");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by vtotalstockprodbb.";
 $wh="";
  if ($_POST['kategori']!='') {
    $wh = " and kd_kategori='".$_POST['kategori']."' ";
  }

  $query = $datatable->get_custom("select id,id_barang, vtotalstockprodbb.kd_barang,vtotalstockprodbb.nm_barang,vtotalstockprodbb.Stock,vtotalstockprodbb.satuan,vtotalstockprodbb.nm_kategori,vtotalstockprodbb.kd_barang from v_stock_outgoing vtotalstockprodbb where 1=1 $wh",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
    $ResultData[] = "<button class='btn btn-primary' style='font-size:12px' id='btn_$value->id' onclick='sinkron_stock(\"$value->id_barang\",\"4\",\"$value->id\")'><i class='fa fa-gear'></i> Sinkron Stock </button>";
    $ResultData[] = $value->kd_barang;
    $ResultData[] = $value->nm_barang;
    $ResultData[] = "<a style='cursor:pointer' onclick='get_detail_stock(\"$value->kd_barang\")'>".$value->Stock."</a>";
    $ResultData[] = $value->satuan;
    $ResultData[] = $value->nm_kategori;
    $ResultData[] = $value->kd_barang;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>