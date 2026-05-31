<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("barang","id",uri_segment(3));
    include "data_barang_detail.php";
    break;
    default:
    include "data_barang_view.php";
    break;
}

?>