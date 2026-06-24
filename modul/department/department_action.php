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

function dept_json($status,$message='',$extra=array()){
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
function dept_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function dept_post($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function dept_code($v){return strtoupper(trim((string)$v));}
function dept_valid_type($v){return in_array($v,array('FUNCTIONAL','OPERATIONAL','SUPPORT','SALES','PRODUCTION','WAREHOUSE','QUALITY','FINANCE','HR'),true);}
function dept_select2($rows,$idField,$textCb){
  $results=array();
  foreach($rows as $r)$results[]=array('id'=>$r->$idField,'text'=>$textCb($r));
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('results'=>$results));
  exit;
}
function dept_row($code){
  global $db;
  return $db->fetch("SELECT d.*,pd.nm_dept parent_name,cs.structure_code,cs.structure_name,cs.structure_type,cc.cost_center_name,pc.profit_center_name,u.username manager_username,TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) manager_name
    FROM dept d
    LEFT JOIN dept pd ON pd.kd_dept=d.parent_dept_code
    LEFT JOIN erp_company_structure cs ON cs.id=d.company_structure_id
    LEFT JOIN erp_cost_center cc ON cc.cost_center_code=d.cost_center_code
    LEFT JOIN erp_profit_center pc ON pc.profit_center_code=d.profit_center_code
    LEFT JOIN sys_users u ON u.id=d.manager_user_id
    WHERE d.kd_dept=? LIMIT 1",array($code));
}
function dept_has_cycle($code,$parent){
  global $db;
  if($code===''||$parent==='')return false;
  if($code===$parent)return true;
  $guard=0;$cur=$parent;
  while($cur!=='' && $guard<50){
    $p=$db->fetch("SELECT parent_dept_code FROM dept WHERE kd_dept=? LIMIT 1",array($cur));
    if(!$p||trim((string)$p->parent_dept_code)==='')return false;
    $cur=trim((string)$p->parent_dept_code);
    if($cur===$code)return true;
    $guard++;
  }
  return false;
}
function dept_data_payload($row){
  $data=(array)$row;
  $data['parent_dept_text']=$row->parent_dept_code?($row->parent_dept_code.' - '.$row->parent_name):'';
  $data['company_structure_text']=$row->company_structure_id?($row->structure_code.' - '.$row->structure_name.' ['.$row->structure_type.']'):'';
  $data['cost_center_text']=$row->cost_center_code?($row->cost_center_code.' - '.$row->cost_center_name):'';
  $data['profit_center_text']=$row->profit_center_code?($row->profit_center_code.' - '.$row->profit_center_name):'';
  $manager=trim($row->manager_name)!==''?$row->manager_name:$row->manager_username;
  $data['manager_text']=$row->manager_user_id?($row->manager_username.' - '.$manager):'';
  return $data;
}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=isset($_SESSION['username'])?$_SESSION['username']:'system';

switch($act){
  case 'department_search':
    $term=trim(dept_post('term'));
    $exclude=dept_code(dept_post('exclude'));
    $like='%'.$term.'%';
    $rows=$db->query("SELECT kd_dept,nm_dept,dept_type FROM dept WHERE (?='' OR kd_dept LIKE ? OR nm_dept LIKE ?) AND (?='' OR kd_dept<>?) ORDER BY kd_dept LIMIT 30",array($term,$like,$like,$exclude,$exclude));
    dept_select2($rows,'kd_dept',function($r){return $r->kd_dept.' - '.$r->nm_dept.' ['.$r->dept_type.']';});
    break;

  case 'company_structure_search':
    $term=trim(dept_post('term'));
    $like='%'.$term.'%';
    $rows=$db->query("SELECT id,structure_code,structure_name,structure_type FROM erp_company_structure WHERE status='ACTIVE' AND structure_type IN ('ORG_UNIT','PERSONNEL_SUBAREA','PERSONNEL_AREA') AND (?='' OR structure_code LIKE ? OR structure_name LIKE ? OR structure_type LIKE ?) ORDER BY FIELD(structure_type,'ORG_UNIT','PERSONNEL_SUBAREA','PERSONNEL_AREA'),structure_code LIMIT 30",array($term,$like,$like,$like));
    dept_select2($rows,'id',function($r){return $r->structure_code.' - '.$r->structure_name.' ['.$r->structure_type.']';});
    break;

  case 'cost_center_search':
    $term=trim(dept_post('term'));
    $like='%'.$term.'%';
    $rows=$db->query("SELECT cost_center_code,cost_center_name,department_code FROM erp_cost_center WHERE status='Aktif' AND (?='' OR cost_center_code LIKE ? OR cost_center_name LIKE ? OR department_code LIKE ?) ORDER BY cost_center_code LIMIT 30",array($term,$like,$like,$like));
    dept_select2($rows,'cost_center_code',function($r){return $r->cost_center_code.' - '.$r->cost_center_name;});
    break;

  case 'profit_center_search':
    $term=trim(dept_post('term'));
    $like='%'.$term.'%';
    $rows=$db->query("SELECT profit_center_code,profit_center_name FROM erp_profit_center WHERE status='Aktif' AND (?='' OR profit_center_code LIKE ? OR profit_center_name LIKE ?) ORDER BY profit_center_code LIMIT 30",array($term,$like,$like));
    dept_select2($rows,'profit_center_code',function($r){return $r->profit_center_code.' - '.$r->profit_center_name;});
    break;

  case 'user_search':
    $term=trim(dept_post('term'));
    $like='%'.$term.'%';
    $rows=$db->query("SELECT id,username,first_name,last_name FROM sys_users WHERE COALESCE(aktif,'Y')='Y' AND (?='' OR username LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name,' ',last_name) LIKE ?) ORDER BY username LIMIT 30",array($term,$like,$like,$like,$like));
    dept_select2($rows,'id',function($r){$name=trim($r->first_name.' '.$r->last_name);return $r->username.' - '.($name!==''?$name:$r->username);});
    break;

  case 'get':
    $id=dept_code(dept_post('id'));
    $row=dept_row($id);
    if(!$row)dept_json('error','Department tidak ditemukan.');
    dept_json('good','',array('data'=>dept_data_payload($row)));
    break;

  case 'save':
    $id=dept_code(dept_post('id'));
    $code=dept_code(dept_post('kd_dept'));
    $name=trim(dept_post('nm_dept'));
    $type=dept_code(dept_post('dept_type'));
    $parent=dept_code(dept_post('parent_dept_code'));
    $companyStructureId=dept_post('company_structure_id')!==''?(int)dept_post('company_structure_id'):null;
    $costCenter=trim(dept_post('cost_center_code'));
    $profitCenter=trim(dept_post('profit_center_code'));
    $managerId=dept_post('manager_user_id')!==''?(int)dept_post('manager_user_id'):null;
    $validFrom=trim(dept_post('valid_from'));
    $validTo=trim(dept_post('valid_to','9999-12-31'));
    $status=dept_code(dept_post('status','ACTIVE'));

    if($code==='')dept_json('error','Dept Code wajib diisi.');
    if(!preg_match('/^[A-Z0-9_-]{2,8}$/',$code))dept_json('error','Dept Code hanya boleh huruf besar, angka, underscore, atau dash. Maksimal 8 karakter.');
    if($name==='')dept_json('error','Department Name wajib diisi.');
    if(!dept_valid_type($type))dept_json('error','Department Type tidak valid.');
    if(!in_array($status,array('ACTIVE','INACTIVE'),true))dept_json('error','Status tidak valid.');
    if($validFrom===''||$validTo==='')dept_json('error','Valid From dan Valid To wajib diisi.');
    if(strtotime($validTo)<strtotime($validFrom))dept_json('error','Valid To tidak boleh sebelum Valid From.');
    if($id!=='' && $id!==$code)dept_json('error','Dept Code tidak boleh diubah saat edit.');
    if($parent!==''){
      $p=$db->fetch("SELECT kd_dept,status FROM dept WHERE kd_dept=? LIMIT 1",array($parent));
      if(!$p)dept_json('error','Parent Department tidak ditemukan.');
      if(dept_has_cycle($code,$parent))dept_json('error','Parent Department menyebabkan hirarki melingkar.');
    }
    if($companyStructureId){
      $cs=$db->fetch("SELECT id FROM erp_company_structure WHERE id=? AND status='ACTIVE' LIMIT 1",array($companyStructureId));
      if(!$cs)dept_json('error','Company Structure / Org Unit tidak valid.');
    }
    if($costCenter!==''){
      $cc=$db->fetch("SELECT id FROM erp_cost_center WHERE cost_center_code=? AND status='Aktif' LIMIT 1",array($costCenter));
      if(!$cc)dept_json('error','Cost Center tidak valid atau nonaktif.');
    }
    if($profitCenter!==''){
      $pc=$db->fetch("SELECT id FROM erp_profit_center WHERE profit_center_code=? AND status='Aktif' LIMIT 1",array($profitCenter));
      if(!$pc)dept_json('error','Profit Center tidak valid atau nonaktif.');
    }
    if($managerId){
      $usr=$db->fetch("SELECT id FROM sys_users WHERE id=? AND COALESCE(aktif,'Y')='Y' LIMIT 1",array($managerId));
      if(!$usr)dept_json('error','Department Manager tidak valid atau nonaktif.');
    }

    $existing=$db->fetch("SELECT kd_dept FROM dept WHERE kd_dept=? LIMIT 1",array($code));
    if($id==='' && $existing)dept_json('error','Dept Code sudah digunakan.');
    if($id!=='' && !$existing)dept_json('error','Department tidak ditemukan.');

    $data=array(
      'kd_dept'=>$code,
      'nm_dept'=>$name,
      'dept_short_name'=>trim(dept_post('dept_short_name')),
      'dept_type'=>$type,
      'parent_dept_code'=>$parent!==''?$parent:null,
      'company_structure_id'=>$companyStructureId,
      'cost_center_code'=>$costCenter,
      'profit_center_code'=>$profitCenter,
      'manager_user_id'=>$managerId,
      'functional_area'=>trim(dept_post('functional_area')),
      'valid_from'=>$validFrom,
      'valid_to'=>$validTo,
      'status'=>$status,
      'sap_reference'=>trim(dept_post('sap_reference')),
      'remarks'=>trim(dept_post('remarks')),
      'updated_by'=>$username,
      'updated_at'=>date('Y-m-d H:i:s')
    );

    if($id===''){
      $data['created_by']=$username;
      $ok=$db->insert('dept',$data);
    }else{
      unset($data['kd_dept']);
      $ok=$db->update('dept',$data,'kd_dept',$id);
    }
    if(!$ok)dept_json('error',$db->getErrorMessage()?:'Department gagal disimpan.');
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menyimpan department '.$code.' dengan nama '.$name.' pada '.date('Y-m-d H:i:s'),$username);
    dept_json('good','Department berhasil disimpan.',array('id'=>$code));
    break;

  case 'status':
    $id=dept_code(dept_post('id'));
    $status=dept_code(dept_post('status'));
    if(!in_array($status,array('ACTIVE','INACTIVE'),true))dept_json('error','Status tidak valid.');
    $row=$db->fetch("SELECT kd_dept FROM dept WHERE kd_dept=? LIMIT 1",array($id));
    if(!$row)dept_json('error','Department tidak ditemukan.');
    $ok=$db->update('dept',array('status'=>$status,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')),'kd_dept',$id);
    if(!$ok)dept_json('error',$db->getErrorMessage()?:'Status gagal diubah.');
    if(function_exists('simpan_log'))simpan_log('User '.$username.' mengubah status department '.$id.' menjadi '.$status,$username);
    dept_json('good','Status berhasil diubah.');
    break;

  case 'delete':
    $id=dept_code(dept_post('id',isset($_GET['id'])?$_GET['id']:''));
    $row=$db->fetch("SELECT kd_dept FROM dept WHERE kd_dept=? LIMIT 1",array($id));
    if(!$row)dept_json('error','Department tidak ditemukan.');
    $child=$db->fetch("SELECT COUNT(*) jml FROM dept WHERE parent_dept_code=?",array($id));
    if($child && (int)$child->jml>0)dept_json('error','Department punya child. Pindahkan/hapus child terlebih dahulu.');
    $db->delete('dept','kd_dept',$id);
    if($db->getErrorMessage())dept_json('error',$db->getErrorMessage());
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menghapus department '.$id,$username);
    dept_json('good','Department berhasil dihapus.');
    break;

  case 'del_massal':
    $ids=explode(',',dept_post('data_ids'));
    foreach($ids as $id){
      $id=dept_code($id);
      if($id==='')continue;
      $child=$db->fetch("SELECT COUNT(*) jml FROM dept WHERE parent_dept_code=?",array($id));
      if($child && (int)$child->jml>0)dept_json('error','Department '.$id.' punya child. Pindahkan/hapus child terlebih dahulu.');
      $db->delete('dept','kd_dept',$id);
    }
    dept_json('good','Department terpilih berhasil dihapus.');
    break;

  case 'detail':
    $id=dept_code(dept_post('id'));
    $r=dept_row($id);
    if(!$r){echo '<div class="alert alert-warning">Department tidak ditemukan.</div>';break;}
    $statusClass=$r->status==='ACTIVE'?'success':'warning';
    echo '<div class="row"><div class="col-md-8"><h3 style="margin-top:0">'.dept_h($r->kd_dept).' <small>'.dept_h($r->nm_dept).'</small></h3><span class="label label-'.$statusClass.'">'.dept_h($r->status).'</span> <span class="label label-info">'.dept_h($r->dept_type).'</span></div><div class="col-md-4 text-right"><strong>SAP Ref</strong><br>'.dept_h($r->sap_reference?:'-').'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><strong>Parent</strong><br>'.dept_h($r->parent_dept_code?($r->parent_dept_code.' - '.$r->parent_name):'Root').'</div><div class="col-sm-3"><strong>Org Assignment</strong><br>'.dept_h($r->structure_code?($r->structure_code.' - '.$r->structure_name):'-').'<br><small>'.dept_h($r->structure_type?:'').'</small></div><div class="col-sm-3"><strong>Functional Area</strong><br>'.dept_h($r->functional_area?:'-').'</div><div class="col-sm-3"><strong>Validity</strong><br>'.dept_h($r->valid_from.' s/d '.$r->valid_to).'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><strong>Cost Center</strong><br>'.dept_h($r->cost_center_code?:'-').'<br><small>'.dept_h($r->cost_center_name?:'').'</small></div><div class="col-sm-3"><strong>Profit Center</strong><br>'.dept_h($r->profit_center_code?:'-').'<br><small>'.dept_h($r->profit_center_name?:'').'</small></div><div class="col-sm-3"><strong>'.hr_h('hr_manager', 'Manager').'</strong><br>'.dept_h($r->manager_username?:'-').'<br><small>'.dept_h(trim($r->manager_name)?:'').'</small></div><div class="col-sm-3"><strong>Short Name</strong><br>'.dept_h($r->dept_short_name?:'-').'</div></div><hr>';
    echo '<strong>'.hr_h('common_remarks', 'Remarks').'</strong><p>'.nl2br(dept_h($r->remarks?:'-')).'</p>';
    break;

  default:
    dept_json('error','Action tidak dikenal.');
}
?>
