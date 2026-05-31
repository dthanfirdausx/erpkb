<?php
include "../../inc/config.php";
$columns = $db->query("show columns from ".$_GET['tb']);
?>
	<option value="main">None(Main Parent)</option>
  <?php foreach ($columns as $tab) {
    echo "<option value='$tab->Field'>".$_GET['tb'].".$tab->Field</option>";
  }
  ?>
