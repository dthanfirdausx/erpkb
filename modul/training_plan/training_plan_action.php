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

function tp_json($status,$message='',$extra=array()){
  header('Content-Type: application/json; charset=utf-8');
  $payload=array('status'=>$status);
  if($message!=='')$payload[$status==='good'?'message':'error_message']=$message;
  foreach($extra as $k=>$v)$payload[$k]=$v;
  echo json_encode($payload);
  exit;
}
function tp_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function tp_p($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function tp_g($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
function tp_code($v){return strtoupper(trim((string)$v));}
function tp_in($v,$arr){return in_array($v,$arr,true);}
function tp_select2($rows,$idField,$textCb){
  $results=array();
  foreach($rows as $r)$results[]=array('id'=>$r->$idField,'text'=>$textCb($r));
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('results'=>$results));
  exit;
}
function tp_next_code(){
  global $db;
  $prefix='TPL-'.date('Y').'-';
  $r=$db->fetch("SELECT plan_code FROM erp_training_plan WHERE plan_code LIKE ? ORDER BY plan_code DESC LIMIT 1",array($prefix.'%'));
  $n=1;
  if($r && preg_match('/(\d+)$/',$r->plan_code,$m))$n=(int)$m[1]+1;
  return $prefix.str_pad($n,4,'0',STR_PAD_LEFT);
}
function tp_row($id){
  global $db;
  return $db->fetch("SELECT tp.*,tc.training_code,tc.training_name,d.nm_dept,jt.job_title_code,jt.job_title_name
    FROM erp_training_plan tp
    LEFT JOIN erp_training_catalog tc ON tc.id=tp.training_catalog_id
    LEFT JOIN dept d ON d.kd_dept=tp.target_department_code
    LEFT JOIN erp_job_title jt ON jt.id=tp.target_job_title_id
    WHERE tp.id=? LIMIT 1",array((int)$id));
}
function tp_participants($planId){
  global $db;
  return $db->query("SELECT tpp.*,e.employee_no,e.full_name,e.department_code,jt.job_title_code,jt.job_title_name
    FROM erp_training_plan_participant tpp
    JOIN erp_employee_master e ON e.id=tpp.employee_id
    LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id
    WHERE tpp.training_plan_id=?
    ORDER BY e.employee_no",array((int)$planId));
}
function tp_payload($r){
  $data=(array)$r;
  $data['catalog_text']=$r->training_catalog_id?($r->training_code.' - '.$r->training_name):'';
  $data['department_text']=$r->target_department_code?($r->target_department_code.' - '.$r->nm_dept):'';
  $data['job_title_text']=$r->target_job_title_id?($r->job_title_code.' - '.$r->job_title_name):'';
  $parts=tp_participants($r->id);
  $data['participants']=array();
  foreach($parts as $p)$data['participants'][]=array('id'=>$p->employee_id,'text'=>$p->employee_no.' - '.$p->full_name.' ['.$p->department_code.']','status'=>$p->nomination_status,'remarks'=>$p->remarks);
  return $data;
}
function tp_filters_where($src,&$p){
  $from=isset($src['tgl_awal'])&&$src['tgl_awal']!==''?$src['tgl_awal']:date('Y-01-01');
  $to=isset($src['tgl_akhir'])&&$src['tgl_akhir']!==''?$src['tgl_akhir']:date('Y-12-31');
  $w=" WHERE tp.planned_start_date<=? AND tp.planned_end_date>=? ";
  $p[]=$to;$p[]=$from;
  foreach(array('plan_year','training_catalog_id','target_department_code','priority','approval_status','execution_status') as $k){
    if(isset($src[$k])&&$src[$k]!==''){$w.=" AND tp.$k=? ";$p[]=trim($src[$k]);}
  }
  if(isset($src['keyword'])&&$src['keyword']!==''){
    $kw='%'.trim($src['keyword']).'%';
    $w.=" AND (tp.plan_code LIKE ? OR tp.plan_name LIKE ? OR tc.training_code LIKE ? OR tc.training_name LIKE ? OR d.nm_dept LIKE ? OR jt.job_title_name LIKE ? OR tp.plan_owner LIKE ?) ";
    for($i=0;$i<7;$i++)$p[]=$kw;
  }
  return $w;
}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=isset($_SESSION['username'])?$_SESSION['username']:'system';
$periods=array('Q1','Q2','Q3','Q4','MONTHLY','ANNUAL','ADHOC');
$priorities=array('LOW','MEDIUM','HIGH','CRITICAL');
$sources=array('COMPETENCY_GAP','MANDATORY','MANAGER_REQUEST','SUCCESSION','REGULATORY','OTHER');
$approvalStatuses=array('DRAFT','SUBMITTED','APPROVED','REJECTED','CANCELLED','COMPLETED');
$executionStatuses=array('NOT_STARTED','SCHEDULED','IN_PROGRESS','COMPLETED','CANCELLED');
$groups=array('DIRECTOR','MANAGER','STAFF','NON_STAFF','OPERATOR','CONTRACT','DAILY_WORKER','TRAINEE');

switch($act){
  case 'next_code':
    tp_json('good','',array('code'=>tp_next_code()));
    break;
  case 'catalog_search':
    $term=trim(tp_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT id,training_code,training_name,training_category,duration_hours FROM erp_training_catalog WHERE status='ACTIVE' AND (?='' OR training_code LIKE ? OR training_name LIKE ? OR training_category LIKE ?) ORDER BY training_code LIMIT 50",array($term,$like,$like,$like));
    tp_select2($rows,'id',function($r){return $r->training_code.' - '.$r->training_name.' ['.$r->training_category.', '.$r->duration_hours.' jam]';});
    break;
  case 'department_search':
    $term=trim(tp_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT kd_dept,nm_dept,dept_type FROM dept WHERE status='ACTIVE' AND (?='' OR kd_dept LIKE ? OR nm_dept LIKE ? OR dept_type LIKE ?) ORDER BY kd_dept LIMIT 50",array($term,$like,$like,$like));
    tp_select2($rows,'kd_dept',function($r){return $r->kd_dept.' - '.$r->nm_dept.' ['.$r->dept_type.']';});
    break;
  case 'job_title_search':
    $term=trim(tp_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT id,job_title_code,job_title_name,job_level FROM erp_job_title WHERE status IN ('DRAFT','ACTIVE') AND (?='' OR job_title_code LIKE ? OR job_title_name LIKE ?) ORDER BY job_title_code LIMIT 50",array($term,$like,$like));
    tp_select2($rows,'id',function($r){return $r->job_title_code.' - '.$r->job_title_name.' ['.$r->job_level.']';});
    break;
  case 'employee_search':
    $term=trim(tp_p('term'));$dept=trim(tp_p('department_code'));$like='%'.$term.'%';
    $rows=$db->query("SELECT e.id,e.employee_no,e.full_name,e.department_code,jt.job_title_code FROM erp_employee_master e LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id WHERE e.employment_status IN ('ACTIVE','PROBATION','CONTRACT') AND (?='' OR e.department_code=?) AND (?='' OR e.employee_no LIKE ? OR e.full_name LIKE ? OR e.department_code LIKE ? OR jt.job_title_name LIKE ?) ORDER BY e.employee_no LIMIT 80",array($dept,$dept,$term,$like,$like,$like,$like));
    tp_select2($rows,'id',function($r){return $r->employee_no.' - '.$r->full_name.' ['.$r->department_code.' / '.($r->job_title_code?:'-').']';});
    break;
  case 'get':
    $r=tp_row((int)tp_p('id'));
    if(!$r)tp_json('error','Training Plan tidak ditemukan.');
    tp_json('good','',array('data'=>tp_payload($r)));
    break;
  case 'save':
    $id=(int)tp_p('id');
    $code=tp_code(tp_p('plan_code'));
    $name=trim(tp_p('plan_name'));
    $catalogId=(int)tp_p('training_catalog_id');
    $year=(int)tp_p('plan_year');
    $period=tp_code(tp_p('plan_period'));
    $start=trim(tp_p('planned_start_date'));
    $end=trim(tp_p('planned_end_date'));
    $dept=tp_code(tp_p('target_department_code'));
    $jobId=tp_p('target_job_title_id')!==''?(int)tp_p('target_job_title_id'):null;
    $group=tp_code(tp_p('target_employee_group'));
    $priority=tp_code(tp_p('priority','MEDIUM'));
    $source=tp_code(tp_p('source_type','COMPETENCY_GAP'));
    $approval=tp_code(tp_p('approval_status','DRAFT'));
    $execution=tp_code(tp_p('execution_status','NOT_STARTED'));
    if($code==='')$code=tp_next_code();
    if(!preg_match('/^[A-Z0-9_-]{3,30}$/',$code))tp_json('error','Plan Code hanya boleh huruf besar, angka, underscore, atau dash.');
    if($name==='')tp_json('error','Plan Name wajib diisi.');
    $cat=$db->fetch("SELECT id FROM erp_training_catalog WHERE id=? AND status='ACTIVE' LIMIT 1",array($catalogId));
    if(!$cat)tp_json('error','Training Catalog wajib dipilih dan harus ACTIVE.');
    if($year<2000||$year>2100)tp_json('error','Plan Year tidak valid.');
    if(!tp_in($period,$periods))tp_json('error','Plan Period tidak valid.');
    if($start===''||$end==='')tp_json('error','Planned Start dan End wajib diisi.');
    if(strtotime($end)<strtotime($start))tp_json('error','Planned End tidak boleh sebelum Planned Start.');
    if($dept!==''){
      $d=$db->fetch("SELECT kd_dept FROM dept WHERE kd_dept=? AND status='ACTIVE' LIMIT 1",array($dept));
      if(!$d)tp_json('error','Target Department tidak valid.');
    }
    if($jobId){
      $j=$db->fetch("SELECT id FROM erp_job_title WHERE id=? AND status IN ('DRAFT','ACTIVE') LIMIT 1",array($jobId));
      if(!$j)tp_json('error','Target Job Title tidak valid.');
    }
    if($group!=='' && !tp_in($group,$groups))tp_json('error','Target Employee Group tidak valid.');
    if(!tp_in($priority,$priorities))tp_json('error','Priority tidak valid.');
    if(!tp_in($source,$sources))tp_json('error','Source Type tidak valid.');
    if(!tp_in($approval,$approvalStatuses)||!tp_in($execution,$executionStatuses))tp_json('error','Status tidak valid.');
    $dup=$db->fetch("SELECT id FROM erp_training_plan WHERE plan_code=? AND id<>? LIMIT 1",array($code,$id));
    if($dup)tp_json('error','Plan Code sudah digunakan.');
    $participants=isset($_POST['participants'])&&is_array($_POST['participants'])?array_unique(array_filter(array_map('intval',$_POST['participants']))):array();
    $data=array(
      'plan_code'=>$code,
      'plan_name'=>$name,
      'training_catalog_id'=>$catalogId,
      'plan_year'=>$year,
      'plan_period'=>$period,
      'planned_start_date'=>$start,
      'planned_end_date'=>$end,
      'target_department_code'=>$dept!==''?$dept:null,
      'target_job_title_id'=>$jobId,
      'target_employee_group'=>$group!==''?$group:null,
      'priority'=>$priority,
      'source_type'=>$source,
      'planned_participant'=>max((int)tp_p('planned_participant',0),count($participants)),
      'budget_amount'=>max(0,(float)tp_p('budget_amount',0)),
      'currency'=>tp_code(tp_p('currency','IDR')),
      'plan_owner'=>trim(tp_p('plan_owner')),
      'location'=>trim(tp_p('location')),
      'approval_status'=>$approval,
      'execution_status'=>$execution,
      'business_reason'=>trim(tp_p('business_reason')),
      'remarks'=>trim(tp_p('remarks')),
      'updated_by'=>$username,
      'updated_at'=>date('Y-m-d H:i:s')
    );
    if($id>0){
      $old=$db->fetch("SELECT id FROM erp_training_plan WHERE id=? LIMIT 1",array($id));
      if(!$old)tp_json('error','Training Plan tidak ditemukan.');
      $ok=$db->update('erp_training_plan',$data,'id',$id);
    }else{
      $data['created_by']=$username;
      $ok=$db->insert('erp_training_plan',$data);
      $id=$db->last_insert_id();
    }
    if(!$ok)tp_json('error',$db->getErrorMessage()?:'Training Plan gagal disimpan.');
    $db->query("DELETE FROM erp_training_plan_participant WHERE training_plan_id=?",array($id));
    foreach($participants as $empId){
      $e=$db->fetch("SELECT id FROM erp_employee_master WHERE id=? AND employment_status IN ('ACTIVE','PROBATION','CONTRACT') LIMIT 1",array($empId));
      if($e)$db->insert('erp_training_plan_participant',array('training_plan_id'=>$id,'employee_id'=>$empId,'nomination_status'=>'PLANNED','created_by'=>$username));
    }
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menyimpan Training Plan '.$code.' - '.$name.' pada '.date('Y-m-d H:i:s'),$username);
    tp_json('good','Training Plan berhasil disimpan.',array('id'=>$id));
    break;
  case 'delete':
    $id=(int)tp_p('id');
    $r=$db->fetch("SELECT * FROM erp_training_plan WHERE id=? LIMIT 1",array($id));
    if(!$r)tp_json('error','Training Plan tidak ditemukan.');
    if(in_array($r->approval_status,array('APPROVED','COMPLETED'),true))tp_json('error','Training Plan APPROVED/COMPLETED tidak boleh dihapus. Cancel terlebih dahulu.');
    $db->query("DELETE FROM erp_training_plan_participant WHERE training_plan_id=?",array($id));
    $db->delete('erp_training_plan','id',$id);
    if($db->getErrorMessage())tp_json('error',$db->getErrorMessage());
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menghapus Training Plan '.$r->plan_code,$username);
    tp_json('good','Training Plan berhasil dihapus.');
    break;
  case 'status':
    $id=(int)tp_p('id');$status=tp_code(tp_p('status'));
    if(!tp_in($status,$approvalStatuses))tp_json('error','Approval Status tidak valid.');
    $r=$db->fetch("SELECT * FROM erp_training_plan WHERE id=? LIMIT 1",array($id));
    if(!$r)tp_json('error','Training Plan tidak ditemukan.');
    $exec=$r->execution_status;
    if($status==='APPROVED' && $exec==='NOT_STARTED')$exec='SCHEDULED';
    if($status==='CANCELLED')$exec='CANCELLED';
    $ok=$db->update('erp_training_plan',array('approval_status'=>$status,'execution_status'=>$exec,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')),'id',$id);
    if(!$ok)tp_json('error',$db->getErrorMessage()?:'Status gagal diubah.');
    if(function_exists('simpan_log'))simpan_log('User '.$username.' mengubah status Training Plan '.$r->plan_code.' menjadi '.$status,$username);
    tp_json('good','Status berhasil diubah.');
    break;
  case 'detail':
    $r=tp_row((int)tp_p('id'));
    if(!$r){echo '<div class="alert alert-warning">Training Plan tidak ditemukan.</div>';break;}
    $parts=tp_participants($r->id);
    echo '<div class="row"><div class="col-md-8"><h3 style="margin-top:0">'.tp_h($r->plan_code).' <small>'.tp_h($r->plan_name).'</small></h3><span class="label label-info">'.tp_h($r->approval_status).'</span> <span class="label label-primary">'.tp_h($r->execution_status).'</span> <span class="label label-warning">'.tp_h($r->priority).'</span></div><div class="col-md-4 text-right"><strong>'.hr_h('hr_period', 'Period').'</strong><br>'.tp_h($r->planned_start_date.' s/d '.$r->planned_end_date).'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><strong>'.hr_h('hr_training_catalog', 'Training Catalog').'</strong><br>'.tp_h($r->training_code.' - '.$r->training_name).'</div><div class="col-sm-3"><strong>Target Department</strong><br>'.tp_h($r->target_department_code?($r->target_department_code.' - '.$r->nm_dept):'All').'</div><div class="col-sm-3"><strong>Target Job</strong><br>'.tp_h($r->job_title_code?($r->job_title_code.' - '.$r->job_title_name):'All').'</div><div class="col-sm-3"><strong>Budget</strong><br>'.tp_h($r->currency).' '.number_format((float)$r->budget_amount,2).'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-6"><strong>Business Reason</strong><p>'.nl2br(tp_h($r->business_reason?:'-')).'</p></div><div class="col-sm-6"><strong>'.hr_h('common_remarks', 'Remarks').'</strong><p>'.nl2br(tp_h($r->remarks?:'-')).'</p></div></div>';
    echo '<h4>Planned Participants <span class="badge">'.count($parts).'</span></h4><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr><th>'.hr_h('common_no', 'No').'</th><th>'.hr_h('hr_employee', 'Employee').'</th><th>'.hr_h('hr_department', 'Department').'</th><th>Job</th><th>'.hr_h('common_status', 'Status').'</th></tr></thead><tbody>';
    $n=1;foreach($parts as $p){echo '<tr><td>'.$n++.'</td><td>'.tp_h($p->employee_no.' - '.$p->full_name).'</td><td>'.tp_h($p->department_code).'</td><td>'.tp_h($p->job_title_code.' - '.$p->job_title_name).'</td><td>'.tp_h($p->nomination_status).'</td></tr>';}
    if(!count($parts))echo '<tr><td colspan="5" class="text-center text-muted">Belum ada peserta nominasi.</td></tr>';
    echo '</tbody></table></div>';
    break;
  case 'export':
    $initial=ob_get_level();ob_start();error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $p=array();$w=tp_filters_where($_GET,$p);
    $rows=$db->query("SELECT tp.*,tc.training_code,tc.training_name,tc.training_category,d.nm_dept,jt.job_title_code,jt.job_title_name,COUNT(tpp.id) participant_count
      FROM erp_training_plan tp
      LEFT JOIN erp_training_catalog tc ON tc.id=tp.training_catalog_id
      LEFT JOIN dept d ON d.kd_dept=tp.target_department_code
      LEFT JOIN erp_job_title jt ON jt.id=tp.target_job_title_id
      LEFT JOIN erp_training_plan_participant tpp ON tpp.training_plan_id=tp.id
      $w GROUP BY tp.id ORDER BY tp.planned_start_date,tp.plan_code",$p);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Training Plan'));
    $heads=array(erp_export_label("No"),erp_export_label("Plan Code"),erp_export_label("Plan Name"),erp_export_label("Training Code"),erp_export_label("Training Name"),erp_export_label("Category"),erp_export_label("Year"),erp_export_label("Period"),erp_export_label("Start"),erp_export_label("End"),erp_export_label("Department"),erp_export_label("Job Title"),erp_export_label("Employee Group"),erp_export_label("Priority"),erp_export_label("Source"),erp_export_label("Planned Participant"),erp_export_label("Actual Nomination"),erp_export_label("Budget"),erp_export_label("Currency"),erp_export_label("Owner"),erp_export_label("Location"),erp_export_label("Approval Status"),erp_export_label("Execution Status"),erp_export_label("Business Reason"),erp_export_label("Remarks"),erp_export_label("Updated By"));
    foreach($heads as $i=>$h)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);
    $rn=5;$n=1;
    foreach($rows as $r){
      $vals=array($n++,$r->plan_code,$r->plan_name,$r->training_code,$r->training_name,$r->training_category,$r->plan_year,$r->plan_period,$r->planned_start_date,$r->planned_end_date,$r->target_department_code.' - '.$r->nm_dept,$r->job_title_code.' - '.$r->job_title_name,$r->target_employee_group,$r->priority,$r->source_type,$r->planned_participant,$r->participant_count,$r->budget_amount,$r->currency,$r->plan_owner,$r->location,$r->approval_status,$r->execution_status,$r->business_reason,$r->remarks,$r->updated_by?:$r->created_by);
      foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn,$v);
      $rn++;
    }
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('TRAINING PLAN'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rn-1),'column_count'=>count($heads),'filters'=>array('Period'=>array(tp_g('tgl_awal',date('Y-01-01')),tp_g('tgl_akhir',date('Y-12-31'))),'Year'=>tp_g('plan_year',''),'Approval'=>tp_g('approval_status',''),'Execution'=>tp_g('execution_status','')),'decimal_columns'=>array('R'),'money_columns'=>array('R'),'widths'=>array('B'=>18,'C'=>32,'D'=>18,'E'=>34,'K'=>28,'L'=>28,'X'=>40,'Y'=>32)));
    $tmp=erpkb_excel_temp_file('training_plan_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);
    $size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);
    if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
    while(ob_get_level()>$initial)ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="training_plan_'.date('Ymd_His').'.xlsx"');
    header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');
    readfile($tmp);@unlink($tmp);exit;
  default:
    tp_json('error','Action tidak dikenal.');
}
?>
