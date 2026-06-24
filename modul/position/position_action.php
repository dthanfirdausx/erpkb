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

function pos_json($s,$m='',$x=array()){header('Content-Type: application/json; charset=utf-8');$p=array('status'=>$s);if($m!=='')$p[$s==='good'?'message':'error_message']=$m;foreach($x as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function pos_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function pos_p($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function pos_g($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
function pos_c($v){return strtoupper(trim((string)$v));}
function pos_user(){return isset($_SESSION['username'])&&$_SESSION['username']!==''?$_SESSION['username']:'system';}
function pos_select2($rows,$id,$cb){$out=array();foreach($rows as $r)$out[]=array('id'=>$r->$id,'text'=>$cb($r));header('Content-Type: application/json; charset=utf-8');echo json_encode(array('results'=>$out));exit;}
function pos_row($id){
  global $db;
  return $db->fetch("SELECT p.*,jt.job_title_code,jt.job_title_name,d.nm_dept,cs.structure_code,cs.structure_name,cs.structure_type,
    rp.position_code reports_to_code,rp.position_name reports_to_name,e.employee_no holder_no,e.full_name holder_name,
    cc.cost_center_name,pc.profit_center_name,wl.location_code,wl.location_name
    FROM erp_position p
    LEFT JOIN erp_job_title jt ON jt.id=p.job_title_id
    LEFT JOIN dept d ON d.kd_dept=p.department_code
    LEFT JOIN erp_company_structure cs ON cs.id=p.company_structure_id
    LEFT JOIN erp_position rp ON rp.id=p.reports_to_position_id
    LEFT JOIN erp_employee_master e ON e.id=p.holder_employee_id
    LEFT JOIN erp_cost_center cc ON cc.cost_center_code=p.cost_center_code
    LEFT JOIN erp_profit_center pc ON pc.profit_center_code=p.profit_center_code
    LEFT JOIN erp_work_location wl ON wl.id=p.work_location_id
    WHERE p.id=? LIMIT 1",array((int)$id));
}
function pos_payload($r){
  $d=(array)$r;
  $d['job_title_text']=$r->job_title_id?($r->job_title_code.' - '.$r->job_title_name):'';
  $d['department_text']=$r->department_code?($r->department_code.' - '.$r->nm_dept):'';
  $d['company_structure_text']=$r->company_structure_id?($r->structure_code.' - '.$r->structure_name.' ['.$r->structure_type.']'):'';
  $d['reports_to_text']=$r->reports_to_position_id?($r->reports_to_code.' - '.$r->reports_to_name):'';
  $d['holder_text']=$r->holder_employee_id?($r->holder_no.' - '.$r->holder_name):'';
  $d['cost_center_text']=$r->cost_center_code?($r->cost_center_code.' - '.$r->cost_center_name):'';
  $d['profit_center_text']=$r->profit_center_code?($r->profit_center_code.' - '.$r->profit_center_name):'';
  $d['work_location_text']=$r->work_location_id?($r->location_code.' - '.$r->location_name):'';
  return $d;
}
function pos_cycle($id,$parent){
  global $db;
  if($id<=0||$parent<=0)return false;
  if($id===$parent)return true;
  $cur=$parent;$guard=0;
  while($cur>0 && $guard<60){
    $r=$db->fetch("SELECT reports_to_position_id FROM erp_position WHERE id=? LIMIT 1",array($cur));
    if(!$r||!(int)$r->reports_to_position_id)return false;
    $cur=(int)$r->reports_to_position_id;
    if($cur===$id)return true;
    $guard++;
  }
  return false;
}
function pos_export_rows($from,$to,$dept,$status,$vacancy,$kw){
  global $db;
  $w=" WHERE p.valid_from<=? AND p.valid_to>=? ";$pa=array($to,$from);
  if($dept!==''){$w.=" AND p.department_code=? ";$pa[]=$dept;}
  if($status!==''){$w.=" AND p.position_status=? ";$pa[]=$status;}
  if($vacancy!==''){$w.=" AND p.vacancy_status=? ";$pa[]=$vacancy;}
  if($kw!==''){$like='%'.$kw.'%';$w.=" AND (p.position_code LIKE ? OR p.position_name LIKE ? OR jt.job_title_code LIKE ? OR jt.job_title_name LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ?) ";array_push($pa,$like,$like,$like,$like,$like,$like);}
  return $db->query("SELECT p.*,jt.job_title_code,jt.job_title_name,d.nm_dept,cs.structure_code,cs.structure_name,rp.position_code reports_to_code,rp.position_name reports_to_name,e.employee_no holder_no,e.full_name holder_name,cc.cost_center_name,pc.profit_center_name,wl.location_code,wl.location_name
    FROM erp_position p
    LEFT JOIN erp_job_title jt ON jt.id=p.job_title_id
    LEFT JOIN dept d ON d.kd_dept=p.department_code
    LEFT JOIN erp_company_structure cs ON cs.id=p.company_structure_id
    LEFT JOIN erp_position rp ON rp.id=p.reports_to_position_id
    LEFT JOIN erp_employee_master e ON e.id=p.holder_employee_id
    LEFT JOIN erp_cost_center cc ON cc.cost_center_code=p.cost_center_code
    LEFT JOIN erp_profit_center pc ON pc.profit_center_code=p.profit_center_code
    LEFT JOIN erp_work_location wl ON wl.id=p.work_location_id
    $w ORDER BY p.position_code",$pa);
}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=pos_user();
$types=array('STRUCTURAL','FUNCTIONAL','OPERATIONAL','PROJECT','TEMPORARY');
$categories=array('REGULAR','KEY_POSITION','CRITICAL','SUCCESSION','APPRENTICE');
$groups=array('DIRECTOR','MANAGER','STAFF','NON_STAFF','OPERATOR','CONTRACT','DAILY_WORKER','TRAINEE');
$vacancies=array('VACANT','OCCUPIED','PARTIAL','OVERSTAFFED','FROZEN');
$statuses=array('PLANNED','APPROVED','ACTIVE','INACTIVE','OBSOLETE');

switch($act){
  case 'job_title_search':
    $term=trim(pos_p('term'));$like='%'.$term.'%';$rows=$db->query("SELECT id,job_title_code,job_title_name,job_level FROM erp_job_title WHERE status='ACTIVE' AND (?='' OR job_title_code LIKE ? OR job_title_name LIKE ?) ORDER BY job_title_code LIMIT 30",array($term,$like,$like));pos_select2($rows,'id',function($r){return $r->job_title_code.' - '.$r->job_title_name.' ['.$r->job_level.']';});break;
  case 'department_search':
    $term=trim(pos_p('term'));$like='%'.$term.'%';$rows=$db->query("SELECT kd_dept,nm_dept,dept_type FROM dept WHERE status='ACTIVE' AND (?='' OR kd_dept LIKE ? OR nm_dept LIKE ?) ORDER BY kd_dept LIMIT 30",array($term,$like,$like));pos_select2($rows,'kd_dept',function($r){return $r->kd_dept.' - '.$r->nm_dept.' ['.$r->dept_type.']';});break;
  case 'company_structure_search':
    $term=trim(pos_p('term'));$like='%'.$term.'%';$rows=$db->query("SELECT id,structure_code,structure_name,structure_type FROM erp_company_structure WHERE status='ACTIVE' AND (?='' OR structure_code LIKE ? OR structure_name LIKE ? OR structure_type LIKE ?) ORDER BY structure_code LIMIT 30",array($term,$like,$like,$like));pos_select2($rows,'id',function($r){return $r->structure_code.' - '.$r->structure_name.' ['.$r->structure_type.']';});break;
  case 'position_search':
    $term=trim(pos_p('term'));$exclude=(int)pos_p('exclude');$like='%'.$term.'%';$rows=$db->query("SELECT id,position_code,position_name,vacancy_status FROM erp_position WHERE position_status IN ('APPROVED','ACTIVE') AND (?='' OR position_code LIKE ? OR position_name LIKE ?) AND (?=0 OR id<>?) ORDER BY position_code LIMIT 30",array($term,$like,$like,$exclude,$exclude));pos_select2($rows,'id',function($r){return $r->position_code.' - '.$r->position_name.' ['.$r->vacancy_status.']';});break;
  case 'employee_search':
    $term=trim(pos_p('term'));$like='%'.$term.'%';$rows=$db->query("SELECT id,employee_no,full_name,employment_status FROM erp_employee_master WHERE employment_status IN ('ACTIVE','PROBATION','CONTRACT') AND (?='' OR employee_no LIKE ? OR full_name LIKE ?) ORDER BY employee_no LIMIT 30",array($term,$like,$like));pos_select2($rows,'id',function($r){return $r->employee_no.' - '.$r->full_name.' ['.$r->employment_status.']';});break;
  case 'cost_center_search':
    $term=trim(pos_p('term'));$like='%'.$term.'%';$rows=$db->query("SELECT cost_center_code,cost_center_name FROM erp_cost_center WHERE status='Aktif' AND (?='' OR cost_center_code LIKE ? OR cost_center_name LIKE ?) ORDER BY cost_center_code LIMIT 30",array($term,$like,$like));pos_select2($rows,'cost_center_code',function($r){return $r->cost_center_code.' - '.$r->cost_center_name;});break;
  case 'profit_center_search':
    $term=trim(pos_p('term'));$like='%'.$term.'%';$rows=$db->query("SELECT profit_center_code,profit_center_name FROM erp_profit_center WHERE status='Aktif' AND (?='' OR profit_center_code LIKE ? OR profit_center_name LIKE ?) ORDER BY profit_center_code LIMIT 30",array($term,$like,$like));pos_select2($rows,'profit_center_code',function($r){return $r->profit_center_code.' - '.$r->profit_center_name;});break;
  case 'work_location_search':
    $term=trim(pos_p('term'));$like='%'.$term.'%';$rows=$db->query("SELECT id,location_code,location_name,location_type FROM erp_work_location WHERE status='ACTIVE' AND (?='' OR location_code LIKE ? OR location_name LIKE ? OR location_type LIKE ?) ORDER BY location_code LIMIT 30",array($term,$like,$like,$like));pos_select2($rows,'id',function($r){return $r->location_code.' - '.$r->location_name.' ['.$r->location_type.']';});break;
  case 'get':
    $r=pos_row((int)pos_p('id'));if(!$r)pos_json('error','Position tidak ditemukan.');pos_json('good','',array('data'=>pos_payload($r)));break;
  case 'save':
    $id=(int)pos_p('id');$code=pos_c(pos_p('position_code'));$name=trim(pos_p('position_name'));
    $type=pos_c(pos_p('position_type'));$cat=pos_c(pos_p('position_category'));$grp=pos_c(pos_p('employee_group'));
    $vac=pos_c(pos_p('vacancy_status'));$status=pos_c(pos_p('position_status'));
    $job=pos_p('job_title_id')!==''?(int)pos_p('job_title_id'):null;$dept=pos_c(pos_p('department_code'));
    $cs=pos_p('company_structure_id')!==''?(int)pos_p('company_structure_id'):null;$parent=pos_p('reports_to_position_id')!==''?(int)pos_p('reports_to_position_id'):null;
    $holder=pos_p('holder_employee_id')!==''?(int)pos_p('holder_employee_id'):null;$wl=pos_p('work_location_id')!==''?(int)pos_p('work_location_id'):null;
    $cc=trim(pos_p('cost_center_code'));$pc=trim(pos_p('profit_center_code'));$from=trim(pos_p('valid_from'));$to=trim(pos_p('valid_to','9999-12-31'));
    $planned=(float)pos_p('planned_fte',1);$occupied=(float)pos_p('occupied_fte',0);$hc=max(1,(int)pos_p('headcount_plan',1));
    if($code==='')pos_json('error','Position Code wajib diisi.');
    if(!preg_match('/^[A-Z0-9_-]{3,30}$/',$code))pos_json('error','Position Code hanya boleh huruf besar, angka, underscore, atau dash.');
    if($name==='')pos_json('error','Position Name wajib diisi.');
    if(!in_array($type,$types,true))pos_json('error','Position Type tidak valid.');
    if(!in_array($cat,$categories,true))pos_json('error','Position Category tidak valid.');
    if(!in_array($grp,$groups,true))pos_json('error','Employee Group tidak valid.');
    if(!in_array($vac,$vacancies,true))pos_json('error','Vacancy Status tidak valid.');
    if(!in_array($status,$statuses,true))pos_json('error','Position Status tidak valid.');
    if($from===''||$to==='')pos_json('error','Valid From dan Valid To wajib diisi.');
    if(strtotime($to)<strtotime($from))pos_json('error','Valid To tidak boleh sebelum Valid From.');
    if($planned<=0)pos_json('error','Planned FTE harus lebih dari 0.');
    if($occupied<0)pos_json('error','Occupied FTE tidak boleh negatif.');
    if($job && !$db->fetch("SELECT id FROM erp_job_title WHERE id=? AND status='ACTIVE' LIMIT 1",array($job)))pos_json('error','Job Title tidak valid.');
    if($dept!=='' && !$db->fetch("SELECT kd_dept FROM dept WHERE kd_dept=? AND status='ACTIVE' LIMIT 1",array($dept)))pos_json('error','Department tidak valid.');
    if($cs && !$db->fetch("SELECT id FROM erp_company_structure WHERE id=? AND status='ACTIVE' LIMIT 1",array($cs)))pos_json('error','Company Structure tidak valid.');
    if($parent && !$db->fetch("SELECT id FROM erp_position WHERE id=? AND position_status IN ('APPROVED','ACTIVE') LIMIT 1",array($parent)))pos_json('error','Reports To Position tidak valid.');
    if(pos_cycle($id,$parent))pos_json('error','Reports To menyebabkan hirarki position melingkar.');
    if($holder && !$db->fetch("SELECT id FROM erp_employee_master WHERE id=? AND employment_status IN ('ACTIVE','PROBATION','CONTRACT') LIMIT 1",array($holder)))pos_json('error','Holder Employee tidak valid.');
    if($cc!=='' && !$db->fetch("SELECT id FROM erp_cost_center WHERE cost_center_code=? AND status='Aktif' LIMIT 1",array($cc)))pos_json('error','Cost Center tidak valid.');
    if($pc!=='' && !$db->fetch("SELECT id FROM erp_profit_center WHERE profit_center_code=? AND status='Aktif' LIMIT 1",array($pc)))pos_json('error','Profit Center tidak valid.');
    if($wl && !$db->fetch("SELECT id FROM erp_work_location WHERE id=? AND status='ACTIVE' LIMIT 1",array($wl)))pos_json('error','Work Location tidak valid.');
    if($db->fetch("SELECT id FROM erp_position WHERE position_code=? AND id<>? LIMIT 1",array($code,$id)))pos_json('error','Position Code sudah digunakan.');
    if($holder && $db->fetch("SELECT id FROM erp_position WHERE holder_employee_id=? AND id<>? AND position_status='ACTIVE' LIMIT 1",array($holder,$id)))pos_json('error','Employee tersebut sudah menjadi holder position aktif lain.');
    if($vac==='OCCUPIED' && !$holder)pos_json('error','Vacancy OCCUPIED wajib memiliki Holder Employee.');
    if($holder && $vac==='VACANT')pos_json('error','Position dengan Holder Employee tidak boleh VACANT.');
    $data=array('position_code'=>$code,'position_name'=>$name,'position_short_name'=>trim(pos_p('position_short_name')),'position_type'=>$type,'position_category'=>$cat,'job_title_id'=>$job,'department_code'=>$dept!==''?$dept:null,'company_structure_id'=>$cs,'reports_to_position_id'=>$parent,'holder_employee_id'=>$holder,'cost_center_code'=>$cc,'profit_center_code'=>$pc,'work_location_id'=>$wl,'employee_group'=>$grp,'pay_grade'=>trim(pos_p('pay_grade')),'planned_fte'=>$planned,'occupied_fte'=>$occupied,'headcount_plan'=>$hc,'vacancy_status'=>$vac,'position_status'=>$status,'valid_from'=>$from,'valid_to'=>$to,'job_description'=>trim(pos_p('job_description')),'qualification_requirement'=>trim(pos_p('qualification_requirement')),'authority_limit'=>trim(pos_p('authority_limit')),'succession_plan_note'=>trim(pos_p('succession_plan_note')),'sap_reference'=>trim(pos_p('sap_reference')),'remarks'=>trim(pos_p('remarks')),'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s'));
    if($id>0){if(!$db->fetch("SELECT id FROM erp_position WHERE id=? LIMIT 1",array($id)))pos_json('error','Position tidak ditemukan.');$ok=$db->update('erp_position',$data,'id',$id);}else{$data['created_by']=$username;$ok=$db->insert('erp_position',$data);$id=$db->last_insert_id();}
    if(!$ok)pos_json('error',$db->getErrorMessage()?:'Position gagal disimpan.');
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menyimpan Position '.$code.' dengan nama '.$name.' pada '.date('Y-m-d H:i:s'),$username);
    pos_json('good','Position berhasil disimpan.',array('id'=>$id));
  case 'delete':
    $id=(int)pos_p('id');$r=$db->fetch("SELECT * FROM erp_position WHERE id=? LIMIT 1",array($id));if(!$r)pos_json('error','Position tidak ditemukan.');
    if($db->fetch("SELECT id FROM erp_position WHERE reports_to_position_id=? LIMIT 1",array($id)))pos_json('error','Position masih menjadi Reports To. Pindahkan subordinate terlebih dahulu.');
    $db->delete('erp_position','id',$id);if($db->getErrorMessage())pos_json('error',$db->getErrorMessage());
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menghapus Position '.$r->position_code,$username);
    pos_json('good','Position berhasil dihapus.');
  case 'status':
    $id=(int)pos_p('id');$status=pos_c(pos_p('status'));if(!in_array($status,$statuses,true))pos_json('error','Status tidak valid.');
    $r=$db->fetch("SELECT * FROM erp_position WHERE id=? LIMIT 1",array($id));if(!$r)pos_json('error','Position tidak ditemukan.');
    $ok=$db->update('erp_position',array('position_status'=>$status,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')),'id',$id);if(!$ok)pos_json('error',$db->getErrorMessage()?:'Status gagal diubah.');
    if(function_exists('simpan_log'))simpan_log('User '.$username.' mengubah status Position '.$r->position_code.' menjadi '.$status,$username);
    pos_json('good','Status berhasil diubah.');
  case 'detail':
    $r=pos_row((int)pos_p('id'));if(!$r){echo '<div class="alert alert-warning">Position tidak ditemukan.</div>';break;}
    echo '<h3 style="margin-top:0">'.pos_h($r->position_code).' <small>'.pos_h($r->position_name).'</small></h3><span class="label label-success">'.pos_h($r->position_status).'</span> <span class="label label-info">'.pos_h($r->vacancy_status).'</span> <span class="label label-default">'.pos_h($r->position_type.' / '.$r->position_category).'</span><hr>';
    echo '<div class="row"><div class="col-sm-3"><b>'.hr_h('hr_job_title', 'Job Title').'</b><br>'.pos_h($r->job_title_code?($r->job_title_code.' - '.$r->job_title_name):'-').'</div><div class="col-sm-3"><b>'.hr_h('hr_department', 'Department').'</b><br>'.pos_h($r->department_code?($r->department_code.' - '.$r->nm_dept):'-').'</div><div class="col-sm-3"><b>Org Unit</b><br>'.pos_h($r->structure_code?($r->structure_code.' - '.$r->structure_name):'-').'</div><div class="col-sm-3"><b>Reports To</b><br>'.pos_h($r->reports_to_code?($r->reports_to_code.' - '.$r->reports_to_name):'-').'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><b>Holder</b><br>'.pos_h($r->holder_no?($r->holder_no.' - '.$r->holder_name):'-').'</div><div class="col-sm-3"><b>FTE</b><br>'.pos_h($r->occupied_fte.' / '.$r->planned_fte).'</div><div class="col-sm-3"><b>Cost Center</b><br>'.pos_h($r->cost_center_code?:'-').'<br><small>'.pos_h($r->cost_center_name?:'').'</small></div><div class="col-sm-3"><b>'.hr_h('hr_work_location', 'Work Location').'</b><br>'.pos_h($r->location_code?($r->location_code.' - '.$r->location_name):'-').'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-4"><b>Job Description</b><p>'.nl2br(pos_h($r->job_description?:'-')).'</p></div><div class="col-sm-4"><b>Qualification</b><p>'.nl2br(pos_h($r->qualification_requirement?:'-')).'</p></div><div class="col-sm-4"><b>Authority</b><p>'.nl2br(pos_h($r->authority_limit?:'-')).'</p></div></div>';
    break;
  case 'export':
    $initial=ob_get_level();ob_start();error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $from=pos_g('tgl_awal',date('Y-01-01'));$to=pos_g('tgl_akhir','9999-12-31');$dept=pos_g('department_code','');$status=pos_g('position_status','');$vac=pos_g('vacancy_status','');$kw=pos_g('keyword','');$rows=pos_export_rows($from,$to,$dept,$status,$vac,$kw);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Position'));$heads=array(erp_export_label("No"),erp_export_label("Position Code"),erp_export_label("Position Name"),erp_export_label("Type"),erp_export_label("Category"),erp_export_label("Job Title"),erp_export_label("Department"),erp_export_label("Org Unit"),erp_export_label("Reports To"),erp_export_label("Holder"),erp_export_label("Employee Group"),erp_export_label("Pay Grade"),erp_export_label("FTE Planned"),erp_export_label("FTE Occupied"),erp_export_label("Headcount"),erp_export_label("Vacancy"),erp_export_label("Status"),erp_export_label("Cost Center"),erp_export_label("Profit Center"),erp_export_label("Work Location"),erp_export_label("Validity"),erp_export_label("SAP Ref"),erp_export_label("Updated By"));
    foreach($heads as $i=>$h)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);$rnum=5;$n=1;foreach($rows as $r){$vals=array($n++,$r->position_code,$r->position_name,$r->position_type,$r->position_category,$r->job_title_code.' - '.$r->job_title_name,$r->department_code.' - '.$r->nm_dept,$r->structure_code.' - '.$r->structure_name,$r->reports_to_code.' - '.$r->reports_to_name,$r->holder_no.' - '.$r->holder_name,$r->employee_group,$r->pay_grade,(float)$r->planned_fte,(float)$r->occupied_fte,(int)$r->headcount_plan,$r->vacancy_status,$r->position_status,$r->cost_center_code.' - '.$r->cost_center_name,$r->profit_center_code.' - '.$r->profit_center_name,$r->location_code.' - '.$r->location_name,$r->valid_from.' s/d '.$r->valid_to,$r->sap_reference,$r->updated_by?:$r->created_by);foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rnum,$v);$rnum++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('POSITION MASTER REPORT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rnum-1),'column_count'=>count($heads),'decimal_columns'=>array('M','N'),'numeric_columns'=>array('O'),'filters'=>array('Validity'=>$from.' s/d '.$to,'Department'=>$dept?:erp_export_all_text(),'Position Status'=>$status?:erp_export_all_text(),'Vacancy'=>$vac?:erp_export_all_text())));
    $tmp=erpkb_excel_temp_file('position_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="position_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:
    pos_json('error','Action tidak dikenal.');
}
?>
