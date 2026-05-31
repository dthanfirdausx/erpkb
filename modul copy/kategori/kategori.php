<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("kategori","kd_kategori",uri_segment(3));
    include "kategori_detail.php";
    break;
    default:
    include "kategori_view.php";
    break;
}

?>