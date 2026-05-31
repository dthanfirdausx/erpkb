                <!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                       Add Page
                    </h1>
                    <ol class="breadcrumb">
                        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>modul">Page</a></li>
                        <li class="active">Add Page</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
<div class="row">
    <div class="col-lg-12">
        <div class="box box-primary">
                                <div class="box-header">
                                </div><!-- /.box-header -->

                  <div class="box-body">
                        <form class="form-horizontal" id="inputss" action="<?=base_admin();?>system/service/input_page.php" method="post">
                      <div class="form-group">
                        <label for="text1" class="control-label col-lg-2">Name</label>
                        <div class="col-lg-10">
                          <input type="text" autofocus="" name="page_name" required placeholder="Page name" class="form-control">
                        </div>
                      </div><!-- /.form-group -->

                      <div class="another_choice">

                         <div class="form-group">
                        <label class="control-label col-lg-2"><?=$lang['main_table'];?></label>
                        <div class="col-lg-10">
                          <select id="main_table" data-placeholder="Pilih Table" onChange="fetch_table(this.value)" name="table" class="form-control chzn-select" tabindex="2">
                            <option value="">Select Table</option>
                            <?php foreach ($db->query('show table status') as $tb) {
                              echo "<option value='$tb->Name'>$tb->Name</option>";
                            }
                            ?>
                          </select>
                        </div>
                      </div>


                       <div id="isi_table"></div>
                       <div id="isi_view"> </div>
                       </div>

                      <div class="form-group">
                        <label for="tags" class="control-label col-lg-2">&nbsp;</label>
                        <div class="col-lg-10">
                          <input type="submit" class="btn btn-primary btn-flat" value="Submit">
                        </div>
                      </div><!-- /.form-group -->
                    </form>
                  </div>
                  </div>
              </div>
</div>

                </section><!-- /.content -->


 <script type="text/javascript">
//type menu

  function type_of_menu(val)
  {
   if(val=='page')  {
         $("#type_menu").show();
        //$(".another_choice").show();
      }else  {
        $("#isi_table").html('');
        $("#isi_view").html('');
        $("#type_menu").hide();
         $("#main_table").val('').trigger("chosen:updated");
         $(".another_choice").hide();
         $("#crud_style").hide();
         $("#method_table").val('');
        //$(".another_choice").show();
      }
  }
  //list type
  function list_type(val)
  {

      if (val=='gallery') {
        $(".another_choice").hide();
        $("#crud_style").hide();
        $("#list_type").show();

      } else {
         $("#list_type").hide();
        $(".another_choice").show();
        $("#crud_style").show();
      }
  }
  function fetch_table(val)
  {
    if (val!='') {
         $.ajax({
      url: "<?=base_admin();?>system/service/get_table.php?tb="+val,
      success:function(data){
      //  alert(data);
        $("#isi_table").html(data);
      }
      });
      $.ajax({
        url: "<?=base_admin();?>system/service/for_view.php?tb="+val,
        success:function(data){
        //  alert(data);
          $("#isi_view").html(data);
        }
      });
    } else {
      $("#isi_table").html('');
      $("#isi_view").html('');

    }

  }

  //copy lable on check
  function copy_to(table,col)
  {
    if(document.getElementById("checked_"+table+"_"+col).checked) {
         $("#label_"+table+"_"+col).val(col);
         $("#requiredcheck_"+table+"_"+col).show();
    }
    else {
        $("#label_"+table+"_"+col).val('');
        $("#requiredcheck_"+table+"_"+col).hide();
    }


  }
  //requirec checked
    function required_checked(table,col)
  {
    var selected_type = $("#tipe_"+col+" option:selected").val();
    if(document.getElementById("required_"+table+"_"+col).checked) {
        $("#required_content_"+col).html("<div class='form-group'><div class='row' style='margin-left:0;'><div class='col-lg-3' style='padding-top:10px'>Error text</div><div class='col-lg-8' style='padding-left:0;padding-top:3px'> <input type='text' value='This field is required' required name='error_text["+col+"][text]' class='form-control'><input type='hidden' name='error_text["+col+"][type]' value='"+selected_type+"'> </div> </div></div>");
    }
    else {
        $("#required_content_"+col).html("");
    }


  }




       //copy lable on check list table,val is primary or not
  function copy_to1(table,col,val)
  {
    if(document.getElementById("checked1_"+table+"_"+col).checked) {
        $("#label1_"+table+"_"+col).val(col);
        if ($("#order_by option[value='"+table+"."+col+"']").length == 0) {
          $('#order_by').append($(document.createElement("option")).attr("value",table+"."+col).text(table+"."+col));
          $("#order_by option[value='"+table+"."+col+"']").addClass('select_'+table);
        }

    }
    else {
        $("#label1_"+table+"_"+col).val('');
        //if it's not main table primary then remove option
        if (val=='') {
          $("#order_by option[value='"+table+"."+col+"']").remove();
        }


    }

  }

function id_generator() {
    var S4 = function() {
       return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
    };
    return (S4()+S4());
}


  //onchange type
  function tipe(val,col)
  {

    if (val=='select') {
        $.ajax({
      url: "<?=base_admin();?>system/page/isi_from_table.php?col="+col,
      success:function(data){
        $("#type_content_"+col).html(data);
        }
      });

    }
    else if (val=='select_chaining') {
       var maintb = $('#main_table').find(":selected").text();
               $.ajax({
              url: "<?=base_admin();?>system/page/isi_chaining.php?main_table="+maintb+"&col="+col,
              success:function(data){
                $("#type_content_"+col).html(data);
                }
              });
    }

    else if(val=='select_custom') {
      $("#type_content_"+col).html("<div class='form-group'> <div class='row' style='margin-left: 0;'> <div class='col-lg-4' style='padding-top:2px'> <input type='text' id='required' name='select_custom_value["+col+"][]' placeholder='Value' class='form-control'> </div> <div class='col-lg-4' style='padding-left:0;padding-top:2px'> <input type='text' id='required' name='select_custom_display["+col+"][]' placeholder='Display Name' class='form-control'> </div> <div class='col-lg-1' style='padding-left:0;padding-top:6.5px'> <span onclick=\"add_new_row('"+col+"')\" class='btn btn-block btn-info btn-xs'><i class='fa fa-plus' style='padding: 4px'></i></span> </div> </div> <span id='add_next_row_"+col+"'></span> </div>");

    }

    else if(val=='checkbox') {
      var id_checkbox=[];
      id_checkbox.push(id_generator());
      id_checkbox.push(id_generator());
      id_checkbox.push(id_generator());
            $("#type_content_"+col).html("<div class='form-group'> <div class='row' style='margin-left: 0;'> <div class='col-lg-12' style='padding-top:2px'><div class='radio radio-inline radio-success'> <input type='radio' name='checkbox_type["+col+"]' required id='"+id_checkbox[0]+"' onclick='check_box_option(\""+col+"\",this.value)' value='custom_checkbox' > <label for='"+id_checkbox[0]+"' style='padding-left:0'>  Custom </label> </div> <div class='radio radio-inline radio-success'> <input type='radio' name='checkbox_type["+col+"]' required onclick='check_box_option(\""+col+"\",this.value)' id='"+id_checkbox[1]+"' value='checkbox_database' > <label for='"+id_checkbox[1]+"' style='padding-left:0'>From Database </label> </div> <div class='radio radio-inline radio-success'> <input type='radio' name='checkbox_type["+col+"]' required onclick='check_box_option(\""+col+"\",this.value)' id='"+id_checkbox[2]+"' value='checkbox_normalized' > <label for='"+id_checkbox[2]+"' style='padding-left:0'> From Database Normalized </label> </div> </div> </div> <span id='check_box_type_"+col+"'></span>  <div class='row' style='margin-left:0;'><div class='col-lg-2' style='padding-left:50px;padding-top:10px'>Style</div><div class='col-lg-7' style='padding-left:0;padding-top:3px'> <select name='show_checkbox["+col+"]' class='form-control'> <option value='horizontal'>Inline Horizontal</option> <option value='vertical'>Outline Vertical</option> </select> </div> </div></div>");

    }

    else if(val=='radio') {
      var id_checkbox=[];
      id_checkbox.push(id_generator());
      id_checkbox.push(id_generator());
      id_checkbox.push(id_generator());
            $("#type_content_"+col).html("<div class='form-group'> <div class='row' style='margin-left: 0;'> <div class='col-lg-12' style='padding-top:2px;margin-left:15px'><div class='radio radio-inline radio-success'> <input type='radio' name='radio_type["+col+"]' required id='"+id_checkbox[0]+"' onclick='radio_box_option(\""+col+"\",this.value)' value='custom_radio' > <label for='"+id_checkbox[0]+"' style='padding-left:0'>  Custom </label> </div> <div class='radio radio-inline radio-success'> <input type='radio' name='radio_type["+col+"]' required onclick='radio_box_option(\""+col+"\",this.value)' id='"+id_checkbox[1]+"' value='radio_database' > <label for='"+id_checkbox[1]+"' style='padding-left:0'>From Database </label> </div> <div class='radio radio-inline radio-success'> </div> </div> </div> <span id='radio_box_type_"+col+"'></span>  <div class='row' style='margin-left:0;'><div class='col-lg-2' style='padding-top:10px'>Style</div><div class='col-lg-7' style='padding-left:0;padding-top:3px'> <select name='show_radio["+col+"]' class='form-control'> <option value='horizontal'>Inline Horizontal</option> <option value='vertical'>Outline Vertical</option> </select> </div> </div></div>");

    }



    else if(val=='uimager')
        {
            $("#type_content_"+col).html("<div class='form-group'> <label class='control-label col-lg-2'>Width </label> <div class='col-lg-3' style='padding-left:0;padding-top:2px'> <input type='text' id='required' name='width["+col+"]' class='form-control'> </div> </div><div class='form-group'> <label class='control-label col-lg-2'>Height </label> <div class='col-lg-3' style='padding-left:0;padding-top:2px'> <input type='text' id='required' name='height["+col+"]' class='form-control'> </div> </div> ");
        }
         else if(val=='boolean')
        {
            $("#type_content_"+col).html("<div class='form-group'> <label class='control-label col-lg-3'>Yes Value </label> <div class='col-lg-3' style='padding-left:0;padding-top:2px'> <input type='text' id='required' name='yes_val["+col+"]' class='form-control'> </div> </div><div class='form-group'> <label class='control-label col-lg-3'>No Value </label> <div class='col-lg-3' style='padding-left:0;padding-top:2px'> <input type='text' id='required' name='no_val["+col+"]' class='form-control'> </div> </div> ");
        }
            else if(val=='image')
      {
               var maintb = $('#main_table').find(":selected").text();
            $("#type_content_"+col).html("<div class='form-group'> <label class='control-label col-lg-4'>Path Upload</label> <div class='col-lg-8' style='padding-top: 3px;'> <input type='text' id='required' name='path_foto["+col+"]' value='/upload/"+maintb+"' class='form-control col-lg-6'> </div> </div>");
      }
      else if(val=='file')
      {
             var maintb = $('#main_table').find(":selected").text();
            $("#type_content_"+col).html("<div class='form-group'> <label class='control-label col-lg-5'>Allowed Type</label> <div class='col-lg-7' style='padding-top: 3px;'> <input type='text' id='required' name='alowed["+col+"]' value='pdf|txt|docx|doc' class='form-control col-lg-6'> </div> </div><div class='form-group'> <label class='control-label col-lg-5'>Path Upload</label> <div class='col-lg-7' style='padding-top: 3px;'> <input type='text' id='required' name='path_file["+col+"]' value='/upload/"+maintb+"' class='form-control'> </div> </div>");
      }

     else {
       $("#type_content_"+col).html('');
    }
  }
  //from table , fetch on name and on value
  //param table, and column
  function from_table(tb,col)
  {
    if (tb!='') {
            $.ajax({
      url: "<?=base_admin();?>system/page/isi_on.php?tb="+tb+"&col="+col,
      success:function(data){

        $("#isi_on_"+col).html(data);
        }
      });
    }
     else {
      $("#isi_on_"+col).html('');
     }


  }

  //show primary chain
  function show_primary_chain(tb,col)
  {

          if (tb!='') {

      //foreign from checkbox
            $.ajax({
      url: "<?=base_admin();?>system/page/isi_chain_primary.php?tb="+tb+"&col="+col,
      success:function(data){

        $("#primary_chain_"+col).html(data);
         $("#parent_data_"+col).html('');
        }
        });

                    $.ajax({
      url: "<?=base_admin();?>system/page/isi_chain_label_first.php?tb="+tb+"&col="+col,
      success:function(data){

        $("#label_chain_col_first_"+col).html(data);
        }
        });


      $.ajax({
      url: "<?=base_admin();?>system/page/isi_chain_foreign_first.php?tb="+tb+"&col="+col,
      success:function(data){

        $("#primary_chain_foreign_"+col).html(data);
        }
        });





      }
       else {
        $("#primary_chain_"+col).html('');
        $("#label_chain_col_first_"+col).html('');
        $("#primary_chain_foreign_"+col).html('');
        $("#parent_data_"+col).html('');
       }
  }

    //show primary chain
  function show_primary_chain_parent(tb,col,rand,rand2,rand3)
  {

          if (tb!='') {

      //foreign from checkbox
            $.ajax({
      url: "<?=base_admin();?>system/page/isi_chain_primary_parent.php?tb="+tb+"&col="+col+"&rand_id="+rand,
      success:function(data){

        $("#primary_chain_parent_"+rand+"_"+col).html(data);
        $("#label_chain_col_"+rand2+"_"+col).html(data);

        }
        });

        $.ajax({
      url: "<?=base_admin();?>system/page/isi_chain_foreign_parent.php?tb="+tb+"&col="+col+"&rand_id="+rand,
      success:function(data){
         $("#foreign_chain_parent_"+rand3+"_"+col).html(data);

        }
        });




      }
       else {
        $("#primary_chain_parent_"+rand+"_"+col).html('');
        $("#label_chain_col_"+rand2+"_"+col).html('');
        $("#foreign_chain_parent_"+rand3+"_"+col).html('');
       }
  }


  //foreign table checkbox from
  function foreign_table_checkbox(tb,col)
  {

          if (tb!='') {

      //foreign from checkbox
            $.ajax({
      url: "<?=base_admin();?>system/page/foreign_table_checkbox.php?tb="+tb+"&col="+col,
      success:function(data){

        $("#foreign_key_from_"+col).html(data);
        }
        });

      //foreign main table
      $.ajax({
      url: "<?=base_admin();?>system/page/foreign_table_checkbox_main.php?tb="+tb+"&col="+col,
      success:function(data){

        $("#foreign_key_main_checkbox_"+col).html(data);
        }
        });

      }
       else {
        $("#isi_on_"+col).html('');
       }
  }

    //from table checkbox database
    function from_table_radio(tb,col)
  {
    if (tb!='') {
            $.ajax({
      url: "<?=base_admin();?>system/page/isi_label_for_radio.php?tb="+tb+"&col="+col,
      success:function(data){

        $("#isi_label_radio_"+col).html(data);
        }
      });
    }
     else {
      $("#isi_label_radio_"+col).html('');
     }


  }


  //from table checkbox database
    function from_table_checkbox(tb,col)
  {
    if (tb!='') {
            $.ajax({
      url: "<?=base_admin();?>system/page/isi_label_for_checkbox.php?tb="+tb+"&col="+col,
      success:function(data){

        $("#isi_display_checkbox_"+col).html(data);
        }
      });
    }
     else {
      $("#isi_display_checkbox_"+col).html('');
     }


  }

    //from table checkbox database
    function from_table_checkbox_normal(tb,col)
  {
    if (tb!='') {
       var maintb = $('#main_table').find(":selected").text();
            $.ajax({
      url: "<?=base_admin();?>system/page/isi_label_for_checkbox_normal.php?tb="+tb+"&col="+col+"&maintb="+maintb,
      success:function(data){

        $("#isi_display_checkbox_normal_"+col).html(data);
        }
      });
    }
     else {
      $("#isi_display_checkbox_normal_"+col).html('');
     }


  }


  function add_join()
  {
    var tb = $('#main_table').find(":selected").text();
     $.ajax({

      //?tb="+tb+"&col="+col
      url: "<?=base_admin();?>system/page/isi_join.php?prev_tb="+tb,
      success:function(data){

        $("#isi_join").append(data);
        }
      });
  }

  //val is selected table join
  function isi_kanan(val,prev_tb)
  {
     $('#isi_kanan_join').attr('id','isi_kanan_join_'+val);
    $.ajax({
      //?tb="+tb+"&col="+col
      url: "<?=base_admin();?>system/page/isi_kanan_join.php?tb="+val+"&prev="+prev_tb,
      success:function(data){
        $("#isi_kanan_join_"+val).html(data);
        }
      });
     $.ajax({
      //?tb="+tb+"&col="+col
      url: "<?=base_admin();?>system/service/embed_list.php?tb="+val,
      success:function(data){
        $('#list_table > tbody:last-child').append(data);
       // $("#embed").append(data);
        }
      });
  }


  //val is table
  function del_join(val)
  {

    $('#isi_kanan_join_'+val).parent().parent().remove();
    $('.embed_'+val).remove();
     $('.select_'+val).remove();
  }

  function add_join_with(prev)
  {
     $.ajax({

      //?tb="+tb+"&col="+col
      url: "<?=base_admin();?>system/page/isi_join_with.php?prev_tb="+prev,
      success:function(data){

        $("#isi_join").append(data);
        }
      });
  }

  //add multi image
  function add_multi_image()
  {
     var tb = $('#main_table').find(":selected").text();
    $.ajax({
      url: "<?=base_admin();?>system/page/isi_multi_image.php?prev_tb="+tb,
      success:function(data){

        $("#isi_remote").html(data);
        }
      });
  }

  //add custom select
  function add_new_row(col)
  {
      $("#add_next_row_"+col).append("<div class='row' style='margin-left: 0;'> <div class='col-lg-4' style='padding-top:2px'> <input type='text' id='required' name='select_custom_value["+col+"][]' placeholder='Value' class='form-control'> </div> <div class='col-lg-4' style='padding-left:0;padding-top:2px'> <input type='text' id='required' name='select_custom_display["+col+"][]' placeholder='Display Name' class='form-control'> </div> <div class='col-lg-1' style='padding-left:0;padding-top:6.5px'> <span class='btn btn-block btn-danger btn-xs hapus_row'><i class='fa fa-minus' style='padding: 4px'></i></span> </div> </div>");
  }

  //radio row
  function add_new_row_radio(col)
  {
       $("#add_next_row_radio_"+col).append("<div class='row' style='margin-left: 0;'> <div class='col-lg-4' style='padding-top:2px;margin-left:15px'> <input type='text' id='required' name='radio_custom_value["+col+"][]' placeholder='Value' class='form-control'> </div> <div class='col-lg-6' style='padding-left:0;padding-top:2px'> <input type='text' id='required' name='radio_custom_label["+col+"][]' placeholder='Item/Label Name' class='form-control'> </div> <div class='col-lg-1' style='padding-left:0;padding-top:6.5px'> <span class='btn btn-block btn-danger btn-xs hapus_row'><i class='fa fa-minus' style='padding: 5px'></i></span> </div> </div>");
  }


  //radio option
    function radio_box_option(col,value)
  {
 //   alert(col);
     // console.log('yes');
      if (value=='custom_radio') {
      $("#radio_box_type_"+col).html("<div class='form-group'> <div class='row' style='margin-left: 0;'> <div class='col-lg-4' style='padding-top:2px;margin-left:15px'> <input type='text' id='required' name='radio_custom_value["+col+"][]' placeholder='Value' class='form-control'> </div> <div class='col-lg-6' style='padding-left:0;padding-top:2px'> <input type='text' id='required' name='radio_custom_label["+col+"][]' placeholder='Item/Label Name' class='form-control'> </div> <div class='col-lg-1' style='padding-left:0;padding-top:6.5px'> <span onclick=\"add_new_row_radio('"+col+"')\" class='btn btn-block btn-info btn-xs'><i class='fa fa-plus' style='padding: 5px'></i></span> </div> </div> <span id='add_next_row_radio_"+col+"'></span> </div>");


      } else if(value=='radio_database') {

                  $.ajax({
                  url: "<?=base_admin();?>system/page/isi_from_table_radio.php?col="+col,
                  success:function(data){
                      $("#radio_box_type_"+col).html(data);
                    }
                  });
      }

  }



  function check_box_option(col,value)
  {
 //   alert(col);
     // console.log('yes');
      if (value=='custom_checkbox') {
          $("#check_box_type_"+col).html("<div class='form-group'> <div class='row' style='margin-left: 0;'> <div class='col-lg-9' style='padding-top:2px;margin-left: 15px;'> <input type='text' id='required' name='checkbox_custom_display["+col+"][]' placeholder='Display Item' class='form-control'> </div> <div class='col-lg-1' style='padding-left:0;padding-top:5.5px;width:50px'> <span onclick=\"add_new_row_checkbox('"+col+"')\" class='btn btn-block btn-info btn-xs'><i class='fa fa-plus' style='padding: 6px'></i></span> </div> </div><span id='add_new_row_checkbox"+col+"'></span> <span id='add_next_row_checkbox_"+col+"'></span> </div>");

      } else if(value=='checkbox_database') {

                  $.ajax({
                  url: "<?=base_admin();?>system/page/isi_from_table_checkbox.php?col="+col,
                  success:function(data){
                      $("#check_box_type_"+col).html(data);
                    }
                  });
      }

       else if (value=='checkbox_normalized') {
             $.ajax({
              url: "<?=base_admin();?>system/page/isi_from_table_checkbox_normal.php?col="+col,
              success:function(data){
                    $("#check_box_type_"+col).html(data);
                 }
              });
              }
  }

  function add_row_checkbox(col)
  {
      $("#check_box_type_"+col).html("<div class='row' style='margin-left: 0;'> <div class='col-lg-9' style='padding-top:2px'> <input type='text' id='required' name='checkbox_custom_display["+col+"][]' placeholder='Display Item' class='form-control'> </div> <div class='col-lg-1' style='padding-left:0;padding-top:5.5px;width:50px'> <span class='btn btn-block btn-danger btn-xs hapus_row'><i class='fa fa-minus' style='padding: 6px'></i></span> </div> </div>");
  }

   //add custom checkbox
  function add_new_row_checkbox(col)
  {
        $("#add_next_row_checkbox_"+col).append("<div class='row' style='margin-left: 0;'> <div class='col-lg-9' style='padding-top:2px;margin-left: 15px;'> <input type='text' id='required' name='checkbox_custom_display["+col+"][]' placeholder='Display Item' class='form-control'> </div> <div class='col-lg-1' style='padding-left:0;padding-top:5.5px;width:50px'> <span class='btn btn-block btn-danger btn-xs hapus_row'><i class='fa fa-minus' style='padding: 6px'></i></span> </div> </div>");
  }

  function add_new_row_chain(col)
  {
      $.ajax({
      url: "<?=base_admin();?>system/page/isi_chain_parent.php?col="+col,
      success:function(data){
        $("#parent_data_"+col).append(data);
        }
      });
  }

  //get foreign album table
  function get_foreign_album(key)
  {
     $.ajax({
      url: "<?=base_admin();?>system/page/isi_foreign_album.php?table="+key,
      success:function(data){
        $("#isi_foreign").html(data);
        }
      });
  }
  //album table
   //multi image foreign key
  function get_album(key)
  {

     $.ajax({
      url: "<?=base_admin();?>system/page/isi_create_album.php?table="+key,
      success:function(data){
        $("#isi_album").html(data);
        }
      });
  }

  //multi image foreign key
  function change_key(key)
  {

     $.ajax({
      url: "<?=base_admin();?>system/page/isi_remote_key.php?table="+key,
      success:function(data){
        $("#isi_remote_key").html(data);
        }
      });
  }
  //hapus multi
  function hapus_multi()
  {
    $("#isi_remote").html('');
  }

 $("body").on('click','.hapus_row',function() {
        $(this).parent().parent().remove();
        });
  $("body").on('click','.hapus_row_chain',function() {
        $(this).parent().parent().parent().remove();
        });
</script>
