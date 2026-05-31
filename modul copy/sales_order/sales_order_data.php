<?php
include "../../inc/config.php";

$columns = array(
    'sales_order.id_quotation',
    'sales_order.so_date',
    'penerima.nama',
    'sales_order.sales_id',
    'sales_order.currency',
    'sales_order.user',
    'sales_order.shipping_address',
    'sales_order.alasan',
    'sales_order.id_sales_order',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('shipping_address','sales_order.id_sales_order');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("sales_order.id_sales_order");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by sales_order.id_sales_order";

  $query = $datatable->get_custom("select alasan, sales_order.status, sales_order.no_sales_order, sales_order.id_quotation,sales_order.so_date,penerima.nama,sales_order.sales_id,sales_order.currency,sales_order.user,sales_order.shipping_address,sales_order.id_sales_order from sales_order inner join penerima on sales_order.kode_penerima=penerima.kode_penerima ",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = "<a href='".base_url()."index.php/sales-order/detail/$value->id_sales_order'>$value->no_sales_order</a>"; 
    $ResultData[] = $value->so_date;
    $ResultData[] = $value->nama;
    $ResultData[] = $value->sales_id;
    $ResultData[] = $value->currency;
    $ResultData[] = $value->user;
    $ResultData[] = $value->alasan;
   // $ResultData[] = $value->status;
    $ResultData[] = $value->id_sales_order;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>