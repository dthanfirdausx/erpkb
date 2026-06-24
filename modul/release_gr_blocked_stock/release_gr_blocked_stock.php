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
      if (uri_segment(1)==$isi->url && uri_segment(2)=="tambah") {
        if ($role_act["insert_act"]=="Y") {
          include "release_gr_blocked_stock_add.php";
        } else {
          echo "permission denied";
        }
      }
    }
    break;
  default:
    include "release_gr_blocked_stock_view.php";
    break;
}
?>
