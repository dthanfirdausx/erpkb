<?php
include "../../inc/config.php";


$table = $db->query("show table status");
$col = $_GET['col'];

$rand_id = bin2hex(openssl_random_pseudo_bytes(10));
$rand_dua= bin2hex(openssl_random_pseudo_bytes(10));
$rand_three= bin2hex(openssl_random_pseudo_bytes(10));
?>
<div class="main_parent">
<div class='row' style='margin-left:0;'>
 <div class='col-lg-4' style='padding-top:10px'>Parent Table</div><div class='col-lg-6' style='padding-left:0;padding-top:3px'>  <select onchange="show_primary_chain_parent(this.value,'<?=$col;?>','<?=$rand_id;?>','<?=$rand_dua;?>','<?=$rand_three;?>')" name="parent_table_chain[<?=$col?>][]" class="form-control">
 <option value="">Select Table</option>
  <?php  foreach ($table as $tab) {
                            echo "<option value='$tab->Name'>$tab->Name</option>";
                          }

  ?>

</select>
 </div>
</div>
<div class='row' style='margin-left:0;'>
 <div class='col-lg-2' style='padding-top:10px'>Label</div>
 <div class='col-lg-4' style='padding-left:0;padding-top:3px'>
<input type="text" name="label_chain[<?=$col;?>][]" required placeholder="Label Name" class="form-control">
 </div>
  <div class='col-lg-4' style='padding-left:0;padding-top:3px'>
<select name="label_chain_col[<?=$col?>][]" id="label_chain_col_<?=$rand_dua;?>_<?=$col?>" class="form-control">
</select>
 </div>
</div>

<div class='row' style='margin-left:0;'>
 <div class='col-lg-4' style='padding-top:10px'>Primary Key</div><div class='col-lg-6' style='padding-left:0;padding-top:3px'>  <select name="primary_chain[<?=$col?>][]" id="primary_chain_parent_<?=$rand_id;?>_<?=$col?>" class="form-control">


</select>
 </div>


 </div>

 <div class='row' style='margin-left:0;'>
 <div class='col-lg-4' style='padding-top:10px'>Foreign Key</div><div class='col-lg-6' style='padding-left:0;padding-top:3px'>  <select name="foreign_chain[<?=$col?>][]" id="foreign_chain_parent_<?=$rand_three;?>_<?=$col?>" class="form-control">


</select>
 </div>
<div class='col-lg-1' style='padding-left:0;padding-top:5.5px;width:50px'> <span class='btn btn-block btn-danger btn-xs hapus_row_chain'><i class='fa fa-minus' style='padding: 6px'></i></span> </div>

 </div>


 </div>
