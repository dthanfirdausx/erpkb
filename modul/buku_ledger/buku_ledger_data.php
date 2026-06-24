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
    'jurnal_header.id',
    'jurnal_header.no_jurnal',
    'jurnal_header.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('no_jurnal','jurnal_header.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("jurnal_header.id");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by jurnal_header.id";

  $query = $datatable->get_custom("select jurnal_header.id,jurnal_header.no_jurnal from jurnal_header",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->id;
    $ResultData[] = $value->no_jurnal;
    $ResultData[] = '<a href="'.base_index().'buku-ledger/detail/'.$value->id.'" class="btn btn-success btn-sm" data-toggle="tooltip" title="'.fin_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></a>';

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>
