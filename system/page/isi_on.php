<?php
include "../../inc/config.php";
$table = $db->query("show columns from ".$_GET['tb']);
$tabs = $db->query("show columns from ".$_GET['tb']);
$col = $_GET['col'];
?>
  Value <select name="on_value[<?=$col;?>]" class="form-control">
  <?php foreach ($tabs as $tab) {
    echo "<option value='".$_GET['tb'].".$tab->Field'>".$_GET['tb'].".$tab->Field</option>";
  }
  ?>

</select>
 Label/Name <select name="on_name[<?=$col?>]" id="on_name[<?=$col;?>]" class="form-control">
  <?php foreach ($table as $tab) {
    echo "<option value='".$_GET['tb'].".$tab->Field'>".$_GET['tb'].".$tab->Field</option>";
  }
  ?>

</select>
