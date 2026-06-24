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

function mr_json($status,$message='',$extra=array()){header('Content-Type: application/json; charset=utf-8');$p=array('status'=>$status);if($message!=='')$p[$status==='good'?'message':'error_message']=$message;foreach($extra as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function mr_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function mr_p($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function mr_g($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
function mr_code($v){return strtoupper(trim((string)$v));}
function mr_valid_date($v){return preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)$v);}
function mr_user(){return isset($_SESSION['username'])&&$_SESSION['username']!==''?$_SESSION['username']:'employee';}
function mr_current_employee(){global $db;$uid=isset($_SESSION['id_user'])?(int)$_SESSION['id_user']:0;if($uid<=0)return null;return $db->fetch("SELECT e.*,u.username,d.nm_dept,j.job_title_code,j.job_title_name,m.employee_no manager_no,m.full_name manager_name FROM erp_employee_master e LEFT JOIN sys_users u ON u.id=e.user_id LEFT JOIN dept d ON d.kd_dept=e.department_code LEFT JOIN erp_job_title j ON j.id=e.job_title_id LEFT JOIN erp_employee_master m ON m.id=e.manager_employee_id WHERE e.user_id=? LIMIT 1",array($uid));}
function mr_next_no(){global $db;$prefix='ERQ'.date('Ym');$r=$db->fetch("SELECT request_no FROM erp_employee_request WHERE request_no LIKE ? ORDER BY request_no DESC LIMIT 1",array($prefix.'%'));$n=$r?((int)substr($r->request_no,-4)+1):1;return $prefix.sprintf('%04d',$n);}
function mr_history($id,$old,$new,$note,$user){global $db;$db->insert('erp_employee_request_history',array('request_id'=>(int)$id,'previous_status'=>$old,'new_status'=>$new,'action_note'=>$note,'action_by'=>$user,'action_at'=>date('Y-m-d H:i:s')));}
function mr_row($id,$employeeId){global $db;return $db->fetch("SELECT r.*,a.employee_no approver_no,a.full_name approver_name,hr.employee_no hr_no,hr.full_name hr_name FROM erp_employee_request r LEFT JOIN erp_employee_master a ON a.id=r.approver_employee_id LEFT JOIN erp_employee_master hr ON hr.id=r.hr_reviewer_employee_id WHERE r.id=? AND r.employee_id=? LIMIT 1",array((int)$id,(int)$employeeId));}

$employee=mr_current_employee();
if(!$employee)mr_json('error','Data employee untuk user aktif belum terhubung.');
$username=mr_user();
$act=isset($_GET['act'])?$_GET['act']:'';

switch($act){
  case 'get':
    $r=mr_row((int)mr_p('id'),(int)$employee->id);if(!$r)mr_json('error','Request tidak ditemukan.');
    mr_json('good','',array('data'=>(array)$r));break;
  case 'save':
    $id=(int)mr_p('id');$oldRow=$id>0?mr_row($id,(int)$employee->id):null;if($id>0&&!$oldRow)mr_json('error','Request tidak ditemukan.');
    if($oldRow&&!in_array($oldRow->workflow_status,array('DRAFT','RETURNED'),true))mr_json('error','Hanya DRAFT atau RETURNED yang bisa diedit dari ESS.');
    $cat=mr_code(mr_p('request_category','OTHER'));$priority=mr_code(mr_p('priority','NORMAL'));$submit=mr_p('submit_status')==='SUBMITTED';
    $validCat=array('EMPLOYEE_DATA','CERTIFICATE','CLAIM','BENEFIT','PAYROLL','ATTENDANCE_CORRECTION','DOCUMENT','FACILITY','OTHER');
    if(!in_array($cat,$validCat,true))mr_json('error','Kategori request tidak valid.');
    if(!in_array($priority,array('LOW','NORMAL','HIGH','URGENT'),true))mr_json('error','Priority tidak valid.');
    $requestDate=mr_p('request_date',date('Y-m-d'));$requiredDate=trim(mr_p('required_date'));
    if(!mr_valid_date($requestDate))mr_json('error','Request Date wajib valid.');
    if($requiredDate!==''&&!mr_valid_date($requiredDate))mr_json('error','Required Date tidak valid.');
    $type=trim(mr_p('request_type'));$subject=trim(mr_p('subject'));$desc=trim(mr_p('description'));
    if($type==='')mr_json('error','Request Type wajib diisi.');if($subject==='')mr_json('error','Subject wajib diisi.');if($submit&&$desc==='')mr_json('error','Description wajib diisi sebelum submit.');
    $status=$submit?'SUBMITTED':'DRAFT';$level=$submit?'MANAGER':'EMPLOYEE';
    $data=array('request_no'=>$oldRow?$oldRow->request_no:mr_next_no(),'employee_id'=>(int)$employee->id,'employee_no'=>$employee->employee_no,'department_code'=>$employee->department_code,'job_title_id'=>$employee->job_title_id,'request_date'=>$requestDate,'request_category'=>$cat,'request_type'=>$type,'priority'=>$priority,'required_date'=>$requiredDate?:null,'subject'=>$subject,'description'=>$desc,'attachment_ref'=>trim(mr_p('attachment_ref')),'approver_employee_id'=>$employee->manager_employee_id,'hr_reviewer_employee_id'=>null,'workflow_status'=>$status,'approval_level'=>$level,'decision'=>'PENDING','remarks'=>trim(mr_p('remarks')),'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s'));
    if($id>0){$ok=$db->update('erp_employee_request',$data,'id',$id);$oldStatus=$oldRow->workflow_status;}else{$data['created_by']=$username;$ok=$db->insert('erp_employee_request',$data);$id=$db->last_insert_id();$oldStatus=null;}
    if(!$ok||$db->getErrorMessage()!=='')mr_json('error',$db->getErrorMessage()?:'Request gagal disimpan.');
    mr_history($id,$oldStatus,$status,$submit?'Submit request':'Save draft',$username);
    simpan_log('User '.$username.' menyimpan employee request '.$data['request_no'].' pada '.date('Y-m-d H:i:s'),$username);
    mr_json('good','Request berhasil disimpan.',array('id'=>$id,'request_no'=>$data['request_no']));
  case 'cancel':
    $r=mr_row((int)mr_p('id'),(int)$employee->id);if(!$r)mr_json('error','Request tidak ditemukan.');
    if(in_array($r->workflow_status,array('APPROVED','REJECTED','CANCELLED','CLOSED'),true))mr_json('error','Status final tidak bisa dibatalkan.');
    $reason=trim(mr_p('reason'));$db->update('erp_employee_request',array('workflow_status'=>'CANCELLED','approval_level'=>'FINAL','decision'=>'CANCELLED','decision_by'=>$username,'decision_at'=>date('Y-m-d H:i:s'),'cancellation_reason'=>$reason,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')),'id',(int)$r->id);
    if($db->getErrorMessage()!=='')mr_json('error',$db->getErrorMessage());
    mr_history($r->id,$r->workflow_status,'CANCELLED',$reason,$username);simpan_log('User '.$username.' membatalkan employee request '.$r->request_no.' pada '.date('Y-m-d H:i:s'),$username);mr_json('good','Request berhasil dibatalkan.');
  case 'detail':
    $r=mr_row((int)mr_p('id'),(int)$employee->id);if(!$r){echo '<div class="alert alert-warning">Request tidak ditemukan.</div>';break;}
    $hist=$db->query("SELECT * FROM erp_employee_request_history WHERE request_id=? ORDER BY action_at,id",array((int)$r->id));
    echo '<h3 style="margin-top:0">'.mr_h($r->request_no).' <small>'.mr_h($r->workflow_status).'</small></h3><span class="label label-info">'.mr_h($r->request_category).'</span> <span class="label label-primary">'.mr_h($r->priority).'</span><hr><div class="row"><div class="col-sm-3"><b>Request Date</b><br>'.mr_h($r->request_date).'</div><div class="col-sm-3"><b>Required Date</b><br>'.mr_h($r->required_date?:'-').'</div><div class="col-sm-3"><b>Approver</b><br>'.mr_h($r->approver_no?($r->approver_no.' - '.$r->approver_name):'-').'</div><div class="col-sm-3"><b>Decision</b><br>'.mr_h($r->decision).'</div></div><hr><b>Subject</b><p>'.mr_h($r->subject).'</p><b>'.hr_h('hr_description', 'Description').'</b><p>'.nl2br(mr_h($r->description?:'-')).'</p><b>Resolution / Notes</b><p>'.nl2br(mr_h($r->resolution_note?:$r->manager_note?:$r->hr_note?:'-')).'</p><h4>History</h4><table class="table table-bordered table-condensed"><tr><th>From</th><th>To</th><th>By</th><th>At</th><th>Note</th></tr>';
    $n=0;foreach($hist as $h){$n++;echo '<tr><td>'.mr_h($h->previous_status?:'-').'</td><td>'.mr_h($h->new_status).'</td><td>'.mr_h($h->action_by).'</td><td>'.mr_h($h->action_at).'</td><td>'.mr_h($h->action_note).'</td></tr>';}if(!$n)echo '<tr><td colspan="5" class="text-muted text-center">Belum ada history.</td></tr>';echo '</table>';break;
  case 'export':
    $initial=ob_get_level();ob_start();error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $from=mr_g('tgl_awal',date('Y-01-01'));$to=mr_g('tgl_akhir',date('Y-m-d'));if(!mr_valid_date($from))$from=date('Y-01-01');if(!mr_valid_date($to))$to=date('Y-m-d');$cat=mr_code(mr_g('request_category'));$status=mr_code(mr_g('status'));$priority=mr_code(mr_g('priority'));
    $where=" WHERE employee_id=? AND request_date BETWEEN ? AND ? ";$p=array((int)$employee->id,$from,$to);if($cat!==''){$where.=" AND request_category=? ";$p[]=$cat;}if($status!==''){$where.=" AND workflow_status=? ";$p[]=$status;}if($priority!==''){$where.=" AND priority=? ";$p[]=$priority;}
    $rows=$db->query("SELECT * FROM erp_employee_request $where ORDER BY request_date DESC,request_no DESC",$p);
    $excel=new PHPExcel();$sh=$excel->setActiveSheetIndex(0);$sh->setTitle(erp_export_sheet_title('My Request'));$heads=array(erp_export_label("No"),erp_export_label("Request No"),erp_export_label("Date"),erp_export_label("Required Date"),erp_export_label("Category"),erp_export_label("Type"),erp_export_label("Priority"),erp_export_label("Subject"),erp_export_label("Status"),erp_export_label("Decision"),erp_export_label("Decision By"),erp_export_label("Decision At"),erp_export_label("Attachment"),erp_export_label("Description"),erp_export_label("Resolution"),erp_export_label("Updated By"));foreach($heads as $i=>$h)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);$rn=5;$n=1;foreach($rows as $r){$vals=array($n++,$r->request_no,$r->request_date,$r->required_date,$r->request_category,$r->request_type,$r->priority,$r->subject,$r->workflow_status,$r->decision,$r->decision_by,$r->decision_at,$r->attachment_ref,$r->description,$r->resolution_note,$r->updated_by?:$r->created_by);foreach($vals as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn,$v);$rn++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sh,'title'=>erp_export_title('MY REQUEST REPORT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rn-1),'column_count'=>count($heads),'filters'=>array('Employee'=>$employee->employee_no.' - '.$employee->full_name,'Period'=>$from.' s/d '.$to,'Category'=>$cat?:erp_export_all_text(),'Status'=>$status?:erp_export_all_text()),'widths'=>array('B'=>20,'E'=>22,'F'=>22,'H'=>36,'N'=>42,'O'=>42)));
    $tmp=erpkb_excel_temp_file('my_request_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="my_request_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:mr_json('error','Aksi tidak dikenal.');
}
