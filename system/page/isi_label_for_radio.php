<?php
include "../../inc/config.php";
$table = $db->query("show columns from ".$_GET['tb']);
$tabs = $db->query("show columns from ".$_GET['tb']);
$col = $_GET['col'];
?>
<div class='row' style='margin-left:0;'>
 <div class='col-lg-2' style='padding-top:10px'>Value</div><div class='col-lg-7' style='padding-left:0;padding-top:3px'>  <select name="value_radio_db[<?=$col?>]" class="form-control">
  <?php foreach ($table as $tab) {
    echo "<option value='".$_GET['tb'].".$tab->Field'>".$_GET['tb'].".$tab->Field</option>";
  }

 $table = $db->query("show columns from ".$_GET['tb']);
  ?>

</select>
 </div> </div>

<div class='row' style='margin-left:0;'>
 <div class='col-lg-2' style='padding-top:10px'>Label</div><div class='col-lg-7' style='padding-left:0;padding-top:3px'>  <select name="label_radio_db[<?=$col?>]" class="form-control">
  <?php foreach ($table as $tab) {
    echo "<option value='".$_GET['tb'].".$tab->Field'>".$_GET['tb'].".$tab->Field</option>";
  }
  ?>

</select>
 </div> </div>
