<?php
include "../../inc/config.php";

$columns = array(
    'sys_users.first_name',
    'sys_users.username',
    'sys_users.email',
    'sys_group_users.level',
    'sys_users.id',
  );

  //if you want to exclude column for searching, put columns name in array
  //$new_table->disable_search = array('aktif','sys_users.id');
  
  //set numbering is true
  $datatable->set_numbering_status(1);

  //set order by column
  $datatable->set_order_by("sys_users.id");

  //set order by type
  $datatable->set_order_type("desc");

  //set group by column
  //$new_table->group_by = "group by sys_users.id";

  $query = $datatable->get_custom("select sys_users.first_name,sys_users.username,sys_users.email,sys_group_users.level,sys_users.id from sys_users inner join sys_group_users on sys_users.group_level=sys_group_users.id where sys_group_users.level<>'employee_self_service'",$columns);

  //buat inisialisasi array data
  $data = array();

  $i=1;
  foreach ($query as $value) {

    //array data
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
  
    $ResultData[] = $value->first_name;
    $ResultData[] = $value->username;
    $ResultData[] = $value->email;
    $ResultData[] = $value->level;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
  }

//set data
$datatable->set_data($data);
//create our json
$datatable->create_data();

?>
