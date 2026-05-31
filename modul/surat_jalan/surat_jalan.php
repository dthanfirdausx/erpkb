<?php
switch (uri_segment(2)) {
    case "tambah":
        foreach ($db->fetch_all("sys_menu") as $isi) {
            if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah") {
                if ($role_act["insert_act"]=="Y") {
                    include "surat_jalan_add.php";
                } else {
                    echo "permission denied";
                }
            }
        }
        break; 
    case "edit":
        $id = uri_segment(3);

        $data_edit = $db->fetch_single_row("surat_jalan","id",uri_segment(3));
        foreach ($db->fetch_all("sys_menu") as $isi) {
            if (uri_segment(1)==$isi->url&&uri_segment(2)=="edit") {
                if ($role_act["up_act"]=="Y") {
                    include "surat_jalan_edit.php";
                } else {
                    echo "permission denied";
                }
            }
        }
        break;
    case "detail":
        $data_edit = $db->fetch_single_row("surat_jalan","id",uri_segment(3));
        include "surat_jalan_detail.php";
        break;
    case "print":
        $data_edit = $db->fetch_single_row("surat_jalan","id",uri_segment(3));
        include "surat_jalan_print.php";
        break;
    default:
        include "surat_jalan_view.php";
        break;
}
?>