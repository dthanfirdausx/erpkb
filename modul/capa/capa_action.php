<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
include "capa_lib.php";

$act = isset($_GET['act']) ? $_GET['act'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

if ($act === 'next_no') {
  session_check_json();
  ilot_json('good','',array('capa_no'=>capa_next_number(date('Y-m-d'))));
}

if ($act === 'qn_candidate') {
  session_check_json();
  $rows = capa_qn_candidates($db, capa_input('term'));
  $results = array();
  foreach ($rows as $row) {
    $results[] = array(
      'id'=>$row->id,
      'text'=>$row->notification_no.' | '.$row->material_code.' - '.$row->material_name.' | '.$row->severity.' | '.$row->defect_category,
      'notification_no'=>$row->notification_no,
      'material_code'=>$row->material_code,
      'material_name'=>$row->material_name,
      'defect_category'=>$row->defect_category,
      'defect_code'=>$row->defect_code,
      'problem_statement'=>$row->defect_description,
      'root_cause'=>$row->root_cause,
      'correction_action'=>$row->containment_action,
      'corrective_action'=>$row->corrective_action,
      'preventive_action'=>$row->preventive_action,
      'owner_user'=>$row->responsible_user,
      'risk_level'=>$row->severity === 'CRITICAL' ? 'CRITICAL' : ($row->severity === 'HIGH' ? 'HIGH' : 'MEDIUM'),
      'priority'=>$row->priority
    );
  }
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('results'=>$results)); exit;
}

if ($act === 'save') {
  session_check_json();
  $id = (int)capa_input('id',0);
  $capaNo = capa_input('capa_no') ?: capa_next_number(date('Y-m-d'));
  $problem = capa_input('problem_statement');
  if ($problem === '') ilot_json('error','Problem statement wajib diisi.');
  $data = array(
    'capa_no'=>$capaNo,
    'capa_type'=>capa_input('capa_type','BOTH') ?: 'BOTH',
    'source_type'=>capa_input('source_type','MANUAL') ?: 'MANUAL',
    'notification_id'=>(int)capa_input('notification_id',0),
    'notification_no'=>capa_input('notification_no'),
    'material_code'=>capa_input('material_code'),
    'material_name'=>capa_input('material_name'),
    'defect_category'=>capa_input('defect_category'),
    'defect_code'=>capa_input('defect_code'),
    'problem_statement'=>$problem,
    'root_cause'=>capa_input('root_cause'),
    'correction_action'=>capa_input('correction_action'),
    'corrective_action'=>capa_input('corrective_action'),
    'preventive_action'=>capa_input('preventive_action'),
    'verification_plan'=>capa_input('verification_plan'),
    'effectiveness_result'=>capa_input('effectiveness_result'),
    'owner_user'=>capa_input('owner_user'),
    'approver_user'=>capa_input('approver_user'),
    'start_date'=>capa_valid_date(capa_input('start_date')),
    'due_date'=>capa_valid_date(capa_input('due_date')),
    'verification_date'=>capa_valid_date(capa_input('verification_date')),
    'priority'=>capa_input('priority','NORMAL') ?: 'NORMAL',
    'risk_level'=>capa_input('risk_level','MEDIUM') ?: 'MEDIUM',
    'status'=>capa_input('status','OPEN') ?: 'OPEN'
  );
  foreach (array('notification_id') as $field) if ($data[$field] <= 0) unset($data[$field]);
  foreach (array('start_date','due_date','verification_date') as $field) if ($data[$field] === null) unset($data[$field]);
  if (in_array($data['status'], array('CLOSED','EFFECTIVE'), true)) { $data['closed_by']=$username; $data['closed_at']=date('Y-m-d H:i:s'); }
  if ($id > 0) {
    $old = capa_fetch($db,$id);
    if (!$old) ilot_json('error','CAPA tidak ditemukan.');
    $data['updated_by'] = $username;
    if (!$db->update('erp_capa',$data,'id',$id)) ilot_json('error',$db->getErrorMessage());
    capa_insert_action($db,$id,'COMMENT','CAPA updated.',$username);
    capa_sync_notification_status($db, isset($data['notification_id'])?$data['notification_id']:$old->notification_id, $data['status'], $username);
    ilot_json('good','',array('capa_no'=>$capaNo,'id'=>$id));
  }
  if ($db->fetch("SELECT id FROM erp_capa WHERE capa_no=? LIMIT 1", array($capaNo))) ilot_json('error','Nomor CAPA sudah dipakai.');
  $data['created_by'] = $username;
  if (!$db->insert('erp_capa',$data)) ilot_json('error',$db->getErrorMessage());
  $newId = (int)$db->last_insert_id();
  capa_insert_action($db,$newId,'STATUS','CAPA created with status '.$data['status'],$username);
  capa_sync_notification_status($db, isset($data['notification_id'])?$data['notification_id']:0, $data['status'], $username);
  ilot_json('good','',array('capa_no'=>$capaNo,'id'=>$newId));
}

if ($act === 'get') {
  session_check_json();
  $row = capa_fetch($db,(int)capa_input('id',0));
  if (!$row) ilot_json('error','CAPA tidak ditemukan.');
  header('Content-Type: application/json; charset=utf-8'); echo json_encode(array('status'=>'good','data'=>$row)); exit;
}

if ($act === 'post_action') {
  session_check_json();
  $id = (int)capa_input('id',0);
  $row = capa_fetch($db,$id);
  if (!$row) ilot_json('error','CAPA tidak ditemukan.');
  $status = capa_input('status',$row->status);
  $text = capa_input('action_text');
  if ($text === '') ilot_json('error','Action text wajib diisi.');
  $type = $status === 'WAITING_VERIFICATION' || $status === 'EFFECTIVE' || $status === 'INEFFECTIVE' ? 'VERIFICATION' : 'STATUS';
  $update = array('status'=>$status,'updated_by'=>$username);
  if (in_array($status, array('CLOSED','EFFECTIVE'), true)) { $update['closed_by']=$username; $update['closed_at']=date('Y-m-d H:i:s'); }
  if ($status === 'EFFECTIVE' || $status === 'INEFFECTIVE') { $update['effectiveness_result']=$text; $update['verification_date']=date('Y-m-d'); }
  if (!$db->update('erp_capa',$update,'id',$id)) ilot_json('error',$db->getErrorMessage());
  capa_insert_action($db,$id,$type,$text,$username);
  capa_sync_notification_status($db, $row->notification_id, $status, $username);
  ilot_json('good');
}

if ($act === 'cancel') {
  session_check_json();
  $id = (int)capa_input('id',0);
  $row = capa_fetch($db,$id);
  if (!$row) ilot_json('error','CAPA tidak ditemukan.');
  if (!$db->update('erp_capa', array('status'=>'CANCELLED','updated_by'=>$username), 'id', $id)) ilot_json('error',$db->getErrorMessage());
  capa_insert_action($db,$id,'STATUS','CAPA cancelled.',$username);
  capa_sync_notification_status($db, $row->notification_id, 'CANCELLED', $username);
  ilot_json('good');
}

if ($act === 'detail' || $act === 'action_form') {
  session_check_json();
  $id = (int)capa_input('id',0);
  $row = capa_fetch($db,$id);
  if (!$row) { echo '<div class="alert alert-warning">CAPA tidak ditemukan.</div>'; exit; }
  if ($act === 'action_form') {
    ?>
    <form id="form_capa_action"><input type="hidden" name="id" value="<?=intval($row->id);?>"><div class="alert alert-info"><strong><?=capa_h($row->capa_no);?></strong> - update action, verification, atau effectiveness CAPA.</div><div class="form-group"><label>Status</label><select name="status" id="capa_action_status" class="form-control"><option <?=$row->status==='OPEN'?'selected':'';?>>OPEN</option><option <?=$row->status==='IN_PROGRESS'?'selected':'';?>>IN_PROGRESS</option><option <?=$row->status==='WAITING_VERIFICATION'?'selected':'';?>>WAITING_VERIFICATION</option><option <?=$row->status==='EFFECTIVE'?'selected':'';?>>EFFECTIVE</option><option <?=$row->status==='INEFFECTIVE'?'selected':'';?>>INEFFECTIVE</option><option <?=$row->status==='CLOSED'?'selected':'';?>>CLOSED</option><option <?=$row->status==='CANCELLED'?'selected':'';?>>CANCELLED</option></select></div><div class="form-group"><label>Action / Verification Note</label><textarea name="action_text" class="form-control" rows="4" placeholder="Tuliskan progress, hasil verifikasi, evidence, atau alasan status"></textarea></div></form>
    <?php exit;
  }
  $actions = $db->query("SELECT * FROM erp_capa_action WHERE capa_id=? ORDER BY action_at DESC,id DESC", array($id));
  ?>
  <div class="row"><div class="col-md-8"><h3 style="margin-top:0"><?=capa_h($row->capa_no);?> <small><?=capa_h($row->capa_type.' / '.$row->source_type);?></small></h3><p class="text-muted">NCR: <?=capa_h($row->notification_no ?: '-');?> | <?=capa_h($row->material_code.' - '.$row->material_name);?></p></div><div class="col-md-4 text-right"><?=capa_status_badge($row->status);?> <?=capa_risk_badge($row->risk_level);?></div></div>
  <div class="row"><div class="col-sm-3"><strong>Owner</strong><br><?=capa_h($row->owner_user ?: '-');?></div><div class="col-sm-3"><strong>Approver</strong><br><?=capa_h($row->approver_user ?: '-');?></div><div class="col-sm-3"><strong>Due Date</strong><br><?=capa_h($row->due_date ?: '-');?></div><div class="col-sm-3"><strong>Verification</strong><br><?=capa_h($row->verification_date ?: '-');?></div></div><hr>
  <div class="row"><div class="col-md-6"><h4>Problem & Root Cause</h4><table class="table table-bordered table-condensed"><tr><th style="width:160px;background:#f8fafc">Category</th><td><?=capa_h($row->defect_category ?: '-');?></td></tr><tr><th style="background:#f8fafc">Code</th><td><?=capa_h($row->defect_code ?: '-');?></td></tr><tr><th style="background:#f8fafc">Problem</th><td><?=nl2br(capa_h($row->problem_statement));?></td></tr><tr><th style="background:#f8fafc">Root Cause</th><td><?=nl2br(capa_h($row->root_cause));?></td></tr></table></div><div class="col-md-6"><h4>CAPA Plan</h4><table class="table table-bordered table-condensed"><tr><th style="width:160px;background:#f8fafc">Correction</th><td><?=nl2br(capa_h($row->correction_action));?></td></tr><tr><th style="background:#f8fafc">Corrective</th><td><?=nl2br(capa_h($row->corrective_action));?></td></tr><tr><th style="background:#f8fafc">Preventive</th><td><?=nl2br(capa_h($row->preventive_action));?></td></tr><tr><th style="background:#f8fafc">Verification Plan</th><td><?=nl2br(capa_h($row->verification_plan));?></td></tr><tr><th style="background:#f8fafc">Effectiveness</th><td><?=nl2br(capa_h($row->effectiveness_result));?></td></tr></table></div></div>
  <h4>Action Log</h4><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Date</th><th>Type</th><th>Action</th><th>By</th></tr></thead><tbody><?php $count=0; foreach($actions as $a){ $count++; ?><tr><td><?=capa_h($a->action_at);?></td><td><?=capa_h($a->action_type);?></td><td><?=nl2br(capa_h($a->action_text));?></td><td><?=capa_h($a->action_by);?></td></tr><?php } if($count===0){ ?><tr><td colspan="4" class="text-center text-muted">Belum ada action.</td></tr><?php } ?></tbody></table></div>
  <?php exit;
}

if ($act === 'excel') {
  $initial = ob_get_level(); ob_start();
  ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php'; require_once '../../inc/excel_style_helper.php'; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $filters = capa_filters(); $rows = capa_load_rows($db,$filters);
  $excel = new PHPExcel(); $sheet = $excel->setActiveSheetIndex(0); $sheet->setTitle(erp_export_sheet_title('CAPA'));
  $heads = array(erp_export_label("No"),erp_export_label("CAPA No"),erp_export_label("Type"),erp_export_label("Source"),erp_export_label("NCR"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Risk"),erp_export_label("Priority"),erp_export_label("Status"),erp_export_label("Owner"),erp_export_label("Approver"),erp_export_label("Start"),erp_export_label("Due"),erp_export_label("Verification"),erp_export_label("Closed At"),erp_export_label("Created At"),erp_export_label("Created By"),erp_export_label("Category"),erp_export_label("Defect Code"),erp_export_label("Problem"),erp_export_label("Root Cause"),erp_export_label("Correction"),erp_export_label("Corrective"),erp_export_label("Preventive"),erp_export_label("Verification Plan"),erp_export_label("Effectiveness"));
  foreach($heads as $c=>$h) $sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5; $n=1; foreach($rows as $row){$vals=array($n++,$row->capa_no,$row->capa_type,$row->source_type,$row->notification_no,$row->material_code,$row->material_name,$row->risk_level,$row->priority,$row->status,$row->owner_user,$row->approver_user,$row->start_date,$row->due_date,$row->verification_date,$row->closed_at,$row->created_at,$row->created_by,$row->defect_category,$row->defect_code,$row->problem_statement,$row->root_cause,$row->correction_action,$row->corrective_action,$row->preventive_action,$row->verification_plan,$row->effectiveness_result); foreach($vals as $c=>$v)$sheet->setCellValueByColumnAndRow($c,$r,$v); $r++;}
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('CAPA REPORT - SAP QM'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>27,'filters'=>array('Periode'=>$filters['tgl_awal'].' s/d '.$filters['tgl_akhir'],'Status'=>$filters['status'],'Risk'=>$filters['risk_level'],'Owner'=>$filters['owner_user'],'Source'=>$filters['source_type'],'Keyword'=>$filters['keyword'])));
  $tmp=erpkb_excel_temp_file('capa_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp); $size=@filesize($tmp); $sig=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
  while(ob_get_level()>$initial)ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="capa_'.$filters['tgl_awal'].'_sd_'.$filters['tgl_akhir'].'.xlsx"'); header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;
}

session_check_json();
ilot_json('error','Aksi CAPA tidak dikenal.');
?>
