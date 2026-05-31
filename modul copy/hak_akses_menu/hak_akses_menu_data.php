<?php
include "../../inc/config.php";

$columns = array(
    'sys_menu_role.id_menu',
    'sys_menu_role.group_level',
    'sys_menu_role.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('group_level','sys_menu_role.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("sys_menu_role.id");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by sys_menu_role.id";

  $query = $datatable->get_custom("select sys_menu_role.id_menu,sys_menu_role.group_level,sys_menu_role.id from sys_menu_role",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->id_menu;
    $ResultData[] = $value->group_level;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>