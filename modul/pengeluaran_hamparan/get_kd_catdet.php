<?php
      session_start();
      include "../../inc/config.php";

      $jenis_dokpab = $_POST["jenis_dokpab"];

      $data = $db->query("select * from detail_catatan where jenis_dokpab=?",array("jenis_dokpab" => $jenis_dokpab));
       echo "<option value=''>Pilih </option>";
      foreach ($data as $dt) {
        echo "<option value='$dt->kdd_catatan'>$dt->nd_catatan</option>";
      }
      