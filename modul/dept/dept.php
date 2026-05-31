<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("dept","kd_dept",uri_segment(3));
    include "dept_detail.php";
    break;
    default:
    include "dept_view.php";
    break;
}

?>