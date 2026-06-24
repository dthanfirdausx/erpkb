<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "quality_notification_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;

$rows = qn_load_rows($db, qn_filters());
$pageRows = array_slice($rows, $start, $length);
$data = array();
$no = $start + 1;

foreach ($pageRows as $row) {
  $isClosed = in_array($row->status, array('CLOSED','CANCELLED'));
  $action = '<div class="btn-group btn-group-xs">'
    .'<button type="button" class="btn btn-info btn-qn-detail" data-id="'.intval($row->id).'" title="Detail"><i class="fa fa-search"></i></button>';
  if (!$isClosed) {
    $action .= '<button type="button" class="btn btn-primary btn-qn-edit" data-id="'.intval($row->id).'" title="Edit"><i class="fa fa-pencil"></i></button>'
      .'<button type="button" class="btn btn-warning btn-qn-action" data-id="'.intval($row->id).'" title="Action / Status"><i class="fa fa-tasks"></i></button>';
  }
  $action .= '</div>';
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $dueClass = ($row->due_date && $row->due_date < date('Y-m-d') && $row->status !== 'CLOSED') ? 'text-danger' : 'text-muted';
  $data[] = array(
    $no++,
    $action,
    '<strong>'.ilot_h($row->notification_no).'</strong><br><small>'.ilot_h($row->notification_type.' / '.$row->source_type).'</small>',
    '<strong>'.ilot_h($row->material_code ?: '-').'</strong><br><small class="text-muted">'.ilot_h($row->material_name).'</small>',
    '<span class="pull-right">'.qn_num($row->defect_qty).'</span><br><small>'.ilot_h($row->uom).'</small>',
    qn_severity_badge($row->severity).'<br><small>'.ilot_h($row->priority).'</small>',
    qn_status_badge($row->status).'<br><small>'.intval($row->action_count).' action</small>',
    ilot_h($row->defect_category ?: '-').'<br><small>'.ilot_h($row->defect_code ?: '-').'</small>',
    ilot_h($row->source_ref_no ?: $row->lot_no ?: '-').'<br><small class="text-muted">'.ilot_h($location ?: '-').'</small>',
    ilot_h($row->responsible_user ?: '-').'<br><small class="'.$dueClass.'">Due '.ilot_h($row->due_date ?: '-').'</small>',
    ilot_h(substr((string)$row->created_at,0,16)).'<br><small>'.ilot_h($row->created_by).'</small>'
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>count($rows),'recordsFiltered'=>count($rows),'data'=>$data));
?>
