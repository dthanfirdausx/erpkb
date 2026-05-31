<?php
include "../../inc/config.php";
?>

   <div class="col-lg-12">
    <h3>List Table Page</h3>
               </div>
                 <div id="isi_join"></div>
  <span class='btn btn-primary btn-flat' onClick="add_join()" style="margin-bottom: 10px;"><i class='fa  fa-plus-square'></i> Add Join</span>
                <div class="box">
                  <div id="defaultTable" class="body collapse in">
                    <table class="table responsive-table" id="list_table">
                      <thead>
                        <tr style="background: rgb(60, 141, 188);color:#fff;cursor:auto">
                          <th>&nbsp;</th>
                          <th>FIELD</th>
                          <th>LABLE</th>
                        </tr>
                        <tr style="background: rgb(0, 166, 90);color: #fff;cursor:auto">
                      <th colspan="3">Table Name :  <?=$_GET['tb'];?></th>
                      </tr>
                      </thead>
                        <tbody id="list_table_sort">
<?php
$column = $db->query("show columns from ".$_GET['tb']);
foreach ($column as $col) {

	?>
<tr>
<td style="width:1px">
  <div class="checkbox checkbox-success" style="padding-top:0">
<?php
if ($col->Key=='PRI') {
?>
 <input type="checkbox" id="checked1_<?=$_GET['tb'].'_'.$col->Field;?>" name="dipilih1[<?=$_GET['tb'];?>.<?=$col->Field;?>]" onClick="copy_to1('<?=$_GET['tb']?>','<?=$col->Field?>','pr')" class="flat-red">
 <?php
} else {
?>
 <input type="checkbox" id="checked1_<?=$_GET['tb'].'_'.$col->Field;?>" name="dipilih1[<?=$_GET['tb'];?>.<?=$col->Field;?>]" onClick="copy_to1('<?=$_GET['tb']?>','<?=$col->Field?>','')" class="flat-red">
 <?php
}
?>

                     <label>&nbsp;</label>
                    </div>

</td>
<td>
<?=$col->Field;?>

</td>
                          <td>
<input type="text" class="form-control" id="label1_<?=$_GET['tb'].'_'.$col->Field;?>" name="label1[<?=$_GET['tb'];?>.<?=$col->Field;?>]"></td>
                        </tr>




	<?php
}

?>
   </tbody>
 </table>
                  </div>
                </div>
                <div id="embed"></div>
 <div class="form-group">
                 <label class="control-label col-lg-2" style="text-align: left">Order By</label>
                 <div class="col-lg-4">
                   <select name="order_by" id="order_by" class="form-control" tabindex="2">
                   <?php
                   $column = $db->query("show columns from ".$_GET['tb']);
                   foreach ($column as $col) {
                    if ($col->Key=='PRI') {
                     ?>
                      <option value="<?=$_GET['tb'];?>.<?=$col->Field;?>"><?=$_GET['tb'];?>.<?=$col->Field;?></option>
                      <?php
                   }
                 }
                   ?>
                   </select>
                   </div>

                    <div class="col-lg-2" style="margin-left: -20px;">
                   <select class="form-control" name="order_by_type">
                    <option value="desc">DESC</option>
                   <option value="asc">ASC</option>


                   </select>
                 </div>
               </div>
<div class="form-group">
                 <label class="control-label col-lg-2" style="text-align: left">Create No Column</label>
                 <div class="col-lg-4">
                          <input name="create_number" class="make-switch" data-on-text="Yes" data-off-text="No" type="checkbox" data-on-color="info" data-off-color="danger" checked="">
                    </div>
                   </div>
              </div><!-- /.col-lg-6 -->
<script type="text/javascript">
                      $.each($('.make-switch'), function () {
        $(this).bootstrapSwitch({
            onText: $(this).data('onText'),
            offText: $(this).data('offText'),
            onColor: $(this).data('onColor'),
            offColor: $(this).data('offColor'),
            size: $(this).data('size'),
            labelText: $(this).data('labelText')
        });
    });
   $("#list_table_sort").sortable({
           cancel: ".primary,input,select,.no_sort",
            revert: 100,
           placeholder: "dashed-placeholder"
        });

</script>
