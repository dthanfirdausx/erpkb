<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "quality_dashboard_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;

$filters = qdash_filters();
$rows = qdash_exception_rows($db, $filters);
$pageRows = array_slice($rows, $start, $length);
$data = array();
$no = $start + 1;

foreach ($pageRows as $row) {
  $line = array();
  $line[] = $no++;
  $line[] = '<button type="button" class="btn btn-info btn-xs btn-qm-detail" data-source="'.qdash_h($row->source_type).'" data-id="'.intval($row->id).'" title="Detail"><i class="fa fa-search"></i></button>';
  $line[] = '<span class="label label-primary">'.qdash_h($row->source_label).'</span>';
  $line[] = qdash_h($row->doc_date);
  $line[] = '<strong>'.qdash_h($row->material_code).'</strong><br><small class="text-muted">'.qdash_h($row->material_name).'</small>';
  $line[] = qdash_h($row->location ?: '-').'<br><small class="text-muted">'.qdash_h($row->reference).'</small>';
  $line[] = '<span class="pull-right">'.qdash_num($row->qty,5).'</span><br><small>'.qdash_h($row->uom).'</small>';
  $line[] = qdash_status_badge($row->status);
  $line[] = qdash_h($row->bc_document ?: '-');
  $line[] = '<small>'.qdash_h($row->remarks).'</small>';
  $data[] = $line;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
  'draw' => $draw,
  'recordsTotal' => count($rows),
  'recordsFiltered' => count($rows),
  'data' => $data
));
?>
