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

function csj($status,$message='',$extra=array()){
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
function csh($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function csp($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function cs_clean_code($v){return strtoupper(trim((string)$v));}
function cs_valid_type($v){return in_array($v,array('COMPANY','COMPANY_CODE','BUSINESS_AREA','PERSONNEL_AREA','PERSONNEL_SUBAREA','ORG_UNIT'),true);}
function cs_center_text($code,$name){return trim((string)$code)!=='' ? trim((string)$code.' - '.(string)$name) : '';}
function cs_select2_json($rows,$codeField,$nameField){
  $results=array();
  foreach($rows as $r){
    $code=$r->$codeField;
    $name=$r->$nameField;
    $results[]=array('id'=>$code,'text'=>cs_center_text($code,$name),'code'=>$code,'name'=>$name);
  }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('results'=>$results));
  exit;
}
function cs_validate_parent($type,$parentId){
  if($parentId<=0)return $type==='COMPANY';
  global $db;
  $parent=$db->fetch("SELECT id,structure_type,status FROM erp_company_structure WHERE id=? LIMIT 1",array($parentId));
  if(!$parent)return false;
  if($parent->status==='INACTIVE')return false;
  $allowed=array(
    'COMPANY'=>array(),
    'COMPANY_CODE'=>array('COMPANY'),
    'BUSINESS_AREA'=>array('COMPANY','COMPANY_CODE'),
    'PERSONNEL_AREA'=>array('COMPANY','COMPANY_CODE','BUSINESS_AREA'),
    'PERSONNEL_SUBAREA'=>array('PERSONNEL_AREA'),
    'ORG_UNIT'=>array('COMPANY','COMPANY_CODE','BUSINESS_AREA','PERSONNEL_AREA','PERSONNEL_SUBAREA','ORG_UNIT')
  );
  return in_array($parent->structure_type,$allowed[$type],true);
}
function cs_has_cycle($id,$parentId){
  global $db;
  if($id<=0||$parentId<=0)return false;
  if($id===$parentId)return true;
  $guard=0;$cur=$parentId;
  while($cur>0 && $guard<50){
    $p=$db->fetch("SELECT parent_id FROM erp_company_structure WHERE id=? LIMIT 1",array($cur));
    if(!$p||!(int)$p->parent_id)return false;
    $cur=(int)$p->parent_id;
    if($cur===$id)return true;
    $guard++;
  }
  return false;
}
function cs_tree($parentId,$level=0){
  global $db;
  $rows=$db->query("SELECT * FROM erp_company_structure WHERE ".($parentId>0?"parent_id=?":"parent_id IS NULL")." ORDER BY structure_type,structure_code",$parentId>0?array($parentId):null);
  $html='<ul class="cs-tree">';
  foreach($rows as $r){
    $html.='<li><strong>'.csh($r->structure_code).'</strong> - '.csh($r->structure_name).' <small>['.csh($r->structure_type).' / '.csh($r->status).']</small>';
    if($level<8)$html.=cs_tree((int)$r->id,$level+1);
    $html.='</li>';
  }
  $html.='</ul>';
  return $html;
}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=isset($_SESSION['username'])?$_SESSION['username']:'system';

switch($act){
  case 'cost_center_search':
    $term=trim(csp('term'));
    $like='%'.$term.'%';
    $rows=$db->query("SELECT cost_center_code,cost_center_name,department_code FROM erp_cost_center WHERE status='Aktif' AND (?='' OR cost_center_code LIKE ? OR cost_center_name LIKE ? OR department_code LIKE ?) ORDER BY cost_center_code LIMIT 30",array($term,$like,$like,$like));
    cs_select2_json($rows,'cost_center_code','cost_center_name');
    break;

  case 'profit_center_search':
    $term=trim(csp('term'));
    $like='%'.$term.'%';
    $rows=$db->query("SELECT profit_center_code,profit_center_name FROM erp_profit_center WHERE status='Aktif' AND (?='' OR profit_center_code LIKE ? OR profit_center_name LIKE ?) ORDER BY profit_center_code LIMIT 30",array($term,$like,$like));
    cs_select2_json($rows,'profit_center_code','profit_center_name');
    break;

  case 'get':
    $id=(int)csp('id');
    $row=$db->fetch("SELECT * FROM erp_company_structure WHERE id=? LIMIT 1",array($id));
    if(!$row)csj('error','Company Structure tidak ditemukan.');
    $data=(array)$row;
    $data['cost_center_text']='';
    $data['profit_center_text']='';
    if(trim((string)$row->cost_center_code)!==''){
      $cc=$db->fetch("SELECT cost_center_code,cost_center_name FROM erp_cost_center WHERE cost_center_code=? LIMIT 1",array($row->cost_center_code));
      $data['cost_center_text']=$cc?cs_center_text($cc->cost_center_code,$cc->cost_center_name):$row->cost_center_code;
    }
    if(trim((string)$row->profit_center_code)!==''){
      $pc=$db->fetch("SELECT profit_center_code,profit_center_name FROM erp_profit_center WHERE profit_center_code=? LIMIT 1",array($row->profit_center_code));
      $data['profit_center_text']=$pc?cs_center_text($pc->profit_center_code,$pc->profit_center_name):$row->profit_center_code;
    }
    csj('good','',array('data'=>$data));
    break;

  case 'save':
    $id=(int)csp('id');
    $code=cs_clean_code(csp('structure_code'));
    $name=trim(csp('structure_name'));
    $type=cs_clean_code(csp('structure_type'));
    $parentId=csp('parent_id')!==''?(int)csp('parent_id'):0;
    $country=cs_clean_code(csp('country','ID'));
    $currency=cs_clean_code(csp('currency','IDR'));
    $validFrom=trim(csp('valid_from'));
    $validTo=trim(csp('valid_to','9999-12-31'));

    if($code==='')csj('error','Structure Code wajib diisi.');
    if(!preg_match('/^[A-Z0-9_-]{2,20}$/',$code))csj('error','Structure Code hanya boleh huruf besar, angka, underscore, atau dash. Minimal 2 karakter.');
    if($name==='')csj('error','Structure Name wajib diisi.');
    if(!cs_valid_type($type))csj('error','Structure Type tidak valid.');
    if($country===''||strlen($country)>3)csj('error','Country wajib diisi maksimal 3 karakter.');
    if($currency===''||strlen($currency)>3)csj('error','Currency wajib diisi maksimal 3 karakter.');
    if($validFrom===''||$validTo==='')csj('error','Valid From dan Valid To wajib diisi.');
    if(strtotime($validTo)<strtotime($validFrom))csj('error','Valid To tidak boleh sebelum Valid From.');
    if(!cs_validate_parent($type,$parentId))csj('error','Parent tidak sesuai hirarki SAP HR untuk type '.$type.'.');
    if(cs_has_cycle($id,$parentId))csj('error','Parent menyebabkan struktur melingkar.');

    $dup=$db->fetch("SELECT id FROM erp_company_structure WHERE structure_code=? AND id<>? LIMIT 1",array($code,$id));
    if($dup)csj('error','Structure Code sudah digunakan.');

    if($id>0){
      $old=$db->fetch("SELECT status FROM erp_company_structure WHERE id=? LIMIT 1",array($id));
      if(!$old)csj('error','Company Structure tidak ditemukan.');
      if($old->status!=='DRAFT')csj('error','Hanya status DRAFT yang bisa diedit.');
    }

    $costCenterCode=trim(csp('cost_center_code'));
    $profitCenterCode=trim(csp('profit_center_code'));
    if($costCenterCode!==''){
      $cc=$db->fetch("SELECT id FROM erp_cost_center WHERE cost_center_code=? AND status='Aktif' LIMIT 1",array($costCenterCode));
      if(!$cc)csj('error','Cost Center tidak valid atau sudah nonaktif.');
    }
    if($profitCenterCode!==''){
      $pc=$db->fetch("SELECT id FROM erp_profit_center WHERE profit_center_code=? AND status='Aktif' LIMIT 1",array($profitCenterCode));
      if(!$pc)csj('error','Profit Center tidak valid atau sudah nonaktif.');
    }

    $data=array(
      'structure_code'=>$code,
      'structure_name'=>$name,
      'structure_type'=>$type,
      'parent_id'=>$parentId>0?$parentId:null,
      'legal_entity_name'=>trim(csp('legal_entity_name')),
      'tax_id'=>trim(csp('tax_id')),
      'country'=>$country,
      'currency'=>$currency,
      'valid_from'=>$validFrom,
      'valid_to'=>$validTo,
      'address'=>trim(csp('address')),
      'city'=>trim(csp('city')),
      'phone'=>trim(csp('phone')),
      'email'=>trim(csp('email')),
      'cost_center_code'=>$costCenterCode,
      'profit_center_code'=>$profitCenterCode,
      'sap_reference'=>trim(csp('sap_reference')),
      'remarks'=>trim(csp('remarks')),
      'updated_by'=>$username,
      'updated_at'=>date('Y-m-d H:i:s')
    );
    if($data['email']!=='' && !filter_var($data['email'],FILTER_VALIDATE_EMAIL))csj('error','Format email tidak valid.');

    if($id>0){
      $ok=$db->update('erp_company_structure',$data,'id',$id);
    }else{
      $data['status']='DRAFT';
      $data['created_by']=$username;
      $ok=$db->insert('erp_company_structure',$data);
      $id=$db->last_insert_id();
    }
    if(!$ok)csj('error',$db->getErrorMessage()?:'Company Structure gagal disimpan.');
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menyimpan Company Structure '.$code.' pada '.date('Y-m-d H:i:s'),$username);
    csj('good','',array('id'=>$id));
    break;

  case 'activate':
    $id=(int)csp('id');
    $row=$db->fetch("SELECT * FROM erp_company_structure WHERE id=? LIMIT 1",array($id));
    if(!$row)csj('error','Company Structure tidak ditemukan.');
    if($row->status!=='DRAFT')csj('error','Hanya DRAFT yang bisa di-activate.');
    if($row->structure_type!=='COMPANY' && !$row->parent_id)csj('error','Structure selain COMPANY wajib punya parent sebelum active.');
    $db->query("UPDATE erp_company_structure SET status='ACTIVE',updated_by=?,updated_at=NOW() WHERE id=?",array($username,$id));
    if(function_exists('simpan_log'))simpan_log('User '.$username.' activate Company Structure '.$row->structure_code,$username);
    csj('good','Company Structure berhasil active.');
    break;

  case 'inactive':
    $id=(int)csp('id');$reason=trim(csp('reason'));
    if($reason==='')csj('error','Reason inactive wajib diisi.');
    $row=$db->fetch("SELECT * FROM erp_company_structure WHERE id=? LIMIT 1",array($id));
    if(!$row)csj('error','Company Structure tidak ditemukan.');
    if($row->status!=='ACTIVE')csj('error','Hanya ACTIVE yang bisa inactive.');
    $activeChild=$db->fetch("SELECT COUNT(*) jml FROM erp_company_structure WHERE parent_id=? AND status='ACTIVE'",array($id));
    if($activeChild && (int)$activeChild->jml>0)csj('error','Masih ada child ACTIVE. Inactive child terlebih dahulu.');
    $db->query("UPDATE erp_company_structure SET status='INACTIVE',inactive_reason=?,updated_by=?,updated_at=NOW() WHERE id=?",array($reason,$username,$id));
    if(function_exists('simpan_log'))simpan_log('User '.$username.' inactive Company Structure '.$row->structure_code.' alasan '.$reason,$username);
    csj('good','Company Structure berhasil inactive.');
    break;

  case 'delete':
    $id=(int)csp('id');
    $row=$db->fetch("SELECT * FROM erp_company_structure WHERE id=? LIMIT 1",array($id));
    if(!$row)csj('error','Company Structure tidak ditemukan.');
    if($row->status!=='DRAFT')csj('error','Hanya DRAFT yang bisa delete.');
    $child=$db->fetch("SELECT COUNT(*) jml FROM erp_company_structure WHERE parent_id=?",array($id));
    if($child && (int)$child->jml>0)csj('error','Structure punya child, hapus child terlebih dahulu.');
    $db->delete('erp_company_structure','id',$id);
    if(function_exists('simpan_log'))simpan_log('User '.$username.' delete draft Company Structure '.$row->structure_code,$username);
    csj('good','Company Structure berhasil dihapus.');
    break;

  case 'detail':
    $id=(int)csp('id');
    $r=$db->fetch("SELECT cs.*,p.structure_code parent_code,p.structure_name parent_name,p.structure_type parent_type FROM erp_company_structure cs LEFT JOIN erp_company_structure p ON p.id=cs.parent_id WHERE cs.id=? LIMIT 1",array($id));
    if(!$r){echo '<div class="alert alert-warning">Company Structure tidak ditemukan.</div>';break;}
    echo '<h3 style="margin-top:0">'.csh($r->structure_code).' <small>'.csh($r->status).'</small></h3>';
    echo '<div class="row"><div class="col-sm-3"><strong>Name</strong><br>'.csh($r->structure_name).'</div><div class="col-sm-3"><strong>Type</strong><br>'.csh($r->structure_type).'</div><div class="col-sm-3"><strong>Parent</strong><br>'.($r->parent_code?csh($r->parent_code.' - '.$r->parent_name.' ['.$r->parent_type.']'):'Root').'</div><div class="col-sm-3"><strong>Validity</strong><br>'.csh($r->valid_from.' s/d '.$r->valid_to).'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-4"><strong>Legal Entity</strong><br>'.csh($r->legal_entity_name?:'-').'<br><small>Tax: '.csh($r->tax_id?:'-').'</small></div><div class="col-sm-4"><strong>Location</strong><br>'.csh($r->address?:'-').'<br><small>'.csh($r->city?:'-').' / '.csh($r->country).'</small></div><div class="col-sm-4"><strong>Contact</strong><br>'.csh($r->phone?:'-').'<br><small>'.csh($r->email?:'-').'</small></div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><strong>Currency</strong><br>'.csh($r->currency).'</div><div class="col-sm-3"><strong>Cost Center</strong><br>'.csh($r->cost_center_code?:'-').'</div><div class="col-sm-3"><strong>Profit Center</strong><br>'.csh($r->profit_center_code?:'-').'</div><div class="col-sm-3"><strong>SAP Reference</strong><br>'.csh($r->sap_reference?:'-').'</div></div><hr>';
    echo '<strong>'.hr_h('common_remarks', 'Remarks').'</strong><p>'.nl2br(csh($r->remarks?:'-')).'</p><hr>';
    echo '<h4>Child Structure</h4>';
    $childCount=$db->fetch("SELECT COUNT(*) jml FROM erp_company_structure WHERE parent_id=?",array($id));
    echo ($childCount && (int)$childCount->jml>0)?cs_tree($id):'<div class="alert alert-info">Belum ada child structure.</div>';
    break;

  default:
    csj('error','Action tidak dikenal.');
}
?>
