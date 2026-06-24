<?php
      
if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
session_start();
      include "../../inc/config.php";

      $jenis_dokumen = $_POST["jenis_dokumen"];

      $data = $db->query("select * from detail_catatan where jenis_dokpab=?",array("jenis_dokpab" => $jenis_dokumen));
       echo "<option value=''>Pilih </option>";
      foreach ($data as $dt) {
        echo "<option value='$dt->kdd_catatan'>$dt->nd_catatan</option>";
      }
      