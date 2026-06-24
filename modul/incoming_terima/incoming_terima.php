<?php
switch (uri_segment(2)) {
    case "tambah":
      echo "<section class='content'><div class='alert alert-info'><strong>Input manual dinonaktifkan.</strong> GR from Production Order harus diposting dari dokumen transfer produksi outstanding di workbench.</div><a href='".base_index()."incoming-terima' class='btn btn-default'><i class='fa fa-arrow-left'></i> Kembali</a></section>";
    break;
  case "edit":
    echo "<section class='content'><div class='alert alert-info'><strong>Edit manual tidak tersedia.</strong> Dokumen material yang sudah diposting harus dikoreksi melalui proses reversal, bukan edit langsung.</div><a href='".base_index()."incoming-terima' class='btn btn-default'><i class='fa fa-arrow-left'></i> Kembali</a></section>";

    break;
    case "detail":
    error_reporting(0);
    $data_edit = $db->fetch_single_row("transfer","id_transfer",uri_segment(3));
    include "incoming_terima_detail.php";
    break;
    default:
    include "incoming_terima_view.php"; 
    break;
}

?>
