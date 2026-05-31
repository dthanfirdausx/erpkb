<?php
error_reporting(0);
include "../../inc/config.php";

$columns = array(
    'a.no_jurnal',
    'a.tgl_jurnal',
    'a.no_bukti',
    'total_debet',
    'total_kredit',
    'a.id',
);

$datatable->set_numbering_status(1);

$datatable->set_order_by("a.id");

$datatable->set_order_type("desc");

$where = "";

if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {

    $start = $_POST['start_date'];
    $end   = $_POST['end_date'];

    $where .= " AND a.tgl_jurnal BETWEEN '$start' AND '$end' ";
 
}

$query = $datatable->get_custom("

SELECT 
    a.id,
    a.no_jurnal,
    a.tgl_jurnal,
    a.no_bukti,

    IFNULL(SUM(b.debet),0) as total_debet,
    IFNULL(SUM(b.kredit),0) as total_kredit

FROM jurnal_header a

LEFT JOIN jurnal_detail b
    ON b.id_header = a.id where 1=1 
$where
GROUP BY a.id

", $columns);

$data = array();

$i = 1;

foreach ($query as $value) {

    $ResultData = array();

    $ResultData[] = $datatable->number($i);

    $ResultData[] = '

    <a href="javascript:void(0)"
       class="detail_jurnal"
       data-id="'.$value->id.'">

       '.$value->no_jurnal.'

    </a>

    ';

    $ResultData[] = $value->tgl_jurnal;

    $ResultData[] = $value->no_bukti;

    $ResultData[] = number_format($value->total_debet,2);

    $ResultData[] = number_format($value->total_kredit,2);

    $ResultData[] = $value->id;

    $data[] = $ResultData;

    $i++;
}

$datatable->set_data($data);

$datatable->create_data();
?>