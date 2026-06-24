<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "capa_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
$filters = capa_filters();
$rows = capa_load_rows($db, $filters);
$total = count($rows);
$slice = array_slice($rows, $start, $length > 0 ? $length : 25);
$data = array();
$no = $start + 1;
foreach ($slice as $row) {
  $buttons = '<div class="btn-group btn-group-xs" role="group">'
    .'<button class="btn btn-info btn-capa-detail" data-id="'.intval($row->id).'" title="Detail"><i class="fa fa-eye"></i></button>'
    .'<button class="btn btn-primary btn-capa-edit" data-id="'.intval($row->id).'" title="Edit"><i class="fa fa-pencil"></i></button>'
    .'<button class="btn btn-warning btn-capa-action" data-id="'.intval($row->id).'" title="Action"><i class="fa fa-tasks"></i></button>'
    .'<button class="btn btn-danger btn-capa-cancel" data-id="'.intval($row->id).'" title="Cancel"><i class="fa fa-ban"></i></button>'
    .'</div>';
  $source = capa_h($row->source_type).'<br><small>'.capa_h($row->notification_no ?: '-').'</small>';
  $problem = '<strong>'.capa_h($row->defect_category ?: '-').'</strong><br><small>'.capa_h(mb_substr((string)$row->problem_statement,0,95)).'</small>';
  $owner = capa_h($row->owner_user ?: '-').'<br><small>Due: '.capa_h($row->due_date ?: '-').'</small>';
  $data[] = array(
    $no++,
    $buttons,
    '<strong>'.capa_h($row->capa_no).'</strong><br><small>'.capa_h($row->capa_type).' / '.capa_h($row->priority).'</small>',
    $source,
    capa_h($row->material_code ?: '-').'<br><small>'.capa_h($row->material_name ?: '').'</small>',
    capa_risk_badge($row->risk_level),
    capa_status_badge($row->status),
    $problem,
    $owner,
    capa_num($row->action_count),
    capa_h($row->created_at)
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
