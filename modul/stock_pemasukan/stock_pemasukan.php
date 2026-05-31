<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("vtotalstockpemasukan","",uri_segment(3));
    include "stock_pemasukan_detail.php";
    break;
    default:
    include "stock_pemasukan_view.php";
    break;
}

?>