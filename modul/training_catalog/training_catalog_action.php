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

function tc_json($status,$message='',$extra=array()){
  header('Content-Type: application/json; charset=utf-8');
  $payload=array('status'=>$status);
  if($message!=='')$payload[$status==='good'?'message':'error_message']=$message;
  foreach($extra as $k=>$v)$payload[$k]=$v;
  echo json_encode($payload);
  exit;
}
function tc_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function tc_p($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function tc_g($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
function tc_code($v){return strtoupper(trim((string)$v));}
function tc_in($v,$arr){return in_array($v,$arr,true);}
function tc_select2($rows,$idField,$textCb){
  $results=array();
  foreach($rows as $r)$results[]=array('id'=>$r->$idField,'text'=>$textCb($r));
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('results'=>$results));
  exit;
}
function tc_row($id){
  global $db;
  return $db->fetch("SELECT tc.*,d.nm_dept,cc.cost_center_name
    FROM erp_training_catalog tc
    LEFT JOIN dept d ON d.kd_dept=tc.owner_department_code
    LEFT JOIN erp_cost_center cc ON cc.cost_center_code=tc.cost_center_code
    WHERE tc.id=? LIMIT 1",array((int)$id));
}
function tc_payload($r){
  $data=(array)$r;
  $data['department_text']=$r->owner_department_code?($r->owner_department_code.' - '.$r->nm_dept):'';
  $data['cost_center_text']=$r->cost_center_code?($r->cost_center_code.' - '.$r->cost_center_name):'';
  return $data;
}
function tc_filters_where($src,&$p){
  $from=isset($src['tgl_awal'])&&$src['tgl_awal']!==''?$src['tgl_awal']:date('Y-m-d');
  $to=isset($src['tgl_akhir'])&&$src['tgl_akhir']!==''?$src['tgl_akhir']:'9999-12-31';
  $w=" WHERE tc.valid_from<=? AND tc.valid_to>=? ";
  $p[]=$to;$p[]=$from;
  foreach(array('training_category','delivery_method','training_type','provider_type','owner_department_code','status') as $k){
    if(isset($src[$k])&&$src[$k]!==''){$w.=" AND tc.$k=? ";$p[]=trim($src[$k]);}
  }
  if(isset($src['keyword'])&&$src['keyword']!==''){
    $kw='%'.trim($src['keyword']).'%';
    $w.=" AND (tc.training_code LIKE ? OR tc.training_name LIKE ? OR tc.provider_name LIKE ? OR tc.target_audience LIKE ? OR tc.competency_area LIKE ? OR tc.sap_reference LIKE ? OR d.nm_dept LIKE ? OR cc.cost_center_name LIKE ?) ";
    for($i=0;$i<8;$i++)$p[]=$kw;
  }
  return $w;
}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=isset($_SESSION['username'])?$_SESSION['username']:'system';
$categories=array('TECHNICAL','QUALITY','SAFETY','COMPLIANCE','LEADERSHIP','SOFT_SKILL','ONBOARDING','CERTIFICATION','OTHER');
$methods=array('CLASSROOM','ONLINE','BLENDED','ON_THE_JOB','WORKSHOP','EXTERNAL');
$levels=array('BASIC','INTERMEDIATE','ADVANCED','EXPERT');
$types=array('MANDATORY','OPTIONAL','CERTIFICATION','REFRESHER');
$providerTypes=array('INTERNAL','EXTERNAL');
$yn=array('Y','N');
$statuses=array('DRAFT','ACTIVE','INACTIVE','OBSOLETE');

switch($act){
  case 'department_search':
    $term=trim(tc_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT kd_dept,nm_dept,dept_type FROM dept WHERE status='ACTIVE' AND (?='' OR kd_dept LIKE ? OR nm_dept LIKE ? OR dept_type LIKE ?) ORDER BY kd_dept LIMIT 50",array($term,$like,$like,$like));
    tc_select2($rows,'kd_dept',function($r){return $r->kd_dept.' - '.$r->nm_dept.' ['.$r->dept_type.']';});
    break;
  case 'cost_center_search':
    $term=trim(tc_p('term'));$like='%'.$term.'%';
    $rows=$db->query("SELECT cost_center_code,cost_center_name,department_code FROM erp_cost_center WHERE status='Aktif' AND (?='' OR cost_center_code LIKE ? OR cost_center_name LIKE ? OR department_code LIKE ?) ORDER BY cost_center_code LIMIT 50",array($term,$like,$like,$like));
    tc_select2($rows,'cost_center_code',function($r){return $r->cost_center_code.' - '.$r->cost_center_name;});
    break;
  case 'get':
    $r=tc_row((int)tc_p('id'));
    if(!$r)tc_json('error','Training Catalog tidak ditemukan.');
    tc_json('good','',array('data'=>tc_payload($r)));
    break;
  case 'save':
    $id=(int)tc_p('id');
    $code=tc_code(tc_p('training_code'));
    $name=trim(tc_p('training_name'));
    $category=tc_code(tc_p('training_category'));
    $method=tc_code(tc_p('delivery_method'));
    $level=tc_code(tc_p('training_level'));
    $type=tc_code(tc_p('training_type'));
    $providerType=tc_code(tc_p('provider_type'));
    $assessment=tc_code(tc_p('assessment_required','N'));
    $certificate=tc_code(tc_p('certificate_required','N'));
    $status=tc_code(tc_p('status','DRAFT'));
    $department=tc_code(tc_p('owner_department_code'));
    $costCenter=trim(tc_p('cost_center_code'));
    $duration=(float)tc_p('duration_hours',0);
    $passing=trim(tc_p('passing_score'));
    $validFrom=trim(tc_p('valid_from'));
    $validTo=trim(tc_p('valid_to','9999-12-31'));
    if($code==='')tc_json('error','Training Code wajib diisi.');
    if(!preg_match('/^[A-Z0-9_-]{3,30}$/',$code))tc_json('error','Training Code hanya boleh huruf besar, angka, underscore, atau dash.');
    if($name==='')tc_json('error','Training Name wajib diisi.');
    if(!tc_in($category,$categories))tc_json('error','Training Category tidak valid.');
    if(!tc_in($method,$methods))tc_json('error','Delivery Method tidak valid.');
    if(!tc_in($level,$levels))tc_json('error','Training Level tidak valid.');
    if(!tc_in($type,$types))tc_json('error','Training Type tidak valid.');
    if(!tc_in($providerType,$providerTypes))tc_json('error','Provider Type tidak valid.');
    if(!tc_in($assessment,$yn)||!tc_in($certificate,$yn))tc_json('error','Assessment/Certificate tidak valid.');
    if(!tc_in($status,$statuses))tc_json('error','Status tidak valid.');
    if($duration<=0)tc_json('error','Duration Hours wajib lebih dari 0.');
    if($assessment==='Y' && ($passing==='' || (float)$passing<=0))tc_json('error','Passing Score wajib diisi jika assessment required.');
    if($validFrom===''||$validTo==='')tc_json('error','Valid From dan Valid To wajib diisi.');
    if(strtotime($validTo)<strtotime($validFrom))tc_json('error','Valid To tidak boleh sebelum Valid From.');
    if($department!==''){
      $dept=$db->fetch("SELECT kd_dept FROM dept WHERE kd_dept=? AND status='ACTIVE' LIMIT 1",array($department));
      if(!$dept)tc_json('error','Owner Department tidak valid atau nonaktif.');
    }
    if($costCenter!==''){
      $cc=$db->fetch("SELECT id FROM erp_cost_center WHERE cost_center_code=? AND status='Aktif' LIMIT 1",array($costCenter));
      if(!$cc)tc_json('error','Cost Center tidak valid atau nonaktif.');
    }
    $dup=$db->fetch("SELECT id FROM erp_training_catalog WHERE training_code=? AND id<>? LIMIT 1",array($code,$id));
    if($dup)tc_json('error','Training Code sudah digunakan.');

    $data=array(
      'training_code'=>$code,
      'training_name'=>$name,
      'training_category'=>$category,
      'delivery_method'=>$method,
      'training_level'=>$level,
      'training_type'=>$type,
      'provider_type'=>$providerType,
      'provider_name'=>trim(tc_p('provider_name')),
      'duration_hours'=>$duration,
      'validity_months'=>max(0,(int)tc_p('validity_months',0)),
      'target_audience'=>trim(tc_p('target_audience')),
      'competency_area'=>trim(tc_p('competency_area')),
      'prerequisite'=>trim(tc_p('prerequisite')),
      'learning_objective'=>trim(tc_p('learning_objective')),
      'syllabus'=>trim(tc_p('syllabus')),
      'assessment_required'=>$assessment,
      'passing_score'=>$passing!==''?(float)$passing:null,
      'certificate_required'=>$certificate,
      'cost_estimate'=>max(0,(float)tc_p('cost_estimate',0)),
      'currency'=>tc_code(tc_p('currency','IDR')),
      'max_participant'=>max(0,(int)tc_p('max_participant',0)),
      'owner_department_code'=>$department!==''?$department:null,
      'cost_center_code'=>$costCenter!==''?$costCenter:null,
      'sap_reference'=>trim(tc_p('sap_reference')),
      'status'=>$status,
      'valid_from'=>$validFrom,
      'valid_to'=>$validTo,
      'remarks'=>trim(tc_p('remarks')),
      'updated_by'=>$username,
      'updated_at'=>date('Y-m-d H:i:s')
    );
    if($id>0){
      $old=$db->fetch("SELECT id FROM erp_training_catalog WHERE id=? LIMIT 1",array($id));
      if(!$old)tc_json('error','Training Catalog tidak ditemukan.');
      $ok=$db->update('erp_training_catalog',$data,'id',$id);
    }else{
      $data['created_by']=$username;
      $ok=$db->insert('erp_training_catalog',$data);
      $id=$db->last_insert_id();
    }
    if(!$ok)tc_json('error',$db->getErrorMessage()?:'Training Catalog gagal disimpan.');
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menyimpan Training Catalog '.$code.' - '.$name.' pada '.date('Y-m-d H:i:s'),$username);
    tc_json('good','Training Catalog berhasil disimpan.',array('id'=>$id));
    break;
  case 'delete':
    $id=(int)tc_p('id');
    $r=$db->fetch("SELECT * FROM erp_training_catalog WHERE id=? LIMIT 1",array($id));
    if(!$r)tc_json('error','Training Catalog tidak ditemukan.');
    if($r->status==='ACTIVE')tc_json('error','Training Catalog ACTIVE tidak boleh dihapus. Ubah ke INACTIVE/OBSOLETE terlebih dahulu.');
    $db->delete('erp_training_catalog','id',$id);
    if($db->getErrorMessage())tc_json('error',$db->getErrorMessage());
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menghapus Training Catalog '.$r->training_code,$username);
    tc_json('good','Training Catalog berhasil dihapus.');
    break;
  case 'status':
    $id=(int)tc_p('id');$status=tc_code(tc_p('status'));
    if(!tc_in($status,$statuses))tc_json('error','Status tidak valid.');
    $r=$db->fetch("SELECT * FROM erp_training_catalog WHERE id=? LIMIT 1",array($id));
    if(!$r)tc_json('error','Training Catalog tidak ditemukan.');
    $ok=$db->update('erp_training_catalog',array('status'=>$status,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')),'id',$id);
    if(!$ok)tc_json('error',$db->getErrorMessage()?:'Status gagal diubah.');
    if(function_exists('simpan_log'))simpan_log('User '.$username.' mengubah status Training Catalog '.$r->training_code.' menjadi '.$status,$username);
    tc_json('good','Status berhasil diubah.');
    break;
  case 'detail':
    $r=tc_row((int)tc_p('id'));
    if(!$r){echo '<div class="alert alert-warning">Training Catalog tidak ditemukan.</div>';break;}
    $class=$r->status==='ACTIVE'?'success':($r->status==='DRAFT'?'default':($r->status==='OBSOLETE'?'danger':'warning'));
    echo '<div class="row"><div class="col-md-8"><h3 style="margin-top:0">'.tc_h($r->training_code).' <small>'.tc_h($r->training_name).'</small></h3><span class="label label-'.$class.'">'.tc_h($r->status).'</span> <span class="label label-info">'.tc_h($r->training_category.' / '.$r->training_type).'</span> <span class="label label-primary">'.tc_h($r->delivery_method.' / '.$r->training_level).'</span></div><div class="col-md-4 text-right"><strong>SAP Ref</strong><br>'.tc_h($r->sap_reference?:'-').'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><strong>Provider</strong><br>'.tc_h($r->provider_type).' - '.tc_h($r->provider_name?:'-').'</div><div class="col-sm-3"><strong>Duration / Capacity</strong><br>'.number_format((float)$r->duration_hours,2).' jam / '.(int)$r->max_participant.' peserta</div><div class="col-sm-3"><strong>Owner Department</strong><br>'.tc_h($r->owner_department_code?($r->owner_department_code.' - '.$r->nm_dept):'-').'</div><div class="col-sm-3"><strong>Cost Center</strong><br>'.tc_h($r->cost_center_code?($r->cost_center_code.' - '.$r->cost_center_name):'-').'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><strong>Cost Estimate</strong><br>'.tc_h($r->currency).' '.number_format((float)$r->cost_estimate,2).'</div><div class="col-sm-3"><strong>Assessment</strong><br>'.tc_h($r->assessment_required).' | Passing: '.tc_h($r->passing_score!==null?$r->passing_score:'-').'</div><div class="col-sm-3"><strong>Certificate</strong><br>'.tc_h($r->certificate_required).' | Validity: '.(int)$r->validity_months.' bulan</div><div class="col-sm-3"><strong>Validity Catalog</strong><br>'.tc_h($r->valid_from.' s/d '.$r->valid_to).'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-4"><strong>Target Audience</strong><p>'.nl2br(tc_h($r->target_audience?:'-')).'</p><strong>Competency Area</strong><p>'.nl2br(tc_h($r->competency_area?:'-')).'</p></div><div class="col-sm-4"><strong>Learning Objective</strong><p>'.nl2br(tc_h($r->learning_objective?:'-')).'</p><strong>Prerequisite</strong><p>'.nl2br(tc_h($r->prerequisite?:'-')).'</p></div><div class="col-sm-4"><strong>Syllabus</strong><p>'.nl2br(tc_h($r->syllabus?:'-')).'</p><strong>'.hr_h('common_remarks', 'Remarks').'</strong><p>'.nl2br(tc_h($r->remarks?:'-')).'</p></div></div>';
    break;
  case 'export':
    $initial=ob_get_level();
    ob_start();
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require_once "../../inc/lib/PHPExcel.php";
    require_once "../../inc/excel_style_helper.php";
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $p=array();$w=tc_filters_where($_GET,$p);
    $rows=$db->query("SELECT tc.*,d.nm_dept,cc.cost_center_name
      FROM erp_training_catalog tc
      LEFT JOIN dept d ON d.kd_dept=tc.owner_department_code
      LEFT JOIN erp_cost_center cc ON cc.cost_center_code=tc.cost_center_code
      $w
      ORDER BY tc.training_category, tc.training_code",$p);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Training Catalog'));
    $heads=array(erp_export_label("No"),erp_export_label("Training Code"),erp_export_label("Training Name"),erp_export_label("Category"),erp_export_label("Method"),erp_export_label("Level"),erp_export_label("Type"),erp_export_label("Provider Type"),erp_export_label("Provider Name"),erp_export_label("Duration Hours"),erp_export_label("Certificate Validity Months"),erp_export_label("Target Audience"),erp_export_label("Competency Area"),erp_export_label("Prerequisite"),erp_export_label("Learning Objective"),erp_export_label("Syllabus"),erp_export_label("Assessment Required"),erp_export_label("Passing Score"),erp_export_label("Certificate Required"),erp_export_label("Cost Estimate"),erp_export_label("Currency"),erp_export_label("Max Participant"),erp_export_label("Owner Department"),erp_export_label("Cost Center"),erp_export_label("SAP Reference"),erp_export_label("Status"),erp_export_label("Valid From"),erp_export_label("Valid To"),erp_export_label("Remarks"),erp_export_label("Updated By"));
    foreach($heads as $i=>$h)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);
    $rn=5;$n=1;
    foreach($rows as $r){
      $vals=array($n++,$r->training_code,$r->training_name,$r->training_category,$r->delivery_method,$r->training_level,$r->training_type,$r->provider_type,$r->provider_name,$r->duration_hours,$r->validity_months,$r->target_audience,$r->competency_area,$r->prerequisite,$r->learning_objective,$r->syllabus,$r->assessment_required,$r->passing_score,$r->certificate_required,$r->cost_estimate,$r->currency,$r->max_participant,$r->owner_department_code.' - '.$r->nm_dept,$r->cost_center_code.' - '.$r->cost_center_name,$r->sap_reference,$r->status,$r->valid_from,$r->valid_to,$r->remarks,$r->updated_by?:$r->created_by);
      foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn,$v);
      $rn++;
    }
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('TRAINING CATALOG'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rn-1),'column_count'=>count($heads),'filters'=>array('Period'=>array(tc_g('tgl_awal',date('Y-m-d')),tc_g('tgl_akhir','9999-12-31')),'Category'=>tc_g('training_category',''),'Method'=>tc_g('delivery_method',''),'Type'=>tc_g('training_type',''),'Status'=>tc_g('status','')),'decimal_columns'=>array('J','R','T'),'money_columns'=>array('T'),'widths'=>array('B'=>18,'C'=>34,'D'=>18,'E'=>18,'I'=>24,'L'=>32,'M'=>24,'N'=>32,'O'=>40,'P'=>40,'W'=>28,'X'=>30,'Y'=>20,'AC'=>32)));
    $tmp=erpkb_excel_temp_file('training_catalog_');
    PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);
    $size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);
    if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
    while(ob_get_level()>$initial)ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="training_catalog_'.date('Ymd_His').'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp);
    @unlink($tmp);
    exit;
  default:
    tc_json('error','Action tidak dikenal.');
}
?>
