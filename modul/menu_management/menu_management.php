<?php
if (!isset($_SESSION['group_level']) || !in_array($_SESSION['group_level'], array('admin', 'system_administrator'), true)) {
    exit('permission denied');
}
switch (uri_segment(2)) {
	default:
		include "menu_management_view.php";
		break;
}

?>
