<?php
include "../../inc/config.php";
$table = $db->query("show columns from ".$_GET['tb']);
$tabs = $db->query("show columns from ".$_GET['tb']);
$col = $_GET['col'];
?>
<div class='row' style='margin-left:0;'>
 <div class='col-lg-2' style='padding-left:50px;padding-top:10px'>Item</div><div class='col-lg-7' style='padding-left:0;padding-top:3px'>  <select name="on_name_checkbox[<?=$col?>]" id="on_name_checkbox[<?=$col->Field;?>]" class="form-control">
  <?php foreach ($table as $tab) {
    echo "<option value='".$_GET['tb'].".$tab->Field'>".$_GET['tb'].".$tab->Field</option>";
  }
  ?>

</select>
 </div> </div>
