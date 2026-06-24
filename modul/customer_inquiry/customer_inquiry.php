<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
switch (uri_segment(2)) {
  case "tambah":
    if ($role_act["insert_act"] == "Y") {
      include "customer_inquiry_add.php";
    } else {
      echo "permission denied";
    }
    break;
  case "edit":
    $data_edit = $db->fetch_single_row("sales_inquiry", "id", uri_segment(3));
    if ($role_act["up_act"] == "Y") {
      include "customer_inquiry_edit.php";
    } else {
      echo "permission denied";
    }
    break;
  case "detail":
    $data_edit = $db->fetch_single_row("sales_inquiry", "id", uri_segment(3));
    include "customer_inquiry_detail.php";
    break;
  default:
    include "customer_inquiry_view.php";
    break;
}
?>
