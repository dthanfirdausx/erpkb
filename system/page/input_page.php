<?php
include "../../inc/config.php";
echo "<pre>";
print_r($_POST);


$main_table = $_POST['table'];
$primary_key = $_POST['primary_key'];



//jika type menu adalah single menu, parent set jadi 0
if ($_POST['type_menu']=='single') {
  $parent = 0;

  $parent_name = $db->fetch_single_row('sys_menu','id',$parent)->page_name;
   $parent_name = $parent_name;
} else {
  $parent = $_POST['parent'];
  $parent_name = "";

}


$column_dipilih = $_POST['dipilih1'];

if (isset($_POST['from_checkbox_normal'])) {
    $key_should_be_removed = array_keys($_POST['from_checkbox_normal']);

    foreach ($key_should_be_removed as $dihapus) {
        unset($column_dipilih[$main_table.".".$dihapus]);
    }


}


if(isset($_POST["tampil"])=="on")
    {
      $tampil = "Y";
    } else {
      $tampil = "N";
    }

if ($_POST['type_menu']=='main') {

  $data = array(
    'page_name'=>strtolower($_POST['page_name']),
    'icon'=>$_POST['icon'],
    'urutan_menu'=>$_POST['urutan_menu'],
    'parent'=>$parent,
    'parent_name' => $parent_name,
    'tampil'=>$tampil,
    'type_menu'=>$_POST['type_menu']
    );
  $db->insert('sys_menu',$data);

    $last_id= $db->last_insert_id();

  foreach ($db->fetch_all('sys_group_users') as $group) {
    if ($group->level=='admin') {
      $db->query("insert into sys_menu_role(id_menu,group_level,read_act,insert_act,update_act,delete_act)
  values (".$last_id.",'".$group->level."','Y','Y','Y','Y')");
    } else {
      $db->query("insert into sys_menu_role(id_menu,group_level,read_act,insert_act,update_act,delete_act)
  values (".$last_id.",'".$group->level."','N','N','N','N')");
    }

  }
  exit();
}



//let's create rule for validation
if (count($_POST['required'])>0) {
    foreach ($_POST['required'] as $required_one_key => $required_one_val) {
      if ($_POST['error_text'][$required_one_key]['type']=='checkbox') {
          $rule_req = "'$required_one_key"."[]'";
      } 
       else {
          $js_validate ='
      //trigger validation onchange
      $(\'select\').on(\'change\', function() {
          $(this).valid();
      });
      //hidden validate because we use chosen select
      $.validator.setDefaults({ ignore: ":hidden:not(select)" });
      ';
      $js_choosen ='
          //chosen select
          $(".chzn-select").chosen();
          $(".chzn-select-deselect").chosen({
              allow_single_deselect: true
          });
        ';
           $rule_req = $required_one_key;
      }
       
      $write_required_rule.='
          '.$rule_req.': {
          required: true,
          //minlength: 2
          },
        ';
      $write_required_message.='
          '.$rule_req.': {
          required: "'.$_POST['error_text'][$required_one_key]['text'].'",
          //minlength: "Your username must consist of at least 2 characters"
          },
        ';
      }
    $required_js ='
        rules: {
            '.$write_required_rule.'
        },
         messages: {
            '.$write_required_message.'
        },
    ';
}




$modul_name = str_replace(" ", "_", strtolower($_POST['page_name']));
//mkdir('../../modul/'.$modul_name);
if (!is_dir('../../modul/'.$modul_name)) {
  mkdir('../../modul/'.$modul_name);
}

$pilih=array();
$label=array();
$type=array();
$required=array();
$from=array();
$from_checkbox=array();
$on_value=array();
$on_name=array();
$on_name_checkbox=array();
$allowed = array();
$width=array();
$height=array();
$yes_val=array();
$no_val=array();
//checkbox normalized
$from_checkbox_normal=array();
$from_checkbox_normal_primary=array();
$foreign_table_checkbox=array();
$foreign_key_from = array();
$foreign_key_main_checkbox=array();




$select_table='';


//list table creation
foreach ($_POST['dipilih1'] as $key => $value) {
  $thead[$key]=$value;
  $col_head .="'$key',";
}

function array_not_unique($raw_array) {
    $dupes = array();
    natcasesort($raw_array);
    reset($raw_array);

    $old_key   = NULL;
    $old_value = NULL;
    foreach ($raw_array as $key => $value) {
        if ($value === NULL) { continue; }
        if (strcasecmp($old_value, $value) === 0) {
            $dupes[$old_key] = $old_value;
            $dupes[$key]     = $value;
        }
        $old_value = $value;
        $old_key   = $key;
    }
    return $dupes;
}



$primary_key_table_col = $main_table.".".$primary_key;

if (array_key_exists($primary_key_table_col, $column_dipilih)) {
    $primary_for_query = "";

} else {
    $primary_for_query = ",".$main_table.".".$primary_key;
}


foreach ($column_dipilih as $key_key => $val_val) {
      $key_dipilih_table[] = strstr($key_key, '.', true);
      $key_dipilih_column[] = str_replace(".","",strstr($key_key, '.'));
      $key_dipilih_only[] = $key_key;
      $key_dipilih_only_original[] = $key_key;
      $key_dipilih_result_data[] = str_replace(".","",strstr($key_key, '.'));
      $key_dipilih_result_data_original[] = str_replace(".","",strstr($key_key, '.'));
}
//print duplicate array
$array_dipilih_duplicate = array_not_unique($key_dipilih_column);

$jml_duplicate = count($array_dipilih_duplicate);

if ($jml_duplicate>1) {
    foreach ($array_dipilih_duplicate as $key => $value) {
    $new_array_duplicate[$key] = $key_dipilih_table[$key].".".$value." as ".$key_dipilih_table[$key]."_".$value;
    unset($key_dipilih_only[$key]);
    unset($key_dipilih_result_data[$key]);
    $new_column_name_dipilih[$key] = $key_dipilih_table[$key]."_".$value;
    }
    $col_new_dipilih = $key_dipilih_only + $new_array_duplicate;
    ksort($col_new_dipilih);

    //column for result data
    $new_column_result_data = $key_dipilih_result_data+$new_column_name_dipilih;
    ksort($new_column_result_data);

    //original column name
    $original_column_name = $key_dipilih_only_original;

    $column_head_query = implode(",", $col_new_dipilih);

    //aray combine original column with renamed one
    $assoc_col_new_col = array_combine($original_column_name, $new_column_result_data);

} else {
    $column_head_query = implode(",", array_keys($column_dipilih));
    $new_column_result_data = $key_dipilih_result_data_original;
    $original_column_name = array_keys($column_dipilih);

        //aray combine original column with renamed one
    $assoc_col_new_col = array_combine($original_column_name, array_keys($new_column_result_data));
}



if (array_key_exists($primary_key_table_col, $assoc_col_new_col)) {
    $primary_key_only_col = $assoc_col_new_col[$primary_key_table_col];
} else {
    $primary_key_only_col = $_POST['primary_key'];
}



//label list table
foreach ($_POST['label1'] as $key => $value) {
  if (in_array($key, array_keys($thead)) ) {
    $table_header .="
                                  <th>$value</th>";
  }
}

//checked berarti dibuath crud
foreach ($_POST['dipilih'] as $key => $value) {
  $pilih[$key]=$value;
}

//for label,
foreach (array_filter($_POST['label']) as $key => $value) {
  //check if sama dengan array yang ada di dipilih
  if (in_array($key, array_keys($pilih)) ) {
    $label[$key]=$value;
  }


}
//required checkbox
foreach ($_POST['required'] as $key => $value) {
  if (in_array($key, array_keys($pilih)) ) {
  $required[$key]=$value;
  }
}
//pilih type
foreach ($_POST['type'] as $key => $value) {
  if (in_array($key, array_keys($pilih)) ) {
  $type[$key]=$value;
  }
}

//from
foreach ($_POST['from'] as $key => $value) {
  $from[$key]=$value;
}

//on value
foreach ($_POST['on_value'] as $key => $value) {
  $on_value[$key]=$value;
}

//on name
foreach ($_POST['on_name'] as $key => $value) {
  $on_name[$key]=$value;
}


//from checkbox database
foreach ($_POST['from_checkbox'] as $key => $value) {
  $from_checkbox[$key]=$value;
}

//on name checkbox database
foreach ($_POST['on_name_checkbox'] as $key => $value) {
  $on_name_checkbox[$key]=$value;
}



//allowed upload file

foreach ($_POST['alowed'] as $key => $value) {
  $allowed[$key]=$value;
}

//witdh upload image resize
foreach ($_POST['width'] as $key => $value) {
  $width[$key]=$value;
}
//heigh upload image resize
foreach ($_POST['height'] as $key => $value) {
  $height[$key]=$value;
}

//boolean yes value
foreach ($_POST['yes_val'] as $key => $value) {
  $yes_val[$key]=$value;
}
//boolean no value
foreach ($_POST['no_val'] as $key => $value) {
  $no_val[$key]=$value;
}

$tes = array_merge_recursive($pilih,$label,$type,$required,$from,$from_checkbox,$on_name_checkbox,$on_value,$on_name,$allowed,$width,$height,$yes_val,$no_val);

$i=1;




$how_many_times = 0;
foreach ($tes as $key => $value) {


  if ($value[3]=='on') {
    $required="required";
    $required_sign = '<span style="color:#FF0000">*</span>';
  } else {
    $required='';
    $required_sign = '';
  }



  if ($value[2]=='text') {
    $input='
              <div class="form-group">
                <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                <div class="col-lg-10">
                  <input type="text" name="'.$key.'" placeholder="'.$value[1].'" class="form-control" '.$required.'>
                </div>
              </div><!-- /.form-group -->
              ';

    $update='
              <div class="form-group">
                <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                <div class="col-lg-10">
                  <input type="text" name="'.$key.'" value="<?=$data_edit->'.$key.';?>" class="form-control" '.$required.'>
                </div>
              </div><!-- /.form-group -->
              ';
    $detail='
              <div class="form-group">
                <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->'.$key.';?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              ';

    $input_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
    $update_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
  }elseif ($value[2]=='email') {
  $input='
              <div class="form-group">
                <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                <div class="col-lg-10">
                    <input type="text" data-rule-email="true" name="'.$key.'" placeholder="'.$value[1].'" class="form-control" '.$required.'>
                </div>
              </div><!-- /.form-group -->
            ';

    $update='
              <div class="form-group">
                <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                <div class="col-lg-10">
                  <input type="text"  data-rule-email="true" name="'.$key.'" value="<?=$data_edit->'.$key.';?>" class="form-control" '.$required.'>
                </div>
              </div><!-- /.form-group -->
              ';
    $detail='
              <div class="form-group">
                <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->'.$key.';?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              ';
    $input_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
    $update_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
  }    elseif ($value[2]=='time') {
    $input ='
              <div class="form-group">
                <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                <div class="col-lg-10">
                  <div class="bootstrap-timepicker">
                      <!-- time Picker -->
                      <div class="input-group">
                        <input type="text" class="form-control timepicker" name="'.$key.'">
                        <div class="input-group-addon">
                          <i class="fa fa-clock-o"></i>
                        </div>
                      </div>
                  </div><!-- /.input group -->
                    <textarea class="form-control col-xs-12" rows="5" name="'.$key.'" ></textarea>
                </div>
              </div><!-- /.form-group -->
              ';
    $update='
              <div class="form-group">
                <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                <div class="col-lg-10">
                  <div class="bootstrap-timepicker">
                    <!-- time Picker -->
                    <div class="input-group">
                      <input type="text" class="form-control timepicker" value="<?=$data_edit->'.$key.';?>" name="'.$key.'">
                        <div class="input-group-addon">
                          <i class="fa fa-clock-o"></i>
                      </div>
                    </div>
                  </div><!-- /.input group -->
                </div>
              </div><!-- /.form-group -->
              ';
   $detail='
              <div class="form-group">
                <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->'.$key.';?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              ';
    $input_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
    $update_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
  }

   elseif ($value[2]=='radio') {

       if ($_POST['radio_type'][$key]=='custom_radio') {

    $label_value = array_combine($_POST['radio_custom_value'][$key],$_POST['radio_custom_label'][$key]);


    if ($_POST['show_radio'][$key]=='horizontal') {
        $class_horizontal = "radio-inline";
    } else {
        $class_horizontal = "";
    }

            $increment_radio=1;

      foreach ($label_value as $dt_check => $dt_val) {

$radio_option.= '
                <div class="radio radio-success '.$class_horizontal.'">
                  <input type="radio" name="'.$key.'"  id="radio'.$increment_radio.'" value="'.$dt_check.'" '.$required.'>
                    <label for="radio'.$increment_radio.'" style="padding-left: 5px;">
                      '.$dt_val.'
                    </label>
                </div>
                ';

$radio_edit .= '
                <div class="radio radio-success '.$class_horizontal.'">
                  <input type="radio" name="'.$key.'"  id="radio'.$increment_radio.'" value="'.$dt_check.'" <?=($data_edit->'.$key.'=="'.$dt_check.'")?"checked":"";?> '.$required.'>
                    <label for="radio'.$increment_radio.'" style="padding-left: 5px;">
                      '.$dt_val.'
                    </label>
                </div>
                ';
                            $increment_radio++;
                 }




      $input = '
                <div class="form-group">
                  <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                  <div class="col-lg-10">
                    '.$radio_option.'
                  </div>
                </div><!-- /.form-group -->
                ';
      $update = '
                <div class="form-group">
                  <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                      <div class="col-lg-10">
                        '.$radio_edit.'
                      </div>
                </div><!-- /.form-group -->
                ';

      $detail='
                <div class="form-group">
                  <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                  <div class="col-lg-10">
                    <input type="text" disabled="" value="<?=$data_edit->'.$key.';?>" class="form-control">
                  </div>
                </div><!-- /.form-group -->
                ';
    $input_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
    $update_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';

    }

    elseif ($_POST['radio_type'][$key]=='radio_database') {

      if ($_POST['show_radio'][$key]=='horizontal') {
              $class_horizontal = "radio-inline";
          } else {
              $class_horizontal = "";
          }

    $from_radio = $_POST['from_radio'][$key];

    $value_radio_db = $_POST['value_radio_db'][$key];
    $value_radio_db = explode(".", $value_radio_db);
    $value_radio_db = $value_radio_db[1];

    $label_radio_db = $_POST['label_radio_db'][$key];
    $label_radio_db = explode(".", $label_radio_db);
    $label_radio_db = $label_radio_db[1];


      $input = '
                <div class="form-group">
                  <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                  <div class="col-lg-10">
                      <?php
                      $data_'.$key.' = $db->fetch_all("'.$from_radio.'");
                      $i=1;
                      foreach ($data_'.$key.' as $dt_'.$key.') {
                      ?>
                    <div class="radio radio-success '.$class_horizontal.'">
                      <input type="radio" name="'.$key.'"  id="radio<?=$i;?>" value="<?=$dt_'.$key.'->'.$value_radio_db.';?>" '.$required.'>
                      <label for="radio<?=$i;?>" style="padding-left: 5px;">
                        <?=$dt_'.$key.'->'.$label_radio_db.';?>
                      </label>
                    </div>
                      <?php
                          $i++;
                      }
                      ?>
                    </div>
                  </div><!-- /.form-group -->
                  ';

      $update = '
                <div class="form-group">
                  <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                  <div class="col-lg-10">
                   <?php
                  $data_'.$key.' = $db->fetch_all("'.$from_radio.'");
                  $i=1;
                  foreach ($data_'.$key.' as $dt_'.$key.') {
                  ?>
                    <div class="radio radio-success '.$class_horizontal.'">
                      <input type="radio" name="'.$key.'"  id="radio<?=$i;?>" value="<?=$dt_'.$key.'->'.$value_radio_db.';?>" <?=($data_edit->'.$key.'=="$dt_'.$key.'->'.$value_radio_db.'")?"checked":"";?> '.$required.'>
                      <label for="radio<?=$i;?>" style="padding-left: 5px;">
                        <?=$dt_'.$key.'->'.$label_radio_db.';?>
                      </label>
                    </div>
                  <?php
                      $i++;
                  }
                  ?>
                  </div>
                </div><!-- /.form-group -->
                ';

     $detail='
                <div class="form-group">
                  <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                  <div class="col-lg-10">
                    <input type="text" disabled="" value="<?=$data_edit->'.$key.';?>" class="form-control">
                  </div>
                </div><!-- /.form-group -->
                ';
          $input_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
          $update_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
    }


  }
  //checkbox
  elseif ($value[2]=='checkbox') {




    if ($_POST['checkbox_type'][$key]=='custom_checkbox') {
          $display_value = array_combine($_POST['checkbox_custom_display'][$key],$_POST['checkbox_custom_display'][$key]);

            $increment_check=1;

            if ($_POST['show_checkbox'][$key]=='horizontal') {
                      foreach ($display_value as $dt_check => $dt_val) {
                $checkbox_option .= '
                      <div class="checkbox checkbox-inline checkbox-success">
                        <input type="checkbox" id="inlineCheckbox'.$increment_check.'" name="'.$key.'[]" value="'.$dt_val.'" '.$required.'>
                        <label for="inlineCheckbox'.$increment_check.'" style="padding-left:0"> '.$dt_val.' </label>
                      </div>
                      ';
          $checkbox_edit .= '
                      <div class="checkbox checkbox-inline checkbox-success">
                        <input type="checkbox" id="inlineCheckbox'.$increment_check.'" name="'.$key.'[]" value="'.$dt_val.'" <?=in_array(\''.$dt_val.'\',$'.$key.'s)?\'checked\':\'\';?> '.$required.'>
                        <label for="inlineCheckbox'.$increment_check.'" style="padding-left:0"> '.$dt_val.' </label>
                      </div>
                      ';
                      $increment_check++;
                 }
            } else {
                      foreach ($display_value as $dt_check => $dt_val) {
                $checkbox_option .= '
                        <div class="checkbox checkbox-success">
                          <input id="checkbox'.$increment_check.'" type="checkbox" name="'.$key.'[]" value="'.$dt_val.'" '.$required.'>
                          <label for="checkbox'.$increment_check.'" style="padding-left:5px">
                            '.$dt_val.'
                          </label>
                        </div>
                        ';
        $checkbox_edit .= '
                        <div class="checkbox checkbox-success">
                          <input id="checkbox'.$increment_check.'" type="checkbox" name="'.$key.'[]" value="'.$dt_val.'" <?=in_array(\''.$dt_val.'\',$'.$key.'s)?\'checked\':\'\';?> '.$required.'>
                          <label for="checkbox'.$increment_check.'" style="padding-left:5px">
                            '.$dt_val.'
                          </label>
                        </div>
                        ';
                            $increment_check++;
                }
            }



  $input = '
                  <div class="form-group">
                      <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                      <div class="col-lg-10">
                        '.$checkbox_option.'
                      </div>
                  </div><!-- /.form-group -->
                  ';
  $update='
                  <?php

                  $'.$key.' = $db->fetch_single_row("'.$main_table.'","'.$primary_key.'",uri_segment(3))->'.$key.';

                  $'.$key.'s = explode(",", $'.$key.');

                  ?>

                  <div class="form-group">
                    <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                      <div class="col-lg-10">
                        '.$checkbox_edit.'
                      </div>
                  </div><!-- /.form-group -->
                  ';

        $for_action_checkbox .= '$'.$key.'s = implode(",", $_POST[\''.$key.'\']);';

        $detail='
                  <div class="form-group">
                    <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                    <div class="col-lg-10">
                      <input type="text" disabled="" value="<?=$data_edit->'.$key.';?>" class="form-control">
                    </div>
                  </div><!-- /.form-group -->
                  ';
            $input_action = '
              "'.$key.'"=>$'.$key.'s,';
            $update_action = '
              "'.$key.'"=>$'.$key.'s,';
    }

    elseif ($_POST['checkbox_type'][$key]=='checkbox_database') {


    if ($value[3]=='on') {
      $label_name = explode('.', $value[5]);
      $from_table_checkbox=$value[4];
    } else {
      $label_name = explode('.', $value[4]);
      $from_table_checkbox=$value[3];
    }



            if ($_POST['show_checkbox'][$key]=='horizontal') {
              $checkbox_option = '
                    <?php
                    $data_'.$key.' = $db->fetch_all("'.$from_table_checkbox.'");
                    $i=1;
                    foreach ($data_'.$key.' as $dt_'.$key.') {
                        ?>
                    <div class="checkbox checkbox-inline checkbox-success">
                      <input type="checkbox" id="inlineCheckbox<?=$i;?>" name="'.$key.'[]" value="<?=$dt_'.$key.'->'.$label_name[1].';?>" '.$required.'>
                      <label for="inlineCheckbox<?=$i;?>" style="padding-left:0"> <?=$dt_'.$key.'->'.$label_name[1].';?> </label>
                    </div>

                        <?php
                        $i++;
                    }
                    ?>';
$checkbox_edit= '
                    <?php
                      $'.$key.'s= explode(",", $data_edit->'.$key.');

                      $data_'.$label_name[1].'s= $db->fetch_all("'.$from_table_checkbox.'");
                      $i=1;
                      foreach ($data_'.$label_name[1].'s as $dt_'.$label_name[1].') {
                    ?>
                    <div class="checkbox checkbox-inline checkbox-success">
                      <input type="checkbox" id="inlineCheckbox_'.$key.'<?=$i;?>" name="'.$key.'[]" value="<?=$dt_'.$label_name[1].'->'.$label_name[1].';?>" <?=in_array($dt_'.$label_name[1].'->'.$label_name[1].',$'.$key.'s)?"checked":"";?> '.$required.'>
                      <label for="inlineCheckbox_'.$key.'<?=$i;?>" style="padding-left:0"> <?=$dt_'.$label_name[1].'->'.$label_name[1].';?> </label>
                    </div>
                        <?php
                        $i++;
                    }
                    ?>
                    ';

} else {

        $checkbox_option = '
                    <?php
                      $data_'.$key.' = $db->fetch_all("'.$from_table_checkbox.'");
                      $i=1;
                      foreach ($data_'.$key.' as $dt_'.$key.') {
                          ?>
                    <div class="checkbox checkbox-success">
                      <input type="checkbox" id="inlineCheckbox_'.$key.'<?=$i;?>" name="'.$key.'[]" value="<?=$dt_'.$key.'->'.$label_name[1].';?>" '.$required.'>
                      <label for="inlineCheckbox_'.$key.'<?=$i;?>" style="padding-left:0"> <?=$dt_'.$key.'->'.$label_name[1].';?> </label>
                    </div>

                        <?php
                        $i++;
                    }
                    ?>';
        $checkbox_edit= '
                    <?php

                      $'.$key.'s= explode(",", $data_edit->'.$key.');

                      $data_'.$label_name[1].'s= $db->fetch_all("'.$from_table_checkbox.'");
                      $i=1;
                      foreach ($data_'.$label_name[1].'s as $dt_'.$label_name[1].') {
                          ?>
                    <div class="checkbox  checkbox-success">
                      <input type="checkbox" id="inlineCheckbox_'.$key.'<?=$i;?>" name="'.$key.'[]" value="<?=$dt_'.$label_name[1].'->'.$label_name[1].';?>" <?=in_array($dt_'.$label_name[1].'->'.$label_name[1].',$'.$key.'s)?"checked":"";?> '.$required.'>
                      <label for="inlineCheckbox_'.$key.'<?=$i;?>" style="padding-left:0"> <?=$dt_'.$label_name[1].'->'.$label_name[1].';?> </label>
                    </div>
                        <?php
                        $i++;
                    }
                    ?>
                    ';
}




    $input = '
                  <div class="form-group">
                      <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                      <div class="col-lg-10">
                        '.$checkbox_option.'
                      </div>
                  </div><!-- /.form-group -->
                  ';
    $update= '
                  <div class="form-group">
                      <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                      <div class="col-lg-10">
                        '.$checkbox_edit.
                      '</div>
                  </div><!-- /.form-group -->
                  ';

        $for_action_checkbox .= '$'.$key.'s = implode(",", $_POST[\''.$key.'\']);';

    $detail='
                  <div class="form-group">
                    <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                    <div class="col-lg-10">
                      <input type="text" disabled="" value="<?=$data_edit->'.$key.';?>" class="form-control">
                    </div>
                  </div><!-- /.form-group -->
                  ';
            $input_action = '
      "'.$key.'"=>$'.$key.'s,';
            $update_action = '
      "'.$key.'"=>$'.$key.'s,';

    }

    elseif ($_POST['checkbox_type'][$key]=='checkbox_normalized') {


    $from_checkbox_normal = $_POST['from_checkbox_normal'][$key];

    $from_checkbox_normal_item = $_POST['from_checkbox_normal_item'][$key];

    $from_checkbox_normal_item_single = explode(".", $from_checkbox_normal_item);
    $from_checkbox_normal_item_single = $from_checkbox_normal_item_single[1];

    $primary_normal = $_POST['from_checkbox_normal_primary'][$key];

    $primary_from_checkbox = explode(".", $primary_normal);
    $primary_from_checkbox = $primary_from_checkbox[1];

    $foreign_table_checkbox = $_POST['foreign_table_checkbox'][$key];

    $foreign_key_from = $_POST['foreign_key_from'][$key];

    $foreign_key_from_normal = explode(".", $foreign_key_from);
    $foreign_key_from_normal = $foreign_key_from_normal[1];

    $foreign_key_main_checkbox = $_POST['foreign_key_main_checkbox'][$key];

    $main_foreign_key_normal = explode(".", $foreign_key_main_checkbox);
    $main_foreign_key_normal = $main_foreign_key_normal[1];




            if ($_POST['show_checkbox'][$key]=='horizontal') {
              $checkbox_option = '
                <?php
                  $data_'.$key.' = $db->fetch_all("'.$from_checkbox_normal.'");
                  $i=1;
                  foreach ($data_'.$key.' as $dt_'.$key.') {
                      ?>
                      <div class="checkbox checkbox-inline checkbox-success">
                        <input type="checkbox" id="inlineCheckbox<?=$i;?>" name="'.$key.'[]" value="<?=$dt_'.$key.'->'.$primary_from_checkbox.';?>" '.$required.'>
                        <label for="inlineCheckbox<?=$i;?>" style="padding-left:0"> <?=$dt_'.$key.'->'.$from_checkbox_normal_item_single.';?> </label>
                      </div>

                      <?php
                      $i++;
                  }
                ?>';
$checkbox_edit= '
                <?php

                  $'.$key.' = $db->query("select '.$primary_normal.' from '.$from_checkbox_normal.' inner join
                 '.$foreign_table_checkbox.' on '.$primary_normal.'='.$foreign_key_from.' where '.$foreign_key_main_checkbox.'=?",array("'.$foreign_key_main_checkbox.'" => uri_segment(3)));

                $'.$key.'s = array();
                 foreach ($'.$key.' as $dt) {
                      $'.$key.'s[] = $dt->'.$primary_from_checkbox.';
                 }




                $data_'.$key.'s= $db->fetch_all("'.$from_checkbox_normal.'");
                $i=1;
                foreach ($data_'.$key.'s as $dt_'.$key.') {
                    ?>
                      <div class="checkbox checkbox-inline checkbox-success">
                          <input type="checkbox" id="inlineCheckbox_'.$key.'<?=$i;?>" name="'.$key.'[]" value="<?=$dt_'.$key.'->'.$primary_from_checkbox.';?>" <?=in_array($dt_'.$key.'->'.$primary_from_checkbox.',$'.$key.'s)?"checked":"";?> '.$required.'>
                          <label for="inlineCheckbox_'.$key.'<?=$i;?>" style="padding-left:0"> <?=$dt_'.$key.'->'.$from_checkbox_normal_item_single.';?> </label>
                      </div>
                    <?php
                    $i++;
                }
                ?>
';

} else {

        $checkbox_option = '
                  <?php
                    $data_'.$key.' = $db->fetch_all("'.$from_checkbox_normal.'");
                    $i=1;
                    foreach ($data_'.$key.' as $dt_'.$key.') {
                        ?>
                            <div class="checkbox checkbox-success">
                                            <input type="checkbox" id="inlineCheckbox_'.$key.'<?=$i;?>" name="'.$key.'[]" value="<?=$dt_'.$key.'->'.$primary_from_checkbox.';?>" '.$required.'>
                                            <label for="inlineCheckbox_'.$key.'<?=$i;?>" style="padding-left:0"> <?=$dt_'.$key.'->'.$from_checkbox_normal_item_single.';?> </label>
                                        </div>

                        <?php
                        $i++;
                    }
                ?>';
        $checkbox_edit= '
                  <?php

                    $'.$key.' = $db->query("select '.$primary_normal.' from '.$from_checkbox_normal.' inner join
                   '.$foreign_table_checkbox.' on '.$primary_normal.'='.$foreign_key_from.' where '.$foreign_key_main_checkbox.'=?",array("'.$foreign_key_main_checkbox.'" => uri_segment(3)));

                  $'.$key.'s = array();
                   foreach ($'.$key.' as $dt) {
                        $'.$key.'s[] = $dt->'.$primary_from_checkbox.';
                   }



                  $data_'.$key.'s= $db->fetch_all("'.$from_checkbox_normal.'");
                  $i=1;
                  foreach ($data_'.$key.'s as $dt_'.$key.') {
                      ?>
                        <div class="checkbox checkbox-success">
                            <input type="checkbox" id="inlineCheckbox_'.$key.'<?=$i;?>" name="'.$key.'[]" value="<?=$dt_'.$key.'->'.$primary_from_checkbox.';?>" <?=in_array($dt_'.$key.'->'.$primary_from_checkbox.',$'.$key.'s)?"checked":"";?> '.$required.'>
                            <label for="inlineCheckbox_'.$key.'<?=$i;?>" style="padding-left:0"> <?=$dt_'.$key.'->'.$from_checkbox_normal_item_single.';?> </label>
                        </div>
                      <?php
                      $i++;
                  }
                  ?>
        ';

}

        $input = '
              <div class="form-group">
                  <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                  <div class="col-lg-10">
                    '.$checkbox_option.'
                  </div>
              </div><!-- /.form-group -->
              ';
        $update= '
              <div class="form-group">
                  <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                  <div class="col-lg-10">
                    '.$checkbox_edit.
                  '</div>
              </div><!-- /.form-group -->
              ';

        $for_action_checkbox_normal .= '

                $last_id = $db->last_insert_id();

                foreach ($_POST["'.$key.'"] as $'.$key.') {
                    $db->insert("'.$foreign_table_checkbox.'",array("'.$main_foreign_key_normal.'" => $last_id, "'.$foreign_key_from_normal.'" => $'.$key.'));
                }';

    $for_delete_normal = '
               $db->query("delete from '.$foreign_table_checkbox.' where '.$main_foreign_key_normal.'=?",array("'.$main_foreign_key_normal.'" => $_POST["id"]));';

    $for_action_checkbox_normal_update .= '
              foreach ($_POST["'.$key.'"] as $'.$key.') {
                  $db->insert("'.$foreign_table_checkbox.'",array("'.$main_foreign_key_normal.'" => $_POST["id"], "'.$foreign_key_from_normal.'" => $'.$key.'));
              }';

    $query_checkbox .= ',(select group_concat('.$from_checkbox_normal_item.') from '.$from_checkbox_normal.' inner join
 '.$foreign_table_checkbox.' on '.$primary_normal.'='.$foreign_key_from_normal.' where '.$main_foreign_key_normal.'='.$main_table.".".$primary_key.') as '.$key;


    $detail='
          <div class="form-group">
              <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=$data_edit->'.$key.';?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          ';
            $input_action = "";
            $update_action = "";

    }

  }
   elseif ($value[2]=='textarea_only') {
    $input ='
          <div class="form-group">
              <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
              <div class="col-lg-10">
                <textarea class="form-control col-xs-12" rows="5" name="'.$key.'" ></textarea>
              </div>
          </div><!-- /.form-group -->
          ';
  $update='
          <div class="form-group">
              <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
              <div class="col-lg-10">
              <textarea class="form-control col-xs-12" rows="5" name="'.$key.'"'.$required.'><?=$data_edit->'.$key.';?> </textarea>
              </div>
          </div><!-- /.form-group -->
          ';
  $detail='
          <div class="form-group">
              <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
              <div class="col-lg-10">
                <textarea class="form-control col-xs-12" rows="5" name="'.$key.'" disabled="" '.$required.'><?=$data_edit->'.$key.';?> </textarea>
              </div>
          </div><!-- /.form-group -->
          ';
    $input_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
    $update_action =
    '
      "'.$key.'"=>$_POST["'.$key.'"],';
  }
  elseif ($value[2]=='textarea') {
    $input ='
          <div class="form-group">
              <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
              <div class="col-lg-10">
                <textarea id="editbox" name="'.$key.'" class="editbox"></textarea>
              </div>
          </div><!-- /.form-group -->
          ';
    $update='
          <div class="form-group">
              <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
              <div class="col-lg-10">
                <textarea id="editbox" name="'.$key.'" class="editbox"'.$required.'><?=$data_edit->'.$key.';?> </textarea>
              </div>
          </div><!-- /.form-group -->
          ';
  $detail='
          <div class="form-group">
              <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
              <div class="col-lg-10">
                <textarea id="editbox" name="'.$key.'" disabled="" class="editbox"'.$required.'><?=$data_edit->'.$key.';?> </textarea>
              </div>
          </div><!-- /.form-group -->
          ';
    $input_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
    $update_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
  } elseif ($value[2]=='number') {
    $input='
          <div class="form-group">
              <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
              <div class="col-lg-10">
                <input type="text" data-rule-number="true" name="'.$key.'" placeholder="'.$value[1].'" class="form-control" '.$required.'>
              </div>
          </div><!-- /.form-group -->
          ';

    $update='
          <div class="form-group">
              <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
              <div class="col-lg-10">
                <input type="text" data-rule-number="true" name="'.$key.'" value="<?=$data_edit->'.$key.';?>" class="form-control" '.$required.'>
              </div>
          </div><!-- /.form-group -->
          ';

    $detail='
          <div class="form-group">
              <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=$data_edit->'.$key.';?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          ';

    $input_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
    $update_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
  }elseif ($value[2]=='date') {
    $detail='
          <div class="form-group">
              <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
              <div class="col-lg-10">
                <input type="text" disabled="" value="<?=tgl_indo($data_edit->'.$key.');?>" class="form-control">
              </div>
          </div><!-- /.form-group -->
          ';
    $input='
          <div class="form-group">
              <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl'.$i.'">
                    <input type="text" class="form-control" name="'.$key.'" '.$required.' />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          ';
    $update='
              <div class="form-group">
              <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
              <div class="col-lg-3">
                <div class="input-group date" id="tgl'.$i.'">
                    <input type="text" class="form-control" value="<?=$data_edit->'.$key.';?>" name="'.$key.'" '.$required.' />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
              </div>
          </div><!-- /.form-group -->
          ';
    $js_date='
    $("#tgl'.$i.'").datepicker({ 
    format: "yyyy-mm-dd",
    autoclose: true, 
    todayHighlight: true
    }).on("change",function(){
      $("#tgl'.$i.' :input").valid();
    });';

    $i++;
    $input_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
    $update_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
  } elseif ($value[2]=='boolean') {
    $input = '
          <div class="form-group">
              <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
              <div class="col-lg-10">
                <input name="'.$key.'" class="make-switch" type="checkbox" checked>
              </div>
          </div><!-- /.form-group -->
          ';

    if ($value[3]=='on') {
      $isi_yes=$value[4];
      $isi_no = $value[5];
    } else {
      $isi_yes=$value[3];
      $isi_no = $value[4];
    }
    $if_boolean.= '
          if(isset($_POST["'.$key.'"])=="on")
          {
            $'.$key.' = array("'.$key.'"=>"'.$isi_yes.'");
            $data=array_merge($data,$'.$key.');
          } else {
            $'.$key.' = array("'.$key.'"=>"'.$isi_no.'");
            $data=array_merge($data,$'.$key.');
          }';

    $update = '
            <div class="form-group">
                <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                <div class="col-lg-10">
                <?php if ($data_edit->'.$key.'=="'.$isi_yes.'") {
                ?>
                  <input name="'.$key.'" class="make-switch" type="checkbox" checked>
                <?php
              } else {
                ?>
                  <input name="'.$key.'" class="make-switch" type="checkbox">
                <?php
              }?>

                </div>
            </div><!-- /.form-group -->
            ';

    $detail='
            <div class="form-group">
                <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                <div class="col-lg-10">
                <?php if ($data_edit->'.$key.'=="'.$isi_yes.'") {
                  ?>
                  <input name="'.$key.'" class="make-switch" disabled type="checkbox" checked>
                  <?php
                } else {
                  ?>
                  <input name="'.$key.'" class="make-switch" disabled type="checkbox">
                  <?php
                }?>
                </div>
            </div><!-- /.form-group -->
            ';

    $input_action = '';
             $update_action = '';

  }
  elseif ($value[2]=='select_custom')
  {

    $value_display = array_combine($_POST['select_custom_value'][$key], $_POST['select_custom_display'][$key]);

    foreach ($value_display as $isi_key => $isi_value) {
        $option_custom .= PHP_EOL."<option value='$isi_key'>$isi_value</option>".PHP_EOL;
        $array_option .= PHP_EOL."'$isi_key' => '$isi_value',".PHP_EOL;



    }

    $input ='
            <div class="form-group">
                <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                <div class="col-lg-10">
                  <select name="'.$key.'" id="'.$key.'" data-placeholder="Pilih '.$value[1].' ..." class="form-control chzn-select" tabindex="2" '.$required.'>
                    '.$option_custom.'
                  </select>
                </div>
            </div><!-- /.form-group -->
            ';
    $update = '
            <div class="form-group">
                <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                <div class="col-lg-10">
                    <select id="'.$key.'" name="'.$key.'" data-placeholder="Pilih '.$value[1].'..." class="form-control chzn-select" tabindex="2" '.$required.'>
                      <option value=""></option>
                     <?php
                     $option = array('.$array_option.');
                     foreach ($option as $isi => $val) {

                        if ($data_edit->'.$key.'==$isi) {
                          echo "<option value=\'$data_edit->'.$key.'\' selected>$val</option>";
                        } else {
                       echo "<option value=\'$isi\'>$val</option>";
                          }
                     } ?>
                    </select>
                  </div>
            </div><!-- /.form-group -->
            ';
        $detail='
            <div class="form-group">
                <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                <div class="col-lg-10">
                <?php
                  $option = array('.$array_option.');
                  foreach ($option as $isi => $val) {
                  if ($data_edit->'.$key.'==$isi) {

                    echo "<input disabled class=\'form-control\' type=\'text\' value=\'$val\'>";
                  }
               } ?>
                  </div>
            </div><!-- /.form-group -->
            ';
    $input_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
             $update_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
  }

   elseif ($value[2]=='select_chaining')
  {

      $parent_table_chain_first = $_POST['parent_table_chain_first'][$key];
      $primary_chain_first = $_POST['primary_chain_first'][$key];
      $label_chain_col_first = $_POST['label_chain_col_first'][$key];

      $parent_table_chain = $_POST['parent_table_chain'][$key];
      $primary_key_chain = $_POST['parent_table_chain'][$key];


      ksort($primary_key_chain);
      $primary_key_chain = array_values($primary_key_chain);



      $label_chain_col = $_POST['label_chain_col'][$key];
      ksort($label_chain_col);
      $label_chain_col = array_values($label_chain_col);


      krsort($parent_table_chain);
      $parent_table_chain = array_values($parent_table_chain);


            //primary key data
      $primary_chain = $_POST['primary_chain'][$key];
      krsort($primary_chain);
      $primary_chain = array_values($primary_chain);





      $array_label_chain = $_POST['label_chain'][$key];
      krsort($array_label_chain);
      $array_label_chain = array_values($array_label_chain);

      $input_chaining_array = array_combine($parent_table_chain,$array_label_chain);


      $chaining_increment=0;
      $jml_chained_increment = count($input_chaining_array);
      foreach ($input_chaining_array as $ked => $vald) {

        $for_id_label = strtolower(str_replace(" ", "_", $vald));

        if ($chaining_increment==0) {
                      $input_chaining .='
              <div class="form-group">
                  <label for="'.$vald.'" class="control-label col-lg-2">'.$vald.' '.$required_sign.'</label>
                  <div class="col-lg-10">
                      <select name="'.$ked.'_'.$for_id_label.'" id="'.$ked.'_'.$for_id_label.'" data-placeholder="Pilih '.$vald.' ..." class="form-control chzn-select" tabindex="2" '.$required.'>
                        <option value="">Pilih '.$array_label_chain[$chaining_increment].'</option>
                        <?php foreach ($db->fetch_all("'.$ked.'") as $isi) {
                        echo "<option value=\'$isi->'.$primary_chain[$chaining_increment].'\'>$isi->'.$label_chain_col[$chaining_increment].'</option>";
                        } ?>
                      </select>
                  </div>
              </div><!-- /.form-group -->
                      ';
        } else {
                    $input_chaining .='
              <div class="form-group">
                  <label for="'.$vald.'" class="control-label col-lg-2">'.$vald.' '.$required_sign.'</label>
                  <div class="col-lg-10">
                      <select name="'.$ked.'_'.$for_id_label.'" id="'.$ked.'_'.$for_id_label.'" data-placeholder="Pilih '.$vald.' ..." class="form-control chzn-select" tabindex="2" '.$required.' >
                        <option value="">Pilih '.$array_label_chain[$chaining_increment-1].'</option>
                      </select>
                  </div>
              </div><!-- /.form-group -->
                    ';
        }

          $chaining_increment++;
      }

       //let's begin by defining last chained data
      //primary
      $primary_key_last_chained = $_POST['primary_chain_first'][$key];
      //foreign key
      $foreign_key_last_chained = $_POST['primary_chain_first_foreign'][$key];
      //table name last chained
      $table_name_last_chained = $_POST['parent_table_chain_first'][$key];
      //last label column field
      $label_column_last_chained = $_POST['label_chain_col_first'][$key];


      //let's defined main and others chained data
      //table chained array
      $table_chained_data = $_POST['parent_table_chain'][$key];
      krsort($table_chained_data);
      $table_chained_data = array_values($table_chained_data);

      //label chained data
      $label_chain_data = $_POST['label_chain'][$key];
      krsort($label_chain_data);
      $label_chain_data = array_values($label_chain_data);

      //label chain column data
      $label_chain_column_data = $_POST['label_chain_col'][$key];
      krsort($label_chain_column_data);
      $label_chain_column_data = array_values($label_chain_column_data);

      //primary key chained data
      $primary_key_chained_data = $_POST['primary_chain'][$key];
      krsort($primary_key_chained_data);
      $primary_key_chained_data = array_values($primary_key_chained_data);

            //foreign key chained data
      $foreign_key_chained_data = $_POST['foreign_chain'][$key];
      krsort($foreign_key_chained_data);
      $foreign_key_chained_data = array_values($foreign_key_chained_data);

       //edit box
      $chaining_increment_edit=0;
      foreach ($input_chaining_array as $ked => $vald) {

        $for_id_label = strtolower(str_replace(" ", "_", $vald));
          $edit_chaining .='

          <?php
          $'.$ked.' = $db->fetch_custom_single("select '.$ked.'.'.$primary_key_chained_data[$chaining_increment_edit].' from '.$ked;
              $loop_chain = count($table_chained_data);
          for ($i=$chaining_increment_edit; $i < $loop_chain  ; $i++) {
                if ($i!=$loop_chain-1) {
                  $edit_chaining.= '
                  inner join '.$table_chained_data[$i+1].' on '.$table_chained_data[$i].'.'.$primary_key_chained_data[$i].'='.$table_chained_data[$i+1].'.'.$foreign_key_chained_data[$i+1];
                } else {
                    $edit_chaining.= '
                    inner join '.$table_name_last_chained.' on '.$table_chained_data[$i].'.'.$primary_key_chained_data[$i].'='.$table_name_last_chained.'.'.$foreign_key_last_chained;
                }

           }
           $edit_chaining .='
           where '.$table_name_last_chained.'.'.$primary_key_last_chained.'='.'$data_edit->'.$key.'");
          ?>
            <div class="form-group">
                <label for="'.$vald.'" class="control-label col-lg-2">'.$vald.' '.$required_sign.'</label>
                <div class="col-lg-10">
                    <select name="'.$ked.'_'.$for_id_label.'"  id="'.$ked.'_'.$for_id_label.'" data-placeholder="Pilih '.$vald.'..." class="form-control chzn-select" tabindex="2" '.$required.'>
                    <option value=""></option>
                     <?php
                     ';
      if ($chaining_increment_edit==0) {
          $edit_chaining .='foreach ($db->fetch_all("'.$ked.'") as $isi) {';
      } else {
          $edit_chaining .='foreach ($db->query("select * from '.$ked.' where '.$foreign_key_chained_data[$chaining_increment_edit].'=$'.$table_chained_data[$chaining_increment_edit-1].'->'.$primary_key_chained_data[$chaining_increment_edit-1].'") as $isi) {';
      }

      $edit_chaining .='
                      if ($'.$ked.'->'.$primary_key_chained_data[$chaining_increment_edit].'==$isi->'.$primary_key_chained_data[$chaining_increment_edit].') {
                        echo "<option value=\'$isi->'.$primary_key_chained_data[$chaining_increment_edit].'\' selected>$isi->'.$label_chain_column_data[$chaining_increment_edit].'</option>";
                        } else {
                        echo "<option value=\'$isi->'.$primary_key_chained_data[$chaining_increment_edit].'\'>$isi->'.$label_chain_column_data[$chaining_increment_edit].'</option>";
                        }

                        } ?>
                      </select>
                  </div>
              </div><!-- /.form-group -->
              ';

          $chaining_increment_edit++;
      }

        $edit_chaining .= '
              <div class="form-group">
                  <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                  <div class="col-lg-10">
                  <?php
                  $'.$table_name_last_chained.' = $db->fetch_custom_single("select '.$table_name_last_chained.'.'.$primary_key_last_chained.' from '.$table_name_last_chained.'
                   where '.$table_name_last_chained.'.'.$primary_key_last_chained.'='.'$data_edit->'.$key.'");
                  ?>
                  <select name="'.$key.'" id="'.$table_name_last_chained.'_'.strtolower(str_replace(" ", "_", $value[1])).'" data-placeholder="Pilih '.$value[1].' ..." class="form-control chzn-select" tabindex="2" '.$required.'>
                    <option value=""></option>
                   <?php

                   foreach ($db->query("select * from '.$table_name_last_chained.' where '.$foreign_key_last_chained.'=$'.$ked.'->'.end($primary_key_chained_data).'") as $isi) {
                            if ($'.$table_name_last_chained.'->'.$primary_key_last_chained.'==$isi->'.$primary_key_last_chained.') {
                        echo "<option value=\'$isi->'.$primary_key_last_chained.'\' selected>$isi->'.$label_column_last_chained.'</option>";
                      } else {
                        echo "<option value=\'$isi->'.$primary_key_last_chained.'\'>$isi->'.$label_column_last_chained.'</option>";
                        }

                   } ?>
                    </select>
                  </div>
              </div><!-- /.form-group -->
            ';



      $counter_js = $label_chain_data;
      $table_counter_chained = $table_chained_data;


      $jumlah_chained_data = count($label_chain_column_data);
      for($jml_chaineded=0;$jml_chaineded<$jumlah_chained_data;$jml_chaineded++)
      {
         $next_increment = $jml_chaineded+1;
          if ($jml_chaineded != $jumlah_chained_data - 1) {
    $file_chain_data ='<?php
      session_start();
      include "../../inc/config.php";

      $'.strtolower(str_replace(" ", "_", $label_chain_data[$jml_chaineded])).' = $_POST["'.strtolower(str_replace(" ", "_", $label_chain_data[$jml_chaineded])).'"];

      $data = $db->query("select * from '.$table_chained_data[$next_increment].' where '.$foreign_key_chained_data[$next_increment].'=?",array("'.$foreign_key_chained_data[$next_increment].'" => $'.strtolower(str_replace(" ", "_", $label_chain_data[$jml_chaineded])).'));
      echo "<option value=\'\'>Pilih '.$label_chain_data[$next_increment].'</option>";
      foreach ($data as $dt) {
        echo "<option value=\'$dt->'.$primary_key_chained_data[$next_increment].'\'>$dt->'.$label_chain_column_data[$next_increment].'</option>";
      }
      ';
                $db->buat_file('../../modul/'.$modul_name.'/get_'.strtolower(str_replace(" ", "_", $label_chain_data[$next_increment])).'.php',$file_chain_data);
          } else {


              $file_chain_data ='<?php
      session_start();
      include "../../inc/config.php";

      $'.strtolower(str_replace(" ", "_", $label_chain_data[$jml_chaineded])).' = $_POST["'.strtolower(str_replace(" ", "_", $label_chain_data[$jml_chaineded])).'"];

      $data = $db->query("select * from '.$parent_table_chain_first.' where '.$foreign_key_last_chained.'=?",array("'.$foreign_key_last_chained.'" => $'.strtolower(str_replace(" ", "_", $label_chain_data[$jml_chaineded])).'));
       echo "<option value=\'\'>Pilih '.$label_chain_data[$next_increment].'</option>";
      foreach ($data as $dt) {
        echo "<option value=\'$dt->'.$primary_key_last_chained.'\'>$dt->'.$label_column_last_chained.'</option>";
      }
      ';
                $db->buat_file('../../modul/'.$modul_name.'/get_'.strtolower(str_replace(" ", "_", $value[1])).'.php',$file_chain_data);

          }
          $next_increment++;
      }


      //js chained data

      $js_chaining_input .='
                  <script type="text/javascript">';

      $jumlah_chained_data = count($label_chain_column_data);

      for($jml_chained=0;$jml_chained<$jumlah_chained_data;$jml_chained++)
      {

         $next_increment = $jml_chained+1;
         if ($jml_chained == $jumlah_chained_data - 1) {

        $js_chaining_input .='
                  $("#'.$table_chained_data[$jml_chained].'_'.strtolower(str_replace(" ", "_", $label_chain_data[$jml_chained])).'").change(function(){

                        $.ajax({
                        type : "post",
                        url : "<?=base_admin();?>modul/'.$modul_name.'/get_'.strtolower(str_replace(" ", "_", $value[1])).'.php",
                        data : {'.strtolower(str_replace(" ", "_", $label_chain_data[$jml_chained])).':this.value},
                        success : function(data) {
                            $("#'.$table_name_last_chained.'_'.strtolower(str_replace(" ", "_", $value[1])).'").html(data);
                            $("#'.$table_name_last_chained.'_'.strtolower(str_replace(" ", "_", $value[1])).'").trigger("chosen:updated");

                        }
                    });

                  });

                  ';
          } else {



              $js_chaining_input .='
                  $("#'.$table_chained_data[$jml_chained].'_'.strtolower(str_replace(" ", "_", $label_chain_data[$jml_chained])).'").change(function(){
                      $.ajax({
                      type : "post",
                      url : "<?=base_admin();?>modul/'.$modul_name.'/get_'.strtolower(str_replace(" ", "_", $label_chain_data[$next_increment])).'.php",
                      data : {'.strtolower(str_replace(" ", "_", $label_chain_data[$jml_chained])).':this.value},
                      success : function(data) {
                    ';


            array_shift($counter_js);
            array_shift($table_counter_chained);

               for($i=0;$i<count($counter_js);$i++)
               {
                if ($i==0) {
            $js_chaining_input .='
                          $("#'.$table_counter_chained[$i].'_'.strtolower(str_replace(" ", "_", $counter_js[$i])).'").html(data);
                          $("#'.$table_counter_chained[$i].'_'.strtolower(str_replace(" ", "_", $counter_js[$i])).'").trigger("chosen:updated");';
               }
                 else {
                                    $js_chaining_input .='
                          $("#'.$table_counter_chained[$i].'_'.strtolower(str_replace(" ", "_", $counter_js[$i])).'").html("");
                          $("#'.$table_counter_chained[$i].'_'.strtolower(str_replace(" ", "_", $counter_js[$i])).'").trigger("chosen:updated");';
               }
                }


                $js_chaining_input .='
                          $("#'.$table_name_last_chained.'_'.strtolower(str_replace(" ", "_", $value[1])).'").html("");
                          $("#'.$table_name_last_chained.'_'.strtolower(str_replace(" ", "_", $value[1])).'").trigger("chosen:updated");';

                $js_chaining_input .='
                        }
                    });

                  });
                  ';


      }

      $next_increment++;

                }

       $js_chaining_input .='
                  </script>';



     $input_chaining .= '
            <div class="form-group">
                <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                <div class="col-lg-10">
                  <select name="'.$key.'" id="'.$table_name_last_chained.'_'.strtolower(str_replace(" ", "_", $value[1])).'" data-placeholder="Pilih '.$value[1].' ..." class="form-control chzn-select" tabindex="2" '.$required.'>
                  </select>
                </div>
            </div><!-- /.form-group -->
            ';

    $input_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
    $update_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';

      $input = $input_chaining;
      $update = $edit_chaining;
      $input_js_chained = $js_chaining_input;



  }

  elseif ($value[2]=='select')
  {
    if ($value[3]=='on') {
      $val = explode('.', $value[5]);
      $val_name = explode('.', $value[6]);
      $from_table=$value[4];
    } else {
      $val = explode('.', $value[4]);
      $val_name = explode('.', $value[5]);
      $from_table=$value[3];
    }

    $input ='<div class="form-group">
                        <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                        <div class="col-lg-10">
            <select  id="'.$key.'" name="'.$key.'" data-placeholder="Pilih '.$value[1].' ..." class="form-control chzn-select" tabindex="2" '.$required.'>
               <option value=""></option>
               <?php foreach ($db->fetch_all("'.$from_table.'") as $isi) {
                  echo "<option value=\'$isi->'.$val[1].'\'>$isi->'.$val_name[1].'</option>";
               } ?>
              </select>
            </div>
                      </div><!-- /.form-group -->'.PHP_EOL;
        $update = '<div class="form-group">
                        <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                        <div class="col-lg-10">
              <select  id="'.$key.'" name="'.$key.'" data-placeholder="Pilih '.$value[1].'..." class="form-control chzn-select" tabindex="2" '.$required.'>
               <option value=""></option>
               <?php foreach ($db->fetch_all("'.$from_table.'") as $isi) {

                  if ($data_edit->'.$key.'==$isi->'.$val[1].') {
                    echo "<option value=\'$isi->'.$val[1].'\' selected>$isi->'.$val_name[1].'</option>";
                  } else {
                  echo "<option value=\'$isi->'.$val[1].'\'>$isi->'.$val_name[1].'</option>";
                    }
               } ?>
              </select>
          </div>
                      </div><!-- /.form-group -->'.PHP_EOL;

        $detail='<div class="form-group">
                        <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                        <div class="col-lg-10">
              <?php foreach ($db->fetch_all("'.$from_table.'") as $isi) {
                  if ($data_edit->'.$key.'==$isi->'.$val[1].') {

                    echo "<input disabled class=\'form-control\' type=\'text\' value=\'$isi->'.$val_name[1].'\'>";
                  }
               } ?>
              </div>
                      </div><!-- /.form-group -->'.PHP_EOL;

    $input_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
             $update_action =
    '
      "'.$key.'" => $_POST["'.$key.'"],';
  } //upload file
  elseif($value[2]=='ufile')
  {
    if ($value[3]=='on') {
      $tipe_alow=$value[4];
    } else {
      $tipe_alow=$value[3];
    }
    $input = '<div class="form-group">
                        <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                        <div class="col-lg-10">
              <div class="fileinput fileinput-new" data-provides="fileinput">
                            <div class="input-group">
                              <div class="form-control uneditable-input span3" data-trigger="fileinput">
                                <i class="glyphicon glyphicon-file fileinput-exists"></i> <span class="fileinput-filename"></span>
                              </div>
                              <span class="input-group-addon btn btn-default btn-file"><span class="fileinput-new">Select file</span> <span class="fileinput-exists">Change</span>
                                <input type="file" name="'.$key.'" '.$required.'>
                              </span>
                              <a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
                            </div>
                          </div>
                    </div>
                      </div><!-- /.form-group -->'.PHP_EOL;

                          $if_input_file = 'if (!is_dir("../../../upload/'.$modul_name.'")) {
              mkdir("../../../upload/'.$modul_name.'");
            }';
    $update = '<div class="form-group">
                        <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                        <div class="col-lg-10">
                <div class="fileinput fileinput-new" data-provides="fileinput">
                            <div class="input-group">
                              <div class="form-control uneditable-input span3" data-trigger="fileinput">
                                <i class="glyphicon glyphicon-file fileinput-exists"></i> <span class="fileinput-filename"></span>
                              </div>
                              <span class="input-group-addon btn btn-default btn-file"><span class="fileinput-new">Select file</span> <span class="fileinput-exists">Change</span>
                                <input type="file" name="'.$key.'">
                              </span>
                              <a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>

                            </div>
                             <a href="../../../../upload/'.$modul_name.'/<?=$data_edit->'.$key.'?>"><?=$data_edit->'.$key.'?></a>
                          </div>
                        </div>
                      </div><!-- /.form-group -->'.PHP_EOL;

                          $for_file_in .= '
                    if (!preg_match("/.('.$tipe_alow.')$/i", $_FILES["'.$key.'"]["name"]) ) {

              echo "pastikan file yang anda pilih '.$tipe_alow.'";
              exit();

            } else {
              move_uploaded_file($_FILES["'.$key.'"]["tmp_name"], "../../../upload/'.$modul_name.'/".$_FILES[\''.$key.'\'][\'name\']);
              $'.$key.' = array("'.$key.'"=>$_FILES["'.$key.'"]["name"]);
              $data = array_merge($data,$'.$key.');
            }';
                         $for_file .= '
                         if(isset($_FILES["'.$key.'"]["name"])) {
                        if (!preg_match("/.('.$tipe_alow.')$/i", $_FILES["'.$key.'"]["name"]) ) {

              echo "pastikan file yang anda pilih '.$tipe_alow.'";
              exit();

            } else {
              move_uploaded_file($_FILES["'.$key.'"]["tmp_name"], "../../../upload/'.$modul_name.'/".$_FILES[\''.$key.'\'][\'name\']);
              $db->deleteDirectory("../../../upload/'.$modul_name.'/".$db->fetch_single_row("'.$main_table.'","'.$primary_key.'",$_POST["id"])->'.$key.');
              $'.$key.' = array("'.$key.'"=>$_FILES["'.$key.'"]["name"]);
              $data = array_merge($data,$'.$key.');
            }

                         }';
//delete image action
$for_file_delete = '$db->deleteDirectory("../../../upload/'.$modul_name.'/".$db->fetch_single_row("'.$main_table.'","'.$primary_key.'",$_POST["id"])->'.$key.');';

        $detail='<div class="form-group">
                        <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                        <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->'.$key.';?>" class="form-control">
                  </div>
                      </div><!-- /.form-group -->'.PHP_EOL;

                          $input_action = '';
             $update_action = '';
  } elseif ($value[2]=='uimager') {
    if ($value[3]=='on') {
      $height_crop=$value[4];
      $width_crop=$value[5];
    } else {
      $height_crop=$value[3];
      $width_crop=$value[4];
    }
    $detail='<div class="form-group">
                        <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                        <div class="col-lg-10">
              <div class="fileinput fileinput-new" data-provides="fileinput">
                    <div class="fileinput-preview thumbnail" data-trigger="fileinput" style="width: 200px; height: 150px;">
                    <img src="../../../../upload/'.$modul_name.'/<?=$data_edit->'.$key.'?>"></div>
                  </div>
                  </div>
                      </div><!-- /.form-group -->'.PHP_EOL;
            $input = '<div class="form-group">
                        <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                        <div class="col-lg-10">
                      <div class="fileinput fileinput-new" data-provides="fileinput">
                            <div class="fileinput-new thumbnail" style="width: 200px; height: 150px;">
                              <img data-src="holder.js/100%x100%" alt="...">
                            </div>
                            <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 200px; max-height: 150px;"></div>
                            <div>
                              <span class="btn btn-default btn-file"><span class="fileinput-new">Select image</span> <span class="fileinput-exists">Change</span>
                                <input type="file" name="'.$key.'" accept="image/*">
                              </span>
                              <a href="#" class="btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
                            </div>
                          </div>
                        </div>
                      </div><!-- /.form-group -->'.PHP_EOL;
          $if_input_uimager = 'if (!is_dir("../../../upload/'.$modul_name.'")) {
              mkdir("../../../upload/'.$modul_name.'");
            }';
        $for_uimager_in .= '
                    if (!preg_match("/.(png|jpg|jpeg|gif|bmp)$/i", $_FILES["'.$key.'"]["name"]) ) {

              echo "pastikan file yang anda pilih png|jpg|jpeg|gif";
              exit();

            } else {
$db->compressImage($_FILES["'.$key.'"]["type"],$_FILES["'.$key.'"]["tmp_name"],"../../../upload/'.$modul_name.'/",$_FILES["'.$key.'"]["name"],'.$height_crop.','.$width_crop.');
            $'.$key.' = array("'.$key.'"=>$_FILES["'.$key.'"]["name"]);
              $data = array_merge($data,$'.$key.');
            }';

   $for_uimager .= '
                         if(isset($_FILES["'.$key.'"]["name"])) {
                        if (!preg_match("/.(png|jpg|jpeg|gif|bmp)$/i", $_FILES["'.$key.'"]["name"]) ) {

              echo "pastikan file yang anda pilih gambar";
              exit();

            } else {
$db->compressImage($_FILES["'.$key.'"]["type"],$_FILES["'.$key.'"]["tmp_name"],"../../../upload/'.$modul_name.'/",$_FILES["'.$key.'"]["name"],'.$height_crop.','.$width_crop.');
              $db->deleteDirectory("../../../upload/'.$modul_name.'/".$db->fetch_single_row("'.$main_table.'","'.$primary_key.'",$_POST["id"])->'.$key.');
              $'.$key.' = array("'.$key.'"=>$_FILES["'.$key.'"]["name"]);
              $data = array_merge($data,$'.$key.');
            }

                         }';
   $for_uimager_delete .= '
    $db->deleteDirectory("../../../upload/'.$modul_name.'/".$db->fetch_single_row("'.$main_table.'","'.$primary_key.'",$_POST["id"])->'.$key.');';

$update = '<div class="form-group">
                        <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                        <div class="col-lg-10">
              <div class="fileinput fileinput-new" data-provides="fileinput">
                            <div class="fileinput-new thumbnail" style="width: 200px; height: 150px;">
                             <img src="../../../../upload/'.$modul_name.'/<?=$data_edit->'.$key.'?>">
                            </div>
                            <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 200px; max-height: 150px;"></div>
                            <div>
                              <span class="btn btn-default btn-file"><span class="fileinput-new">Select image</span> <span class="fileinput-exists">Change</span>
                                <input type="file" name="'.$key.'" accept="image/*">
                              </span>
                              <a href="#" class="btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
                            </div>
                          </div>
                        </div>
                      </div><!-- /.form-group -->'.PHP_EOL;
                       $input_action = '';
             $update_action = '';

  } elseif ($value[2]=='uimagef') {
    $detail='<div class="form-group">
                        <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                        <div class="col-lg-10">
              <div class="fileinput fileinput-new" data-provides="fileinput">
                    <div class="fileinput-preview thumbnail" data-trigger="fileinput" style="width: 200px; height: 150px;">
                    <img src="../../../../upload/'.$modul_name.'/<?=$data_edit->'.$key.'?>"></div>
                  </div>
                  </div>
                      </div><!-- /.form-group -->'.PHP_EOL;
        $input = '<div class="form-group">
                        <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                        <div class="col-lg-10">
                    <div class="fileinput fileinput-new" data-provides="fileinput">
                            <div class="fileinput-new thumbnail" style="width: 200px; height: 150px;">
                              <img data-src="holder.js/100%x100%" alt="...">
                            </div>
                            <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 200px; max-height: 150px;"></div>
                            <div>
                              <span class="btn btn-default btn-file"><span class="fileinput-new">Select image</span> <span class="fileinput-exists">Change</span>
                                <input type="file" name="'.$key.'" accept="image/*">
                              </span>
                              <a href="#" class="btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
                            </div>
                          </div>
                          </div>
                      </div><!-- /.form-group -->'.PHP_EOL;

          $if_input_uimagef = 'if (!is_dir("../../../upload/'.$modul_name.'")) {
              mkdir("../../../upload/'.$modul_name.'");
            }';
        $for_uimagef_in .= '
                    if (!preg_match("/.(png|jpg|jpeg|gif|bmp)$/i", $_FILES["'.$key.'"]["name"]) ) {

              echo "pastikan file yang anda pilih png|jpg|jpeg|gif";
              exit();

            } else {
      move_uploaded_file($_FILES["'.$key.'"]["tmp_name"], "../../../upload/'.$modul_name.'/".$_FILES[\''.$key.'\'][\'name\']);

            $'.$key.' = array("'.$key.'"=>$_FILES["'.$key.'"]["name"]);
              $data = array_merge($data,$'.$key.');
            }';

   $for_uimagef .= '
                         if(isset($_FILES["'.$key.'"]["name"])) {
                        if (!preg_match("/.(png|jpg|jpeg|gif|bmp)$/i", $_FILES["'.$key.'"]["name"]) ) {

              echo "pastikan file yang anda pilih gambar";
              exit();

            } else {
      move_uploaded_file($_FILES["'.$key.'"]["tmp_name"], "../../../upload/'.$modul_name.'/".$_FILES[\''.$key.'\'][\'name\']);

              $db->deleteDirectory("../../../upload/'.$modul_name.'/".$db->fetch_single_row("'.$main_table.'","'.$primary_key.'",$_POST["id"])->'.$key.');
              $'.$key.' = array("'.$key.'"=>$_FILES["'.$key.'"]["name"]);
              $data = array_merge($data,$'.$key.');
            }

                         }';
   $for_uimagef_delete .= '
    $db->deleteDirectory("../../../upload/'.$modul_name.'/".$db->fetch_single_row("'.$main_table.'","'.$primary_key.'",$_POST["id"])->'.$key.');';

        $update = '<div class="form-group">
                        <label for="'.$value[1].'" class="control-label col-lg-2">'.$value[1].' '.$required_sign.'</label>
                        <div class="col-lg-10">
                    <div class="fileinput fileinput-new" data-provides="fileinput">
                            <div class="fileinput-new thumbnail" style="width: 200px; height: 150px;">
                             <img src="../../../../upload/'.$modul_name.'/<?=$data_edit->'.$key.'?>">
                            </div>
                            <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 200px; max-height: 150px;"></div>
                            <div>
                              <span class="btn btn-default btn-file"><span class="fileinput-new">Select image</span> <span class="fileinput-exists">Change</span>
                                <input type="file" name="'.$key.'" accept="image/*">
                              </span>
                              <a href="#" class="btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
                            </div>
                          </div>
                          </div>
                      </div><!-- /.form-group -->'.PHP_EOL;
                       $input_action = '';
             $update_action = '';

  }



$input_element .= $input;
$input_js_chaining .=$input_js_chained;

$js_date_input .=$js_date;

$update_element .= $update;
$detail_element .= $detail;

$for_input_action .= $input_action;
$for_update_action .=$update_action;

$option_custom = '';
$array_option = '';

$input_chaining = '';
$js_chaining_input = "";

$edit_chaining = "";
$chaining_increment = '';

$input_js_chained = "";
$checkbox_option = '';
$checkbox_edit = '';

$radio_option = '';
$radio_edit = '';

$how_many_times++;


}


if ($_POST['crud_style']=='modal') {
    $main_modal_status = "yes";
    if ($_POST['method_table']=='dtb_manual') {
        $reload_mode = 'location.reload();';
    } elseif ($_POST['method_table']=='dtb_server') {
        $reload_mode = 'dataTable.draw(false);';
    } else {
        $reload_mode = 'location.reload();';
    }
    $js_modal = '
      $("#add_'.$modul_name.'").click(function() {
          $.ajax({
              url : "<?=base_admin();?>modul/'.$modul_name.'/'.$modul_name.'_add.php",
              type : "GET",
              success: function(data) {
                  $("#isi_'.$modul_name.'").html(data);
              }
          });

      $(\'#modal_'.$modul_name.'\').modal({ keyboard: false,backdrop:\'static\',show:true });

    });
    ';
    $js_modal_edit = '
    $(".table").on(\'click\',\'.edit_data\',function(event) {
        $("#loadnya").show();
        event.preventDefault();
        var currentBtn = $(this);

        id = currentBtn.attr(\'data-id\');

        $.ajax({
            url : "<?=base_admin();?>modul/'.$modul_name.'/'.$modul_name.'_edit.php",
            type : "post",
            data : {id_data:id},
            success: function(data) {
                $("#isi_'.$modul_name.'").html(data);
                $("#loadnya").hide();
          }
        });

    $(\'#modal_'.$modul_name.'\').modal({ keyboard: false,backdrop:\'static\' });

    });
    ';
    $edit_data_modal = '$data_edit = $db->fetch_single_row("'.$main_table.'","'.$primary_key.'",$_POST[\'id_data\']);';
        $button_modal = 'id="add_'.$modul_name.'"';
    $modal_template ='
    <div class="modal" id="modal_'.$modul_name.'" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"> <div class="modal-dialog modal-lg"> <div class="modal-content"><div class="modal-header"> <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> <h4 class="modal-title"><?php echo $lang["add_button"];?> '.ucwords($_POST['page_name']).'</h4> </div> <div class="modal-body" id="isi_'.$modul_name.'"> </div> </div><!-- /.modal-content --> </div><!-- /.modal-dialog --> </div>
    ';
     $button_edit_modal = "";
     $button_edit_modal_manual = "data-uri";
} else {
    $main_modal_status = "no";
    $button_modal = 'href="<?=base_index();?>'.str_replace("_", "-", $modul_name).'/tambah"';
        $button_edit_modal = 'href=".base_index()."'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'/edit/\'+aData[indek]+\'';
     $button_edit_modal_manual = "href";

    $modal_template ='';
     $js_modal = '';
}



if ($_POST['method_table']=='gallery') {
$main_table = $_POST['album_table'];
$primary_key = $_POST['album_primary'];
  $input_gallery_action = '<?php
include "../../inc/config.php";
switch ($_GET["act"]) {
  case "in"://add new album & upload foto
  $data = array("'.$_POST['album_name'].'"=>$_POST["'.$_POST['album_name'].'"],"'.$_POST['deskripsi_album'].'"=>$_POST["'.$_POST['deskripsi_album'].'"]);
    $in = $db->insert("'.$_POST['album_table'].'",$data);
  $id_akhir=$db->last_insert_id();
  if (!is_dir("../../../upload/foto_'.$_POST['album_table'].'")) {
  mkdir("../../../upload/foto_'.$_POST['album_table'].'");
  }
  for ($i=0; $i <count($_FILES["foto_banyak"]["name"]) ; $i++) {
     if (!preg_match("/.(jpg|png|jpeg|gif|bmp)$/i", $_FILES["foto_banyak"]["name"][$i]) ) {
              echo "pastikan file yang anda pilih jpg|png|jpeg|gif|bmp";
              exit();
            } else {';
 if(isset($_POST["rename_foto"])=="on")
    {
      $input_gallery_action .='
$filename = $_FILES["foto_banyak"]["name"][$i];
$filename = preg_replace("#[^a-z.0-9]#i", "", $filename);
$ex = explode(".", $filename); // split filename
$fileExt = end($ex); // ekstensi akhir
 $filename = time().rand().".".$fileExt;//rename nama file';
    } else {
$input_gallery_action .='$filename = $_FILES["foto_banyak"]["name"][$i];';
    }
$input_gallery_action .='
move_uploaded_file($_FILES["foto_banyak"]["tmp_name"][$i], "../../../upload/foto_'.$_POST['album_table'].'/".$filename);
$db->insert("'.$_POST['foreign_album'].'",array("'.$_POST['foreign_album_nama'].'"=>$filename,"'.$_POST['foreign_album_id'].'"=>$id_akhir));
            }
  }
    if ($in=true) {
      echo "good";
    } else {
      return false;
    }
    break; //delete album
  case "hapus_album":
    foreach ($db->query("select * from '.$_POST['foreign_album'].' where '.$_POST['foreign_album_primary'].'=?",array("'.$_POST['foreign_album_primary'].'"=>$_GET["id"])) as $key) {
      $db->deleteDirectory("../../../upload/foto_'.$_POST['album_table'].'/".$key->'.$_POST['foreign_album_nama'].');
    }
    $db->delete("'.$_POST['album_table'].'","'.$_POST['album_primary'].'",$_GET["id"]);
    break;
    //update desc foto
  case "up_desc":
    $up=$db->update("'.$_POST['foreign_album'].'",array("'.$_POST['foreign_album_desc'].'"=>$_POST["'.$_POST['foreign_album_desc'].'"]),"'.$_POST['foreign_album_primary'].'",$_POST["id"]);
    if ($up=true) {
      echo "good";
    } else {
      return false;
    }
    break;
    //up album name & desc
  case "up_name":
    $data = array("'.$_POST['album_name'].'"=>$_POST["'.$_POST['album_name'].'"],"'.$_POST['deskripsi_album'].'"=>$_POST["'.$_POST['deskripsi_album'].'"],);
    $up = $db->update("'.$_POST['album_table'].'",$data,"'.$_POST['album_primary'].'",$_POST["id"]);
    if ($up=true) {
      echo "good";
    } else {
      return false;
    }
    break;
  //upload tambahan foto di album
  case "up":
$images = array();
  for ($i=0; $i <count($_FILES["foto_banyak"]["name"]) ; $i++) {
     if (!preg_match("/.(jpg|png|jpeg|gif|bmp)$/i", $_FILES["foto_banyak"]["name"][$i]) ) {
$images = array("error"=>"pastikan file yang anda pilih jpg|png|jpeg|gif|bmp");
            } else {';
 if(isset($_POST["rename_foto"])=="on")
    {
      $input_gallery_action .='
$filename = $_FILES["foto_banyak"]["name"][$i];
$filename = preg_replace("#[^a-z.0-9]#i", "", $filename);
$ex = explode(".", $filename); // split filename
$fileExt = end($ex); // ekstensi akhir
 $filename = time().rand().".".$fileExt;//rename nama file';
    } else {
$input_gallery_action .='$filename = $_FILES["foto_banyak"]["name"][$i];';
    }
$input_gallery_action .='
move_uploaded_file($_FILES["foto_banyak"]["tmp_name"][$i], "../../../upload/foto_'.$_POST['album_table'].'/".$filename);
$db->insert("'.$_POST['foreign_album'].'",array("'.$_POST['foreign_album_nama'].'"=>$filename,"'.$_POST['foreign_album_id'].'"=>$_POST["id"]));
$images[] = "../../../../upload/foto_'.$_POST['album_table'].'/".$filename;
            }
  }
?>
<script type="text/javascript">
  window.parent.Uploader.done(\'<?php echo json_encode($images); ?>\');
  </script>
<?php
    break;
case "hapus_foto":
  $db->deleteDirectory("../../../upload/foto_'.$_POST['album_table'].'/".$db->fetch_single_row("'.$_POST['foreign_album'].'","'.$_POST['foreign_album_primary'].'",$_GET["id"])->'.$_POST['foreign_album_nama'].');
    $db->delete("'.$_POST['foreign_album'].'","'.$_POST['foreign_album_primary'].'",$_GET["id"]);
  break;
  default:
    # code...
    break;
}
?>';

$query_album_on_list = '
<?php
 $limit =12;
        $search ="";
        if (isset($_GET["q"])) {
          $search_condition = $db->getRawWhereFilterForColumns
          ($_GET["q"], array("'.$_POST['album_name'].'"));
          $search = "where $search_condition";
        }

    $dtb=$pg->query("select * from '.$_POST['album_table'].' $search",$limit);
       $no=$pg->Num($limit);
        $count=$pg->Num($limit);

        if ($dtb->rowCount()<1) {
        echo\'<div class="col-xs-6" style="margin-top:10px">
    No matching records found
                        </div>\';
      }
      foreach ($dtb as $isi) {

      $sub=$db->query("select count('.$_POST['foreign_album_primary'].') as jml,'.$_POST['foreign_album_nama'].' from '.$_POST['foreign_album'].' where '.$_POST['foreign_album_id'].'=\'$isi->id\' limit 1");

        foreach ($sub as $sb) {
          ?>
<div class="col-sm-4">
<article class="album">
<header>
<a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'/detail/<?=$isi->id;?>">
<img class="img-responsive gambar" src="<?=base_url();?>upload/foto_'.$_POST['album_table'].'/<?=$sb->'.$_POST['foreign_album_nama'].';?>">
</a>

</header>
<section class="album-info">
<h3>
<a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'/detail/<?=$isi->id;?>"><?=$isi->'.$_POST['album_name'].';?></a>
</h3>
<p><?=$isi->'.$_POST['deskripsi_album'].';?></p>
</section>
<footer>
<div class="album-images-count"> <i class="fa fa-picture-o"></i>
<?=$sb->jml;?>
</div>
<div class="album-options">
<a class="hapus_album" data-id="<?=$data_edit->'.$_POST['album_primary'].';?>" data-uri="<?=base_admin();?>modul/'.$modul_name.'/'.$modul_name.'_action.php" data-gallery="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'"><i class="fa fa-trash"></i></a>
 </div>
 </footer>
 </article>
 </div>
                <?php
        }
    $no++;
}
?>';
$input_gallery = '
<form id="input" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/'.strtolower(str_replace(" ", "_", $_POST['page_name'])).'/'.$modul_name.'_action.php?act=in">
                      <div class="form-group">
                        <label for="Nama Album" class="control-label col-lg-2">Nama Album</label>
                        <div class="col-lg-10">
                          <input type="text" name="'.$_POST['album_name'].'" placeholder="Nama Album" class="form-control" required>
                        </div>
                      </div><!-- /.form-group -->
                      <div class="form-group">
                        <label for="Nama Album" class="control-label col-lg-2">Deskripsi Album</label>
                        <div class="col-lg-10">
                          <input type="text" name="'.$_POST['deskripsi_album'].'" placeholder="Deskripsi Album" class="form-control" required>
                        </div>
                      </div><!-- /.form-group -->
                      <div class="form-group">
                        <label class="control-label col-lg-2">Foto</label>
                        <div class="col-lg-10">
                          <div class="fileinput fileinput-new" data-provides="fileinput">
                            <div class="fileinput-new thumbnail" style="width: 200px; height: 150px;">
                              <img data-src="holder.js/100%x100%" alt="...">
                            </div>
                            <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 200px; max-height: 150px;"></div>
                            <div>
                              <span class="btn btn-default btn-file"><span class="fileinput-new">Select image</span> <span class="fileinput-exists">Change</span>
                                <input type="file" name="foto_banyak[]" accept="image/*">
                              </span>
                              <a href="#" class="btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
                            </div>
                          </div>
                            <span id="add_next">
                  </span>
                             <span class="btn btn-primary " onClick="add_multi()"><i class="fa fa-plus"></i> Add Foto</span>

                        </div>
                      </div>';
$gallery_detail_top = '
<div class="modal" id="foto-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"> <div class="modal-dialog"> <div class="modal-content"> <div class="modal-header"> <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button> <h4 class="modal-title">Edit Deskripsi Foto</h4> </div> <div class="modal-body"> <form id="upfoto" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/'.str_replace(" ", "_", $_POST['page_name']).'/'.str_replace(" ", "_", $_POST['page_name']).'_action.php?act=up_desc" novalidate="novalidate"> <div class="form-group"> <label for="nama_album" class="control-label col-lg-3">Deskripsi Foto</label> <div class="col-lg-9"> <input type="text" name="'.$_POST['foreign_album_desc'].'" placeholder="Deskripsi Foto" id="desc_foto" class="form-control"> </div> </div><!-- /.form-group --> <input id="id" type="hidden" name="id"> </div> <div class="modal-footer"> <button type="button" class="btn btn-outline pull-left" data-dismiss="modal">Close</button> <button type="submit" class="btn btn-primary" id="save">Save changes</button> </form> </div> </div><!-- /.modal-content --> </div><!-- /.modal-dialog --> </div><!-- /.modal --> <div class="modal" id="album-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"> <div class="modal-dialog"> <div class="modal-content"> <div class="modal-header"> <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button> <h4 class="modal-title">Edit Album</h4> </div> <div class="modal-body"> <form id="upalb" method="post" class="form-horizontal foto_banyak" action="<?=base_admin();?>modul/'.$modul_name.'/'.$modul_name.'_action.php?act=up_name" novalidate="novalidate"> <div class="form-group"> <label for="nama_album" class="control-label col-lg-3">Nama Album</label> <div class="col-lg-9"> <input value="<?=$data_edit->'.$_POST['album_name'].';?>" type="text" name="'.$_POST['album_name'].'" required="required" placeholder="nama_album" id="nama_album" class="form-control"> </div> </div><!-- /.form-group --> <div class="form-group"> <label for="deskripsi_album" class="control-label col-lg-3">Deskripsi Album</label> <div class="col-lg-9"> <textarea id="desc_album" name="'.$_POST['deskripsi_album'].'" class="form-control"><?=$data_edit->'.$_POST['deskripsi_album'].';?></textarea> </div> </div><!-- /.form-group --> <input type="hidden" name="id" value="<?=$data_edit->'.$_POST['album_primary'].';?>"> </div> <div class="modal-footer"> <button type="button" class="btn btn-outline pull-left" data-dismiss="modal">Close</button> <button type="submit" class="btn btn-primary" id="save">Save changes</button> </form> </div> </div><!-- /.modal-content --> </div><!-- /.modal-dialog --> </div><!-- /.modal -->
';
$button_gallery_detail = '
                  <div class="box-body">
<button class="btn edit_album"><i class="fa fa-pencil"></i> Edit Album</button>
<div id="upload_form" class="hide">
  <form action="<?=base_admin();?>modul/'.$modul_name.'/'.$modul_name.'_action.php?act=up" target="hidden_iframe" enctype="multipart/form-data" method="post">
  <input type="hidden" name="id" value="<?=$data_edit->'.$_POST['album_primary'].';?>">
    <input type="file" multiple name="foto_banyak[]" id="upload_files" multiple accept=\'image/*\'>
  </form>
</div>';
$album_title = '
     <h2 class=\'title_album\'>
               <?=$data_edit->'.$_POST['album_name'].';?>
              </h2>
               <h4 class=\'desc_album\'><?=$data_edit->'.$_POST['deskripsi_album'].';?></h4>
                 </div>
<div id="update_content">
 <div class="row" id="row_foto">
<?php
 $limit =12;
$fotos=$pg->query("select * from '.$_POST['foreign_album'].' where '.$_POST['foreign_album_id'].'=\'".$data_edit->'.$_POST['album_primary'].'."\' order by '.$_POST['foreign_album_primary'].' desc ",$limit);
       $no=$pg->Num($limit);
        $count=$pg->Num($limit);
  if ($fotos->rowCount()<1) {
        echo "No matching records found";
      }
    foreach ($fotos as $foto) {
      ?>
       <div class="col-lg-3 col-md-3 col-xs-6 thumb" id="foto_<?=$foto->'.$_POST['foreign_album_primary'].';?>">
        <span id="foto_list">
      <a class="fancybox" rel="gallery1" id="the_foto_<?=$foto->'.$_POST['foreign_album_primary'].';?>" href="<?=base_url();?>upload/foto_'.$_POST['album_table'].'/<?=$foto->'.$_POST['foreign_album_nama'].';?>" title="<?=$foto->'.$_POST['foreign_album_desc'].';?> ">
      <img class="img-responsive gambar_list" src="../../../../upload/foto_'.$_POST['album_table'].'/<?=$foto->'.$_POST['foreign_album_nama'].';?>" />
    </a>
    <span data-id="<?=$foto->'.$_POST['foreign_album_primary'].';?>" data-desc="<?=$foto->'.$_POST['foreign_album_desc'].';?>" class="btn btn-default  up_foto"><i class="fa fa-pencil icon"></i></span>

    <span data-id="<?=$foto->'.$_POST['foreign_album_primary'].';?>" data-uri="<?=base_admin();?>modul/'.$modul_name.'/'.$modul_name.'_action.php" class="btn btn-danger  hapus_foto"><i class="fa fa-trash"></i></span>
    </span>
            </div>

  <?php
      $no++;
    }
      ?>

                  </div>
</div>

                  <div class="row">
                          <div class="col-xs-6" style="margin-top:10px">
    Showing <?=$count;?> to <?=$no-1;?> of <?=$pg->total_record;?> entries

                        </div>

                        <div class="col-xs-6">

                                    <?php
                                    $pg->setParameter(array(
                                      \'range\'=>$limit,
                                      ));
                                      ?>

                                    <div class="dataTables_paginate paging_bootstrap">
                                    <ul class="pagination">
                                    <?=$pg->create();?>
                                    </ul>
                                    </div>
                        </div>
                </div>


<a href="<?=base_index();?>'.strtolower(str_replace(' ', '-', $_POST['page_name'])).'" class="btn btn-success "><i class="fa fa-step-backward"></i> Back</a>
                  </div>
                  </div>
              </div>
</div>
                </section><!-- /.content -->
<script>

function update_content(val)
{
  $.ajax({
      url: "<?=base_admin();?>modul/'.$modul_name.'/'.$modul_name.'_remote.php?id="+val,
      success:function(data){

        $("#update_content").html(data);
        }
      });
}
var Uploader = (function () {

        jQuery(\'#upload_files\').on(\'change\', function () {
            jQuery("#wait").removeClass(\'hide\');
            jQuery(\'#upload_files\').parent(\'form\').submit();
        });

        var fnUpload = function () {
            jQuery(\'#upload_files\').trigger(\'click\');
        }

        var fnDone = function (data) {

           update_content(\'<?=$data_edit->'.$_POST['album_primary'].';?>\');';
$gallery_remote = '
<div class="row" id="row_foto">
<?php
include "../../inc/config.php";
$id = $_GET[\'id\'];
 $limit =12;
$fotos=$pg->query("select * from '.$_POST['foreign_album'].' where '.$_POST['foreign_album_id'].'=\'".$id."\' order by '.$_POST['foreign_album_primary'].' desc ",$limit);
       $no=$pg->Num($limit);
        $count=$pg->Num($limit);

    foreach ($fotos as $foto) {
      ?>
       <div class="col-lg-3 col-md-3 col-xs-6 thumb" id="foto_<?=$foto->'.$_POST['foreign_album_primary'].';?>">
        <span id="foto_list">
      <a class="fancybox" rel="gallery1" id="the_foto_<?=$foto->'.$_POST['foreign_album_primary'].';?>" href="<?=base_url();?>upload/foto_'.$_POST['album_table'].'/<?=$foto->'.$_POST['foreign_album_nama'].';?>" title="<?=$foto->'.$_POST['foreign_album_desc'].';?> ">
      <img class="img-responsive" src="../../../../upload/foto_'.$_POST['album_table'].'/<?=$foto->'.$_POST['foreign_album_nama'].';?>" />
    </a>
    <span data-id="<?=$foto->'.$_POST['foreign_album_primary'].';?>" data-desc="<?=$foto->'.$_POST['foreign_album_desc'].';?>" class="btn btn-default  up_foto"><i class="fa fa-pencil icon"></i></span>

    <span data-id="<?=$foto->'.$_POST['foreign_album_primary'].';?>" data-uri="<?=base_admin();?>modul/'.$modul_name.'/'.$modul_name.'_action.php" class="btn btn-danger  hapus_foto"><i class="fa fa-trash"></i></span>
    </span>
            </div>

  <?php
      $no++;
    }
      ?>
                  </div>';
}

elseif ($_POST['method_table']=='dtb_server') {


     foreach ($new_column_result_data as $value_res) {

     $dtable_array .= '
    $ResultData[] = $value->'.$value_res.';';

    }

    foreach ($original_column_name as $value_ori) {
    $column_head .= "
    '$value_ori',";
    }
    $dtable_array .= '
    $ResultData[] = $value->'.$primary_key_only_col.';';
    $column_head .= "
    '$main_table.$primary_key',";

$disable_search = '$new_table->disable_search = array'."('$key','$main_table.$primary_key');";
$group_by = '$new_table->group_by = "group by '.$main_table.'.'.$primary_key.'";';

  $total_column = count($assoc_col_new_col);
  $total_column = $total_column;

  $create_order =  "'targets': [$total_column],";

  if ($_POST['create_number']=='on') {
    $total_column = $total_column+1;
    $create_order =  "'targets': [$total_column],";
    $column_def = "
           'columnDefs': [ {
            ".$create_order."
              'orderable': false,
              'searchable': false
            },
                {
            'width': '5%',
            'targets': 0,
            'orderable': false,
            'searchable': false,
            'className': 'dt-center'
          }
             ],

    ";
     $set_numbering = '
  //set numbering is true
  $datatable->set_numbering_status(1);';
    $head_no = "<th>No</th>";
    $i_init = '$i=1;';
    $i_increment = '$i++;';

    $create_number = '  $ResultData[] = $datatable->number($i);';
    $create_order =  "'targets': [0,$total_column],";
  } else {
    $create_number = "";
    $column_def = "

         //disable order dan searching pada tombol aksi
                 'columnDefs': [ {
             ".$create_order."
              'orderable': false,
              'searchable': false

            } ],
      ";
  }

  for ($i=0; $i <count($_POST['join_cond']) ; $i++) {
  $join.= " ".$_POST['join_cond'][$i]." join ".$_POST['join_with'][$i]." on ".$_POST['join_on_first'][$i]."=".$_POST['join_on_next'][$i];
  }
}
 elseif ($_POST['method_table']=='dtb_manual') {

  if ($_POST['create_number']=='on') {
        $i_init = '$i=1;';
        $i_increment = '$i++;';

        $head_no = '<th style="width:25px" align="center">No</th>';

        $loop_number = '<td align="center"><?=$i;?></td>';
  }


  foreach ($new_column_result_data as $value) {


  $col .='
          <td><?=$isi->'.$value.';?></td>';
  }
  if (count($_POST['join_cond'])>0) {
    for ($i=0; $i <count($_POST['join_cond']) ; $i++) {
      $join1.= " ".$_POST['join_cond'][$i]." join ".$_POST['join_with'][$i]." on ".$_POST['join_on_first'][$i]."=".$_POST['join_on_next'][$i];
    }

      $select_table = '
      <?php
      $dtb=$db->query("select '.$column_head_query.$primary_for_query.$query_checkbox.' from '.$main_table.' '.$join1.' order by '.$_POST['order_by'].' '.$_POST['order_by_type'].'");
      '.$i_init.'
      foreach ($dtb as $isi) {
        ?>
      <tr id="line_<?=$isi->'.$primary_key_only_col.';?>">
        '.$loop_number.$col.'
        <td>
          <?php
          echo \'<a href="\'.base_index().\''.strtolower(str_replace(" ", "-", $_POST['page_name'])).'/detail/\'.$isi->'.$primary_key_only_col.'.\'" class="btn btn-success "><i class="fa fa-eye"></i></a> \';
          if($role_act["up_act"]=="Y") {
            echo \'<a '.$button_edit_modal_manual.'="\'.base_index().\''.strtolower(str_replace(" ", "-", $_POST['page_name'])).'/edit/\'.$isi->'.$primary_key_only_col.'.\'" data-id="\'.$isi->'.$primary_key_only_col.'.\'" class="btn edit_data btn-primary "><i class="fa fa-pencil"></i></a> \';
          }
          if($role_act["del_act"]=="Y") {
            echo \'<button class="btn btn-danger hapus"  data-uri="\'.base_admin().\'modul/'.strtolower(str_replace(" ", "_", $_POST['page_name'])).'/'.$modul_name.'_action.php" data-variable="\'dtb_'.$modul_name.'\'" data-id="\'.$isi->'.$primary_key_only_col.'.\'"><i class="fa fa-trash-o"></i></button>\';
          }
        ?>
        </td>
      </tr>
        <?php
       '.$i_increment.'
      }
      ?>';
  } else {
      $select_table = '
      <?php
      $dtb=$db->query("select '.$column_head_query.$primary_for_query.$query_checkbox.' from '.$main_table.'");
      $i=1;
      foreach ($dtb as $isi) {
        ?><tr id="line_<?=$isi->'.$primary_key_only_col.';?>">
          <td align="center"><?=$i;?></td>'.$col.'
        <td>
            <?php
            echo \'<a href="\'.base_index().\''.strtolower(str_replace(" ", "-", $_POST['page_name'])).'/detail/\'.$isi->'.$primary_key_only_col.'.\'" class="btn btn-success "><i class="fa fa-eye"></i></a> \';
            if($role_act["up_act"]=="Y") {
              echo \'<a '.$button_edit_modal_manual.'="\'.base_index().\''.strtolower(str_replace(" ", "-", $_POST['page_name'])).'/edit/\'.$isi->'.$primary_key_only_col.'.\'" data-id="\'.$isi->'.$primary_key_only_col.'.\'" class="btn edit_data btn-primary "><i class="fa fa-pencil"></i></a> \';
            }
            if($role_act["del_act"]=="Y") {
              echo \'<button class="btn btn-danger hapus " data-uri="\'.base_admin().\'modul/'.strtolower(str_replace(" ", "_", $_POST['page_name'])).'/'.$modul_name.'_action.php" data-id="\'.$isi->'.$primary_key_only_col.'.\'"><i class="fa fa-trash-o"></i></button>\';
            }
          ?>
        </td>
        </tr>
        <?php
      $i++;
      }
      ?>';
  }

} elseif ($_POST['method_table']=='manual_pagination') {
  foreach ($original_column_name as $value) {
      $col_search.='"'.$value.'",';
  }
  foreach ($new_column_result_data as $key => $value) {
        $i_init = '$no=$pg->Num($limit);';
        $i_increment = '$no++;';

    if ($_POST['create_number']=='on') {


        $head_no = '<th style="width:25px" align="center">No</th>'.PHP_EOL;

        $loop_number = '<td align="center"><?=$i;?></td>';
        $set_no = '<td><?=$no;?></td>';
  }


  $col .='
          <td><?=$isi->'.$value.'?></td>';

  }
  if (count($_POST['join_cond'])>0) {
    for ($i=0; $i <count($_POST['join_cond']) ; $i++) {
      $join1.= " ".$_POST['join_cond'][$i]." join ".$_POST['join_with'][$i]." on ".$_POST['join_on_first'][$i]."=".$_POST['join_on_next'][$i];
    }
      $select_table = '
      <?php
        $limit =10;
        $search ="";
        if (isset($_GET["q"])) {
          $search_condition = $db->getRawWhereFilterForColumns($_GET["q"], array('.$col_search.'));
          $search = "where $search_condition";
        }

        $dtb=$pg->query("select '.$column_head_query.$primary_for_query.$query_checkbox.' from '.$main_table.' '.$join1.' $search",$limit);
          '.$i_init.'
        $count=$pg->Num($limit);
        if ($dtb->rowCount()<1) {
          echo "<tr><td colspan=\''.(count($_POST['dipilih1'])+2).'\'>No matching records found</td></tr>";
        }
      foreach ($dtb as $isi) {
      ?>
        <tr id="line_<?=$isi->'.$primary_key_only_col.';?>">
          '.$set_no.''.$col.'
          <td>
          <a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'/detail/<?=$isi->'.$primary_key_only_col.';?>" class="btn btn-success "><i class="fa fa-eye"></i></a>
          <?php
            if($role_act["up_act"]=="Y") {
              echo "<a href=\'".base_index()."'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'/edit/".$isi->'.$primary_key_only_col.'."\' data-id=\'".$isi->'.$primary_key_only_col.'."\' class=\'btn edit_data btn-primary\'><i class=\'fa fa-pencil\'></i></a> ";
            }
            if($role_act["del_act"]=="Y") {
              echo "<button class=\'btn btn-danger hapus_manual\' data-uri=\'".base_admin()."modul/'.strtolower(str_replace(" ", "_", $_POST['page_name'])).'/'.$modul_name.'_action.php\' data-id=\'".$isi->'.$primary_key_only_col.'."\'><i class=\'fa fa-trash-o\'></i></button>";
            }
            ?>
          </td>
        </tr>
        <?php
        '.$i_increment.'
        }
      ?>';

      $bottom_pagination='
      <div class="col-xs-6" style="margin-top:10px">
        Showing <?=$count;?> to <?=$no-1;?> of <?=$pg->total_record;?> entries
      </div>

      <div class="col-xs-6">
          <?php
              if (isset($_GET[\'q\'])) {
                  $pg->url=base_index()."'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'?q=".$_GET[\'q\']."&page=";
              }
              $pg->setParameter(array(
                  \'range\'=>$limit,
              ));
          ?>
          <div class="dataTables_paginate paging_bootstrap">
              <ul class="pagination">
                  <?=$pg->create();?>
              </ul>
          </div>
      </div>';
  } else {
      $select_table = '
      <?php
        $limit =10;
        $search ="";
        if (isset($_GET["q"])) {
            $search_condition = $db->getRawWhereFilterForColumns($_GET["q"], array('.$col_search.'));
            $search = "where $search_condition";
        }
        $dtb=$pg->query("select '.$column_head_query.$primary_for_query.$query_checkbox.' from '.$main_table.' $search",$limit);
        $no=$pg->Num($limit);
        $count=$pg->Num($limit);
        if ($dtb->rowCount()<1) {
            echo "<tr><td colspan=\''.(count($_POST['dipilih1'])+2).'\'>No matching records found</td></tr>";
        }
      foreach ($dtb as $isi) {
        ?>
        <tr id="line_<?=$isi->'.$primary_key_only_col.';?>">
          <td align="center"><?=$no;?></td>'.$col.'
          <td>
            <a href="<?=base_index();?>'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'/detail/<?=$isi->'.$primary_key_only_col.';?>" class="btn btn-success "><i class="fa fa-eye"></i></a>
            <?php
              if($role_act["up_act"]=="Y") {
                echo "<a href=\'".base_index()."'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'/edit/".$isi->'.$primary_key_only_col.'."\' data-id=\'".$isi->'.$primary_key_only_col.'."\' class=\'btn edit_data btn-primary\'><i class=\'fa fa-pencil\'></i></a> ";
              }
              if($role_act["del_act"]=="Y") {
                echo "<button class=\'btn btn-danger hapus_manual\' data-uri=\'".base_admin()."modul/'.strtolower(str_replace(" ", "_", $_POST['page_name'])).'/'.$modul_name.'_action.php\' data-id=\'".$isi->'.$primary_key_only_col.'."\'><i class=\'fa fa-trash-o\'></i></button>";
              }
              ?>
          </td>
        </tr>
        <?php
      $no++;
      }
      ?>';
      $bottom_pagination='
      <div class="col-xs-6" style="margin-top:10px">
          Showing <?=$count;?> to <?=$no-1;?> of <?=$pg->total_record;?> entries
      </div>

      <div class="col-xs-6">
          <?php
              if (isset($_GET[\'q\'])) {
                  $pg->url=base_index()."'.strtolower(str_replace(" ", "-", $_POST['page_name'])).'?q=".$_GET[\'q\']."&page=";
              }
              $pg->setParameter(array(
                  \'range\'=>$limit,
              ));
          ?>
          <div class="dataTables_paginate paging_bootstrap">
            <ul class="pagination">
              <?=$pg->create();?>
            </ul>
          </div>
      </div>';
  }
  $js_date_input = "";
}




include "template.php";

if ($_POST['crud_style']=='modal') {
    $modul_add_template = $modul_add_modal;
    $modul_edit_template = $modul_edit_modal;
} else {
    $modul_add_template = $modul_add;
    $modul_edit_template = $modul_edit;
}
//write form add
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_add.php',$modul_add_template);
//write main modul action
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'.php',$main);

//write modul data
if ($_POST['method_table']=='dtb_server') {
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_data.php',$modul_data);
//write list table
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_view.php',$list_table);
//write modul action
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_action.php',$modul_action);
//write modul edit form
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_edit.php',$modul_edit_template);
//write detial form
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_detail.php',$modul_detail);

} elseif($_POST['method_table']=='dtb_manual') {
  //write list table
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_view.php',$list_table_off);
//write modul action
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_action.php',$modul_action);
//write modul edit form
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_edit.php',$modul_edit_template);
//write detial form
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_detail.php',$modul_detail);

} elseif($_POST['method_table']=='manual_pagination') {
    //write list table
  $db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_view.php',$list_table_manual);
  //write modul action
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_action.php',$modul_action);
//write modul edit form
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_edit.php',$modul_edit_template);
//write detial form
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_detail.php',$modul_detail);
} elseif($_POST['method_table']=='gallery') {
  //write list table
  $db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_view.php',$list_gallery);
//write gallery action
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_action.php',$input_gallery_action);
//write gallery detail
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_detail.php',$gallery_detail);
//write remote gallery file
$db->buat_file('../../modul/'.$modul_name.'/'.$modul_name.'_remote.php',$gallery_remote);
}



$data = array(
  'nav_act'=>$modul_name,
  'page_name'=>strtolower($_POST['page_name']),
  'main_table'=>$_POST['table'],
  'urutan_menu'=>$_POST['urutan_menu'],
  'url'=>strtolower(str_replace(" ", "-", $_POST['page_name'])),
  'icon'=>$_POST['icon'],
    'parent'=>$parent,
    'parent_name' => $parent_name,
    'tampil'=>$tampil,
    'type_menu'=>$_POST['type_menu']
  );
    $db->insert('sys_menu',$data);

    $last_id= $db->last_insert_id();

  foreach ($db->fetch_all('sys_group_users') as $group) {

    print_r($group);
    if ($group->level=='admin') {
  $in =  $db->query("insert into sys_menu_role(id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
  values (".$last_id.",'admin','Y','Y','Y','Y','Y')");

    } else {
     $in =   $db->query("insert into sys_menu_role(id_menu,group_level,read_act,insert_act,update_act,delete_act)
  values (".$last_id.",'".$group->level."','N','N','N','N')");
    }
  }

?>
