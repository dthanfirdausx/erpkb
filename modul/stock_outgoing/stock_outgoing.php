<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
// Stock outgoing saat ini adalah report read-only berbasis stock_layer.
// Aksi tambah/edit/detail legacy dikunci agar tidak membaca view/tabel lama.
include "stock_outgoing_view.php";
?>
