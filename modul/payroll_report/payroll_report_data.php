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

function prd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function prd_label($s){
  $map=array('ACTIVE'=>'success','ARCHIVED'=>'default','LOCKED'=>'primary','VOID'=>'danger','RELEASED'=>'success','GENERATED'=>'info','DRAFT'=>'default','READY'=>'warning','POSTED'=>'success','REVERSED'=>'danger','CANCELLED'=>'danger','NOT_GENERATED'=>'default','NOT_CREATED'=>'default','CALCULATED'=>'info','APPROVED'=>'success');
  $c=isset($map[$s])?$map[$s]:'default';
  return '<span class="label label-'.$c.'">'.prd_h($s?:'-').'</span>';
}
function prd_filters(){
  return array(
    'from'=>isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-m-01'),
    'to'=>isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-m-t'),
    'period_year'=>isset($_POST['period_year'])?trim($_POST['period_year']):'',
    'period_month'=>isset($_POST['period_month'])?trim($_POST['period_month']):'',
    'employee_id'=>isset($_POST['employee_id'])?trim($_POST['employee_id']):'',
    'department_code'=>isset($_POST['department_code'])?trim($_POST['department_code']):'',
    'payroll_area'=>isset($_POST['payroll_area'])?trim($_POST['payroll_area']):'',
    'payroll_process_status'=>isset($_POST['payroll_process_status'])?trim($_POST['payroll_process_status']):'',
    'payslip_status'=>isset($_POST['payslip_status'])?trim($_POST['payslip_status']):'',
    'posting_status'=>isset($_POST['posting_status'])?trim($_POST['posting_status']):'',
    'history_status'=>isset($_POST['history_status'])?trim($_POST['history_status']):'',
    'impact_type'=>isset($_POST['impact_type'])?trim($_POST['impact_type']):'',
    'keyword'=>isset($_POST['keyword'])?trim($_POST['keyword']):''
  );
}
function prd_where($f,&$p){
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
  if($f['keyword']!==''){
    $kw='%'.$f['keyword'].'%';
    $w.=" AND (ph.history_no LIKE ? OR ph.payroll_run_no LIKE ? OR ph.payslip_no LIKE ? OR ph.posting_no LIKE ? OR ph.employee_no LIKE ? OR ph.full_name LIKE ? OR ph.sap_reference LIKE ? OR d.nm_dept LIKE ?) ";
    for($i=0;$i<8;$i++)$p[]=$kw;
  }
  return $w;
}

$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;
$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;
$length=isset($_POST['length'])?(int)$_POST['length']:25;
if($length<=0||$length>500)$length=25;
$f=prd_filters();$p=array();$w=prd_where($f,$p);
$base=" FROM erp_payroll_history ph LEFT JOIN dept d ON d.kd_dept=ph.department_code LEFT JOIN erp_payroll_process pp ON pp.id=ph.payroll_process_id LEFT JOIN erp_payslip ps ON ps.id=ph.payslip_id LEFT JOIN erp_payroll_posting ppost ON ppost.id=ph.payroll_posting_id ";
$kpi=$db->fetch("SELECT COUNT(*) total,COUNT(DISTINCT ph.employee_id) employee_count,ROUND(SUM(ph.gross_pay),2) gross,ROUND(SUM(ph.total_deduction),2) deduction,ROUND(SUM(ph.tax_amount),2) tax,ROUND(SUM(ph.net_pay),2) net,SUM(ph.payslip_status='RELEASED') released_slip,SUM(ph.posting_status='POSTED') posted_count,SUM(ph.posting_status IN ('DRAFT','READY','NOT_CREATED') OR ph.posting_status IS NULL) posting_pending $base $w",$p);
$cnt=$db->fetch("SELECT COUNT(*) jml $base $w",$p);$total=$cnt?(int)$cnt->jml:0;
$orderMap=array(2=>'ph.payroll_run_no',3=>'ph.period_from',4=>'ph.employee_no',5=>'ph.department_code',6=>'ph.payroll_area',7=>'ph.working_days',8=>'ph.gross_pay',9=>'ph.total_deduction',10=>'ph.tax_amount',11=>'ph.net_pay',12=>'ph.payslip_status',13=>'ph.posting_status',14=>'ph.updated_at');
$orderBy='ph.period_year DESC,ph.period_month DESC,ph.employee_no';
if(isset($_POST['order'][0]['column'])){$col=(int)$_POST['order'][0]['column'];$dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;}
$rows=$db->query("SELECT ph.*,d.nm_dept,pp.process_type,pp.run_mode,pp.control_record_status,ppost.posting_date,ppost.journal_header_id $base $w ORDER BY $orderBy LIMIT $start,$length",$p);
$data=array();$no=$start+1;
foreach($rows as $r){
  $period=prd_h($r->period_from.' s/d '.$r->period_to).'<br><small>Pay date '.prd_h($r->pay_date?:'-').'</small>';
  $employee='<strong>'.prd_h($r->employee_no).'</strong><br><small>'.prd_h($r->full_name).'</small>';
  $dept='<strong>'.prd_h($r->department_code?:'-').'</strong><br><small>'.prd_h($r->nm_dept?:'-').'</small>';
  $area='<strong>'.prd_h($r->payroll_area).'</strong><br><small>'.prd_h(($r->salary_structure_code?:'-').' / '.($r->employee_group?:'-')).'</small>';
  $att='Work '.number_format((float)$r->working_days,2).'<br><small>Paid '.number_format((float)$r->paid_days,2).' | Abs '.number_format((float)$r->absence_days,2).' | OT '.number_format((float)$r->overtime_hours,2).'</small>';
  $run='<strong>'.prd_h($r->payroll_run_no).'</strong><br><small>'.prd_h(($r->process_type?:'-').' / '.($r->run_mode?:'-')).'</small>';
  $status=prd_label($r->history_status).'<br><small>'.prd_h($r->payroll_process_status?:'-').'</small>';
  $data[]=array(
    $no++,
    '<button class="btn btn-info btn-xs btn-pr-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button>',
    $run,
    $period,
    $employee,
    $dept,
    $area,
    $att,
    number_format((float)$r->gross_pay,2),
    number_format((float)$r->total_deduction,2),
    number_format((float)$r->tax_amount,2),
    '<strong>'.number_format((float)$r->net_pay,2).'</strong>',
    prd_label($r->payslip_status).'<br><small>'.prd_h($r->payslip_no?:'-').'</small>',
    prd_label($r->posting_status).'<br><small>'.prd_h($r->posting_no?:'-').'</small>',
    $status.'<br><small>'.prd_h($r->updated_by?:$r->created_by?:'-').'</small>'
  );
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'kpi'=>array('total'=>$kpi?(int)$kpi->total:0,'employees'=>$kpi?(int)$kpi->employee_count:0,'gross'=>$kpi?(float)$kpi->gross:0,'deduction'=>$kpi?(float)$kpi->deduction:0,'tax'=>$kpi?(float)$kpi->tax:0,'net'=>$kpi?(float)$kpi->net:0,'released_slip'=>$kpi?(int)$kpi->released_slip:0,'posted'=>$kpi?(int)$kpi->posted_count:0,'posting_pending'=>$kpi?(int)$kpi->posting_pending:0)));
?>
