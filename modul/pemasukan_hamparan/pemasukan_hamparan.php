<?php
switch (uri_segment(2)) {
    case "tambah":
          foreach ($db->fetch_all("sys_menu") as $isi) {
               if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah") {
                          if ($role_act["insert_act"]=="Y") {
                             include "pemasukan_hamparan_add.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }
    break;
  case "edit":
    $data_edit = $db->fetch_single_row("pemasukan","id",uri_segment(3));
        foreach ($db->fetch_all("sys_menu") as $isi) {
                      if (uri_segment(1)==$isi->url&&uri_segment(2)=="edit") {
                          if ($role_act["up_act"]=="Y") {
                             include "pemasukan_hamparan_edit.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }

    break;
    case "detail": 
    $data_edit = $db->fetch_single_row("pemasukan","id",uri_segment(3));
    include "pemasukan_hamparan_detail.php";
    break;
    case "upload_tpb": 
   // echo "string";
   // $data_edit = $db->fetch_single_row("pemasukan","id",uri_segment(3));
    include "upload_tpb.php";
    break;
    default:
    include "pemasukan_hamparan_view.php";
    break;
}

?>