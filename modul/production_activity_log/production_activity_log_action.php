<?php
if (!function_exists('prod_t')) {
  function prod_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('prod_h')) {
  function prod_h($key, $fallback = '') { return htmlspecialchars((string) prod_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('prod_js')) {
  function prod_js($key, $fallback = '') { return json_encode(prod_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if(session_status()===PHP_SESSION_NONE)session_start();
include "../../inc/config.php";
session_check_json();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
function paa_json($s,$m='',$x=array()){header('Content-Type: application/json');$p=array('status'=>$s);if($m!=='')$p['error_message']=$m;foreach($x as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function paa_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function paa_dt($v){$v=trim((string)$v);if($v==='')return '';$v=str_replace('T',' ',$v);if(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/',$v))$v.=':00';$dt=DateTime::createFromFormat('Y-m-d H:i:s',$v);return ($dt&&$dt->format('Y-m-d H:i:s')===$v)?$v:'';}
function paa_next_no($date){global $db;$prefix='PAL'.date('Ym',strtotime($date));$r=$db->fetch("SELECT activity_no FROM erp_production_activity_log WHERE activity_no LIKE ? ORDER BY activity_no DESC LIMIT 1",array($prefix.'%'));$n=1;if($r&&preg_match('/(\d{5})$/',$r->activity_no,$m))$n=(int)$m[1]+1;return $prefix.sprintf('%05d',$n);}
function paa_hist($id,$old,$new,$rem,$user){global $db;$db->insert('erp_production_activity_log_history',array('activity_id'=>$id,'status_lama'=>$old,'status_baru'=>$new,'remarks'=>$rem,'changed_by'=>$user));}
function paa_union(){
  return "SELECT a.id,'MANUAL' source_type,a.activity_no doc_no,a.activity_date,a.activity_time,a.id_production_order,a.no_production_order,a.operation_no,a.operation_name,a.work_center,a.work_center_name,a.plant_code,a.shift_code,a.operator_name,a.activity_type,a.severity,a.activity_text,a.action_taken,a.reference_type,a.reference_id,a.status,a.remarks,a.created_by,a.created_at
          FROM erp_production_activity_log a
          UNION ALL
          SELECT c.id_confirmation,'CONFIRMATION',c.confirmation_no,COALESCE(c.posting_date,DATE(c.confirmation_date)),c.confirmation_date,c.id_production_order,p.no_production_order,c.operation_no,c.operation_name,c.work_center,c.work_center,p.plant,c.shift_code,c.operator_name,'CONFIRMATION','INFO',CONCAT('Confirmation yield ',CAST(c.yield_qty AS CHAR),' ',p.uom,', scrap ',CAST(c.scrap_qty AS CHAR)),c.remarks,'CONFIRMATION',c.id_confirmation,c.status,c.remarks,c.created_by,c.created_at
          FROM production_order_confirmation c JOIN production_order p ON p.id_production_order=c.id_production_order
          UNION ALL
          SELECT d.id,'DOWNTIME',d.downtime_no,d.downtime_date,d.start_time,d.id_production_order,d.no_production_order,d.operation_no,d.operation_name,d.work_center,d.work_center_name,d.plant_code,d.shift_code,d.created_by,'DOWNTIME',CASE WHEN d.impact_type='UNPLANNED' THEN 'WARNING' ELSE 'INFO' END,CONCAT(d.downtime_category,' downtime: ',d.reason_text,' (',CAST(d.duration_minutes AS CHAR),' min)'),d.action_taken,'DOWNTIME',d.id,d.approval_status,d.remarks,d.created_by,d.created_at
          FROM erp_production_downtime d";
}
$act=isset($_GET['act'])?$_GET['act']:'';$username=isset($_SESSION['username'])?$_SESSION['username']:'system';
switch($act){
  case 'order_search':
    $term=isset($_POST['term'])?trim($_POST['term']):'';$like='%'.$term.'%';
    $rows=$db->query("SELECT id_production_order,no_production_order,plant,material_code,material_name,order_qty,uom,status FROM production_order WHERE status IN ('RELEASED','IN_PROCESS','CONFIRMED','TECO') AND (?='' OR no_production_order LIKE ? OR material_code LIKE ? OR material_name LIKE ?) ORDER BY id_production_order DESC LIMIT 30",array($term,$like,$like,$like));
    $res=array();foreach($rows as $r)$res[]=array('id'=>$r->id_production_order,'text'=>$r->no_production_order.' | '.$r->material_code.' - '.$r->material_name.' | '.$r->status,'plant'=>$r->plant,'material_code'=>$r->material_code,'material_name'=>$r->material_name);
    echo json_encode(array('results'=>$res));break;
  case 'operations':
    $id=isset($_POST['production_id'])?(int)$_POST['production_id']:0;
    $ops=$db->query("SELECT id_operation,operation_no,operation_name,work_center,status FROM production_order_operation WHERE id_production_order=? ORDER BY CAST(operation_no AS UNSIGNED),operation_no,id_operation",array($id));
    $res=array();foreach($ops as $o)$res[]=array('id'=>$o->id_operation,'text'=>$o->operation_no.' - '.$o->operation_name.' | WC '.$o->work_center.' | '.$o->status,'operation_no'=>$o->operation_no,'operation_name'=>$o->operation_name,'work_center'=>$o->work_center);
    echo json_encode(array('results'=>$res));break;
  case 'work_center_search':
    $term=isset($_POST['term'])?trim($_POST['term']):'';$like='%'.$term.'%';
    $rows=$db->query("SELECT work_center,MAX(work_center_name) work_center_name FROM (SELECT work_center,work_center_name FROM erp_production_schedule_detail UNION SELECT work_center,work_center FROM production_order_operation UNION SELECT work_center,work_center FROM production_order_confirmation UNION SELECT work_center,work_center_name FROM erp_production_activity_log UNION SELECT work_center,work_center_name FROM erp_production_downtime) x WHERE work_center IS NOT NULL AND work_center<>'' AND (?='' OR work_center LIKE ? OR work_center_name LIKE ?) GROUP BY work_center ORDER BY work_center LIMIT 30",array($term,$like,$like));
    $res=array();foreach($rows as $r)$res[]=array('id'=>$r->work_center,'text'=>$r->work_center.' - '.$r->work_center_name,'work_center_name'=>$r->work_center_name);
    echo json_encode(array('results'=>$res));break;
  case 'save':
    $time=paa_dt(isset($_POST['activity_time'])?$_POST['activity_time']:'');$text=isset($_POST['activity_text'])?trim($_POST['activity_text']):'';$wc=isset($_POST['work_center'])?trim($_POST['work_center']):'';
    if($time==='')paa_json('error','Activity time wajib valid.');if($text==='')paa_json('error','Activity text wajib diisi.');if($wc==='')paa_json('error','Work Center wajib diisi.');
    $productionId=isset($_POST['production_id'])?(int)$_POST['production_id']:0;$operationId=isset($_POST['operation_id'])?(int)$_POST['operation_id']:0;
    $po=$productionId?$db->fetch("SELECT * FROM production_order WHERE id_production_order=? LIMIT 1",array($productionId)):null;$op=$operationId?$db->fetch("SELECT * FROM production_order_operation WHERE id_operation=? LIMIT 1",array($operationId)):null;
    if($wc===''&&$op)$wc=$op->work_center;$shiftId=isset($_POST['shift_id'])?(int)$_POST['shift_id']:0;$shift=$shiftId?$db->fetch("SELECT kode_shift FROM erp_shift WHERE id=? LIMIT 1",array($shiftId)):null;
    $date=substr($time,0,10);$no=paa_next_no($date);
    $data=array('activity_no'=>$no,'activity_date'=>$date,'activity_time'=>$time,'id_production_order'=>$po?$po->id_production_order:null,'no_production_order'=>$po?$po->no_production_order:null,'id_operation'=>$op?$op->id_operation:null,'operation_no'=>$op?$op->operation_no:null,'operation_name'=>$op?$op->operation_name:null,'work_center'=>$wc,'work_center_name'=>isset($_POST['work_center_name'])?trim($_POST['work_center_name']):$wc,'plant_code'=>$po?$po->plant:(isset($_POST['plant_code'])?trim($_POST['plant_code']):''),'shift_id'=>$shiftId?:null,'shift_code'=>$shift?$shift->kode_shift:null,'operator_name'=>isset($_POST['operator_name'])?trim($_POST['operator_name']):'','activity_type'=>isset($_POST['activity_type'])?$_POST['activity_type']:'NOTE','severity'=>isset($_POST['severity'])?$_POST['severity']:'INFO','activity_text'=>$text,'action_taken'=>isset($_POST['action_taken'])?trim($_POST['action_taken']):'','reference_type'=>'MANUAL','status'=>'POSTED','remarks'=>isset($_POST['remarks'])?trim($_POST['remarks']):'','created_by'=>$username,'updated_by'=>$username);
    if(!$db->insert('erp_production_activity_log',$data))paa_json('error',$db->getErrorMessage()?:'Activity log gagal disimpan.');$id=$db->last_insert_id();paa_hist($id,null,'POSTED','Create activity log',$username);simpan_log('User '.$username.' mencatat production activity '.$no.' work center '.$wc.' pada '.date('Y-m-d H:i:s'),$username);paa_json('good','',array('activity_no'=>$no));
    break;
  case 'cancel':
    $id=isset($_POST['id'])?(int)$_POST['id']:0;$reason=isset($_POST['reason'])?trim($_POST['reason']):'';$h=$db->fetch("SELECT * FROM erp_production_activity_log WHERE id=? LIMIT 1",array($id));if(!$h)paa_json('error','Activity log tidak ditemukan.');if($h->status!=='POSTED')paa_json('error','Hanya activity POSTED yang bisa cancel.');
    $db->update('erp_production_activity_log',array('status'=>'CANCELLED','cancelled_by'=>$username,'cancelled_at'=>date('Y-m-d H:i:s'),'cancel_reason'=>$reason,'updated_by'=>$username),'id',$id);paa_hist($id,'POSTED','CANCELLED',$reason,$username);simpan_log('User '.$username.' membatalkan production activity '.$h->activity_no.' pada '.date('Y-m-d H:i:s'),$username);paa_json('good');break;
  case 'detail':
    $id=isset($_POST['id'])?(int)$_POST['id']:0;$source=isset($_POST['source_type'])?trim($_POST['source_type']):'MANUAL';
    $h=$db->fetch("SELECT q.* FROM (".paa_union().") q WHERE q.source_type=? AND q.id=? LIMIT 1",array($source,$id));if(!$h){echo '<div class="alert alert-warning">Activity tidak ditemukan.</div>';break;}
    echo '<h3 style="margin-top:0">'.paa_h($h->doc_no).' <small>'.paa_h($h->source_type.' / '.$h->status).'</small></h3><div class="row"><div class="col-sm-3"><strong>Time</strong><br>'.paa_h($h->activity_time).'</div><div class="col-sm-3"><strong>Work Center</strong><br>'.paa_h($h->work_center).'<br><small>'.paa_h(trim($h->plant_code.' / '.$h->shift_code,' /')).'</small></div><div class="col-sm-3"><strong>Production Order</strong><br>'.paa_h($h->no_production_order?:'-').'<br><small>'.paa_h(trim($h->operation_no.' - '.$h->operation_name,' -')).'</small></div><div class="col-sm-3"><strong>Operator</strong><br>'.paa_h($h->operator_name?:$h->created_by).'</div></div><hr><div class="row"><div class="col-md-4"><strong>Type</strong><br>'.paa_h($h->activity_type.' / '.$h->severity).'</div><div class="col-md-8"><strong>Activity</strong><br>'.paa_h($h->activity_text).'</div></div><hr><strong>Action Taken</strong><br>'.paa_h($h->action_taken?:'-').'<hr><strong>Remarks</strong><br>'.paa_h($h->remarks?:'-');
    if($source==='MANUAL'){$hs=$db->query("SELECT * FROM erp_production_activity_log_history WHERE activity_id=? ORDER BY changed_at,id",array($id));echo '<hr><h4>History</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Time</th><th>From</th><th>To</th><th>User</th><th>Remarks</th></tr></thead><tbody>';foreach($hs as $x)echo '<tr><td>'.paa_h($x->changed_at).'</td><td>'.paa_h($x->status_lama?:'-').'</td><td>'.paa_h($x->status_baru).'</td><td>'.paa_h($x->changed_by).'</td><td>'.paa_h($x->remarks).'</td></tr>';echo '</tbody></table>';}
    break;
  case 'excel':
    $initial=ob_get_level();ob_start();require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);$from=isset($_GET['tgl_awal'])?$_GET['tgl_awal']:date('Y-m-d');$to=isset($_GET['tgl_akhir'])?$_GET['tgl_akhir']:date('Y-m-d');$p=array($from,$to);$w=" WHERE q.activity_date BETWEEN ? AND ? ";if(!empty($_GET['plant'])){$w.=" AND q.plant_code=? ";$p[]=$_GET['plant'];}if(!empty($_GET['activity_type'])){$w.=" AND q.activity_type=? ";$p[]=$_GET['activity_type'];}
    $rows=$db->query("SELECT q.* FROM (".paa_union().") q $w ORDER BY q.activity_time DESC",$p);$excel=new PHPExcel();$sh=$excel->setActiveSheetIndex(0);$sh->setTitle(erp_export_sheet_title('Activity Log'));$heads=array(erp_export_label("No"),erp_export_label("Source"),erp_export_label("Document"),erp_export_label("Date"),erp_export_label("Time"),erp_export_label("Plant"),erp_export_label("Work Center"),erp_export_label("Shift"),erp_export_label("Production Order"),erp_export_label("Operation"),erp_export_label("Operator"),erp_export_label("Type"),erp_export_label("Severity"),erp_export_label("Activity"),erp_export_label("Action Taken"),erp_export_label("Status"),erp_export_label("User"));foreach($heads as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$v);$r=5;$n=1;foreach($rows as $row){$vals=array($n++,$row->source_type,$row->doc_no,$row->activity_date,$row->activity_time,$row->plant_code,$row->work_center,$row->shift_code,$row->no_production_order,$row->operation_no.' - '.$row->operation_name,$row->operator_name,$row->activity_type,$row->severity,$row->activity_text,$row->action_taken,$row->status,$row->created_by);foreach($vals as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r,$v);$r++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sh,'title'=>erp_export_title('PRODUCTION ACTIVITY LOG'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>17,'filters'=>array('Activity Date'=>$from.' s/d '.$to,'Plant'=>isset($_GET['plant'])?$_GET['plant']:erp_export_all_text(),'Activity Type'=>isset($_GET['activity_type'])?$_GET['activity_type']:erp_export_all_text())));$tmp=erpkb_excel_temp_file('production_activity_log_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="production_activity_log_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:paa_json('error','Action tidak dikenal.');
}
?>
