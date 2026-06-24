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

function pfr_json($s,$m='',$x=array()){header('Content-Type: application/json; charset=utf-8');$p=array('status'=>$s);if($m!=='')$p[$s==='good'?'message':'error_message']=$m;foreach($x as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function pfr_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function pfr_p($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function pfr_select2($rows,$id,$cb){$out=array();foreach($rows as $r)$out[]=array('id'=>$r->$id,'text'=>$cb($r));header('Content-Type: application/json; charset=utf-8');echo json_encode(array('results'=>$out));exit;}
function pfr_filters($src){
  return array('from'=>isset($src['tgl_awal'])&&$src['tgl_awal']!==''?$src['tgl_awal']:date('Y-01-01'),'to'=>isset($src['tgl_akhir'])&&$src['tgl_akhir']!==''?$src['tgl_akhir']:date('Y-m-d'),'cycle_year'=>isset($src['cycle_year'])?trim($src['cycle_year']):'','appraisal_period'=>isset($src['appraisal_period'])?trim($src['appraisal_period']):'','appraisal_type'=>isset($src['appraisal_type'])?trim($src['appraisal_type']):'','employee_id'=>isset($src['employee_id'])?trim($src['employee_id']):'','appraiser_employee_id'=>isset($src['appraiser_employee_id'])?trim($src['appraiser_employee_id']):'','department_code'=>isset($src['department_code'])?trim($src['department_code']):'','job_title_id'=>isset($src['job_title_id'])?trim($src['job_title_id']):'','final_rating'=>isset($src['final_rating'])?trim($src['final_rating']):'','calibration_status'=>isset($src['calibration_status'])?trim($src['calibration_status']):'','decision'=>isset($src['decision'])?trim($src['decision']):'','improvement_required'=>isset($src['improvement_required'])?trim($src['improvement_required']):'','impact_type'=>isset($src['impact_type'])?trim($src['impact_type']):'','keyword'=>isset($src['keyword'])?trim($src['keyword']):'');
}
function pfr_where($f,&$p){
  $w=" WHERE a.appraisal_date BETWEEN ? AND ? ";array_push($p,$f['from'],$f['to']);
  foreach(array('cycle_year','appraisal_period','appraisal_type','department_code','final_rating','calibration_status','decision','improvement_required') as $k){if($f[$k]!==''){$w.=" AND a.$k=? ";$p[]=$f[$k];}}
  foreach(array('employee_id','appraiser_employee_id','job_title_id') as $k){if($f[$k]!==''){$w.=" AND a.$k=? ";$p[]=(int)$f[$k];}}
  if($f['impact_type']==='HIGH_PERFORMER')$w.=" AND a.final_rating IN ('A','B') AND a.final_score>=85 ";
  elseif($f['impact_type']==='LOW_PERFORMER')$w.=" AND (a.final_rating IN ('D','E') OR a.final_score<70) ";
  elseif($f['impact_type']==='PIP')$w.=" AND a.improvement_required='Y' ";
  elseif($f['impact_type']==='PENDING_APPROVAL')$w.=" AND a.calibration_status IN ('DRAFT','SUBMITTED','MANAGER_APPROVED','HR_REVIEW') ";
  elseif($f['impact_type']==='APPROVED_RESULT')$w.=" AND a.calibration_status='APPROVED' ";
  if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (a.appraisal_no LIKE ? OR e.employee_no LIKE ? OR e.full_name LIKE ? OR d.nm_dept LIKE ? OR j.job_title_name LIKE ? OR ap.full_name LIKE ? OR a.reward_recommendation LIKE ?) ";for($i=0;$i<7;$i++)$p[]=$kw;}
  return $w;
}

$act=isset($_GET['act'])?$_GET['act']:'';
switch($act){
case 'employee_search':
case 'appraiser_search':
  $t=trim(pfr_p('term'));$like='%'.$t.'%';
  $rows=$db->query("SELECT id,employee_no,full_name,department_code,employee_group FROM erp_employee_master WHERE (?='' OR employee_no LIKE ? OR full_name LIKE ? OR department_code LIKE ?) ORDER BY employee_no LIMIT 50",array($t,$like,$like,$like));
  pfr_select2($rows,'id',function($r){return $r->employee_no.' - '.$r->full_name.' ['.$r->department_code.' / '.$r->employee_group.']';});
case 'department_search':
  $t=trim(pfr_p('term'));$like='%'.$t.'%';
  $rows=$db->query("SELECT kd_dept,nm_dept FROM dept WHERE (?='' OR kd_dept LIKE ? OR nm_dept LIKE ?) ORDER BY kd_dept LIMIT 50",array($t,$like,$like));
  pfr_select2($rows,'kd_dept',function($r){return $r->kd_dept.' - '.$r->nm_dept;});
case 'job_title_search':
  $t=trim(pfr_p('term'));$like='%'.$t.'%';
  $rows=$db->query("SELECT id,job_title_code,job_title_name,job_level FROM erp_job_title WHERE (?='' OR job_title_code LIKE ? OR job_title_name LIKE ?) ORDER BY job_level DESC,job_title_code LIMIT 50",array($t,$like,$like));
  pfr_select2($rows,'id',function($r){return $r->job_title_code.' - '.$r->job_title_name.' ['.$r->job_level.']';});
case 'detail':
  $id=(int)pfr_p('id');
  $r=$db->fetch("SELECT a.*,e.employee_no,e.full_name,e.employee_group,d.nm_dept,j.job_title_code,j.job_title_name,ap.employee_no appraiser_no,ap.full_name appraiser_name,sp.employee_no second_no,sp.full_name second_name,hr.employee_no hr_no,hr.full_name hr_name FROM erp_appraisal_approval a JOIN erp_employee_master e ON e.id=a.employee_id LEFT JOIN dept d ON d.kd_dept=a.department_code LEFT JOIN erp_job_title j ON j.id=a.job_title_id LEFT JOIN erp_employee_master ap ON ap.id=a.appraiser_employee_id LEFT JOIN erp_employee_master sp ON sp.id=a.second_appraiser_employee_id LEFT JOIN erp_employee_master hr ON hr.id=a.hr_reviewer_employee_id WHERE a.id=? LIMIT 1",array($id));
  if(!$r){echo '<div class="alert alert-warning">Performance report detail tidak ditemukan.</div>';break;}
  echo '<div class="pfr-detail"><div class="row"><div class="col-sm-8"><h3 style="margin-top:0">'.pfr_h($r->appraisal_no).'</h3><p><b>'.pfr_h($r->employee_no.' - '.$r->full_name).'</b><br>'.pfr_h($r->department_code.' - '.$r->nm_dept).' / '.pfr_h($r->job_title_code.' - '.$r->job_title_name).'</p></div><div class="col-sm-4 text-right"><h4>'.pfr_h($r->cycle_year.' '.$r->appraisal_period).'</h4><span class="label label-primary">Rating '.pfr_h($r->final_rating).'</span> <span class="label label-success">'.pfr_h($r->calibration_status).'</span></div></div><hr>';
  echo '<div class="row"><div class="col-sm-3"><b>Appraisal Date</b><br>'.pfr_h($r->appraisal_date).'</div><div class="col-sm-3"><b>Type</b><br>'.pfr_h($r->appraisal_type).'</div><div class="col-sm-3"><b>Decision</b><br>'.pfr_h($r->decision.' / '.($r->decision_by?:'-')).'</div><div class="col-sm-3"><b>Improvement Required</b><br>'.pfr_h($r->improvement_required).'</div></div><hr>';
  echo '<table class="table table-bordered"><thead><tr><th>KPI</th><th>Competency</th><th>Behavior</th><th>Final Score</th><th>'.hr_h('hr_rating', 'Rating').'</th></tr></thead><tbody><tr><td>'.number_format((float)$r->kpi_score,2).'</td><td>'.number_format((float)$r->competency_score,2).'</td><td>'.number_format((float)$r->behavior_score,2).'</td><td><b>'.number_format((float)$r->final_score,2).'</b></td><td><b>'.pfr_h($r->final_rating).'</b></td></tr></tbody></table>';
  echo '<div class="row"><div class="col-sm-4"><b>Appraiser</b><p>'.pfr_h($r->appraiser_no.' - '.$r->appraiser_name).'<br>Second: '.pfr_h($r->second_no?($r->second_no.' - '.$r->second_name):'-').'<br>HR: '.pfr_h($r->hr_no?($r->hr_no.' - '.$r->hr_name):'-').'</p></div><div class="col-sm-4"><b>Reward Recommendation</b><p>'.pfr_h($r->reward_recommendation?:'-').'</p></div><div class="col-sm-4"><b>Development Plan</b><p>'.pfr_h($r->development_plan?:'-').'</p></div></div>';
  echo '<hr><div class="row"><div class="col-sm-4"><b>Manager Comment</b><p>'.pfr_h($r->manager_comment?:'-').'</p></div><div class="col-sm-4"><b>HR Comment</b><p>'.pfr_h($r->hr_comment?:'-').'</p></div><div class="col-sm-4"><b>Employee Comment</b><p>'.pfr_h($r->employee_comment?:'-').'</p></div></div><p><b>Updated:</b> '.pfr_h(($r->updated_by?:$r->created_by?:'-').' / '.($r->updated_at?:$r->created_at?:'-')).'</p></div>';break;
case 'export':
  $initial=ob_get_level();ob_start();error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $f=pfr_filters($_GET);$p=array();$w=pfr_where($f,$p);
  $rows=$db->query("SELECT a.*,e.employee_no,e.full_name,e.employee_group,d.nm_dept,j.job_title_code,j.job_title_name,ap.employee_no appraiser_no,ap.full_name appraiser_name,hr.employee_no hr_no,hr.full_name hr_name FROM erp_appraisal_approval a JOIN erp_employee_master e ON e.id=a.employee_id LEFT JOIN dept d ON d.kd_dept=a.department_code LEFT JOIN erp_job_title j ON j.id=a.job_title_id LEFT JOIN erp_employee_master ap ON ap.id=a.appraiser_employee_id LEFT JOIN erp_employee_master hr ON hr.id=a.hr_reviewer_employee_id $w ORDER BY a.appraisal_date DESC,a.id DESC",$p);
  $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Performance Report'));
  $heads=array(erp_export_label("No"),erp_export_label("Appraisal No"),erp_export_label("Cycle Year"),erp_export_label("Period"),erp_export_label("Type"),erp_export_label("Date"),erp_export_label("Employee No"),erp_export_label("Employee"),erp_export_label("Employee Group"),erp_export_label("Department"),erp_export_label("Job Title"),erp_export_label("Appraiser"),erp_export_label("HR Reviewer"),erp_export_label("KPI"),erp_export_label("Competency"),erp_export_label("Behavior"),erp_export_label("Final Score"),erp_export_label("Rating"),erp_export_label("Status"),erp_export_label("Decision"),erp_export_label("Improvement Required"),erp_export_label("Reward Recommendation"),erp_export_label("Development Plan"),erp_export_label("Manager Comment"),erp_export_label("HR Comment"),erp_export_label("Employee Comment"),erp_export_label("Updated By"),erp_export_label("Updated At"));
  foreach($heads as $i=>$h)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);
  $rn=5;$n=1;foreach($rows as $r){$vals=array($n++,$r->appraisal_no,$r->cycle_year,$r->appraisal_period,$r->appraisal_type,$r->appraisal_date,$r->employee_no,$r->full_name,$r->employee_group,$r->department_code.' - '.$r->nm_dept,$r->job_title_code.' - '.$r->job_title_name,$r->appraiser_no.' - '.$r->appraiser_name,$r->hr_no?($r->hr_no.' - '.$r->hr_name):'',(float)$r->kpi_score,(float)$r->competency_score,(float)$r->behavior_score,(float)$r->final_score,$r->final_rating,$r->calibration_status,$r->decision,$r->improvement_required,$r->reward_recommendation,$r->development_plan,$r->manager_comment,$r->hr_comment,$r->employee_comment,$r->updated_by?:$r->created_by,$r->updated_at?:$r->created_at);foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rn,$v);$rn++;}
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('PERFORMANCE REPORT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rn-1),'column_count'=>count($heads),'numeric_columns'=>array('N','O','P','Q'),'filters'=>array('Tanggal'=>$f['from'].' s/d '.$f['to'],'Year'=>$f['cycle_year']!==''?$f['cycle_year']:erp_export_all_text(),'Period'=>$f['appraisal_period']!==''?$f['appraisal_period']:erp_export_all_text(),'Rating'=>$f['final_rating']!==''?$f['final_rating']:erp_export_all_text(),'Impact'=>$f['impact_type']!==''?$f['impact_type']:erp_export_all_text()),'widths'=>array('B'=>18,'H'=>24,'J'=>26,'K'=>28,'L'=>28,'M'=>28,'V'=>28,'W'=>34,'X'=>34,'Y'=>34,'Z'=>34,'AA'=>16,'AB'=>18)));
  $tmp=erpkb_excel_temp_file('performance_report_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
  while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="performance_report_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
default:pfr_json('error','Action tidak dikenal.');
}
?>
