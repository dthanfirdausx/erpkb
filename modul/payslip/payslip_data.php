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
function psd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function psd_label($s){$map=array('DRAFT'=>'default','GENERATED'=>'info','RELEASED'=>'success','VOID'=>'danger');$c=isset($map[$s])?$map[$s]:'default';return '<span class="label label-'.$c.'">'.psd_h($s).'</span>';}
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$from=isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-m-01');$to=isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-m-t');$area=isset($_POST['payroll_area'])?trim($_POST['payroll_area']):'';$status=isset($_POST['payslip_status'])?trim($_POST['payslip_status']):'';$emp=isset($_POST['employee_id'])?trim($_POST['employee_id']):'';$kw=isset($_POST['keyword'])?trim($_POST['keyword']):'';
$w=" WHERE ps.period_from<=? AND ps.period_to>=? ";$p=array($to,$from);if($area!==''){$w.=" AND ps.payroll_area=? ";$p[]=$area;}if($status!==''){$w.=" AND ps.payslip_status=? ";$p[]=$status;}if($emp!==''){$w.=" AND ps.employee_id=? ";$p[]=(int)$emp;}if($kw!==''){$like='%'.$kw.'%';$w.=" AND (ps.payslip_no LIKE ? OR ps.payroll_run_no LIKE ? OR ps.employee_no LIKE ? OR ps.full_name LIKE ? OR ps.sap_reference LIKE ?) ";array_push($p,$like,$like,$like,$like,$like);}
$kpi=$db->fetch("SELECT COUNT(*) total,SUM(payslip_status='GENERATED') generated,SUM(payslip_status='RELEASED') released,SUM(payslip_status='VOID') voided,SUM(net_pay) net,SUM(gross_pay) gross FROM erp_payslip");
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_payslip ps $w",$p);$total=$cnt?(int)$cnt->jml:0;
$orderMap=array(2=>'ps.payslip_no',3=>'ps.payroll_run_no',4=>'ps.employee_no',5=>'ps.payroll_area',6=>'ps.net_pay',7=>'ps.payslip_status',8=>'ps.released_at',9=>'ps.updated_at');$orderBy='ps.period_year DESC,ps.period_month DESC,ps.employee_no';if(isset($_POST['order'][0]['column'])){$col=(int)$_POST['order'][0]['column'];$dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;}
$rows=$db->query("SELECT ps.*,d.nm_dept FROM erp_payslip ps LEFT JOIN dept d ON d.kd_dept=ps.department_code $w ORDER BY $orderBy LIMIT $start,$length",$p);
$data=array();$no=$start+1;foreach($rows as $r){$releaseDisabled=$r->payslip_status==='RELEASED'||$r->payslip_status==='VOID'?' disabled':'';$voidDisabled=$r->payslip_status==='VOID'?' disabled':'';$delDisabled=$r->payslip_status==='RELEASED'?' disabled':'';$act='<div class="ps-action"><button class="btn btn-info btn-xs btn-ps-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> <button class="btn btn-success btn-xs btn-ps-release" data-id="'.(int)$r->id.'" title="Release"'.$releaseDisabled.'><i class="fa fa-paper-plane"></i></button> <button class="btn btn-warning btn-xs btn-ps-void" data-id="'.(int)$r->id.'" title="Void"'.$voidDisabled.'><i class="fa fa-ban"></i></button> <button class="btn btn-danger btn-xs btn-ps-delete" data-id="'.(int)$r->id.'" data-no="'.psd_h($r->payslip_no).'" title="'.hr_h('common_delete', 'Delete').'"'.$delDisabled.'><i class="fa fa-trash"></i></button></div>';
  $data[]=array($no++,$act,'<strong>'.psd_h($r->payslip_no).'</strong><br><small>'.psd_h($r->sap_reference).'</small>','<strong>'.psd_h($r->payroll_run_no).'</strong><br><small>'.psd_h($r->period_from.' s/d '.$r->period_to).'</small>','<strong>'.psd_h($r->employee_no).'</strong><br><small>'.psd_h($r->full_name).'</small>','<strong>'.psd_h($r->payroll_area).'</strong><br><small>'.psd_h($r->department_code.' - '.$r->nm_dept).'</small>','Gross '.number_format((float)$r->gross_pay,0).'<br><small>Net <b>'.number_format((float)$r->net_pay,0).'</b></small>',psd_label($r->payslip_status),psd_h($r->release_channel).'<br><small>'.psd_h($r->released_at?:'-').'</small>',psd_h($r->updated_by?:$r->created_by?:'-').'<br><small>'.psd_h($r->updated_at?:$r->created_at).'</small>');}
header('Content-Type: application/json; charset=utf-8');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'kpi'=>array('total'=>$kpi?(int)$kpi->total:0,'generated'=>$kpi?(int)$kpi->generated:0,'released'=>$kpi?(int)$kpi->released:0,'voided'=>$kpi?(int)$kpi->voided:0,'net'=>$kpi?(float)$kpi->net:0,'gross'=>$kpi?(float)$kpi->gross:0)));
?>
