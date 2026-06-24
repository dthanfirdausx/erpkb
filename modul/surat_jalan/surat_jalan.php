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