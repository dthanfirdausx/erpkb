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

function msr_json($s,$m='',$x=array()){header('Content-Type: application/json');$p=array('status'=>$s);if($m!=='')$p['error_message']=$m;foreach($x as $k=>$v)$p[$k]=$v;echo json_encode($p);exit;}
function msr_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function msr_num($v,$d=5){return number_format((float)$v,$d,',','.');}
function msr_qty($v){return (float)str_replace(',','.',trim((string)$v));}
function msr_next_no($date){global $db;$prefix='MSR'.date('Ym',strtotime($date));$row=$db->fetch("SELECT staging_no FROM erp_material_staging_request WHERE staging_no LIKE ? ORDER BY staging_no DESC LIMIT 1",array($prefix.'%'));$n=1;if($row&&preg_match('/(\d{5})$/',$row->staging_no,$m))$n=((int)$m[1])+1;return $prefix.sprintf('%05d',$n);}
function msr_layer_filter($materialCode,$plantId,$slocId,$binId){
  $where=" WHERE sl.kode=? AND sl.qty_sisa>0 AND sl.lokasi='GUDANG' AND COALESCE(sl.stock_type,'UNRESTRICTED')='UNRESTRICTED' ";
  $params=array($materialCode);
  if($plantId>0){$where.=" AND sl.plant_id=? ";$params[]=$plantId;}
  if($slocId>0){$where.=" AND sl.storage_location_id=? ";$params[]=$slocId;}
  if($binId>0){$where.=" AND sl.storage_bin_id=? ";$params[]=$binId;}
  return array($where,$params);
}
function msr_storage_label($table,$id,$codeCol,$nameCol){
  global $db;if((int)$id<=0)return null;$row=$db->fetch("SELECT $codeCol AS kode,$nameCol AS nama FROM $table WHERE id=? LIMIT 1",array((int)$id));return $row?trim($row->kode.' - '.$row->nama):null;
}
function msr_history($id,$old,$new,$remarks,$user){global $db;$db->insert('erp_material_staging_request_history',array('staging_id'=>$id,'status_lama'=>$old,'status_baru'=>$new,'remarks'=>$remarks,'changed_by'=>$user));}

$act=isset($_GET['act'])?$_GET['act']:'';
$username=isset($_SESSION['username'])?$_SESSION['username']:'system';

switch($act){
  case 'production_search':
    $term=isset($_POST['term'])?trim($_POST['term']):'';$like='%'.$term.'%';
    $rows=$db->query("SELECT p.id_production_order,p.no_production_order,p.start_date,p.finish_date,p.plant,p.storage_location,p.material_code,p.material_name,p.order_qty,p.uom,p.status,
                             COALESCE(COUNT(m.id_material),0) item_count,COALESCE(SUM(CASE WHEN COALESCE(m.remaining_qty,m.required_qty)>0 THEN 1 ELSE 0 END),0) open_item
                      FROM production_order p
                      LEFT JOIN production_order_material m ON m.id_production_order=p.id_production_order
                      WHERE p.status IN ('RELEASED','IN_PROCESS')
                        AND (?='' OR p.no_production_order LIKE ? OR p.material_code LIKE ? OR p.material_name LIKE ?)
                      GROUP BY p.id_production_order
                      HAVING open_item>0
                      ORDER BY p.id_production_order DESC LIMIT 30",array($term,$like,$like,$like));
    $res=array();
    foreach($rows as $r)$res[]=array('id'=>$r->id_production_order,'text'=>$r->no_production_order.' | '.$r->material_code.' - '.$r->material_name.' | '.$r->open_item.' component | '.$r->status,'production_no'=>$r->no_production_order,'plant'=>$r->plant,'storage_location'=>$r->storage_location);
    echo json_encode(array('results'=>$res));break;

  case 'production_items':
    $pid=isset($_POST['production_id'])?(int)$_POST['production_id']:0;$plantId=isset($_POST['plant_id'])?(int)$_POST['plant_id']:0;$slocId=isset($_POST['storage_location_id'])?(int)$_POST['storage_location_id']:0;$binId=isset($_POST['storage_bin_id'])?(int)$_POST['storage_bin_id']:0;
    $po=$db->fetch("SELECT * FROM production_order WHERE id_production_order=? LIMIT 1",array($pid));
    if(!$po){echo '<div class="alert alert-warning">Production Order tidak ditemukan.</div>';break;}
    if(!in_array($po->status,array('RELEASED','IN_PROCESS'),true)){echo '<div class="alert alert-danger">Production Order harus RELEASED atau IN_PROCESS.</div>';break;}
    $details=$db->query("SELECT m.*,b.nm_barang,b.satuan FROM production_order_material m LEFT JOIN barang b ON b.kd_barang=m.material_code WHERE m.id_production_order=? AND COALESCE(m.remaining_qty,m.required_qty)>0 ORDER BY m.id_material",array($pid));
    echo '<div class="alert alert-info"><strong>'.msr_h($po->no_production_order).'</strong> | '.msr_h($po->material_code.' - '.$po->material_name).' | Qty '.msr_num($po->order_qty).' '.msr_h($po->uom).'<br><small>Status '.msr_h($po->status).' | Plant '.msr_h($po->plant).' | SLoc '.msr_h($po->storage_location).'</small></div>';
    echo '<div class="table-responsive"><table class="table table-bordered table-condensed msr-item-table"><thead><tr><th style="width:42px">Pick</th><th>Component</th><th class="text-right">Required</th><th class="text-right">Issued</th><th class="text-right">Remaining</th><th class="text-right">Available</th><th>UOM</th><th style="width:135px">Request Qty</th><th class="text-right">Shortage</th><th>Customs Preview</th><th>Remark</th></tr></thead><tbody>';
    foreach($details as $d){
      list($w,$p)=msr_layer_filter($d->material_code,$plantId,$slocId,$binId);
      $st=$db->fetch("SELECT COALESCE(SUM(sl.qty_sisa),0) available_qty,GROUP_CONCAT(DISTINCT CONCAT(COALESCE(sl.no_aju,''),' / ',COALESCE(sl.no_dokpab,'')) ORDER BY sl.tgl_masuk,sl.id SEPARATOR '<br>') customs_refs FROM stock_layer sl ".$w,$p);
      $required=(float)$d->required_qty;$issued=(float)$d->issued_qty;$remaining=(float)($d->remaining_qty!==null?$d->remaining_qty:max($required-$issued,0));$available=$st?(float)$st->available_qty:0;$short=max($remaining-$available,0);$id=(int)$d->id_material;
      echo '<tr data-available="'.$available.'"><td class="text-center"><input type="checkbox" name="selected_line[]" value="'.$id.'" checked></td><td><strong>'.msr_h($d->material_code).'</strong><br><small>'.msr_h($d->material_name?:$d->nm_barang).'</small></td><td class="text-right">'.msr_num($required).'</td><td class="text-right">'.msr_num($issued).'</td><td class="text-right">'.msr_num($remaining).'</td><td class="text-right">'.msr_num($available).'</td><td>'.msr_h($d->uom?:$d->satuan).'</td><td><input type="number" step="0.00001" min="0" name="request_qty['.$id.']" class="form-control text-right msr-request-qty" value="'.msr_h($remaining).'"></td><td class="text-right msr-shortage">'.msr_num($short).'</td><td><small>'.($st&&$st->customs_refs?$st->customs_refs:'<span class="text-danger">Tidak ada stock layer</span>').'</small></td><td><input name="item_remarks['.$id.']" class="form-control" placeholder="Catatan item"></td></tr>';
    }
    echo '</tbody></table></div>';break;

  case 'save':
    $pid=isset($_POST['production_id'])?(int)$_POST['production_id']:0;$requestDate=isset($_POST['request_date'])?trim($_POST['request_date']):'';$requiredDate=isset($_POST['required_date'])?trim($_POST['required_date']):'';$submit=isset($_POST['submit'])&&$_POST['submit']==='Y';
    if($pid<=0)msr_json('error','Production Order wajib dipilih.');if($requestDate==='')msr_json('error','Request Date wajib diisi.');if(empty($_POST['selected_line'])||!is_array($_POST['selected_line']))msr_json('error','Minimal satu component wajib dipilih.');
    $po=$db->fetch("SELECT * FROM production_order WHERE id_production_order=? LIMIT 1",array($pid));if(!$po)msr_json('error','Production Order tidak ditemukan.');if(!in_array($po->status,array('RELEASED','IN_PROCESS'),true))msr_json('error','Production Order harus RELEASED atau IN_PROCESS.');
    $plantId=isset($_POST['plant_id'])?(int)$_POST['plant_id']:0;$slocId=isset($_POST['storage_location_id'])?(int)$_POST['storage_location_id']:0;$binId=isset($_POST['storage_bin_id'])?(int)$_POST['storage_bin_id']:0;
    $selected=array();foreach($_POST['selected_line'] as $line){$line=(int)$line;$qty=isset($_POST['request_qty'][$line])?msr_qty($_POST['request_qty'][$line]):0;if($line>0&&$qty>0)$selected[$line]=$qty;}if(!$selected)msr_json('error','Request Qty wajib lebih dari nol.');
    $db->query('START TRANSACTION');$no=msr_next_no($requestDate);$status=$submit?'REQUESTED':'DRAFT';
    $plant=$plantId>0?$db->fetch("SELECT plant_code FROM erp_plant WHERE id=? LIMIT 1",array($plantId)):null;
    $header=array('staging_no'=>$no,'id_production_order'=>$po->id_production_order,'no_production_order'=>$po->no_production_order,'request_date'=>$requestDate,'required_date'=>$requiredDate?:null,'request_type'=>'PRODUCTION_ORDER','plant_id'=>$plantId>0?$plantId:null,'plant_code'=>$plant?$plant->plant_code:$po->plant,'source_storage_location_id'=>$slocId>0?$slocId:null,'source_storage_location'=>msr_storage_label('erp_storage_location',$slocId,'storage_code','storage_name'),'source_storage_bin_id'=>$binId>0?$binId:null,'source_storage_bin'=>msr_storage_label('erp_storage_bin',$binId,'bin_code','bin_name'),'destination_storage_location'=>isset($_POST['destination_storage_location'])?trim($_POST['destination_storage_location']):'PRODUCTION','destination_area'=>isset($_POST['destination_area'])?trim($_POST['destination_area']):'Production Staging Area','priority'=>isset($_POST['priority'])?trim($_POST['priority']):'NORMAL','staging_status'=>$status,'reference_no'=>isset($_POST['reference_no'])?trim($_POST['reference_no']):'','remarks'=>isset($_POST['remarks'])?trim($_POST['remarks']):'','created_by'=>$username,'updated_by'=>$username);
    if($submit){$header['submitted_by']=$username;$header['submitted_at']=date('Y-m-d H:i:s');}
    if(!$db->insert('erp_material_staging_request',$header)){$err=$db->getErrorMessage();$db->query('ROLLBACK');msr_json('error',$err?:'Header Material Staging gagal disimpan.');}
    $sid=$db->last_insert_id();$lineNo=1;
    foreach($selected as $lineId=>$qty){
      $m=$db->fetch("SELECT m.*,b.nm_barang,b.satuan FROM production_order_material m LEFT JOIN barang b ON b.kd_barang=m.material_code WHERE m.id_material=? AND m.id_production_order=? LIMIT 1",array($lineId,$pid));
      if(!$m){$db->query('ROLLBACK');msr_json('error','Component production order tidak valid.');}
      list($w,$p)=msr_layer_filter($m->material_code,$plantId,$slocId,$binId);$st=$db->fetch("SELECT COALESCE(SUM(qty_sisa),0) available_qty FROM stock_layer sl ".$w,$p);
      $required=(float)$m->required_qty;$issued=(float)$m->issued_qty;$remaining=(float)($m->remaining_qty!==null?$m->remaining_qty:max($required-$issued,0));$available=$st?(float)$st->available_qty:0;$short=max($qty-$available,0);
      $detail=array('staging_id'=>$sid,'production_material_id'=>$lineId,'line_no'=>$lineNo++,'material_code'=>$m->material_code,'material_name'=>$m->material_name?:$m->nm_barang,'required_qty'=>$required,'requested_qty'=>$qty,'issued_qty_snapshot'=>$issued,'remaining_qty_snapshot'=>$remaining,'available_qty_snapshot'=>$available,'uom'=>$m->uom?:$m->satuan,'source_storage_location'=>$header['source_storage_location'],'source_storage_bin'=>$header['source_storage_bin'],'stock_type'=>'UNRESTRICTED','shortage_qty'=>$short,'line_status'=>$short>0?'SHORTAGE':'OPEN','remarks'=>isset($_POST['item_remarks'][$lineId])?trim($_POST['item_remarks'][$lineId]):'');
      if(!$db->insert('erp_material_staging_request_detail',$detail)){$err=$db->getErrorMessage();$db->query('ROLLBACK');msr_json('error',$err?:'Detail Material Staging gagal disimpan.');}
    }
    msr_history($sid,null,$status,$submit?'Submit staging request':'Save draft',$username);simpan_log('User '.$username.' membuat Material Staging Request '.$no.' untuk Production Order '.$po->no_production_order.' pada '.date('Y-m-d H:i:s'),$username);
    $db->query('COMMIT');msr_json('good','',array('staging_no'=>$no));break;

  case 'submit': case 'start_picking': case 'confirm_staged':
    $id=isset($_POST['id'])?(int)$_POST['id']:0;$h=$db->fetch("SELECT * FROM erp_material_staging_request WHERE id=? LIMIT 1",array($id));if(!$h)msr_json('error','Material Staging Request tidak ditemukan.');
    $old=$h->staging_status;$new=$old;$extra=array('updated_by'=>$username);
    if($act==='submit'){if($old!=='DRAFT')msr_json('error','Hanya DRAFT yang bisa submit.');$new='REQUESTED';$extra['submitted_by']=$username;$extra['submitted_at']=date('Y-m-d H:i:s');}
    if($act==='start_picking'){if($old!=='REQUESTED')msr_json('error','Start picking hanya untuk REQUESTED.');$new='PICKING';$db->query("UPDATE erp_material_staging_request_detail SET line_status='PICKING' WHERE staging_id=? AND line_status IN ('OPEN','SHORTAGE')",array($id));}
    if($act==='confirm_staged'){if(!in_array($old,array('REQUESTED','PICKING'),true))msr_json('error','Confirm staged hanya untuk REQUESTED/PICKING.');$new='STAGED';$extra['staged_by']=$username;$extra['staged_at']=date('Y-m-d H:i:s');$db->query("UPDATE erp_material_staging_request_detail SET staged_qty=requested_qty,line_status='STAGED' WHERE staging_id=? AND line_status<>'CANCELLED'",array($id));}
    $extra['staging_status']=$new;$db->update('erp_material_staging_request',$extra,'id',$id);msr_history($id,$old,$new,$act,$username);simpan_log('User '.$username.' mengubah Material Staging Request '.$h->staging_no.' dari '.$old.' menjadi '.$new.' pada '.date('Y-m-d H:i:s'),$username);msr_json('good');break;

  case 'cancel':
    $id=isset($_POST['id'])?(int)$_POST['id']:0;$reason=isset($_POST['reason'])?trim($_POST['reason']):'';$h=$db->fetch("SELECT * FROM erp_material_staging_request WHERE id=? LIMIT 1",array($id));if(!$h)msr_json('error','Material Staging Request tidak ditemukan.');if(in_array($h->staging_status,array('STAGED','ISSUED','CANCELLED'),true))msr_json('error','Status ini tidak bisa dibatalkan dari menu staging.');
    $db->update('erp_material_staging_request',array('staging_status'=>'CANCELLED','cancelled_by'=>$username,'cancelled_at'=>date('Y-m-d H:i:s'),'cancel_reason'=>$reason,'updated_by'=>$username),'id',$id);$db->query("UPDATE erp_material_staging_request_detail SET line_status='CANCELLED' WHERE staging_id=?",array($id));msr_history($id,$h->staging_status,'CANCELLED',$reason,$username);simpan_log('User '.$username.' membatalkan Material Staging Request '.$h->staging_no.' pada '.date('Y-m-d H:i:s'),$username);msr_json('good');break;

  case 'detail':
    $id=isset($_POST['id'])?(int)$_POST['id']:0;$h=$db->fetch("SELECT h.*,p.material_code fg_code,p.material_name fg_name,p.order_qty,p.uom,p.status po_status FROM erp_material_staging_request h LEFT JOIN production_order p ON p.id_production_order=h.id_production_order WHERE h.id=? LIMIT 1",array($id));if(!$h){echo '<div class="alert alert-warning">Data tidak ditemukan.</div>';break;}
    $ds=$db->query("SELECT * FROM erp_material_staging_request_detail WHERE staging_id=? ORDER BY line_no,id",array($id));$hs=$db->query("SELECT * FROM erp_material_staging_request_history WHERE staging_id=? ORDER BY changed_at,id",array($id));
    echo '<h3 style="margin-top:0">'.msr_h($h->staging_no).' <small>'.msr_h($h->staging_status).'</small></h3><div class="row"><div class="col-sm-3"><strong>Production Order</strong><br>'.msr_h($h->no_production_order).'<br><small>'.msr_h($h->fg_code.' - '.$h->fg_name).'</small></div><div class="col-sm-2"><strong>FG Qty</strong><br>'.msr_num($h->order_qty).' '.msr_h($h->uom).'</div><div class="col-sm-2"><strong>Date</strong><br>'.msr_h($h->request_date).' s/d '.msr_h($h->required_date?:'-').'</div><div class="col-sm-3"><strong>Source</strong><br>'.msr_h(trim(($h->plant_code?:'').' / '.($h->source_storage_location?:''),' /')).'<br><small>Bin '.msr_h($h->source_storage_bin?:'-').'</small></div><div class="col-sm-2"><strong>Priority</strong><br>'.msr_h($h->priority).'</div></div><hr>';
    echo '<h4>Component Staging</h4><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>#</th><th>Material</th><th class="text-right">Required</th><th class="text-right">Remaining</th><th class="text-right">Available</th><th class="text-right">Requested</th><th class="text-right">Staged</th><th class="text-right">Shortage</th><th>UOM</th><th>Status</th><th>Remarks</th></tr></thead><tbody>';
    foreach($ds as $d)echo '<tr><td>'.intval($d->line_no).'</td><td><strong>'.msr_h($d->material_code).'</strong><br><small>'.msr_h($d->material_name).'</small></td><td class="text-right">'.msr_num($d->required_qty).'</td><td class="text-right">'.msr_num($d->remaining_qty_snapshot).'</td><td class="text-right">'.msr_num($d->available_qty_snapshot).'</td><td class="text-right">'.msr_num($d->requested_qty).'</td><td class="text-right">'.msr_num($d->staged_qty).'</td><td class="text-right">'.msr_num($d->shortage_qty).'</td><td>'.msr_h($d->uom).'</td><td>'.msr_h($d->line_status).'</td><td>'.msr_h($d->remarks).'</td></tr>';
    echo '</tbody></table></div><h4>Status History</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Time</th><th>From</th><th>To</th><th>User</th><th>Remarks</th></tr></thead><tbody>';
    foreach($hs as $x)echo '<tr><td>'.msr_h($x->changed_at).'</td><td>'.msr_h($x->status_lama?:'-').'</td><td>'.msr_h($x->status_baru).'</td><td>'.msr_h($x->changed_by).'</td><td>'.msr_h($x->remarks).'</td></tr>';
    echo '</tbody></table>';break;

  case 'excel':
    $initial=ob_get_level();ob_start();require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);$from=isset($_GET['tgl_awal'])?$_GET['tgl_awal']:date('Y-m-01');$to=isset($_GET['tgl_akhir'])?$_GET['tgl_akhir']:date('Y-m-d');$status=isset($_GET['status'])?trim($_GET['status']):'';$params=array($from,$to);$where=" WHERE h.request_date BETWEEN ? AND ? ";if($status!==''){$where.=" AND h.staging_status=? ";$params[]=$status;}
    $rows=$db->query("SELECT h.*,COUNT(d.id) item_count,SUM(d.requested_qty) requested_qty,SUM(d.staged_qty) staged_qty,SUM(d.shortage_qty) shortage_qty FROM erp_material_staging_request h LEFT JOIN erp_material_staging_request_detail d ON d.staging_id=h.id ".$where." GROUP BY h.id ORDER BY h.request_date,h.id",$params);
    $excel=new PHPExcel();$sh=$excel->setActiveSheetIndex(0);$sh->setTitle(erp_export_sheet_title('Material Staging'));$heads=array(erp_export_label("No"),erp_export_label("Staging No"),erp_export_label("Production Order"),erp_export_label("Request Date"),erp_export_label("Required Date"),erp_export_label("Source"),erp_export_label("Destination"),erp_export_label("Items"),erp_export_label("Requested Qty"),erp_export_label("Staged Qty"),erp_export_label("Shortage"),erp_export_label("Priority"),erp_export_label("Status"),erp_export_label("Created By"),erp_export_label("Remarks"));foreach($heads as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$v);$r=5;$n=1;foreach($rows as $row){$vals=array($n++,$row->staging_no,$row->no_production_order,$row->request_date,$row->required_date,trim(($row->plant_code?:'').' / '.($row->source_storage_location?:'').' / '.($row->source_storage_bin?:''),' /'),$row->destination_area?:$row->destination_storage_location,(int)$row->item_count,(float)$row->requested_qty,(float)$row->staged_qty,(float)$row->shortage_qty,$row->priority,$row->staging_status,$row->created_by,$row->remarks);foreach($vals as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r,$v);$r++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sh,'title'=>erp_export_title('MATERIAL STAGING REQUEST'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>15,'numeric_columns'=>array('H','I','J','K'),'filters'=>array('Request Date'=>$from.' s/d '.$to,'Status'=>$status?:erp_export_all_text())));$tmp=erpkb_excel_temp_file('material_staging_request_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="material_staging_request_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:msr_json('error','Action tidak dikenal.');
}
?>
