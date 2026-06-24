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

function em_json($status,$message='',$extra=array()){
  header('Content-Type: application/json; charset=utf-8');
  $payload=array('status'=>$status);
  if($message!=='')$payload[$status==='good'?'message':'error_message']=$message;
  foreach($extra as $k=>$v)$payload[$k]=$v;
  echo json_encode($payload); exit;
}
function em_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function em_post($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function em_code($v){return strtoupper(trim((string)$v));}
function em_select2($rows,$idField,$textCb){$out=array();foreach($rows as $r)$out[]=array('id'=>$r->$idField,'text'=>$textCb($r));header('Content-Type: application/json; charset=utf-8');echo json_encode(array('results'=>$out));exit;}
function em_next_employee_no(){
  global $db;
  $r=$db->fetch("SELECT MAX(CAST(SUBSTRING(employee_no,5) AS UNSIGNED)) last_no FROM erp_employee_master WHERE employee_no REGEXP '^EMP-[0-9]+$'");
  $next=($r&&$r->last_no)?((int)$r->last_no+1):1;
  return 'EMP-'.str_pad($next,4,'0',STR_PAD_LEFT);
}
function em_upload_document_file(){
  if(!isset($_FILES['document_file'])||!is_array($_FILES['document_file'])||$_FILES['document_file']['error']===UPLOAD_ERR_NO_FILE)em_json('error','File document wajib dipilih.');
  $f=$_FILES['document_file'];
  if($f['error']!==UPLOAD_ERR_OK)em_json('error','Upload file gagal. Kode error: '.$f['error']);
  if((int)$f['size']>5*1024*1024)em_json('error','Ukuran file maksimal 5MB.');
  $original=isset($f['name'])?$f['name']:'document';
  $ext=strtolower(pathinfo($original,PATHINFO_EXTENSION));
  $allowedExt=array('pdf','jpg','jpeg','png','gif','webp');
  if(!in_array($ext,$allowedExt,true))em_json('error','File harus PDF atau image (jpg, jpeg, png, gif, webp).');
  $allowedMime=array('application/pdf','image/jpeg','image/png','image/gif','image/webp');
  $mime='';
  if(function_exists('finfo_open')){
    $fi=finfo_open(FILEINFO_MIME_TYPE);
    if($fi)$mime=finfo_file($fi,$f['tmp_name']);
  }
  if($mime!==''&&!in_array($mime,$allowedMime,true))em_json('error','Tipe file tidak valid: '.$mime);
  $dir='../../upload/employee_documents';
  if(!is_dir($dir)&&!@mkdir($dir,0777,true))em_json('error','Folder upload employee_documents tidak bisa dibuat.');
  $safeBase=preg_replace('/[^a-zA-Z0-9_-]/','_',pathinfo($original,PATHINFO_FILENAME));
  $safeBase=substr($safeBase!==''?$safeBase:'document',0,50);
  $fileName='EMP-DOC-'.date('YmdHis').'-'.mt_rand(1000,9999).'-'.$safeBase.'.'.$ext;
  $target=$dir.'/'.$fileName;
  $ok=@move_uploaded_file($f['tmp_name'],$target);
  if(!$ok&&PHP_SAPI==='cli')$ok=@copy($f['tmp_name'],$target);
  if(!$ok)em_json('error','File gagal disimpan ke folder upload.');
  em_json('good','File berhasil diupload.',array('file_ref'=>'upload/employee_documents/'.$fileName,'file_name'=>$fileName,'mime_type'=>$mime));
}
function em_row($id){
  global $db;
  return $db->fetch("SELECT e.*,d.nm_dept,j.job_title_code,j.job_title_name,j.job_family,j.job_level,cs.structure_code,cs.structure_name,cs.structure_type,cc.cost_center_name,pc.profit_center_name,
      m.employee_no manager_no,m.full_name manager_name,u.username,TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) user_full_name
    FROM erp_employee_master e
    LEFT JOIN dept d ON d.kd_dept=e.department_code
    LEFT JOIN erp_job_title j ON j.id=e.job_title_id
    LEFT JOIN erp_company_structure cs ON cs.id=e.company_structure_id
    LEFT JOIN erp_cost_center cc ON cc.cost_center_code=e.cost_center_code
    LEFT JOIN erp_profit_center pc ON pc.profit_center_code=e.profit_center_code
    LEFT JOIN erp_employee_master m ON m.id=e.manager_employee_id
    LEFT JOIN sys_users u ON u.id=e.user_id
    WHERE e.id=? LIMIT 1",array((int)$id));
}
function em_payload($r){
  $data=(array)$r;
  $data['department_text']=$r->department_code?($r->department_code.' - '.$r->nm_dept):'';
  $data['job_title_text']=$r->job_title_id?($r->job_title_code.' - '.$r->job_title_name.' ['.$r->job_level.']'):'';
  $data['company_structure_text']=$r->company_structure_id?($r->structure_code.' - '.$r->structure_name.' ['.$r->structure_type.']'):'';
  $data['cost_center_text']=$r->cost_center_code?($r->cost_center_code.' - '.$r->cost_center_name):'';
  $data['profit_center_text']=$r->profit_center_code?($r->profit_center_code.' - '.$r->profit_center_name):'';
  $data['manager_text']=$r->manager_employee_id?($r->manager_no.' - '.$r->manager_name):'';
  $userName=trim((string)$r->user_full_name)!==''?$r->user_full_name:$r->username;
  $data['user_text']=$r->user_id?($r->username.' - '.$userName):'';
  $data['families']=em_family_rows($r->id);
  $data['educations']=em_education_rows($r->id);
  $data['documents']=em_document_rows($r->id);
  return $data;
}
function em_family_rows($employeeId){
  global $db;
  $rows=$db->query("SELECT relationship_type,family_name,gender,birth_date,identity_type,identity_no,phone,is_dependent,emergency_contact,status FROM erp_employee_family_data WHERE employee_id=? ORDER BY FIELD(relationship_type,'SPOUSE','CHILD','FATHER','MOTHER','SIBLING','GUARDIAN','OTHER'),family_name",array((int)$employeeId));
  $out=array(); foreach($rows as $r)$out[]=(array)$r; return $out;
}
function em_education_rows($employeeId){
  global $db;
  $rows=$db->query("SELECT education_level,education_type,institution_name,major,graduation_year,certificate_no,gpa,highest_education,verified_status,document_ref,status FROM erp_employee_education WHERE employee_id=? ORDER BY highest_education DESC,graduation_year DESC,institution_name",array((int)$employeeId));
  $out=array(); foreach($rows as $r)$out[]=(array)$r; return $out;
}
function em_document_rows($employeeId){
  global $db;
  $rows=$db->query("SELECT document_type,document_category,document_title,document_number,issue_date,expiry_date,file_ref,confidential,mandatory_document,verification_status,status FROM erp_employee_document WHERE employee_id=? ORDER BY document_category,document_type,document_title",array((int)$employeeId));
  $out=array(); foreach($rows as $r)$out[]=(array)$r; return $out;
}
function em_json_array($key){
  $raw=em_post($key,'[]');
  $rows=json_decode($raw,true);
  return is_array($rows)?$rows:array();
}
function em_pick($row,$key,$default=''){
  return isset($row[$key])?trim((string)$row[$key]):$default;
}
function em_yn($value){
  $v=em_code($value);
  return $v==='Y'?'Y':'N';
}
function em_sync_child_details($employeeId,$employeeNo,$username){
  global $db;
  $employeeId=(int)$employeeId;
  $employeeNo=em_code($employeeNo);
  $now=date('Y-m-d H:i:s');

  $db->query("DELETE FROM erp_employee_family_data WHERE employee_id=?",array($employeeId));
  $families=em_json_array('family_rows_json');
  $i=1;
  foreach($families as $row){
    $name=em_pick($row,'family_name');
    if($name==='')continue;
    $db->insert('erp_employee_family_data',array(
      'family_no'=>'FAM-'.$employeeNo.'-'.str_pad($i,2,'0',STR_PAD_LEFT),
      'employee_id'=>$employeeId,
      'relationship_type'=>em_code(em_pick($row,'relationship_type','OTHER')),
      'family_name'=>$name,
      'gender'=>em_code(em_pick($row,'gender','OTHER')),
      'birth_date'=>em_pick($row,'birth_date')?:null,
      'identity_type'=>em_code(em_pick($row,'identity_type','OTHER')),
      'identity_no'=>em_pick($row,'identity_no'),
      'phone'=>em_pick($row,'phone'),
      'is_dependent'=>em_yn(em_pick($row,'is_dependent','N')),
      'emergency_contact'=>em_yn(em_pick($row,'emergency_contact','N')),
      'effective_from'=>em_post('valid_from',date('Y-01-01')),
      'effective_to'=>em_post('valid_to','9999-12-31'),
      'status'=>em_code(em_pick($row,'status','ACTIVE')),
      'sap_reference'=>'SAP-FAM-'.$employeeNo.'-'.str_pad($i,2,'0',STR_PAD_LEFT),
      'created_by'=>$username,
      'updated_by'=>$username,
      'updated_at'=>$now
    ));
    $i++;
  }

  $db->query("DELETE FROM erp_employee_education WHERE employee_id=?",array($employeeId));
  $educations=em_json_array('education_rows_json');
  $i=1;
  foreach($educations as $row){
    $institution=em_pick($row,'institution_name');
    if($institution==='')continue;
    $db->insert('erp_employee_education',array(
      'education_no'=>'EDU-'.$employeeNo.'-'.str_pad($i,2,'0',STR_PAD_LEFT),
      'employee_id'=>$employeeId,
      'education_level'=>em_code(em_pick($row,'education_level','OTHER')),
      'education_type'=>em_code(em_pick($row,'education_type','FORMAL')),
      'institution_name'=>$institution,
      'major'=>em_pick($row,'major'),
      'graduation_year'=>em_pick($row,'graduation_year')?:null,
      'certificate_no'=>em_pick($row,'certificate_no'),
      'gpa'=>em_pick($row,'gpa'),
      'highest_education'=>em_yn(em_pick($row,'highest_education','N')),
      'verified_status'=>em_code(em_pick($row,'verified_status','PENDING')),
      'document_ref'=>em_pick($row,'document_ref'),
      'effective_from'=>em_post('valid_from',date('Y-01-01')),
      'effective_to'=>em_post('valid_to','9999-12-31'),
      'status'=>em_code(em_pick($row,'status','ACTIVE')),
      'sap_reference'=>'SAP-EDU-'.$employeeNo.'-'.str_pad($i,2,'0',STR_PAD_LEFT),
      'created_by'=>$username,
      'updated_by'=>$username,
      'updated_at'=>$now
    ));
    $i++;
  }

  $db->query("DELETE FROM erp_employee_document WHERE employee_id=?",array($employeeId));
  $documents=em_json_array('document_rows_json');
  $i=1;
  foreach($documents as $row){
    $title=em_pick($row,'document_title');
    if($title==='')continue;
    $db->insert('erp_employee_document',array(
      'document_no'=>'DOC-'.$employeeNo.'-'.str_pad($i,2,'0',STR_PAD_LEFT),
      'employee_id'=>$employeeId,
      'document_type'=>em_code(em_pick($row,'document_type','OTHER')),
      'document_category'=>em_code(em_pick($row,'document_category','OTHER')),
      'document_title'=>$title,
      'document_number'=>em_pick($row,'document_number'),
      'issue_date'=>em_pick($row,'issue_date')?:null,
      'expiry_date'=>em_pick($row,'expiry_date')?:null,
      'file_ref'=>em_pick($row,'file_ref'),
      'confidential'=>em_yn(em_pick($row,'confidential','N')),
      'mandatory_document'=>em_yn(em_pick($row,'mandatory_document','N')),
      'verification_status'=>em_code(em_pick($row,'verification_status','PENDING')),
      'effective_from'=>em_post('valid_from',date('Y-01-01')),
      'effective_to'=>em_post('valid_to','9999-12-31'),
      'status'=>em_code(em_pick($row,'status','ACTIVE')),
      'sap_reference'=>'SAP-DOC-'.$employeeNo.'-'.str_pad($i,2,'0',STR_PAD_LEFT),
      'created_by'=>$username,
      'updated_by'=>$username,
      'updated_at'=>$now
    ));
    $i++;
  }
}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=isset($_SESSION['username'])?$_SESSION['username']:'system';

switch($act){
  case 'next_no':
    em_json('good','',array('employee_no'=>em_next_employee_no())); break;
  case 'upload_document_file':
    em_upload_document_file(); break;
  case 'department_search':
    $term=trim(em_post('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT kd_dept,nm_dept,dept_type FROM dept WHERE status='ACTIVE' AND (?='' OR kd_dept LIKE ? OR nm_dept LIKE ? OR dept_type LIKE ?) ORDER BY kd_dept LIMIT 30",array($term,$like,$like,$like));
    em_select2($rows,'kd_dept',function($r){return $r->kd_dept.' - '.$r->nm_dept.' ['.$r->dept_type.']';}); break;
  case 'job_title_search':
    $term=trim(em_post('term'));$dept=em_code(em_post('department_code'));$like='%'.$term.'%';
    $where=" WHERE jt.status='ACTIVE' AND (?='' OR jt.job_title_code LIKE ? OR jt.job_title_name LIKE ?) ";$p=array($term,$like,$like);
    if($dept!==''){$where.=" AND jt.department_code=? ";$p[]=$dept;}
    $rows=$db->query("SELECT jt.id,jt.job_title_code,jt.job_title_name,jt.job_level,d.nm_dept FROM erp_job_title jt LEFT JOIN dept d ON d.kd_dept=jt.department_code $where ORDER BY jt.job_level DESC,jt.job_title_code LIMIT 30",$p);
    em_select2($rows,'id',function($r){return $r->job_title_code.' - '.$r->job_title_name.' ['.$r->job_level.']';}); break;
  case 'company_structure_search':
    $term=trim(em_post('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT id,structure_code,structure_name,structure_type FROM erp_company_structure WHERE status='ACTIVE' AND structure_type IN ('ORG_UNIT','PERSONNEL_SUBAREA','PERSONNEL_AREA') AND (?='' OR structure_code LIKE ? OR structure_name LIKE ? OR structure_type LIKE ?) ORDER BY FIELD(structure_type,'ORG_UNIT','PERSONNEL_SUBAREA','PERSONNEL_AREA'),structure_code LIMIT 30",array($term,$like,$like,$like));
    em_select2($rows,'id',function($r){return $r->structure_code.' - '.$r->structure_name.' ['.$r->structure_type.']';}); break;
  case 'employee_search':
    $term=trim(em_post('term'));$exclude=(int)em_post('exclude');$like='%'.$term.'%';
    $rows=$db->query("SELECT id,employee_no,full_name FROM erp_employee_master WHERE employment_status IN ('ACTIVE','PROBATION','CONTRACT') AND (?='' OR employee_no LIKE ? OR full_name LIKE ?) AND (?=0 OR id<>?) ORDER BY employee_no LIMIT 30",array($term,$like,$like,$exclude,$exclude));
    em_select2($rows,'id',function($r){return $r->employee_no.' - '.$r->full_name;}); break;
  case 'user_search':
    $term=trim(em_post('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT id,username,first_name,last_name FROM sys_users WHERE COALESCE(aktif,'Y')='Y' AND (?='' OR username LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name,' ',last_name) LIKE ?) ORDER BY username LIMIT 30",array($term,$like,$like,$like,$like));
    em_select2($rows,'id',function($r){$n=trim($r->first_name.' '.$r->last_name);return $r->username.' - '.($n!==''?$n:$r->username);}); break;
  case 'cost_center_search':
    $term=trim(em_post('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT cost_center_code,cost_center_name,department_code FROM erp_cost_center WHERE status='Aktif' AND (?='' OR cost_center_code LIKE ? OR cost_center_name LIKE ? OR department_code LIKE ?) ORDER BY cost_center_code LIMIT 30",array($term,$like,$like,$like));
    em_select2($rows,'cost_center_code',function($r){return $r->cost_center_code.' - '.$r->cost_center_name;}); break;
  case 'profit_center_search':
    $term=trim(em_post('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT profit_center_code,profit_center_name FROM erp_profit_center WHERE status='Aktif' AND (?='' OR profit_center_code LIKE ? OR profit_center_name LIKE ?) ORDER BY profit_center_code LIMIT 30",array($term,$like,$like));
    em_select2($rows,'profit_center_code',function($r){return $r->profit_center_code.' - '.$r->profit_center_name;}); break;
  case 'get':
    $r=em_row((int)em_post('id')); if(!$r)em_json('error','Employee tidak ditemukan.'); em_json('good','',array('data'=>em_payload($r))); break;
  case 'save':
    $id=(int)em_post('id');
    $empNo=em_code(em_post('employee_no')); if($id===0&&$empNo==='')$empNo=em_next_employee_no(); $first=trim(em_post('first_name')); $last=trim(em_post('last_name')); $full=trim($first.' '.$last);
    $hire=trim(em_post('hire_date')); $validFrom=trim(em_post('valid_from')); $validTo=trim(em_post('valid_to','9999-12-31'));
    $status=em_code(em_post('employment_status','ACTIVE')); $group=em_code(em_post('employee_group','STAFF')); $gender=em_code(em_post('gender','MALE'));
    $marital=em_code(em_post('marital_status','SINGLE')); $identityType=em_code(em_post('identity_type','KTP')); $location=em_code(em_post('work_location_type','OFFICE'));
    $dept=em_code(em_post('department_code')); $jobId=em_post('job_title_id')!==''?(int)em_post('job_title_id'):null; $orgId=em_post('company_structure_id')!==''?(int)em_post('company_structure_id'):null;
    $managerId=em_post('manager_employee_id')!==''?(int)em_post('manager_employee_id'):null; $userId=em_post('user_id')!==''?(int)em_post('user_id'):null;
    $cost=trim(em_post('cost_center_code')); $profit=trim(em_post('profit_center_code'));
    if($empNo===''||!preg_match('/^[A-Z0-9_-]{2,20}$/',$empNo))em_json('error','Employee No wajib diisi dan hanya boleh huruf besar/angka/underscore/dash.');
    if($first==='')em_json('error','First Name wajib diisi.');
    if($hire==='')em_json('error','Hire Date wajib diisi.');
    if($validFrom===''||$validTo==='')em_json('error','Valid From dan Valid To wajib diisi.');
    if(strtotime($validTo)<strtotime($validFrom))em_json('error','Valid To tidak boleh sebelum Valid From.');
    if(!in_array($status,array('ACTIVE','PROBATION','CONTRACT','INACTIVE','TERMINATED'),true))em_json('error','Employment Status tidak valid.');
    if(!in_array($group,array('DIRECTOR','MANAGER','STAFF','NON_STAFF','OPERATOR','CONTRACT','DAILY_WORKER','TRAINEE'),true))em_json('error','Employee Group tidak valid.');
    if(!in_array($gender,array('MALE','FEMALE','OTHER'),true))em_json('error','Gender tidak valid.');
    if(!in_array($marital,array('SINGLE','MARRIED','DIVORCED','WIDOWED'),true))em_json('error','Marital Status tidak valid.');
    if(!in_array($identityType,array('KTP','PASSPORT','KITAS','OTHER'),true))em_json('error','Identity Type tidak valid.');
    if(!in_array($location,array('OFFICE','PLANT','WAREHOUSE','FIELD','REMOTE','HYBRID'),true))em_json('error','Work Location Type tidak valid.');
    if(trim(em_post('email'))!==''&&!filter_var(trim(em_post('email')),FILTER_VALIDATE_EMAIL))em_json('error','Format email tidak valid.');
    if($dept!==''){if(!$db->fetch("SELECT kd_dept FROM dept WHERE kd_dept=? AND status='ACTIVE' LIMIT 1",array($dept)))em_json('error','Department tidak valid atau nonaktif.');}
    if($jobId){if(!$db->fetch("SELECT id FROM erp_job_title WHERE id=? AND status='ACTIVE' LIMIT 1",array($jobId)))em_json('error','Job Title tidak valid atau nonaktif.');}
    if($orgId){if(!$db->fetch("SELECT id FROM erp_company_structure WHERE id=? AND status='ACTIVE' LIMIT 1",array($orgId)))em_json('error','Company Structure tidak valid.');}
    if($managerId){if($managerId===$id)em_json('error','Manager tidak boleh employee yang sama.'); if(!$db->fetch("SELECT id FROM erp_employee_master WHERE id=? LIMIT 1",array($managerId)))em_json('error','Manager Employee tidak valid.');}
    if($userId){if(!$db->fetch("SELECT id FROM sys_users WHERE id=? LIMIT 1",array($userId)))em_json('error','User account tidak valid.');}
    if($cost!==''){if(!$db->fetch("SELECT id FROM erp_cost_center WHERE cost_center_code=? AND status='Aktif' LIMIT 1",array($cost)))em_json('error','Cost Center tidak valid.');}
    if($profit!==''){if(!$db->fetch("SELECT id FROM erp_profit_center WHERE profit_center_code=? AND status='Aktif' LIMIT 1",array($profit)))em_json('error','Profit Center tidak valid.');}
    $dup=$db->fetch("SELECT id FROM erp_employee_master WHERE employee_no=? AND id<>? LIMIT 1",array($empNo,$id)); if($dup)em_json('error','Employee No sudah digunakan.');
    $data=array('employee_no'=>$empNo,'personnel_no'=>trim(em_post('personnel_no')),'first_name'=>$first,'last_name'=>$last,'full_name'=>$full,'gender'=>$gender,'birth_place'=>trim(em_post('birth_place')),'birth_date'=>trim(em_post('birth_date'))?:null,'marital_status'=>$marital,'nationality'=>em_code(em_post('nationality','ID')),'religion'=>trim(em_post('religion')),'identity_type'=>$identityType,'identity_no'=>trim(em_post('identity_no')),'tax_no'=>trim(em_post('tax_no')),'bpjs_kesehatan_no'=>trim(em_post('bpjs_kesehatan_no')),'bpjs_tk_no'=>trim(em_post('bpjs_tk_no')),'email'=>trim(em_post('email')),'phone'=>trim(em_post('phone')),'address'=>trim(em_post('address')),'city'=>trim(em_post('city')),'postal_code'=>trim(em_post('postal_code')),'emergency_contact_name'=>trim(em_post('emergency_contact_name')),'emergency_contact_phone'=>trim(em_post('emergency_contact_phone')),'hire_date'=>$hire,'probation_end_date'=>trim(em_post('probation_end_date'))?:null,'termination_date'=>trim(em_post('termination_date'))?:null,'employment_status'=>$status,'employee_group'=>$group,'employee_subgroup'=>trim(em_post('employee_subgroup')),'company_structure_id'=>$orgId,'department_code'=>$dept?:null,'job_title_id'=>$jobId,'manager_employee_id'=>$managerId,'cost_center_code'=>$cost,'profit_center_code'=>$profit,'payroll_area'=>trim(em_post('payroll_area')),'pay_grade'=>trim(em_post('pay_grade')),'work_location_type'=>$location,'shift_code'=>trim(em_post('shift_code')),'user_id'=>$userId,'valid_from'=>$validFrom,'valid_to'=>$validTo,'sap_reference'=>trim(em_post('sap_reference')),'remarks'=>trim(em_post('remarks')),'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s'));
    if($id>0){$ok=$db->update('erp_employee_master',$data,'id',$id);}else{$data['created_by']=$username;$ok=$db->insert('erp_employee_master',$data);$id=$db->last_insert_id();}
    if(!$ok)em_json('error',$db->getErrorMessage()?:hr_t('hr_employee_save_failed', 'Employee failed to save.'));
    em_sync_child_details($id,$empNo,$username);
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menyimpan Employee '.$empNo.' '.$full.' pada '.date('Y-m-d H:i:s'),$username);
    em_json('good','Employee berhasil disimpan.',array('id'=>$id));
    break;
  case 'delete':
    $id=(int)em_post('id');$r=$db->fetch("SELECT * FROM erp_employee_master WHERE id=? LIMIT 1",array($id)); if(!$r)em_json('error','Employee tidak ditemukan.');
    $child=$db->fetch("SELECT COUNT(*) jml FROM erp_employee_master WHERE manager_employee_id=?",array($id)); if($child&&(int)$child->jml>0)em_json('error','Employee masih menjadi manager bawahan.');
    $db->query("DELETE FROM erp_employee_family_data WHERE employee_id=?",array($id));
    $db->query("DELETE FROM erp_employee_education WHERE employee_id=?",array($id));
    $db->query("DELETE FROM erp_employee_document WHERE employee_id=?",array($id));
    $db->delete('erp_employee_master','id',$id); if($db->getErrorMessage())em_json('error',$db->getErrorMessage()); em_json('good','Employee berhasil dihapus.'); break;
  case 'detail':
    $r=em_row((int)em_post('id')); if(!$r){echo '<div class="alert alert-warning">Employee tidak ditemukan.</div>';break;}
    $families=em_family_rows($r->id);$educations=em_education_rows($r->id);$documents=em_document_rows($r->id);
    echo '<h3 style="margin-top:0">'.em_h($r->employee_no).' <small>'.em_h($r->full_name).'</small></h3><span class="label label-success">'.em_h($r->employment_status).'</span><hr>';
    echo '<ul class="nav nav-tabs"><li class="active"><a href="#emdt_profile" data-toggle="tab">Profile</a></li><li><a href="#emdt_family" data-toggle="tab">Family <span class="badge">'.count($families).'</span></a></li><li><a href="#emdt_education" data-toggle="tab">Education <span class="badge">'.count($educations).'</span></a></li><li><a href="#emdt_document" data-toggle="tab">'.hr_h('hr_document', 'Document').' <span class="badge">'.count($documents).'</span></a></li></ul><div class="tab-content" style="padding-top:14px">';
    echo '<div class="tab-pane active" id="emdt_profile"><div class="row"><div class="col-sm-3"><strong>Job</strong><br>'.em_h($r->job_title_code?($r->job_title_code.' - '.$r->job_title_name):'-').'</div><div class="col-sm-3"><strong>'.hr_h('hr_department', 'Department').'</strong><br>'.em_h($r->department_code?($r->department_code.' - '.$r->nm_dept):'-').'</div><div class="col-sm-3"><strong>'.hr_h('hr_manager', 'Manager').'</strong><br>'.em_h($r->manager_no?($r->manager_no.' - '.$r->manager_name):'-').'</div><div class="col-sm-3"><strong>Hire Date</strong><br>'.em_h($r->hire_date).'</div></div><hr><div class="row"><div class="col-sm-3"><strong>Cost Center</strong><br>'.em_h($r->cost_center_code?:'-').'<br><small>'.em_h($r->cost_center_name?:'').'</small></div><div class="col-sm-3"><strong>Profit Center</strong><br>'.em_h($r->profit_center_code?:'-').'<br><small>'.em_h($r->profit_center_name?:'').'</small></div><div class="col-sm-3"><strong>Contact</strong><br>'.em_h($r->phone?:'-').'<br><small>'.em_h($r->email?:'').'</small></div><div class="col-sm-3"><strong>User</strong><br>'.em_h($r->username?:'-').'</div></div><hr><strong>Address</strong><p>'.em_h($r->address?:'-').'</p></div>';
    echo '<div class="tab-pane" id="emdt_family"><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr><th>Relation</th><th>Name</th><th>Gender</th><th>Birth Date</th><th>Identity</th><th>Phone</th><th>Flags</th><th>'.hr_h('common_status', 'Status').'</th></tr></thead><tbody>';foreach($families as $f){echo '<tr><td>'.em_h($f['relationship_type']).'</td><td>'.em_h($f['family_name']).'</td><td>'.em_h($f['gender']).'</td><td>'.em_h($f['birth_date']).'</td><td>'.em_h($f['identity_type'].' '.$f['identity_no']).'</td><td>'.em_h($f['phone']).'</td><td>Dependent '.em_h($f['is_dependent']).' / Emergency '.em_h($f['emergency_contact']).'</td><td>'.em_h($f['status']).'</td></tr>';}echo '</tbody></table></div></div>';
    echo '<div class="tab-pane" id="emdt_education"><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr><th>Level</th><th>Type</th><th>Institution</th><th>Major</th><th>Graduation</th><th>Certificate</th><th>GPA</th><th>'.hr_h('common_status', 'Status').'</th></tr></thead><tbody>';foreach($educations as $ed){echo '<tr><td>'.em_h($ed['education_level']).'</td><td>'.em_h($ed['education_type']).'</td><td>'.em_h($ed['institution_name']).'</td><td>'.em_h($ed['major']).'</td><td>'.em_h($ed['graduation_year']).'</td><td>'.em_h($ed['certificate_no']).'</td><td>'.em_h($ed['gpa']).'</td><td>'.em_h($ed['verified_status'].' / '.$ed['status']).'</td></tr>';}echo '</tbody></table></div></div>';
    echo '<div class="tab-pane" id="emdt_document"><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr><th>Type</th><th>Category</th><th>Title</th><th>Number</th><th>Issue</th><th>Expiry</th><th>'.hr_h('hr_file', 'File').'</th><th>'.hr_h('common_status', 'Status').'</th></tr></thead><tbody>';foreach($documents as $doc){echo '<tr><td>'.em_h($doc['document_type']).'</td><td>'.em_h($doc['document_category']).'</td><td>'.em_h($doc['document_title']).'</td><td>'.em_h($doc['document_number']).'</td><td>'.em_h($doc['issue_date']).'</td><td>'.em_h($doc['expiry_date']).'</td><td>'.em_h($doc['file_ref']).'</td><td>'.em_h($doc['verification_status'].' / '.$doc['status']).'</td></tr>';}echo '</tbody></table></div></div></div>';
    break;
  default: em_json('error','Action tidak dikenal.');
}
?>
