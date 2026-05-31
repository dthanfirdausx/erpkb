<?php
include "../../inc/config.php";

$columns = array(
    'surat_jalan.no_surat_jalan',
    'surat_jalan.tgl_surat_jalan',
    'surat_jalan.no_sales_order',
    'penerima.nama',
    'surat_jalan.sopir',
    'surat_jalan.status', 
    'surat_jalan.id',
);

$datatable->set_numbering_status(1);
$datatable->set_order_by("surat_jalan.tgl_surat_jalan");
$datatable->set_order_type("desc");

$query = $datatable->get_custom("SELECT surat_jalan.id, surat_jalan.no_surat_jalan, 
                                 surat_jalan.tgl_surat_jalan, surat_jalan.no_sales_order,
                                 surat_jalan.no_po, penerima.nama, surat_jalan.sopir, 
                                 surat_jalan.status
                                 FROM surat_jalan 
                                 LEFT JOIN penerima ON surat_jalan.kode_penerima = penerima.kode_penerima", $columns);

$data = array();
$i=1;
foreach ($query as $value) {
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
    $ResultData[] = $value->no_surat_jalan;
    $ResultData[] = date('d/m/Y', strtotime($value->tgl_surat_jalan));
    $ResultData[] = $value->no_sales_order;
    $ResultData[] = $value->no_po;
    $ResultData[] = $value->nama;
    $ResultData[] = $value->sopir;
    
    // Status dengan badge
    $badge_class = '';
    switch($value->status) {
        case 'draft': $badge_class = 'bg-gray'; break;
        case 'dikirim': $badge_class = 'bg-blue'; break;
        case 'diterima': $badge_class = 'bg-green'; break;
        case 'dibatalkan': $badge_class = 'bg-red'; break;
    }
    $ResultData[] = '<span class="badge '.$badge_class.'">'.strtoupper($value->status).'</span>';
    $ResultData[] = $value->status;
    $ResultData[] = $value->id;

    $data[] = $ResultData;
    $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>