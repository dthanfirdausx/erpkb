<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("jenisbcmasuk","kode",uri_segment(3));
    include "bc_masuk_detail.php";
    break;
    default:
    include "bc_masuk_view.php";
    break;
}

?>
