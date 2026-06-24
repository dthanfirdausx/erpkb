<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
include "../../inc/config.php";

$columns = array(
    'x.kd_barang',
    'x.nm_barang',
    'x.Stock',
    'x.satuan',
    'x.nm_kategori',
    'x.kd_barang',
  );

  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("x.kd_barang");

  //set order by type
  $datatable->set_order_type("desc");

 $wh="";
  if (!empty($_POST['kategori'])) {
    $kategori = str_replace("'", "''", $_POST['kategori']);
    $wh = " and x.kd_kategori='".$kategori."' ";
  }

  $qStock = "SELECT
      MIN(sl.id) id,
      MIN(b.id) id_barang,
      b.kd_barang,
      b.nm_barang,
      COALESCE(SUM(sl.qty_sisa),0) Stock,
      b.satuan,
      b.kd_kategori,
      k.nm_kategori
    FROM stock_layer sl
    INNER JOIN barang b ON b.kd_barang=sl.kode
    LEFT JOIN kategori k ON k.kd_kategori=b.kd_kategori
    WHERE sl.qty_sisa>0
      AND sl.lokasi='OUTGOING'
    GROUP BY b.kd_barang,b.nm_barang,b.satuan,b.kd_kategori,k.nm_kategori";
  $query = $datatable->get_custom("select x.id,x.id_barang,x.kd_barang,x.nm_barang,x.Stock,x.satuan,x.nm_kategori,x.kd_barang from ($qStock) x where 1=1 $wh",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {
    $kd_barang_attr = htmlspecialchars($value->kd_barang, ENT_QUOTES, 'UTF-8');
    $kd_barang_js = htmlspecialchars(json_encode($value->kd_barang), ENT_QUOTES, 'UTF-8');

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
    $ResultData[] = $kd_barang_attr;
    $ResultData[] = htmlspecialchars($value->nm_barang, ENT_QUOTES, 'UTF-8');
    $ResultData[] = "<a class='qty-link' onclick='get_detail_stock(".$kd_barang_js.")'>".number_format((float)$value->Stock, 5, ",", ".")."</a>";
    $ResultData[] = htmlspecialchars($value->satuan, ENT_QUOTES, 'UTF-8');
    $ResultData[] = htmlspecialchars($value->nm_kategori, ENT_QUOTES, 'UTF-8');
    $ResultData[] = "<button type='button' class='btn btn-info btn-xs' onclick='get_detail_stock(".$kd_barang_js.")'><i class='fa fa-search'></i> Detail Layer</button>";

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>
