<?php
switch (uri_segment(2)) {
  case "tambah":
    foreach ($db->fetch_all("sys_menu") as $isi) {
      if (uri_segment(1)==$isi->url && uri_segment(2)=="tambah") {
        if ($role_act["insert_act"]=="Y") {
          include "pr_add.php";
        } else {
          echo "permission denied";
        }
      }
    }
    break;
  case "detail":
    $data_edit = $db->fetch_single_row("purchase_requisition","id_pr",uri_segment(3));
    include "pr_detail.php";
    break;
  case "edit":
    $data_edit = $db->fetch_single_row("purchase_requisition","id_pr",uri_segment(3));
    foreach ($db->fetch_all("sys_menu") as $isi) {
      if (uri_segment(1)==$isi->url && uri_segment(2)=="edit") {
        if ($role_act["up_act"]=="Y") {
          include "pr_edit.php";
        } else {
          echo "permission denied";
        }
      }
    }
    break;
  default:
    include "pr_view.php";
    break;
}
?>
