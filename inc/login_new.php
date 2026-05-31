<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
include "config.php";

 
$json_response = array();
 
//i only receive ajax request :D
if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

		$data = array(
		'username'=>$_POST['username'],
		'password'=>md5($_POST['password'])
		);
		$check = $db->check_exist('sys_users',$data);
		if ($check==true) {
		$dt=$db->fetch_single_row('sys_users','username',$_POST['username']);
		$group_dt=$db->fetch_single_row('sys_group_users','id',$dt->group_level);
			$_SESSION['group_level']=$group_dt->level;
			$_SESSION['id_user']=$dt->id;
			$_SESSION['login']=1;
			$_SESSION['username'] = $dt->username;
			$_SESSION['IKB4_status_UserName'] = $dt->username;
			$_SESSION['nama'] = $dt->first_name." ".$dt->last_name;
			$_SESSION['level']=$dt->group_level;			
			$status['status'] = "good";
			  simpan_log("Login ke aplikasi Inventory",$_SESSION['username']);
			  echo "1";
		} else {
			echo "0";
			// $status['status'] = "bad";
			// $status['error_log'] = $db->getErrorMessage();
		}

} else { 
	//hei , don't ever try if you're not ajax request, because you gonna die
	//$status['status'] = "go out dude";
	echo "0";
}


// array_push($json_response, $status);
// echo json_encode($json_response);

?>
