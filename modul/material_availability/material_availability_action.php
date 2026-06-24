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
function maa_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function maa_num($v,$d=5){return number_format((float)$v,$d,',','.');}
function maa_stock_where(&$params){
  $w=" WHERE sl.qty_sisa>0 AND sl.lokasi='GUDANG' ";
  if(isset($_REQUEST['stock_type'])&&trim($_REQUEST['stock_type'])!==''){$w.=" AND COALESCE(sl.stock_type,'UNRESTRICTED')=? ";$params[]=trim($_REQUEST['stock_type']);}
  if(isset($_REQUEST['plant_id'])&&(int)$_REQUEST['plant_id']>0){$w.=" AND sl.plant_id=? ";$params[]=(int)$_REQUEST['plant_id'];}
  if(isset($_REQUEST['storage_location_id'])&&(int)$_REQUEST['storage_location_id']>0){$w.=" AND sl.storage_location_id=? ";$params[]=(int)$_REQUEST['storage_location_id'];}
  if(isset($_REQUEST['storage_bin_id'])&&(int)$_REQUEST['storage_bin_id']>0){$w.=" AND sl.storage_bin_id=? ";$params[]=(int)$_REQUEST['storage_bin_id'];}
  return $w;
}
$act=isset($_GET['act'])?$_GET['act']:'';
switch($act){
  case 'material_search':
    $term=isset($_POST['term'])?trim($_POST['term']):'';$like='%'.$term.'%';
    $rows=$db->query("SELECT DISTINCT m.material_code,m.material_name,m.uom FROM production_order_material m WHERE (?='' OR m.material_code LIKE ? OR m.material_name LIKE ?) ORDER BY m.material_code LIMIT 30",array($term,$like,$like));
    $res=array();foreach($rows as $r)$res[]=array('id'=>$r->material_code,'text'=>$r->material_code.' - '.$r->material_name,'material_name'=>$r->material_name,'uom'=>$r->uom);
    echo json_encode(array('results'=>$res));break;
  case 'layer_detail':
    $code=isset($_POST['material_code'])?trim($_POST['material_code']):'';$poId=isset($_POST['po_id'])?(int)$_POST['po_id']:0;
    if($code===''){echo '<div class="alert alert-warning">Material tidak valid.</div>';break;}
    $params=array();$w=maa_stock_where($params);$params[]=$code;
    $layers=$db->query("SELECT sl.*,p.plant_code,s.storage_code,b.bin_code,br.nm_barang,br.satuan FROM stock_layer sl LEFT JOIN erp_plant p ON p.id=sl.plant_id LEFT JOIN erp_storage_location s ON s.id=sl.storage_location_id LEFT JOIN erp_storage_bin b ON b.id=sl.storage_bin_id LEFT JOIN barang br ON br.kd_barang=sl.kode ".$w." AND sl.kode=? ORDER BY sl.tgl_masuk,sl.id",$params);
    $req=$poId?$db->fetch("SELECT m.*,p.no_production_order FROM production_order_material m JOIN production_order p ON p.id_production_order=m.id_production_order WHERE m.id_production_order=? AND m.material_code=? LIMIT 1",array($poId,$code)):null;
    echo '<h4 style="margin-top:0">Stock Layer Availability: '.maa_h($code).'</h4>';
    if($req)echo '<div class="alert alert-info"><strong>'.maa_h($req->no_production_order).'</strong> Requirement '.maa_num($req->required_qty).' | Issued '.maa_num($req->issued_qty).' | Remaining '.maa_num($req->remaining_qty).' '.maa_h($req->uom).'</div>';
    echo '<div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Layer</th><th>Location</th><th>Stock Type</th><th class="text-right">Qty Sisa</th><th>BC Doc</th><th>Lot/Ref</th><th>Tgl Masuk</th></tr></thead><tbody>';
    $total=0;foreach($layers as $l){$total+=(float)$l->qty_sisa;echo '<tr><td>'.(int)$l->id.'<br><small>'.maa_h($l->kode.' - '.$l->nm_barang).'</small></td><td>'.maa_h(trim($l->plant_code.' / '.$l->storage_code.' / '.$l->bin_code,' /')).'</td><td>'.maa_h($l->stock_type).'</td><td class="text-right">'.maa_num($l->qty_sisa).'</td><td>'.maa_h(trim($l->jenis_dokpab.' '.$l->no_aju.' / '.$l->no_dokpab)).'</td><td>'.maa_h($l->no_bpb?:$l->ref_table.'#'.$l->ref_id).'</td><td>'.maa_h($l->tgl_masuk).'</td></tr>';}
    echo '</tbody><tfoot><tr><th colspan="3" class="text-right">Total Available</th><th class="text-right">'.maa_num($total).'</th><th colspan="3"></th></tr></tfoot></table></div>';break;
  case 'excel':
    $initial=ob_get_level();ob_start();require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $from=isset($_GET['tgl_awal'])?$_GET['tgl_awal']:date('Y-m-01');$to=isset($_GET['tgl_akhir'])?$_GET['tgl_akhir']:date('Y-m-d');$poStatus=isset($_GET['po_status'])?trim($_GET['po_status']):'';$plant=isset($_GET['plant'])?trim($_GET['plant']):'';$stockType=isset($_GET['stock_type'])?trim($_GET['stock_type']):'UNRESTRICTED';
    $sp=array();$sw=maa_stock_where($sp);$stockSub="SELECT kode,SUM(qty_sisa) available_qty,COUNT(*) layer_count FROM stock_layer sl ".$sw." GROUP BY kode";
    $p=array_merge($sp,array($from,$to));$w=" WHERE po.status IN ('CREATED','RELEASED','IN_PROCESS') AND po.start_date BETWEEN ? AND ? ";if($poStatus!==''){$w.=" AND po.status=? ";$p[]=$poStatus;}if($plant!==''){$w.=" AND po.plant=? ";$p[]=$plant;}
    $rows=$db->query("SELECT po.no_production_order,po.status,po.start_date,po.plant,m.material_code,m.material_name,m.required_qty,m.issued_qty,m.remaining_qty,m.uom,COALESCE(st.available_qty,0) available_qty,COALESCE(st.layer_count,0) layer_count FROM production_order_material m JOIN production_order po ON po.id_production_order=m.id_production_order LEFT JOIN ($stockSub) st ON st.kode=m.material_code $w ORDER BY po.start_date,po.no_production_order,m.material_code",$p);
    $excel=new PHPExcel();$sh=$excel->setActiveSheetIndex(0);$sh->setTitle(erp_export_sheet_title('Material Avail'));$heads=array(erp_export_label("No"),erp_export_label("Production Order"),erp_export_label("PO Status"),erp_export_label("Start Date"),erp_export_label("Plant"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Required"),erp_export_label("Issued"),erp_export_label("Remaining"),erp_export_label("Available"),erp_export_label("Shortage"),erp_export_label("UOM"),erp_export_label("Layer Count"));foreach($heads as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$v);$r=5;$n=1;foreach($rows as $row){$remaining=(float)$row->remaining_qty;$short=max($remaining-(float)$row->available_qty,0);$vals=array($n++,$row->no_production_order,$row->status,$row->start_date,$row->plant,$row->material_code,$row->material_name,(float)$row->required_qty,(float)$row->issued_qty,$remaining,(float)$row->available_qty,$short,$row->uom,(int)$row->layer_count);foreach($vals as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r,$v);$r++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sh,'title'=>erp_export_title('MATERIAL AVAILABILITY CHECK'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>14,'numeric_columns'=>array('H','I','J','K','L'),'decimal_columns'=>array('N'),'filters'=>array('Start Date'=>$from.' s/d '.$to,'PO Status'=>$poStatus?:erp_export_all_text(),'Plant'=>$plant?:erp_export_all_text(),'Stock Type'=>$stockType?:erp_export_all_text())));$tmp=erpkb_excel_temp_file('material_availability_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="material_availability_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:header('Content-Type: application/json');echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
}
?>
