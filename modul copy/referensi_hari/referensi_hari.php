<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("h_hari","hari_id",uri_segment(3));
    include "referensi_hari_detail.php";
    break;
    default:
    include "referensi_hari_view.php";
    break;
}

?>