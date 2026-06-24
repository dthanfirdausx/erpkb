<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
include "../../inc/config.php";

$columns = array(
    'sales_invoice.bill_to',
    'sales_invoice.ship_to',
    'sales_invoice.invoice_date',
    'sales_invoice.no_sales_invoice',
    'sales_invoice.nopo',
    'sales_invoice.term',
    'sales_invoice.valuta',
    'sales_invoice.ship_date',
    'sales_invoice.no_do',
    'sales_invoice.billing_status',
    'sales_invoice.id_sales',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('tax','sales_invoice.id_sales');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("sales_invoice.id_sales");

  //set order by type
  $datatable->set_order_type("desc");

  $tgl_awal  = $_POST['tgl_awal'];
$tgl_akhir = $_POST['tgl_akhir'];
$customer  = $_POST['customer'];

$where = " ";

if ($tgl_awal != "" && $tgl_akhir != "") {
    $where .= " AND sales_invoice.invoice_date BETWEEN '$tgl_awal' AND '$tgl_akhir' ";
}

if ($customer != "all") {
    $where .= " AND p.kode_penerima = '$customer' ";
}

  //set group by column
  //$new_table->group_by = "group by sales_invoice.id_sales";

  $query = $datatable->get_custom("select no_sales_invoice, sales_invoice.bill_to,sales_invoice.ship_to,
sales_invoice.invoice_date,sales_invoice.invoice_no,sales_invoice.nopo,sales_invoice.term,
sales_invoice.valuta,sales_invoice.ship_date,sales_invoice.no_do,sales_invoice.billing_status,sales_invoice.id_sales,p.nama ,pp.nama as nama2
from sales_invoice 
join penerima p
on p.kode_penerima=sales_invoice.bill_to
join penerima pp
on pp.kode_penerima=sales_invoice.ship_to where 1=1 $where",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->nama;
    $ResultData[] = $value->nama2;
    $ResultData[] = $value->invoice_date;
    $ResultData[] = $value->no_sales_invoice;
    $ResultData[] = $value->nopo;
    $ResultData[] = $value->term;
    $ResultData[] = $value->valuta;
    $ResultData[] = $value->ship_date;
    $ResultData[] = $value->no_do;
    $statusClass = $value->billing_status === 'POSTED' ? 'success' : ($value->billing_status === 'CANCELLED' ? 'danger' : 'default');
    $ResultData[] = '<span class="label label-'.$statusClass.'">'.$value->billing_status.'</span>';
    $ResultData[] = $value->id_sales;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>
