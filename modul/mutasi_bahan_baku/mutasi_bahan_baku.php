<?php
switch (uri_segment(2)) {
    case "tambah":
          foreach ($db->fetch_all("sys_menu") as $isi) {
               if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah") {
                          if ($role_act["insert_act"]=="Y") {
                             include "mutasi_bahan_baku_add.php";
                          } else {
                            echo customs_t("permission_denied", "permission denied");
                          }
                       }

      }
    break;
  case "edit":
    $data_edit = $db->fetch_single_row("mutasi_bahanbaku","id",uri_segment(3));
        foreach ($db->fetch_all("sys_menu") as $isi) {
                      if (uri_segment(1)==$isi->url&&uri_segment(2)=="edit") {
                          if ($role_act["up_act"]=="Y") {
                             include "mutasi_bahan_baku_edit.php";
                          } else {
                            echo customs_t("permission_denied", "permission denied");
                          }
                       }

      }

    break;
    case "detail":
    $data_edit = $db->fetch_single_row("mutasi_bahanbaku","id",uri_segment(3));
    include "mutasi_bahan_baku_detail.php";
    break;
    default:
    $tgl_awal = date('Y-m-01');
    $tgl_akhir = date("Y-m-d");
    include "mutasi_bahan_baku_view.php";
    break;
}

?>