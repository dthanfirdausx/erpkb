<?php
if (!function_exists('prod_t')) {
  function prod_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('prod_h')) {
  function prod_h($key, $fallback = '') { return htmlspecialchars((string) prod_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('prod_js')) {
  function prod_js($key, $fallback = '') { return json_encode(prod_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
?>
<!-- Content Header (Page header) -->
              <section class="content-header">
                  <h1><?=prod_h('production_mrp', 'MRP');?></h1>
                    <ol class="breadcrumb">
                        <li>
                        <a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=prod_h('common_home', 'Home');?></a>
                        </li>
                        <li>
                        <a href="<?=base_index();?>mrp"><?=prod_h('production_mrp', 'MRP');?></a>
                        </li>
                        <li class="active">Edit MRP</li>
                    </ol>
              </section>

              <!-- Main content -->
              <section class="content">
              <div class="row">
                  <div class="col-lg-12">
                      <div class="box box-solid box-primary">
                          <div class="box-header">
                              <h3 class="box-title">Edit MRP</h3>
                              <div class="box-tools pull-right">
                                  <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-pencil"></i></button>
                              </div>
                          </div>
                      <div class="box-body">
                       <div class="alert alert-danger error_data" style="display:none">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <span class="isi_warning"></span>
                      </div>
                          <form id="edit_mrp" method="post" class="form-horizontal" action="<?=base_admin();?>modul/mrp/mrp_action.php?act=up">
                            
              <div class="form-group">
                <label for="Order" class="control-label col-lg-2">Order <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="no_order" id="order" value="<?=$data_edit->no_order;?>" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Style" class="control-label col-lg-2">Style <span style="color:#FF0000">*</span></label>
                <div class="col-lg-10">
                  <input type="text" name="style" value="<?=$data_edit->style;?>" class="form-control" required>
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Qty" class="control-label col-lg-2"><?=prod_h('production_qty', 'Qty');?> </label>
                <div class="col-lg-10">
                  <input type="text" name="order_qty" value="<?=$data_edit->order_qty;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="term" class="control-label col-lg-2">term </label>
                <div class="col-lg-10">
                  <input type="text" name="term" value="<?=$data_edit->term;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
              <label for="Delivery" class="control-label col-lg-2">Delivery </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl1">
                    <input type="text" class="form-control" value="<?=$data_edit->delivery;?>" name="delivery"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
              <label for="Receipt" class="control-label col-lg-2">Receipt </label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl2">
                    <input type="text" class="form-control" value="<?=$data_edit->receipt;?>" name="receipt"  />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          
              <div class="form-group">
                <label for="PO" class="control-label col-lg-2">PO </label>
                <div class="col-lg-10">
                  <input type="text" name="po" value="<?=$data_edit->po;?>" class="form-control" >
                </div>
              </div><!-- /.form-group -->
              <div class="form-group">
                        <label for="Buyer" class="control-label col-lg-2">Buyer <span style="color:#FF0000">*</span></label>
                        <div class="col-lg-10">
              <select  id="buyer" name="buyer" data-placeholder="Pilih Buyer..." class="form-control chzn-select" tabindex="2" required>
               <option value=""></option>
               <?php foreach ($db->fetch_all("penerima") as $isi) {

                  if ($data_edit->buyer==$isi->kode_penerima) {
                    echo "<option value='$isi->kode_penerima' selected>$isi->nama</option>";
                  } else {
                  echo "<option value='$isi->kode_penerima'>$isi->nama</option>";
                    }
               } ?>
              </select>
          </div>
                      </div>
                      <div class="form-group">
              <label for="Receipt" class="control-label col-lg-2">Detail MRP </label>
              <div class="col-lg-3">
                <div class="input-group date">
                    <input type="file" name="file" id="file" onchange="upload_file(this.value)">
                     <a href="<?= base_url() ?>upload/template/template_mrp.xls"><i class="fa fa-download"></i>Download Template</a>
                </div>
              </div>
          </div>   
           <div class="form-group" id="detail_mrp">
             
              <div class="col-lg-12" >
               <table class="table">
                  <thead>
                    <tr>
                      <th><?=prod_h('common_no', 'No');?></th>
                      <th>Order</th>
                      <th>Kode</th>
                      <th>Color</th>
                      <th>Width</th>
                      <th>qty</th>
                      <th>supplier</th>
                      <th>tipe</th>
                      <th>Po</th>
                      <th>price</th>
                      <th>amount</th>
                      <th>currency</th>
                      <th>rate</th>
                      <th>price usd</th>
                      <th>amount usd</th>
                      <th>Ket</th>
                    </tr>
                  </thead>
                  <tbody id="isi_mrp">
                    <?php
                    $q = $db->query("select mm.* from mrp m join mrpmaterial mm on mm.no_order=m.no_order where m.id='".uri_segment(3)."' ");
                     $i=1;
                      foreach ($q as $data) {
   echo "<tr>
                            <td>$i</td>
                            <td>".$data->no_order."</td>
                            <td>".$data->kode."</td>
                            <td>".$data->color."</td>
                            <td>".$data->width."</td>
                            <td>".$data->qty_gross."</td>
                            <td>".$data->supplier."</td>
                            <td>".$data->tipe."</td>
                            <td>".$data->po."</t
                            <td>".$data->price."</td>
                            <td>".$data->amount."</td>
                            <td>".$data->currency."</td>
                            <td>".$data->rate."</td>
                            <td>".$data->price_usd."</td>
                            <td>".$data->amount_usd."</td>
                            <td>".$data->ket."</td>
                          </tr>";
                          $i++;
  }
                    ?>
                  </tbody>
               </table>
              </div>
          </div> 

                            <input type="hidden" name="id" value="<?=$data_edit->Id;?>">
                            <div class="form-group">
                                <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                                <div class="col-lg-10">
                                <a href="<?=base_index();?>mrp" class="btn btn-default "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>
                                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $lang["submit_button"];?></button>
                                </div>
                            </div><!-- /.form-group -->
                          </form>
                      </div>
                  </div>
              </div>
              </section><!-- /.content -->

<script type="text/javascript">

  function upload_file(file){
       //var form = $('#input_mrp')[0];    
       if ($("#order").val()=='') {
          $("#error_order").show();
          $("#file").val(null);
          $("#order").focus();
       }else{
           $("#isi_mrp").html("Uploading Data...");
           $("#error_order").hide();
           var data = new FormData();
           data.append( 'file', $( '#file' )[0].files[0] );
           data.append( 'order', $( '#order' ).val() );
           $.ajax({
             url : "<?= base_url() ?>modul/mrp/mrp_action.php?act=upload_file",
             type : "POST",
             processData: false,
             contentType: false,
             cache: false,
             enctype: 'multipart/form-data',
             data : data,
             dataType : 'JSON',
             success : function(data){
                 $("#detail_mrp").show();
                 $("#isi_mrp").html(data.tabel);
             }
           });
       }       
    }


    $(document).ready(function() {
    
    $("#tgl1").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl1 :input").valid();
    });
    $("#tgl2").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl2 :input").valid();
    });
    $("#tgl2").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl2 :input").valid();
    });
    $("#tgl2").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl2 :input").valid();
    });
    
      //trigger validation onchange
      $('select').on('change', function() {
          $(this).valid();
      });
      //hidden validate because we use chosen select
      $.validator.setDefaults({ ignore: ":hidden:not(select)" });
      
    $("#edit_mrp").validate({
        errorClass: "help-block",
        errorElement: "span",
        highlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-success").addClass("has-error");
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass(
                "has-error").addClass("has-success");
        },
        errorPlacement: function(error, element) {
            if (element.hasClass("chzn-select")) {
                var id = element.attr("id");
                error.insertAfter("#" + id + "_chosen");
            } else if (element.attr("type") == "checkbox") {
                element.parent().parent().append(error);
            } else if (element.attr("type") == "radio") {
                element.parent().parent().append(error);
            } else {
                error.insertAfter(element);
            }
        },
        
        rules: {
            
          order: {
          required: true,
          //minlength: 2
          },
        
          style: {
          required: true,
          //minlength: 2
          },
        
          buyer: {
          required: true,
          //minlength: 2
          },
        
        },
         messages: {
            
          order: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          style: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
          buyer: {
          required: "This field is required",
          //minlength: "Your username must consist of at least 2 characters"
          },
        
        },
    

        submitHandler: function(form) {
            $("#loadnya").show();
            $(form).ajaxSubmit({
                url : $(this).attr("action"),
                dataType: "json",
                type : "post",
                error: function(data ) { 
                  $("#loadnya").hide();
                  console.log(data); 
                },
                success: function(responseText) {
                  $("#loadnya").hide();
                  console.log(responseText);
                      $.each(responseText, function(index) {
                          console.log(responseText[index].status);
                          if (responseText[index].status=="die") {
                            $("#informasi").modal("show");
                          } else if(responseText[index].status=="error") {
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          } else if(responseText[index].status=="good") {
                            $(".error_data").hide();
                            $(".notif_top_up").fadeIn(1000);
                            $(".notif_top_up").fadeOut(1000, function() {
                                    window.history.back();
                            });
                          } else {
                             console.log(responseText);
                             $(".isi_warning").text(responseText[index].error_message);
                             $(".error_data").focus()
                             $(".error_data").fadeIn();
                          }
                    });
                }

            });
        }
    });
});
</script>
