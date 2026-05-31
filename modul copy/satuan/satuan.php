<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("satuan","kode",uri_segment(3));
    include "satuan_detail.php";
    break;
    default:
    include "satuan_view.php";
    break;
}

?>