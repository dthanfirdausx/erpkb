<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("h_pegawai","idPegawai",uri_segment(3));
    include "data_pegawai_detail.php";
    break;
    default:
    include "data_pegawai_view.php";
    break;
}

?>