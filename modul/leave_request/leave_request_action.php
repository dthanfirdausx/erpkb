<?php
if (!function_exists('hr_t')) {
  function hr_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('hr_h')) {
  function hr_h($key, $fallback = '') { return htmlspecialchars((string) hr_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('hr_js')) {
  function hr_js($key, $fallback = '') { return json_encode(hr_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
session_check_json();

function lr_json($s,$m='',$x=array()){header('Content-Type: application/json; charset=utf-8');$p=array('status'=>$s);if($m!=='')$p[$s==='good'?'message':'error_message']=$m;foreach($x as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function lr_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function lr_p($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function lr_g($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
function lr_c($v){return strtoupper(trim((string)$v));}
function lr_user(){return isset($_SESSION['username'])&&$_SESSION['username']!==''?$_SESSION['username']:'admin';}
function lr_select2($rows,$id,$cb){$out=array();foreach($rows as $r)$out[]=array('id'=>$r->$id,'text'=>$cb($r));header('Content-Type: application/json; charset=utf-8');echo json_encode(array('results'=>$out));exit;}
function lr_no(){global $db;$prefix='LVR'.date('Ym');$r=$db->fetch("SELECT leave_no FROM erp_leave_request WHERE leave_no LIKE ? ORDER BY leave_no DESC LIMIT 1",array($prefix.'%'));$n=$r?((int)substr($r->leave_no,-4)+1):1;return $prefix.sprintf('%04d',$n);}
function lr_days($from,$to,$sh,$eh){$d1=strtotime($from);$d2=strtotime($to);if(!$d1||!$d2||$d2<$d1)return 0;$days=(($d2-$d1)/86400)+1;if($sh==='PM')$days-=0.5;if($eh==='AM')$days-=0.5;return max(0.5,$days);}
function lr_row($id){
  global $db;
  return $db->fetch("SELECT l.*,e.employee_no,e.full_name,d.nm_dept,j.job_title_code,j.job_title_name,
      h.employee_no handover_no,h.full_name handover_name,
      a.employee_no approver_no,a.full_name approver_name,
      hr.employee_no hr_no,hr.full_name hr_name
    FROM erp_leave_request l
    JOIN erp_employee_master e ON e.id=l.employee_id
    LEFT JOIN dept d ON d.kd_dept=l.department_code
    LEFT JOIN erp_job_title j ON j.id=l.job_title_id
    LEFT JOIN erp_employee_master h ON h.id=l.handover_to_employee_id
    LEFT JOIN erp_employee_master a ON a.id=l.approver_employee_id
    LEFT JOIN erp_employee_master hr ON hr.id=l.hr_reviewer_employee_id
    WHERE l.id=? LIMIT 1",array((int)$id));
}
function lr_payload($r){
  $d=(array)$r;
  $d['employee_text']=$r->employee_no.' - '.$r->full_name;
  $d['handover_text']=$r->handover_no?($r->handover_no.' - '.$r->handover_name):'';
  $d['approver_text']=$r->approver_no?($r->approver_no.' - '.$r->approver_name):'';
  $d['hr_text']=$r->hr_no?($r->hr_no.' - '.$r->hr_name):'';
  $d['department_text']=$r->department_code?($r->department_code.' - '.$r->nm_dept):'';
  $d['job_title_text']=$r->job_title_id?($r->job_title_code.' - '.$r->job_title_name):'';
  return $d;
}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=lr_user();
switch($act){
  case 'employee_search':
    $term=trim(lr_p('term'));$exclude=(int)lr_p('exclude');$like='%'.$term.'%';
    $rows=$db->query("SELECT id,employee_no,full_name FROM erp_employee_master WHERE employment_status IN ('ACTIVE','PROBATION','CONTRACT') AND (?='' OR employee_no LIKE ? OR full_name LIKE ?) AND (?=0 OR id<>?) ORDER BY employee_no LIMIT 30",array($term,$like,$like,$exclude,$exclude));
    lr_select2($rows,'id',function($r){return $r->employee_no.' - '.$r->full_name;});break;
  case 'department_search':
    $term=trim(lr_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT kd_dept,nm_dept,dept_type FROM dept WHERE status='ACTIVE' AND (?='' OR kd_dept LIKE ? OR nm_dept LIKE ?) ORDER BY kd_dept LIMIT 30",array($term,$like,$like));
    lr_select2($rows,'kd_dept',function($r){return $r->kd_dept.' - '.$r->nm_dept.' ['.$r->dept_type.']';});break;
  case 'job_title_search':
    $term=trim(lr_p('term'));$dept=lr_c(lr_p('department_code'));$like='%'.$term.'%';$w=" WHERE jt.status='ACTIVE' AND (?='' OR jt.job_title_code LIKE ? OR jt.job_title_name LIKE ?) ";$p=array($term,$like,$like);if($dept!==''){$w.=" AND jt.department_code=? ";$p[]=$dept;}
    $rows=$db->query("SELECT id,job_title_code,job_title_name,job_level FROM erp_job_title jt $w ORDER BY job_title_code LIMIT 30",$p);
    lr_select2($rows,'id',function($r){return $r->job_title_code.' - '.$r->job_title_name.' ['.$r->job_level.']';});break;
  case 'employee_snapshot':
    $r=$db->fetch("SELECT e.id,e.employee_no,e.full_name,e.department_code,e.job_title_id,e.manager_employee_id,d.nm_dept,j.job_title_code,j.job_title_name,m.employee_no manager_no,m.full_name manager_name FROM erp_employee_master e LEFT JOIN dept d ON d.kd_dept=e.department_code LEFT JOIN erp_job_title j ON j.id=e.job_title_id LEFT JOIN erp_employee_master m ON m.id=e.manager_employee_id WHERE e.id=? LIMIT 1",array((int)lr_p('employee_id')));
    if(!$r)lr_json('error','Employee tidak ditemukan.');lr_json('good','',array('data'=>(array)$r));break;
  case 'get':
    $r=lr_row((int)lr_p('id'));if(!$r)lr_json('error','Leave Request tidak ditemukan.');lr_json('good','',array('data'=>lr_payload($r)));break;
  case 'save':
    $id=(int)lr_p('id');$employee=(int)lr_p('employee_id');$dept=lr_c(lr_p('department_code'));$job=lr_p('job_title_id')!==''?(int)lr_p('job_title_id'):null;$type=lr_c(lr_p('leave_type','ANNUAL_LEAVE'));$req=trim(lr_p('request_date'));$from=trim(lr_p('start_date'));$to=trim(lr_p('end_date'));$sh=lr_c(lr_p('start_half_day','FULL_DAY'));$eh=lr_c(lr_p('end_half_day','FULL_DAY'));$status=lr_c(lr_p('workflow_status','DRAFT'));
    $handover=lr_p('handover_to_employee_id')!==''?(int)lr_p('handover_to_employee_id'):null;$approver=lr_p('approver_employee_id')!==''?(int)lr_p('approver_employee_id'):null;$hr=lr_p('hr_reviewer_employee_id')!==''?(int)lr_p('hr_reviewer_employee_id'):null;
    if(!$employee)lr_json('error','Employee wajib dipilih.');if(!$req||!$from||!$to)lr_json('error','Request Date, Start Date, End Date wajib diisi.');if(strtotime($to)<strtotime($from))lr_json('error','End Date tidak boleh sebelum Start Date.');
    if(!in_array($type,array('ANNUAL_LEAVE','SICK_LEAVE','SPECIAL_LEAVE','MATERNITY_LEAVE','PATERNITY_LEAVE','MARRIAGE_LEAVE','BEREAVEMENT_LEAVE','UNPAID_LEAVE','PERMISSION','OTHER'),true))lr_json('error','Leave Type tidak valid.');
    if(!in_array($sh,array('FULL_DAY','AM','PM'),true)||!in_array($eh,array('FULL_DAY','AM','PM'),true))lr_json('error','Half day tidak valid.');
    if(!in_array($status,array('DRAFT','SUBMITTED','MANAGER_APPROVED','HR_APPROVED','APPROVED','REJECTED','RETURNED','CANCELLED'),true))lr_json('error','Status tidak valid.');
    foreach(array($employee,$handover,$approver,$hr) as $eid){if($eid&&!$db->fetch("SELECT id FROM erp_employee_master WHERE id=? LIMIT 1",array($eid)))lr_json('error','Employee / reviewer tidak valid.');}
    if($handover&&$handover===$employee)lr_json('error','Handover tidak boleh employee yang sama.');if($approver&&$approver===$employee)lr_json('error','Approver tidak boleh employee yang sama.');
    if($dept!==''&&!$db->fetch("SELECT kd_dept FROM dept WHERE kd_dept=? AND status='ACTIVE' LIMIT 1",array($dept)))lr_json('error','Department tidak valid.');if($job&&!$db->fetch("SELECT id FROM erp_job_title WHERE id=? AND status='ACTIVE' LIMIT 1",array($job)))lr_json('error','Job title tidak valid.');
    $days=lr_days($from,$to,$sh,$eh);$before=(float)lr_p('leave_quota_before',12);$after=max(0,$before-$days);$no=trim(lr_p('leave_no'))!==''?lr_c(lr_p('leave_no')):lr_no();$dup=$db->fetch("SELECT id FROM erp_leave_request WHERE leave_no=? AND id<>? LIMIT 1",array($no,$id));if($dup)lr_json('error','Leave No sudah digunakan.');
    $data=array('leave_no'=>$no,'employee_id'=>$employee,'department_code'=>$dept?:null,'job_title_id'=>$job,'leave_type'=>$type,'request_date'=>$req,'start_date'=>$from,'end_date'=>$to,'start_half_day'=>$sh,'end_half_day'=>$eh,'total_days'=>$days,'leave_quota_before'=>$before,'leave_quota_after'=>$after,'reason'=>trim(lr_p('reason')),'attachment_ref'=>trim(lr_p('attachment_ref')),'handover_to_employee_id'=>$handover,'approver_employee_id'=>$approver,'hr_reviewer_employee_id'=>$hr,'workflow_status'=>$status,'approval_level'=>$status==='DRAFT'?'EMPLOYEE':($status==='SUBMITTED'?'MANAGER':($status==='MANAGER_APPROVED'?'HR':'FINAL')),'decision'=>$status==='APPROVED'?'APPROVED':($status==='REJECTED'?'REJECTED':($status==='RETURNED'?'RETURNED':($status==='CANCELLED'?'CANCELLED':'PENDING'))),'approver_note'=>trim(lr_p('approver_note')),'hr_note'=>trim(lr_p('hr_note')),'cancellation_reason'=>trim(lr_p('cancellation_reason')),'sap_reference'=>trim(lr_p('sap_reference')),'remarks'=>trim(lr_p('remarks')),'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s'));
    if($id>0){$old=$db->fetch("SELECT workflow_status FROM erp_leave_request WHERE id=? LIMIT 1",array($id));if(!$old)lr_json('error','Leave Request tidak ditemukan.');if($old->workflow_status==='APPROVED')lr_json('error','Data APPROVED tidak bisa diedit.');$ok=$db->update('erp_leave_request',$data,'id',$id);}else{$data['created_by']=$username;$ok=$db->insert('erp_leave_request',$data);$id=$db->last_insert_id();}
    if(!$ok)lr_json('error',$db->getErrorMessage()?:'Leave Request gagal disimpan.');if(function_exists('simpan_log'))simpan_log('User '.$username.' menyimpan Leave Request '.$no.' pada '.date('Y-m-d H:i:s'),$username);lr_json('good','Leave Request berhasil disimpan.',array('id'=>$id,'total_days'=>$days));
  case 'decision':
    $id=(int)lr_p('id');$decision=lr_c(lr_p('decision'));$note=trim(lr_p('note'));$r=$db->fetch("SELECT * FROM erp_leave_request WHERE id=? LIMIT 1",array($id));if(!$r)lr_json('error','Leave Request tidak ditemukan.');if(!in_array($decision,array('APPROVE','REJECT','RETURN','CANCEL'),true))lr_json('error','Decision tidak valid.');if($r->workflow_status==='APPROVED'&&$decision!=='CANCEL')lr_json('error','Leave sudah approved.');
    $status=$decision==='APPROVE'?'APPROVED':($decision==='REJECT'?'REJECTED':($decision==='RETURN'?'RETURNED':'CANCELLED'));$dec=$decision==='APPROVE'?'APPROVED':($decision==='REJECT'?'REJECTED':($decision==='RETURN'?'RETURNED':'CANCELLED'));
    $db->update('erp_leave_request',array('workflow_status'=>$status,'approval_level'=>'FINAL','decision'=>$dec,'decision_by'=>$username,'decision_at'=>date('Y-m-d H:i:s'),'approver_note'=>$note,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')),'id',$id);if($db->getErrorMessage())lr_json('error',$db->getErrorMessage());lr_json('good','Decision berhasil disimpan.');break;
  case 'delete':
    $id=(int)lr_p('id');$r=$db->fetch("SELECT workflow_status FROM erp_leave_request WHERE id=? LIMIT 1",array($id));if(!$r)lr_json('error','Leave Request tidak ditemukan.');if($r->workflow_status==='APPROVED')lr_json('error','Data APPROVED tidak bisa dihapus.');$db->delete('erp_leave_request','id',$id);if($db->getErrorMessage())lr_json('error',$db->getErrorMessage());lr_json('good','Leave Request berhasil dihapus.');break;
  case 'detail':
    $r=lr_row((int)lr_p('id'));if(!$r){echo '<div class="alert alert-warning">Leave Request tidak ditemukan.</div>';break;}echo '<h3 style="margin-top:0">'.lr_h($r->leave_no).' <small>'.lr_h($r->employee_no.' - '.$r->full_name).'</small></h3><span class="label label-info">'.lr_h($r->leave_type).'</span> <span class="label label-success">'.lr_h($r->workflow_status).'</span><hr>';echo '<div class="row"><div class="col-sm-3"><b>'.hr_h('hr_period', 'Period').'</b><br>'.lr_h($r->start_date.' s/d '.$r->end_date).'</div><div class="col-sm-3"><b>Total Days</b><br>'.lr_h($r->total_days).'</div><div class="col-sm-3"><b>Quota</b><br>'.lr_h($r->leave_quota_before.' -> '.$r->leave_quota_after).'</div><div class="col-sm-3"><b>Approver</b><br>'.lr_h($r->approver_no?($r->approver_no.' - '.$r->approver_name):'-').'</div></div><hr><b>Reason</b><p>'.nl2br(lr_h($r->reason?:'-')).'</p><b>Approver Note</b><p>'.nl2br(lr_h($r->approver_note?:'-')).'</p>';break;
  case 'export':
    $initial=ob_get_level();ob_start();error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $from=lr_g('tgl_awal',date('Y-01-01'));$to=lr_g('tgl_akhir',date('Y-m-d'));$type=lr_c(lr_g('leave_type'));$status=lr_c(lr_g('status'));$dept=lr_c(lr_g('department_code'));$kw=trim(lr_g('keyword'));$where=" WHERE l.start_date<=? AND l.end_date>=? ";$p=array($to,$from);if($type!==''){$where.=" AND l.leave_type=? ";$p[]=$type;}if($status!==''){$where.=" AND l.workflow_status=? ";$p[]=$status;}if($dept!==''){$where.=" AND l.department_code=? ";$p[]=$dept;}if($kw!==''){$like='%'.$kw.'%';$where.=" AND (l.leave_no LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ? OR d.nm_dept LIKE ?) ";array_push($p,$like,$like,$like,$like);}
    $rows=$db->query("SELECT l.*,e.employee_no,e.full_name,d.nm_dept,a.employee_no approver_no,a.full_name approver_name FROM erp_leave_request l JOIN erp_employee_master e ON e.id=l.employee_id LEFT JOIN dept d ON d.kd_dept=l.department_code LEFT JOIN erp_employee_master a ON a.id=l.approver_employee_id $where ORDER BY l.start_date DESC,l.id DESC",$p);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Leave Request'));$heads=array(erp_export_label("No"),erp_export_label("Leave No"),erp_export_label("Employee"),erp_export_label("Department"),erp_export_label("Type"),erp_export_label("Request Date"),erp_export_label("Start"),erp_export_label("End"),erp_export_label("Half Day"),erp_export_label("Total Days"),erp_export_label("Quota Before"),erp_export_label("Quota After"),erp_export_label("Approver"),erp_export_label("Status"),erp_export_label("Decision"),erp_export_label("Decision By"),erp_export_label("Decision At"),erp_export_label("Reason"),erp_export_label("Updated By"),erp_export_label("Updated At"));foreach($heads as $i=>$h)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);$rnum=5;$n=1;foreach($rows as $r){$vals=array($n++,$r->leave_no,$r->employee_no.' - '.$r->full_name,$r->department_code.' - '.$r->nm_dept,$r->leave_type,$r->request_date,$r->start_date,$r->end_date,$r->start_half_day.' / '.$r->end_half_day,(float)$r->total_days,(float)$r->leave_quota_before,(float)$r->leave_quota_after,$r->approver_no.' - '.$r->approver_name,$r->workflow_status,$r->decision,$r->decision_by,$r->decision_at,$r->reason,$r->updated_by?:$r->created_by,$r->updated_at?:$r->created_at);foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rnum,$v);$rnum++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('LEAVE REQUEST REPORT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rnum-1),'column_count'=>count($heads),'decimal_columns'=>array('J','K','L'),'filters'=>array('Tanggal'=>$from.' s/d '.$to,'Type'=>$type?:erp_export_all_text(),'Status'=>$status?:erp_export_all_text(),'Department'=>$dept?:erp_export_all_text())));$tmp=erpkb_excel_temp_file('leave_request_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="leave_request_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:lr_json('error','Action tidak dikenal.');
}
?>
