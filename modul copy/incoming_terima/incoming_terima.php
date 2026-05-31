<?php
switch (uri_segment(2)) {
    case "tambah":
          foreach ($db->fetch_all("sys_menu") as $isi) {
               if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah") {
                          if ($role_act["insert_act"]=="Y") {
                             include "incoming_terima_add.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }
    break;
  case "edit":
    $data_edit = $db->fetch_single_row("incoming_terima","no_lpb",uri_segment(3));
        foreach ($db->fetch_all("sys_menu") as $isi) {
                      if (uri_segment(1)==$isi->url&&uri_segment(2)=="edit") {
                          if ($role_act["up_act"]=="Y") {
                             include "incoming_terima_edit.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }

    break;
    case "detail":
    error_reporting(0);
    $data_edit = $db->fetch_single_row("transfer","id_transfer",uri_segment(3));
    include "incoming_terima_detail.php";
    break;
    default:
    include "incoming_terima_view.php"; 
    break;
}

?>