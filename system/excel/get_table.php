<?php
include "../../inc/config.php";
$column = $db->query("show columns from ".$_GET['tb']);
?>
<style type="text/css">
tbody tr {
  cursor: move;
}
</style>
   <div class="col-lg-12">
   
   	<h3>POST DATA</h3>
                <div class="box">
                  <div id="defaultTable" class="body collapse in">
                    <table class="table responsive-table" >
                      <thead>

                        <tr style="background: rgb(60, 141, 188);color: #fff;">
                          <th></th>
                          <th>FIELD</th>
                          <th>LABEL</th>
                          <th>CONTOH DATA</th>
                          <th>KOMENTAR</th>
                          <th>BOLEH KOSONG</th>
                        </tr>
                      </thead>
                       <tbody id="input_sort" class="contain">
<?php
foreach ($column as $col) {

  if ($col->Key=='PRI') {
    echo "<input type='hidden' name='primary_key' value='$col->Field'>";
  }
?>
<tr>



<td style="width:1px">
  <div class="checkbox checkbox-success" style="padding-top:0">
                     <input id="checked_<?=$_GET['tb'].'_'.$col->Field;?>" class="styled" type="checkbox" name="dipilih[<?=$col->Field;?>]" onClick="copy_to('<?=$_GET['tb']?>','<?=$col->Field?>')">
                     <label>&nbsp;</label>
                    </div>

</td>

                          <td><?=$col->Field;?></td>
                                      <td>
                       <input type="text" class="form-control" id="label_<?=$_GET['tb'].'_'.$col->Field;?>" name="alias[<?=$col->Field;?>]"></td>
                       <td>
                       <input type="text" class="form-control" id="contoh_<?=$_GET['tb'].'_'.$col->Field;?>" name="contoh[<?=$col->Field;?>]"></td>
                          <td>
                        <textarea type="text" class="form-control" id="label_<?=$_GET['tb'].'_'.$col->Field;?>" name="label[<?=$col->Field;?>]" rows="5"></textarea>
                       </td>
                                                 <td>
                       <input name="allownull[<?=$col->Field;?>]" class="make-switch" data-on-text="Yes" data-off-text="No" type="checkbox" data-on-color="info" data-off-color="danger">
                       </td>

                        </tr>


	<?php
}

?>
    </tbody>
 </table>



                  </div>
                </div>

<div style="margin-bottom:20px" id="isi_remote"></div>


              </div><!-- /.col-lg-6 -->
 <style type="text/css">
   .ui-highlight {
    border: 1px dashed #999;
    height: 0px;
    background: #fee;


}
 </style>
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
   $("#input_sort").sortable({
           cancel: ".primary,select,input,checkbox,.bootstrap-switch,textarea",
            axis: "y",
             connectWith: '.input_sort',
           cursorAt: { top: 0, left: 0 },
           cursor: "move",
           helper: fixWidthHelper,
           tolerance: "pointer",
           placeholder: "ui-highlight"
        });
   function fixWidthHelper(e, ui) {
    ui.children().each(function() {
        $(this).width($(this).width());
    });
    return ui;
}
    $( "#input_sort" ).disableSelection();

</script>
