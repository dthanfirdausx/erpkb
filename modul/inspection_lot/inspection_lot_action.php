<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "inspection_lot_lib.php";

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

if ($act === 'material_search') {
  session_check_json();
  $term = ilot_input('term');
  $params = array();
  $where = " WHERE b.status=1 ";
  if ($term !== '') {
    $where .= " AND (b.kd_barang LIKE ? OR b.nm_barang LIKE ?) ";
    $params[] = '%'.$term.'%'; $params[] = '%'.$term.'%';
  }
  $rows = $db->query("SELECT b.kd_barang,b.nm_barang,b.satuan FROM barang b $where ORDER BY b.kd_barang LIMIT 30", $params);
  $results = array();
  foreach ($rows as $row) $results[] = array('id'=>$row->kd_barang,'text'=>$row->kd_barang.' - '.$row->nm_barang.' | '.$row->satuan,'uom'=>$row->satuan,'name'=>$row->nm_barang);
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('results'=>$results)); exit;
}

if ($act === 'source_search') {
  session_check_json();
  $term = ilot_input('term');
  $rows = ilot_stock_layers($db, $term);
  $results = array();
  foreach ($rows as $row) {
    $loc = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
    $results[] = array(
      'id'=>$row->id,
      'text'=>'Layer #'.$row->id.' | '.$row->kode.' - '.$row->nm_barang.' | '.ilot_num($row->qty_sisa).' '.$row->satuan.' | '.$row->stock_type.' | '.$loc,
      'material_code'=>$row->kode,'material_name'=>$row->nm_barang,'qty'=>$row->qty_sisa,'uom'=>$row->satuan,
      'plant_id'=>$row->plant_id,'storage_location_id'=>$row->storage_location_id,'storage_bin_id'=>$row->storage_bin_id,
      'stock_type'=>$row->stock_type,'source_ref_type'=>$row->ref_table,'source_ref_id'=>$row->ref_id,'source_ref_no'=>$row->no_bpb,
      'no_aju'=>$row->no_aju,'jenis_dokpab'=>$row->jenis_dokpab,'no_dokpab'=>$row->no_dokpab,'no_bpb'=>$row->no_bpb
    );
  }
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('results'=>$results)); exit;
}

if ($act === 'next_no') {
  session_check_json();
  ilot_json('good','',array('lot_no'=>ilot_next_number(date('Y-m-d'))));
}

if ($act === 'save') {
  session_check_json();
  $id = (int)ilot_input('id', 0);
  $lotNo = ilot_input('lot_no');
  if ($lotNo === '') $lotNo = ilot_next_number(date('Y-m-d'));
  $materialCode = ilot_input('material_code');
  $materialName = ilot_input('material_name');
  $lotQty = ilot_qty(ilot_input('lot_qty'));
  $sampleQty = ilot_qty(ilot_input('sample_qty'));
  if ($materialCode === '') ilot_json('error', 'Material wajib diisi.');
  if ($lotQty <= 0) ilot_json('error', 'Lot qty wajib lebih dari 0.');
  if ($sampleQty < 0) $sampleQty = 0;
  $data = array(
    'lot_no'=>$lotNo,
    'inspection_origin'=>ilot_input('inspection_origin','MANUAL') ?: 'MANUAL',
    'inspection_type'=>ilot_input('inspection_type','01') ?: '01',
    'source_ref_type'=>ilot_input('source_ref_type'),
    'source_ref_id'=>(int)ilot_input('source_ref_id',0),
    'source_ref_no'=>ilot_input('source_ref_no'),
    'stock_layer_id'=>(int)ilot_input('stock_layer_id',0),
    'material_code'=>$materialCode,
    'material_name'=>$materialName,
    'lot_qty'=>$lotQty,
    'sample_qty'=>$sampleQty,
    'uom'=>ilot_input('uom'),
    'plant_id'=>(int)ilot_input('plant_id',0),
    'storage_location_id'=>(int)ilot_input('storage_location_id',0),
    'storage_bin_id'=>(int)ilot_input('storage_bin_id',0),
    'stock_type'=>ilot_input('stock_type','QUALITY') ?: 'QUALITY',
    'inspection_plan'=>ilot_input('inspection_plan'),
    'batch_no'=>ilot_input('batch_no'),
    'no_aju'=>ilot_input('no_aju'),
    'jenis_dokpab'=>ilot_input('jenis_dokpab'),
    'no_dokpab'=>ilot_input('no_dokpab'),
    'no_bpb'=>ilot_input('no_bpb'),
    'notes'=>ilot_input('notes')
  );
  if ($data['stock_layer_id'] <= 0) unset($data['stock_layer_id']);
  if ($data['source_ref_id'] <= 0) unset($data['source_ref_id']);
  if ($data['plant_id'] <= 0) unset($data['plant_id']);
  if ($data['storage_location_id'] <= 0) unset($data['storage_location_id']);
  if ($data['storage_bin_id'] <= 0) unset($data['storage_bin_id']);
  if ($id > 0) {
    $existing = ilot_fetch($db, $id);
    if (!$existing) ilot_json('error', 'Inspection lot tidak ditemukan.');
    if (in_array($existing->lot_status, array('UD_ACCEPTED','UD_REJECTED','UD_PARTIAL','CANCELLED'))) ilot_json('error', 'Lot yang sudah final tidak bisa diedit.');
    $data['updated_by'] = $username;
    if (!$db->update('erp_inspection_lot', $data, 'id', $id)) ilot_json('error', $db->getErrorMessage());
    ilot_json('good','',array('lot_no'=>$lotNo));
  }
  $dup = $db->fetch("SELECT id FROM erp_inspection_lot WHERE lot_no=? LIMIT 1", array($lotNo));
  if ($dup) ilot_json('error', 'Lot number sudah dipakai.');
  $data['lot_status'] = 'CREATED';
  $data['created_by'] = $username;
  if (!$db->insert('erp_inspection_lot', $data)) ilot_json('error', $db->getErrorMessage());
  $newId = (int)$db->last_insert_id();
  ilot_create_default_results($db, $newId, $sampleQty, $username);
  ilot_json('good','',array('lot_no'=>$lotNo,'id'=>$newId));
}

if ($act === 'get') {
  session_check_json();
  $row = ilot_fetch($db, (int)ilot_input('id',0));
  if (!$row) ilot_json('error','Inspection lot tidak ditemukan.');
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('status'=>'good','data'=>$row)); exit;
}

if ($act === 'start') {
  session_check_json();
  $id = (int)ilot_input('id',0);
  $row = ilot_fetch($db, $id);
  if (!$row) ilot_json('error','Inspection lot tidak ditemukan.');
  if ($row->lot_status !== 'CREATED') ilot_json('error','Hanya lot CREATED yang bisa mulai inspection.');
  if (!$db->update('erp_inspection_lot', array('lot_status'=>'IN_INSPECTION','updated_by'=>$username), 'id', $id)) ilot_json('error',$db->getErrorMessage());
  ilot_json('good');
}

if ($act === 'record_result') {
  session_check_json();
  $id = (int)ilot_input('id',0);
  $row = ilot_fetch($db, $id);
  if (!$row) ilot_json('error','Inspection lot tidak ditemukan.');
  if (in_array($row->lot_status, array('UD_ACCEPTED','UD_REJECTED','UD_PARTIAL','CANCELLED'))) ilot_json('error','Lot sudah final.');
  $charNames = isset($_POST['characteristic_name']) ? $_POST['characteristic_name'] : array();
  $db->query("DELETE FROM erp_inspection_lot_result WHERE inspection_lot_id=?", array($id));
  $fail = 0; $totalDefect = 0; $i = 0;
  foreach ($charNames as $idx => $name) {
    $name = trim((string)$name);
    if ($name === '') continue;
    $status = isset($_POST['result_status'][$idx]) ? $_POST['result_status'][$idx] : 'INFO';
    if (!in_array($status, array('PASS','FAIL','INFO'))) $status = 'INFO';
    $defectQty = ilot_qty(isset($_POST['defect_qty'][$idx]) ? $_POST['defect_qty'][$idx] : 0);
    if ($status === 'FAIL') $fail++;
    $totalDefect += $defectQty;
    $db->insert('erp_inspection_lot_result', array(
      'inspection_lot_id'=>$id,
      'characteristic_no'=>isset($_POST['characteristic_no'][$idx]) ? trim((string)$_POST['characteristic_no'][$idx]) : sprintf('%04d',($i+1)*10),
      'characteristic_name'=>$name,
      'specification'=>isset($_POST['specification'][$idx]) ? trim((string)$_POST['specification'][$idx]) : '',
      'sample_qty'=>ilot_qty(isset($_POST['sample_qty_result'][$idx]) ? $_POST['sample_qty_result'][$idx] : $row->sample_qty),
      'result_value'=>isset($_POST['result_value'][$idx]) ? trim((string)$_POST['result_value'][$idx]) : '',
      'result_status'=>$status,
      'defect_code'=>isset($_POST['defect_code'][$idx]) ? trim((string)$_POST['defect_code'][$idx]) : '',
      'defect_qty'=>$defectQty,
      'remarks'=>isset($_POST['remarks_result'][$idx]) ? trim((string)$_POST['remarks_result'][$idx]) : '',
      'recorded_by'=>$username
    ));
    $i++;
  }
  $accepted = max(0, (float)$row->lot_qty - $totalDefect);
  $newStatus = $fail > 0 ? 'RESULT_RECORDED' : 'RESULT_RECORDED';
  $db->update('erp_inspection_lot', array('lot_status'=>$newStatus,'accepted_qty'=>$accepted,'rejected_qty'=>$totalDefect,'updated_by'=>$username), 'id', $id);
  ilot_json('good');
}

if ($act === 'usage_decision') {
  session_check_json();
  $id = (int)ilot_input('id',0);
  $row = ilot_fetch($db, $id);
  if (!$row) ilot_json('error','Inspection lot tidak ditemukan.');
  if ($row->lot_status === 'CANCELLED') ilot_json('error','Lot sudah cancel.');
  $ud = ilot_input('ud_code');
  $accepted = ilot_qty(ilot_input('accepted_qty'));
  $rejected = ilot_qty(ilot_input('rejected_qty'));
  if (!in_array($ud, array('A','R','P'))) ilot_json('error','Usage decision wajib dipilih.');
  if ($accepted + $rejected > (float)$row->lot_qty + 0.00001) ilot_json('error','Accepted + rejected tidak boleh melebihi lot qty.');
  $status = $ud === 'A' ? 'UD_ACCEPTED' : ($ud === 'R' ? 'UD_REJECTED' : 'UD_PARTIAL');
  $text = $ud === 'A' ? 'Accepted' : ($ud === 'R' ? 'Rejected' : 'Partial Accepted');
  $ok = $db->update('erp_inspection_lot', array(
    'lot_status'=>$status,'ud_code'=>$ud,'ud_text'=>$text,'accepted_qty'=>$accepted,'rejected_qty'=>$rejected,
    'ud_date'=>date('Y-m-d H:i:s'),'ud_by'=>$username,'updated_by'=>$username,'notes'=>ilot_input('ud_notes', $row->notes)
  ), 'id', $id);
  if (!$ok) ilot_json('error',$db->getErrorMessage());
  ilot_json('good');
}

if ($act === 'cancel') {
  session_check_json();
  $id = (int)ilot_input('id',0);
  $row = ilot_fetch($db, $id);
  if (!$row) ilot_json('error','Inspection lot tidak ditemukan.');
  if (in_array($row->lot_status, array('UD_ACCEPTED','UD_REJECTED','UD_PARTIAL'))) ilot_json('error','Lot yang sudah UD tidak bisa cancel.');
  if (!$db->update('erp_inspection_lot', array('lot_status'=>'CANCELLED','updated_by'=>$username,'notes'=>ilot_input('reason',$row->notes)), 'id', $id)) ilot_json('error',$db->getErrorMessage());
  ilot_json('good');
}

if ($act === 'detail' || $act === 'result_form' || $act === 'ud_form') {
  session_check_json();
  $id = (int)ilot_input('id',0);
  $row = ilot_fetch($db, $id);
  if (!$row) { echo '<div class="alert alert-warning">Inspection lot tidak ditemukan.</div>'; exit; }
  $results = $db->query("SELECT * FROM erp_inspection_lot_result WHERE inspection_lot_id=? ORDER BY characteristic_no,id", array($id));
  if ($act === 'result_form') {
    ?>
    <form id="form_ilot_result">
      <input type="hidden" name="id" value="<?=intval($row->id);?>">
      <div class="alert alert-info"><strong><?=ilot_h($row->lot_no);?></strong> - record inspection result per characteristic. Status FAIL akan masuk sebagai exception untuk usage decision.</div>
      <div class="table-responsive"><table class="table table-bordered table-condensed" id="tbl_ilot_result"><thead><tr class="bg-gray"><th>No</th><th>Characteristic</th><th>Specification</th><th class="text-right">Sample</th><th>Result</th><th>Status</th><th>Defect Code</th><th class="text-right">Defect Qty</th><th>Remarks</th><th></th></tr></thead><tbody>
      <?php $i=0; foreach($results as $res){ $i++; ?>
        <tr><td><input name="characteristic_no[]" class="form-control input-sm" value="<?=ilot_h($res->characteristic_no);?>"></td><td><input name="characteristic_name[]" class="form-control input-sm" value="<?=ilot_h($res->characteristic_name);?>"></td><td><input name="specification[]" class="form-control input-sm" value="<?=ilot_h($res->specification);?>"></td><td><input name="sample_qty_result[]" class="form-control input-sm text-right ilot-dec" value="<?=ilot_h($res->sample_qty);?>"></td><td><input name="result_value[]" class="form-control input-sm" value="<?=ilot_h($res->result_value);?>"></td><td><select name="result_status[]" class="form-control input-sm"><option value="PASS" <?=$res->result_status==='PASS'?'selected':'';?>>PASS</option><option value="FAIL" <?=$res->result_status==='FAIL'?'selected':'';?>>FAIL</option><option value="INFO" <?=$res->result_status==='INFO'?'selected':'';?>>INFO</option></select></td><td><input name="defect_code[]" class="form-control input-sm" value="<?=ilot_h($res->defect_code);?>"></td><td><input name="defect_qty[]" class="form-control input-sm text-right ilot-dec" value="<?=ilot_h($res->defect_qty);?>"></td><td><input name="remarks_result[]" class="form-control input-sm" value="<?=ilot_h($res->remarks);?>"></td><td><button type="button" class="btn btn-danger btn-xs btn-remove-result"><i class="fa fa-trash"></i></button></td></tr>
      <?php } ?>
      </tbody></table></div>
      <button type="button" class="btn btn-default btn-sm" id="btn_add_result_row"><i class="fa fa-plus"></i> Add Characteristic</button>
    </form>
    <?php exit;
  }
  if ($act === 'ud_form') {
    $failCount = 0; foreach($results as $res) if ($res->result_status === 'FAIL') $failCount++;
    ?>
    <form id="form_ilot_ud">
      <input type="hidden" name="id" value="<?=intval($row->id);?>">
      <div class="alert alert-<?=($failCount>0?'warning':'success');?>"><strong><?=ilot_h($row->lot_no);?></strong> memiliki <?=intval($failCount);?> failed characteristic. Tentukan usage decision akhir sesuai hasil inspection.</div>
      <div class="form-group"><label>Usage Decision</label><select name="ud_code" id="ud_code" class="form-control"><option value="">Pilih</option><option value="A">Accept</option><option value="R">Reject</option><option value="P">Partial Accept</option></select></div>
      <div class="row"><div class="col-sm-4"><label>Lot Qty</label><input class="form-control text-right" value="<?=ilot_h($row->lot_qty);?>" readonly></div><div class="col-sm-4"><label>Accepted Qty</label><input name="accepted_qty" id="accepted_qty" class="form-control text-right" value="<?=ilot_h($row->accepted_qty ?: $row->lot_qty);?>"></div><div class="col-sm-4"><label>Rejected Qty</label><input name="rejected_qty" id="rejected_qty" class="form-control text-right" value="<?=ilot_h($row->rejected_qty);?>"></div></div>
      <div class="form-group" style="margin-top:12px"><label>Notes</label><textarea name="ud_notes" class="form-control" rows="3"><?=ilot_h($row->notes);?></textarea></div>
    </form>
    <?php exit;
  }
  ?>
  <div class="row"><div class="col-md-8"><h3 style="margin-top:0"><?=ilot_h($row->lot_no);?> <small><?=ilot_h(ilot_origin_label($row->inspection_origin));?></small></h3><p class="text-muted"><?=ilot_h($row->material_code.' - '.$row->material_name);?></p></div><div class="col-md-4 text-right"><?=ilot_status_badge($row->lot_status);?></div></div>
  <div class="row">
    <div class="col-sm-3"><strong>Lot Qty</strong><br><?=ilot_num($row->lot_qty).' '.ilot_h($row->uom);?></div>
    <div class="col-sm-3"><strong>Sample Qty</strong><br><?=ilot_num($row->sample_qty).' '.ilot_h($row->uom);?></div>
    <div class="col-sm-3"><strong>Accepted / Rejected</strong><br><?=ilot_num($row->accepted_qty).' / '.ilot_num($row->rejected_qty);?></div>
    <div class="col-sm-3"><strong>Location</strong><br><?=ilot_h(ilot_location_text($row) ?: '-');?></div>
  </div><hr>
  <div class="row"><div class="col-sm-3"><strong>Source</strong><br><?=ilot_h(trim((string)$row->source_ref_type.' #'.(string)$row->source_ref_id.' / '.(string)$row->source_ref_no, ' #/'));?></div><div class="col-sm-3"><strong>Stock Layer</strong><br>#<?=intval($row->stock_layer_id);?></div><div class="col-sm-3"><strong>No Aju</strong><br><?=ilot_h($row->no_aju ?: '-');?></div><div class="col-sm-3"><strong>Dokumen BC</strong><br><?=ilot_h(trim((string)$row->jenis_dokpab.' '.(string)$row->no_dokpab) ?: '-');?></div></div>
  <hr><h4>Inspection Results</h4><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>No</th><th>Characteristic</th><th>Spec</th><th class="text-right">Sample</th><th>Result</th><th>Status</th><th>Defect</th><th>Remarks</th></tr></thead><tbody>
  <?php foreach($results as $res){ ?>
    <tr><td><?=ilot_h($res->characteristic_no);?></td><td><?=ilot_h($res->characteristic_name);?></td><td><?=ilot_h($res->specification);?></td><td class="text-right"><?=ilot_num($res->sample_qty);?></td><td><?=ilot_h($res->result_value);?></td><td><?=ilot_status_badge($res->result_status);?></td><td><?=ilot_h($res->defect_code).' / '.ilot_num($res->defect_qty);?></td><td><?=ilot_h($res->remarks);?></td></tr>
  <?php } ?>
  </tbody></table></div>
  <?php
  exit;
}

if ($act === 'excel') {
  $initial = ob_get_level(); ob_start();
  ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php'; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $filters = ilot_filters(); $rows = ilot_load_rows($db, $filters);
  $excel = new PHPExcel(); $sheet = $excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('Inspection Lot'));
  $heads = array(erp_export_label("No"),erp_export_label("Lot No"),erp_export_label("Origin"),erp_export_label("Inspection Type"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Lot Qty"),erp_export_label("Sample Qty"),erp_export_label("Accepted Qty"),erp_export_label("Rejected Qty"),erp_export_label("UOM"),erp_export_label("Location"),erp_export_label("Stock Type"),erp_export_label("Status"),erp_export_label("Source Ref"),erp_export_label("No Aju"),erp_export_label("Dokumen BC"),erp_export_label("No BPB"),erp_export_label("Created At"),erp_export_label("Created By"),erp_export_label("UD By"),erp_export_label("UD Date"),erp_export_label("Notes"));
  foreach($heads as $c=>$h) $sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5; $n=1; foreach($rows as $row){ $vals=array($n++,$row->lot_no,ilot_origin_label($row->inspection_origin),$row->inspection_type,$row->material_code,$row->material_name,(float)$row->lot_qty,(float)$row->sample_qty,(float)$row->accepted_qty,(float)$row->rejected_qty,$row->uom,ilot_location_text($row),$row->stock_type,$row->lot_status,trim($row->source_ref_type.' #'.$row->source_ref_id.' / '.$row->source_ref_no,' #/'),$row->no_aju,trim($row->jenis_dokpab.' '.$row->no_dokpab),$row->no_bpb,$row->created_at,$row->created_by,$row->ud_by,$row->ud_date,$row->notes); foreach($vals as $c=>$v)$sheet->setCellValueByColumnAndRow($c,$r,$v); $r++; }
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('INSPECTION LOT - SAP QM'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>23,'numeric_columns'=>array('G','H','I','J'),'filters'=>array('Periode'=>$filters['tgl_awal'].' s/d '.$filters['tgl_akhir'],'Status'=>$filters['lot_status'],'Origin'=>$filters['inspection_origin'],'Material'=>$filters['material_code'],'Keyword'=>$filters['keyword'])));
  $tmp=erpkb_excel_temp_file('inspection_lot_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp); $size=@filesize($tmp); $sig=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$sig!=='PK'){@unlink($tmp); while(ob_get_level()>$initial)ob_end_clean(); header('Content-Type:text/plain; charset=utf-8'); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit;}
  while(ob_get_level()>$initial)ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="inspection_lot_'.$filters['tgl_awal'].'_sd_'.$filters['tgl_akhir'].'.xlsx"'); header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;
}

ilot_json('error','Action tidak dikenal.');
?>
