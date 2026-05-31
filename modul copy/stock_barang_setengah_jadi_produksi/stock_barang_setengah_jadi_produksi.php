<?php
switch (uri_segment(2)) {
    case "tambah":
          foreach ($db->fetch_all("sys_menu") as $isi) {
               if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah") {
                          if ($role_act["insert_act"]=="Y") {
                             include "stock_barang_setengah_jadi_produksi_add.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }
    break;
  case "edit":
    $data_edit = $db->fetch_single_row("vtotalstockprodbsj","",uri_segment(3));
        foreach ($db->fetch_all("sys_menu") as $isi) {
                      if (uri_segment(1)==$isi->url&&uri_segment(2)=="edit") {
                          if ($role_act["up_act"]=="Y") {
                             include "stock_barang_setengah_jadi_produksi_edit.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }

    break;
    case "detail":
    $data_edit = $db->fetch_single_row("vtotalstockprodbsj","",uri_segment(3));
    include "stock_barang_setengah_jadi_produksi_detail.php";
    break;
    default:
    include "stock_barang_setengah_jadi_produksi_view.php";
    break;
}

?>