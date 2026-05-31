<?php
include "../../inc/config.php";
$table = $db->query("show columns from ".$_GET['tb']);
$tabs = $db->query("show columns from ".$_GET['tb']);
$col = $_GET['col'];
?>
<div class='row' style='margin-left:0;'>
 <div class='col-lg-4' style='padding-left:50px;padding-top:10px'>Value Field</div><div class='col-lg-7' style='margin-left:-30px;padding-top:3px'>  <select name="from_checkbox_normal_primary[<?=$col?>]" id="from_checkbox_normal_primary[<?=$col->Field;?>]" class="form-control">
  <?php foreach ($table as $tab) {
    echo "<option value='".$_GET['tb'].".$tab->Field'>".$_GET['tb'].".$tab->Field</option>";
  }

 $table = $db->query("show columns from ".$_GET['tb']);
  ?>

</select>
 </div> </div>
 <div class='row' style='margin-left:0;'>
 <div class='col-lg-4' style='padding-left:50px;padding-top:10px'>Item / Lable</div><div class='col-lg-7' style='margin-left:-30px;padding-top:3px'>  <select name="from_checkbox_normal_item[<?=$col?>]" id="from_checkbox_normal_item[<?=$col->Field;?>]" class="form-control">
  <?php foreach ($table as $tab) {
    echo "<option value='".$_GET['tb'].".$tab->Field'>".$_GET['tb'].".$tab->Field</option>";
  }
  ?>

</select>
 </div> </div>

 <?php
 $table = $db->query("show table status");
 ?>
 <div class='row' style='margin-left:0;'>
 <div class='col-lg-4' style='padding-left:50px;padding-top:10px'>Foreign Table</div><div class='col-lg-7' style='    margin-left: -30px;;padding-top:3px'>  <select onChange="foreign_table_checkbox(this.value,'<?=$col;?>')" name="foreign_table_checkbox[<?=$col?>]" id="foreign_table_checkbox_<?=$col;?>" class="form-control">
  <option value="">Select Table</option>
  <?php foreach ($table as $tab) {
    echo "<option value='$tab->Name'>$tab->Name</option>";
  }
  ?>
</select>
 </div> </div>

 <div class='row' style='margin-left:0;'>
 <div class='col-lg-4' style='padding-left:50px;padding-top:10px'>Foreign Key <?=$_GET['tb'];?></div><div class='col-lg-7' style='margin-left:-30px;padding-top:3px'>  <select name="foreign_key_from[<?=$col?>]" id="foreign_key_from_<?=$col;?>" class="form-control">

</select>
 </div> </div>
 <div class='row' style='margin-left:0;'>
 <div class='col-lg-4' style='padding-left:50px;padding-top:10px'>Foreign Key <?=$_GET['maintb'];?></div><div class='col-lg-7' style='margin-left:-30px;padding-top:3px'>  <select name="foreign_key_main_checkbox[<?=$col?>]" id="foreign_key_main_checkbox_<?=$col;?>" class="form-control">

</select>
 </div> </div>
