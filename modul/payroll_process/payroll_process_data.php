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
function ppd_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function ppd_label($s){$map=array('DRAFT'=>'default','CALCULATED'=>'info','APPROVED'=>'success','POSTED'=>'primary','CANCELLED'=>'danger');$c=isset($map[$s])?$map[$s]:'default';return '<span class="label label-'.$c.'">'.ppd_h($s).'</span>';}
$draw=isset($_POST['draw'])?(int)$_POST['draw']:1;$start=isset($_POST['start'])?max(0,(int)$_POST['start']):0;$length=isset($_POST['length'])?(int)$_POST['length']:25;if($length<=0||$length>500)$length=25;
$from=isset($_POST['tgl_awal'])&&$_POST['tgl_awal']!==''?$_POST['tgl_awal']:date('Y-m-01');$to=isset($_POST['tgl_akhir'])&&$_POST['tgl_akhir']!==''?$_POST['tgl_akhir']:date('Y-m-t');$area=isset($_POST['payroll_area'])?trim($_POST['payroll_area']):'';$type=isset($_POST['process_type'])?trim($_POST['process_type']):'';$status=isset($_POST['process_status'])?trim($_POST['process_status']):'';$mode=isset($_POST['run_mode'])?trim($_POST['run_mode']):'';$kw=isset($_POST['keyword'])?trim($_POST['keyword']):'';
$w=" WHERE p.period_from<=? AND p.period_to>=? ";$p=array($to,$from);if($area!==''){$w.=" AND p.payroll_area=? ";$p[]=$area;}if($type!==''){$w.=" AND p.process_type=? ";$p[]=$type;}if($status!==''){$w.=" AND p.process_status=? ";$p[]=$status;}if($mode!==''){$w.=" AND p.run_mode=? ";$p[]=$mode;}if($kw!==''){$like='%'.$kw.'%';$w.=" AND (p.payroll_run_no LIKE ? OR p.posting_reference LIKE ? OR p.sap_reference LIKE ? OR p.remarks LIKE ?) ";array_push($p,$like,$like,$like,$like);}
$kpi=$db->fetch("SELECT COUNT(*) total,SUM(process_status='CALCULATED') calculated,SUM(process_status='APPROVED') approved,SUM(process_status='POSTED') posted,SUM(total_employee) employees,SUM(total_net) net FROM erp_payroll_process");
$cnt=$db->fetch("SELECT COUNT(*) jml FROM erp_payroll_process p $w",$p);$total=$cnt?(int)$cnt->jml:0;
$orderMap=array(2=>'p.payroll_run_no',3=>'p.period_from',4=>'p.payroll_area',5=>'p.process_type',6=>'p.total_employee',7=>'p.total_net',8=>'p.process_status',9=>'p.updated_at');$orderBy='p.period_year DESC,p.period_month DESC,p.payroll_run_no';if(isset($_POST['order'][0]['column'])){$col=(int)$_POST['order'][0]['column'];$dir=(isset($_POST['order'][0]['dir'])&&strtolower($_POST['order'][0]['dir'])==='desc')?'DESC':'ASC';if(isset($orderMap[$col]))$orderBy=$orderMap[$col].' '.$dir;}
$rows=$db->query("SELECT p.* FROM erp_payroll_process p $w ORDER BY $orderBy LIMIT $start,$length",$p);
$data=array();$no=$start+1;foreach($rows as $r){$canDel=in_array($r->process_status,array('APPROVED','POSTED'),true)?' disabled':'';$next=$r->process_status==='CALCULATED'?'APPROVED':($r->process_status==='APPROVED'?'POSTED':'CALCULATED');$act='<div class="pp-action"><button class="btn btn-info btn-xs btn-pp-detail" data-id="'.(int)$r->id.'" title="'.hr_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> <button class="btn btn-primary btn-xs btn-pp-edit" data-id="'.(int)$r->id.'" title="'.hr_h('common_edit', 'Edit').'"><i class="fa fa-pencil"></i></button> <button class="btn btn-success btn-xs btn-pp-status" data-id="'.(int)$r->id.'" data-status="'.$next.'" title="'.$next.'"><i class="fa fa-check"></i></button> <button class="btn btn-danger btn-xs btn-pp-delete" data-id="'.(int)$r->id.'" data-no="'.ppd_h($r->payroll_run_no).'" title="'.hr_h('common_delete', 'Delete').'"'.$canDel.'><i class="fa fa-trash"></i></button></div>';
  $data[]=array($no++,$act,'<strong>'.ppd_h($r->payroll_run_no).'</strong><br><small>'.ppd_h($r->sap_reference).'</small>','<strong>'.ppd_h($r->period_from.' s/d '.$r->period_to).'</strong><br><small>Pay '.ppd_h($r->pay_date).'</small>','<strong>'.ppd_h($r->payroll_area).'</strong><br><small>'.ppd_h($r->run_mode.' / '.$r->control_record_status).'</small>','<strong>'.ppd_h($r->process_type).'</strong><br><small>'.ppd_h($r->currency).'</small>','<span class="badge bg-blue">'.(int)$r->total_employee.' employee</span>','Gross '.number_format((float)$r->total_gross,0).'<br><small>Net <b>'.number_format((float)$r->total_net,0).'</b></small>',ppd_label($r->process_status),ppd_h($r->updated_by?:$r->created_by?:'-').'<br><small>'.ppd_h($r->updated_at?:$r->created_at).'</small>');}
header('Content-Type: application/json; charset=utf-8');echo json_encode(array('draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$total,'data'=>$data,'kpi'=>array('total'=>$kpi?(int)$kpi->total:0,'calculated'=>$kpi?(int)$kpi->calculated:0,'approved'=>$kpi?(int)$kpi->approved:0,'posted'=>$kpi?(int)$kpi->posted:0,'employees'=>$kpi?(int)$kpi->employees:0,'net'=>$kpi?(float)$kpi->net:0)));
?>
