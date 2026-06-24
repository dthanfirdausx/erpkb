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

function ap_json($s,$m='',$x=array()){header('Content-Type: application/json; charset=utf-8');$p=array('status'=>$s);if($m!=='')$p[$s==='good'?'message':'error_message']=$m;foreach($x as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function ap_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function ap_p($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function ap_g($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
function ap_c($v){return strtoupper(trim((string)$v));}
function ap_user(){return isset($_SESSION['username'])&&$_SESSION['username']!==''?$_SESSION['username']:'system';}
function ap_select2($rows,$id,$cb){$out=array();foreach($rows as $r)$out[]=array('id'=>$r->$id,'text'=>$cb($r));header('Content-Type: application/json; charset=utf-8');echo json_encode(array('results'=>$out));exit;}
function ap_next_no($year){global $db;$year=(int)$year>0?(int)$year:(int)date('Y');$r=$db->fetch("SELECT applicant_no FROM erp_applicant_data WHERE applicant_no LIKE ? ORDER BY applicant_no DESC LIMIT 1",array('APP-'.$year.'-%'));$n=1;if($r&&preg_match('/-(\d+)$/',$r->applicant_no,$m))$n=(int)$m[1]+1;return 'APP-'.$year.'-'.str_pad($n,3,'0',STR_PAD_LEFT);}
function ap_row($id){
  global $db;
  return $db->fetch("SELECT a.*,v.vacancy_no,v.vacancy_title,p.position_code,p.position_name,jt.job_title_code,jt.job_title_name,d.nm_dept,rec.employee_no recruiter_no,rec.full_name recruiter_name,ref.employee_no referred_no,ref.full_name referred_name,he.employee_no hired_no,he.full_name hired_name
    FROM erp_applicant_data a
    LEFT JOIN erp_job_vacancy v ON v.id=a.vacancy_id
    LEFT JOIN erp_position p ON p.id=v.position_id
    LEFT JOIN erp_job_title jt ON jt.id=v.job_title_id
    LEFT JOIN dept d ON d.kd_dept=v.department_code
    LEFT JOIN erp_employee_master rec ON rec.id=a.recruiter_employee_id
    LEFT JOIN erp_employee_master ref ON ref.id=a.referred_by_employee_id
    LEFT JOIN erp_employee_master he ON he.id=a.hired_employee_id
    WHERE a.id=? LIMIT 1",array((int)$id));
}
function ap_payload($r){
  $d=(array)$r;
  $d['vacancy_text']=$r->vacancy_id?($r->vacancy_no.' - '.$r->vacancy_title):'';
  $d['recruiter_text']=$r->recruiter_employee_id?($r->recruiter_no.' - '.$r->recruiter_name):'';
  $d['referred_by_text']=$r->referred_by_employee_id?($r->referred_no.' - '.$r->referred_name):'';
  $d['hired_employee_text']=$r->hired_employee_id?($r->hired_no.' - '.$r->hired_name):'';
  return $d;
}
function ap_sync_vacancy($vacancyId){
  global $db;
  if((int)$vacancyId<=0)return;
  $db->query("UPDATE erp_job_vacancy v
    SET applicant_count=(SELECT COUNT(*) FROM erp_applicant_data a WHERE a.vacancy_id=v.id),
        shortlisted_count=(SELECT COUNT(*) FROM erp_applicant_data a WHERE a.vacancy_id=v.id AND a.applicant_status IN ('SHORTLISTED','INTERVIEW','OFFER','HIRED')),
        interview_count=(SELECT COUNT(*) FROM erp_applicant_data a WHERE a.vacancy_id=v.id AND a.applicant_status IN ('INTERVIEW','OFFER','HIRED')),
        offer_count=(SELECT COUNT(*) FROM erp_applicant_data a WHERE a.vacancy_id=v.id AND a.applicant_status IN ('OFFER','HIRED')),
        hired_count=(SELECT COUNT(*) FROM erp_applicant_data a WHERE a.vacancy_id=v.id AND a.applicant_status='HIRED'),
        headcount_filled=(SELECT COUNT(*) FROM erp_applicant_data a WHERE a.vacancy_id=v.id AND a.applicant_status='HIRED'),
        updated_by='admin',
        updated_at=NOW()
    WHERE v.id=?",array((int)$vacancyId));
}
function ap_export_rows($from,$to,$status,$vacancy,$source,$kw){
  global $db;$p=array($from,$to);$w=" WHERE a.application_date BETWEEN ? AND ? ";
  if($status!==''){$w.=" AND a.applicant_status=? ";$p[]=$status;}if($vacancy!==''){$w.=" AND a.vacancy_id=? ";$p[]=(int)$vacancy;}if($source!==''){$w.=" AND a.source_channel=? ";$p[]=$source;}if($kw!==''){$like='%'.$kw.'%';$w.=" AND (a.applicant_no LIKE ? OR a.applicant_name LIKE ? OR a.email LIKE ? OR a.phone LIKE ? OR v.vacancy_no LIKE ? OR v.vacancy_title LIKE ?) ";array_push($p,$like,$like,$like,$like,$like,$like);}
  return $db->query("SELECT a.*,v.vacancy_no,v.vacancy_title,p.position_code,jt.job_title_name,d.nm_dept,rec.employee_no recruiter_no,rec.full_name recruiter_name
    FROM erp_applicant_data a
    LEFT JOIN erp_job_vacancy v ON v.id=a.vacancy_id
    LEFT JOIN erp_position p ON p.id=v.position_id
    LEFT JOIN erp_job_title jt ON jt.id=v.job_title_id
    LEFT JOIN dept d ON d.kd_dept=v.department_code
    LEFT JOIN erp_employee_master rec ON rec.id=a.recruiter_employee_id
    $w ORDER BY a.application_date DESC,a.applicant_no DESC",$p);
}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=ap_user();
$statuses=array('NEW','SCREENING','SHORTLISTED','INTERVIEW','OFFER','HIRED','REJECTED','WITHDRAWN','BLACKLISTED');
$genders=array('MALE','FEMALE','OTHER');
$educations=array('SMA_SMK','DIPLOMA','S1','S2','S3','OTHER');
$ids=array('KTP','PASSPORT','KITAS','OTHER');

switch($act){
case 'next_no':ap_json('good','',array('applicant_no'=>ap_next_no((int)ap_p('year',date('Y')))));
case 'vacancy_search':
  $t=trim(ap_p('term'));$like='%'.$t.'%';$rows=$db->query("SELECT id,vacancy_no,vacancy_title,vacancy_status FROM erp_job_vacancy WHERE vacancy_status IN ('OPEN','SCREENING','INTERVIEW','OFFER','DRAFT') AND (?='' OR vacancy_no LIKE ? OR vacancy_title LIKE ?) ORDER BY vacancy_no DESC LIMIT 50",array($t,$like,$like));ap_select2($rows,'id',function($r){return $r->vacancy_no.' - '.$r->vacancy_title.' ['.$r->vacancy_status.']';});
case 'employee_search':
  $t=trim(ap_p('term'));$like='%'.$t.'%';$rows=$db->query("SELECT id,employee_no,full_name,employment_status FROM erp_employee_master WHERE employment_status IN ('ACTIVE','PROBATION','CONTRACT') AND (?='' OR employee_no LIKE ? OR full_name LIKE ?) ORDER BY employee_no LIMIT 40",array($t,$like,$like));ap_select2($rows,'id',function($r){return $r->employee_no.' - '.$r->full_name.' ['.$r->employment_status.']';});
case 'source_search':
  $rows=$db->query("SELECT DISTINCT source_channel FROM erp_applicant_data WHERE source_channel IS NOT NULL AND source_channel<>'' ORDER BY source_channel LIMIT 50");$out=array();foreach($rows as $r)$out[]=array('id'=>$r->source_channel,'text'=>$r->source_channel);header('Content-Type: application/json; charset=utf-8');echo json_encode(array('results'=>$out));exit;
case 'get':$r=ap_row((int)ap_p('id'));if(!$r)ap_json('error','Applicant tidak ditemukan.');ap_json('good','',array('data'=>ap_payload($r)));
case 'save':
  $id=(int)ap_p('id');$no=ap_c(ap_p('applicant_no'));$name=trim(ap_p('applicant_name'));$appDate=trim(ap_p('application_date'));$year=$appDate!==''?(int)substr($appDate,0,4):(int)date('Y');if($no===''||$no==='AUTO')$no=ap_next_no($year);
  $gender=ap_c(ap_p('gender','OTHER'));$edu=ap_c(ap_p('education_level','S1'));$identity=ap_c(ap_p('identity_type','KTP'));$status=ap_c(ap_p('applicant_status','NEW'));
  if($name==='')ap_json('error','Applicant Name wajib diisi.');if($appDate==='')ap_json('error','Application Date wajib diisi.');if(!in_array($gender,$genders,true))$gender='OTHER';if(!in_array($edu,$educations,true))$edu='OTHER';if(!in_array($identity,$ids,true))$identity='OTHER';if(!in_array($status,$statuses,true))ap_json('error','Applicant Status tidak valid.');if($db->fetch("SELECT id FROM erp_applicant_data WHERE applicant_no=? AND id<>? LIMIT 1",array($no,$id)))ap_json('error','Applicant No sudah digunakan.');
  $oldVac=0;if($id>0){$old=ap_row($id);if(!$old)ap_json('error','Applicant tidak ditemukan.');$oldVac=(int)$old->vacancy_id;}
  $data=array('applicant_no'=>$no,'vacancy_id'=>ap_p('vacancy_id')!==''?(int)ap_p('vacancy_id'):null,'applicant_name'=>$name,'gender'=>$gender,'birth_place'=>trim(ap_p('birth_place')),'birth_date'=>trim(ap_p('birth_date'))?:null,'nationality'=>ap_c(ap_p('nationality','ID'))?:'ID','identity_type'=>$identity,'identity_no'=>trim(ap_p('identity_no')),'email'=>trim(ap_p('email')),'phone'=>trim(ap_p('phone')),'address'=>trim(ap_p('address')),'city'=>trim(ap_p('city')),'postal_code'=>trim(ap_p('postal_code')),'education_level'=>$edu,'major'=>trim(ap_p('major')),'university'=>trim(ap_p('university')),'graduation_year'=>ap_p('graduation_year')!==''?(int)ap_p('graduation_year'):null,'gpa'=>ap_p('gpa')!==''?(float)ap_p('gpa'):null,'current_company'=>trim(ap_p('current_company')),'current_position'=>trim(ap_p('current_position')),'years_experience'=>(float)ap_p('years_experience',0),'expected_salary'=>(float)ap_p('expected_salary',0),'currency'=>ap_c(ap_p('currency','IDR'))?:'IDR','source_channel'=>trim(ap_p('source_channel')),'referred_by_employee_id'=>ap_p('referred_by_employee_id')!==''?(int)ap_p('referred_by_employee_id'):null,'application_date'=>$appDate,'available_start_date'=>trim(ap_p('available_start_date'))?:null,'applicant_status'=>$status,'screening_score'=>(float)ap_p('screening_score',0),'interview_score'=>(float)ap_p('interview_score',0),'final_score'=>(float)ap_p('final_score',0),'recruiter_employee_id'=>ap_p('recruiter_employee_id')!==''?(int)ap_p('recruiter_employee_id'):null,'cv_reference'=>trim(ap_p('cv_reference')),'portfolio_url'=>trim(ap_p('portfolio_url')),'linkedin_url'=>trim(ap_p('linkedin_url')),'skills'=>trim(ap_p('skills')),'screening_notes'=>trim(ap_p('screening_notes')),'interview_notes'=>trim(ap_p('interview_notes')),'rejection_reason'=>trim(ap_p('rejection_reason')),'hired_employee_id'=>ap_p('hired_employee_id')!==''?(int)ap_p('hired_employee_id'):null,'sap_reference'=>trim(ap_p('sap_reference')),'remarks'=>trim(ap_p('remarks')),'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s'));
  if($id>0){$ok=$db->update('erp_applicant_data',$data,'id',$id);}else{$data['created_by']=$username;$ok=$db->insert('erp_applicant_data',$data);$id=(int)$db->last_insert_id();}
  if(!$ok)ap_json('error',$db->getErrorMessage()?:'Applicant gagal disimpan.');
  ap_sync_vacancy($oldVac);ap_sync_vacancy((int)$data['vacancy_id']);
  if(function_exists('simpan_log'))simpan_log('User '.$username.' menyimpan Applicant '.$no.' - '.$name.' pada '.date('Y-m-d H:i:s'),$username);
  ap_json('good','Applicant berhasil disimpan.',array('id'=>$id,'applicant_no'=>$no));
case 'status':
  $id=(int)ap_p('id');$status=ap_c(ap_p('status'));if(!in_array($status,$statuses,true))ap_json('error','Status tidak valid.');$r=ap_row($id);if(!$r)ap_json('error','Applicant tidak ditemukan.');
  $ok=$db->update('erp_applicant_data',array('applicant_status'=>$status,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')),'id',$id);if(!$ok)ap_json('error',$db->getErrorMessage()?:'Status gagal diubah.');ap_sync_vacancy((int)$r->vacancy_id);if(function_exists('simpan_log'))simpan_log('User '.$username.' mengubah status Applicant '.$r->applicant_no.' menjadi '.$status,$username);ap_json('good','Status berhasil diubah.');
case 'delete':
  $id=(int)ap_p('id');$r=ap_row($id);if(!$r)ap_json('error','Applicant tidak ditemukan.');if(!in_array($r->applicant_status,array('NEW','REJECTED','WITHDRAWN'),true))ap_json('error','Hanya status NEW/REJECTED/WITHDRAWN yang boleh dihapus.');$vac=(int)$r->vacancy_id;$db->delete('erp_applicant_data','id',$id);if($db->getErrorMessage())ap_json('error',$db->getErrorMessage());ap_sync_vacancy($vac);if(function_exists('simpan_log'))simpan_log('User '.$username.' menghapus Applicant '.$r->applicant_no,$username);ap_json('good','Applicant berhasil dihapus.');
case 'detail':
  $r=ap_row((int)ap_p('id'));if(!$r){echo '<div class="alert alert-warning">Applicant tidak ditemukan.</div>';break;}
  echo '<div class="ap-detail"><h3 style="margin-top:0">'.ap_h($r->applicant_no).' <small>'.ap_h($r->applicant_name).'</small></h3><span class="label label-info">'.ap_h($r->applicant_status).'</span> <span class="label label-default">'.ap_h($r->education_level).'</span><hr><div class="row"><div class="col-sm-3"><b>Vacancy</b><br>'.ap_h($r->vacancy_no?($r->vacancy_no.' - '.$r->vacancy_title):'-').'</div><div class="col-sm-3"><b>'.hr_h('hr_position', 'Position').'</b><br>'.ap_h($r->position_code?($r->position_code.' - '.$r->position_name):'-').'</div><div class="col-sm-3"><b>Contact</b><br>'.ap_h($r->email?:'-').'<br>'.ap_h($r->phone?:'-').'</div><div class="col-sm-3"><b>Application</b><br>'.ap_h($r->application_date).' / '.ap_h($r->source_channel?:'-').'</div></div><hr><div class="row"><div class="col-sm-3"><b>Education</b><br>'.ap_h($r->education_level.' - '.$r->major).'<br><small>'.ap_h($r->university?:'-').'</small></div><div class="col-sm-3"><b>Experience</b><br>'.number_format((float)$r->years_experience,2).' years<br><small>'.ap_h(($r->current_company?:'-').' / '.($r->current_position?:'-')).'</small></div><div class="col-sm-3"><b>'.hr_h('hr_score', 'Score').'</b><br>Screen '.number_format((float)$r->screening_score,2).' / Interview '.number_format((float)$r->interview_score,2).'<br><b>Final '.number_format((float)$r->final_score,2).'</b></div><div class="col-sm-3"><b>Recruiter</b><br>'.ap_h($r->recruiter_no?($r->recruiter_no.' - '.$r->recruiter_name):'-').'</div></div><hr><div class="row"><div class="col-sm-4"><b>Skills</b><p>'.nl2br(ap_h($r->skills?:'-')).'</p></div><div class="col-sm-4"><b>Screening Notes</b><p>'.nl2br(ap_h($r->screening_notes?:'-')).'</p></div><div class="col-sm-4"><b>Interview Notes</b><p>'.nl2br(ap_h($r->interview_notes?:'-')).'</p></div></div><hr><p><b>CV:</b> '.ap_h($r->cv_reference?:'-').'<br><b>LinkedIn:</b> '.ap_h($r->linkedin_url?:'-').'<br><b>SAP Reference:</b> '.ap_h($r->sap_reference?:'-').'<br><b>Remarks:</b> '.nl2br(ap_h($r->remarks?:'-')).'</p></div>';break;
case 'export':
  $initial=ob_get_level();ob_start();error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $from=ap_g('tgl_awal',date('Y-01-01'));$to=ap_g('tgl_akhir',date('Y-12-31'));$status=ap_g('applicant_status','');$vac=ap_g('vacancy_id','');$source=ap_g('source_channel','');$kw=ap_g('keyword','');$rows=ap_export_rows($from,$to,$status,$vac,$source,$kw);
  $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Applicant Data'));$heads=array(erp_export_label("No"),erp_export_label("Applicant No"),erp_export_label("Name"),erp_export_label("Status"),erp_export_label("Vacancy"),erp_export_label("Position"),erp_export_label("Job Title"),erp_export_label("Application Date"),erp_export_label("Source"),erp_export_label("Email"),erp_export_label("Phone"),erp_export_label("Gender"),erp_export_label("Birth Date"),erp_export_label("City"),erp_export_label("Education"),erp_export_label("Major"),erp_export_label("University"),erp_export_label("Grad Year"),erp_export_label("GPA"),erp_export_label("Experience"),erp_export_label("Expected Salary"),erp_export_label("Currency"),erp_export_label("Screen Score"),erp_export_label("Interview Score"),erp_export_label("Final Score"),erp_export_label("Recruiter"),erp_export_label("CV Ref"),erp_export_label("SAP Ref"),erp_export_label("Updated By"));
  foreach($heads as $i=>$h)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);$rn=5;$n=1;foreach($rows as $r){$vals=array($n++,$r->applicant_no,$r->applicant_name,$r->applicant_status,$r->vacancy_no.' - '.$r->vacancy_title,$r->position_code,$r->job_title_name,$r->application_date,$r->source_channel,$r->email,$r->phone,$r->gender,$r->birth_date,$r->city,$r->education_level,$r->major,$r->university,$r->graduation_year,$r->gpa,$r->years_experience,$r->expected_salary,$r->currency,$r->screening_score,$r->interview_score,$r->final_score,$r->recruiter_no.' - '.$r->recruiter_name,$r->cv_reference,$r->sap_reference,$r->updated_by?:$r->created_by);foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn,$v);$rn++;}
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('APPLICANT DATA REPORT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rn-1),'column_count'=>count($heads),'decimal_columns'=>array('S','T','U','W','X','Y'),'filters'=>array('Application Date'=>$from.' s/d '.$to,'Status'=>$status?:erp_export_all_text(),'Vacancy'=>$vac?:erp_export_all_text(),'Source'=>$source?:erp_export_all_text()),'widths'=>array('B'=>18,'C'=>28,'E'=>32,'F'=>20,'G'=>24,'J'=>28,'K'=>16,'O'=>16,'Q'=>26,'Z'=>28,'AA'=>28,'AB'=>20)));
  $tmp=erpkb_excel_temp_file('applicant_data_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="applicant_data_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
default:ap_json('error','Action tidak dikenal.');
}
?>
