<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("matauang","kd_valas",uri_segment(3));
    include "mata_uang_detail.php";
    break;
    default:
    include "mata_uang_view.php";
    break;
}

?>