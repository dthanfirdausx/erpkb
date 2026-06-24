<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "inspection_lot_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;

$rows = ilot_load_rows($db, ilot_filters());
$total = is_array($rows) ? count($rows) : 0;
$pageRows = array_slice($rows ?: array(), $start, $length);
$data = array();
$no = $start + 1;

foreach ($pageRows as $row) {
  $location = ilot_location_text($row);
  $isFinal = in_array($row->lot_status, array('UD_ACCEPTED','UD_REJECTED','UD_PARTIAL','CANCELLED'));
  $action = '<div class="btn-group btn-group-xs">'
    .'<button type="button" class="btn btn-info btn-ilot-detail" data-id="'.intval($row->id).'" title="Detail"><i class="fa fa-search"></i></button>';
  if (!$isFinal) {
    $action .= '<button type="button" class="btn btn-primary btn-ilot-edit" data-id="'.intval($row->id).'" title="Edit"><i class="fa fa-pencil"></i></button>'
      .($row->lot_status === 'CREATED' ? '<button type="button" class="btn btn-default btn-ilot-start" data-id="'.intval($row->id).'" data-no="'.ilot_h($row->lot_no).'" title="Start Inspection"><i class="fa fa-play"></i></button>' : '')
      .'<button type="button" class="btn btn-success btn-ilot-result" data-id="'.intval($row->id).'" title="Record Result"><i class="fa fa-check-square-o"></i></button>'
      .'<button type="button" class="btn btn-warning btn-ilot-ud" data-id="'.intval($row->id).'" title="Usage Decision"><i class="fa fa-gavel"></i></button>';
  }
  $action .= '</div>';
  if (!$isFinal) {
    $action .= ' <button type="button" class="btn btn-danger btn-xs btn-ilot-cancel" data-id="'.intval($row->id).'" data-no="'.ilot_h($row->lot_no).'" title="Cancel"><i class="fa fa-ban"></i></button>';
  }
  $line = array();
  $line[] = $no++;
  $line[] = $action;
  $line[] = '<strong>'.ilot_h($row->lot_no).'</strong><br><small class="text-muted">'.ilot_h(ilot_origin_label($row->inspection_origin).' / Type '.$row->inspection_type).'</small>';
  $line[] = '<strong>'.ilot_h($row->material_code).'</strong><br><small class="text-muted">'.ilot_h($row->material_name).'</small>';
  $line[] = '<span class="pull-right">'.ilot_num($row->lot_qty).'</span><br><small>'.ilot_h($row->uom).'</small>';
  $line[] = '<span class="pull-right">'.ilot_num($row->sample_qty).'</span><br><small>sample</small>';
  $line[] = ilot_h($location ?: '-').'<br><small class="text-muted">'.ilot_h($row->stock_type).'</small>';
  $line[] = ilot_status_badge($row->lot_status).'<br><small>'.intval($row->result_count).' result / '.intval($row->fail_count).' fail</small>';
  $customs = trim((string)$row->jenis_dokpab.' '.(string)$row->no_dokpab);
  if (trim((string)$row->no_aju) !== '') $customs = trim($customs.' / Aju '.$row->no_aju, ' /');
  $line[] = ilot_h(trim((string)$row->source_ref_no.' / '.(string)$row->no_bpb, ' /') ?: '-').'<br><small class="text-muted">'.ilot_h($customs ?: '-').'</small>';
  $line[] = ilot_h(substr((string)$row->created_at,0,16)).'<br><small>'.ilot_h($row->created_by).'</small>';
  $data[] = $line;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
