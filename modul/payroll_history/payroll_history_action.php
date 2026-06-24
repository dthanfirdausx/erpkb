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

function ph_json($s,$m='',$x=array()){header('Content-Type: application/json; charset=utf-8');$p=array('status'=>$s);if($m!=='')$p[$s==='good'?'message':'error_message']=$m;foreach($x as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function ph_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function ph_p($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function ph_g($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
function ph_c($v){return strtoupper(trim((string)$v));}
function ph_user(){return isset($_SESSION['username'])&&$_SESSION['username']!==''?$_SESSION['username']:'system';}
function ph_select2($rows,$id,$cb){$out=array();foreach($rows as $r)$out[]=array('id'=>$r->$id,'text'=>$cb($r));header('Content-Type: application/json; charset=utf-8');echo json_encode(array('results'=>$out));exit;}

function ph_row($id){
  global $db;
  return $db->fetch("SELECT ph.*,d.nm_dept FROM erp_payroll_history ph LEFT JOIN dept d ON d.kd_dept=ph.department_code WHERE ph.id=? LIMIT 1",array((int)$id));
}

function ph_rows($from,$to,$area,$historyStatus,$payslipStatus,$postingStatus,$emp,$kw){
  global $db;
  $w=" WHERE ph.period_from<=? AND ph.period_to>=? ";
  $p=array($to,$from);
  if($area!==''){$w.=" AND ph.payroll_area=? ";$p[]=$area;}
  if($historyStatus!==''){$w.=" AND ph.history_status=? ";$p[]=$historyStatus;}
  if($payslipStatus!==''){$w.=" AND ph.payslip_status=? ";$p[]=$payslipStatus;}
  if($postingStatus!==''){$w.=" AND ph.posting_status=? ";$p[]=$postingStatus;}
  if($emp!==''){$w.=" AND ph.employee_id=? ";$p[]=(int)$emp;}
  if($kw!==''){$like='%'.$kw.'%';$w.=" AND (ph.history_no LIKE ? OR ph.payroll_run_no LIKE ? OR ph.payslip_no LIKE ? OR ph.posting_no LIKE ? OR ph.employee_no LIKE ? OR ph.full_name LIKE ? OR ph.sap_reference LIKE ?) ";array_push($p,$like,$like,$like,$like,$like,$like,$like);}
  return $db->query("SELECT ph.*,d.nm_dept FROM erp_payroll_history ph LEFT JOIN dept d ON d.kd_dept=ph.department_code $w ORDER BY ph.period_year DESC,ph.period_month DESC,ph.employee_no",$p);
}

function ph_sync_detail($historyId,$payrollEmployeeId,$payslipId){
  global $db;
  $db->delete('erp_payroll_history_detail','payroll_history_id',(int)$historyId);
  if((int)$payslipId>0){
    $db->query("INSERT INTO erp_payroll_history_detail (payroll_history_id,line_no,component_code,component_name,wage_type_code,component_type,quantity,rate,amount,currency,taxable,sequence_no)
      SELECT ?,line_no,component_code,component_name,wage_type_code,component_type,quantity,rate,amount,currency,taxable,sequence_no
        FROM erp_payslip_detail WHERE payslip_id=? ORDER BY sequence_no,id",array((int)$historyId,(int)$payslipId));
  } else {
    $db->query("INSERT INTO erp_payroll_history_detail (payroll_history_id,line_no,component_code,component_name,wage_type_code,component_type,quantity,rate,amount,currency,taxable,sequence_no)
      SELECT ?,sequence_no,component_code,component_name,wage_type_code,component_type,quantity,rate,amount,currency,taxable,sequence_no
        FROM erp_payroll_process_result WHERE payroll_employee_id=? AND payslip_display='Y' ORDER BY sequence_no,id",array((int)$historyId,(int)$payrollEmployeeId));
  }
}

function ph_generate($payrollEmployeeId,$status,$source,$remarks){
  global $db;
  $pe=$db->fetch("SELECT pe.*,p.payroll_run_no,p.period_year,p.period_month,p.period_from,p.period_to,p.pay_date,p.currency
    FROM erp_payroll_process_employee pe
    JOIN erp_payroll_process p ON p.id=pe.payroll_process_id
    WHERE pe.id=? LIMIT 1",array((int)$payrollEmployeeId));
  if(!$pe)throw new Exception('Payroll result employee tidak ditemukan.');
  $ps=$db->fetch("SELECT * FROM erp_payslip WHERE payroll_process_id=? AND employee_id=? AND payslip_status<>'VOID' ORDER BY id DESC LIMIT 1",array($pe->payroll_process_id,$pe->employee_id));
  $pp=$db->fetch("SELECT * FROM erp_payroll_posting WHERE payroll_process_id=? ORDER BY id DESC LIMIT 1",array($pe->payroll_process_id));
  $historyNo='PH-'.$pe->period_year.'-'.str_pad($pe->period_month,2,'0',STR_PAD_LEFT).'-'.$pe->employee_no;
  $existing=$db->fetch("SELECT id,history_status FROM erp_payroll_history WHERE payroll_process_id=? AND employee_id=? LIMIT 1",array($pe->payroll_process_id,$pe->employee_id));
  if($existing&&$existing->history_status==='LOCKED')throw new Exception('Payroll history LOCKED tidak bisa digenerate ulang.');
  if(!in_array($status,array('ACTIVE','ARCHIVED','LOCKED','VOID'),true))$status='ACTIVE';
  if(!in_array($source,array('AUTO_SNAPSHOT','MANUAL_ADJUSTMENT','IMPORT'),true))$source='AUTO_SNAPSHOT';
  $journalNo=($pp&&$pp->journal_header_id)?'JRN-'.$pp->journal_header_id:null;
  $data=array(
    'history_no'=>$historyNo,'payroll_process_id'=>$pe->payroll_process_id,'payroll_employee_id'=>$pe->id,
    'payslip_id'=>$ps?$ps->id:null,'payroll_posting_id'=>$pp?$pp->id:null,'payroll_run_no'=>$pe->payroll_run_no,
    'payslip_no'=>$ps?$ps->payslip_no:null,'posting_no'=>$pp?$pp->posting_no:null,'employee_id'=>$pe->employee_id,
    'employee_no'=>$pe->employee_no,'full_name'=>$pe->full_name,'department_code'=>$pe->department_code,
    'employee_group'=>$pe->employee_group,'payroll_area'=>$pe->payroll_area,'period_year'=>$pe->period_year,
    'period_month'=>$pe->period_month,'period_from'=>$pe->period_from,'period_to'=>$pe->period_to,'pay_date'=>$pe->pay_date,
    'salary_structure_code'=>$pe->salary_structure_code,'working_days'=>$pe->working_days,'paid_days'=>$pe->paid_days,
    'absence_days'=>$pe->absence_days,'overtime_hours'=>$pe->overtime_hours,'gross_pay'=>$pe->gross_pay,
    'total_earning'=>$pe->total_earning,'total_deduction'=>$pe->total_deduction,'tax_amount'=>$pe->tax_amount,
    'net_pay'=>$pe->net_pay,'currency'=>$pe->currency,'payroll_process_status'=>$pe->process_status,
    'payslip_status'=>$ps?$ps->payslip_status:'NOT_GENERATED','posting_status'=>$pp?$pp->posting_status:'NOT_CREATED',
    'history_status'=>$status,'audit_source'=>$source,'release_channel'=>$ps?$ps->release_channel:null,
    'released_at'=>$ps?$ps->released_at:null,'journal_no'=>$journalNo,
    'sap_reference'=>'SAP-HCM-HIST-'.$pe->period_year.'-'.str_pad($pe->period_month,2,'0',STR_PAD_LEFT).'-'.$pe->employee_no,
    'remarks'=>$remarks,'updated_by'=>ph_user(),'updated_at'=>date('Y-m-d H:i:s')
  );
  if($existing){$id=(int)$existing->id;$ok=$db->update('erp_payroll_history',$data,'id',$id);}else{$data['created_by']=ph_user();$ok=$db->insert('erp_payroll_history',$data);$id=(int)$db->last_insert_id();}
  if(!$ok)throw new Exception($db->getErrorMessage()?:'Payroll history gagal disimpan.');
  ph_sync_detail($id,$pe->id,$ps?$ps->id:0);
  return $id;
}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=ph_user();
$historyStatuses=array('ACTIVE','ARCHIVED','LOCKED','VOID');
$auditSources=array('AUTO_SNAPSHOT','MANUAL_ADJUSTMENT','IMPORT');

switch($act){
case 'employee_search':
  $t=trim(ph_p('term'));$like='%'.$t.'%';
  $rows=$db->query("SELECT id,employee_no,full_name,employee_group,payroll_area FROM erp_employee_master WHERE employment_status IN ('ACTIVE','PROBATION','CONTRACT') AND (?='' OR employee_no LIKE ? OR full_name LIKE ?) ORDER BY employee_no LIMIT 40",array($t,$like,$like));
  ph_select2($rows,'id',function($r){return $r->employee_no.' - '.$r->full_name.' ['.$r->employee_group.' / '.$r->payroll_area.']';});
case 'payroll_employee_search':
  $t=trim(ph_p('term'));$like='%'.$t.'%';
  $rows=$db->query("SELECT pe.id,pe.employee_no,pe.full_name,p.payroll_run_no,p.period_year,p.period_month,pe.net_pay,COALESCE(ph.history_status,'NEW') hist
    FROM erp_payroll_process_employee pe
    JOIN erp_payroll_process p ON p.id=pe.payroll_process_id
    LEFT JOIN erp_payroll_history ph ON ph.payroll_process_id=pe.payroll_process_id AND ph.employee_id=pe.employee_id
    WHERE (ph.id IS NULL OR ph.history_status<>'LOCKED')
      AND (?='' OR pe.employee_no LIKE ? OR pe.full_name LIKE ? OR p.payroll_run_no LIKE ?)
    ORDER BY p.period_year DESC,p.period_month DESC,pe.employee_no LIMIT 50",array($t,$like,$like,$like));
  ph_select2($rows,'id',function($r){return $r->payroll_run_no.' - '.$r->employee_no.' '.$r->full_name.' [Net '.number_format((float)$r->net_pay,0).' / '.$r->hist.']';});
case 'get':
  $r=ph_row((int)ph_p('id'));if(!$r)ph_json('error','Payroll history tidak ditemukan.');ph_json('good','',array('data'=>(array)$r));
case 'save':
  try{$id=ph_generate((int)ph_p('payroll_employee_id'),ph_c(ph_p('history_status','ACTIVE')),ph_c(ph_p('audit_source','AUTO_SNAPSHOT')),trim(ph_p('remarks')));if(function_exists('simpan_log'))simpan_log('User '.$username.' membuat snapshot Payroll History ID '.$id.' pada '.date('Y-m-d H:i:s'),$username);ph_json('good','Payroll history berhasil disimpan.',array('id'=>$id));}catch(Exception $e){ph_json('error',$e->getMessage());}
case 'update':
  $id=(int)ph_p('id');$r=ph_row($id);if(!$r)ph_json('error','Payroll history tidak ditemukan.');if($r->history_status==='LOCKED')ph_json('error','Payroll history LOCKED tidak bisa diedit.');
  $status=ph_c(ph_p('history_status','ACTIVE'));$source=ph_c(ph_p('audit_source','AUTO_SNAPSHOT'));
  if(!in_array($status,$historyStatuses,true))ph_json('error','Status history tidak valid.');
  if(!in_array($source,$auditSources,true))ph_json('error','Audit source tidak valid.');
  $ok=$db->update('erp_payroll_history',array('history_status'=>$status,'audit_source'=>$source,'remarks'=>trim(ph_p('remarks')),'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')),'id',$id);
  if(!$ok)ph_json('error',$db->getErrorMessage()?:'Payroll history gagal diupdate.');
  ph_json('good','Payroll history berhasil diupdate.');
case 'status':
  $id=(int)ph_p('id');$status=ph_c(ph_p('status'));if(!in_array($status,$historyStatuses,true))ph_json('error','Status tidak valid.');
  $r=ph_row($id);if(!$r)ph_json('error','Payroll history tidak ditemukan.');
  $db->update('erp_payroll_history',array('history_status'=>$status,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')),'id',$id);
  ph_json('good','Status payroll history berhasil diubah.');
case 'delete':
  $id=(int)ph_p('id');$r=ph_row($id);if(!$r)ph_json('error','Payroll history tidak ditemukan.');if($r->history_status==='LOCKED')ph_json('error','Payroll history LOCKED tidak bisa dihapus.');
  $db->delete('erp_payroll_history','id',$id);if($db->getErrorMessage())ph_json('error',$db->getErrorMessage());ph_json('good','Payroll history berhasil dihapus.');
case 'detail':
  $r=ph_row((int)ph_p('id'));if(!$r){echo '<div class="alert alert-warning">Payroll history tidak ditemukan.</div>';break;}
  $lines=$db->query("SELECT * FROM erp_payroll_history_detail WHERE payroll_history_id=? ORDER BY sequence_no,id",array((int)$r->id));
  echo '<div class="ph-detail"><div class="row"><div class="col-sm-7"><h3 style="margin-top:0">'.ph_h($r->history_no).'</h3><p><b>'.ph_h($r->employee_no.' - '.$r->full_name).'</b><br>'.ph_h($r->department_code.' - '.$r->nm_dept).' / '.ph_h($r->payroll_area).'</p></div><div class="col-sm-5 text-right"><h4>'.ph_h($r->period_from.' s/d '.$r->period_to).'</h4><span class="label label-primary">'.ph_h($r->history_status).'</span> <span class="label label-info">'.ph_h($r->audit_source).'</span></div></div><hr><div class="row"><div class="col-sm-3"><b>Payroll Run</b><br>'.ph_h($r->payroll_run_no).'</div><div class="col-sm-3"><b>'.hr_h('hr_payslip', 'Payslip').'</b><br>'.ph_h($r->payslip_no?:'-').' / '.ph_h($r->payslip_status).'</div><div class="col-sm-3"><b>Posting</b><br>'.ph_h($r->posting_no?:'-').' / '.ph_h($r->posting_status).'</div><div class="col-sm-3"><b>Journal</b><br>'.ph_h($r->journal_no?:'-').'</div></div><hr><div class="row"><div class="col-sm-3"><b>Gross</b><br>'.number_format((float)$r->gross_pay,2).'</div><div class="col-sm-3"><b>Earning</b><br>'.number_format((float)$r->total_earning,2).'</div><div class="col-sm-3"><b>Deduction + Tax</b><br>'.number_format(((float)$r->total_deduction+(float)$r->tax_amount),2).'</div><div class="col-sm-3"><b>'.hr_h('hr_net_pay', 'Net Pay').'</b><br><b>'.number_format((float)$r->net_pay,2).'</b></div></div><hr><h4>Wage Type Snapshot</h4><table class="table table-bordered table-condensed"><thead><tr><th>'.hr_h('common_no', 'No').'</th><th>Component</th><th>Type</th><th>Qty</th><th>Rate</th><th>Amount</th><th>Taxable</th></tr></thead><tbody>';
  $n=1;foreach($lines as $l){echo '<tr><td>'.$n++.'</td><td><b>'.ph_h($l->component_code).'</b><br><small>'.ph_h($l->component_name.' / '.$l->wage_type_code).'</small></td><td>'.ph_h($l->component_type).'</td><td class="text-right">'.number_format((float)$l->quantity,4).'</td><td class="text-right">'.number_format((float)$l->rate,4).'</td><td class="text-right">'.number_format((float)$l->amount,2).'</td><td>'.ph_h($l->taxable).'</td></tr>';}
  echo '</tbody></table><hr><p><b>SAP Reference:</b> '.ph_h($r->sap_reference).'<br><b>Remarks:</b> '.ph_h($r->remarks).'</p></div>';break;
case 'export':
  $initial=ob_get_level();ob_start();error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $from=ph_g('tgl_awal',date('Y-m-01'));$to=ph_g('tgl_akhir',date('Y-m-t'));
  $rows=ph_rows($from,$to,ph_g('payroll_area',''),ph_g('history_status',''),ph_g('payslip_status',''),ph_g('posting_status',''),ph_g('employee_id',''),ph_g('keyword',''));
  $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Payroll History'));
  $heads=array(erp_export_label("No"),erp_export_label("History No"),erp_export_label("Payroll Run"),erp_export_label("Employee No"),erp_export_label("Employee"),erp_export_label("Department"),erp_export_label("Area"),erp_export_label("Period"),erp_export_label("Pay Date"),erp_export_label("Gross"),erp_export_label("Earning"),erp_export_label("Deduction"),erp_export_label("Tax"),erp_export_label("Net"),erp_export_label("History Status"),erp_export_label("Payslip Status"),erp_export_label("Posting Status"),erp_export_label("Payslip No"),erp_export_label("Posting No"),erp_export_label("Journal"),erp_export_label("Source"),erp_export_label("Updated By"));
  foreach($heads as $i=>$h)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);
  $rn=5;$n=1;foreach($rows as $r){$vals=array($n++,$r->history_no,$r->payroll_run_no,$r->employee_no,$r->full_name,$r->department_code.' - '.$r->nm_dept,$r->payroll_area,$r->period_from.' s/d '.$r->period_to,$r->pay_date,$r->gross_pay,$r->total_earning,$r->total_deduction,$r->tax_amount,$r->net_pay,$r->history_status,$r->payslip_status,$r->posting_status,$r->payslip_no,$r->posting_no,$r->journal_no,$r->audit_source,$r->updated_by?:$r->created_by);foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn,$v);$rn++;}
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('PAYROLL HISTORY REPORT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rn-1),'column_count'=>count($heads),'decimal_columns'=>array('J','K','L','M','N'),'filters'=>array('Period'=>$from.' s/d '.$to,'Area'=>ph_g('payroll_area',erp_export_all_text()),'History Status'=>ph_g('history_status',erp_export_all_text()),'Payslip'=>ph_g('payslip_status',erp_export_all_text()),'Posting'=>ph_g('posting_status',erp_export_all_text())),'widths'=>array('B'=>20,'C'=>18,'E'=>24,'F'=>24,'H'=>24,'O'=>16,'P'=>16,'Q'=>16,'R'=>20,'S'=>18,'T'=>16,'U'=>18)));
  $tmp=erpkb_excel_temp_file('payroll_history_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
  while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="payroll_history_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
default:ph_json('error','Action tidak dikenal.');
}
?>
