<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("h_shift","shiftId",uri_segment(3));
    include "management_shift_detail.php";
    break;
    default:
    include "management_shift_view.php";
    break;
}

?>