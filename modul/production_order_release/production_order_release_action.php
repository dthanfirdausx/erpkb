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
include "release_helper.php";
function prj($s,$m='',$x=array()){header('Content-Type: application/json');$p=array('status'=>$s);if($m!=='')$p['error_message']=$m;foreach($x as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function prh($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$act=isset($_GET['act'])?$_GET['act']:'';$username=isset($_SESSION['username'])?$_SESSION['username']:'system';
switch($act){
  case 'readiness':
    $id=(int)$_POST['id'];$r=por_readiness($id);prj(empty($r['errors'])?'good':'error',empty($r['errors'])?'':'Readiness masih memiliki error.',array('readiness'=>$r));break;
  case 'release':
    $id=(int)$_POST['id'];$remarks=isset($_POST['remarks'])?trim($_POST['remarks']):'';$res=por_release_order($id,$username,$remarks);if($res['status']==='good')prj('good','',array('readiness'=>$res['readiness']));prj('error',$res['message'],array('readiness'=>$res['readiness']));break;
  case 'detail':
    $id=(int)$_POST['id'];$r=por_readiness($id);$po=$r['po'];if(!$po){echo '<div class="alert alert-warning">Production Order tidak ditemukan.</div>';break;}
    echo '<h3 style="margin-top:0">'.prh($po->no_production_order).' <small>'.prh($po->status).'</small></h3><div class="row"><div class="col-sm-3"><strong>Material</strong><br>'.prh($po->material_code.' - '.$po->material_name).'</div><div class="col-sm-2"><strong>Qty</strong><br>'.por_num($po->order_qty).' '.prh($po->uom).'</div><div class="col-sm-2"><strong>Plant</strong><br>'.prh($po->plant).'</div><div class="col-sm-2"><strong>Schedule</strong><br>'.prh($po->start_date.' s/d '.$po->finish_date).'</div><div class="col-sm-3"><strong>PV/BOM/Routing</strong><br>'.prh(($po->production_version_no?:'-').' / '.($po->bom_no?:'-').' / '.($po->routing_no?:'-')).'</div></div><hr>';
    echo '<div class="row"><div class="col-md-4"><div class="alert alert-'.(empty($r['errors'])?'success':'danger').'"><strong>Readiness Score: '.$r['score'].'%</strong><br>Error '.count($r['errors']).' / Warning '.count($r['warnings']).'</div></div><div class="col-md-8">';
    if($r['errors']){echo '<div class="alert alert-danger"><strong>Errors</strong><ul>';foreach($r['errors'] as $e)echo '<li>'.prh($e).'</li>';echo '</ul></div>';}
    if($r['warnings']){echo '<div class="alert alert-warning"><strong>Warnings</strong><ul>';foreach($r['warnings'] as $w)echo '<li>'.prh($w).'</li>';echo '</ul></div>';}
    echo '</div></div><h4>Material Availability</h4><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Material</th><th class="text-right">Required</th><th class="text-right">Available</th><th>UOM</th><th>Status</th></tr></thead><tbody>';
    foreach($r['materials'] as $m){$cls=$m['status']==='OK'?'success':'danger';echo '<tr><td><strong>'.prh($m['material_code']).'</strong><br><small>'.prh($m['material_name']).'</small></td><td class="text-right">'.por_num($m['required_qty']).'</td><td class="text-right">'.por_num($m['available_qty']).'</td><td>'.prh($m['uom']).'</td><td><span class="label label-'.$cls.'">'.prh($m['status']).'</span></td></tr>';}
    echo '</tbody></table></div><h4>Operations</h4><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Op</th><th>Work Center</th><th>Name</th><th class="text-right">Setup</th><th class="text-right">Machine</th><th class="text-right">Labor</th></tr></thead><tbody>';
    foreach($r['operations'] as $o)echo '<tr><td>'.prh($o['operation_no']).'</td><td>'.prh($o['work_center']).'</td><td>'.prh($o['operation_name']).'</td><td class="text-right">'.por_num($o['setup_time'],2).'</td><td class="text-right">'.por_num($o['machine_time'],2).'</td><td class="text-right">'.por_num($o['labor_time'],2).'</td></tr>';
    echo '</tbody></table></div>';break;
  case 'excel':
    $initial=ob_get_level();ob_start();error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);$from=isset($_GET['tgl_awal'])?$_GET['tgl_awal']:date('Y-m-01');$to=isset($_GET['tgl_akhir'])?$_GET['tgl_akhir']:date('Y-m-d');$plant=isset($_GET['plant'])?trim($_GET['plant']):'';$where=" WHERE status='CREATED' AND start_date BETWEEN ? AND ? ";$p=array($from,$to);if($plant!==''){$where.=" AND plant=? ";$p[]=$plant;}$rows=$db->query("SELECT * FROM production_order $where ORDER BY start_date,id_production_order",$p);$excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('PO Release'));$heads=array(erp_export_label("No"),erp_export_label("PO"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Qty"),erp_export_label("UOM"),erp_export_label("Plant"),erp_export_label("Start"),erp_export_label("Finish"),erp_export_label("PV"),erp_export_label("BOM"),erp_export_label("Routing"),erp_export_label("Score"),erp_export_label("Errors"),erp_export_label("Warnings"),erp_export_label("Status"));foreach($heads as $i=>$h)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$h);$rnum=5;$n=1;foreach($rows as $row){$ready=por_readiness($row->id_production_order);$vals=array($n++,$row->no_production_order,$row->material_code,$row->material_name,(float)$row->order_qty,$row->uom,$row->plant,$row->start_date,$row->finish_date,$row->production_version_no,$row->bom_no,$row->routing_no,$ready['score'],implode('; ',$ready['errors']),implode('; ',$ready['warnings']),$row->status);foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$rnum,$v);$rnum++;}erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('PRODUCTION ORDER RELEASE READINESS'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$rnum-1),'column_count'=>16,'numeric_columns'=>array('E','M'),'filters'=>array('Start Date'=>$from.' s/d '.$to,'Plant'=>$plant)));$tmp=erpkb_excel_temp_file('production_order_release_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="production_order_release_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:prj('error','Action tidak dikenal.');
}
?>
