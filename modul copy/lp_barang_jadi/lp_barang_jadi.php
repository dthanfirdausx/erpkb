<?php
switch (uri_segment(2)) {
    case "tambah":
          foreach ($db->fetch_all("sys_menu") as $isi) {
               if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah") {
                          if ($role_act["insert_act"]=="Y") {
                             include "lp_barang_jadi_add.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }
    break; 
    case "tambah_lp_gabungan":
          reset_lp_gabungan($_SESSION['username']);
         // $db->query("delete from temp_lp_gabungan where user='".$_SESSION['username']."' "); 
          foreach ($db->fetch_all("sys_menu") as $isi) {
               if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah_lp_gabungan") {
                          if ($role_act["insert_act"]=="Y") {
                             include "lp_barang_jadi_gabungan_add.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }
    break;
  case "edit":
    $data_edit = $db->fetch_single_row("brgjadi","id_produksi",uri_segment(3));
        foreach ($db->fetch_all("sys_menu") as $isi) {
                      if (uri_segment(1)==$isi->url&&uri_segment(2)=="edit") {
                          if ($role_act["up_act"]=="Y") {
                             include "lp_barang_jadi_edit.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }

    break;
    case "detail":
    $data_edit = $db->fetch_single_row("brgjadi","id_produksi",uri_segment(3));
    include "lp_barang_jadi_detail.php";
    break;
    default:
    include "lp_barang_jadi_view.php";
    break;
}

?>