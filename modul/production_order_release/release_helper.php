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
function por_num($v,$d=5){return number_format((float)$v,$d,',','.');}
function por_stock_available($materialCode,$plantCode=''){
  global $db;
  $params=array($materialCode);
  $join='';$where=" WHERE sl.kode=? AND sl.qty_sisa>0 AND sl.stock_type='UNRESTRICTED' ";
  if($plantCode!==''){$join=" LEFT JOIN erp_plant p ON p.id=sl.plant_id ";$where.=" AND (p.plant_code=? OR sl.plant_id IS NULL) ";$params[]=$plantCode;}
  $row=$db->fetch("SELECT COALESCE(SUM(sl.qty_sisa),0) qty FROM stock_layer sl $join $where",$params);
  return $row?(float)$row->qty:0;
}
function por_readiness($id){
  global $db;
  $po=$db->fetch("SELECT * FROM production_order WHERE id_production_order=? LIMIT 1",array($id));
  $res=array('po'=>$po,'errors'=>array(),'warnings'=>array(),'checks'=>array(),'score'=>0,'materials'=>array(),'operations'=>array());
  if(!$po){$res['errors'][]='Production Order tidak ditemukan.';return $res;}
  if($po->status!=='CREATED')$res['errors'][]='Hanya Production Order status CREATED yang bisa release.';
  if(trim((string)$po->material_code)===''||trim((string)$po->material_name)==='')$res['errors'][]='Material header belum lengkap.';
  if((float)$po->order_qty<=0)$res['errors'][]='Order Qty wajib lebih dari nol.';
  if(trim((string)$po->plant)==='')$res['errors'][]='Plant belum diisi.';
  if(trim((string)$po->start_date)===''||trim((string)$po->finish_date)==='')$res['errors'][]='Start Date dan Finish Date wajib diisi.';
  if($po->start_date&&$po->finish_date&&strtotime($po->finish_date)<strtotime($po->start_date))$res['errors'][]='Finish Date tidak boleh sebelum Start Date.';
  if(empty($po->production_version_id))$res['warnings'][]='Production Version belum tersimpan di header PO. PO tetap bisa release, tetapi disarankan memakai Production Version RELEASED.';
  if(empty($po->bom_id)||empty($po->bom_no))$res['warnings'][]='Referensi BOM belum tersimpan di header PO.';
  if(empty($po->routing_id)||empty($po->routing_no))$res['warnings'][]='Referensi Routing belum tersimpan di header PO.';
  if($po->plant&&$po->start_date&&$po->finish_date){
    $cal=$db->fetch("SELECT COUNT(*) work_days FROM erp_factory_calendar d JOIN erp_factory_calendar_header h ON h.id=d.calendar_id WHERE h.calendar_status='RELEASED' AND d.plant_code=? AND d.tanggal BETWEEN ? AND ? AND d.tipe_hari='Kerja'",array($po->plant,$po->start_date,$po->finish_date));
    if(!$cal||(int)$cal->work_days<=0)$res['warnings'][]='Factory Calendar RELEASED tidak menemukan hari kerja pada range schedule order.';
  }
  $materials=$db->query("SELECT * FROM production_order_material WHERE id_production_order=? ORDER BY id_material",array($id));
  if(empty($materials))$res['errors'][]='Material requirement belum terbentuk. Cek BOM / Production Version.';
  foreach($materials as $m){
    $required=(float)$m->remaining_qty>0?(float)$m->remaining_qty:(float)$m->required_qty;
    $available=por_stock_available($m->material_code,$po->plant);
    $status=$available+0.00001>=$required?'OK':'SHORTAGE';
    if($status==='SHORTAGE')$res['warnings'][]='Stock shortage '.$m->material_code.' required '.por_num($required).' available '.por_num($available).'.';
    $res['materials'][]=array('material_code'=>$m->material_code,'material_name'=>$m->material_name,'required_qty'=>$required,'available_qty'=>$available,'uom'=>$m->uom,'status'=>$status);
  }
  $ops=$db->query("SELECT * FROM production_order_operation WHERE id_production_order=? ORDER BY operation_no,id_operation",array($id));
  if(empty($ops))$res['errors'][]='Operation/routing belum terbentuk.';
  foreach($ops as $op){
    if(trim((string)$op->operation_no)===''||trim((string)$op->operation_name)==='')$res['errors'][]='Ada operation dengan nomor/nama kosong.';
    if(trim((string)$op->work_center)==='')$res['warnings'][]='Operation '.$op->operation_no.' belum punya work center.';
    $res['operations'][]=array('operation_no'=>$op->operation_no,'work_center'=>$op->work_center,'operation_name'=>$op->operation_name,'setup_time'=>(float)$op->setup_time,'machine_time'=>(float)$op->machine_time,'labor_time'=>(float)$op->labor_time);
  }
  $totalChecks=8;
  $passed=$totalChecks-count($res['errors']);
  if($passed<0)$passed=0;
  $res['score']=round(($passed/$totalChecks)*100,2);
  return $res;
}
function por_release_order($id,$username,$remarks=''){
  global $db;
  $ready=por_readiness($id);
  if(!$ready['po'])return array('status'=>'error','message'=>'Production Order tidak ditemukan.','readiness'=>$ready);
  if(!empty($ready['errors']))return array('status'=>'error','message'=>implode(' ', $ready['errors']),'readiness'=>$ready);
  $po=$ready['po'];
  $db->query('START TRANSACTION');
  $db->query("UPDATE production_order SET status='RELEASED',updated_by=?,updated_at=NOW() WHERE id_production_order=? AND status='CREATED'",array($username,$id));
  $db->query("UPDATE production_order_operation SET status='OPEN' WHERE id_production_order=? AND status IS NULL",array($id));
  $db->insert('production_order_release_log',array('id_production_order'=>$id,'no_production_order'=>$po->no_production_order,'release_status'=>'RELEASED','readiness_score'=>$ready['score'],'error_count'=>count($ready['errors']),'warning_count'=>count($ready['warnings']),'readiness_json'=>json_encode(array('errors'=>$ready['errors'],'warnings'=>$ready['warnings'],'materials'=>$ready['materials'],'operations'=>$ready['operations'])),'remarks'=>$remarks,'released_by'=>$username,'released_at'=>date('Y-m-d H:i:s')));
  if(function_exists('simpan_log'))simpan_log('User '.$username.' release Production Order '.$po->no_production_order.' readiness '.$ready['score'].'% warning '.count($ready['warnings']).' pada '.date('Y-m-d H:i:s'),$username);
  $db->query('COMMIT');
  return array('status'=>'good','message'=>'','readiness'=>$ready);
}
?>
