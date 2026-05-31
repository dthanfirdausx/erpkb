<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("dept","kd_dept",uri_segment(3));
    include "department_detail.php";
    break;
    default:
    include "department_view.php";
    break;
}

?>