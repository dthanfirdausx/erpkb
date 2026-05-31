<?php
      session_start();
      include "../../inc/config.php";

      $jenis_dokumen = $_POST["jenis_dokumen"];

      $data = $db->query("select * from detail_catatan where jenis_dokpab=?",array("jenis_dokpab" => $jenis_dokumen));
       echo "<option value=''>Pilih </option>";
      foreach ($data as $dt) {
        echo "<option value='$dt->kdd_catatan'>$dt->nd_catatan</option>";
      }
      