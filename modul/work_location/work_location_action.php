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

function wl_json($status,$message='',$extra=array()){header('Content-Type: application/json; charset=utf-8');$p=array('status'=>$status);if($message!=='')$p[$status==='good'?'message':'error_message']=$message;foreach($extra as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function wl_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function wl_p($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function wl_g($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
function wl_c($v){return strtoupper(trim((string)$v));}
function wl_user(){return isset($_SESSION['username'])&&$_SESSION['username']!==''?$_SESSION['username']:'admin';}
function wl_select2($rows,$idField,$cb){$out=array();foreach($rows as $r)$out[]=array('id'=>$r->$idField,'text'=>$cb($r));header('Content-Type: application/json; charset=utf-8');echo json_encode(array('results'=>$out));exit;}
function wl_center_text($code,$name){return trim((string)$code)!==''?trim((string)$code.' - '.(string)$name):'';}
function wl_row($id){
  global $db;
  return $db->fetch("SELECT wl.*,cs.structure_code,cs.structure_name,cs.structure_type,p.plant_code,p.plant_name,sl.storage_code,sl.storage_name,cc.cost_center_name,pc.profit_center_name
    FROM erp_work_location wl
    LEFT JOIN erp_company_structure cs ON cs.id=wl.company_structure_id
    LEFT JOIN erp_plant p ON p.id=wl.plant_id
    LEFT JOIN erp_storage_location sl ON sl.id=wl.storage_location_id
    LEFT JOIN erp_cost_center cc ON cc.cost_center_code=wl.cost_center_code
    LEFT JOIN erp_profit_center pc ON pc.profit_center_code=wl.profit_center_code
    WHERE wl.id=? LIMIT 1",array((int)$id));
}
function wl_payload($r){
  $d=(array)$r;
  $d['company_structure_text']=$r->company_structure_id?($r->structure_code.' - '.$r->structure_name.' ['.$r->structure_type.']'):'';
  $d['plant_text']=$r->plant_id?($r->plant_code.' - '.$r->plant_name):'';
  $d['storage_location_text']=$r->storage_location_id?($r->storage_code.' - '.$r->storage_name):'';
  $d['cost_center_text']=$r->cost_center_code?wl_center_text($r->cost_center_code,$r->cost_center_name):'';
  $d['profit_center_text']=$r->profit_center_code?wl_center_text($r->profit_center_code,$r->profit_center_name):'';
  return $d;
}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=wl_user();

switch($act){
  case 'company_structure_search':
    $term=trim(wl_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT id,structure_code,structure_name,structure_type FROM erp_company_structure WHERE status='ACTIVE' AND structure_type IN ('PERSONNEL_AREA','PERSONNEL_SUBAREA','ORG_UNIT','COMPANY_CODE') AND (?='' OR structure_code LIKE ? OR structure_name LIKE ? OR structure_type LIKE ?) ORDER BY FIELD(structure_type,'COMPANY_CODE','PERSONNEL_AREA','PERSONNEL_SUBAREA','ORG_UNIT'),structure_code LIMIT 30",array($term,$like,$like,$like));
    wl_select2($rows,'id',function($r){return $r->structure_code.' - '.$r->structure_name.' ['.$r->structure_type.']';});break;
  case 'plant_search':
    $term=trim(wl_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT id,plant_code,plant_name,city FROM erp_plant WHERE status='Aktif' AND (?='' OR plant_code LIKE ? OR plant_name LIKE ? OR city LIKE ?) ORDER BY plant_code LIMIT 30",array($term,$like,$like,$like));
    wl_select2($rows,'id',function($r){return $r->plant_code.' - '.$r->plant_name.($r->city?' ['.$r->city.']':'');});break;
  case 'storage_location_search':
    $term=trim(wl_p('term'));$plant=(int)wl_p('plant_id');$like='%'.$term.'%';
    $where=" WHERE sl.status='Aktif' AND (?='' OR sl.storage_code LIKE ? OR sl.storage_name LIKE ? OR sl.storage_type LIKE ?) ";$p=array($term,$like,$like,$like);
    if($plant>0){$where.=" AND sl.plant_id=? ";$p[]=$plant;}
    $rows=$db->query("SELECT sl.id,sl.storage_code,sl.storage_name,sl.storage_type,p.plant_code FROM erp_storage_location sl LEFT JOIN erp_plant p ON p.id=sl.plant_id $where ORDER BY p.plant_code,sl.storage_code LIMIT 30",$p);
    wl_select2($rows,'id',function($r){return $r->plant_code.' / '.$r->storage_code.' - '.$r->storage_name.' ['.$r->storage_type.']';});break;
  case 'cost_center_search':
    $term=trim(wl_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT cost_center_code,cost_center_name,department_code FROM erp_cost_center WHERE status='Aktif' AND (?='' OR cost_center_code LIKE ? OR cost_center_name LIKE ? OR department_code LIKE ?) ORDER BY cost_center_code LIMIT 30",array($term,$like,$like,$like));
    wl_select2($rows,'cost_center_code',function($r){return wl_center_text($r->cost_center_code,$r->cost_center_name);});break;
  case 'profit_center_search':
    $term=trim(wl_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT profit_center_code,profit_center_name FROM erp_profit_center WHERE status='Aktif' AND (?='' OR profit_center_code LIKE ? OR profit_center_name LIKE ?) ORDER BY profit_center_code LIMIT 30",array($term,$like,$like));
    wl_select2($rows,'profit_center_code',function($r){return wl_center_text($r->profit_center_code,$r->profit_center_name);});break;
  case 'get':
    $r=wl_row((int)wl_p('id'));if(!$r)wl_json('error','Work Location tidak ditemukan.');
    wl_json('good','',array('data'=>wl_payload($r)));break;
  case 'save':
    $id=(int)wl_p('id');$code=wl_c(wl_p('location_code'));$name=trim(wl_p('location_name'));$type=wl_c(wl_p('location_type','PLANT'));$cat=wl_c(wl_p('work_location_category','PRIMARY'));
    $org=wl_p('company_structure_id')!==''?(int)wl_p('company_structure_id'):null;$plant=wl_p('plant_id')!==''?(int)wl_p('plant_id'):null;$storage=wl_p('storage_location_id')!==''?(int)wl_p('storage_location_id'):null;
    $cost=trim(wl_p('cost_center_code'));$profit=trim(wl_p('profit_center_code'));$country=wl_c(wl_p('country','ID'));$validFrom=trim(wl_p('valid_from'));$validTo=trim(wl_p('valid_to','9999-12-31'));$status=wl_c(wl_p('status','DRAFT'));
    if($code===''||!preg_match('/^[A-Z0-9_-]{2,20}$/',$code))wl_json('error','Location Code wajib diisi dan hanya boleh huruf besar/angka/underscore/dash.');
    if($name==='')wl_json('error','Location Name wajib diisi.');
    if(!in_array($type,array('HEAD_OFFICE','BRANCH_OFFICE','PLANT','WAREHOUSE','SALES_OFFICE','REMOTE','FIELD','CUSTOMER_SITE','OTHER'),true))wl_json('error','Location Type tidak valid.');
    if(!in_array($cat,array('PRIMARY','SECONDARY','TEMPORARY','VIRTUAL'),true))wl_json('error','Work Location Category tidak valid.');
    if(!in_array($status,array('DRAFT','ACTIVE','INACTIVE'),true))wl_json('error','Status tidak valid.');
    if($country===''||strlen($country)>3)wl_json('error','Country wajib diisi maksimal 3 karakter.');
    if($validFrom===''||$validTo===''||strtotime($validTo)<strtotime($validFrom))wl_json('error','Validity date tidak valid.');
    if($org&&!$db->fetch("SELECT id FROM erp_company_structure WHERE id=? AND status='ACTIVE' LIMIT 1",array($org)))wl_json('error','Company Structure tidak valid.');
    if($plant&&!$db->fetch("SELECT id FROM erp_plant WHERE id=? AND status='Aktif' LIMIT 1",array($plant)))wl_json('error','Plant tidak valid.');
    if($storage&&!$db->fetch("SELECT id FROM erp_storage_location WHERE id=? AND status='Aktif' ".($plant?'AND plant_id='.(int)$plant:'')." LIMIT 1",array($storage)))wl_json('error','Storage Location tidak valid untuk plant yang dipilih.');
    if($cost!==''&&!$db->fetch("SELECT id FROM erp_cost_center WHERE cost_center_code=? AND status='Aktif' LIMIT 1",array($cost)))wl_json('error','Cost Center tidak valid.');
    if($profit!==''&&!$db->fetch("SELECT id FROM erp_profit_center WHERE profit_center_code=? AND status='Aktif' LIMIT 1",array($profit)))wl_json('error','Profit Center tidak valid.');
    $dup=$db->fetch("SELECT id FROM erp_work_location WHERE location_code=? AND id<>? LIMIT 1",array($code,$id));if($dup)wl_json('error','Location Code sudah digunakan.');
    $email=trim(wl_p('email'));if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))wl_json('error','Format email tidak valid.');
    $data=array('location_code'=>$code,'location_name'=>$name,'location_type'=>$type,'company_structure_id'=>$org,'plant_id'=>$plant,'storage_location_id'=>$storage,'cost_center_code'=>$cost,'profit_center_code'=>$profit,'country'=>$country,'province'=>trim(wl_p('province')),'city'=>trim(wl_p('city')),'district'=>trim(wl_p('district')),'postal_code'=>trim(wl_p('postal_code')),'address'=>trim(wl_p('address')),'latitude'=>trim(wl_p('latitude'))!==''?(float)wl_p('latitude'):null,'longitude'=>trim(wl_p('longitude'))!==''?(float)wl_p('longitude'):null,'timezone'=>trim(wl_p('timezone','Asia/Jakarta')),'work_location_category'=>$cat,'attendance_required'=>wl_c(wl_p('attendance_required','Y'))==='N'?'N':'Y','geo_fence_radius_meter'=>wl_p('geo_fence_radius_meter')!==''?(int)wl_p('geo_fence_radius_meter'):null,'capacity_headcount'=>max(0,(int)wl_p('capacity_headcount')),'working_calendar_code'=>trim(wl_p('working_calendar_code')),'default_shift_code'=>trim(wl_p('default_shift_code')),'contact_person'=>trim(wl_p('contact_person')),'phone'=>trim(wl_p('phone')),'email'=>$email,'valid_from'=>$validFrom,'valid_to'=>$validTo,'sap_reference'=>trim(wl_p('sap_reference')),'status'=>$status,'remarks'=>trim(wl_p('remarks')),'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s'));
    if($id>0){$old=$db->fetch("SELECT id FROM erp_work_location WHERE id=? LIMIT 1",array($id));if(!$old)wl_json('error','Work Location tidak ditemukan.');$ok=$db->update('erp_work_location',$data,'id',$id);}
    else{$data['created_by']=$username;$ok=$db->insert('erp_work_location',$data);$id=$db->last_insert_id();}
    if(!$ok)wl_json('error',$db->getErrorMessage()?:'Work Location gagal disimpan.');
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menyimpan Work Location '.$code.' pada '.date('Y-m-d H:i:s'),$username);
    wl_json('good','Work Location berhasil disimpan.',array('id'=>$id));break;
  case 'delete':
    $id=(int)wl_p('id');$r=$db->fetch("SELECT location_code,status FROM erp_work_location WHERE id=? LIMIT 1",array($id));if(!$r)wl_json('error','Work Location tidak ditemukan.');
    if($r->status==='ACTIVE')wl_json('error','Work Location ACTIVE tidak bisa dihapus. Ubah ke INACTIVE terlebih dahulu.');
    $db->delete('erp_work_location','id',$id);if($db->getErrorMessage())wl_json('error',$db->getErrorMessage());wl_json('good','Work Location berhasil dihapus.');break;
  case 'detail':
    $r=wl_row((int)wl_p('id'));if(!$r){echo '<div class="alert alert-warning">Work Location tidak ditemukan.</div>';break;}
    echo '<h3 style="margin-top:0">'.wl_h($r->location_code).' <small>'.wl_h($r->location_name).'</small></h3><span class="label label-info">'.wl_h($r->location_type).'</span> <span class="label label-success">'.wl_h($r->status).'</span><hr>';
    echo '<div class="row"><div class="col-sm-3"><b>Org Assignment</b><br>'.wl_h($r->structure_code?($r->structure_code.' - '.$r->structure_name):'-').'</div><div class="col-sm-3"><b>Plant</b><br>'.wl_h($r->plant_code?($r->plant_code.' - '.$r->plant_name):'-').'</div><div class="col-sm-3"><b>Storage Location</b><br>'.wl_h($r->storage_code?($r->storage_code.' - '.$r->storage_name):'-').'</div><div class="col-sm-3"><b>Validity</b><br>'.wl_h($r->valid_from.' s/d '.$r->valid_to).'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-4"><b>Address</b><br>'.wl_h($r->address?:'-').'<br><small>'.wl_h(trim($r->district.' '.$r->city.' '.$r->province.' '.$r->postal_code)).'</small></div><div class="col-sm-4"><b>'.hr_h('hr_attendance', 'Attendance').'</b><br>'.wl_h($r->attendance_required).' | Geo radius '.wl_h($r->geo_fence_radius_meter?:'-').' m<br><small>'.wl_h($r->latitude.', '.$r->longitude).'</small></div><div class="col-sm-4"><b>Contact</b><br>'.wl_h($r->contact_person?:'-').'<br><small>'.wl_h($r->phone?:'-').' / '.wl_h($r->email?:'-').'</small></div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><b>Cost Center</b><br>'.wl_h($r->cost_center_code?:'-').'<br><small>'.wl_h($r->cost_center_name?:'').'</small></div><div class="col-sm-3"><b>Profit Center</b><br>'.wl_h($r->profit_center_code?:'-').'<br><small>'.wl_h($r->profit_center_name?:'').'</small></div><div class="col-sm-3"><b>Calendar / Shift</b><br>'.wl_h(($r->working_calendar_code?:'-').' / '.($r->default_shift_code?:'-')).'</div><div class="col-sm-3"><b>Capacity</b><br>'.(int)$r->capacity_headcount.' HC</div></div><hr><b>'.hr_h('common_remarks', 'Remarks').'</b><p>'.nl2br(wl_h($r->remarks?:'-')).'</p>';break;
  case 'export':
    $initial=ob_get_level();ob_start();error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $from=wl_g('tgl_awal',date('Y-01-01'));$to=wl_g('tgl_akhir','9999-12-31');$type=wl_c(wl_g('location_type'));$status=wl_c(wl_g('status'));$city=trim(wl_g('city'));$kw=trim(wl_g('keyword'));
    $where=" WHERE wl.valid_from<=? AND wl.valid_to>=? ";$p=array($to,$from);if($type!==''){$where.=" AND wl.location_type=? ";$p[]=$type;}if($status!==''){$where.=" AND wl.status=? ";$p[]=$status;}if($city!==''){$where.=" AND wl.city LIKE ? ";$p[]='%'.$city.'%';}if($kw!==''){$like='%'.$kw.'%';$where.=" AND (wl.location_code LIKE ? OR wl.location_name LIKE ? OR wl.address LIKE ? OR wl.sap_reference LIKE ? OR p.plant_name LIKE ?) ";array_push($p,$like,$like,$like,$like,$like);}
    $rows=$db->query("SELECT wl.*,cs.structure_code,cs.structure_name,p.plant_code,p.plant_name,sl.storage_code,sl.storage_name,cc.cost_center_name,pc.profit_center_name FROM erp_work_location wl LEFT JOIN erp_company_structure cs ON cs.id=wl.company_structure_id LEFT JOIN erp_plant p ON p.id=wl.plant_id LEFT JOIN erp_storage_location sl ON sl.id=wl.storage_location_id LEFT JOIN erp_cost_center cc ON cc.cost_center_code=wl.cost_center_code LEFT JOIN erp_profit_center pc ON pc.profit_center_code=wl.profit_center_code $where ORDER BY wl.location_code",$p);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Work Location'));$heads=array(erp_export_label("No"),erp_export_label("Code"),erp_export_label("Name"),erp_export_label("Type"),erp_export_label("Category"),erp_export_label("Org Unit"),erp_export_label("Plant"),erp_export_label("Storage Location"),erp_export_label("Cost Center"),erp_export_label("Profit Center"),erp_export_label("Address"),erp_export_label("City"),erp_export_label("Province"),erp_export_label("Country"),erp_export_label("Timezone"),erp_export_label("Attendance"),erp_export_label("Geo Radius"),erp_export_label("Capacity"),erp_export_label("Calendar"),erp_export_label("Shift"),erp_export_label("Contact"),erp_export_label("Phone"),erp_export_label("Email"),erp_export_label("Validity"),erp_export_label("SAP Ref"),erp_export_label("Status"),erp_export_label("Updated By"),erp_export_label("Updated At"));
    foreach($heads as $i=>$h)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);
    $rnum=5;$n=1;foreach($rows as $r){$vals=array($n++,$r->location_code,$r->location_name,$r->location_type,$r->work_location_category,$r->structure_code.' - '.$r->structure_name,$r->plant_code.' - '.$r->plant_name,$r->storage_code.' - '.$r->storage_name,$r->cost_center_code.' - '.$r->cost_center_name,$r->profit_center_code.' - '.$r->profit_center_name,$r->address,$r->city,$r->province,$r->country,$r->timezone,$r->attendance_required,$r->geo_fence_radius_meter,(int)$r->capacity_headcount,$r->working_calendar_code,$r->default_shift_code,$r->contact_person,$r->phone,$r->email,$r->valid_from.' s/d '.$r->valid_to,$r->sap_reference,$r->status,$r->updated_by?:$r->created_by,$r->updated_at?:$r->created_at);foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rnum,$v);$rnum++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('WORK LOCATION MASTER REPORT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rnum-1),'column_count'=>count($heads),'numeric_columns'=>array('Q','R'),'filters'=>array('Validity'=>$from.' s/d '.$to,'Type'=>$type?:erp_export_all_text(),'Status'=>$status?:erp_export_all_text(),'City'=>$city?:erp_export_all_text())));
    $tmp=erpkb_excel_temp_file('work_location_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="work_location_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:wl_json('error','Action tidak dikenal.');
}
?>
