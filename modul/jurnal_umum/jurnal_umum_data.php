<?php
if (!function_exists('fin_t')) {
  function fin_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('fin_h')) {
  function fin_h($key, $fallback = '') { return htmlspecialchars((string) fin_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('fin_js')) {
  function fin_js($key, $fallback = '') { return json_encode(fin_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
error_reporting(0);
include "../../inc/config.php";

function ju_badge($status)
{
    $map = array(
        'DRAFT' => 'warning',
        'POSTED' => 'success',
        'REVERSED' => 'danger'
    );
    $class = isset($map[$status]) ? $map[$status] : 'default';
    return '<span class="label label-'.$class.'">'.htmlspecialchars($status, ENT_QUOTES, 'UTF-8').'</span>';
}

$columns = array(
    'a.no_jurnal',
    'a.posting_status',
    'a.document_type',
    'a.tgl_jurnal',
    'a.no_bukti',
    'a.source_module',
    'total_debet',
    'total_kredit',
    'a.id'
);

$datatable->set_numbering_status(1);
$datatable->set_order_by("a.id");
$datatable->set_order_type("desc");

$where = "";

if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
    $start = addslashes($_POST['start_date']);
    $end = addslashes($_POST['end_date']);
    $where .= " AND a.tgl_jurnal BETWEEN '$start' AND '$end' ";
}

if (!empty($_POST['posting_status'])) {
    $status = addslashes($_POST['posting_status']);
    $where .= " AND a.posting_status = '$status' ";
}

if (!empty($_POST['document_type'])) {
    $docType = addslashes($_POST['document_type']);
    $where .= " AND a.document_type = '$docType' ";
}

if (!empty($_POST['source_module'])) {
    $source = addslashes($_POST['source_module']);
    $where .= " AND a.source_module LIKE '%$source%' ";
}

$query = $datatable->get_custom("
SELECT
    a.id,
    a.no_jurnal,
    a.document_type,
    a.posting_status,
    a.tgl_jurnal,
    a.no_bukti,
    a.source_module,
    COALESCE(SUM(b.debet),0) total_debet,
    COALESCE(SUM(b.kredit),0) total_kredit
FROM jurnal_header a
LEFT JOIN jurnal_detail b ON b.id_header = a.id
WHERE 1=1
$where
GROUP BY a.id
", $columns);

$data = array();
$i = 1;

foreach ($query as $value) {
    $actions = '<div class="btn-group btn-group-sm">';
    $actions .= '<button type="button" class="btn btn-info detail_jurnal" data-id="'.$value->id.'" title="'.fin_h('common_detail', 'Detail').'"><i class="fa fa-search"></i></button>';
    if ($value->posting_status === 'DRAFT') {
        $actions .= '<button type="button" class="btn btn-primary edit_jurnal" data-id="'.$value->id.'" title="'.fin_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button>';
        $actions .= '<button type="button" class="btn btn-success post_jurnal" data-id="'.$value->id.'" title="'.fin_h('common_post', 'Post').'"><i class="fa fa-check"></i></button>';
        $actions .= '<button type="button" class="btn btn-danger delete_jurnal" data-id="'.$value->id.'" title="Delete Draft"><i class="fa fa-trash"></i></button>';
    } elseif ($value->posting_status === 'POSTED') {
        $actions .= '<button type="button" class="btn btn-warning reverse_jurnal" data-id="'.$value->id.'" title="Reversal"><i class="fa fa-undo"></i></button>';
    }
    $actions .= '</div>';

    $row = array();
    $row[] = $datatable->number($i);
    $row[] = '<a href="javascript:void(0)" class="detail_jurnal" data-id="'.$value->id.'">'.htmlspecialchars($value->no_jurnal, ENT_QUOTES, 'UTF-8').'</a>';
    $row[] = ju_badge($value->posting_status);
    $row[] = htmlspecialchars($value->document_type, ENT_QUOTES, 'UTF-8');
    $row[] = htmlspecialchars($value->tgl_jurnal, ENT_QUOTES, 'UTF-8');
    $row[] = htmlspecialchars($value->no_bukti, ENT_QUOTES, 'UTF-8');
    $row[] = htmlspecialchars($value->source_module, ENT_QUOTES, 'UTF-8');
    $row[] = number_format($value->total_debet, 2);
    $row[] = number_format($value->total_kredit, 2);
    $row[] = $actions;
    $data[] = $row;
    $i++;
}

$datatable->set_data($data);
$datatable->create_data();
?>
