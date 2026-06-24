<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "quality_notification_lib.php";

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

if ($act === 'material_search') {
  session_check_json();
  $rows = qn_material_search($db, qn_input('term'));
  $results = array();
  foreach ($rows as $row) $results[] = array('id'=>$row->kd_barang,'text'=>$row->kd_barang.' - '.$row->nm_barang.' | '.$row->satuan,'name'=>$row->nm_barang,'uom'=>$row->satuan);
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('results'=>$results)); exit;
}

if ($act === 'inspection_candidate') {
  session_check_json();
  $rows = qn_failed_inspection_candidates($db, qn_input('term'));
  $results = array();
  foreach ($rows as $row) {
    $results[] = array(
      'id'=>$row->result_id,
      'text'=>$row->lot_no.' | '.$row->material_code.' - '.$row->material_name.' | '.$row->characteristic_name.' | Defect '.qn_num($row->defect_qty).' '.$row->uom,
      'inspection_lot_id'=>$row->inspection_lot_id,'source_ref_no'=>$row->lot_no,'material_code'=>$row->material_code,'material_name'=>$row->material_name,'defect_qty'=>$row->defect_qty,'uom'=>$row->uom,'defect_code'=>$row->defect_code,'defect_description'=>$row->characteristic_name.' - '.$row->remarks,'plant_id'=>$row->plant_id,'storage_location_id'=>$row->storage_location_id,'storage_bin_id'=>$row->storage_bin_id,'no_aju'=>$row->no_aju,'jenis_dokpab'=>$row->jenis_dokpab,'no_dokpab'=>$row->no_dokpab,'no_bpb'=>$row->no_bpb
    );
  }
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('results'=>$results)); exit;
}

if ($act === 'ng_candidate') {
  session_check_json();
  $rows = qn_ng_candidates($db, qn_input('term'));
  $results = array();
  foreach ($rows as $row) {
    $results[] = array('id'=>$row->id,'text'=>'NG #'.$row->id.' | '.$row->kd_barang.' - '.$row->nm_barang.' | '.qn_num($row->jumlah).' '.$row->satuan.' | '.$row->tgl_produksi,'material_code'=>$row->kd_barang,'material_name'=>$row->nm_barang,'defect_qty'=>$row->jumlah,'uom'=>$row->satuan,'defect_code'=>$row->ket,'defect_description'=>trim($row->ket.' '.$row->catatan),'source_ref_no'=>'NG #'.$row->id_ng);
  }
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('results'=>$results)); exit;
}

if ($act === 'next_no') {
  session_check_json();
  ilot_json('good','',array('notification_no'=>qn_next_number(date('Y-m-d'))));
}

if ($act === 'save') {
  session_check_json();
  $id = (int)qn_input('id',0);
  $notificationNo = qn_input('notification_no') ?: qn_next_number(date('Y-m-d'));
  $materialCode = qn_input('material_code');
  if ($materialCode === '') ilot_json('error','Material wajib diisi.');
  $data = array(
    'notification_no'=>$notificationNo,
    'notification_type'=>qn_input('notification_type','NCR') ?: 'NCR',
    'source_type'=>qn_input('source_type','MANUAL') ?: 'MANUAL',
    'source_ref_id'=>(int)qn_input('source_ref_id',0),
    'source_ref_no'=>qn_input('source_ref_no'),
    'inspection_lot_id'=>(int)qn_input('inspection_lot_id',0),
    'material_code'=>$materialCode,
    'material_name'=>qn_input('material_name'),
    'defect_qty'=>qn_qty(qn_input('defect_qty')),
    'uom'=>qn_input('uom'),
    'severity'=>qn_input('severity','MEDIUM') ?: 'MEDIUM',
    'priority'=>qn_input('priority','NORMAL') ?: 'NORMAL',
    'defect_category'=>qn_input('defect_category'),
    'defect_code'=>qn_input('defect_code'),
    'defect_description'=>qn_input('defect_description'),
    'containment_action'=>qn_input('containment_action'),
    'root_cause'=>qn_input('root_cause'),
    'corrective_action'=>qn_input('corrective_action'),
    'preventive_action'=>qn_input('preventive_action'),
    'responsible_user'=>qn_input('responsible_user'),
    'due_date'=>qn_valid_date(qn_input('due_date'), null),
    'status'=>qn_input('status','OPEN') ?: 'OPEN',
    'plant_id'=>(int)qn_input('plant_id',0),
    'storage_location_id'=>(int)qn_input('storage_location_id',0),
    'storage_bin_id'=>(int)qn_input('storage_bin_id',0),
    'no_aju'=>qn_input('no_aju'),
    'jenis_dokpab'=>qn_input('jenis_dokpab'),
    'no_dokpab'=>qn_input('no_dokpab'),
    'no_bpb'=>qn_input('no_bpb')
  );
  foreach (array('source_ref_id','inspection_lot_id','plant_id','storage_location_id','storage_bin_id') as $field) if ($data[$field] <= 0) unset($data[$field]);
  if ($data['due_date'] === null) unset($data['due_date']);
  if ($id > 0) {
    if (!qn_fetch($db,$id)) ilot_json('error','NCR tidak ditemukan.');
    $data['updated_by'] = $username;
    if (!$db->update('erp_quality_notification',$data,'id',$id)) ilot_json('error',$db->getErrorMessage());
    qn_insert_action($db,$id,'COMMENT','NCR updated.',$username);
    ilot_json('good','',array('notification_no'=>$notificationNo,'id'=>$id));
  }
  if ($db->fetch("SELECT id FROM erp_quality_notification WHERE notification_no=? LIMIT 1", array($notificationNo))) ilot_json('error','Nomor notification sudah dipakai.');
  $data['created_by'] = $username;
  if (!$db->insert('erp_quality_notification',$data)) ilot_json('error',$db->getErrorMessage());
  $newId = (int)$db->last_insert_id();
  qn_insert_action($db,$newId,'STATUS','NCR created with status '.$data['status'],$username);
  ilot_json('good','',array('notification_no'=>$notificationNo,'id'=>$newId));
}

if ($act === 'get') {
  session_check_json();
  $row = qn_fetch($db,(int)qn_input('id',0));
  if (!$row) ilot_json('error','NCR tidak ditemukan.');
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('status'=>'good','data'=>$row)); exit;
}

if ($act === 'post_action') {
  session_check_json();
  $id = (int)qn_input('id',0);
  $row = qn_fetch($db,$id);
  if (!$row) ilot_json('error','NCR tidak ditemukan.');
  $status = qn_input('status',$row->status);
  $text = qn_input('action_text');
  if ($text === '') ilot_json('error','Action text wajib diisi.');
  $update = array('status'=>$status,'updated_by'=>$username);
  if ($status === 'CLOSED') { $update['closed_by']=$username; $update['closed_at']=date('Y-m-d H:i:s'); }
  if (!$db->update('erp_quality_notification',$update,'id',$id)) ilot_json('error',$db->getErrorMessage());
  qn_insert_action($db,$id,'STATUS',$text,$username);
  ilot_json('good');
}

if ($act === 'detail' || $act === 'action_form') {
  session_check_json();
  $id = (int)qn_input('id',0);
  $row = qn_fetch($db,$id);
  if (!$row) { echo '<div class="alert alert-warning">NCR tidak ditemukan.</div>'; exit; }
  if ($act === 'action_form') {
    ?>
    <form id="form_qn_action"><input type="hidden" name="id" value="<?=intval($row->id);?>"><div class="alert alert-info"><strong><?=ilot_h($row->notification_no);?></strong> - update workflow status dan catat action NCR.</div><div class="form-group"><label>Status</label><select name="status" id="qn_action_status" class="form-control"><option <?=$row->status==='OPEN'?'selected':'';?>>OPEN</option><option <?=$row->status==='IN_REVIEW'?'selected':'';?>>IN_REVIEW</option><option <?=$row->status==='CONTAINED'?'selected':'';?>>CONTAINED</option><option <?=$row->status==='CAPA_REQUIRED'?'selected':'';?>>CAPA_REQUIRED</option><option <?=$row->status==='CAPA_IN_PROGRESS'?'selected':'';?>>CAPA_IN_PROGRESS</option><option <?=$row->status==='CLOSED'?'selected':'';?>>CLOSED</option><option <?=$row->status==='CANCELLED'?'selected':'';?>>CANCELLED</option></select></div><div class="form-group"><label>Action / Comment</label><textarea name="action_text" class="form-control" rows="4" placeholder="Tuliskan containment, review, CAPA note, atau alasan close/cancel"></textarea></div></form>
    <?php exit;
  }
  $actions = $db->query("SELECT * FROM erp_quality_notification_action WHERE notification_id=? ORDER BY action_at DESC,id DESC", array($id));
  $location = trim((string)$row->plant_code.' / '.(string)$row->storage_code.' / '.(string)$row->bin_code, ' /');
  ?>
  <div class="row"><div class="col-md-8"><h3 style="margin-top:0"><?=ilot_h($row->notification_no);?> <small><?=ilot_h($row->notification_type.' / '.$row->source_type);?></small></h3><p class="text-muted"><?=ilot_h($row->material_code.' - '.$row->material_name);?></p></div><div class="col-md-4 text-right"><?=qn_status_badge($row->status);?> <?=qn_severity_badge($row->severity);?></div></div>
  <div class="row"><div class="col-sm-3"><strong>Defect Qty</strong><br><?=qn_num($row->defect_qty).' '.ilot_h($row->uom);?></div><div class="col-sm-3"><strong>Source</strong><br><?=ilot_h($row->source_ref_no ?: $row->lot_no ?: '-');?></div><div class="col-sm-3"><strong>Responsible</strong><br><?=ilot_h($row->responsible_user ?: '-');?></div><div class="col-sm-3"><strong>Due Date</strong><br><?=ilot_h($row->due_date ?: '-');?></div></div><hr>
  <div class="row"><div class="col-sm-3"><strong>Location</strong><br><?=ilot_h($location ?: '-');?></div><div class="col-sm-3"><strong>No BPB</strong><br><?=ilot_h($row->no_bpb ?: '-');?></div><div class="col-sm-3"><strong>No Aju</strong><br><?=ilot_h($row->no_aju ?: '-');?></div><div class="col-sm-3"><strong>Dokumen BC</strong><br><?=ilot_h(trim($row->jenis_dokpab.' '.$row->no_dokpab) ?: '-');?></div></div><hr>
  <div class="row"><div class="col-md-6"><h4>Problem</h4><table class="table table-bordered table-condensed"><tr><th style="width:160px;background:#f8fafc">Category</th><td><?=ilot_h($row->defect_category ?: '-');?></td></tr><tr><th style="background:#f8fafc">Code</th><td><?=ilot_h($row->defect_code ?: '-');?></td></tr><tr><th style="background:#f8fafc">Description</th><td><?=nl2br(ilot_h($row->defect_description));?></td></tr></table></div><div class="col-md-6"><h4>CAPA Summary</h4><table class="table table-bordered table-condensed"><tr><th style="width:160px;background:#f8fafc">Containment</th><td><?=nl2br(ilot_h($row->containment_action));?></td></tr><tr><th style="background:#f8fafc">Root Cause</th><td><?=nl2br(ilot_h($row->root_cause));?></td></tr><tr><th style="background:#f8fafc">Corrective</th><td><?=nl2br(ilot_h($row->corrective_action));?></td></tr><tr><th style="background:#f8fafc">Preventive</th><td><?=nl2br(ilot_h($row->preventive_action));?></td></tr></table></div></div>
  <h4>Action Log</h4><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Date</th><th>Type</th><th>Action</th><th>By</th></tr></thead><tbody><?php $count=0; foreach($actions as $a){ $count++; ?><tr><td><?=ilot_h($a->action_at);?></td><td><?=ilot_h($a->action_type);?></td><td><?=nl2br(ilot_h($a->action_text));?></td><td><?=ilot_h($a->action_by);?></td></tr><?php } if($count===0){ ?><tr><td colspan="4" class="text-center text-muted">Belum ada action.</td></tr><?php } ?></tbody></table></div>
  <?php
  exit;
}

if ($act === 'excel') {
  $initial = ob_get_level(); ob_start();
  ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php'; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $filters = qn_filters(); $rows = qn_load_rows($db,$filters);
  $excel = new PHPExcel(); $sheet = $excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('Quality NCR'));
  $heads = array(erp_export_label("No"),erp_export_label("Notification No"),erp_export_label("Type"),erp_export_label("Source"),erp_export_label("Source Ref"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Defect Qty"),erp_export_label("UOM"),erp_export_label("Severity"),erp_export_label("Priority"),erp_export_label("Status"),erp_export_label("Category"),erp_export_label("Defect Code"),erp_export_label("Responsible"),erp_export_label("Due Date"),erp_export_label("No Aju"),erp_export_label("Dokumen BC"),erp_export_label("No BPB"),erp_export_label("Created At"),erp_export_label("Created By"),erp_export_label("Description"),erp_export_label("Containment"),erp_export_label("Root Cause"),erp_export_label("Corrective"),erp_export_label("Preventive"));
  foreach($heads as $c=>$h) $sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5; $n=1; foreach($rows as $row){$vals=array($n++,$row->notification_no,$row->notification_type,$row->source_type,$row->source_ref_no,$row->material_code,$row->material_name,(float)$row->defect_qty,$row->uom,$row->severity,$row->priority,$row->status,$row->defect_category,$row->defect_code,$row->responsible_user,$row->due_date,$row->no_aju,trim($row->jenis_dokpab.' '.$row->no_dokpab),$row->no_bpb,$row->created_at,$row->created_by,$row->defect_description,$row->containment_action,$row->root_cause,$row->corrective_action,$row->preventive_action); foreach($vals as $c=>$v)$sheet->setCellValueByColumnAndRow($c,$r,$v); $r++;}
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('QUALITY NOTIFICATION / NCR - SAP QM'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>26,'numeric_columns'=>array('H'),'filters'=>array('Periode'=>$filters['tgl_awal'].' s/d '.$filters['tgl_akhir'],'Status'=>$filters['status'],'Severity'=>$filters['severity'],'Source'=>$filters['source_type'],'Material'=>$filters['material_code'],'Keyword'=>$filters['keyword'])));
  $tmp=erpkb_excel_temp_file('quality_notification_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp); $size=@filesize($tmp); $sig=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$sig!=='PK'){@unlink($tmp); while(ob_get_level()>$initial)ob_end_clean(); header('Content-Type:text/plain; charset=utf-8'); echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.'); exit;}
  while(ob_get_level()>$initial)ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="quality_notification_'.$filters['tgl_awal'].'_sd_'.$filters['tgl_akhir'].'.xlsx"'); header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;
}

ilot_json('error','Action tidak dikenal.');
?>
