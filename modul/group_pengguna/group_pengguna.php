<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("sys_group_users","id",uri_segment(3));
    include "group_pengguna_detail.php";
    break;
    default:
    include "group_pengguna_view.php";
    break;
}

?>