<?php
switch (uri_segment(2)) {
    case "detail":
    $data_edit = $db->fetch_single_row("vtotalstockprodbb","",uri_segment(3));
    include "stock_bahan_baku_produksi_detail.php";
    break;
    default:
    include "stock_bahan_baku_produksi_view.php";
    break;
}

?>