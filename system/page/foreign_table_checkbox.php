<?php
include "../../inc/config.php";
$table = $db->query("show columns from ".$_GET['tb']);
$tabs = $db->query("show columns from ".$_GET['tb']);
$col = $_GET['col'];
?>
  <?php foreach ($table as $tab) {
    echo "<option value='".$_GET['tb'].".$tab->Field'>".$_GET['tb'].".$tab->Field</option>";
  }
  ?>
