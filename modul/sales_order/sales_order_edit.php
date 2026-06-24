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
$soMode = 'edit';
$soRecord = $data_edit;
$soItems = $db->query("
  SELECT sod.*, b.nm_barang, b.satuan
  FROM sales_order_detail sod
  LEFT JOIN barang b ON b.kd_barang=sod.kd_barang
  WHERE sod.id_sales_order=?
  ORDER BY COALESCE(sod.line_no, sod.id_detail), sod.id_detail
", array($data_edit->id_sales_order));
include __DIR__ . '/sales_order_form.php';
?>
