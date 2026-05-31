<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("pemasok","kode_pemasok",uri_segment(3));
    include "pemasok_detail.php";
    break;
    default:
    include "pemasok_view.php";
    break;
}

?>