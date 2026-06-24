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
   // 'so.id_quotation',
    'so.no_sales_order',
    'so.so_date',
    'so.nama',
    'so.no_po',
    'so.currency',
    'so.user',
    'so.shipping_address',
    'so.alasan',
    'so.id_sales_order',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('shipping_address','so.id_sales_order');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("so.id_sales_order");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by so.id_sales_order";
  $where = " where 1=1 ";
  $tgl_awal = isset($_POST['tgl_awal']) ? trim($_POST['tgl_awal']) : '';
  $tgl_akhir = isset($_POST['tgl_akhir']) ? trim($_POST['tgl_akhir']) : '';
  $customer = isset($_POST['customer']) ? trim($_POST['customer']) : 'all';
  $status_so = isset($_POST['status_so']) ? trim($_POST['status_so']) : 'all';
 // $status_so = $_POST['status_so'];

if(!empty($tgl_awal) && !empty($tgl_akhir)){
  $where .= " AND so.so_date BETWEEN '".$tgl_awal."' AND '".$tgl_akhir."'";
}

if($customer != 'all'){
  $where .= " AND so.kode_penerima = '".$customer."'";
}

if($status_so != '' && $status_so != 'all'){

    $where .= " AND status_so = '$status_so' ";
}
 
// if($_POST['pic_sales'] != 'all'){
//   $where .= " AND pic_sales = '".$_POST['pic_sales']."'"; 
// }

  $query = $datatable->get_custom("select * from v_sales_status so  $where ",$columns); 

  //buat inisialisasi array data
  $data = array();

  $i=1;
 foreach ($query as $value) {

    // ======================================
    // LABEL STATUS SO
    // ======================================

    switch($value->status_so){

        case 'BELUM PRODUKSI':
            $status_so = "<span class='label label-default'>
                            BELUM PRODUKSI
                          </span>";
        break;

        case 'PRODUKSI BELUM FULL':
            $status_so = "<span class='label label-warning'>
                            PRODUKSI BELUM FULL
                          </span>";
        break;

        case 'PROSES PRODUKSI':
            $status_so = "<span class='label label-primary'>
                            PROSES PRODUKSI
                          </span>";
        break;

        case 'DIKIRIM SEBAGIAN':
            $status_so = "<span class='label label-info'>
                            DIKIRIM SEBAGIAN
                          </span>";
        break;

        case 'SUDAH DIKIRIM':
            $status_so = "<span class='label label-success'>
                            SUDAH DIKIRIM
                          </span>";
        break;

        default:
            $status_so = "<span class='label label-default'>
                            OPEN
                          </span>";
        break;
    }

    // ======================================
    // ARRAY DATA
    // ======================================

    $ResultData = array();

    $ResultData[] = $datatable->number($i);

    $ResultData[] = "
        <a href='".base_url()."index.php/sales-order/detail/$value->id_sales_order'>
            $value->no_sales_order
        </a>
    ";

    $ResultData[] = $value->so_date;
    $ResultData[] = $value->nama;
    $ResultData[] = $value->no_po;
    $ResultData[] = $value->currency;
    $ResultData[] = $value->user;
    $ResultData[] = $value->alasan;

    // 🔥 STATUS LABEL
    $ResultData[] = $status_so;

    $ResultData[] = $value->id_sales_order;

    $data[] = $ResultData;

    $i++;
}
//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>
