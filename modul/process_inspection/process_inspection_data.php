<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "process_inspection_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;

$rows = pins_candidates($db, pins_filters());
$pageRows = array_slice($rows, $start, $length);
$data = array();
$no = $start + 1;

foreach ($pageRows as $row) {
  $lotId = (int)$row->inspection_lot_id;
  $action = '<div class="btn-group btn-group-xs">';
  $action .= '<button type="button" class="btn btn-info btn-pins-source-detail" data-confirmation-id="'.intval($row->id_confirmation).'" title="Source Detail"><i class="fa fa-search"></i></button>';
  if ($lotId > 0) {
    $isFinal = in_array($row->lot_status, array('UD_ACCEPTED','UD_REJECTED','UD_PARTIAL','CANCELLED'));
    $action .= '<button type="button" class="btn btn-primary btn-pins-lot-detail" data-id="'.$lotId.'" title="Lot Detail"><i class="fa fa-clipboard"></i></button>';
    if (!$isFinal) {
      $action .= '<button type="button" class="btn btn-success btn-pins-result" data-id="'.$lotId.'" title="Record Result"><i class="fa fa-check-square-o"></i></button>';
      $action .= '<button type="button" class="btn btn-warning btn-pins-ud" data-id="'.$lotId.'" title="Usage Decision"><i class="fa fa-gavel"></i></button>';
    }
  } else {
    $action .= '<button type="button" class="btn btn-success btn-pins-create-lot" data-confirmation-id="'.intval($row->id_confirmation).'" title="Create Inspection Lot"><i class="fa fa-plus"></i> Lot</button>';
  }
  $action .= '</div>';
  $qtyText = '<span class="pull-right">'.ilot_num($row->yield_qty).'</span><br><small>Yield</small>';
  if ((float)$row->scrap_qty > 0 || (float)$row->rework_qty > 0) $qtyText .= '<br><small class="text-danger">Scrap '.ilot_num($row->scrap_qty).' / Rework '.ilot_num($row->rework_qty).'</small>';
  $data[] = array(
    $no++,
    $action,
    '<strong>'.ilot_h($row->confirmation_no ?: '-').'</strong><br><small>'.ilot_h($row->posting_date).'</small>',
    '<strong>'.ilot_h($row->no_production_order).'</strong><br><small>'.ilot_h($row->po_status.' / '.$row->plant).'</small>',
    '<strong>'.ilot_h($row->material_code).'</strong><br><small class="text-muted">'.ilot_h($row->material_name).'</small>',
    ilot_h(trim((string)$row->operation_no.' / '.(string)$row->work_center, ' /')).'<br><small>'.ilot_h($row->operation_name).'</small>',
    $qtyText,
    ilot_h($row->uom),
    $lotId > 0 ? '<strong>'.ilot_h($row->lot_no).'</strong><br>'.pins_status_badge($row->lot_status) : pins_status_badge(''),
    '<small>Result '.intval($row->result_count).' / Fail '.intval($row->fail_count).'</small><br><small>'.ilot_h($row->ud_text ?: '-').'</small>'
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>count($rows),'recordsFiltered'=>count($rows),'data'=>$data));
?>
