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

function pr_json($s,$m='',$x=array()){header('Content-Type: application/json; charset=utf-8');$p=array('status'=>$s);if($m!=='')$p[$s==='good'?'message':'error_message']=$m;foreach($x as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function pr_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function pr_p($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function pr_g($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
function pr_select2($rows,$id,$cb){$out=array();foreach($rows as $r)$out[]=array('id'=>$r->$id,'text'=>$cb($r));header('Content-Type: application/json; charset=utf-8');echo json_encode(array('results'=>$out));exit;}
function pr_filters($src){
  return array(
    'from'=>isset($src['tgl_awal'])&&$src['tgl_awal']!==''?$src['tgl_awal']:date('Y-m-01'),
    'to'=>isset($src['tgl_akhir'])&&$src['tgl_akhir']!==''?$src['tgl_akhir']:date('Y-m-t'),
    'period_year'=>isset($src['period_year'])?trim($src['period_year']):'',
    'period_month'=>isset($src['period_month'])?trim($src['period_month']):'',
    'employee_id'=>isset($src['employee_id'])?trim($src['employee_id']):'',
    'department_code'=>isset($src['department_code'])?trim($src['department_code']):'',
    'payroll_area'=>isset($src['payroll_area'])?trim($src['payroll_area']):'',
    'payroll_process_status'=>isset($src['payroll_process_status'])?trim($src['payroll_process_status']):'',
    'payslip_status'=>isset($src['payslip_status'])?trim($src['payslip_status']):'',
    'posting_status'=>isset($src['posting_status'])?trim($src['posting_status']):'',
    'history_status'=>isset($src['history_status'])?trim($src['history_status']):'',
    'impact_type'=>isset($src['impact_type'])?trim($src['impact_type']):'',
    'keyword'=>isset($src['keyword'])?trim($src['keyword']):''
  );
}
function pr_where($f,&$p){
  $w=" WHERE ph.period_from<=? AND ph.period_to>=? ";
  array_push($p,$f['to'],$f['from']);
  if($f['period_year']!==''){$w.=" AND ph.period_year=? ";$p[]=(int)$f['period_year'];}
  if($f['period_month']!==''){$w.=" AND ph.period_month=? ";$p[]=(int)$f['period_month'];}
  if($f['employee_id']!==''){$w.=" AND ph.employee_id=? ";$p[]=(int)$f['employee_id'];}
  foreach(array('department_code','payroll_area','payroll_process_status','payslip_status','posting_status','history_status') as $k){if($f[$k]!==''){$w.=" AND ph.$k=? ";$p[]=$f[$k];}}
  if($f['impact_type']==='NET_PAY')$w.=" AND ph.net_pay>0 ";
  elseif($f['impact_type']==='PAYSLIP_NOT_RELEASED')$w.=" AND ph.payslip_status<>'RELEASED' ";
  elseif($f['impact_type']==='POSTING_PENDING')$w.=" AND (ph.posting_status IS NULL OR ph.posting_status IN ('DRAFT','READY','NOT_CREATED','')) ";
  elseif($f['impact_type']==='POSTED')$w.=" AND ph.posting_status='POSTED' ";
  elseif($f['impact_type']==='EXCEPTION')$w.=" AND (ph.history_status='VOID' OR ph.payroll_process_status='CANCELLED' OR ph.posting_status IN ('REVERSED','CANCELLED')) ";
  if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (ph.history_no LIKE ? OR ph.payroll_run_no LIKE ? OR ph.payslip_no LIKE ? OR ph.posting_no LIKE ? OR ph.employee_no LIKE ? OR ph.full_name LIKE ? OR ph.sap_reference LIKE ? OR d.nm_dept LIKE ?) ";for($i=0;$i<8;$i++)$p[]=$kw;}
  return $w;
}
function pr_label($s){
  $m=array('ACTIVE'=>'success','ARCHIVED'=>'default','LOCKED'=>'primary','VOID'=>'danger','RELEASED'=>'success','GENERATED'=>'info','DRAFT'=>'default','READY'=>'warning','POSTED'=>'success','REVERSED'=>'danger','CANCELLED'=>'danger','NOT_GENERATED'=>'default','NOT_CREATED'=>'default','CALCULATED'=>'info','APPROVED'=>'success');
  $c=isset($m[$s])?$m[$s]:'default';
  return '<span class="label label-'.$c.'">'.pr_h($s?:'-').'</span>';
}

$act=isset($_GET['act'])?$_GET['act']:'';
switch($act){
case 'employee_search':
  $t=trim(pr_p('term'));$like='%'.$t.'%';
  $rows=$db->query("SELECT id,employee_no,full_name,department_code,employee_group,payroll_area FROM erp_employee_master WHERE (?='' OR employee_no LIKE ? OR full_name LIKE ? OR department_code LIKE ?) ORDER BY employee_no LIMIT 50",array($t,$like,$like,$like));
  pr_select2($rows,'id',function($r){return $r->employee_no.' - '.$r->full_name.' ['.$r->department_code.' / '.$r->employee_group.' / '.$r->payroll_area.']';});
case 'department_search':
  $t=trim(pr_p('term'));$like='%'.$t.'%';
  $rows=$db->query("SELECT kd_dept,nm_dept FROM dept WHERE (?='' OR kd_dept LIKE ? OR nm_dept LIKE ?) ORDER BY kd_dept LIMIT 50",array($t,$like,$like));
  pr_select2($rows,'kd_dept',function($r){return $r->kd_dept.' - '.$r->nm_dept;});
case 'payroll_run_search':
  $t=trim(pr_p('term'));$like='%'.$t.'%';
  $rows=$db->query("SELECT DISTINCT payroll_run_no id,payroll_run_no,period_year,period_month,payroll_area FROM erp_payroll_history WHERE (?='' OR payroll_run_no LIKE ?) ORDER BY period_year DESC,period_month DESC,payroll_run_no LIMIT 50",array($t,$like));
  pr_select2($rows,'id',function($r){return $r->payroll_run_no.' ['.$r->period_year.'-'.$r->period_month.' / '.$r->payroll_area.']';});
case 'detail':
  $id=(int)pr_p('id');
  $r=$db->fetch("SELECT ph.*,d.nm_dept,pp.process_type,pp.run_mode,pp.control_record_status,pp.total_employee,ppost.posting_date,ppost.document_date,ppost.journal_header_id,ppost.posting_variant,ps.release_channel ps_release_channel
    FROM erp_payroll_history ph
    LEFT JOIN dept d ON d.kd_dept=ph.department_code
    LEFT JOIN erp_payroll_process pp ON pp.id=ph.payroll_process_id
    LEFT JOIN erp_payroll_posting ppost ON ppost.id=ph.payroll_posting_id
    LEFT JOIN erp_payslip ps ON ps.id=ph.payslip_id
    WHERE ph.id=? LIMIT 1",array($id));
  if(!$r){echo '<div class="alert alert-warning">Payroll report detail tidak ditemukan.</div>';break;}
  $lines=$db->query("SELECT * FROM erp_payroll_history_detail WHERE payroll_history_id=? ORDER BY sequence_no,id",array($id));
  echo '<div class="pr-detail"><div class="row"><div class="col-sm-8"><h3 style="margin-top:0">'.pr_h($r->history_no).'</h3><p><b>'.pr_h($r->employee_no.' - '.$r->full_name).'</b><br>'.pr_h($r->department_code.' - '.$r->nm_dept).' / '.pr_h($r->employee_group).' / '.pr_h($r->payroll_area).'</p></div><div class="col-sm-4 text-right"><h4>'.pr_h($r->period_from.' s/d '.$r->period_to).'</h4>'.pr_label($r->history_status).' '.pr_label($r->payroll_process_status).'</div></div><hr>';
  echo '<div class="row"><div class="col-sm-3"><b>Payroll Run</b><br>'.pr_h($r->payroll_run_no).'<br><small>'.pr_h(($r->process_type?:'-').' / '.($r->run_mode?:'-')).'</small></div><div class="col-sm-3"><b>'.hr_h('hr_payslip', 'Payslip').'</b><br>'.pr_h($r->payslip_no?:'-').'<br>'.pr_label($r->payslip_status).'</div><div class="col-sm-3"><b>Posting</b><br>'.pr_h($r->posting_no?:'-').'<br>'.pr_label($r->posting_status).'</div><div class="col-sm-3"><b>Journal</b><br>'.pr_h($r->journal_no?:($r->journal_header_id?'JRN-'.$r->journal_header_id:'-')).'</div></div><hr>';
  echo '<div class="row"><div class="col-sm-3"><b>Working / Paid Days</b><br>'.number_format((float)$r->working_days,2).' / '.number_format((float)$r->paid_days,2).'</div><div class="col-sm-3"><b>Absence / Overtime</b><br>'.number_format((float)$r->absence_days,2).' / '.number_format((float)$r->overtime_hours,2).'</div><div class="col-sm-3"><b>'.hr_h('hr_gross_pay', 'Gross Pay').'</b><br>'.number_format((float)$r->gross_pay,2).'</div><div class="col-sm-3"><b>'.hr_h('hr_net_pay', 'Net Pay').'</b><br><b>'.number_format((float)$r->net_pay,2).'</b></div></div><hr>';
  echo '<h4>Wage Type / Payroll Component</h4><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr><th>'.hr_h('common_no', 'No').'</th><th>Component</th><th>Wage Type</th><th>Type</th><th>Qty</th><th>Rate</th><th>Amount</th><th>Taxable</th></tr></thead><tbody>';
  $n=1;foreach($lines as $l){echo '<tr><td>'.$n++.'</td><td><b>'.pr_h($l->component_code).'</b><br><small>'.pr_h($l->component_name).'</small></td><td>'.pr_h($l->wage_type_code?:'-').'</td><td>'.pr_h($l->component_type).'</td><td class="text-right">'.number_format((float)$l->quantity,4).'</td><td class="text-right">'.number_format((float)$l->rate,4).'</td><td class="text-right"><b>'.number_format((float)$l->amount,2).'</b></td><td>'.pr_h($l->taxable).'</td></tr>';}
  if(!$lines)echo '<tr><td colspan="8" class="text-center text-muted">Belum ada detail komponen payroll.</td></tr>';
  echo '</tbody></table></div><hr><p><b>SAP Reference:</b> '.pr_h($r->sap_reference?:'-').'<br><b>Remarks:</b> '.pr_h($r->remarks?:'-').'<br><b>Updated:</b> '.pr_h(($r->updated_by?:$r->created_by?:'-').' / '.($r->updated_at?:$r->created_at?:'-')).'</p></div>';break;
case 'export':
  $initial=ob_get_level();ob_start();error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $f=pr_filters($_GET);$p=array();$w=pr_where($f,$p);
  $rows=$db->query("SELECT ph.*,d.nm_dept FROM erp_payroll_history ph LEFT JOIN dept d ON d.kd_dept=ph.department_code $w ORDER BY ph.period_year DESC,ph.period_month DESC,ph.employee_no",$p);
  $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Payroll Report'));
  $heads=array(erp_export_label("No"),erp_export_label("History No"),erp_export_label("Payroll Run"),erp_export_label("Period Year"),erp_export_label("Period Month"),erp_export_label("Period"),erp_export_label("Pay Date"),erp_export_label("Employee No"),erp_export_label("Employee"),erp_export_label("Department"),erp_export_label("Employee Group"),erp_export_label("Payroll Area"),erp_export_label("Salary Structure"),erp_export_label("Working Days"),erp_export_label("Paid Days"),erp_export_label("Absence Days"),erp_export_label("Overtime Hours"),erp_export_label("Gross Pay"),erp_export_label("Total Earning"),erp_export_label("Total Deduction"),erp_export_label("Tax"),erp_export_label("Net Pay"),erp_export_label("Currency"),erp_export_label("Process Status"),erp_export_label("Payslip Status"),erp_export_label("Posting Status"),erp_export_label("History Status"),erp_export_label("Payslip No"),erp_export_label("Posting No"),erp_export_label("Journal No"),erp_export_label("SAP Reference"),erp_export_label("Remarks"),erp_export_label("Updated By"));
  foreach($heads as $i=>$h)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);
  $rn=5;$n=1;foreach($rows as $r){$vals=array($n++,$r->history_no,$r->payroll_run_no,$r->period_year,$r->period_month,$r->period_from.' s/d '.$r->period_to,$r->pay_date,$r->employee_no,$r->full_name,$r->department_code.' - '.$r->nm_dept,$r->employee_group,$r->payroll_area,$r->salary_structure_code,$r->working_days,$r->paid_days,$r->absence_days,$r->overtime_hours,$r->gross_pay,$r->total_earning,$r->total_deduction,$r->tax_amount,$r->net_pay,$r->currency,$r->payroll_process_status,$r->payslip_status,$r->posting_status,$r->history_status,$r->payslip_no,$r->posting_no,$r->journal_no,$r->sap_reference,$r->remarks,$r->updated_by?:$r->created_by);foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn,$v);$rn++;}
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('PAYROLL REPORT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rn-1),'column_count'=>count($heads),'decimal_columns'=>array('N','O','P','Q','R','S','T','U','V'),'filters'=>array('Period'=>$f['from'].' s/d '.$f['to'],'Area'=>$f['payroll_area']!==''?$f['payroll_area']:erp_export_all_text(),'Department'=>$f['department_code']!==''?$f['department_code']:erp_export_all_text(),'Employee'=>$f['employee_id']!==''?$f['employee_id']:erp_export_all_text(),'Impact'=>$f['impact_type']!==''?$f['impact_type']:erp_export_all_text()),'widths'=>array('B'=>20,'C'=>18,'F'=>24,'I'=>24,'J'=>26,'M'=>18,'X'=>18,'Y'=>18,'Z'=>18,'AA'=>18,'AB'=>20,'AC'=>18,'AD'=>18,'AE'=>24,'AF'=>32,'AG'=>18)));
  $tmp=erpkb_excel_temp_file('payroll_report_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
  while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="payroll_report_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
default:pr_json('error','Action tidak dikenal.');
}
?>
