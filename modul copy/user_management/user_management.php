<?php
session_check_adm();
switch (uri_segment(2)) {
	case "tambah":
         include "user_management_add.php";
		break;
  case 'reset':
    $data_edit = $db->fetch_single_row("sys_users","id",uri_segment(3));
        include "user_reset.php";
    break;
	case "edit":
		$data_edit = $db->fetch_single_row("sys_users","id",uri_segment(3));
		   include "user_management_edit.php";
		break;
      case "detail":
    $data_edit = $db->fetch_single_row("sys_users","id",uri_segment(3));
    include "user_management_detail.php";
    break;
	default:
	// echo "string";
	// die();
		include "user_management_view.php";
		break;
}

?>
