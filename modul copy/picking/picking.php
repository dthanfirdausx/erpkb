<?php
switch (uri_segment(2)) {
    case "tambah":
          foreach ($db->fetch_all("sys_menu") as $isi) {
               if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah") {
                          if ($role_act["insert_act"]=="Y") {
                             include "picking_add.php";
                          } else {
                            echo "permission denied"; 
                          }
                       }

      }
    break;
  case "edit":
    $data_edit = $db->fetch_single_row("tmp_pemasukan1","no_bpb",uri_segment(3));
        foreach ($db->fetch_all("sys_menu") as $isi) {
                      if (uri_segment(1)==$isi->url&&uri_segment(2)=="edit") {
                          if ($role_act["up_act"]=="Y") {
                             include "picking_edit.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }

    break;
    case "detail":
    $data_edit = $db->fetch_single_row("tmp_pemasukan1","no_bpb",uri_segment(3));
    include "picking_detail.php";
    break;
    default:
    include "picking_view.php";
    break;
}

?>