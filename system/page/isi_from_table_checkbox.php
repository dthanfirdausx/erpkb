<?php
include "../../inc/config.php";
$table = $db->query("show table status");
$col = $_GET['col'];
?>
<div class='row' style='margin-left:0;'>
 <div class='col-lg-2' style='padding-left:50px;padding-top:10px'>From</div><div class='col-lg-7' style='padding-left:0;padding-top:3px'>  <select onChange="from_table_checkbox(this.value,'<?=$col;?>')" name="from_checkbox[<?=$col?>]" id="from[<?=$col;?>]" class="form-control">
  <option value="">Select Table</option>
  <?php foreach ($table as $tab) {
    echo "<option value='$tab->Name'>$tab->Name</option>";
  }
  ?>
</select>
 </div> </div>
<div id="isi_display_checkbox_<?=$col;?>"></div>
