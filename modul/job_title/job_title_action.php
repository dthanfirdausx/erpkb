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

function jt_json($status,$message='',$extra=array()){
  header('Content-Type: application/json; charset=utf-8');
  $payload=array('status'=>$status);
  if($message!==''){
    if($status==='good')$payload['message']=$message;
    else $payload['error_message']=$message;
  }
  foreach($extra as $k=>$v)$payload[$k]=$v;
  echo json_encode($payload);
  exit;
}
function jt_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function jt_post($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function jt_code($v){return strtoupper(trim((string)$v));}
function jt_in($v,$arr){return in_array($v,$arr,true);}
function jt_select2($rows,$idField,$textCb){
  $results=array();
  foreach($rows as $r)$results[]=array('id'=>$r->$idField,'text'=>$textCb($r));
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('results'=>$results));
  exit;
}
function jt_row($id){
  global $db;
  return $db->fetch("SELECT jt.*,d.nm_dept,cs.structure_code,cs.structure_name,cs.structure_type,rt.job_title_code reports_to_code,rt.job_title_name reports_to_name,cc.cost_center_name,pc.profit_center_name
    FROM erp_job_title jt
    LEFT JOIN dept d ON d.kd_dept=jt.department_code
    LEFT JOIN erp_company_structure cs ON cs.id=jt.company_structure_id
    LEFT JOIN erp_job_title rt ON rt.id=jt.reports_to_job_title_id
    LEFT JOIN erp_cost_center cc ON cc.cost_center_code=jt.cost_center_code
    LEFT JOIN erp_profit_center pc ON pc.profit_center_code=jt.profit_center_code
    WHERE jt.id=? LIMIT 1",array((int)$id));
}
function jt_payload($r){
  $data=(array)$r;
  $data['department_text']=$r->department_code?($r->department_code.' - '.$r->nm_dept):'';
  $data['company_structure_text']=$r->company_structure_id?($r->structure_code.' - '.$r->structure_name.' ['.$r->structure_type.']'):'';
  $data['reports_to_text']=$r->reports_to_job_title_id?($r->reports_to_code.' - '.$r->reports_to_name):'';
  $data['cost_center_text']=$r->cost_center_code?($r->cost_center_code.' - '.$r->cost_center_name):'';
  $data['profit_center_text']=$r->profit_center_code?($r->profit_center_code.' - '.$r->profit_center_name):'';
  return $data;
}
function jt_has_cycle($id,$reportsToId){
  global $db;
  if($id<=0||$reportsToId<=0)return false;
  if($id===$reportsToId)return true;
  $guard=0;$cur=$reportsToId;
  while($cur>0 && $guard<50){
    $p=$db->fetch("SELECT reports_to_job_title_id FROM erp_job_title WHERE id=? LIMIT 1",array($cur));
    if(!$p||!(int)$p->reports_to_job_title_id)return false;
    $cur=(int)$p->reports_to_job_title_id;
    if($cur===$id)return true;
    $guard++;
  }
  return false;
}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=isset($_SESSION['username'])?$_SESSION['username']:'system';
$families=array('EXECUTIVE','MANAGEMENT','PROFESSIONAL','SUPERVISOR','STAFF','OPERATOR','TECHNICIAN','ADMINISTRATION','SALES','QUALITY','WAREHOUSE','PRODUCTION','FINANCE','HR','IT','PROCUREMENT');
$levels=array('L1','L2','L3','L4','L5','L6','L7','L8','L9','L10');
$empGroups=array('DIRECTOR','MANAGER','STAFF','NON_STAFF','OPERATOR','CONTRACT','DAILY_WORKER','TRAINEE');
$locations=array('OFFICE','PLANT','WAREHOUSE','FIELD','REMOTE','HYBRID');

switch($act){
  case 'department_search':
    $term=trim(jt_post('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT kd_dept,nm_dept,dept_type FROM dept WHERE status='ACTIVE' AND (?='' OR kd_dept LIKE ? OR nm_dept LIKE ? OR dept_type LIKE ?) ORDER BY kd_dept LIMIT 30",array($term,$like,$like,$like));
    jt_select2($rows,'kd_dept',function($r){return $r->kd_dept.' - '.$r->nm_dept.' ['.$r->dept_type.']';});
    break;
  case 'company_structure_search':
    $term=trim(jt_post('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT id,structure_code,structure_name,structure_type FROM erp_company_structure WHERE status='ACTIVE' AND structure_type IN ('ORG_UNIT','PERSONNEL_SUBAREA','PERSONNEL_AREA') AND (?='' OR structure_code LIKE ? OR structure_name LIKE ? OR structure_type LIKE ?) ORDER BY FIELD(structure_type,'ORG_UNIT','PERSONNEL_SUBAREA','PERSONNEL_AREA'),structure_code LIMIT 30",array($term,$like,$like,$like));
    jt_select2($rows,'id',function($r){return $r->structure_code.' - '.$r->structure_name.' ['.$r->structure_type.']';});
    break;
  case 'job_title_search':
    $term=trim(jt_post('term'));$exclude=(int)jt_post('exclude');$like='%'.$term.'%';
    $rows=$db->query("SELECT id,job_title_code,job_title_name,job_level FROM erp_job_title WHERE status IN ('DRAFT','ACTIVE') AND (?='' OR job_title_code LIKE ? OR job_title_name LIKE ?) AND (?=0 OR id<>?) ORDER BY job_level DESC,job_title_code LIMIT 30",array($term,$like,$like,$exclude,$exclude));
    jt_select2($rows,'id',function($r){return $r->job_title_code.' - '.$r->job_title_name.' ['.$r->job_level.']';});
    break;
  case 'cost_center_search':
    $term=trim(jt_post('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT cost_center_code,cost_center_name,department_code FROM erp_cost_center WHERE status='Aktif' AND (?='' OR cost_center_code LIKE ? OR cost_center_name LIKE ? OR department_code LIKE ?) ORDER BY cost_center_code LIMIT 30",array($term,$like,$like,$like));
    jt_select2($rows,'cost_center_code',function($r){return $r->cost_center_code.' - '.$r->cost_center_name;});
    break;
  case 'profit_center_search':
    $term=trim(jt_post('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT profit_center_code,profit_center_name FROM erp_profit_center WHERE status='Aktif' AND (?='' OR profit_center_code LIKE ? OR profit_center_name LIKE ?) ORDER BY profit_center_code LIMIT 30",array($term,$like,$like));
    jt_select2($rows,'profit_center_code',function($r){return $r->profit_center_code.' - '.$r->profit_center_name;});
    break;
  case 'get':
    $id=(int)jt_post('id');
    $r=jt_row($id);
    if(!$r)jt_json('error','Job Title tidak ditemukan.');
    jt_json('good','',array('data'=>jt_payload($r)));
    break;
  case 'save':
    $id=(int)jt_post('id');
    $code=jt_code(jt_post('job_title_code'));
    $name=trim(jt_post('job_title_name'));
    $family=jt_code(jt_post('job_family'));
    $level=jt_code(jt_post('job_level'));
    $empGroup=jt_code(jt_post('employee_group'));
    $location=jt_code(jt_post('work_location_type'));
    $department=jt_code(jt_post('department_code'));
    $companyStructureId=jt_post('company_structure_id')!==''?(int)jt_post('company_structure_id'):null;
    $reportsTo=jt_post('reports_to_job_title_id')!==''?(int)jt_post('reports_to_job_title_id'):null;
    $costCenter=trim(jt_post('cost_center_code'));
    $profitCenter=trim(jt_post('profit_center_code'));
    $validFrom=trim(jt_post('valid_from'));
    $validTo=trim(jt_post('valid_to','9999-12-31'));
    $status=jt_code(jt_post('status','DRAFT'));

    if($code==='')jt_json('error','Job Title Code wajib diisi.');
    if(!preg_match('/^[A-Z0-9_-]{2,20}$/',$code))jt_json('error','Job Title Code hanya boleh huruf besar, angka, underscore, atau dash.');
    if($name==='')jt_json('error','Job Title Name wajib diisi.');
    if(!jt_in($family,$families))jt_json('error','Job Family tidak valid.');
    if(!jt_in($level,$levels))jt_json('error','Job Level tidak valid.');
    if(!jt_in($empGroup,$empGroups))jt_json('error','Employee Group tidak valid.');
    if(!jt_in($location,$locations))jt_json('error','Work Location Type tidak valid.');
    if(!jt_in($status,array('DRAFT','ACTIVE','INACTIVE')))jt_json('error','Status tidak valid.');
    if($validFrom===''||$validTo==='')jt_json('error','Valid From dan Valid To wajib diisi.');
    if(strtotime($validTo)<strtotime($validFrom))jt_json('error','Valid To tidak boleh sebelum Valid From.');
    if($department!==''){
      $dept=$db->fetch("SELECT kd_dept FROM dept WHERE kd_dept=? AND status='ACTIVE' LIMIT 1",array($department));
      if(!$dept)jt_json('error','Department tidak valid atau nonaktif.');
    }
    if($companyStructureId){
      $cs=$db->fetch("SELECT id FROM erp_company_structure WHERE id=? AND status='ACTIVE' LIMIT 1",array($companyStructureId));
      if(!$cs)jt_json('error','Company Structure / Org Unit tidak valid.');
    }
    if($reportsTo){
      $rt=$db->fetch("SELECT id FROM erp_job_title WHERE id=? AND status IN ('DRAFT','ACTIVE') LIMIT 1",array($reportsTo));
      if(!$rt)jt_json('error','Reports To Job Title tidak valid.');
      if(jt_has_cycle($id,$reportsTo))jt_json('error','Reports To menyebabkan hirarki job title melingkar.');
    }
    if($costCenter!==''){
      $cc=$db->fetch("SELECT id FROM erp_cost_center WHERE cost_center_code=? AND status='Aktif' LIMIT 1",array($costCenter));
      if(!$cc)jt_json('error','Cost Center tidak valid atau nonaktif.');
    }
    if($profitCenter!==''){
      $pc=$db->fetch("SELECT id FROM erp_profit_center WHERE profit_center_code=? AND status='Aktif' LIMIT 1",array($profitCenter));
      if(!$pc)jt_json('error','Profit Center tidak valid atau nonaktif.');
    }
    $dup=$db->fetch("SELECT id FROM erp_job_title WHERE job_title_code=? AND id<>? LIMIT 1",array($code,$id));
    if($dup)jt_json('error','Job Title Code sudah digunakan.');

    $data=array(
      'job_title_code'=>$code,
      'job_title_name'=>$name,
      'job_title_short_name'=>trim(jt_post('job_title_short_name')),
      'job_family'=>$family,
      'job_level'=>$level,
      'employee_group'=>$empGroup,
      'employee_subgroup'=>trim(jt_post('employee_subgroup')),
      'department_code'=>$department!==''?$department:null,
      'company_structure_id'=>$companyStructureId,
      'reports_to_job_title_id'=>$reportsTo,
      'cost_center_code'=>$costCenter,
      'profit_center_code'=>$profitCenter,
      'pay_grade'=>trim(jt_post('pay_grade')),
      'work_location_type'=>$location,
      'headcount_plan'=>max(0,(int)jt_post('headcount_plan')),
      'minimum_education'=>trim(jt_post('minimum_education')),
      'competency_profile'=>trim(jt_post('competency_profile')),
      'job_purpose'=>trim(jt_post('job_purpose')),
      'key_responsibility'=>trim(jt_post('key_responsibility')),
      'authority_limit'=>trim(jt_post('authority_limit')),
      'valid_from'=>$validFrom,
      'valid_to'=>$validTo,
      'status'=>$status,
      'sap_reference'=>trim(jt_post('sap_reference')),
      'remarks'=>trim(jt_post('remarks')),
      'updated_by'=>$username,
      'updated_at'=>date('Y-m-d H:i:s')
    );
    if($id>0){
      $old=$db->fetch("SELECT id FROM erp_job_title WHERE id=? LIMIT 1",array($id));
      if(!$old)jt_json('error','Job Title tidak ditemukan.');
      $ok=$db->update('erp_job_title',$data,'id',$id);
    }else{
      $data['created_by']=$username;
      $ok=$db->insert('erp_job_title',$data);
      $id=$db->last_insert_id();
    }
    if(!$ok)jt_json('error',$db->getErrorMessage()?:'Job Title gagal disimpan.');
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menyimpan Job Title '.$code.' dengan nama '.$name.' pada '.date('Y-m-d H:i:s'),$username);
    jt_json('good','Job Title berhasil disimpan.',array('id'=>$id));
    break;
  case 'delete':
    $id=(int)jt_post('id');
    $r=$db->fetch("SELECT * FROM erp_job_title WHERE id=? LIMIT 1",array($id));
    if(!$r)jt_json('error','Job Title tidak ditemukan.');
    $child=$db->fetch("SELECT COUNT(*) jml FROM erp_job_title WHERE reports_to_job_title_id=?",array($id));
    if($child && (int)$child->jml>0)jt_json('error','Job Title masih menjadi Reports To. Pindahkan child terlebih dahulu.');
    $db->delete('erp_job_title','id',$id);
    if($db->getErrorMessage())jt_json('error',$db->getErrorMessage());
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menghapus Job Title '.$r->job_title_code,$username);
    jt_json('good','Job Title berhasil dihapus.');
    break;
  case 'status':
    $id=(int)jt_post('id');$status=jt_code(jt_post('status'));
    if(!jt_in($status,array('DRAFT','ACTIVE','INACTIVE')))jt_json('error','Status tidak valid.');
    $r=$db->fetch("SELECT * FROM erp_job_title WHERE id=? LIMIT 1",array($id));
    if(!$r)jt_json('error','Job Title tidak ditemukan.');
    $ok=$db->update('erp_job_title',array('status'=>$status,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')),'id',$id);
    if(!$ok)jt_json('error',$db->getErrorMessage()?:'Status gagal diubah.');
    if(function_exists('simpan_log'))simpan_log('User '.$username.' mengubah status Job Title '.$r->job_title_code.' menjadi '.$status,$username);
    jt_json('good','Status berhasil diubah.');
    break;
  case 'detail':
    $id=(int)jt_post('id');
    $r=jt_row($id);
    if(!$r){echo '<div class="alert alert-warning">Job Title tidak ditemukan.</div>';break;}
    $class=$r->status==='ACTIVE'?'success':($r->status==='DRAFT'?'default':'warning');
    echo '<div class="row"><div class="col-md-8"><h3 style="margin-top:0">'.jt_h($r->job_title_code).' <small>'.jt_h($r->job_title_name).'</small></h3><span class="label label-'.$class.'">'.jt_h($r->status).'</span> <span class="label label-info">'.jt_h($r->job_family.' / '.$r->job_level).'</span></div><div class="col-md-4 text-right"><strong>SAP Ref</strong><br>'.jt_h($r->sap_reference?:'-').'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><strong>'.hr_h('hr_department', 'Department').'</strong><br>'.jt_h($r->department_code?($r->department_code.' - '.$r->nm_dept):'-').'</div><div class="col-sm-3"><strong>Org Unit</strong><br>'.jt_h($r->structure_code?($r->structure_code.' - '.$r->structure_name):'-').'</div><div class="col-sm-3"><strong>Reports To</strong><br>'.jt_h($r->reports_to_code?($r->reports_to_code.' - '.$r->reports_to_name):'-').'</div><div class="col-sm-3"><strong>Validity</strong><br>'.jt_h($r->valid_from.' s/d '.$r->valid_to).'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><strong>Employee Group</strong><br>'.jt_h($r->employee_group).'<br><small>'.jt_h($r->employee_subgroup?:'-').'</small></div><div class="col-sm-3"><strong>Pay Grade</strong><br>'.jt_h($r->pay_grade?:'-').'</div><div class="col-sm-3"><strong>Cost Center</strong><br>'.jt_h($r->cost_center_code?:'-').'<br><small>'.jt_h($r->cost_center_name?:'').'</small></div><div class="col-sm-3"><strong>Profit Center</strong><br>'.jt_h($r->profit_center_code?:'-').'<br><small>'.jt_h($r->profit_center_name?:'').'</small></div></div><hr>';
    echo '<div class="row"><div class="col-sm-4"><strong>Purpose</strong><p>'.nl2br(jt_h($r->job_purpose?:'-')).'</p></div><div class="col-sm-4"><strong>Key Responsibility</strong><p>'.nl2br(jt_h($r->key_responsibility?:'-')).'</p></div><div class="col-sm-4"><strong>Authority Limit</strong><p>'.nl2br(jt_h($r->authority_limit?:'-')).'</p></div></div>';
    break;
  default:
    jt_json('error','Action tidak dikenal.');
}
?>
