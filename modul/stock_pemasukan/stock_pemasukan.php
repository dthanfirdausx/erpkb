<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
// Stock overview adalah report read-only berbasis v_stock_transaksi/stock_layer.
// Detail layer dibuka melalui modal di view agar tidak membaca tabel summary lama.
include "stock_pemasukan_view.php";
?>
