<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("catatan","kd_catatan",uri_segment(3));
    include "catatan_kirim_detail.php";
    break;
    default:
    include "catatan_kirim_view.php";
    break;
}

?>