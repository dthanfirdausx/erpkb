<?php
include "../../inc/config.php";
?>

   <div class="col-lg-12">
              <h3>Filter Creator</h3>      
      <div class="box">
                  <div id="defaultTable" class="body collapse in">
                    <table class="table responsive-table" id="list_table">
                      <thead>
                        <tr style="background: rgb(60, 141, 188);color:#fff;cursor:auto">
                           <th style="width: 20px">#</th>
                          <th>VARIABLE NAME</th>
                          <th>LABEL</th>
                          <th>#</th>
                        </tr>
                      </thead>
                        <tbody id="list_table_sort" class="list_contain clone-embed">
<tr class="clone-text">
  <td><i class="fa fa-align-justify"></i></td>
                          <td>
<input type="text" class="form-control var_name" name="var_name[]"</td>

                          <td>
<input type="text" class="form-control label_name" name="label_name[]"></td>
<td><span class="btn btn-success add_clone"><i class="fa fa-plus"> Add</i></span> <span class="btn btn-danger remove_clone"><i class="fa fa-trash"></i></span></td>
                        </tr>
   </tbody>
 </table>
                  </div>
                </div>

              </div><!-- /.col-lg-6 -->
 <style type="text/css">
   .ui-high {
    border: 1px dashed #999;
    height: 10px;
    background: #fee;


}
 </style>
<script type="text/javascript">

$(".table").on('click','.add_clone',function(event) {
    var cloned = $(this).parent().parent().clone().insertAfter( $(this).parent().parent());
   // $(this).parent().parent().clone().appendTo('.clone-embed');
    cloned.find('.var_name').val('');
    cloned.find('.label_name').val('');
});
$(".table").on('click','.remove_clone',function(event) {
    var jml_element = $('.clone-text').length;
    if (jml_element>1) {
      $(this).parent().parent().remove();
    }
});

$("#token_type").change(function(){
    if (this.value=='temporary') {
        $("#expire_in").show();
    } else {
        $("#expire_in").hide();
    }
});


 $("#list_table_sort").sortable({
           cancel: ".primary,select,input,checkbox,textarea",
            axis: "y",
           cursorAt: { top: 0, left: 0 },
           cursor: "move",
           helper: fixWidthHelper,
           containment: 'parent',
           tolerance: "pointer",
           placeholder: "ui-high"
        });
   function fixWidthHelper(e, ui) {
    ui.children().each(function() {
        $(this).width($(this).width());
    });
    return ui;
}
    $( "#list_table_sort" ).disableSelection();


</script>
