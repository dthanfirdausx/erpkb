<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("barang","id",uri_segment(3));
    include "barang_detail.php";
    break;
    default:
   // var_dump($lang);
    include "barang_view.php";
    break;
}

?>