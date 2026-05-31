<?php
include "../../inc/config.php";
echo "<pre>";
print_r($_POST);


$main_table = $_POST['table'];
$primary_key = $_POST['primary_key'];

$token_type = $_POST['token_type'];
$format_data = $_POST['output_type'];

$service_name = strtolower(str_replace(" ", "-", $_POST['page_name']));


$column_dipilih = $_POST['dipilih1'];

if (isset($_POST['from_checkbox_normal'])) {
    $key_should_be_removed = array_keys($_POST['from_checkbox_normal']);

    foreach ($key_should_be_removed as $dihapus) {
        unset($column_dipilih[$main_table.".".$dihapus]);
    }


}

if (!is_dir('../../../api/services/'.$service_name)) {
  mkdir('../../../api/services/'.$service_name);
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
    $assoc_col_new_col = array_combine($original_column_name, $new_column_result_data);
}




foreach ($assoc_col_new_col as $key => $value) {
	$alias_name[$key] = $_POST['alias1'][$key];
}


foreach ($alias_name as $key => $value) {
	$alias_name_final[$value] = $assoc_col_new_col[$key];
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
//for alias
foreach (array_filter($_POST['alias']) as $key => $value) {
  //check if sama dengan array yang ada di dipilih
  if (in_array($key, array_keys($pilih)) ) {
    $alias[$key]=$value;
    if($_POST['allownull']=='on') {
      $allownull[$key]='yes';
    } else {
      $allownull[$key]='no';
    }
    
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


$tes = array_merge_recursive($alias,$label,$type,$allownull,$required,$from,$from_checkbox,$on_name_checkbox,$on_value,$on_name,$allowed,$width,$height,$yes_val,$no_val);

$top_post_images = '';
$delete_file_images = '';
$upload_post_images = '';
$data_put_updates = '';
$upload_update_images = '';
$valid_array = '
		$validation = array(';
$data_posts = '
		$data = array(';
$data_updates = '
		$data = array(';
foreach ($tes as $key => $value) {
  $post_component = '
    <tr class="row-even">
    <td>'.$value[0].'</td>
    <td>'.$value[2].'</td>
    <td>'.$value[1].'</td>
    </tr>';

  if ($value[2]=='file') {
        $delete_file_image = '
          if(file_exists(SITE_ROOT."'.$_POST['path_file'][$key].'/$single_data->'.$key.'")) {
            unlink(SITE_ROOT."'.$_POST['path_file'][$key].'/$single_data->'.$key.'");
          }';
        $upload_post_image = '
              if (isset($_FILES["'.$value[0].'"]["tmp_name"]) && $nama_file=="no") {
                 $upload = $db->upload_file($_FILES["'.$value[0].'"]["tmp_name"],SITE_ROOT."'.$_POST['path_file'][$key].'/",$'.$key.'_name);

              }';
        $upload_update_image = '
            if (isset($_FILES["'.$value[0].'"]["tmp_name"])) {
               $upload = $db->upload_file($_FILES["'.$value[0].'"]["tmp_name"],SITE_ROOT."'.$_POST['path_file'][$key].'/",$'.$key.'_name);

            }';
        $top_post_image = '
          $'.$key.'_value = "";
          $'.$key.'_name = "";
          $'.$key.' = "'.$value[3].'";
          if (isset($_FILES["'.$value[0].'"]["tmp_name"]) && $'.$key.'=="no") {
            $'.$key.'_value = $_FILES["'.$value[0].'"]["tmp_name"];
            $'.$key.'_name = $db->unique_name($_FILES["'.$value[0].'"]["name"]);
          }
    ';
    $data_put_update = '
        if (isset($_FILES["'.$value[0].'"]["name"])) {
          $'.$value[0].'_validation = array(
            "'.$value[0].'" => array(
              "type" => "file",
              "alias" => "'.$value[0].'",
              "value" => $_FILES["'.$value[0].'"]["name"],
              "allownull" => "'.$value[3].'",
              "extention" => "'.$value[4].'"
        ));
        $'.$key.'_name = $db->unique_name(trim($_FILES["'.$value[0].'"]["name"]));
        $'.$value[0].'_data =  array(
            "'.$key.'" => $'.$key.'_name
        );
        $validation = array_merge($validation,$'.$value[0].'_validation);
        $data = array_merge($data,$'.$value[0].'_data);
        }';
            $data_post = '
            "'.$key.'" => $'.$key.'_name,';
  	$data_update = '
  	"'.$key.'" => $_FILES["'.$value[0].'"]["name"],';
  	
    $validation_array =
    '
		"'.$key.'" => array(
              "type" => "file",
              "alias" => "'.$value[0].'",
              "value" => $_FILES["'.$value[0].'"]["name"],
              "allownull" => "'.$value[3].'",
              "extention" => "'.$value[4].'"
		),';
  } elseif ($value[2]=='image') {
        $delete_file_image = '
          if(file_exists(SITE_ROOT."'.$_POST['path_foto'][$key].'/$single_data->'.$key.'")) {
            unlink(SITE_ROOT."'.$_POST['path_foto'][$key].'/$single_data->'.$key.'");
          }';
        $upload_post_image = '
          if (isset($_FILES["'.$value[0].'"]["tmp_name"]) && $'.$key.'=="no") {
             $upload = $db->upload_image_custom($_FILES["'.$value[0].'"]["type"],$_FILES["'.$value[0].'"]["tmp_name"],SITE_ROOT."'.$_POST['path_foto'][$key].'/",$'.$key.'_name,1200);

          }';
                $upload_update_image = '
          if (isset($_FILES["'.$value[0].'"]["tmp_name"])) {
             $upload = $db->upload_image_custom(trim($_FILES["'.$value[0].'"]["type"]),$_FILES["'.$value[0].'"]["tmp_name"],SITE_ROOT."'.$_POST['path_foto'][$key].'/",$'.$key.'_name,1200);

          }';
    $top_post_image = '
          $'.$key.'_value = "";
          $'.$key.'_name = "";
          $'.$key.' = "'.$value[3].'";
          if (isset($_FILES["'.$value[0].'"]["tmp_name"]) && $'.$key.'=="no") {
            $'.$key.'_value = $_FILES["'.$value[0].'"]["tmp_name"];
            $'.$key.'_name = $db->unique_name(trim($_FILES["'.$value[0].'"]["name"]));
          }
    ';
    $data_put_update = '
        if (isset($_FILES["'.$value[0].'"]["name"])) {
          $'.$value[0].'_validation = array(
            "'.$value[0].'" => array(
              "type" => "image",
              "alias" => "'.$value[0].'",
              "value" => $_FILES["'.$value[0].'"]["tmp_name"],
              "allownull" => "'.$value[3].'",
              "path_foto" => "'.$_POST['path_foto'][$key].'",
        ));

        $'.$key.'_name = $db->unique_name(trim($_FILES["'.$value[0].'"]["name"]));

        $'.$value[0].'_data =  array(
            "'.$key.'" =>  $'.$key.'_name
        );
        $validation = array_merge($validation,$'.$value[0].'_validation);
        $data = array_merge($data,$'.$value[0].'_data);
        }';
            $data_post = '
            "'.$key.'" => $'.$key.'_name,';
    $data_update = '
  	"'.$key.'" => $_FILES["'.$value[0].'"]["tmp_name"],';
    
    $validation_array =
    '
		"'.$key.'" => array(
              "type" => "image",
              "alias" => "'.$value[0].'",
              "value" => $'.$key.'_value,
              "allownull" => "'.$value[3].'",
              "path_foto" => "'.$_POST['path_foto'][$key].'",
		),';
  } else {
    $data_put_update = '
        if (isset($_PUT["'.$value[0].'"])) {
          $'.$value[0].'_validation = array(
            "'.$value[0].'" => array(
              "type" => "'.$value[2].'",
              "alias" => "'.$value[0].'",
              "value" => $_PUT["'.$value[0].'"],
              "allownull" => "'.$value[3].'",
        ));
        $'.$value[0].'_data =  array(
            "'.$key.'" => $_PUT["'.$value[0].'"]
        );
        $validation = array_merge($validation,$'.$value[0].'_validation);
        $data = array_merge($data,$'.$value[0].'_data);
        }';
            $data_post = '
            "'.$key.'" => $request->post("'.$value[0].'"),';
          $data_update = '
      "'.$key.'" => $request->put("'.$value[0].'"),';
    
  	    $validation_array =
    '
		"'.$key.'" => array(
              "type" => "'.$value[2].'",
              "alias" => "'.$value[0].'",
              "value" => $request->post("'.$value[0].'"),
              "allownull" => "'.$value[3].'",
		),';
  		
  }
$upload_post_images .=$upload_post_image;
$delete_file_images .=$delete_file_image;
$upload_update_images .= $upload_update_image;
$post_components .=$post_component;
$data_put_updates .=$data_put_update;
$valid_array .=$validation_array;
$data_posts .=$data_post;
$top_post_images .=$top_post_image;
$data_updates .=$data_update;
}
$data_posts .='
		);';
$data_updates .='
		);';
$valid_array .= '
		);';

//access data for view
$view_data_access = '
		$row = array('; 
$result_request ="
      {";
foreach ($alias_name_final as $key => $value) {
		$view_data_access.= "
				'$key' => ".'$dt->'."$value,";
    $request_view[$key] = $value;
    $result_request .=" 
        $key:'$key data',";
}
$result_request .=" 
      },";
$view_data_access.='
				);';


  if (count($_POST['join_cond'])>0) {
    for ($i=0; $i <count($_POST['join_cond']) ; $i++) {
      $join1.= " ".$_POST['join_cond'][$i]." join ".$_POST['join_with'][$i]." on ".$_POST['join_on_first'][$i]."=".$_POST['join_on_next'][$i];
    }
    $select_query = 'select '.$column_head_query.$query_checkbox.' from '.$main_table.' '.$join1;
   	$select_query_detail = 'select '.$column_head_query.$query_checkbox.' from '.$main_table.' '.$join1.' where '.$primary_key.'=?';
   
      
  } else {
  	$select_query = 'select '.$column_head_query.$query_checkbox.' from '.$main_table;
  	$select_query_detail = 'select '.$column_head_query.$query_checkbox.' from '.$main_table.' where '.$primary_key.'=?';

  }

$query = $db->query($select_query.' limit 0,3');

if ($query->rowCount()>0) {
  

foreach ($query as $dt) {
    $query_result[] = array_values($db->convert_obj_to_array($dt));
}

foreach ($query_result as $key => $value) {
    $final_query[] = array_combine(array_keys($request_view), $value);
}

  //view and search
foreach ($final_query as $key => $value) {
      $data_req .='
        {';

     // $data_req = implode(",", $value);
     $panjang = count($value)-1;
     $i=0;
    $single_req = $value;
    foreach ($value as $key => $value) {
        if ($i==$panjang) {
                    $data_req .= "
          $key:\"$value\"";
        } else {
                  $data_req .= "
          $key:\"$value\",";
        }

        $i++;
    }

    $data_req .='
        },';
}
    //for doc single data
     $panjang = count($single_req)-1;
     $i=0;
    foreach ($single_req as $key => $value) {
        if ($i==$panjang) {
                    $view_single_req .= "
          $key:\"$value\"";
        } else {
                    $view_single_req .= "
          $key:\"$value\",";
        }
        $i++;
    }

} else {
  $data_req = $result_request;
}

include "template.php";


//write form add
$db->buat_file('../../../api/services/'.$service_name.'/'.$service_name.'.php',$service_template);

include "doc_template.php";

$db->buat_file('../../../api/services/'.$service_name.'/doc.php',$doc_template);

if ($_POST['create_auth']=='on') {
	$create_auth = 'Y';
} else {
	$create_auth = 'N';
}

if ($_POST['read_auth']=='on') {
	$read_auth = 'Y';
} else {
	$read_auth = 'N';
}

if ($_POST['update_auth']=='on') {
	$update_auth = 'Y';
} else {
	$update_auth = 'N';
}

if ($_POST['delete_auth']=='on') {
	$delete_auth = 'Y';
} else {
	$delete_auth = 'N';
}



$data = array(
	'nav_act'     => $service_name,
	'page_name'   => $service_name,
	'url' 		  => $service_name,
	'main_table'  => $main_table,
	'create_auth' => $create_auth,
	'read_auth'   => $read_auth,
	'update_auth' => $update_auth,
	'delete_auth' => $delete_auth
	);
$db->insert('sys_services',$data);

$id_service = $db->last_insert_id();

$get_all_user = $db->fetch_all('sys_users');
foreach ($get_all_user as $user) {
    if ($user->group_level=='admin') {
    $data = array(
      'id_service' => $id_service,
      'id_user' => $user->id,
      'type_token' => 'permanent',
      'read_access' => "{access:1,token:0}",
      'create_access' => "{access:1,token:0}",
      'update_access' => "{access:1,token:0}",
      'delete_access' => "{access:1,token:0}",
      );

    } else {
       $data = array(
      'id_service' => $id_service,
      'id_user' => $user->id,
      'type_token' => 'permanent',
      'read_access' => "{access:0,token:0}",
      'create_access' => "{access:0,token:0}",
      'update_access' => "{access:0,token:0}",
      'delete_access' => "{access:0,token:0}",
      );
     
    }
    $db->insert('sys_token',$data);
}

/*
if ($output_type=='temporary') {
    $token_data = array(
      'id_service' => $id_service,
      'type_token' => $token_type,
      'format_data' => $format_data,
      'token_exp' => $_POST['token_expiration']
    );
} else {
    $token_data = array(
    'id_service' => $id_service,
    'type_token' => $token_type,
    'format_data' => $format_data,
    );
}

print_r($token_data);

$db->insert('sys_token',$token_data);
*/

echo $db->getErrorMessage();
