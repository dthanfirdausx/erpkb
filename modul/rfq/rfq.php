<?php
switch (uri_segment(2)) {
  case "tambah":
    if (isset($role_act["insert_act"]) && $role_act["insert_act"]=="Y") {
      include "rfq_add.php";
    } else {
      echo lang_text('rfq_permission_denied', 'permission denied');
    }
    break;
  default:
    include "rfq_view.php";
    break;
}
?>
