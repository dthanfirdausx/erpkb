<?php
if (!function_exists('fin_t')) {
  function fin_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('fin_h')) {
  function fin_h($key, $fallback = '') { return htmlspecialchars((string) fin_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('fin_js')) {
  function fin_js($key, $fallback = '') { return json_encode(fin_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
include "../../inc/config.php";

$columns = array(
    'v_neraca.kategori_akun',
    'v_neraca.kategori',
    'v_neraca.no_rek',
    'v_neraca.nama_rek',
    'v_neraca.total_debet',
    'v_neraca.total_kredit',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('nama_rek');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("v_neraca.kategori_akun");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by v_neraca.no_rek";

  $query = $datatable->get_custom("select v_neraca.kategori_akun,v_neraca.kategori,v_neraca.no_rek,v_neraca.nama_rek,v_neraca.total_debet,v_neraca.total_kredit from v_neraca",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->kategori_akun;
    $ResultData[] = $value->kategori;
    $ResultData[] = $value->no_rek;
    $ResultData[] = $value->nama_rek;
    $ResultData[] = $value->total_debet;
    $ResultData[] = $value->total_kredit;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>
