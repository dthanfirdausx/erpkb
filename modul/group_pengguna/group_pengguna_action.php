<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {
  case "in":
    
    	$level = str_replace(" ", "_", strtolower($_POST['level_name']));
  	$data = array(
  	    "level"=>$level,
  	    "level_name" => $_POST['level_name'],
  	    "deskripsi"=>$_POST["deskripsi"]
  	    );

		$in = $db->insert("sys_group_users",$data);

      		$db->query("insert into sys_menu_role(id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
select sys_menu.id,'".$level."','N','N','N','N','N' from sys_menu");
	
  
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("sys_group_users","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("sys_group_users","id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
      "level_name" => $_POST["level_name"],
      "deskripsi" => $_POST["deskripsi"],
   );
   
   
   

    
    
    $up = $db->update("sys_group_users",$data,"id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>