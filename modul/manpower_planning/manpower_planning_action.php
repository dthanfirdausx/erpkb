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

function mp_json($s,$m='',$x=array()){header('Content-Type: application/json; charset=utf-8');$p=array('status'=>$s);if($m!=='')$p[$s==='good'?'message':'error_message']=$m;foreach($x as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function mp_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function mp_p($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function mp_g($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
function mp_c($v){return strtoupper(trim((string)$v));}
function mp_user(){return isset($_SESSION['username'])&&$_SESSION['username']!==''?$_SESSION['username']:'system';}
function mp_select2($rows,$id,$cb){$out=array();foreach($rows as $r)$out[]=array('id'=>$r->$id,'text'=>$cb($r));header('Content-Type: application/json; charset=utf-8');echo json_encode(array('results'=>$out));exit;}
function mp_next_no($year){
  global $db;
  $year=(int)$year>0?(int)$year:(int)date('Y');
  $r=$db->fetch("SELECT plan_no FROM erp_manpower_plan WHERE plan_no LIKE ? ORDER BY plan_no DESC LIMIT 1",array('MPP-'.$year.'-%'));
  $n=1;if($r&&preg_match('/-(\d+)$/',$r->plan_no,$m))$n=(int)$m[1]+1;
  return 'MPP-'.$year.'-'.str_pad($n,3,'0',STR_PAD_LEFT);
}
function mp_row($id){
  global $db;
  return $db->fetch("SELECT p.*,d.nm_dept,cs.structure_code,cs.structure_name,cc.cost_center_name,pc.profit_center_name,e.employee_no,e.full_name
    FROM erp_manpower_plan p
    LEFT JOIN dept d ON d.kd_dept=p.department_code
    LEFT JOIN erp_company_structure cs ON cs.id=p.company_structure_id
    LEFT JOIN erp_cost_center cc ON cc.cost_center_code=p.cost_center_code
    LEFT JOIN erp_profit_center pc ON pc.profit_center_code=p.profit_center_code
    LEFT JOIN erp_employee_master e ON e.id=p.approved_by_employee_id
    WHERE p.id=? LIMIT 1",array((int)$id));
}
function mp_payload($r){
  global $db;
  $d=(array)$r;
  $d['company_structure_text']=$r->company_structure_id?($r->structure_code.' - '.$r->structure_name):'';
  $d['department_text']=$r->department_code?($r->department_code.' - '.$r->nm_dept):'';
  $d['cost_center_text']=$r->cost_center_code?($r->cost_center_code.' - '.$r->cost_center_name):'';
  $d['profit_center_text']=$r->profit_center_code?($r->profit_center_code.' - '.$r->profit_center_name):'';
  $d['approved_by_text']=$r->approved_by_employee_id?($r->employee_no.' - '.$r->full_name):'';
  $lines=$db->query("SELECT x.*,d.nm_dept,pos.position_code,pos.position_name,jt.job_title_code,jt.job_title_name
    FROM erp_manpower_plan_detail x
    LEFT JOIN dept d ON d.kd_dept=x.department_code
    LEFT JOIN erp_position pos ON pos.id=x.position_id
    LEFT JOIN erp_job_title jt ON jt.id=x.job_title_id
    WHERE x.plan_id=? ORDER BY x.line_no,x.id",array((int)$r->id));
  $d['lines']=array();
  foreach($lines as $l){
    $a=(array)$l;
    $a['department_text']=$l->department_code?($l->department_code.' - '.$l->nm_dept):'';
    $a['position_text']=$l->position_id?($l->position_code.' - '.$l->position_name):'';
    $a['job_title_text']=$l->job_title_id?($l->job_title_code.' - '.$l->job_title_name):'';
    $d['lines'][]=$a;
  }
  return $d;
}
function mp_export_rows($from,$to,$type,$status,$dept,$kw){
  global $db;
  $p=array($to,$from);$w=" WHERE p.period_from<=? AND p.period_to>=? ";
  if($type!==''){$w.=" AND p.planning_type=? ";$p[]=$type;}
  if($status!==''){$w.=" AND p.planning_status=? ";$p[]=$status;}
  if($dept!==''){$w.=" AND (p.department_code=? OR EXISTS(SELECT 1 FROM erp_manpower_plan_detail x WHERE x.plan_id=p.id AND x.department_code=?)) ";array_push($p,$dept,$dept);}
  if($kw!==''){$like='%'.$kw.'%';$w.=" AND (p.plan_no LIKE ? OR p.plan_name LIKE ? OR p.sap_reference LIKE ?) ";array_push($p,$like,$like,$like);}
  return $db->query("SELECT p.*,d.nm_dept,cs.structure_code,cs.structure_name,cc.cost_center_name,pc.profit_center_name,e.employee_no,e.full_name
    FROM erp_manpower_plan p
    LEFT JOIN dept d ON d.kd_dept=p.department_code
    LEFT JOIN erp_company_structure cs ON cs.id=p.company_structure_id
    LEFT JOIN erp_cost_center cc ON cc.cost_center_code=p.cost_center_code
    LEFT JOIN erp_profit_center pc ON pc.profit_center_code=p.profit_center_code
    LEFT JOIN erp_employee_master e ON e.id=p.approved_by_employee_id
    $w ORDER BY p.period_from DESC,p.plan_no DESC",$p);
}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=mp_user();
$types=array('ANNUAL','QUARTERLY','MONTHLY','PROJECT','REPLACEMENT');
$statuses=array('DRAFT','SUBMITTED','APPROVED','REJECTED','CLOSED');
$groups=array('DIRECTOR','MANAGER','STAFF','NON_STAFF','OPERATOR','CONTRACT','DAILY_WORKER','TRAINEE');
$hireTypes=array('NEW_HIRE','REPLACEMENT','TRANSFER','CONTRACT_EXTENSION','OUTSOURCE');
$priorities=array('LOW','MEDIUM','HIGH','CRITICAL');
$recStatuses=array('NOT_STARTED','OPEN','IN_PROGRESS','OFFER','HIRED','CANCELLED');

switch($act){
  case 'next_no':
    mp_json('good','',array('plan_no'=>mp_next_no((int)mp_p('plan_year',date('Y')))));
  case 'department_search':
    $term=trim(mp_p('term'));$like='%'.$term.'%';$rows=$db->query("SELECT kd_dept,nm_dept,dept_type FROM dept WHERE status='ACTIVE' AND (?='' OR kd_dept LIKE ? OR nm_dept LIKE ?) ORDER BY kd_dept LIMIT 40",array($term,$like,$like));mp_select2($rows,'kd_dept',function($r){return $r->kd_dept.' - '.$r->nm_dept.' ['.$r->dept_type.']';});
  case 'company_structure_search':
    $term=trim(mp_p('term'));$like='%'.$term.'%';$rows=$db->query("SELECT id,structure_code,structure_name,structure_type FROM erp_company_structure WHERE status='ACTIVE' AND (?='' OR structure_code LIKE ? OR structure_name LIKE ? OR structure_type LIKE ?) ORDER BY structure_code LIMIT 40",array($term,$like,$like,$like));mp_select2($rows,'id',function($r){return $r->structure_code.' - '.$r->structure_name.' ['.$r->structure_type.']';});
  case 'cost_center_search':
    $term=trim(mp_p('term'));$like='%'.$term.'%';$rows=$db->query("SELECT cost_center_code,cost_center_name FROM erp_cost_center WHERE status='Aktif' AND (?='' OR cost_center_code LIKE ? OR cost_center_name LIKE ?) ORDER BY cost_center_code LIMIT 40",array($term,$like,$like));mp_select2($rows,'cost_center_code',function($r){return $r->cost_center_code.' - '.$r->cost_center_name;});
  case 'profit_center_search':
    $term=trim(mp_p('term'));$like='%'.$term.'%';$rows=$db->query("SELECT profit_center_code,profit_center_name FROM erp_profit_center WHERE status='Aktif' AND (?='' OR profit_center_code LIKE ? OR profit_center_name LIKE ?) ORDER BY profit_center_code LIMIT 40",array($term,$like,$like));mp_select2($rows,'profit_center_code',function($r){return $r->profit_center_code.' - '.$r->profit_center_name;});
  case 'employee_search':
    $term=trim(mp_p('term'));$like='%'.$term.'%';$rows=$db->query("SELECT id,employee_no,full_name,employment_status FROM erp_employee_master WHERE employment_status IN ('ACTIVE','PROBATION','CONTRACT') AND (?='' OR employee_no LIKE ? OR full_name LIKE ?) ORDER BY employee_no LIMIT 40",array($term,$like,$like));mp_select2($rows,'id',function($r){return $r->employee_no.' - '.$r->full_name.' ['.$r->employment_status.']';});
  case 'job_title_search':
    $term=trim(mp_p('term'));$like='%'.$term.'%';$rows=$db->query("SELECT id,job_title_code,job_title_name,job_level FROM erp_job_title WHERE status='ACTIVE' AND (?='' OR job_title_code LIKE ? OR job_title_name LIKE ?) ORDER BY job_title_code LIMIT 40",array($term,$like,$like));mp_select2($rows,'id',function($r){return $r->job_title_code.' - '.$r->job_title_name.' ['.$r->job_level.']';});
  case 'position_search':
    $term=trim(mp_p('term'));$like='%'.$term.'%';$rows=$db->query("SELECT id,position_code,position_name,vacancy_status FROM erp_position WHERE position_status IN ('APPROVED','ACTIVE','PLANNED') AND (?='' OR position_code LIKE ? OR position_name LIKE ?) ORDER BY position_code LIMIT 40",array($term,$like,$like));mp_select2($rows,'id',function($r){return $r->position_code.' - '.$r->position_name.' ['.$r->vacancy_status.']';});
  case 'position_info':
    $id=(int)mp_p('id');$r=$db->fetch("SELECT p.*,d.nm_dept,jt.job_title_code,jt.job_title_name FROM erp_position p LEFT JOIN dept d ON d.kd_dept=p.department_code LEFT JOIN erp_job_title jt ON jt.id=p.job_title_id WHERE p.id=? LIMIT 1",array($id));
    if(!$r)mp_json('error','Position tidak ditemukan.');
    mp_json('good','',array('data'=>array(
      'department_code'=>$r->department_code,'department_text'=>$r->department_code?($r->department_code.' - '.$r->nm_dept):'',
      'job_title_id'=>$r->job_title_id,'job_title_text'=>$r->job_title_id?($r->job_title_code.' - '.$r->job_title_name):'',
      'employee_group'=>$r->employee_group,'pay_grade'=>$r->pay_grade,'current_headcount'=>$r->occupied_fte,'current_fte'=>$r->occupied_fte,'planned_headcount'=>$r->headcount_plan,'planned_fte'=>$r->planned_fte
    )));
  case 'get':
    $r=mp_row((int)mp_p('id'));if(!$r)mp_json('error','Manpower Plan tidak ditemukan.');mp_json('good','',array('data'=>mp_payload($r)));
  case 'save':
    $id=(int)mp_p('id');$year=(int)mp_p('plan_year',date('Y'));$planNo=mp_c(mp_p('plan_no'));$name=trim(mp_p('plan_name'));
    $type=mp_c(mp_p('planning_type','ANNUAL'));$status=mp_c(mp_p('planning_status','DRAFT'));$from=trim(mp_p('period_from'));$to=trim(mp_p('period_to'));
    if($planNo===''||$planNo==='AUTO')$planNo=mp_next_no($year);
    if($name==='')mp_json('error','Plan Name wajib diisi.');
    if($year<2000||$year>2100)mp_json('error','Plan Year tidak valid.');
    if(!in_array($type,$types,true))mp_json('error','Planning Type tidak valid.');
    if(!in_array($status,$statuses,true))mp_json('error','Planning Status tidak valid.');
    if($from===''||$to===''||strtotime($to)<strtotime($from))mp_json('error','Period From/To tidak valid.');
    if($db->fetch("SELECT id FROM erp_manpower_plan WHERE plan_no=? AND id<>? LIMIT 1",array($planNo,$id)))mp_json('error','Plan No sudah digunakan.');
    $lines=json_decode(mp_p('lines_json','[]'),true);
    if(!is_array($lines)||count($lines)<1)mp_json('error','Minimal satu detail manpower plan wajib diisi.');
    $tot=array('current'=>0,'planned'=>0,'requested'=>0,'gap'=>0,'budget'=>0);$clean=array();$ln=1;
    foreach($lines as $line){
      $dept=mp_c(isset($line['department_code'])?$line['department_code']:'');$pos=isset($line['position_id'])&&$line['position_id']!==''?(int)$line['position_id']:null;$job=isset($line['job_title_id'])&&$line['job_title_id']!==''?(int)$line['job_title_id']:null;
      $grp=mp_c(isset($line['employee_group'])?$line['employee_group']:'STAFF');$hire=mp_c(isset($line['hire_type'])?$line['hire_type']:'NEW_HIRE');$pri=mp_c(isset($line['priority'])?$line['priority']:'MEDIUM');$rec=mp_c(isset($line['recruitment_status'])?$line['recruitment_status']:'NOT_STARTED');
      $cur=(float)(isset($line['current_headcount'])?$line['current_headcount']:0);$curFte=(float)(isset($line['current_fte'])?$line['current_fte']:0);$planned=(float)(isset($line['planned_headcount'])?$line['planned_headcount']:0);$plannedFte=(float)(isset($line['planned_fte'])?$line['planned_fte']:0);$req=(float)(isset($line['requested_headcount'])?$line['requested_headcount']:0);$appr=(float)(isset($line['approved_headcount'])?$line['approved_headcount']:0);$monthly=(float)(isset($line['estimated_monthly_cost'])?$line['estimated_monthly_cost']:0);$budget=(float)(isset($line['budget_amount'])?$line['budget_amount']:0);
      $gap=$planned-$cur;if($req<=0)$req=max(0,$gap);if($budget<=0)$budget=$monthly*$req*12;
      if($dept==='' && !$pos && !$job)mp_json('error','Detail baris '.$ln.' wajib memiliki Department, Position, atau Job Title.');
      if($planned<0||$req<0||$cur<0)mp_json('error','Headcount baris '.$ln.' tidak boleh negatif.');
      if(!in_array($grp,$groups,true))$grp='STAFF';if(!in_array($hire,$hireTypes,true))$hire='NEW_HIRE';if(!in_array($pri,$priorities,true))$pri='MEDIUM';if(!in_array($rec,$recStatuses,true))$rec='NOT_STARTED';
      $row=array('line_no'=>$ln,'department_code'=>$dept?:null,'position_id'=>$pos,'job_title_id'=>$job,'employee_group'=>$grp,'pay_grade'=>trim(isset($line['pay_grade'])?$line['pay_grade']:''),'current_headcount'=>$cur,'current_fte'=>$curFte,'planned_headcount'=>$planned,'planned_fte'=>$plannedFte,'requested_headcount'=>$req,'approved_headcount'=>$appr,'gap_headcount'=>$gap,'hire_type'=>$hire,'priority'=>$pri,'target_hire_date'=>trim(isset($line['target_hire_date'])?$line['target_hire_date']:''),'estimated_monthly_cost'=>$monthly,'budget_amount'=>$budget,'recruitment_status'=>$rec,'reason'=>trim(isset($line['reason'])?$line['reason']:''),'remarks'=>trim(isset($line['remarks'])?$line['remarks']:''));
      $clean[]=$row;$tot['current']+=$cur;$tot['planned']+=$planned;$tot['requested']+=$req;$tot['gap']+=$gap;$tot['budget']+=$budget;$ln++;
    }
    $approvedBy=mp_p('approved_by_employee_id')!==''?(int)mp_p('approved_by_employee_id'):null;
    $approvedAt=$status==='APPROVED'?(mp_p('approved_at')!==''?mp_p('approved_at'):date('Y-m-d H:i:s')):null;
    $data=array('plan_no'=>$planNo,'plan_name'=>$name,'plan_year'=>$year,'plan_version'=>trim(mp_p('plan_version','V1')),'planning_type'=>$type,'planning_status'=>$status,'period_from'=>$from,'period_to'=>$to,'company_structure_id'=>mp_p('company_structure_id')!==''?(int)mp_p('company_structure_id'):null,'department_code'=>mp_c(mp_p('department_code'))?:null,'cost_center_code'=>trim(mp_p('cost_center_code')),'profit_center_code'=>trim(mp_p('profit_center_code')),'budget_currency'=>mp_c(mp_p('budget_currency','IDR'))?:'IDR','total_current_headcount'=>$tot['current'],'total_planned_headcount'=>$tot['planned'],'total_requested_headcount'=>$tot['requested'],'total_gap_headcount'=>$tot['gap'],'total_budget_amount'=>$tot['budget'],'approved_by_employee_id'=>$approvedBy,'approved_at'=>$approvedAt,'sap_reference'=>trim(mp_p('sap_reference')),'remarks'=>trim(mp_p('remarks')),'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s'));
    if($id>0){if(!mp_row($id))mp_json('error','Manpower Plan tidak ditemukan.');$ok=$db->update('erp_manpower_plan',$data,'id',$id);}else{$data['created_by']=$username;$ok=$db->insert('erp_manpower_plan',$data);$id=(int)$db->last_insert_id();}
    if(!$ok)mp_json('error',$db->getErrorMessage()?:'Manpower Plan gagal disimpan.');
    $db->delete('erp_manpower_plan_detail','plan_id',$id);
    foreach($clean as $row){$row['plan_id']=$id;$db->insert('erp_manpower_plan_detail',$row);}
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menyimpan Manpower Planning '.$planNo.' dengan total request HC '.$tot['requested'].' pada '.date('Y-m-d H:i:s'),$username);
    mp_json('good','Manpower Planning berhasil disimpan.',array('id'=>$id,'plan_no'=>$planNo));
  case 'status':
    $id=(int)mp_p('id');$status=mp_c(mp_p('status'));if(!in_array($status,$statuses,true))mp_json('error','Status tidak valid.');
    $r=mp_row($id);if(!$r)mp_json('error','Manpower Plan tidak ditemukan.');
    $data=array('planning_status'=>$status,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s'));
    if($status==='APPROVED'){$data['approved_at']=date('Y-m-d H:i:s');}
    $ok=$db->update('erp_manpower_plan',$data,'id',$id);if(!$ok)mp_json('error',$db->getErrorMessage()?:'Status gagal diubah.');
    if(function_exists('simpan_log'))simpan_log('User '.$username.' mengubah status Manpower Planning '.$r->plan_no.' menjadi '.$status,$username);
    mp_json('good','Status berhasil diubah.');
  case 'delete':
    $id=(int)mp_p('id');$r=mp_row($id);if(!$r)mp_json('error','Manpower Plan tidak ditemukan.');if($r->planning_status!=='DRAFT')mp_json('error','Hanya status DRAFT yang boleh dihapus.');
    $db->delete('erp_manpower_plan','id',$id);if($db->getErrorMessage())mp_json('error',$db->getErrorMessage());
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menghapus Manpower Planning '.$r->plan_no,$username);
    mp_json('good','Manpower Planning berhasil dihapus.');
  case 'detail':
    $r=mp_row((int)mp_p('id'));if(!$r){echo '<div class="alert alert-warning">Manpower Plan tidak ditemukan.</div>';break;}
    $lines=$db->query("SELECT x.*,d.nm_dept,pos.position_code,pos.position_name,jt.job_title_code,jt.job_title_name FROM erp_manpower_plan_detail x LEFT JOIN dept d ON d.kd_dept=x.department_code LEFT JOIN erp_position pos ON pos.id=x.position_id LEFT JOIN erp_job_title jt ON jt.id=x.job_title_id WHERE x.plan_id=? ORDER BY x.line_no,x.id",array((int)$r->id));
    echo '<div class="mp-detail"><h3 style="margin-top:0">'.mp_h($r->plan_no).' <small>'.mp_h($r->plan_name).'</small></h3><span class="label label-info">'.mp_h($r->planning_type).'</span> <span class="label label-success">'.mp_h($r->planning_status).'</span><hr><div class="row"><div class="col-sm-3"><b>'.hr_h('hr_period', 'Period').'</b><br>'.mp_h($r->period_from.' s/d '.$r->period_to).'</div><div class="col-sm-3"><b>Org Unit</b><br>'.mp_h($r->structure_code?($r->structure_code.' - '.$r->structure_name):'All Org').'</div><div class="col-sm-3"><b>'.hr_h('hr_department', 'Department').'</b><br>'.mp_h($r->department_code?($r->department_code.' - '.$r->nm_dept):'All Dept').'</div><div class="col-sm-3"><b>Approved By</b><br>'.mp_h($r->employee_no?($r->employee_no.' - '.$r->full_name):'-').'</div></div><hr><div class="row"><div class="col-sm-3"><b>Current HC</b><br>'.number_format((float)$r->total_current_headcount,2).'</div><div class="col-sm-3"><b>Planned HC</b><br>'.number_format((float)$r->total_planned_headcount,2).'</div><div class="col-sm-3"><b>Requested / Gap</b><br>'.number_format((float)$r->total_requested_headcount,2).' / '.number_format((float)$r->total_gap_headcount,2).'</div><div class="col-sm-3"><b>Budget</b><br>'.mp_h($r->budget_currency).' '.number_format((float)$r->total_budget_amount,2).'</div></div><hr><h4>Position Requirement Lines</h4><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr><th>'.hr_h('common_no', 'No').'</th><th>'.hr_h('hr_department', 'Department').'</th><th>Position / Job</th><th>Group</th><th class="text-right">Current</th><th class="text-right">Planned</th><th class="text-right">Request</th><th>Hire / Priority</th><th>Target</th><th class="text-right">Budget</th><th>'.hr_h('common_status', 'Status').'</th><th>Reason</th></tr></thead><tbody>';
    foreach($lines as $l){echo '<tr><td>'.(int)$l->line_no.'</td><td>'.mp_h($l->department_code.' - '.$l->nm_dept).'</td><td><b>'.mp_h($l->position_code?:'-').'</b><br><small>'.mp_h(($l->position_name?:'').' / '.($l->job_title_code?:'-').' '.$l->job_title_name).'</small></td><td>'.mp_h($l->employee_group.' / '.$l->pay_grade).'</td><td class="text-right">'.number_format((float)$l->current_headcount,2).'</td><td class="text-right">'.number_format((float)$l->planned_headcount,2).'</td><td class="text-right">'.number_format((float)$l->requested_headcount,2).'</td><td>'.mp_h($l->hire_type.' / '.$l->priority).'</td><td>'.mp_h($l->target_hire_date?:'-').'</td><td class="text-right">'.number_format((float)$l->budget_amount,2).'</td><td>'.mp_h($l->recruitment_status).'</td><td>'.mp_h($l->reason).'</td></tr>';}
    echo '</tbody></table></div><hr><p><b>SAP Reference:</b> '.mp_h($r->sap_reference?:'-').'<br><b>Remarks:</b> '.nl2br(mp_h($r->remarks?:'-')).'</p></div>';break;
  case 'export':
    $initial=ob_get_level();ob_start();error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $from=mp_g('tgl_awal',date('Y-01-01'));$to=mp_g('tgl_akhir',date('Y-12-31'));$type=mp_g('planning_type','');$status=mp_g('planning_status','');$dept=mp_g('department_code','');$kw=mp_g('keyword','');$rows=mp_export_rows($from,$to,$type,$status,$dept,$kw);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Manpower Planning'));$heads=array(erp_export_label("No"),erp_export_label("Plan No"),erp_export_label("Plan Name"),erp_export_label("Year"),erp_export_label("Version"),erp_export_label("Type"),erp_export_label("Status"),erp_export_label("Period"),erp_export_label("Org Unit"),erp_export_label("Department"),erp_export_label("Cost Center"),erp_export_label("Profit Center"),erp_export_label("Current HC"),erp_export_label("Planned HC"),erp_export_label("Requested HC"),erp_export_label("Gap HC"),erp_export_label("Budget"),erp_export_label("Currency"),erp_export_label("Approved By"),erp_export_label("SAP Ref"),erp_export_label("Updated By"));
    foreach($heads as $i=>$h)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);
    $rn=5;$n=1;foreach($rows as $r){$vals=array($n++,$r->plan_no,$r->plan_name,$r->plan_year,$r->plan_version,$r->planning_type,$r->planning_status,$r->period_from.' s/d '.$r->period_to,$r->structure_code.' - '.$r->structure_name,$r->department_code.' - '.$r->nm_dept,$r->cost_center_code.' - '.$r->cost_center_name,$r->profit_center_code.' - '.$r->profit_center_name,(float)$r->total_current_headcount,(float)$r->total_planned_headcount,(float)$r->total_requested_headcount,(float)$r->total_gap_headcount,(float)$r->total_budget_amount,$r->budget_currency,$r->employee_no.' - '.$r->full_name,$r->sap_reference,$r->updated_by?:$r->created_by);foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn,$v);$rn++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('MANPOWER PLANNING REPORT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rn-1),'column_count'=>count($heads),'decimal_columns'=>array('M','N','O','P','Q'),'filters'=>array('Period'=>$from.' s/d '.$to,'Type'=>$type?:erp_export_all_text(),'Status'=>$status?:erp_export_all_text(),'Department'=>$dept?:erp_export_all_text()),'widths'=>array('B'=>18,'C'=>42,'H'=>24,'I'=>30,'J'=>25,'K'=>25,'L'=>25,'S'=>28,'T'=>24)));
    $tmp=erpkb_excel_temp_file('manpower_planning_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);
    if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
    while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="manpower_planning_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:
    mp_json('error','Action tidak dikenal.');
}
?>
