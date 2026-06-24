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

function tr_json($status,$message='',$extra=array()){header('Content-Type: application/json; charset=utf-8');$p=array('status'=>$status);if($message!=='')$p[$status==='good'?'message':'error_message']=$message;foreach($extra as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function tr_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function tr_p($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function tr_g($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
function tr_code($v){return strtoupper(trim((string)$v));}
function tr_in($v,$arr){return in_array($v,$arr,true);}
function tr_select2($rows,$idField,$cb){$out=array();foreach($rows as $r)$out[]=array('id'=>$r->$idField,'text'=>$cb($r));header('Content-Type: application/json; charset=utf-8');echo json_encode(array('results'=>$out));exit;}
function tr_next_no(){global $db;$prefix='TRG-'.date('Y').'-';$r=$db->fetch("SELECT registration_no FROM erp_training_registration WHERE registration_no LIKE ? ORDER BY registration_no DESC LIMIT 1",array($prefix.'%'));$n=1;if($r&&preg_match('/(\d+)$/',$r->registration_no,$m))$n=(int)$m[1]+1;return $prefix.str_pad($n,4,'0',STR_PAD_LEFT);}
function tr_row($id){global $db;return $db->fetch("SELECT tr.*,tp.plan_code,tp.plan_name,tp.planned_start_date,tp.planned_end_date,tc.training_code,tc.training_name,e.employee_no,e.full_name,e.department_code,d.nm_dept,jt.job_title_code,jt.job_title_name FROM erp_training_registration tr JOIN erp_training_plan tp ON tp.id=tr.training_plan_id JOIN erp_training_catalog tc ON tc.id=tp.training_catalog_id JOIN erp_employee_master e ON e.id=tr.employee_id LEFT JOIN dept d ON d.kd_dept=e.department_code LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id WHERE tr.id=? LIMIT 1",array((int)$id));}
function tr_payload($r){$d=(array)$r;$d['plan_text']=$r->training_plan_id?($r->plan_code.' - '.$r->plan_name.' ['.$r->training_code.' - '.$r->training_name.']'):'';$d['employee_text']=$r->employee_id?($r->employee_no.' - '.$r->full_name.' ['.$r->department_code.']'):'';return $d;}
function tr_filters_where($src,&$p){$from=isset($src['tgl_awal'])&&$src['tgl_awal']!==''?$src['tgl_awal']:date('Y-01-01');$to=isset($src['tgl_akhir'])&&$src['tgl_akhir']!==''?$src['tgl_akhir']:date('Y-12-31');$w=" WHERE tr.registration_date BETWEEN ? AND ? ";$p[]=$from;$p[]=$to;foreach(array('training_plan_id','registration_status','approval_status','attendance_status') as $k){if(isset($src[$k])&&$src[$k]!==''){$w.=" AND tr.$k=? ";$p[]=trim($src[$k]);}}if(isset($src['department_code'])&&$src['department_code']!==''){$w.=" AND e.department_code=? ";$p[]=trim($src['department_code']);}if(isset($src['keyword'])&&$src['keyword']!==''){$kw='%'.trim($src['keyword']).'%';$w.=" AND (tr.registration_no LIKE ? OR tp.plan_code LIKE ? OR tp.plan_name LIKE ? OR tc.training_code LIKE ? OR tc.training_name LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ?) ";for($i=0;$i<7;$i++)$p[]=$kw;}return $w;}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=isset($_SESSION['username'])?$_SESSION['username']:'system';
$sources=array('PLAN_NOMINATION','MANUAL','MANAGER_REQUEST','EMPLOYEE_SELF_SERVICE');
$regStatuses=array('REGISTERED','WAITLIST','CANCELLED','ATTENDED','NO_SHOW','COMPLETED');
$approvalStatuses=array('DRAFT','SUBMITTED','APPROVED','REJECTED');
$attendanceStatuses=array('NOT_MARKED','PRESENT','ABSENT','PARTIAL');

switch($act){
  case 'next_no': tr_json('good','',array('code'=>tr_next_no())); break;
  case 'plan_search':
    $term=trim(tr_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT tp.id,tp.plan_code,tp.plan_name,tp.planned_start_date,tp.planned_end_date,tc.training_code,tc.training_name FROM erp_training_plan tp JOIN erp_training_catalog tc ON tc.id=tp.training_catalog_id WHERE tp.approval_status IN ('APPROVED','COMPLETED') AND tp.execution_status IN ('SCHEDULED','IN_PROGRESS','NOT_STARTED') AND (?='' OR tp.plan_code LIKE ? OR tp.plan_name LIKE ? OR tc.training_code LIKE ? OR tc.training_name LIKE ?) ORDER BY tp.planned_start_date DESC LIMIT 50",array($term,$like,$like,$like,$like));
    tr_select2($rows,'id',function($r){return $r->plan_code.' - '.$r->plan_name.' ['.$r->training_code.' - '.$r->training_name.', '.$r->planned_start_date.']';});
    break;
  case 'employee_search':
    $term=trim(tr_p('term'));$planId=(int)tr_p('training_plan_id');$like='%'.$term.'%';
    if($planId>0){
      $rows=$db->query("SELECT e.id,e.employee_no,e.full_name,e.department_code,jt.job_title_code FROM erp_training_plan_participant tpp JOIN erp_employee_master e ON e.id=tpp.employee_id LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id WHERE tpp.training_plan_id=? AND tpp.nomination_status<>'CANCELLED' AND (?='' OR e.employee_no LIKE ? OR e.full_name LIKE ? OR e.department_code LIKE ?) ORDER BY e.employee_no LIMIT 80",array($planId,$term,$like,$like,$like));
    }else{
      $rows=$db->query("SELECT e.id,e.employee_no,e.full_name,e.department_code,jt.job_title_code FROM erp_employee_master e LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id WHERE e.employment_status IN ('ACTIVE','PROBATION','CONTRACT') AND (?='' OR e.employee_no LIKE ? OR e.full_name LIKE ? OR e.department_code LIKE ?) ORDER BY e.employee_no LIMIT 80",array($term,$like,$like,$like));
    }
    tr_select2($rows,'id',function($r){return $r->employee_no.' - '.$r->full_name.' ['.$r->department_code.' / '.($r->job_title_code?:'-').']';});
    break;
  case 'department_search':
    $term=trim(tr_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT kd_dept,nm_dept,dept_type FROM dept WHERE status='ACTIVE' AND (?='' OR kd_dept LIKE ? OR nm_dept LIKE ? OR dept_type LIKE ?) ORDER BY kd_dept LIMIT 50",array($term,$like,$like,$like));
    tr_select2($rows,'kd_dept',function($r){return $r->kd_dept.' - '.$r->nm_dept.' ['.$r->dept_type.']';});
    break;
  case 'get':
    $r=tr_row((int)tr_p('id')); if(!$r)tr_json('error','Training Registration tidak ditemukan.'); tr_json('good','',array('data'=>tr_payload($r))); break;
  case 'save':
    $id=(int)tr_p('id');$no=tr_code(tr_p('registration_no'));if($no==='')$no=tr_next_no();
    $planId=(int)tr_p('training_plan_id');$empId=(int)tr_p('employee_id');$regDate=trim(tr_p('registration_date'));$source=tr_code(tr_p('registration_source','PLAN_NOMINATION'));$regStatus=tr_code(tr_p('registration_status','REGISTERED'));$approval=tr_code(tr_p('approval_status','APPROVED'));$attendance=tr_code(tr_p('attendance_status','NOT_MARKED'));
    if(!preg_match('/^[A-Z0-9_-]{3,30}$/',$no))tr_json('error','Registration No tidak valid.');
    $plan=$db->fetch("SELECT * FROM erp_training_plan WHERE id=? AND approval_status IN ('APPROVED','COMPLETED') LIMIT 1",array($planId));if(!$plan)tr_json('error','Training Plan wajib dipilih dan harus approved.');
    $emp=$db->fetch("SELECT id FROM erp_employee_master WHERE id=? AND employment_status IN ('ACTIVE','PROBATION','CONTRACT') LIMIT 1",array($empId));if(!$emp)tr_json('error','Employee tidak valid atau tidak aktif.');
    if($regDate==='')tr_json('error','Registration Date wajib diisi.');
    if(!tr_in($source,$sources)||!tr_in($regStatus,$regStatuses)||!tr_in($approval,$approvalStatuses)||!tr_in($attendance,$attendanceStatuses))tr_json('error','Status tidak valid.');
    $dup=$db->fetch("SELECT id FROM erp_training_registration WHERE registration_no=? AND id<>? LIMIT 1",array($no,$id));if($dup)tr_json('error','Registration No sudah digunakan.');
    $dupEmp=$db->fetch("SELECT id FROM erp_training_registration WHERE training_plan_id=? AND employee_id=? AND id<>? LIMIT 1",array($planId,$empId,$id));if($dupEmp)tr_json('error','Employee sudah terdaftar di training plan ini.');
    $tpp=$db->fetch("SELECT id FROM erp_training_plan_participant WHERE training_plan_id=? AND employee_id=? LIMIT 1",array($planId,$empId));
    $data=array('registration_no'=>$no,'training_plan_id'=>$planId,'training_plan_participant_id'=>$tpp?$tpp->id:null,'employee_id'=>$empId,'registration_date'=>$regDate,'registration_source'=>$source,'registration_status'=>$regStatus,'approval_status'=>$approval,'attendance_status'=>$attendance,'check_in_time'=>trim(tr_p('check_in_time'))?:null,'check_out_time'=>trim(tr_p('check_out_time'))?:null,'learning_hours'=>max(0,(float)tr_p('learning_hours',0)),'score'=>tr_p('score')!==''?(float)tr_p('score'):null,'certificate_no'=>trim(tr_p('certificate_no')),'certificate_date'=>trim(tr_p('certificate_date'))?:null,'cancellation_reason'=>trim(tr_p('cancellation_reason')),'remarks'=>trim(tr_p('remarks')),'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s'));
    if($id>0){$old=$db->fetch("SELECT id FROM erp_training_registration WHERE id=? LIMIT 1",array($id));if(!$old)tr_json('error','Training Registration tidak ditemukan.');$ok=$db->update('erp_training_registration',$data,'id',$id);}else{$data['created_by']=$username;$ok=$db->insert('erp_training_registration',$data);$id=$db->last_insert_id();}
    if(!$ok)tr_json('error',$db->getErrorMessage()?:'Training Registration gagal disimpan.');
    if($tpp)$db->update('erp_training_plan_participant',array('nomination_status'=>$regStatus==='CANCELLED'?'CANCELLED':($regStatus==='COMPLETED'?'COMPLETED':'REGISTERED')),'id',$tpp->id);
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menyimpan Training Registration '.$no.' pada '.date('Y-m-d H:i:s'),$username);
    tr_json('good','Training Registration berhasil disimpan.',array('id'=>$id));
    break;
  case 'mark_present':
    $id=(int)tr_p('id');$r=$db->fetch("SELECT * FROM erp_training_registration WHERE id=? LIMIT 1",array($id));if(!$r)tr_json('error','Training Registration tidak ditemukan.');
    $ok=$db->update('erp_training_registration',array('registration_status'=>'ATTENDED','attendance_status'=>'PRESENT','check_in_time'=>date('Y-m-d H:i:s'),'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')),'id',$id);
    if(!$ok)tr_json('error',$db->getErrorMessage()?:hr_t('hr_attendance_save_failed', 'Attendance failed to save.'));tr_json('good','Attendance berhasil ditandai present.');
    break;
  case 'delete':
    $id=(int)tr_p('id');$r=$db->fetch("SELECT * FROM erp_training_registration WHERE id=? LIMIT 1",array($id));if(!$r)tr_json('error','Training Registration tidak ditemukan.');
    if(in_array($r->registration_status,array('ATTENDED','COMPLETED'),true))tr_json('error','Registration yang sudah ATTENDED/COMPLETED tidak boleh dihapus. Ubah status cancel jika perlu.');
    $db->delete('erp_training_registration','id',$id);if($db->getErrorMessage())tr_json('error',$db->getErrorMessage());
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menghapus Training Registration '.$r->registration_no,$username);
    tr_json('good','Training Registration berhasil dihapus.');
    break;
  case 'detail':
    $r=tr_row((int)tr_p('id'));if(!$r){echo '<div class="alert alert-warning">Training Registration tidak ditemukan.</div>';break;}
    echo '<h3 style="margin-top:0">'.tr_h($r->registration_no).' <small>'.tr_h($r->employee_no.' - '.$r->full_name).'</small></h3><span class="label label-success">'.tr_h($r->registration_status).'</span> <span class="label label-info">'.tr_h($r->approval_status).'</span> <span class="label label-primary">'.tr_h($r->attendance_status).'</span><hr>';
    echo '<div class="row"><div class="col-sm-3"><b>'.hr_h('hr_training_plan', 'Training Plan').'</b><br>'.tr_h($r->plan_code.' - '.$r->plan_name).'</div><div class="col-sm-3"><b>Catalog</b><br>'.tr_h($r->training_code.' - '.$r->training_name).'</div><div class="col-sm-3"><b>'.hr_h('hr_employee', 'Employee').'</b><br>'.tr_h($r->employee_no.' - '.$r->full_name).'</div><div class="col-sm-3"><b>'.hr_h('hr_department', 'Department').'</b><br>'.tr_h($r->department_code.' - '.$r->nm_dept).'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><b>Registration Date</b><br>'.tr_h($r->registration_date).'</div><div class="col-sm-3"><b>Check In</b><br>'.tr_h($r->check_in_time?:'-').'</div><div class="col-sm-3"><b>Check Out</b><br>'.tr_h($r->check_out_time?:'-').'</div><div class="col-sm-3"><b>Score / Certificate</b><br>'.tr_h($r->score!==null?$r->score:'-').' / '.tr_h($r->certificate_no?:'-').'</div></div><hr><b>'.hr_h('common_remarks', 'Remarks').'</b><p>'.nl2br(tr_h($r->remarks?:'-')).'</p>';
    break;
  case 'export':
    $initial=ob_get_level();ob_start();error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $p=array();$w=tr_filters_where($_GET,$p);$join=" FROM erp_training_registration tr JOIN erp_training_plan tp ON tp.id=tr.training_plan_id JOIN erp_training_catalog tc ON tc.id=tp.training_catalog_id JOIN erp_employee_master e ON e.id=tr.employee_id LEFT JOIN dept d ON d.kd_dept=e.department_code LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id ";
    $rows=$db->query("SELECT tr.*,tp.plan_code,tp.plan_name,tc.training_code,tc.training_name,e.employee_no,e.full_name,e.department_code,d.nm_dept,jt.job_title_code,jt.job_title_name $join $w ORDER BY tr.registration_date,tr.registration_no",$p);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Training Registration'));$heads=array(erp_export_label("No"),erp_export_label("Registration No"),erp_export_label("Plan Code"),erp_export_label("Plan Name"),erp_export_label("Training Code"),erp_export_label("Training Name"),erp_export_label("Employee No"),erp_export_label("Employee Name"),erp_export_label("Department"),erp_export_label("Job Title"),erp_export_label("Registration Date"),erp_export_label("Source"),erp_export_label("Registration Status"),erp_export_label("Approval Status"),erp_export_label("Attendance Status"),erp_export_label("Check In"),erp_export_label("Check Out"),erp_export_label("Learning Hours"),erp_export_label("Score"),erp_export_label("Certificate No"),erp_export_label("Certificate Date"),erp_export_label("Cancel Reason"),erp_export_label("Remarks"),erp_export_label("Updated By"));foreach($heads as $i=>$h)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);
    $rn=5;$n=1;foreach($rows as $r){$vals=array($n++,$r->registration_no,$r->plan_code,$r->plan_name,$r->training_code,$r->training_name,$r->employee_no,$r->full_name,$r->department_code.' - '.$r->nm_dept,$r->job_title_code.' - '.$r->job_title_name,$r->registration_date,$r->registration_source,$r->registration_status,$r->approval_status,$r->attendance_status,$r->check_in_time,$r->check_out_time,$r->learning_hours,$r->score,$r->certificate_no,$r->certificate_date,$r->cancellation_reason,$r->remarks,$r->updated_by?:$r->created_by);foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn,$v);$rn++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('TRAINING REGISTRATION'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rn-1),'column_count'=>count($heads),'filters'=>array('Period'=>array(tr_g('tgl_awal',date('Y-01-01')),tr_g('tgl_akhir',date('Y-12-31'))),'Registration'=>tr_g('registration_status',''),'Attendance'=>tr_g('attendance_status','')),'decimal_columns'=>array('R','S'),'widths'=>array('B'=>20,'D'=>32,'F'=>34,'H'=>28,'I'=>28,'J'=>28,'W'=>34)));
    $tmp=erpkb_excel_temp_file('training_registration_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="training_registration_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default: tr_json('error','Action tidak dikenal.');
}
?>
