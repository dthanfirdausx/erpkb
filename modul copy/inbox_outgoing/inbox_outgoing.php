<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("outgoing_terima","id",uri_segment(3));
    include "inbox_outgoing_detail.php";
    break;
    default:
    include "inbox_outgoing_view.php";
    break;
}

?>