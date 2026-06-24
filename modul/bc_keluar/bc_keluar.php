<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("jenisbckeluar","kode",uri_segment(3));
    include "bc_keluar_detail.php";
    break;
    default:
    include "bc_keluar_view.php";
    break;
}

?>
