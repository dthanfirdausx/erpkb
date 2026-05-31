<?php
include "../../inc/config.php";

$columns = array(
    'purchase_order.purchase_order_no',
//    'purchase_order.season',
    'purchase_order.po_date',
    'purchase_order.seller_name',
    'purchase_order.seller_address',
   // 'purchase_order.issue_by',
    'purchase_order.payment_term',
     'purchase_order.status_po',
    'purchase_order.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('payment','purchase_order.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1); 

  //set order by column
  $datatable->set_order_by("purchase_order.id");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by purch ase_order.id";

  $where = " where 1=1 ";

if(!empty($_POST['tgl_awal']) && !empty($_POST['tgl_akhir'])){
  $where .= " AND po_date BETWEEN '".$_POST['tgl_awal']."' AND '".$_POST['tgl_akhir']."'";
}

if($_POST['supplier'] != 'all'){
  $where .= " AND supplier = '".$_POST['supplier']."'";
}

if($_POST['status_po'] != 'all'){
  $where .= " AND status_po = '".$_POST['status_po']."'";
}

  $query = $datatable->get_custom("select purchase_order.purchase_order_no as po_no,purchase_order.po_date,purchase_order.seller_address,seller_name,

    purchase_order.payment_term,purchase_order.id,status_po from v_purchase_order purchase_order $where",$columns); 

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->po_no; 
    //$ResultData[] = $value->season;
    $ResultData[] = $value->po_date;
    $ResultData[] = $value->seller_name;
    $ResultData[] = $value->seller_address;
 //  $ResultData[] = $value->issue_by;
    $ResultData[] = $value->payment_term;
    $status = '';

    if ($value->status_po == 'OPEN') {
        $status = '<span class="label label-danger">OPEN</span>';
    } 
    elseif ($value->status_po == 'PARTIAL') {
        $status = '<span class="label label-warning">PARTIAL</span>';
    } 
    elseif ($value->status_po == 'CLOSED') {
        $status = '<span class="label label-success">CLOSED</span>';
    } 
    else {
        $status = '<span class="label label-default">'.$value->status_po.'</span>';
    }

    $ResultData[] = $status;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>