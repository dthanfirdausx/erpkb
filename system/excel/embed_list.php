<?php
include "../../inc/config.php";
$column = $db->query("show columns from ".$_GET['tb']);
$table = $_GET['tb'];
$i=1;
?>
<tr class="no_sort embed_<?=$_GET['tb'];?>" style="background: rgb(0, 166, 90);color: #fff;cursor:auto">
                      <th colspan="4">Table Name :  <?=$_GET['tb'];?></th>
                      </tr> 
<?php
foreach ($column as $col) {

	?>
<tr class='embed_<?=$table;?>'>
<td style="width:1px">
  <div class="checkbox checkbox-success" style="padding-top:0">
<input type="checkbox" id="checked1_<?=$_GET['tb'].'_'.$col->Field;?>" name="dipilih1[<?=$_GET['tb'];?>.<?=$col->Field;?>]" onClick="copy_to1('<?=$_GET['tb']?>','<?=$col->Field?>','')">
                     <label>&nbsp;</label>
                    </div>

</td>
                          <td>
<?=$col->Field;?>

</td>
                          <td>

                          <input type="text" class="form-control" id="label1_<?=$_GET['tb'].'_'.$col->Field;?>" name="alias1[<?=$_GET['tb'];?>.<?=$col->Field;?>]">
                       </td>
                       <td>

                          <input type="text" class="form-control" id="label1_<?=$_GET['tb'].'_'.$col->Field;?>" name="label1[<?=$_GET['tb'];?>.<?=$col->Field;?>]">
                       </td>
                        </tr>
<?php $i++;
} ?>
