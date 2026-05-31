<?php
include "../../inc/config.php";
include "../../inc/lib/PHPExcel.php";

include "fungsi.php";

$main_table = $_POST['table'];


//$service_name = strtolower(str_replace(" ", "-", $_POST['page_name']));
$service_name = $_POST['page_name'];

$column_dipilih = $_POST['dipilih'];

if (isset($_POST['from_checkbox_normal'])) {
    $key_should_be_removed = array_keys($_POST['from_checkbox_normal']);

    foreach ($key_should_be_removed as $dihapus) {
        unset($column_dipilih[$main_table.".".$dihapus]);
    }


}

/*if (!is_dir('../../../api/services/'.$service_name)) {
  mkdir('../../../api/services/'.$service_name);
}*/

$pilih=array();
$label=array();
$contoh = array();
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


//for contoh,
foreach (array_filter($_POST['contoh']) as $key => $value) {
  //check if sama dengan array yang ada di dipilih
  if (in_array($key, array_keys($pilih)) ) {
    $contoh[$key]=$value;
  }

}

$allow_null = array();
foreach (array_filter($_POST['alias']) as $key => $value) {
  //check if sama dengan array yang ada di dipilih
  if (in_array($key, array_keys($pilih)) ) {
    $alias[$key]=$value;
    $allow_null[$key] = 'no';
  }

}

if (isset($_POST['allownull'])) {
  foreach ($_POST['allownull'] as $key => $value) {
    $allow_null[$key] = 'yes';
  }

}




//print_r($_POST);
$isset_column = "";
$filter_column = "";
$var_column = "";
$where_column = "";
$filter_view= array();
$dtable_filter = "";

$group_filter="";
$iterator = 0;
$label_filter = $_POST['label_name'];
foreach ($_POST['var_name'] as $value) {
  if ($iterator==0) {
      $isset_column = $value;
    }
    $group_filter.='
              <div class="form-group">
                    <label for="'.$label_filter[$iterator].'" class="control-label col-lg-2">'.$label_filter[$iterator].'</label>
                    <div class="col-lg-5">
                    <select id="'.$value.'" name="'.$value.'" data-placeholder="Pilih '.$label_filter[$iterator].' ..." class="form-control chzn-select" tabindex="2" required="">
                    <?php
                    looping_prodi();
                    ?>
                    </select>
                    </div>
              </div><!-- /.form-group -->
';
  $filter_column.= '
      if ($_POST[\''.$value.'\']!=\'all\') {
        $'.$value.' = \' and change_your_column_here="\'.$_POST[\''.$value.'\'].\'"\';
      }
  ';
  $var_column.='$'.$value.' = "";
  ';
  $dtable_filter.= 'd.'.$value.' = $("#'.$value.'").val();
  ';
  $where_column.= '$'.$value.' ';
$iterator++;
}


$tes = array_merge_recursive($alias,$label,$contoh,$allow_null,$required,$from,$from_checkbox,$on_name_checkbox,$on_value,$on_name,$allowed,$width,$height,$yes_val,$no_val);


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

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);

$letter = createColumnsArray('AZ');
$counter = 0;
//for handling import variable
$loop_var = "";
$query = array();

$letter_col_wajib = array();
$letter_col_opt = array();
$col_width = array();
$header_label = array();
$key_obj = array();


foreach ($tes as $key => $value) {


$query[]= $key;

$key_obj[] = '$key->'.$key;

//komentar cell 
  if (count($value)==4) {
          $objPHPExcel->getActiveSheet()
        ->getComment($letter[$counter].'1')
        ->setAuthor('Sukamedia');
        $objPHPExcel->getActiveSheet()->getComment($letter[$counter]."1")->setWidth("400px");
        $objPHPExcel->getActiveSheet()->getComment($letter[$counter]."1")->setHeight("250px");
        $objPHPExcel->getActiveSheet()
        ->getComment($letter[$counter].'1')
        ->getText()->createTextRun("$value[1]");

        if ($value[3]=='yes') {
           cellColor($letter[$counter].'1', '00ff00');
           $letter_col_opt[] = $letter[$counter]."1";
        } else {
          cellColor($letter[$counter].'1', 'ff0000');
          $letter_col_wajib[] = $letter[$counter]."1";
        }
    // add sample data
    $objPHPExcel->getActiveSheet()->setCellValue($letter[$counter]."2", "$value[2]");

  } else {
    //header color
    if ($value[2]=='yes') {
      //if optional set color to gree
      $letter_col_opt[] = ''.$letter[$counter]."1";
      cellColor($letter[$counter].'1', '00ff00');
    } else {
      //else set color to red
      cellColor($letter[$counter].'1', 'ff0000');
      $letter_col_wajib[] = $letter[$counter]."1";
    }
        // add sample data
    $objPHPExcel->getActiveSheet()->setCellValue($letter[$counter]."2", "$value[1]");
   
  }
  // Add column headers
    $objPHPExcel->getActiveSheet()->setCellValue($letter[$counter]."1", "$value[0]");
    //set width column
  $objPHPExcel->getActiveSheet()->getColumnDimension($letter[$counter])->setWidth(strlen($value[0])+2);
  $col_width[] = ($counter+1).' => '.(strlen($value[0])+2);
  $header_label[] = '"'.$value[0].'" => "string"';

$loop_var.= 'preg_replace( \'/[^[:print:]]/\', \'\',filter_var($val['.$counter.'], FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH)).\'","\'.'."\n";
    $counter++;

}
//rec object
$rec_obj = implode(",\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t", $key_obj);
//$col_width = array_filter(array_merge(array(0), $col_width));
$width_column = implode(",\n", $col_width);

$head_label = implode(",\n", $header_label);
if (!empty($letter_col_wajib)) {
    $letter_wajib = "'" . implode("','", $letter_col_wajib) . "'";

    $column_wajib = '
            array(
                \'fill\' => array(
                    \'color\' => \'ff0000\'
                    ),
                \'cells\' => array(
                   '.$letter_wajib.'
                    ),
                \'border\' => array(
                    \'style\' => \'thin\',
                    \'color\' => \'000000\'
                    ),
                \'verticalAlign\' => \'center\',
                \'horizontalAlign\' => \'center\',
            ),';
}

$column_optional = "";
if (!empty($letter_col_opt)) {
    $letter_optional = "'" . implode("','", $letter_col_opt) . "'";
    $column_optional = '
            array(
                \'fill\' => array(
                    \'color\' => \'00ff00\'
                    ),
                \'cells\' => array(
                  '.$letter_optional.'
                    ),
                \'border\' => array(
                    \'style\' => \'thin\',
                    \'color\' => \'000000\'
                    ),
                \'verticalAlign\' => \'center\',
                \'horizontalAlign\' => \'center\',
            ),';
}



$query = implode(",", $query);

$counter = $counter-1;

$loop_var.='filter_var($val['.$counter.'], FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH).\'"),\';';
//border header 
 $styleArray = array(
      'borders' => array(
          'allborders' => array(
              'style' => PHPExcel_Style_Border::BORDER_THIN
          )
      ),
      'font' => array(
          'bold' => true,
      )
  );

$objPHPExcel->getActiveSheet()->getStyle("A1:".$letter[$counter].'1')->applyFromArray($styleArray);

//aligmnet
$objPHPExcel->getActiveSheet()->getStyle('A1:'.$letter[$counter].'1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(28);

include "template.php";
//include "write_excel.php";

//write form add
//$db->buat_file('result/'.$service_name.'/'.$service_name.'.php',$import_page);

/*$db->buat_file('result/import.php',$import_page);
$db->buat_file('result/filter.php',$filter_page);
$db->buat_file('result/download_data.php',$download_data);


exit();*/

// Set document properties
$objPHPExcel->getProperties()->setCreator("Me")->setLastModifiedBy("Me")->setTitle("My Excel Sheet")->setSubject("My Excel Sheet")->setDescription("Excel Sheet")->setKeywords("Excel Sheet")->setCategory("Me");

// Set worksheet title
$objPHPExcel->getActiveSheet()->setTitle('Sample Data');

//save the file to the server (Excel2007)
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

$objWriter->save( 'result/'.$service_name.'.xlsx');

    $dir_to_download = $db->get_dir_excel(getcwd());
    $file_create = array($import_page => 'import.php',$filter_page => 'filter.php',$download_data => 'download_data.php');
   $db->downloadfolder_excel($dir_to_download,$file_create,$service_name);
   //$db->downloadfolder($dir_to_download,$import_page,'tes');
