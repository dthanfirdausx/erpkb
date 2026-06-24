<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "usage_decision_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;
$mode = ud_input('mode','posted');
$filters = ud_filters();
$rows = $mode === 'pending' ? ud_candidate_rows($db, $filters) : ud_load_rows($db, $filters);
$total = count($rows);
$pageRows = array_slice($rows, $start, $length);
$data = array();
$no = $start + 1;

foreach ($pageRows as $row) {
  if ($mode === 'pending') {
    $buttons = '<button type="button" class="btn btn-warning btn-xs btn-ud-post" data-id="'.intval($row->id).'" title="Post Usage Decision"><i class="fa fa-gavel"></i></button> '
      .'<button type="button" class="btn btn-info btn-xs btn-ud-lot-detail" data-id="'.intval($row->id).'" title="Lot Detail"><i class="fa fa-search"></i></button>';
    $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
    $customs = trim((string)$row->jenis_dokpab.' '.(string)$row->no_dokpab);
    $data[] = array(
      $no++,
      $buttons,
      '<strong>'.ud_h($row->lot_no).'</strong><br><small>'.ud_h(ilot_origin_label($row->inspection_origin).' / Type '.$row->inspection_type).'</small>',
      '<strong>'.ud_h($row->material_code).'</strong><br><small>'.ud_h($row->material_name).'</small>',
      '<span class="pull-right">'.ud_num($row->lot_qty).'</span><br><small>'.ud_h($row->uom).'</small>',
      '<span class="pull-right">'.ud_num($row->defect_qty).'</span><br><small>'.intval($row->fail_count).' fail / '.intval($row->result_count).' result</small>',
      ilot_status_badge($row->lot_status),
      ud_h($location ?: '-').'<br><small>'.ud_h($row->stock_type).'</small>',
      ud_h(trim((string)$row->source_ref_no.' / '.(string)$row->no_bpb, ' /') ?: '-').'<br><small>'.ud_h(trim($customs.' / Aju '.$row->no_aju, ' /') ?: '-').'</small>',
      ud_h(substr((string)$row->created_at,0,16))
    );
  } else {
    $buttons = '<button type="button" class="btn btn-info btn-xs btn-ud-detail" data-id="'.intval($row->id).'" title="Detail"><i class="fa fa-search"></i></button>';
    $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
    $stock = '<span class="label label-'.($row->stock_posted === 'Y' ? 'success' : 'default').'">Stock '.$row->stock_posted.'</span><br><small>'.ud_h($row->movement_type ?: '-').'</small>';
    $data[] = array(
      $no++,
      $buttons,
      '<strong>'.ud_h($row->ud_no).'</strong><br><small>'.ud_h($row->lot_no).'</small>',
      '<strong>'.ud_h($row->material_code).'</strong><br><small>'.ud_h($row->material_name).'</small>',
      ud_decision_badge($row->decision_code).'<br><small>'.ud_h(ud_follow_up_label($row->follow_up_action)).'</small>',
      '<span class="pull-right">'.ud_num($row->accepted_qty).'</span><br><small>accepted</small>',
      '<span class="pull-right">'.ud_num($row->rejected_qty).'</span><br><small>rejected</small>',
      $stock,
      ud_h($location ?: '-').'<br><small>'.ud_h($row->accepted_stock_type ?: '-').' / '.ud_h($row->rejected_stock_type ?: '-').'</small>',
      ud_h(substr((string)$row->decision_at,0,16)).'<br><small>'.ud_h($row->decision_by).'</small>'
    );
  }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data));
?>
