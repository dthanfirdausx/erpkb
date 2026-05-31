<?php
session_check_adm();
switch (uri_segment(2)) {
	case 'import':
		include "import.php";
		break;
	case 'tambah':
		include "page_add.php";
		break;
	case 'edit':
		$data_edit = $db->fetch_single_row('sys_menu','id',uri_segment(3));
		include "page_edit.php";
		break;
	case 'icon':
		include "icon.php";
		break;
	default:
		include "page_view.php";
		break;
}

?>
