<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
switch (uri_segment(2)) {
    case "tambah":
          foreach ($db->fetch_all("sys_menu") as $isi) {
               if (uri_segment(1)==$isi->url&&uri_segment(2)=="tambah") {
                          if ($role_act["insert_act"]=="Y") {
                             include "gr_blocked_stock_add.php";
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
                             include "gr_blocked_stock_edit.php";
                          } else {
                            echo "permission denied";
                          }
                       }

      }

    break;
    case "detail": 
    $data_edit = $db->fetch_single_row("pemasukan","id",uri_segment(3));
    include "gr_blocked_stock_detail.php";
    break;
    case "upload_tpb": 
   // echo "string";
   // $data_edit = $db->fetch_single_row("pemasukan","id",uri_segment(3));
    include "upload_tpb.php";
    break;
    default:
    include "gr_blocked_stock_view.php";
    break;
}

?>