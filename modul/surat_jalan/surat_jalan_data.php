<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
include "../../inc/config.php";

function sj_data_input($key, $default = '') {
    return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default;
}

function sj_data_date($value, $default) {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $default;
}

$tglAwal = sj_data_date(sj_data_input('tgl_awal'), date('Y-01-01'));
$tglAkhir = sj_data_date(sj_data_input('tgl_akhir'), date('Y-m-d'));
$customer = sj_data_input('customer', 'all');
$status = sj_data_input('status', 'all');
$keyword = sj_data_input('keyword');

$columns = array(
    'surat_jalan.no_surat_jalan',
    'surat_jalan.no_surat_jalan',
    'surat_jalan.no_surat_jalan',
    'surat_jalan.tgl_surat_jalan',
    'surat_jalan.document_date',
    'surat_jalan.posting_date',
    'surat_jalan.no_sales_order',
    'surat_jalan.packing_list_no',
    'surat_jalan.delivery_no',
    'surat_jalan.gi_no',
    'surat_jalan.no_po',
    'penerima.nama',
    'surat_jalan.sopir',
    'surat_jalan.no_kendaraan',
    'surat_jalan.print_count',
    'surat_jalan.status',
    'surat_jalan.id',
);

$whereData = array($tglAwal, $tglAkhir);
$where = " WHERE surat_jalan.tgl_surat_jalan BETWEEN ? AND ? ";
if ($customer !== '' && $customer !== 'all') {
    $where .= " AND surat_jalan.kode_penerima = ? ";
    $whereData[] = $customer;
}
if ($status !== '' && $status !== 'all') {
    $where .= " AND surat_jalan.status = ? ";
    $whereData[] = $status;
}
if ($keyword !== '') {
    $kw = "%".$keyword."%";
    $where .= " AND (
        surat_jalan.no_surat_jalan LIKE ?
        OR surat_jalan.packing_list_no LIKE ?
        OR surat_jalan.delivery_no LIKE ?
        OR surat_jalan.gi_no LIKE ?
        OR surat_jalan.no_sales_order LIKE ?
        OR surat_jalan.no_po LIKE ?
        OR surat_jalan.sopir LIKE ?
        OR surat_jalan.no_kendaraan LIKE ?
        OR surat_jalan.shipping_point LIKE ?
        OR surat_jalan.route LIKE ?
        OR surat_jalan.carrier LIKE ?
        OR penerima.nama LIKE ?
    ) ";
    for ($x = 0; $x < 12; $x++) {
        $whereData[] = $kw;
    }
}

$datatable->set_numbering_status(1);
$datatable->set_order_by("surat_jalan.tgl_surat_jalan");
$datatable->set_order_type("desc");

$query = $datatable->get_custom("
    SELECT
        surat_jalan.id,
        surat_jalan.no_surat_jalan,
        surat_jalan.tgl_surat_jalan,
        surat_jalan.document_date,
        surat_jalan.posting_date,
        surat_jalan.no_sales_order,
        surat_jalan.packing_list_no,
        surat_jalan.delivery_no,
        surat_jalan.gi_no,
        surat_jalan.no_po,
        penerima.nama,
        surat_jalan.sopir,
        surat_jalan.no_kendaraan,
        surat_jalan.print_count,
        surat_jalan.status
    FROM surat_jalan
    LEFT JOIN penerima ON surat_jalan.kode_penerima = penerima.kode_penerima
    $where
", $columns, $whereData);

$data = array();
$i = 1;
foreach ($query as $value) {
    $statusRaw = strtolower((string)$value->status);
    $ResultData = array();
    $ResultData[] = $datatable->number($i);
    $ResultData[] = '';
    $ResultData[] = $value->no_surat_jalan;
    $ResultData[] = $value->tgl_surat_jalan ? date('d/m/Y', strtotime($value->tgl_surat_jalan)) : '-';
    $ResultData[] = $value->document_date ? date('d/m/Y', strtotime($value->document_date)) : '-';
    $ResultData[] = $value->posting_date ? date('d/m/Y', strtotime($value->posting_date)) : '-';
    $ResultData[] = '<strong>'.htmlspecialchars((string)($value->packing_list_no ?: '-'), ENT_QUOTES, 'UTF-8').'</strong><br><small>OD: '.htmlspecialchars((string)($value->delivery_no ?: '-'), ENT_QUOTES, 'UTF-8').'</small><br><small>GI: '.htmlspecialchars((string)($value->gi_no ?: '-'), ENT_QUOTES, 'UTF-8').'</small>';
    $ResultData[] = $value->no_sales_order;
    $ResultData[] = $value->no_po;
    $ResultData[] = $value->nama;
    $ResultData[] = trim($value->sopir.' / '.$value->no_kendaraan, ' /');
    $ResultData[] = (int)$value->print_count;
    $ResultData[] = $statusRaw;
    $ResultData[] = $value->id;
    $data[] = $ResultData;
    $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
