<?php
include "../../inc/config.php";


$table = $db->query("show table status where Name!=?",array('Name'=>$_GET['main_table']));

//$tabs = $db->query("show columns from ".$_GET['tb']);
$col = $_GET['col'];


?>
<div class='row' style='margin-left:0;'>
 <div class='col-lg-4' style='padding-top:10px'>Parent Table <?=$_GET['main_table'];?> </div><div class='col-lg-6' style='padding-left:0;padding-top:3px'>  <select onchange="show_primary_chain(this.value,'<?=$col;?>')" name="parent_table_chain_first[<?=$col?>]" class="form-control">
 <option value="">Select Table</option>
  <?php  foreach ($table as $tab) {
                            echo "<option value='$tab->Name'>$tab->Name</option>";
                          }

  ?>

</select>
 </div>
<div class='col-lg-1' style='padding-left:0;padding-top:5.5px;width:50px'> <span onclick="add_new_row_chain('<?=$col;?>')" class='btn btn-block btn-info btn-xs'><i class='fa fa-plus' style='padding: 6px'></i></span> </div>
  </div>

<div class='row' style='margin-left:0;'>
 <div class='col-lg-4' style='padding-top:10px'>Primary Key</div><div class='col-lg-6' style='padding-left:0;padding-top:3px'>  <select name="primary_chain_first[<?=$col?>]" id="primary_chain_<?=$col?>" class="form-control">


</select>
 </div> </div>
 <div class='row' style='margin-left:0;'>
 <div class='col-lg-4' style='padding-top:10px'>Foreign Key</div><div class='col-lg-6' style='padding-left:0;padding-top:3px'>  <select name="primary_chain_first_foreign[<?=$col?>]" id="primary_chain_foreign_<?=$col?>" class="form-control">


</select>
 </div> </div>

 <div class='row' style='margin-left:0;'>
 <div class='col-lg-4' style='padding-top:10px'>Label Column</div><div class='col-lg-6' style='padding-left:0;padding-top:3px'>  <select name="label_chain_col_first[<?=$col?>]" id="label_chain_col_first_<?=$col?>" class="form-control">


</select>
 </div> </div>
 <div id="parent_data_<?=$col;?>">
</div>
