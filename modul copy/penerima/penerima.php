<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("penerima","kode_penerima",uri_segment(3));
    include "penerima_detail.php";
    break;
    default:
    include "penerima_view.php";
    break;
}

?>