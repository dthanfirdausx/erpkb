<?php
switch (uri_segment(2)) {
    case "tambah":
          foreach ($db->fetch_all("sys_menu") as $isi) {
               if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah") {
                          if ($role_act["insert_act"]=="Y") {
                             include "mutasi_barang_jadi_add.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }
    break;
  case "edit":
    $data_edit = $db->fetch_single_row("mutasi_barangjadi","id",uri_segment(3));
        foreach ($db->fetch_all("sys_menu") as $isi) {
                      if (uri_segment(1)==$isi->url&&uri_segment(2)=="edit") {
                          if ($role_act["up_act"]=="Y") {
                             include "mutasi_barang_jadi_edit.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }

    break;
    case "detail":
    $data_edit = $db->fetch_single_row("mutasi_barangjadi","id",uri_segment(3));
    include "mutasi_barang_jadi_detail.php";
    break;
    default:
     $tgl_awal = date('Y-m-01');
    $tgl_akhir = date("Y-m-d");
    include "mutasi_barang_jadi_view.php";
    break;
}

?>