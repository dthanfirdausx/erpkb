<?php
switch (uri_segment(2)) {
    case "tambah":
          foreach ($db->fetch_all("sys_menu") as $isi) {
               if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah") {
                          if ($role_act["insert_act"]=="Y") {
                             include "sales_order_add.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }
    break;
  case "edit":
    $data_edit = $db->fetch_single_row("sales_order","id_sales_order",uri_segment(3));
    $pajak = array();
    if ($data_edit!='') {
       $pajak = json_decode($data_edit->tax_item);
    }
        foreach ($db->fetch_all("sys_menu") as $isi) {
                      if (uri_segment(1)==$isi->url&&uri_segment(2)=="edit") {
                          if ($role_act["up_act"]=="Y") {
                             include "sales_order_edit.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }

    break;
    case "detail":
    $data_edit = $db->fetch_single_row("sales_order","id_sales_order",uri_segment(3));
      $pajak = array();
    if ($data_edit!='') {
       $pajak = json_decode($data_edit->tax_item);
    }
    include "sales_order_detail.php";
    break;
    default:
    include "sales_order_view.php";
    break;
}

?>