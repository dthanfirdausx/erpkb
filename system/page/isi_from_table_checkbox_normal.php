<?php
include "../../inc/config.php";
$table = $db->query("show table status");
$col = $_GET['col'];
?>
<div class='row' style='margin-left:0;'>
 <div class='col-lg-4' style='padding-left:50px;padding-top:10px'>From</div><div class='col-lg-7' style='    margin-left: -30px;;padding-top:3px'>  <select onChange="from_table_checkbox_normal(this.value,'<?=$col;?>')" name="from_checkbox_normal[<?=$col?>]" id="from[<?=$col;?>]" class="form-control">
  <option value="">Select Table</option>
  <?php foreach ($table as $tab) {
    echo "<option value='$tab->Name'>$tab->Name</option>";
  }
  ?>
</select>
 </div> </div>
<div id="isi_display_checkbox_normal_<?=$col;?>"></div>
