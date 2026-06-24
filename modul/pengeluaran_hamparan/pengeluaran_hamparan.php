<?php
switch (uri_segment(2)) {
    case "tambah":
      echo "<section class='content'><div class='alert alert-info'><strong>Form legacy dinonaktifkan.</strong> Posting Goods Issue for Delivery sekarang dilakukan dari workbench utama agar stock layer, jurnal, dan trace dokumen BC selalu konsisten.</div><a href='".base_index()."pengeluaran-hamparan' class='btn btn-primary'><i class='fa fa-arrow-left'></i> Buka Goods Issue for Delivery Workbench</a></section>";
    break;
  case "edit":
    echo "<section class='content'><div class='alert alert-warning'><strong>Edit legacy dinonaktifkan.</strong> Goods Issue yang sudah posted harus dibalik dengan reversal, lalu posting ulang dari workbench agar FIFO stock, material document, jurnal, dan trace BC tetap audit-safe.</div><a href='".base_index()."pengeluaran-hamparan' class='btn btn-primary'><i class='fa fa-arrow-left'></i> Kembali ke Workbench</a></section>";

    break;
    case "detail":
    $data_edit = $db->fetch_single_row("pengeluaran","no_sj",uri_segment(3));
    include "pengeluaran_hamparan_detail.php";
    break;
    default:
    include dirname(__DIR__)."/goods_issue_delivery/goods_issue_delivery_view.php";
    break;
}

?>
