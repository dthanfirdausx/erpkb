<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "usage_decision_lib.php";

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

if ($act === 'next_no') {
  session_check_json();
  ilot_json('good','',array('ud_no'=>ud_next_number(date('Y-m-d'))));
}

if ($act === 'lot_candidate') {
  session_check_json();
  $rows = ud_lot_candidates($db, ud_input('term'));
  $results = array();
  foreach ($rows as $row) {
    $suggested = (int)$row->fail_count > 0 ? 'P' : 'A';
    $results[] = array(
      'id'=>$row->id,
      'text'=>$row->lot_no.' | '.$row->material_code.' - '.$row->material_name.' | '.ud_num($row->lot_qty).' '.$row->uom.' | Fail '.intval($row->fail_count),
      'lot_no'=>$row->lot_no,'inspection_origin'=>$row->inspection_origin,'inspection_type'=>$row->inspection_type,
      'material_code'=>$row->material_code,'material_name'=>$row->material_name,'lot_qty'=>$row->lot_qty,'accepted_qty'=>$row->lot_qty,'rejected_qty'=>0,
      'defect_qty'=>$row->defect_qty,'uom'=>$row->uom,'stock_layer_id'=>$row->stock_layer_id,'stock_type'=>$row->stock_type,
      'plant_id'=>$row->plant_id,'storage_location_id'=>$row->storage_location_id,'storage_bin_id'=>$row->storage_bin_id,
      'no_aju'=>$row->no_aju,'jenis_dokpab'=>$row->jenis_dokpab,'no_dokpab'=>$row->no_dokpab,'no_bpb'=>$row->no_bpb,
      'suggested_decision'=>$suggested,'fail_count'=>$row->fail_count,'result_count'=>$row->result_count
    );
  }
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('results'=>$results)); exit;
}

if ($act === 'lot_detail') {
  session_check_json();
  $row = ilot_fetch($db, (int)ud_input('id',0));
  if (!$row) { echo '<div class="alert alert-warning">Inspection lot tidak ditemukan.</div>'; exit; }
  $results = $db->query("SELECT * FROM erp_inspection_lot_result WHERE inspection_lot_id=? ORDER BY characteristic_no,id", array((int)$row->id));
  ?>
  <div class="row"><div class="col-md-8"><h3 style="margin-top:0"><?=ud_h($row->lot_no);?> <small><?=ud_h(ilot_origin_label($row->inspection_origin));?></small></h3><p class="text-muted"><?=ud_h($row->material_code.' - '.$row->material_name);?></p></div><div class="col-md-4 text-right"><?=ilot_status_badge($row->lot_status);?></div></div>
  <div class="row"><div class="col-sm-3"><strong>Lot Qty</strong><br><?=ud_num($row->lot_qty).' '.ud_h($row->uom);?></div><div class="col-sm-3"><strong>Accepted / Rejected</strong><br><?=ud_num($row->accepted_qty).' / '.ud_num($row->rejected_qty);?></div><div class="col-sm-3"><strong>Stock Layer</strong><br>#<?=intval($row->stock_layer_id);?> / <?=ud_h($row->stock_type);?></div><div class="col-sm-3"><strong>BC</strong><br><?=ud_h(trim($row->jenis_dokpab.' '.$row->no_dokpab) ?: '-');?></div></div><hr>
  <h4>Inspection Results</h4><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>No</th><th>Characteristic</th><th>Spec</th><th>Result</th><th>Status</th><th class="text-right">Defect Qty</th><th>Remarks</th></tr></thead><tbody>
  <?php foreach($results as $res){ ?><tr><td><?=ud_h($res->characteristic_no);?></td><td><?=ud_h($res->characteristic_name);?></td><td><?=ud_h($res->specification);?></td><td><?=ud_h($res->result_value);?></td><td><?=ilot_status_badge($res->result_status);?></td><td class="text-right"><?=ud_num($res->defect_qty);?></td><td><?=ud_h($res->remarks);?></td></tr><?php } ?>
  </tbody></table></div>
  <?php exit;
}

if ($act === 'post') {
  session_check_json();
  $lotId = (int)ud_input('inspection_lot_id',0);
  $lot = ilot_fetch($db, $lotId);
  if (!$lot) ilot_json('error','Inspection lot tidak ditemukan.');
  if ($db->fetch("SELECT id FROM erp_usage_decision WHERE inspection_lot_id=? LIMIT 1", array($lotId))) ilot_json('error','Usage Decision untuk lot ini sudah pernah diposting.');
  if ($lot->lot_status === 'CANCELLED') ilot_json('error','Inspection lot sudah cancel.');
  if (in_array($lot->lot_status, array('UD_ACCEPTED','UD_REJECTED','UD_PARTIAL'), true)) ilot_json('error','Inspection lot sudah final UD.');
  $code = ud_input('decision_code');
  if (!in_array($code, array('A','R','P','RW','RTV','SCRAP'), true)) ilot_json('error','Decision wajib dipilih.');
  $accepted = ud_qty(ud_input('accepted_qty'));
  $rejected = ud_qty(ud_input('rejected_qty'));
  if ($accepted < 0 || $rejected < 0) ilot_json('error','Qty tidak boleh minus.');
  if ($accepted + $rejected <= 0) ilot_json('error','Accepted + rejected qty wajib lebih dari nol.');
  if ($accepted + $rejected > (float)$lot->lot_qty + 0.00001) ilot_json('error','Accepted + rejected tidak boleh melebihi lot qty.');
  if ($code === 'A' && $rejected > 0) ilot_json('error','Decision Accept tidak boleh punya rejected qty.');
  if (in_array($code, array('R','RW','RTV','SCRAP'), true) && $accepted > 0) ilot_json('error','Decision reject/rework/return/scrap tidak boleh punya accepted qty.');
  if ($code === 'P' && ($accepted <= 0 || $rejected <= 0)) ilot_json('error','Partial Accept wajib punya accepted dan rejected qty.');
  $decision = ud_decision_defaults($code);
  $udNo = ud_input('ud_no') ?: ud_next_number(date('Y-m-d'));
  if ($db->fetch("SELECT id FROM erp_usage_decision WHERE ud_no=? LIMIT 1", array($udNo))) ilot_json('error','Nomor UD sudah dipakai.');
  $db->query('START TRANSACTION');
  try {
    $acceptedLayerId = null; $rejectedLayerId = null;
    $stockPosted = ud_post_stock_effect($db, $lot, $accepted, $rejected, $decision, $acceptedLayerId, $rejectedLayerId);
    $data = array(
      'ud_no'=>$udNo,'inspection_lot_id'=>$lotId,'lot_no'=>$lot->lot_no,'decision_code'=>$code,'decision_text'=>$decision['text'],
      'follow_up_action'=>$decision['follow'],'movement_type'=>$decision['move'],'stock_posted'=>$stockPosted,
      'source_stock_layer_id'=>(int)$lot->stock_layer_id ?: null,'accepted_stock_layer_id'=>$acceptedLayerId,'rejected_stock_layer_id'=>$rejectedLayerId,
      'material_code'=>$lot->material_code,'material_name'=>$lot->material_name,'lot_qty'=>$lot->lot_qty,'accepted_qty'=>$accepted,'rejected_qty'=>$rejected,'uom'=>$lot->uom,
      'plant_id'=>$lot->plant_id,'storage_location_id'=>$lot->storage_location_id,'storage_bin_id'=>$lot->storage_bin_id,'source_stock_type'=>$lot->stock_type,
      'accepted_stock_type'=>$decision['accepted_type'],'rejected_stock_type'=>$decision['rejected_type'],
      'no_aju'=>$lot->no_aju,'jenis_dokpab'=>$lot->jenis_dokpab,'no_dokpab'=>$lot->no_dokpab,'no_bpb'=>$lot->no_bpb,
      'reason_code'=>ud_input('reason_code'),'defect_summary'=>ud_defect_summary($db,$lotId),'notes'=>ud_input('notes'),'decision_by'=>$username
    );
    foreach ($data as $k=>$v) if ($v === null || $v === '') unset($data[$k]);
    if (!$db->insert('erp_usage_decision',$data)) throw new Exception($db->getErrorMessage() ?: 'Usage Decision gagal disimpan.');
    $newId = (int)$db->last_insert_id();
    if ($acceptedLayerId) $db->update('stock_layer', array('ref_id'=>$newId), 'id', $acceptedLayerId);
    if ($rejectedLayerId) $db->update('stock_layer', array('ref_id'=>$newId), 'id', $rejectedLayerId);
    if (!$db->update('erp_inspection_lot', array(
      'lot_status'=>$decision['status'],'ud_code'=>$code,'ud_text'=>$decision['text'],'accepted_qty'=>$accepted,'rejected_qty'=>$rejected,
      'ud_date'=>date('Y-m-d H:i:s'),'ud_by'=>$username,'updated_by'=>$username,'notes'=>ud_input('notes',$lot->notes)
    ), 'id', $lotId)) throw new Exception($db->getErrorMessage() ?: 'Inspection lot gagal di-update.');
    ud_insert_action($db,$newId,'POST','Usage Decision posted: '.$decision['text'],$username);
    ud_insert_action($db,$newId,'STOCK','Stock posting: '.$stockPosted.' / accepted layer '.$acceptedLayerId.' / rejected layer '.$rejectedLayerId,$username);
    $db->query('COMMIT');
    ilot_json('good','',array('ud_no'=>$udNo,'id'=>$newId));
  } catch (Exception $e) {
    $db->query('ROLLBACK');
    ilot_json('error',$e->getMessage());
  }
}

if ($act === 'detail') {
  session_check_json();
  $row = ud_fetch($db, (int)ud_input('id',0));
  if (!$row) { echo '<div class="alert alert-warning">Usage Decision tidak ditemukan.</div>'; exit; }
  $actions = $db->query("SELECT * FROM erp_usage_decision_action WHERE usage_decision_id=? ORDER BY action_at DESC,id DESC", array((int)$row->id));
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  ?>
  <div class="row"><div class="col-md-8"><h3 style="margin-top:0"><?=ud_h($row->ud_no);?> <small><?=ud_h($row->lot_no);?></small></h3><p class="text-muted"><?=ud_h($row->material_code.' - '.$row->material_name);?></p></div><div class="col-md-4 text-right"><?=ud_decision_badge($row->decision_code);?> <span class="label label-<?=($row->stock_posted==='Y'?'success':'default');?>">Stock <?=ud_h($row->stock_posted);?></span></div></div>
  <div class="row"><div class="col-sm-3"><strong>Lot Qty</strong><br><?=ud_num($row->lot_qty).' '.ud_h($row->uom);?></div><div class="col-sm-3"><strong>Accepted Qty</strong><br><?=ud_num($row->accepted_qty);?></div><div class="col-sm-3"><strong>Rejected Qty</strong><br><?=ud_num($row->rejected_qty);?></div><div class="col-sm-3"><strong>Movement</strong><br><?=ud_h($row->movement_type ?: '-');?></div></div><hr>
  <div class="row"><div class="col-sm-3"><strong>Location</strong><br><?=ud_h($location ?: '-');?></div><div class="col-sm-3"><strong>Stock Layers</strong><br>Src #<?=intval($row->source_stock_layer_id);?> / Acc #<?=intval($row->accepted_stock_layer_id);?> / Rej #<?=intval($row->rejected_stock_layer_id);?></div><div class="col-sm-3"><strong>No Aju</strong><br><?=ud_h($row->no_aju ?: '-');?></div><div class="col-sm-3"><strong>Dokumen BC</strong><br><?=ud_h(trim($row->jenis_dokpab.' '.$row->no_dokpab) ?: '-');?></div></div><hr>
  <div class="row"><div class="col-md-6"><h4>Decision</h4><table class="table table-bordered table-condensed"><tr><th style="width:170px;background:#f8fafc">Follow Up</th><td><?=ud_h(ud_follow_up_label($row->follow_up_action));?></td></tr><tr><th style="background:#f8fafc">Reason</th><td><?=ud_h($row->reason_code ?: '-');?></td></tr><tr><th style="background:#f8fafc">Notes</th><td><?=nl2br(ud_h($row->notes));?></td></tr></table></div><div class="col-md-6"><h4>Failed Characteristics</h4><pre style="white-space:pre-wrap;background:#f8fafc;border:1px solid #e5e7eb"><?=ud_h($row->defect_summary ?: '-');?></pre></div></div>
  <h4>Action Log</h4><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Date</th><th>Type</th><th>Action</th><th>By</th></tr></thead><tbody><?php $n=0; foreach($actions as $a){$n++;?><tr><td><?=ud_h($a->action_at);?></td><td><?=ud_h($a->action_type);?></td><td><?=nl2br(ud_h($a->action_text));?></td><td><?=ud_h($a->action_by);?></td></tr><?php } if($n===0){?><tr><td colspan="4" class="text-center text-muted">Belum ada action.</td></tr><?php } ?></tbody></table></div>
  <?php exit;
}

if ($act === 'excel') {
  $initial = ob_get_level(); ob_start();
  ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php'; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $filters = ud_filters(); $rows = ud_load_rows($db,$filters);
  $excel = new PHPExcel(); $sheet = $excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('Usage Decision'));
  $heads = array(erp_export_label("No"),erp_export_label("UD No"),erp_export_label("Lot No"),erp_export_label("Origin"),erp_export_label("Decision"),erp_export_label("Follow Up"),erp_export_label("Movement"),erp_export_label("Stock Posted"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Lot Qty"),erp_export_label("Accepted Qty"),erp_export_label("Rejected Qty"),erp_export_label("UOM"),erp_export_label("Location"),erp_export_label("Source Layer"),erp_export_label("Accepted Layer"),erp_export_label("Rejected Layer"),erp_export_label("No Aju"),erp_export_label("Dokumen BC"),erp_export_label("No BPB"),erp_export_label("Decision At"),erp_export_label("Decision By"),erp_export_label("Reason"),erp_export_label("Notes"),erp_export_label("Defect Summary"));
  foreach($heads as $c=>$h) $sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5; $n=1; foreach($rows as $row){$loc=trim($row->plant_code.' / '.$row->storage_code.' / '.$row->bin_code,' /');$vals=array($n++,$row->ud_no,$row->lot_no,ilot_origin_label($row->inspection_origin),ud_decision_label($row->decision_code),ud_follow_up_label($row->follow_up_action),$row->movement_type,$row->stock_posted,$row->material_code,$row->material_name,(float)$row->lot_qty,(float)$row->accepted_qty,(float)$row->rejected_qty,$row->uom,$loc,(int)$row->source_stock_layer_id,(int)$row->accepted_stock_layer_id,(int)$row->rejected_stock_layer_id,$row->no_aju,trim($row->jenis_dokpab.' '.$row->no_dokpab),$row->no_bpb,$row->decision_at,$row->decision_by,$row->reason_code,$row->notes,$row->defect_summary);foreach($vals as $c=>$v)$sheet->setCellValueByColumnAndRow($c,$r,$v);$r++;}
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('USAGE DECISION - SAP QM'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>26,'numeric_columns'=>array('K','L','M'),'filters'=>array('Periode'=>$filters['tgl_awal'].' s/d '.$filters['tgl_akhir'],'Origin'=>$filters['inspection_origin'],'Decision'=>$filters['decision_code'],'Stock Posted'=>$filters['stock_posted'],'Keyword'=>$filters['keyword'])));
  $tmp=erpkb_excel_temp_file('usage_decision_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp); $size=@filesize($tmp); $sig=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
  while(ob_get_level()>$initial)ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="usage_decision_'.$filters['tgl_awal'].'_sd_'.$filters['tgl_akhir'].'.xlsx"'); header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;
}

session_check_json();
ilot_json('error','Aksi Usage Decision tidak dikenal.');
?>
