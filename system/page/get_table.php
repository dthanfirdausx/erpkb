<?php
include "../../inc/config.php";
$column = $db->query("show columns from ".$_GET['tb']);
?>


   <div class="col-lg-12">
   	<h4><?=$lang['check_page_info'];?></h4>
   	<h3>Form Page</h3>
                <div class="box">
                  <div id="defaultTable" class="body collapse in">
                    <table class="table responsive-table" >
                      <thead>

                        <tr style="background: rgb(60, 141, 188);color: #fff;">
                          <th></th>
                          <th>FIELD</th>
                          <th>LABLE</th>
                          <th>TYPE</th>
                          <th>REQUIRED</th>
                        </tr>
                      </thead>
                       <tbody id="input_sort">
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
                       <input type="text" class="form-control" id="label_<?=$_GET['tb'].'_'.$col->Field;?>" name="label[<?=$col->Field;?>]"></td>
                          <td>
                          	<select id="tipe_<?=$col->Field;?>" onChange="tipe(this.value,'<?=$col->Field;?>')" name="type[<?=$col->Field;?>]" class="form-control">
                          		<option value="text">Text</option>
                              <option value="number">Number</option>
                          		<option value="date">Date</option>
                              <option value="email">Email</option>
                              <option value="time">Time</option>
                              <option value="textarea_only">Textarea</option>
                          		<option value="textarea">Textarea With Editor</option>
                              <option value="boolean">Boolean</option>
                              <option value="checkbox">Checkbox</option>
                              <option value="radio">Radio</option>
                              <option value="select_custom">Select Custom Value</option>
                          		<option value="select">Select From Database</option>
                              <option value="select_chaining">Select Chainining</option>
                          		<option value="ufile">Upload File</option>
                          		<option value="uimager">Upload Image Resize</option>
                          		<option value="uimagef">Upload Image Full</option>
                          	 </select>
                              <div id="required_content_<?=$col->Field;?>">

                             </div>
                          	 <div id="type_content_<?=$col->Field;?>">

                          	 </div>

                          </td>
                          <td style="width:1px">
  <div id="requiredcheck_<?=$_GET['tb'].'_'.$col->Field;?>" class="checkbox checkbox-success" style="padding-top:0;display:none">
                    <input type="checkbox"  name="required[<?=$col->Field;?>]" id="required_<?=$_GET['tb'].'_'.$col->Field;?>" onClick="required_checked('<?=$_GET['tb']?>','<?=$col->Field?>')">
                     <label>&nbsp;</label>
                    </div>

</td>
                        </tr>


	<?php
}

?>
    </tbody>
 </table>


                  </div>
                </div>
   <span class='btn btn-primary btn-flat' onClick="add_multi_image()">Add Multiple Image Upload</span> <span onclick="hapus_multi()" class="btn btn-danger btn-flat" ><i class='fa fa-trash-o'></i></span>
<div style="margin-bottom:20px" id="isi_remote"></div>


              </div><!-- /.col-lg-6 -->
<script type="text/javascript">
   $("#input_sort").sortable({
           cancel: ".primary,select,input,checkbox",
           revert: 100,
           placeholder: "dashed-placeholder"
        });

</script>
