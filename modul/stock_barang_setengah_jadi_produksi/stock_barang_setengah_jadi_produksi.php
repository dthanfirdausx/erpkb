<?php
switch (uri_segment(2)) {
    case "tambah":
      echo '<div class="alert alert-warning">Input manual Stock Barang Setengah Jadi Produksi dikunci. Gunakan GR from Production Order.</div>';
    break;
  case "edit":
    echo '<div class="alert alert-warning">Edit manual Stock Barang Setengah Jadi Produksi dikunci. Gunakan GR from Production Order.</div>';
    break;
    case "detail":
    include "stock_barang_setengah_jadi_produksi_detail.php";
    break;
    default:
    include "stock_barang_setengah_jadi_produksi_view.php";
    break;
}

?>
