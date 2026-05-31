<?php
session_check_adm();
switch (uri_segment(2)) {
	default:
		include "menu_management_view.php";
		break;
}

?>
