<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "final_inspection_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;

$rows = fins_candidates($db, fins_filters());
$pageRows = array_slice($rows, $start, $length);
$data = array();
$no = $start + 1;

foreach ($pageRows as $row) {
  $lotId = (int)$row->inspection_lot_id;
  $action = '<div class="btn-group btn-group-xs">';
  $action .= '<button type="button" class="btn btn-info btn-fins-source-detail" data-detail-id="'.intval($row->id).'" title="Source Detail"><i class="fa fa-search"></i></button>';
  if ($lotId > 0) {
    $isFinal = in_array($row->lot_status, array('UD_ACCEPTED','UD_REJECTED','UD_PARTIAL','CANCELLED'));
    $action .= '<button type="button" class="btn btn-primary btn-fins-lot-detail" data-id="'.$lotId.'" title="Lot Detail"><i class="fa fa-clipboard"></i></button>';
    if (!$isFinal) {
      $action .= '<button type="button" class="btn btn-success btn-fins-result" data-id="'.$lotId.'" title="Record Result"><i class="fa fa-check-square-o"></i></button>';
      $action .= '<button type="button" class="btn btn-warning btn-fins-ud" data-id="'.$lotId.'" title="Usage Decision"><i class="fa fa-gavel"></i></button>';
    }
  } else {
    $action .= '<button type="button" class="btn btn-success btn-fins-create-lot" data-detail-id="'.intval($row->id).'" title="Create Inspection Lot"><i class="fa fa-plus"></i> Lot</button>';
  }
  $action .= '</div>';
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $data[] = array(
    $no++,
    $action,
    '<strong>'.ilot_h($row->gr_no).'</strong><br><small>'.ilot_h($row->posting_date).'</small>',
    '<strong>'.ilot_h($row->no_production_order).'</strong><br><small>'.ilot_h($row->confirmation_no ?: '-').'</small>',
    '<strong>'.ilot_h($row->material_code).'</strong><br><small class="text-muted">'.ilot_h($row->material_name).'</small>',
    '<span class="pull-right">'.ilot_num($row->qty).'</span><br><small>'.ilot_h($row->uom).'</small>',
    ilot_h($location ?: '-').'<br><small>'.ilot_h($row->gr_stock_type).'</small>',
    $lotId > 0 ? '<strong>'.ilot_h($row->lot_no).'</strong><br>'.fins_status_badge($row->lot_status) : fins_status_badge(''),
    '<small>Result '.intval($row->result_count).' / Fail '.intval($row->fail_count).'</small><br><small>'.ilot_h($row->ud_text ?: '-').'</small>',
    '<small>Layer #'.intval($row->stock_layer_id).' / Mat Doc '.intval($row->material_doc_id).'</small>'
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>count($rows),'recordsFiltered'=>count($rows),'data'=>$data));
?>
