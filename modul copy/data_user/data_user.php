<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("sys_users","id",uri_segment(3));
    include "data_user_detail.php";
    break;
    default:
    include "data_user_view.php";
    break;
}

?>