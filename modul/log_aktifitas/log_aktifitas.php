<?php
switch (uri_segment(2)) {
    case "tambah":
          foreach ($db->fetch_all("sys_menu") as $isi) {
               if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah") {
                          if ($role_act["insert_act"]=="Y") {
                             include "log_aktifitas_add.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }
    break;
  case "edit":
    $data_edit = $db->fetch_single_row("log_aktifitas","id",uri_segment(3));
        foreach ($db->fetch_all("sys_menu") as $isi) {
                      if (uri_segment(1)==$isi->url&&uri_segment(2)=="edit") {
                          if ($role_act["up_act"]=="Y") {
                             include "log_aktifitas_edit.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }

    break;
    case "detail":
    $data_edit = $db->fetch_single_row("log_aktifitas","id",uri_segment(3));
    include "log_aktifitas_detail.php";
    break;
    default:
    include "log_aktifitas_view.php";
    break;
}

?>