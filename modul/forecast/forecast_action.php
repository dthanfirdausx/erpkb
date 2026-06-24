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
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
session_check_json();

function fcj($status,$msg='',$extra=array()){header('Content-Type: application/json');$p=array('status'=>$status);if($msg!=='')$p['error_message']=$msg;foreach($extra as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function fch($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function fcn($v,$d=5){return number_format((float)$v,$d,',','.');}
function fcqty($v){return (float)str_replace(',','.',trim((string)$v));}
function fc_next_no($date){global $db;$prefix='FC'.date('Ym',strtotime($date?:date('Y-m-d')));$r=$db->fetch("SELECT forecast_no FROM erp_forecast WHERE forecast_no LIKE ? ORDER BY forecast_no DESC LIMIT 1",array($prefix.'%'));$n=1;if($r&&preg_match('/(\d{5})$/',$r->forecast_no,$m))$n=(int)$m[1]+1;return $prefix.sprintf('%05d',$n);}
function fc_filters_get(){
  return array(
    'from'=>isset($_GET['tgl_awal'])&&$_GET['tgl_awal']!==''?$_GET['tgl_awal']:date('Y-m-01'),
    'to'=>isset($_GET['tgl_akhir'])&&$_GET['tgl_akhir']!==''?$_GET['tgl_akhir']:date('Y-m-t'),
    'status'=>isset($_GET['status'])?trim($_GET['status']):'',
    'plant'=>isset($_GET['plant'])?trim($_GET['plant']):'',
    'keyword'=>isset($_GET['keyword'])?trim($_GET['keyword']):''
  );
}
function fc_where($f,&$params){
  $w=" WHERE f.period_from<=? AND f.period_to>=? ";$params[]=$f['to'];$params[]=$f['from'];
  if($f['status']!==''){$w.=" AND f.status=? ";$params[]=$f['status'];}
  if($f['plant']!==''){$w.=" AND f.plant_code=? ";$params[]=$f['plant'];}
  if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (f.forecast_no LIKE ? OR f.forecast_version LIKE ? OR f.customer_name LIKE ? OR f.remarks LIKE ? OR d.material_code LIKE ? OR d.material_name LIKE ?) ";for($i=0;$i<6;$i++)$params[]=$kw;}
  return $w;
}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=isset($_SESSION['username'])?$_SESSION['username']:'system';

switch($act){
  case 'material_search':
    $term=isset($_POST['term'])?trim($_POST['term']):'';$like='%'.$term.'%';
    $rows=$db->query("SELECT b.kd_barang,b.nm_barang,b.satuan,COALESCE(mt.type_code,b.type,b.kategori) material_type FROM barang b LEFT JOIN erp_material_type mt ON mt.id=b.material_type_id WHERE COALESCE(b.status,1)=1 AND (?='' OR b.kd_barang LIKE ? OR b.nm_barang LIKE ?) ORDER BY b.kd_barang LIMIT 30",array($term,$like,$like));
    $res=array();foreach($rows as $r)$res[]=array('id'=>$r->kd_barang,'text'=>$r->kd_barang.' - '.$r->nm_barang,'material_name'=>$r->nm_barang,'uom'=>$r->satuan,'material_type'=>$r->material_type);
    echo json_encode(array('results'=>$res));break;

  case 'customer_search':
    $term=isset($_POST['term'])?trim($_POST['term']):'';$like='%'.$term.'%';
    $rows=$db->query("SELECT kode_penerima,nama FROM penerima WHERE ?='' OR kode_penerima LIKE ? OR nama LIKE ? ORDER BY nama LIMIT 30",array($term,$like,$like));
    $res=array();foreach($rows as $r)$res[]=array('id'=>$r->kode_penerima,'text'=>$r->kode_penerima.' - '.$r->nama,'customer_name'=>$r->nama);
    echo json_encode(array('results'=>$res));break;

  case 'save':
    $periodFrom=isset($_POST['period_from'])?trim($_POST['period_from']):'';$periodTo=isset($_POST['period_to'])?trim($_POST['period_to']):'';
    if($periodFrom===''||$periodTo==='')fcj('error','Period forecast wajib diisi.');
    if(strtotime($periodTo)<strtotime($periodFrom))fcj('error','Period To tidak boleh lebih kecil dari Period From.');
    $materials=isset($_POST['material_code'])?(array)$_POST['material_code']:array();if(empty($materials))fcj('error','Minimal satu material forecast wajib diisi.');
    $db->query('START TRANSACTION');
    $forecastNo=fc_next_no($periodFrom);
    $plantId = isset($_POST['plant_id']) && $_POST['plant_id'] !== '' ? (int)$_POST['plant_id'] : null;
    $header=array(
      'forecast_no'=>$forecastNo,'forecast_type'=>isset($_POST['forecast_type'])?$_POST['forecast_type']:'SALES','forecast_version'=>isset($_POST['forecast_version'])&&$_POST['forecast_version']!==''?$_POST['forecast_version']:'BASE',
      'plant_id'=>$plantId,'plant_code'=>isset($_POST['plant_code'])?$_POST['plant_code']:'',
      'customer_code'=>isset($_POST['customer_code'])?$_POST['customer_code']:'','customer_name'=>isset($_POST['customer_name'])?$_POST['customer_name']:'',
      'period_from'=>$periodFrom,'period_to'=>$periodTo,'status'=>'DRAFT','remarks'=>isset($_POST['remarks'])?$_POST['remarks']:'','created_by'=>$username,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')
    );
    if(!$db->insert('erp_forecast',$header)){ $e=$db->getErrorMessage();$db->query('ROLLBACK');fcj('error',$e?:'Forecast gagal disimpan.');}
    $forecastId=$db->last_insert_id();$total=0;$line=10;
    foreach($materials as $i=>$mat){
      $mat=trim($mat);$qty=isset($_POST['forecast_qty'][$i])?fcqty($_POST['forecast_qty'][$i]):0;$month=isset($_POST['period_month'][$i])?$_POST['period_month'][$i]:'';
      if($mat===''||$qty<=0||$month==='')continue;
      $total+=$qty;
      if(!$db->insert('erp_forecast_detail',array(
        'forecast_id'=>$forecastId,'line_no'=>$line,'material_code'=>$mat,'material_name'=>isset($_POST['material_name'][$i])?$_POST['material_name'][$i]:'',
        'material_type'=>isset($_POST['material_type'][$i])?$_POST['material_type'][$i]:'','period_month'=>$month,'forecast_qty'=>$qty,'uom'=>isset($_POST['uom'][$i])?$_POST['uom'][$i]:'',
        'source_type'=>isset($_POST['source_type'][$i])?$_POST['source_type'][$i]:'MANUAL','confidence_percent'=>isset($_POST['confidence_percent'][$i])?fcqty($_POST['confidence_percent'][$i]):100,'remarks'=>isset($_POST['line_remarks'][$i])?$_POST['line_remarks'][$i]:''
      ))){$e=$db->getErrorMessage();$db->query('ROLLBACK');fcj('error',$e?:'Detail forecast gagal disimpan.');}
      $line+=10;
    }
    if($total<=0){$db->query('ROLLBACK');fcj('error','Total forecast qty harus lebih dari nol.');}
    $db->query("UPDATE erp_forecast SET total_qty=? WHERE id=?",array($total,$forecastId));
    if(function_exists('simpan_log'))simpan_log('User '.$username.' membuat Forecast '.$forecastNo.' total qty '.$total.' pada '.date('Y-m-d H:i:s'),$username);
    $db->query('COMMIT');fcj('good','',array('forecast_no'=>$forecastNo,'id'=>$forecastId));break;

  case 'release':
    $id=(int)$_POST['id'];$f=$db->fetch("SELECT * FROM erp_forecast WHERE id=? LIMIT 1",array($id));if(!$f)fcj('error','Forecast tidak ditemukan.');if($f->status!=='DRAFT')fcj('error','Hanya forecast DRAFT yang bisa release.');
    $db->query("UPDATE erp_forecast SET status='RELEASED',released_by=?,released_at=NOW(),updated_by=?,updated_at=NOW() WHERE id=?",array($username,$username,$id));
    if(function_exists('simpan_log'))simpan_log('User '.$username.' release Forecast '.$f->forecast_no.' pada '.date('Y-m-d H:i:s'),$username);fcj('good');break;

  case 'cancel':
    $id=(int)$_POST['id'];$reason=isset($_POST['reason'])?trim($_POST['reason']):'';if($reason==='')fcj('error','Reason cancel wajib diisi.');$f=$db->fetch("SELECT * FROM erp_forecast WHERE id=? LIMIT 1",array($id));if(!$f)fcj('error','Forecast tidak ditemukan.');if(!in_array($f->status,array('DRAFT','RELEASED'),true))fcj('error','Forecast status ini tidak bisa cancel.');
    $db->query("UPDATE erp_forecast SET status='CANCELLED',cancelled_by=?,cancelled_at=NOW(),cancel_reason=?,updated_by=?,updated_at=NOW() WHERE id=?",array($username,$reason,$username,$id));
    if(function_exists('simpan_log'))simpan_log('User '.$username.' cancel Forecast '.$f->forecast_no.' alasan '.$reason,$username);fcj('good');break;

  case 'detail':
    $id=(int)$_POST['id'];$h=$db->fetch("SELECT * FROM erp_forecast WHERE id=? LIMIT 1",array($id));if(!$h){echo '<div class="alert alert-warning">Forecast tidak ditemukan.</div>';break;}
    $d=$db->query("SELECT * FROM erp_forecast_detail WHERE forecast_id=? ORDER BY line_no,id",array($id));
    echo '<h3 style="margin-top:0">'.fch($h->forecast_no).' <small>'.fch($h->forecast_type.' / '.$h->forecast_version).'</small></h3>';
    echo '<div class="row"><div class="col-sm-3"><strong>Period</strong><br>'.fch($h->period_from.' s/d '.$h->period_to).'</div><div class="col-sm-3"><strong>Plant</strong><br>'.fch($h->plant_code?:'All Plant').'</div><div class="col-sm-3"><strong>Customer</strong><br>'.fch($h->customer_name?:'All Customer').'</div><div class="col-sm-3"><strong>Status</strong><br>'.fch($h->status).'</div></div><hr>';
    echo '<div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Line</th><th>Material</th><th>Month</th><th class="text-right">Qty</th><th>UOM</th><th>Source</th><th class="text-right">Confidence</th><th>Remarks</th></tr></thead><tbody>';
    foreach($d as $r)echo '<tr><td>'.(int)$r->line_no.'</td><td><strong>'.fch($r->material_code).'</strong><br><small>'.fch($r->material_name).'</small></td><td>'.fch($r->period_month).'</td><td class="text-right">'.fcn($r->forecast_qty).'</td><td>'.fch($r->uom).'</td><td>'.fch($r->source_type).'</td><td class="text-right">'.fcn($r->confidence_percent,2).'%</td><td>'.fch($r->remarks).'</td></tr>';
    echo '</tbody></table></div>';break;

  case 'excel':
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";
    $f=fc_filters_get();$params=array();$where=fc_where($f,$params);
    $rows=$db->query("SELECT f.forecast_no,f.forecast_type,f.forecast_version,f.period_from,f.period_to,f.plant_code,f.customer_name,f.status,d.material_code,d.material_name,d.period_month,d.forecast_qty,d.uom,d.source_type,d.confidence_percent,d.remarks FROM erp_forecast f JOIN erp_forecast_detail d ON d.forecast_id=f.id $where ORDER BY f.period_from DESC,f.forecast_no,d.line_no",$params);
    $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Forecast'));
    $headers=array(erp_export_label("No"),erp_export_label("Forecast No"),erp_export_label("Type"),erp_export_label("Version"),erp_export_label("Period From"),erp_export_label("Period To"),erp_export_label("Plant"),erp_export_label("Customer"),erp_export_label("Status"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Period Month"),erp_export_label("Forecast Qty"),erp_export_label("UOM"),erp_export_label("Source"),erp_export_label("Confidence %"),erp_export_label("Remarks"));
    foreach($headers as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$v);
    $r=5;$n=1;foreach($rows as $row){$vals=array($n++,$row->forecast_no,$row->forecast_type,$row->forecast_version,$row->period_from,$row->period_to,$row->plant_code,$row->customer_name,$row->status,$row->material_code,$row->material_name,$row->period_month,(float)$row->forecast_qty,$row->uom,$row->source_type,(float)$row->confidence_percent,$row->remarks);foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r,$v);$r++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('FORECAST REPORT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>17,'numeric_columns'=>array('M'),'decimal_columns'=>array('P'),'filters'=>array('Period'=>$f['from'].' s/d '.$f['to'],'Status'=>$f['status'],'Plant'=>$f['plant'],'Keyword'=>$f['keyword'])));
    $tmp=erpkb_excel_temp_file('forecast_');$writer=PHPExcel_IOFactory::createWriter($excel,'Excel2007');$writer->save($tmp);while(ob_get_level())ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="forecast_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.filesize($tmp));readfile($tmp);unlink($tmp);exit;

  default: fcj('error','Action tidak dikenal.');
}
?>
