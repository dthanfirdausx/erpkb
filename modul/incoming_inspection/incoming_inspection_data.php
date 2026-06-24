<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "incoming_inspection_lib.php";
session_check_json();

$draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
$start = isset($_POST['start']) ? max(0, (int)$_POST['start']) : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
if ($length <= 0 || $length > 500) $length = 25;

$rows = iinq_candidates($db, iinq_filters());
$pageRows = array_slice($rows, $start, $length);
$data = array();
$no = $start + 1;

foreach ($pageRows as $row) {
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  $lotId = (int)$row->inspection_lot_id;
  $action = '<div class="btn-group btn-group-xs">';
  $action .= '<button type="button" class="btn btn-info btn-iinq-source-detail" data-layer-id="'.intval($row->id).'" title="Source Detail"><i class="fa fa-search"></i></button>';
  if ($lotId > 0) {
    $isFinal = in_array($row->lot_status, array('UD_ACCEPTED','UD_REJECTED','UD_PARTIAL','CANCELLED'));
    $action .= '<button type="button" class="btn btn-primary btn-iinq-lot-detail" data-id="'.$lotId.'" title="Lot Detail"><i class="fa fa-clipboard"></i></button>';
    if (!$isFinal) {
      $action .= '<button type="button" class="btn btn-success btn-iinq-result" data-id="'.$lotId.'" title="Record Result"><i class="fa fa-check-square-o"></i></button>';
      $action .= '<button type="button" class="btn btn-warning btn-iinq-ud" data-id="'.$lotId.'" title="Usage Decision"><i class="fa fa-gavel"></i></button>';
    }
  } else {
    $action .= '<button type="button" class="btn btn-success btn-iinq-create-lot" data-layer-id="'.intval($row->id).'" title="Create Inspection Lot"><i class="fa fa-plus"></i> Lot</button>';
  }
  $action .= '</div>';
  $customs = trim((string)$row->jenis_dokpab.' '.(string)$row->no_dokpab);
  if (trim((string)$row->no_aju) !== '') $customs = trim($customs.' / Aju '.$row->no_aju, ' /');
  $data[] = array(
    $no++,
    $action,
    '<strong>'.ilot_h($row->no_bpb ?: '-').'</strong><br><small>'.ilot_h(iinq_source_label($row->ref_table).' #'.$row->ref_id).'</small>',
    ilot_h($row->receipt_date),
    '<strong>'.ilot_h($row->kode).'</strong><br><small class="text-muted">'.ilot_h($row->nm_barang).'</small>',
    '<span class="pull-right">'.ilot_num($row->qty_sisa).'</span><br><small>'.ilot_h($row->satuan).'</small>',
    ilot_h($location ?: '-').'<br><small>'.ilot_h($row->stock_type).'</small>',
    $lotId > 0 ? '<strong>'.ilot_h($row->lot_no).'</strong><br>'.iinq_status_badge($row->lot_status) : iinq_status_badge(''),
    '<small>Result '.intval($row->result_count).' / Fail '.intval($row->fail_count).'</small><br><small>'.ilot_h($row->ud_text ?: '-').'</small>',
    ilot_h($customs ?: '-')
  );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>count($rows),'recordsFiltered'=>count($rows),'data'=>$data));
?>
