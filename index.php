<?php
session_start();

include "inc/config.php";
require_once "inc/url.php";

if (isset($_SESSION['login'])) {


//call header file
include  "header.php"; 

//switch for static menu
switch (uri_segment(1)) {
	case '':
		include "modul/home/home.php";
		break;
	//begin case system menu
	//show only if user is admin
	case 'page-service':
	include "system/service/service.php";
	break;
	case 'excel-generator':
	include "system/excel/service.php";
	break;
	case 'service-permission':
	include "modul/service_permission/service_permission.php";
	break;
	case 'page':
		include "system/page/page.php";
		break;
	case 'group-permission':
		include "modul/menu_management/menu_management.php";
	break;
	case 'akses-prodi':
		include "modul/akses_prodi/akses_prodi.php";
	break;
	case 'group-user':
		include "modul/group_user/group_user.php";
		break;
	// case 'user-management':
	// 	include "modul/user_management/user_management.php";
	// 	break;
	//end case system menu
	case 'change-password':
		include "modul/change_password/change_pass.php";
		break;
	case 'profil':
		include "modul/profil/profil.php";
		break;

	/*default:
		include "modul/home/home.php";
		break;*/
}

     //dynamic menu from database
	//jika url yang di dipanggil ada di role user, include page
	foreach ($db->fetch_all('sys_menu') as $isi) {
		if (in_array($isi->url, $role_user)) {

               		if (uri_segment(1)!='' && uri_segment(1)==$isi->url) {


					include "modul/".$isi->nav_act."/".$isi->nav_act.".php";
					}
               }
	}


include "footer.php";

} else {
	redirect(base_admin()."login.php");
}
?>
