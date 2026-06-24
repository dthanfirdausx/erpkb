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

function mrpj($s,$m='',$x=array()){header('Content-Type: application/json');$p=array('status'=>$s);if($m!=='')$p['error_message']=$m;foreach($x as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function mrph($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function mrpn($v,$d=5){return number_format((float)$v,$d,',','.');}
function mrpq($v){return (float)str_replace(',','.',trim((string)$v));}
function mrp_next_no($date){global $db;$prefix='MRP'.date('Ym',strtotime($date?:date('Y-m-d')));$r=$db->fetch("SELECT mrp_no FROM erp_mrp_run WHERE mrp_no LIKE ? ORDER BY mrp_no DESC LIMIT 1",array($prefix.'%'));$n=1;if($r&&preg_match('/(\d{5})$/',$r->mrp_no,$m))$n=(int)$m[1]+1;return $prefix.sprintf('%05d',$n);}
function mrp_stock($code,$plant=''){global $db;$p=array($code);$w=" WHERE kode=? AND COALESCE(qty_sisa,0)>0 AND stock_type='UNRESTRICTED' ";if($plant!==''){$w.=" AND plant_id=(SELECT id FROM erp_plant WHERE plant_code=? LIMIT 1) ";$p[]=$plant;}$r=$db->fetch("SELECT SUM(COALESCE(qty_sisa,0)) qty FROM stock_layer $w",$p);return $r?(float)$r->qty:0;}
function mrp_item($code){global $db;return $db->fetch("SELECT kd_barang,nm_barang,satuan,type,reorder_level FROM barang WHERE kd_barang=? LIMIT 1",array($code));}
function mrp_bom_components($fg,$plant='',$requirementDate=''){global $db;$date=$requirementDate?:date('Y-m-d');$params=array($fg,$date,$date);$where=" WHERE kodebj=? AND COALESCE(bom_status,CASE WHEN COALESCE(status,1)=1 THEN 'RELEASED' ELSE 'INACTIVE' END)='RELEASED' AND COALESCE(bom_usage,'PRODUCTION')='PRODUCTION' AND (valid_from IS NULL OR valid_from<=?) AND (valid_to IS NULL OR valid_to='' OR valid_to>=?) ";if($plant!==''){$where.=" AND (plant_code=? OR plant_code IS NULL OR plant_code='') ";$params[]=$plant;}$bom=$db->fetch("SELECT * FROM bom $where ORDER BY CASE WHEN plant_code=? THEN 0 ELSE 1 END, COALESCE(valid_from,'1900-01-01') DESC, alternative_bom ASC, id DESC LIMIT 1",array_merge($params,array($plant)));if(!$bom)return array();$base=(float)($bom->base_qty?:$bom->jumlah);if($base<=0)$base=1;$rows=$db->query("SELECT * FROM bom_detail WHERE id_bom=? AND COALESCE(issue_status,'ACTIVE')='ACTIVE' AND COALESCE(status,'1') NOT IN ('0','Nonaktif','INACTIVE') ORDER BY COALESCE(line_no,id),id",array($bom->id));$out=array();foreach($rows as $r){$qty=(float)($r->component_qty?:$r->jumlah);$scrap=(float)$r->scrap_percent;if($scrap>0)$qty=$qty*(1+($scrap/100));$out[]=array('code'=>$r->kodebb,'name'=>$r->nm_barang,'uom'=>$r->component_uom?:$r->satuan,'qty_per'=>$qty/$base,'parent'=>$fg);}return $out;}
function mrp_build_lines_from_demand($demandId){
  global $db;
  $params=array();$where=" WHERE h.status='RELEASED' ";
  if($demandId>0){$where.=" AND h.id=? ";$params[]=$demandId;}
  $rows=$db->query("SELECT h.demand_no,h.plant_code,d.material_code,d.material_name,d.period_date,d.open_qty,d.demand_qty,d.uom,d.source_type,d.source_ref FROM erp_demand_plan h JOIN erp_demand_plan_detail d ON d.demand_id=h.id $where ORDER BY d.period_date,d.material_code",$params);
  $agg=array();
  foreach($rows as $r){
    $qty=(float)($r->open_qty>0?$r->open_qty:$r->demand_qty);if($qty<=0)continue;
    $components=mrp_bom_components($r->material_code,$r->plant_code,$r->period_date);
    if(empty($components))$components=array(array('code'=>$r->material_code,'name'=>$r->material_name,'uom'=>$r->uom,'qty_per'=>1,'parent'=>''));
    foreach($components as $c){
      $key=$c['code'].'|'.$r->period_date.'|'.$r->demand_no.'|'.$c['parent'];
      if(!isset($agg[$key]))$agg[$key]=array('material_code'=>$c['code'],'material_name'=>$c['name'],'requirement_date'=>$r->period_date,'gross_requirement'=>0,'uom'=>$c['uom'],'source_type'=>empty($c['parent'])?'DEMAND_PLAN':'BOM_EXPLOSION','source_ref'=>$r->demand_no,'parent_material_code'=>$c['parent'],'remarks'=>empty($c['parent'])?'Demand plan':'BOM explosion from '.$c['parent']);
      $agg[$key]['gross_requirement'] += $qty*(float)$c['qty_per'];
    }
  }
  return array_values($agg);
}
function mrp_complete_line($line,$plant=''){
  $code=trim($line['material_code']);$item=mrp_item($code);$gross=isset($line['gross_requirement'])?mrpq($line['gross_requirement']):0;$stock=mrp_stock($code,$plant);$safety=$item?(float)$item->reorder_level:0;$net=max($gross+$safety-$stock,0);
  return array(
    'material_code'=>$code,
    'material_name'=>isset($line['material_name'])&&$line['material_name']!==''?$line['material_name']:($item?$item->nm_barang:''),
    'material_type'=>$item?$item->type:'',
    'requirement_date'=>isset($line['requirement_date'])&&$line['requirement_date']!==''?$line['requirement_date']:date('Y-m-d'),
    'gross_requirement'=>$gross,
    'available_stock'=>$stock,
    'open_supply'=>0,
    'safety_stock'=>$safety,
    'net_requirement'=>$net,
    'planned_order_qty'=>$net,
    'uom'=>isset($line['uom'])&&$line['uom']!==''?$line['uom']:($item?$item->satuan:''),
    'procurement_type'=>isset($line['procurement_type'])&&$line['procurement_type']!==''?$line['procurement_type']:'EXTERNAL',
    'source_type'=>isset($line['source_type'])&&$line['source_type']!==''?$line['source_type']:'MANUAL',
    'source_ref'=>isset($line['source_ref'])?$line['source_ref']:'',
    'parent_material_code'=>isset($line['parent_material_code'])?$line['parent_material_code']:'',
    'exception_message'=>$net>0?'Shortage / planned supply required':'Covered by stock',
    'remarks'=>isset($line['remarks'])?$line['remarks']:''
  );
}
function mrp_filters_get(){return array('from'=>isset($_GET['tgl_awal'])&&$_GET['tgl_awal']!==''?$_GET['tgl_awal']:date('Y-m-01'),'to'=>isset($_GET['tgl_akhir'])&&$_GET['tgl_akhir']!==''?$_GET['tgl_akhir']:date('Y-m-t'),'status'=>isset($_GET['status'])?trim($_GET['status']):'','plant'=>isset($_GET['plant'])?trim($_GET['plant']):'','keyword'=>isset($_GET['keyword'])?trim($_GET['keyword']):'');}
function mrp_where($f,&$p){$w=" WHERE h.period_from<=? AND h.period_to>=? ";$p[]=$f['to'];$p[]=$f['from'];if($f['status']!==''){$w.=" AND h.status=? ";$p[]=$f['status'];}if($f['plant']!==''){$w.=" AND h.plant_code=? ";$p[]=$f['plant'];}if($f['keyword']!==''){$kw='%'.$f['keyword'].'%';$w.=" AND (h.mrp_no LIKE ? OR h.remarks LIKE ? OR d.material_code LIKE ? OR d.material_name LIKE ? OR d.source_ref LIKE ? OR d.parent_material_code LIKE ?) ";for($i=0;$i<6;$i++)$p[]=$kw;}return $w;}

$act=isset($_GET['act'])?$_GET['act']:'';$username=isset($_SESSION['username'])?$_SESSION['username']:'system';
switch($act){
  case 'material_search':
    $term=isset($_POST['term'])?trim($_POST['term']):'';$kw='%'.$term.'%';
    $rows=$db->query("SELECT kd_barang,nm_barang,satuan,type,reorder_level FROM barang WHERE COALESCE(status,1)=1 AND (?='' OR kd_barang LIKE ? OR nm_barang LIKE ?) ORDER BY kd_barang LIMIT 30",array($term,$kw,$kw));
    $res=array();foreach($rows as $r)$res[]=array('id'=>$r->kd_barang,'text'=>$r->kd_barang.' - '.$r->nm_barang,'material_name'=>$r->nm_barang,'uom'=>$r->satuan,'material_type'=>$r->type,'safety_stock'=>(float)$r->reorder_level);
    echo json_encode(array('results'=>$res));break;

  case 'demand_search':
    $term=isset($_POST['term'])?trim($_POST['term']):'';$kw='%'.$term.'%';
    $rows=$db->query("SELECT id,demand_no,demand_type,demand_version,period_from,period_to,plant_code,total_qty FROM erp_demand_plan WHERE status='RELEASED' AND (?='' OR demand_no LIKE ? OR demand_version LIKE ? OR plant_code LIKE ?) ORDER BY period_from DESC,id DESC LIMIT 30",array($term,$kw,$kw,$kw));
    $res=array();foreach($rows as $r)$res[]=array('id'=>$r->id,'text'=>$r->demand_no.' | '.$r->demand_type.'/'.$r->demand_version.' | '.$r->period_from.' s/d '.$r->period_to.' | Qty '.mrpn($r->total_qty),'period_from'=>$r->period_from,'period_to'=>$r->period_to,'plant_code'=>$r->plant_code);
    echo json_encode(array('results'=>$res));break;

  case 'demand_lines':
    $demandId=isset($_POST['demand_id'])?(int)$_POST['demand_id']:0;$plant=isset($_POST['plant_code'])?trim($_POST['plant_code']):'';$raw=mrp_build_lines_from_demand($demandId);$lines=array();foreach($raw as $l)$lines[]=mrp_complete_line($l,$plant);mrpj('good','',array('lines'=>$lines));break;

  case 'get':
    $id=(int)$_POST['id'];$h=$db->fetch("SELECT * FROM erp_mrp_run WHERE id=? LIMIT 1",array($id));if(!$h)mrpj('error','MRP tidak ditemukan.');
    $rows=$db->query("SELECT * FROM erp_mrp_run_detail WHERE mrp_id=? ORDER BY line_no,id",array($id));$lines=array();foreach($rows as $r)$lines[]=(array)$r;
    mrpj('good','',array('header'=>(array)$h,'lines'=>$lines));break;

  case 'save':
    $id=isset($_POST['id'])?(int)$_POST['id']:0;$from=isset($_POST['period_from'])?trim($_POST['period_from']):'';$to=isset($_POST['period_to'])?trim($_POST['period_to']):'';if($from===''||$to==='')mrpj('error','Period MRP wajib diisi.');if(strtotime($to)<strtotime($from))mrpj('error','Period To tidak boleh lebih kecil dari Period From.');
    $mats=isset($_POST['material_code'])?(array)$_POST['material_code']:array();if(empty($mats))mrpj('error','Minimal satu material MRP wajib diisi.');
    $plantId=isset($_POST['plant_id'])&&$_POST['plant_id']!==''?(int)$_POST['plant_id']:null;$plant=isset($_POST['plant_code'])?trim($_POST['plant_code']):'';$db->query('START TRANSACTION');
    if($id>0){$old=$db->fetch("SELECT * FROM erp_mrp_run WHERE id=? LIMIT 1",array($id));if(!$old){$db->query('ROLLBACK');mrpj('error','MRP tidak ditemukan.');}if($old->status!=='DRAFT'){$db->query('ROLLBACK');mrpj('error','Hanya MRP DRAFT yang bisa diedit.');}$mrpNo=$old->mrp_no;$ok=$db->update('erp_mrp_run',array('mrp_type'=>$_POST['mrp_type'],'planning_scope'=>$_POST['planning_scope'],'plant_id'=>$plantId,'plant_code'=>$plant,'period_from'=>$from,'period_to'=>$to,'source_demand_id'=>isset($_POST['source_demand_id'])&&$_POST['source_demand_id']!==''?(int)$_POST['source_demand_id']:null,'remarks'=>$_POST['remarks'],'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')),'id',$id);$db->query("DELETE FROM erp_mrp_run_detail WHERE mrp_id=?",array($id));}
    else{$mrpNo=mrp_next_no($from);$ok=$db->insert('erp_mrp_run',array('mrp_no'=>$mrpNo,'mrp_type'=>$_POST['mrp_type'],'planning_scope'=>$_POST['planning_scope'],'plant_id'=>$plantId,'plant_code'=>$plant,'period_from'=>$from,'period_to'=>$to,'source_demand_id'=>isset($_POST['source_demand_id'])&&$_POST['source_demand_id']!==''?(int)$_POST['source_demand_id']:null,'status'=>'DRAFT','remarks'=>$_POST['remarks'],'created_by'=>$username,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s')));$id=$db->last_insert_id();}
    if(!$ok){$e=$db->getErrorMessage();$db->query('ROLLBACK');mrpj('error',$e?:'MRP gagal disimpan.');}
    $totalGross=0;$totalShort=0;$lineNo=10;$count=0;
    foreach($mats as $i=>$mat){$mat=trim($mat);if($mat==='')continue;$line=mrp_complete_line(array('material_code'=>$mat,'material_name'=>$_POST['material_name'][$i],'requirement_date'=>$_POST['requirement_date'][$i],'gross_requirement'=>$_POST['gross_requirement'][$i],'uom'=>$_POST['uom'][$i],'procurement_type'=>$_POST['procurement_type'][$i],'source_type'=>$_POST['source_type'][$i],'source_ref'=>$_POST['source_ref'][$i],'parent_material_code'=>$_POST['parent_material_code'][$i],'remarks'=>$_POST['line_remarks'][$i]),$plant);if($line['gross_requirement']<=0)continue;$totalGross+=$line['gross_requirement'];$totalShort+=$line['net_requirement'];$line['mrp_id']=$id;$line['line_no']=$lineNo;if(!$db->insert('erp_mrp_run_detail',$line)){$e=$db->getErrorMessage();$db->query('ROLLBACK');mrpj('error',$e?:'Detail MRP gagal disimpan.');}$lineNo+=10;$count++;}
    if($count<=0){$db->query('ROLLBACK');mrpj('error','Minimal satu line MRP valid wajib diisi.');}
    $db->query("UPDATE erp_mrp_run SET total_material=?,total_gross_req=?,total_shortage=? WHERE id=?",array($count,$totalGross,$totalShort,$id));
    if(function_exists('simpan_log'))simpan_log('User '.$username.' menyimpan MRP '.$mrpNo.' dengan '.$count.' material pada '.date('Y-m-d H:i:s'),$username);
    $db->query('COMMIT');mrpj('good','',array('id'=>$id,'mrp_no'=>$mrpNo));break;

  case 'release':
    $id=(int)$_POST['id'];$h=$db->fetch("SELECT * FROM erp_mrp_run WHERE id=? LIMIT 1",array($id));if(!$h)mrpj('error','MRP tidak ditemukan.');if($h->status!=='DRAFT')mrpj('error','Hanya MRP DRAFT yang bisa release.');$db->query("UPDATE erp_mrp_run SET status='RELEASED',released_by=?,released_at=NOW(),updated_by=?,updated_at=NOW() WHERE id=?",array($username,$username,$id));if(function_exists('simpan_log'))simpan_log('User '.$username.' release MRP '.$h->mrp_no,$username);mrpj('good');break;

  case 'cancel':
    $id=(int)$_POST['id'];$reason=isset($_POST['reason'])?trim($_POST['reason']):'';if($reason==='')mrpj('error','Reason cancel wajib diisi.');$h=$db->fetch("SELECT * FROM erp_mrp_run WHERE id=? LIMIT 1",array($id));if(!$h)mrpj('error','MRP tidak ditemukan.');if($h->status!=='RELEASED')mrpj('error','Hanya MRP RELEASED yang bisa cancel.');$db->query("UPDATE erp_mrp_run SET status='CANCELLED',cancelled_by=?,cancelled_at=NOW(),cancel_reason=?,updated_by=?,updated_at=NOW() WHERE id=?",array($username,$reason,$username,$id));if(function_exists('simpan_log'))simpan_log('User '.$username.' cancel MRP '.$h->mrp_no.' alasan '.$reason,$username);mrpj('good');break;

  case 'delete':
    $id=(int)$_POST['id'];$h=$db->fetch("SELECT * FROM erp_mrp_run WHERE id=? LIMIT 1",array($id));if(!$h)mrpj('error','MRP tidak ditemukan.');if($h->status!=='DRAFT')mrpj('error','Hanya MRP DRAFT yang bisa delete.');$db->delete('erp_mrp_run','id',$id);if(function_exists('simpan_log'))simpan_log('User '.$username.' delete MRP '.$h->mrp_no,$username);mrpj('good');break;

  case 'detail':
    $id=(int)$_POST['id'];$h=$db->fetch("SELECT * FROM erp_mrp_run WHERE id=? LIMIT 1",array($id));if(!$h){echo '<div class="alert alert-warning">MRP tidak ditemukan.</div>';break;}$d=$db->query("SELECT * FROM erp_mrp_run_detail WHERE mrp_id=? ORDER BY line_no,id",array($id));echo '<h3 style="margin-top:0">'.mrph($h->mrp_no).' <small>'.mrph($h->mrp_type.' / '.$h->planning_scope).'</small></h3><div class="row"><div class="col-sm-3"><strong>Period</strong><br>'.mrph($h->period_from.' s/d '.$h->period_to).'</div><div class="col-sm-3"><strong>Plant</strong><br>'.mrph($h->plant_code?:'All Plant').'</div><div class="col-sm-3"><strong>Status</strong><br>'.mrph($h->status).'</div><div class="col-sm-3"><strong>Total Shortage</strong><br>'.mrpn($h->total_shortage).'</div></div><hr><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Line</th><th>Material</th><th>Req Date</th><th class="text-right">Gross Req</th><th class="text-right">Stock</th><th class="text-right">Safety</th><th class="text-right">Net Req</th><th>UOM</th><th>Proc.</th><th>Source</th><th>Exception</th></tr></thead><tbody>';foreach($d as $r)echo '<tr><td>'.(int)$r->line_no.'</td><td><strong>'.mrph($r->material_code).'</strong><br><small>'.mrph($r->material_name).'</small></td><td>'.mrph($r->requirement_date).'</td><td class="text-right">'.mrpn($r->gross_requirement).'</td><td class="text-right">'.mrpn($r->available_stock).'</td><td class="text-right">'.mrpn($r->safety_stock).'</td><td class="text-right">'.mrpn($r->net_requirement).'</td><td>'.mrph($r->uom).'</td><td>'.mrph($r->procurement_type).'</td><td>'.mrph($r->source_type.' '.$r->source_ref).'<br><small>'.mrph($r->parent_material_code).'</small></td><td>'.mrph($r->exception_message).'</td></tr>';echo '</tbody></table></div>';break;

  case 'excel':
    $initial=ob_get_level();ob_start();error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);$f=mrp_filters_get();$p=array();$w=mrp_where($f,$p);$rows=$db->query("SELECT h.mrp_no,h.mrp_type,h.planning_scope,h.period_from,h.period_to,h.plant_code,h.status,d.material_code,d.material_name,d.requirement_date,d.gross_requirement,d.available_stock,d.safety_stock,d.net_requirement,d.planned_order_qty,d.uom,d.procurement_type,d.source_type,d.source_ref,d.parent_material_code,d.exception_message,d.remarks FROM erp_mrp_run h JOIN erp_mrp_run_detail d ON d.mrp_id=h.id $w ORDER BY h.period_from DESC,h.mrp_no,d.line_no",$p);$excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('MRP'));$heads=array(erp_export_label("No"),erp_export_label("MRP No"),erp_export_label("Type"),erp_export_label("Scope"),erp_export_label("From"),erp_export_label("To"),erp_export_label("Plant"),erp_export_label("Status"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Req Date"),erp_export_label("Gross Req"),erp_export_label("Available Stock"),erp_export_label("Safety Stock"),erp_export_label("Net Req"),erp_export_label("Planned Qty"),erp_export_label("UOM"),erp_export_label("Procurement"),erp_export_label("Source"),erp_export_label("Source Ref"),erp_export_label("Parent Material"),erp_export_label("Exception"),erp_export_label("Remarks"));foreach($heads as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$v);$r=5;$n=1;foreach($rows as $row){$vals=array($n++,$row->mrp_no,$row->mrp_type,$row->planning_scope,$row->period_from,$row->period_to,$row->plant_code,$row->status,$row->material_code,$row->material_name,$row->requirement_date,(float)$row->gross_requirement,(float)$row->available_stock,(float)$row->safety_stock,(float)$row->net_requirement,(float)$row->planned_order_qty,$row->uom,$row->procurement_type,$row->source_type,$row->source_ref,$row->parent_material_code,$row->exception_message,$row->remarks);foreach($vals as $i=>$v)$sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r,$v);$r++;}erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('MRP RUN REPORT - SAP PP'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>23,'numeric_columns'=>array('L','M','N','O','P'),'filters'=>array('Period'=>$f['from'].' s/d '.$f['to'],'Status'=>$f['status'],'Plant'=>$f['plant'],'Keyword'=>$f['keyword'])));$tmp=erpkb_excel_temp_file('mrp_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="mrp_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;

  default:mrpj('error','Action tidak dikenal.');
}
?>
