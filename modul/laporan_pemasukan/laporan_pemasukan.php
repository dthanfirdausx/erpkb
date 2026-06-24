<?php
switch (uri_segment(2)) {
    case "tambah":
          foreach ($db->fetch_all("sys_menu") as $isi) {
               if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah") {
                          if ($role_act["insert_act"]=="Y") {
                             include "laporan_pemasukan_add.php";
                          } else {
                            echo customs_t("permission_denied", "permission denied");
                          }
                       }

      }
    break;
  case "edit":
    $data_edit = $db->fetch_single_row("pemasukan","no_bpb",uri_segment(3));
        foreach ($db->fetch_all("sys_menu") as $isi) {
                      if (uri_segment(1)==$isi->url&&uri_segment(2)=="edit") {
                          if ($role_act["up_act"]=="Y") {
                             include "laporan_pemasukan_edit.php";
                          } else {
                            echo customs_t("permission_denied", "permission denied");
                          }
                       }

      }

    break;
    case "detail":
    $data_edit = $db->fetch_single_row("pemasukan","no_bpb",uri_segment(3));
    include "laporan_pemasukan_detail.php";
    break;
    default:
    include "laporan_pemasukan_view.php";
    break;
}

?>