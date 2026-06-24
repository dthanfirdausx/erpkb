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

function dmj($s,$m='',$x=array()){header('Content-Type: application/json');$p=array('status'=>$s);if($m!=='')$p['error_message']=$m;foreach($x as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function dmh($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function dmn($v,$d=5){return number_format((float)$v,$d,',','.');}
function dmqty($v){return (float)str_replace(',','.',trim((string)$v));}
function dm_next_no($date){global $db;$prefix='DM'.date('Ym',strtotime($date?:date('Y-m-d')));$r=$db->fetch("SELECT demand_no FROM erp_demand_plan WHERE demand_no LIKE ? ORDER BY demand_no DESC LIMIT 1",array($prefix.'%'));$n=1;if($r&&preg_match('/(\d{5})$/',$r->demand_no,$m))$n=(int)$m[1]+1;return $prefix.sprintf('%05d',$n);}
function dm_filters_get(){return array('from'=>isset($_GET['tgl_awal'])&&$_GET['tgl_awal']!==''?$_GET['tgl_awal']:date('Y-m-01'),'to'=>isset($_GET['tgl_akhir'])&&$_GET['tgl_akhir']!==''?$_GET['tgl_akhir']:date('Y-m-t'),'status'=>isset($_GET['status'])?trim($_GET['status']):'','plant'=>isset($_GET['plant'])?trim($_GET['plant']):'','keyword'=>isset($_GET['keyword'])?trim($_GET['keyword']):'');}
function dm_where($f,&$p){$w=" WHERE h.period_from<=? AND h.period_to>=? ";$p[]=$f['to'];$p[]=$f['from'];if($f['status']!==''){$w.=" AND h.status=? ";$p[]=$f['status'];}if($f['plant']!==''){$w.=" AND h.plant_code=? ";$p[]=$f['plant'];}if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (h.demand_no LIKE ? OR h.demand_version LIKE ? OR d.material_code LIKE ? OR d.material_name LIKE ? OR d.source_ref LIKE ? OR d.customer_name LIKE ?) ";for($i=0;$i<6;$i++)$p[]=$kw;}return $w;}
$act=isset($_GET['act'])?$_GET['act']:'';$username=isset($_SESSION['username'])?$_SESSION['username']:'system';

switch($act){
  case 'material_search':
    $term=isset($_POST['term'])?trim($_POST['term']):'';$kw='%'.$term.'%';
    $rows=$db->query("SELECT kd_barang,nm_barang,satuan FROM barang WHERE COALESCE(status,1)=1 AND (?='' OR kd_barang LIKE ? OR nm_barang LIKE ?) ORDER BY kd_barang LIMIT 30",array($term,$kw,$kw));
    $res=array();foreach($rows as $r)$res[]=array('id'=>$r->kd_barang,'text'=>$r->kd_barang.' - '.$r->nm_barang,'material_name'=>$r->nm_barang,'uom'=>$r->satuan);
    echo json_encode(array('results'=>$res));break;

  case 'forecast_search':
    $term=isset($_POST['term'])?trim($_POST['term']):'';$kw='%'.$term.'%';
    $rows=$db->query("SELECT id,forecast_no,forecast_type,forecast_version,period_from,period_to,plant_code,total_qty FROM erp_forecast WHERE status='RELEASED' AND (?='' OR forecast_no LIKE ? OR forecast_version LIKE ? OR plant_code LIKE ?) ORDER BY period_from DESC,id DESC LIMIT 30",array($term,$kw,$kw,$kw));
    $res=array();foreach($rows as $r)$res[]=array('id'=>$r->id,'text'=>$r->forecast_no.' | '.$r->forecast_type.'/'.$r->forecast_version.' | '.$r->period_from.' s/d '.$r->period_to.' | Qty '.dmn($r->total_qty),'period_from'=>$r->period_from,'period_to'=>$r->period_to,'plant_code'=>$r->plant_code);
    echo json_encode(array('results'=>$res));break;

  case 'forecast_lines':
    $id=(int)$_POST['id'];$h=$db->fetch("SELECT * FROM erp_forecast WHERE id=? AND status='RELEASED' LIMIT 1",array($id));if(!$h)dmj('error','Released forecast tidak ditemukan.');
    $rows=$db->query("SELECT * FROM erp_forecast_detail WHERE forecast_id=? ORDER BY line_no,id",array($id));$lines=array();
    foreach($rows as $r)$lines[]=array('material_code'=>$r->material_code,'material_name'=>$r->material_name,'period_date'=>$r->period_month,'demand_qty'=>(float)$r->forecast_qty,'uom'=>$r->uom,'requirement_type'=>'VSF','source_type'=>'FORECAST','source_ref'=>$h->forecast_no,'remarks'=>'From forecast '.$h->forecast_no);
    dmj('good','',array('lines'=>$lines,'period_from'=>$h->period_from,'period_to'=>$h->period_to,'plant_code'=>$h->plant_code));break;

  case 'sales_order_lines':
    $from=isset($_POST['period_from'])?$_POST['period_from']:date('Y-m-01');$to=isset($_POST['period_to'])?$_POST['period_to']:date('Y-m-t');
    $rows=$db->query("SELECT so.id_sales_order,so.no_sales_order,so.kode_penerima,p.nama customer_name,so.delivery_date,sod.id_detail,sod.kd_barang,COALESCE(b.nm_barang,sod.ket) material_name,COALESCE(b.satuan,'') uom,COALESCE(sod.qty,0) qty,COALESCE(ship.shipped_qty,0) shipped_qty,GREATEST(COALESCE(sod.qty,0)-COALESCE(ship.shipped_qty,0),0) open_qty FROM sales_order_detail sod JOIN sales_order so ON so.id_sales_order=sod.id_sales_order LEFT JOIN penerima p ON p.kode_penerima=so.kode_penerima LEFT JOIN barang b ON b.kd_barang=sod.kd_barang LEFT JOIN(SELECT sj.no_sales_order,d.kode_barang,SUM(COALESCE(d.qty_kirim,0)) shipped_qty FROM surat_jalan sj JOIN surat_jalan_detail d ON d.surat_jalan_id=sj.id WHERE sj.status<>'dibatalkan' GROUP BY sj.no_sales_order,d.kode_barang)ship ON ship.no_sales_order=so.no_sales_order AND ship.kode_barang=sod.kd_barang WHERE COALESCE(so.approval_status,'')='APPROVED' AND COALESCE(so.delivery_date,so.so_date) BETWEEN ? AND ? AND GREATEST(COALESCE(sod.qty,0)-COALESCE(ship.shipped_qty,0),0)>0 ORDER BY so.delivery_date,so.no_sales_order,sod.id_detail",array($from,$to));
    $lines=array();foreach($rows as $r)$lines[]=array('material_code'=>$r->kd_barang,'material_name'=>$r->material_name,'period_date'=>$r->delivery_date?:$from,'demand_qty'=>(float)$r->open_qty,'uom'=>$r->uom,'requirement_type'=>'KE','source_type'=>'SALES_ORDER','source_ref'=>$r->no_sales_order,'id_sales_order'=>$r->id_sales_order,'id_sales_order_detail'=>$r->id_detail,'customer_code'=>$r->kode_penerima,'customer_name'=>$r->customer_name,'remarks'=>'Open SO qty');
    dmj('good','',array('lines'=>$lines));break;

  case 'save':
    $from=isset($_POST['period_from'])?trim($_POST['period_from']):'';$to=isset($_POST['period_to'])?trim($_POST['period_to']):'';if($from===''||$to==='')dmj('error','Period demand wajib diisi.');if(strtotime($to)<strtotime($from))dmj('error','Period To tidak boleh lebih kecil dari Period From.');
    $mats=isset($_POST['material_code'])?(array)$_POST['material_code']:array();if(empty($mats))dmj('error','Minimal satu demand line wajib diisi.');
    $plantId=isset($_POST['plant_id'])&&$_POST['plant_id']!==''?(int)$_POST['plant_id']:null;$demandNo=dm_next_no($from);$auto=isset($_POST['auto_release'])&&$_POST['auto_release']==='Y';
    $db->query('START TRANSACTION');
    if(!$db->insert('erp_demand_plan',array('demand_no'=>$demandNo,'demand_type'=>$_POST['demand_type'],'demand_version'=>$_POST['demand_version']?:'BASE','plant_id'=>$plantId,'plant_code'=>$_POST['plant_code'],'period_from'=>$from,'period_to'=>$to,'status'=>$auto?'RELEASED':'DRAFT','remarks'=>$_POST['remarks'],'created_by'=>$username,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s'),'released_by'=>$auto?$username:'','released_at'=>$auto?date('Y-m-d H:i:s'):''))){$e=$db->getErrorMessage();$db->query('ROLLBACK');dmj('error',$e?:'Demand gagal disimpan.');}
    $id=$db->last_insert_id();$total=0;$line=10;
    foreach($mats as $i=>$mat){$mat=trim($mat);$qty=isset($_POST['demand_qty'][$i])?dmqty($_POST['demand_qty'][$i]):0;$period=isset($_POST['period_date'][$i])?$_POST['period_date'][$i]:'';if($mat===''||$qty<=0||$period==='')continue;$total+=$qty;
      if(!$db->insert('erp_demand_plan_detail',array('demand_id'=>$id,'line_no'=>$line,'material_code'=>$mat,'material_name'=>$_POST['material_name'][$i],'period_date'=>$period,'demand_qty'=>$qty,'uom'=>$_POST['uom'][$i],'requirement_type'=>$_POST['requirement_type'][$i],'source_type'=>$_POST['source_type'][$i],'source_ref'=>$_POST['source_ref'][$i],'id_sales_order'=>$_POST['id_sales_order'][$i],'id_sales_order_detail'=>$_POST['id_sales_order_detail'][$i],'customer_code'=>$_POST['customer_code'][$i],'customer_name'=>$_POST['customer_name'][$i],'open_qty'=>$qty,'remarks'=>$_POST['line_remarks'][$i]))){$e=$db->getErrorMessage();$db->query('ROLLBACK');dmj('error',$e?:'Detail demand gagal disimpan.');}$line+=10;}
    if($total<=0){$db->query('ROLLBACK');dmj('error','Total demand qty harus lebih dari nol.');}
    $db->query("UPDATE erp_demand_plan SET total_qty=? WHERE id=?",array($total,$id));
    if(function_exists('simpan_log'))simpan_log('User '.$username.' membuat Demand Plan '.$demandNo.' total qty '.$total.' pada '.date('Y-m-d H:i:s'),$username);
    $db->query('COMMIT');dmj('good','',array('demand_no'=>$demandNo,'id'=>$id));break;

  case 'release':
    $id=(int)$_POST['id'];$h=$db->fetch("SELECT * FROM erp_demand_plan WHERE id=? LIMIT 1",array($id));if(!$h)dmj('error','Demand tidak ditemukan.');if($h->status!=='DRAFT')dmj('error','Hanya demand DRAFT yang bisa release.');$db->query("UPDATE erp_demand_plan SET status='RELEASED',released_by=?,released_at=NOW(),updated_by=?,updated_at=NOW() WHERE id=?",array($username,$username,$id));if(function_exists('simpan_log'))simpan_log('User '.$username.' release Demand Plan '.$h->demand_no,$username);dmj('good');break;

  case 'cancel':
    $id=(int)$_POST['id'];$reason=isset($_POST['reason'])?trim($_POST['reason']):'';if($reason==='')dmj('error','Reason cancel wajib diisi.');$h=$db->fetch("SELECT * FROM erp_demand_plan WHERE id=? LIMIT 1",array($id));if(!$h)dmj('error','Demand tidak ditemukan.');if(!in_array($h->status,array('DRAFT','RELEASED'),true))dmj('error','Status ini tidak bisa cancel.');$db->query("UPDATE erp_demand_plan SET status='CANCELLED',cancelled_by=?,cancelled_at=NOW(),cancel_reason=?,updated_by=?,updated_at=NOW() WHERE id=?",array($username,$reason,$username,$id));if(function_exists('simpan_log'))simpan_log('User '.$username.' cancel Demand Plan '.$h->demand_no.' alasan '.$reason,$username);dmj('good');break;

  case 'detail':
    $id=(int)$_POST['id'];$h=$db->fetch("SELECT * FROM erp_demand_plan WHERE id=? LIMIT 1",array($id));if(!$h){echo '<div class="alert alert-warning">Demand tidak ditemukan.</div>';break;}$d=$db->query("SELECT * FROM erp_demand_plan_detail WHERE demand_id=? ORDER BY line_no,id",array($id));echo '<h3 style="margin-top:0">'.dmh($h->demand_no).' <small>'.dmh($h->demand_type.' / '.$h->demand_version).'</small></h3><div class="row"><div class="col-sm-3"><strong>Period</strong><br>'.dmh($h->period_from.' s/d '.$h->period_to).'</div><div class="col-sm-3"><strong>Plant</strong><br>'.dmh($h->plant_code?:'All Plant').'</div><div class="col-sm-3"><strong>Status</strong><br>'.dmh($h->status).'</div><div class="col-sm-3"><strong>Total Qty</strong><br>'.dmn($h->total_qty).'</div></div><hr><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Line</th><th>Material</th><th>Period</th><th class="text-right">Demand</th><th class="text-right">Open</th><th>UOM</th><th>Req</th><th>Source</th><th>Customer</th><th>Remarks</th></tr></thead><tbody>';foreach($d as $r)echo '<tr><td>'.(int)$r->line_no.'</td><td><strong>'.dmh($r->material_code).'</strong><br><small>'.dmh($r->material_name).'</small></td><td>'.dmh($r->period_date).'</td><td class="text-right">'.dmn($r->demand_qty).'</td><td class="text-right">'.dmn($r->open_qty).'</td><td>'.dmh($r->uom).'</td><td>'.dmh($r->requirement_type).'</td><td>'.dmh($r->source_type.' '.$r->source_ref).'</td><td>'.dmh($r->customer_name).'</td><td>'.dmh($r->remarks).'</td></tr>';echo '</tbody></table></div>';break;

  case 'excel':
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";$f=dm_filters_get();$p=array();$w=dm_where($f,$p);$rows=$db->query("SELECT h.demand_no,h.demand_type,h.demand_version,h.period_from,h.period_to,h.plant_code,h.status,d.material_code,d.material_name,d.period_date,d.demand_qty,d.open_qty,d.uom,d.requirement_type,d.source_type,d.source_ref,d.customer_name,d.remarks FROM erp_demand_plan h JOIN erp_demand_plan_detail d ON d.demand_id=h.id $w ORDER BY h.period_from DESC,h.demand_no,d.line_no",$p);$excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Demand'));$heads=array(erp_export_label("No"),erp_export_label("Demand No"),erp_export_label("Type"),erp_export_label("Version"),erp_export_label("From"),erp_export_label("To"),erp_export_label("Plant"),erp_export_label("Status"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Period"),erp_export_label("Demand Qty"),erp_export_label("Open Qty"),erp_export_label("UOM"),erp_export_label("Req Type"),erp_export_label("Source"),erp_export_label("Source Ref"),erp_export_label("Customer"),erp_export_label("Remarks"));foreach($heads as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$v);$r=5;$n=1;foreach($rows as $row){$vals=array($n++,$row->demand_no,$row->demand_type,$row->demand_version,$row->period_from,$row->period_to,$row->plant_code,$row->status,$row->material_code,$row->material_name,$row->period_date,(float)$row->demand_qty,(float)$row->open_qty,$row->uom,$row->requirement_type,$row->source_type,$row->source_ref,$row->customer_name,$row->remarks);foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r,$v);$r++;}erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('DEMAND MANAGEMENT REPORT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>19,'numeric_columns'=>array('L','M'),'filters'=>array('Period'=>$f['from'].' s/d '.$f['to'],'Status'=>$f['status'],'Plant'=>$f['plant'],'Keyword'=>$f['keyword'])));$tmp=erpkb_excel_temp_file('demand_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);while(ob_get_level())ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="demand_management_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.filesize($tmp));readfile($tmp);unlink($tmp);exit;

  default:dmj('error','Action tidak dikenal.');
}
?>
