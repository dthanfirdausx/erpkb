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

function aa_json($status,$message='',$extra=array()){
  header('Content-Type: application/json; charset=utf-8');
  $payload=array('status'=>$status);
  if($message!=='')$payload[$status==='good'?'message':'error_message']=$message;
  foreach($extra as $k=>$v)$payload[$k]=$v;
  echo json_encode($payload); exit;
}
function aa_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function aa_p($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function aa_g($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
function aa_c($v){return strtoupper(trim((string)$v));}
function aa_user(){return isset($_SESSION['username'])&&$_SESSION['username']!==''?$_SESSION['username']:'admin';}
function aa_select2($rows,$idField,$cb){$out=array();foreach($rows as $r)$out[]=array('id'=>$r->$idField,'text'=>$cb($r));header('Content-Type: application/json; charset=utf-8');echo json_encode(array('results'=>$out));exit;}
function aa_no(){global $db;$prefix='APA'.date('Ym');$r=$db->fetch("SELECT appraisal_no FROM erp_appraisal_approval WHERE appraisal_no LIKE ? ORDER BY appraisal_no DESC LIMIT 1",array($prefix.'%'));$n=$r?((int)substr($r->appraisal_no,-4)+1):1;return $prefix.sprintf('%04d',$n);}
function aa_score($v){$n=(float)$v;if($n<0)$n=0;if($n>100)$n=100;return $n;}
function aa_rating($score){if($score>=90)return 'A';if($score>=80)return 'B';if($score>=70)return 'C';if($score>=60)return 'D';return 'E';}
function aa_row($id){
  global $db;
  return $db->fetch("SELECT a.*,e.employee_no,e.full_name,d.nm_dept,j.job_title_code,j.job_title_name,
      ap.employee_no appraiser_no,ap.full_name appraiser_name,
      sp.employee_no second_no,sp.full_name second_name,
      hr.employee_no hr_no,hr.full_name hr_name
    FROM erp_appraisal_approval a
    JOIN erp_employee_master e ON e.id=a.employee_id
    LEFT JOIN dept d ON d.kd_dept=a.department_code
    LEFT JOIN erp_job_title j ON j.id=a.job_title_id
    LEFT JOIN erp_employee_master ap ON ap.id=a.appraiser_employee_id
    LEFT JOIN erp_employee_master sp ON sp.id=a.second_appraiser_employee_id
    LEFT JOIN erp_employee_master hr ON hr.id=a.hr_reviewer_employee_id
    WHERE a.id=? LIMIT 1",array((int)$id));
}
function aa_payload($r){
  $data=(array)$r;
  $data['employee_text']=$r->employee_no.' - '.$r->full_name;
  $data['appraiser_text']=$r->appraiser_no?($r->appraiser_no.' - '.$r->appraiser_name):'';
  $data['second_text']=$r->second_no?($r->second_no.' - '.$r->second_name):'';
  $data['hr_text']=$r->hr_no?($r->hr_no.' - '.$r->hr_name):'';
  $data['department_text']=$r->department_code?($r->department_code.' - '.$r->nm_dept):'';
  $data['job_title_text']=$r->job_title_id?($r->job_title_code.' - '.$r->job_title_name):'';
  return $data;
}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=aa_user();

switch($act){
  case 'employee_search':
    $term=trim(aa_p('term'));$exclude=(int)aa_p('exclude');$like='%'.$term.'%';
    $rows=$db->query("SELECT id,employee_no,full_name FROM erp_employee_master WHERE employment_status IN ('ACTIVE','PROBATION','CONTRACT') AND (?='' OR employee_no LIKE ? OR full_name LIKE ?) AND (?=0 OR id<>?) ORDER BY employee_no LIMIT 30",array($term,$like,$like,$exclude,$exclude));
    aa_select2($rows,'id',function($r){return $r->employee_no.' - '.$r->full_name;});
    break;
  case 'department_search':
    $term=trim(aa_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT kd_dept,nm_dept,dept_type FROM dept WHERE status='ACTIVE' AND (?='' OR kd_dept LIKE ? OR nm_dept LIKE ? OR dept_type LIKE ?) ORDER BY kd_dept LIMIT 30",array($term,$like,$like,$like));
    aa_select2($rows,'kd_dept',function($r){return $r->kd_dept.' - '.$r->nm_dept.' ['.$r->dept_type.']';});
    break;
  case 'job_title_search':
    $term=trim(aa_p('term'));$dept=aa_c(aa_p('department_code'));$like='%'.$term.'%';
    $where=" WHERE jt.status='ACTIVE' AND (?='' OR jt.job_title_code LIKE ? OR jt.job_title_name LIKE ?) ";$p=array($term,$like,$like);
    if($dept!==''){$where.=" AND jt.department_code=? ";$p[]=$dept;}
    $rows=$db->query("SELECT jt.id,jt.job_title_code,jt.job_title_name,jt.job_level FROM erp_job_title jt $where ORDER BY jt.job_level DESC,jt.job_title_code LIMIT 30",$p);
    aa_select2($rows,'id',function($r){return $r->job_title_code.' - '.$r->job_title_name.' ['.$r->job_level.']';});
    break;
  case 'employee_snapshot':
    $id=(int)aa_p('employee_id');
    $r=$db->fetch("SELECT e.id,e.employee_no,e.full_name,e.department_code,e.job_title_id,e.manager_employee_id,
        d.nm_dept,j.job_title_code,j.job_title_name,m.employee_no manager_no,m.full_name manager_name
      FROM erp_employee_master e
      LEFT JOIN dept d ON d.kd_dept=e.department_code
      LEFT JOIN erp_job_title j ON j.id=e.job_title_id
      LEFT JOIN erp_employee_master m ON m.id=e.manager_employee_id
      WHERE e.id=? LIMIT 1",array($id));
    if(!$r)aa_json('error','Employee tidak ditemukan.');
    aa_json('good','',array('data'=>(array)$r));
    break;
  case 'get':
    $r=aa_row((int)aa_p('id'));if(!$r)aa_json('error','Appraisal approval tidak ditemukan.');
    aa_json('good','',array('data'=>aa_payload($r)));
    break;
  case 'save':
    $id=(int)aa_p('id');
    $employee=(int)aa_p('employee_id');
    $appraiser=(int)aa_p('appraiser_employee_id');
    $second=aa_p('second_appraiser_employee_id')!==''?(int)aa_p('second_appraiser_employee_id'):null;
    $hr=aa_p('hr_reviewer_employee_id')!==''?(int)aa_p('hr_reviewer_employee_id'):null;
    $dept=aa_c(aa_p('department_code'));
    $job=aa_p('job_title_id')!==''?(int)aa_p('job_title_id'):null;
    $year=(int)aa_p('cycle_year',date('Y'));
    $period=aa_c(aa_p('appraisal_period','ANNUAL'));
    $type=aa_c(aa_p('appraisal_type','ANNUAL'));
    $date=trim(aa_p('appraisal_date'));
    $status=aa_c(aa_p('calibration_status','DRAFT'));
    $level=aa_c(aa_p('approval_level','MANAGER'));
    if(!$employee)aa_json('error','Employee wajib dipilih.');
    if(!$appraiser)aa_json('error','Appraiser wajib dipilih.');
    if($employee===$appraiser)aa_json('error','Appraiser tidak boleh employee yang sama.');
    if($second&&$second===$employee)aa_json('error','Second appraiser tidak boleh employee yang sama.');
    if($hr&&$hr===$employee)aa_json('error','HR reviewer tidak boleh employee yang sama.');
    if($year<2000||$year>2100)aa_json('error','Cycle year tidak valid.');
    if(!in_array($period,array('Q1','Q2','Q3','Q4','H1','H2','ANNUAL','PROBATION','SPECIAL'),true))aa_json('error','Appraisal period tidak valid.');
    if(!in_array($type,array('ANNUAL','MID_YEAR','PROBATION','PROJECT','SPECIAL'),true))aa_json('error','Appraisal type tidak valid.');
    if($date==='')aa_json('error','Appraisal date wajib diisi.');
    if(!in_array($status,array('DRAFT','SUBMITTED','MANAGER_APPROVED','HR_REVIEW','APPROVED','REJECTED','RETURNED','CANCELLED'),true))aa_json('error','Status approval tidak valid.');
    if(!in_array($level,array('MANAGER','SECOND_MANAGER','HR','FINAL'),true))aa_json('error','Approval level tidak valid.');
    foreach(array($employee,$appraiser,$second,$hr) as $empId){if($empId&&!$db->fetch("SELECT id FROM erp_employee_master WHERE id=? LIMIT 1",array($empId)))aa_json('error','Employee / reviewer tidak valid.');}
    if($dept!==''&&!$db->fetch("SELECT kd_dept FROM dept WHERE kd_dept=? AND status='ACTIVE' LIMIT 1",array($dept)))aa_json('error','Department tidak valid.');
    if($job&&!$db->fetch("SELECT id FROM erp_job_title WHERE id=? AND status='ACTIVE' LIMIT 1",array($job)))aa_json('error','Job title tidak valid.');
    $kpi=aa_score(aa_p('kpi_score'));$comp=aa_score(aa_p('competency_score'));$beh=aa_score(aa_p('behavior_score'));
    $final=round(($kpi*0.50)+($comp*0.30)+($beh*0.20),2);
    $rating=aa_rating($final);
    $no=trim(aa_p('appraisal_no'))!==''?aa_c(aa_p('appraisal_no')):aa_no();
    $dup=$db->fetch("SELECT id FROM erp_appraisal_approval WHERE appraisal_no=? AND id<>? LIMIT 1",array($no,$id));if($dup)aa_json('error','Appraisal No sudah digunakan.');
    $data=array(
      'appraisal_no'=>$no,'cycle_year'=>$year,'appraisal_period'=>$period,'appraisal_type'=>$type,'employee_id'=>$employee,'appraiser_employee_id'=>$appraiser,
      'second_appraiser_employee_id'=>$second,'hr_reviewer_employee_id'=>$hr,'department_code'=>$dept?:null,'job_title_id'=>$job,'appraisal_date'=>$date,
      'submitted_at'=>$status==='SUBMITTED'?date('Y-m-d H:i:s'):null,'kpi_score'=>$kpi,'competency_score'=>$comp,'behavior_score'=>$beh,'final_score'=>$final,
      'final_rating'=>$rating,'calibration_status'=>$status,'approval_level'=>$level,'decision'=>$status==='APPROVED'?'APPROVED':($status==='REJECTED'?'REJECTED':($status==='RETURNED'?'RETURNED':'PENDING')),
      'manager_comment'=>trim(aa_p('manager_comment')),'hr_comment'=>trim(aa_p('hr_comment')),'employee_comment'=>trim(aa_p('employee_comment')),
      'development_plan'=>trim(aa_p('development_plan')),'reward_recommendation'=>trim(aa_p('reward_recommendation')),
      'improvement_required'=>aa_c(aa_p('improvement_required','N'))==='Y'?'Y':'N','remarks'=>trim(aa_p('remarks')),'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')
    );
    if($id>0){$old=$db->fetch("SELECT calibration_status FROM erp_appraisal_approval WHERE id=? LIMIT 1",array($id));if(!$old)aa_json('error','Appraisal approval tidak ditemukan.');if($old->calibration_status==='APPROVED')aa_json('error','Data APPROVED tidak bisa diedit.');$ok=$db->update('erp_appraisal_approval',$data,'id',$id);}
    else{$data['created_by']=$username;$ok=$db->insert('erp_appraisal_approval',$data);$id=$db->last_insert_id();}
    if(!$ok)aa_json('error',$db->getErrorMessage()?:'Appraisal approval gagal disimpan.');
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menyimpan Appraisal Approval '.$no.' pada '.date('Y-m-d H:i:s'),$username);
    aa_json('good','Appraisal approval berhasil disimpan.',array('id'=>$id,'final_score'=>$final,'final_rating'=>$rating));
    break;
  case 'decision':
    $id=(int)aa_p('id');$decision=aa_c(aa_p('decision'));$comment=trim(aa_p('comment'));
    $r=$db->fetch("SELECT * FROM erp_appraisal_approval WHERE id=? LIMIT 1",array($id));if(!$r)aa_json('error','Appraisal approval tidak ditemukan.');
    if(!in_array($decision,array('APPROVE','REJECT','RETURN'),true))aa_json('error','Decision tidak valid.');
    if($r->calibration_status==='APPROVED')aa_json('error','Appraisal sudah final approved.');
    $status=$decision==='APPROVE'?'APPROVED':($decision==='REJECT'?'REJECTED':'RETURNED');
    $dec=$decision==='APPROVE'?'APPROVED':($decision==='REJECT'?'REJECTED':'RETURNED');
    $data=array('calibration_status'=>$status,'approval_level'=>'FINAL','decision'=>$dec,'decision_by'=>$username,'decision_at'=>date('Y-m-d H:i:s'),'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s'));
    if($comment!=='')$data['hr_comment']=$comment;
    $db->update('erp_appraisal_approval',$data,'id',$id);
    if($db->getErrorMessage())aa_json('error',$db->getErrorMessage());
    if(function_exists('simpan_log'))simpan_log('User '.$username.' melakukan '.$dec.' Appraisal Approval '.$r->appraisal_no.' pada '.date('Y-m-d H:i:s'),$username);
    aa_json('good','Decision berhasil disimpan.');
    break;
  case 'delete':
    $id=(int)aa_p('id');$r=$db->fetch("SELECT appraisal_no,calibration_status FROM erp_appraisal_approval WHERE id=? LIMIT 1",array($id));if(!$r)aa_json('error','Appraisal approval tidak ditemukan.');
    if($r->calibration_status==='APPROVED')aa_json('error','Data APPROVED tidak bisa dihapus.');
    $db->delete('erp_appraisal_approval','id',$id);
    if($db->getErrorMessage())aa_json('error',$db->getErrorMessage());
    aa_json('good','Appraisal approval berhasil dihapus.');
    break;
  case 'detail':
    $r=aa_row((int)aa_p('id'));if(!$r){echo '<div class="alert alert-warning">Appraisal approval tidak ditemukan.</div>';break;}
    echo '<h3 style="margin-top:0">'.aa_h($r->appraisal_no).' <small>'.aa_h($r->employee_no.' - '.$r->full_name).'</small></h3><span class="label label-info">'.aa_h($r->appraisal_type).'</span> <span class="label label-success">'.aa_h($r->calibration_status).'</span> <span class="label label-primary">Rating '.aa_h($r->final_rating).'</span><hr>';
    echo '<div class="row"><div class="col-sm-3"><b>Cycle</b><br>'.aa_h($r->cycle_year.' / '.$r->appraisal_period).'</div><div class="col-sm-3"><b>Appraiser</b><br>'.aa_h($r->appraiser_no.' - '.$r->appraiser_name).'</div><div class="col-sm-3"><b>HR Reviewer</b><br>'.aa_h($r->hr_no?($r->hr_no.' - '.$r->hr_name):'-').'</div><div class="col-sm-3"><b>Decision</b><br>'.aa_h($r->decision.' by '.($r->decision_by?:'-')).'</div></div><hr>';
    echo '<table class="table table-bordered"><tr><th>KPI</th><th>Competency</th><th>Behavior</th><th>Final Score</th><th>'.hr_h('hr_rating', 'Rating').'</th></tr><tr><td>'.aa_h($r->kpi_score).'</td><td>'.aa_h($r->competency_score).'</td><td>'.aa_h($r->behavior_score).'</td><td><b>'.aa_h($r->final_score).'</b></td><td><b>'.aa_h($r->final_rating).'</b></td></tr></table>';
    echo '<div class="row"><div class="col-sm-4"><b>Manager Comment</b><p>'.aa_h($r->manager_comment?:'-').'</p></div><div class="col-sm-4"><b>HR Comment</b><p>'.aa_h($r->hr_comment?:'-').'</p></div><div class="col-sm-4"><b>Development Plan</b><p>'.aa_h($r->development_plan?:'-').'</p></div></div>';
    break;
  case 'export':
    $initial=ob_get_level();ob_start();error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $from=aa_g('tgl_awal',date('Y-01-01'));$to=aa_g('tgl_akhir',date('Y-m-d'));$status=aa_c(aa_g('status'));$rating=aa_c(aa_g('rating'));$dept=aa_c(aa_g('department_code'));$year=trim(aa_g('cycle_year'));$kw=trim(aa_g('keyword'));
    $where=" WHERE a.appraisal_date BETWEEN ? AND ? ";$p=array($from,$to);
    if($status!==''){$where.=" AND a.calibration_status=? ";$p[]=$status;}if($rating!==''){$where.=" AND a.final_rating=? ";$p[]=$rating;}if($dept!==''){$where.=" AND a.department_code=? ";$p[]=$dept;}if($year!==''){$where.=" AND a.cycle_year=? ";$p[]=$year;}
    if($kw!==''){$like='%'.$kw.'%';$where.=" AND (a.appraisal_no LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ? OR d.nm_dept LIKE ?) ";array_push($p,$like,$like,$like,$like);}
    $rows=$db->query("SELECT a.*,e.employee_no,e.full_name,d.nm_dept,j.job_title_code,j.job_title_name,ap.employee_no appraiser_no,ap.full_name appraiser_name,hr.employee_no hr_no,hr.full_name hr_name FROM erp_appraisal_approval a JOIN erp_employee_master e ON e.id=a.employee_id LEFT JOIN dept d ON d.kd_dept=a.department_code LEFT JOIN erp_job_title j ON j.id=a.job_title_id LEFT JOIN erp_employee_master ap ON ap.id=a.appraiser_employee_id LEFT JOIN erp_employee_master hr ON hr.id=a.hr_reviewer_employee_id $where ORDER BY a.appraisal_date DESC,a.id DESC",$p);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Appraisal Approval'));
    $heads=array(erp_export_label("No"),erp_export_label("Appraisal No"),erp_export_label("Cycle"),erp_export_label("Period"),erp_export_label("Type"),erp_export_label("Date"),erp_export_label("Employee"),erp_export_label("Department"),erp_export_label("Job Title"),erp_export_label("Appraiser"),erp_export_label("HR Reviewer"),erp_export_label("KPI"),erp_export_label("Competency"),erp_export_label("Behavior"),erp_export_label("Final Score"),erp_export_label("Rating"),erp_export_label("Status"),erp_export_label("Decision"),erp_export_label("Decision By"),erp_export_label("Decision At"),erp_export_label("Improvement"),erp_export_label("Reward Recommendation"),erp_export_label("Development Plan"),erp_export_label("Updated By"),erp_export_label("Updated At"));
    foreach($heads as $i=>$h)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);
    $rnum=5;$n=1;foreach($rows as $r){$vals=array($n++,$r->appraisal_no,$r->cycle_year,$r->appraisal_period,$r->appraisal_type,$r->appraisal_date,$r->employee_no.' - '.$r->full_name,$r->department_code.' - '.$r->nm_dept,$r->job_title_code.' - '.$r->job_title_name,$r->appraiser_no.' - '.$r->appraiser_name,$r->hr_no?($r->hr_no.' - '.$r->hr_name):'',(float)$r->kpi_score,(float)$r->competency_score,(float)$r->behavior_score,(float)$r->final_score,$r->final_rating,$r->calibration_status,$r->decision,$r->decision_by,$r->decision_at,$r->improvement_required,$r->reward_recommendation,$r->development_plan,$r->updated_by?:$r->created_by,$r->updated_at?:$r->created_at);foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rnum,$v);$rnum++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('APPRAISAL APPROVAL REPORT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rnum-1),'column_count'=>count($heads),'numeric_columns'=>array('L','M','N','O'),'filters'=>array('Tanggal'=>$from.' s/d '.$to,'Status'=>$status?:erp_export_all_text(),'Rating'=>$rating?:erp_export_all_text(),'Department'=>$dept?:erp_export_all_text(),'Year'=>$year?:erp_export_all_text())));
    $tmp=erpkb_excel_temp_file('appraisal_approval_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);
    if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
    while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="appraisal_approval_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:
    aa_json('error','Action tidak dikenal.');
}
?>
