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

function ts_json($status,$message='',$extra=array()){header('Content-Type: application/json; charset=utf-8');$p=array('status'=>$status);if($message!=='')$p[$status==='good'?'message':'error_message']=$message;foreach($extra as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function ts_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function ts_p($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function ts_g($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
function ts_code($v){return strtoupper(trim((string)$v));}
function ts_in($v,$arr){return in_array($v,$arr,true);}
function ts_select2($rows,$idField,$cb){$out=array();foreach($rows as $r)$out[]=array('id'=>$r->$idField,'text'=>$cb($r));header('Content-Type: application/json; charset=utf-8');echo json_encode(array('results'=>$out));exit;}
function ts_next_no(){global $db;$prefix='TRS-'.date('Y').'-';$r=$db->fetch("SELECT result_no FROM erp_training_result WHERE result_no LIKE ? ORDER BY result_no DESC LIMIT 1",array($prefix.'%'));$n=1;if($r&&preg_match('/(\d+)$/',$r->result_no,$m))$n=(int)$m[1]+1;return $prefix.str_pad($n,4,'0',STR_PAD_LEFT);}
function ts_row($id){global $db;return $db->fetch("SELECT trr.*,reg.registration_no,reg.registration_status,reg.attendance_status,tp.plan_code,tp.plan_name,tc.training_code,tc.training_name,tc.passing_score catalog_passing_score,tc.certificate_required,e.employee_no,e.full_name,e.department_code,d.nm_dept,jt.job_title_code,jt.job_title_name FROM erp_training_result trr JOIN erp_training_registration reg ON reg.id=trr.training_registration_id JOIN erp_training_plan tp ON tp.id=reg.training_plan_id JOIN erp_training_catalog tc ON tc.id=tp.training_catalog_id JOIN erp_employee_master e ON e.id=reg.employee_id LEFT JOIN dept d ON d.kd_dept=e.department_code LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id WHERE trr.id=? LIMIT 1",array((int)$id));}
function ts_payload($r){$d=(array)$r;$d['registration_text']=$r->registration_no.' - '.$r->employee_no.' '.$r->full_name.' ['.$r->training_code.' - '.$r->training_name.']';return $d;}
function ts_filters_where($src,&$p){$from=isset($src['tgl_awal'])&&$src['tgl_awal']!==''?$src['tgl_awal']:date('Y-01-01');$to=isset($src['tgl_akhir'])&&$src['tgl_akhir']!==''?$src['tgl_akhir']:date('Y-12-31');$w=" WHERE trr.result_date BETWEEN ? AND ? ";$p[]=$from;$p[]=$to;foreach(array('evaluation_method','result_status','completion_status','certificate_issued') as $k){if(isset($src[$k])&&$src[$k]!==''){$w.=" AND trr.$k=? ";$p[]=trim($src[$k]);}}if(isset($src['training_plan_id'])&&$src['training_plan_id']!==''){$w.=" AND reg.training_plan_id=? ";$p[]=trim($src['training_plan_id']);}if(isset($src['department_code'])&&$src['department_code']!==''){$w.=" AND e.department_code=? ";$p[]=trim($src['department_code']);}if(isset($src['keyword'])&&$src['keyword']!==''){$kw='%'.trim($src['keyword']).'%';$w.=" AND (trr.result_no LIKE ? OR reg.registration_no LIKE ? OR tp.plan_code LIKE ? OR tp.plan_name LIKE ? OR tc.training_code LIKE ? OR tc.training_name LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ? OR trr.certificate_no LIKE ?) ";for($i=0;$i<9;$i++)$p[]=$kw;}return $w;}
function ts_sync_registration($resultId){
  global $db;
  $r=$db->fetch("SELECT trr.*,reg.id reg_id FROM erp_training_result trr JOIN erp_training_registration reg ON reg.id=trr.training_registration_id WHERE trr.id=? LIMIT 1",array((int)$resultId));
  if(!$r)return;
  $regStatus=$r->completion_status==='COMPLETED'?'COMPLETED':($r->result_status==='FAILED'?'COMPLETED':'ATTENDED');
  $attendance=$r->completion_status==='CANCELLED'?'ABSENT':'PRESENT';
  $db->update('erp_training_registration',array('registration_status'=>$regStatus,'attendance_status'=>$attendance,'score'=>$r->final_score,'certificate_no'=>$r->certificate_no,'certificate_date'=>$r->certificate_date,'updated_at'=>date('Y-m-d H:i:s')),'id',$r->reg_id);
}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=isset($_SESSION['username'])?$_SESSION['username']:'system';
$methods=array('EXAM','PRACTICAL','OBSERVATION','ATTENDANCE_ONLY','MIXED');
$resultStatuses=array('DRAFT','PASSED','FAILED','INCOMPLETE','NOT_EVALUATED');
$completionStatuses=array('NOT_STARTED','IN_PROGRESS','COMPLETED','CANCELLED');
$competencies=array('Y','N','PARTIAL');
$yn=array('Y','N');

switch($act){
  case 'next_no':ts_json('good','',array('code'=>ts_next_no()));break;
  case 'registration_search':
    $term=trim(ts_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT reg.id,reg.registration_no,tp.plan_code,tc.training_code,tc.training_name,e.employee_no,e.full_name FROM erp_training_registration reg JOIN erp_training_plan tp ON tp.id=reg.training_plan_id JOIN erp_training_catalog tc ON tc.id=tp.training_catalog_id JOIN erp_employee_master e ON e.id=reg.employee_id WHERE reg.registration_status IN ('REGISTERED','ATTENDED','COMPLETED') AND reg.attendance_status IN ('PRESENT','NOT_MARKED','PARTIAL') AND NOT EXISTS (SELECT 1 FROM erp_training_result x WHERE x.training_registration_id=reg.id) AND (?='' OR reg.registration_no LIKE ? OR tp.plan_code LIKE ? OR tc.training_name LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ?) ORDER BY reg.registration_no DESC LIMIT 80",array($term,$like,$like,$like,$like,$like));
    ts_select2($rows,'id',function($r){return $r->registration_no.' - '.$r->employee_no.' '.$r->full_name.' ['.$r->training_code.' - '.$r->training_name.']';});
    break;
  case 'plan_search':
    $term=trim(ts_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT tp.id,tp.plan_code,tp.plan_name,tc.training_code,tc.training_name FROM erp_training_plan tp JOIN erp_training_catalog tc ON tc.id=tp.training_catalog_id WHERE (?='' OR tp.plan_code LIKE ? OR tp.plan_name LIKE ? OR tc.training_code LIKE ? OR tc.training_name LIKE ?) ORDER BY tp.planned_start_date DESC LIMIT 50",array($term,$like,$like,$like,$like));
    ts_select2($rows,'id',function($r){return $r->plan_code.' - '.$r->plan_name.' ['.$r->training_code.' - '.$r->training_name.']';});
    break;
  case 'department_search':
    $term=trim(ts_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT kd_dept,nm_dept,dept_type FROM dept WHERE status='ACTIVE' AND (?='' OR kd_dept LIKE ? OR nm_dept LIKE ? OR dept_type LIKE ?) ORDER BY kd_dept LIMIT 50",array($term,$like,$like,$like));
    ts_select2($rows,'kd_dept',function($r){return $r->kd_dept.' - '.$r->nm_dept.' ['.$r->dept_type.']';});
    break;
  case 'get':
    $r=ts_row((int)ts_p('id'));if(!$r)ts_json('error','Training Result tidak ditemukan.');ts_json('good','',array('data'=>ts_payload($r)));break;
  case 'save':
    $id=(int)ts_p('id');$no=ts_code(ts_p('result_no'));if($no==='')$no=ts_next_no();
    $regId=(int)ts_p('training_registration_id');$resultDate=trim(ts_p('result_date'));$method=ts_code(ts_p('evaluation_method','EXAM'));$resultStatus=ts_code(ts_p('result_status','DRAFT'));$completion=ts_code(ts_p('completion_status','COMPLETED'));$competency=ts_code(ts_p('competency_achieved','Y'));$cert=ts_code(ts_p('certificate_issued','N'));
    if(!preg_match('/^[A-Z0-9_-]{3,30}$/',$no))ts_json('error','Result No tidak valid.');
    $reg=$db->fetch("SELECT reg.*,tc.passing_score,tc.certificate_required FROM erp_training_registration reg JOIN erp_training_plan tp ON tp.id=reg.training_plan_id JOIN erp_training_catalog tc ON tc.id=tp.training_catalog_id WHERE reg.id=? LIMIT 1",array($regId));if(!$reg)ts_json('error','Training Registration wajib dipilih.');
    if($resultDate==='')ts_json('error','Result Date wajib diisi.');
    if(!ts_in($method,$methods)||!ts_in($resultStatus,$resultStatuses)||!ts_in($completion,$completionStatuses)||!ts_in($competency,$competencies)||!ts_in($cert,$yn))ts_json('error','Status/result tidak valid.');
    $final=ts_p('final_score')!==''?(float)ts_p('final_score'):null;$passing=ts_p('passing_score')!==''?(float)ts_p('passing_score'):($reg->passing_score!==null?(float)$reg->passing_score:null);
    if($resultStatus==='PASSED' && $passing!==null && $final!==null && $final<$passing)ts_json('error','Final score di bawah passing score, status tidak boleh PASSED.');
    if($cert==='Y' && trim(ts_p('certificate_no'))==='')ts_json('error','Certificate No wajib diisi jika certificate issued Y.');
    $dup=$db->fetch("SELECT id FROM erp_training_result WHERE result_no=? AND id<>? LIMIT 1",array($no,$id));if($dup)ts_json('error','Result No sudah digunakan.');
    $dupReg=$db->fetch("SELECT id FROM erp_training_result WHERE training_registration_id=? AND id<>? LIMIT 1",array($regId,$id));if($dupReg)ts_json('error','Registration ini sudah memiliki result.');
    $data=array('result_no'=>$no,'training_registration_id'=>$regId,'result_date'=>$resultDate,'evaluation_method'=>$method,'pre_test_score'=>ts_p('pre_test_score')!==''?(float)ts_p('pre_test_score'):null,'post_test_score'=>ts_p('post_test_score')!==''?(float)ts_p('post_test_score'):null,'final_score'=>$final,'passing_score'=>$passing,'result_status'=>$resultStatus,'completion_status'=>$completion,'competency_achieved'=>$competency,'certificate_issued'=>$cert,'certificate_no'=>trim(ts_p('certificate_no')),'certificate_date'=>trim(ts_p('certificate_date'))?:null,'certificate_valid_until'=>trim(ts_p('certificate_valid_until'))?:null,'evaluator_name'=>trim(ts_p('evaluator_name')),'training_feedback_score'=>ts_p('training_feedback_score')!==''?(float)ts_p('training_feedback_score'):null,'trainer_feedback_score'=>ts_p('trainer_feedback_score')!==''?(float)ts_p('trainer_feedback_score'):null,'improvement_note'=>trim(ts_p('improvement_note')),'follow_up_action'=>trim(ts_p('follow_up_action')),'remarks'=>trim(ts_p('remarks')),'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s'));
    if($id>0){$old=$db->fetch("SELECT id FROM erp_training_result WHERE id=? LIMIT 1",array($id));if(!$old)ts_json('error','Training Result tidak ditemukan.');$ok=$db->update('erp_training_result',$data,'id',$id);}else{$data['created_by']=$username;$ok=$db->insert('erp_training_result',$data);$id=$db->last_insert_id();}
    if(!$ok)ts_json('error',$db->getErrorMessage()?:'Training Result gagal disimpan.');
    ts_sync_registration($id);
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menyimpan Training Result '.$no.' pada '.date('Y-m-d H:i:s'),$username);
    ts_json('good','Training Result berhasil disimpan.',array('id'=>$id));
    break;
  case 'mark_passed':
    $id=(int)ts_p('id');$r=$db->fetch("SELECT * FROM erp_training_result WHERE id=? LIMIT 1",array($id));if(!$r)ts_json('error','Training Result tidak ditemukan.');
    $ok=$db->update('erp_training_result',array('result_status'=>'PASSED','completion_status'=>'COMPLETED','competency_achieved'=>'Y','updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')),'id',$id);
    if(!$ok)ts_json('error',$db->getErrorMessage()?:'Status gagal diubah.');ts_sync_registration($id);ts_json('good','Training Result berhasil ditandai PASSED.');
    break;
  case 'delete':
    $id=(int)ts_p('id');$r=$db->fetch("SELECT * FROM erp_training_result WHERE id=? LIMIT 1",array($id));if(!$r)ts_json('error','Training Result tidak ditemukan.');
    if($r->certificate_issued==='Y')ts_json('error','Result yang sudah issue certificate tidak boleh dihapus.');
    $db->delete('erp_training_result','id',$id);if($db->getErrorMessage())ts_json('error',$db->getErrorMessage());
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menghapus Training Result '.$r->result_no,$username);
    ts_json('good','Training Result berhasil dihapus.');
    break;
  case 'detail':
    $r=ts_row((int)ts_p('id'));if(!$r){echo '<div class="alert alert-warning">Training Result tidak ditemukan.</div>';break;}
    echo '<h3 style="margin-top:0">'.ts_h($r->result_no).' <small>'.ts_h($r->employee_no.' - '.$r->full_name).'</small></h3><span class="label label-success">'.ts_h($r->result_status).'</span> <span class="label label-primary">'.ts_h($r->completion_status).'</span> <span class="label label-info">Competency '.ts_h($r->competency_achieved).'</span><hr>';
    echo '<div class="row"><div class="col-sm-3"><b>Registration</b><br>'.ts_h($r->registration_no).'</div><div class="col-sm-3"><b>'.hr_h('hr_training', 'Training').'</b><br>'.ts_h($r->training_code.' - '.$r->training_name).'</div><div class="col-sm-3"><b>'.hr_h('hr_department', 'Department').'</b><br>'.ts_h($r->department_code.' - '.$r->nm_dept).'</div><div class="col-sm-3"><b>Evaluator</b><br>'.ts_h($r->evaluator_name?:'-').'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><b>'.hr_h('hr_score', 'Score').'</b><br>Pre '.ts_h($r->pre_test_score?:'-').' / Post '.ts_h($r->post_test_score?:'-').' / Final '.ts_h($r->final_score?:'-').'</div><div class="col-sm-3"><b>Passing</b><br>'.ts_h($r->passing_score?:'-').'</div><div class="col-sm-3"><b>Certificate</b><br>'.ts_h($r->certificate_issued).' - '.ts_h($r->certificate_no?:'-').'</div><div class="col-sm-3"><b>Valid Until</b><br>'.ts_h($r->certificate_valid_until?:'-').'</div></div><hr><div class="row"><div class="col-sm-6"><b>Improvement Note</b><p>'.nl2br(ts_h($r->improvement_note?:'-')).'</p></div><div class="col-sm-6"><b>Follow Up Action</b><p>'.nl2br(ts_h($r->follow_up_action?:'-')).'</p></div></div>';
    break;
  case 'export':
    $initial=ob_get_level();ob_start();error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $p=array();$w=ts_filters_where($_GET,$p);$join=" FROM erp_training_result trr JOIN erp_training_registration reg ON reg.id=trr.training_registration_id JOIN erp_training_plan tp ON tp.id=reg.training_plan_id JOIN erp_training_catalog tc ON tc.id=tp.training_catalog_id JOIN erp_employee_master e ON e.id=reg.employee_id LEFT JOIN dept d ON d.kd_dept=e.department_code LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id ";
    $rows=$db->query("SELECT trr.*,reg.registration_no,tp.plan_code,tp.plan_name,tc.training_code,tc.training_name,e.employee_no,e.full_name,e.department_code,d.nm_dept,jt.job_title_code,jt.job_title_name $join $w ORDER BY trr.result_date,trr.result_no",$p);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Training Result'));$heads=array(erp_export_label("No"),erp_export_label("Result No"),erp_export_label("Registration No"),erp_export_label("Plan Code"),erp_export_label("Training Code"),erp_export_label("Training Name"),erp_export_label("Employee No"),erp_export_label("Employee Name"),erp_export_label("Department"),erp_export_label("Job Title"),erp_export_label("Result Date"),erp_export_label("Method"),erp_export_label("Pre Score"),erp_export_label("Post Score"),erp_export_label("Final Score"),erp_export_label("Passing Score"),erp_export_label("Result Status"),erp_export_label("Completion"),erp_export_label("Competency"),erp_export_label("Certificate Issued"),erp_export_label("Certificate No"),erp_export_label("Certificate Date"),erp_export_label("Valid Until"),erp_export_label("Evaluator"),erp_export_label("Training Feedback"),erp_export_label("Trainer Feedback"),erp_export_label("Improvement Note"),erp_export_label("Follow Up"),erp_export_label("Remarks"),erp_export_label("Updated By"));foreach($heads as $i=>$h)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);
    $rn=5;$n=1;foreach($rows as $r){$vals=array($n++,$r->result_no,$r->registration_no,$r->plan_code,$r->training_code,$r->training_name,$r->employee_no,$r->full_name,$r->department_code.' - '.$r->nm_dept,$r->job_title_code.' - '.$r->job_title_name,$r->result_date,$r->evaluation_method,$r->pre_test_score,$r->post_test_score,$r->final_score,$r->passing_score,$r->result_status,$r->completion_status,$r->competency_achieved,$r->certificate_issued,$r->certificate_no,$r->certificate_date,$r->certificate_valid_until,$r->evaluator_name,$r->training_feedback_score,$r->trainer_feedback_score,$r->improvement_note,$r->follow_up_action,$r->remarks,$r->updated_by?:$r->created_by);foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn,$v);$rn++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('TRAINING RESULT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rn-1),'column_count'=>count($heads),'filters'=>array('Period'=>array(ts_g('tgl_awal',date('Y-01-01')),ts_g('tgl_akhir',date('Y-12-31'))),'Result'=>ts_g('result_status',''),'Completion'=>ts_g('completion_status','')),'decimal_columns'=>array('M','N','O','P','Y','Z'),'widths'=>array('B'=>18,'C'=>20,'F'=>34,'H'=>28,'I'=>28,'J'=>28,'AA'=>36,'AB'=>36,'AC'=>30)));
    $tmp=erpkb_excel_temp_file('training_result_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="training_result_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:ts_json('error','Action tidak dikenal.');
}
?>
