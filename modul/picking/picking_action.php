<?php
if (!function_exists('sd_t')) {
  function sd_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('sd_h')) {
  function sd_h($key, $fallback = '') { return htmlspecialchars((string) sd_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sd_js')) {
  function sd_js($key, $fallback = '') { return json_encode(sd_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if(session_status()===PHP_SESSION_NONE)session_start();
include "../../inc/config.php";
include "picking_lib.php";
$act=isset($_GET['act'])?$_GET['act']:'';
function pick_json($p){header('Content-Type: application/json; charset=utf-8');echo json_encode($p);exit;}

if($act==='delivery_search'){
  session_check_json();$term=pick_input('term');$params=array();$where=" WHERE od.status IN ('CREATED','PICKING') AND od.picking_status<>'COMPLETE' ";
  if($term!==''){$kw='%'.$term.'%';$where.=" AND (od.delivery_no LIKE ? OR od.no_sales_order LIKE ? OR od.customer_name LIKE ?) ";$params[]=$kw;$params[]=$kw;$params[]=$kw;}
  $rows=$db->query("SELECT od.id,od.delivery_no,od.no_sales_order,od.customer_name FROM erp_outbound_delivery od $where ORDER BY od.delivery_date DESC LIMIT 30",$params);
  $res=array();foreach($rows as $r)$res[]=array('id'=>$r->id,'text'=>$r->delivery_no.' - SO '.$r->no_sales_order.' - '.$r->customer_name);
  pick_json(array('results'=>$res));
}

if($act==='delivery_items'){
  session_check_json();$id=(int)pick_input('delivery_id');
  $rows=$db->query("SELECT * FROM erp_outbound_delivery_detail WHERE delivery_id=? ORDER BY line_no,id",array($id));
  $no=1;$count=0;foreach($rows as $r){$count++;$open=max(0,(float)$r->delivery_qty-(float)$r->picked_qty); ?>
    <tr><td class="text-center"><?=$no++;?><input type="hidden" name="delivery_detail_id[]" value="<?=intval($r->id);?>"><input type="hidden" name="material_code[]" value="<?=pick_h($r->material_code);?>"><input type="hidden" name="material_name[]" value="<?=pick_h($r->material_name);?>"></td><td><strong><?=pick_h($r->material_code);?></strong><br><small><?=pick_h($r->material_name);?></small></td><td><input name="store[]" class="form-control" value="<?=pick_h($r->store);?>"></td><td><input name="delivery_qty[]" class="form-control text-right" readonly value="<?=number_format((float)$r->delivery_qty,5,'.','');?>"></td><td><input name="picked_qty[]" class="form-control text-right" value="<?=number_format($open,5,'.','');?>"></td><td><input name="uom[]" class="form-control" value="<?=pick_h($r->uom);?>"></td><td><input name="batch_no[]" class="form-control" value="<?=pick_h($r->batch_no);?>"></td><td><input name="source_bin[]" class="form-control"></td><td><input name="item_remarks[]" class="form-control" value="<?=pick_h($r->remarks);?>"></td></tr>
  <?php } if($count===0)echo '<tr><td colspan="9" class="text-center text-muted">Delivery tidak memiliki item.</td></tr>';exit;
}

if($act==='save'){
  session_check_json();$user=pick_user();$deliveryId=(int)pick_input('delivery_id');
  $od=$db->fetch("SELECT * FROM erp_outbound_delivery WHERE id=? LIMIT 1",array($deliveryId));
  if(!$od)pick_json(array('status'=>'error','error_message'=>'Outbound Delivery tidak ditemukan.'));
  $detailIds=isset($_POST['delivery_detail_id'])&&is_array($_POST['delivery_detail_id'])?$_POST['delivery_detail_id']:array();if(!count($detailIds))pick_json(array('status'=>'error','error_message'=>'Item picking wajib diisi.'));
  $pickingNo=pick_next_no($db);$header=array('picking_no'=>$pickingNo,'picking_date'=>pick_date(pick_input('picking_date'),date('Y-m-d')),'delivery_id'=>$deliveryId,'delivery_no'=>$od->delivery_no,'id_sales_order'=>$od->id_sales_order,'no_sales_order'=>$od->no_sales_order,'customer_code'=>$od->customer_code,'customer_name'=>$od->customer_name,'warehouse'=>pick_input('warehouse'),'picker'=>pick_input('picker')?:$user,'status'=>'CREATED','remarks'=>pick_input('remarks'),'created_by'=>$user,'updated_by'=>$user,'updated_at'=>date('Y-m-d H:i:s'));
  if(!$db->insert('erp_picking',$header))pick_json(array('status'=>'error','error_message'=>$db->getErrorMessage()));$pickId=(int)$db->last_insert_id();
  $mat=$_POST['material_code'];$matName=$_POST['material_name'];$store=$_POST['store'];$delQty=$_POST['delivery_qty'];$pickQty=$_POST['picked_qty'];$uom=$_POST['uom'];$batch=$_POST['batch_no'];$bin=$_POST['source_bin'];$rem=$_POST['item_remarks'];$saved=0;$line=10;
  foreach($detailIds as $i=>$did){$pq=(float)str_replace(',','.',$pickQty[$i]);if($pq<=0)continue;$dq=(float)str_replace(',','.',$delQty[$i]);$db->insert('erp_picking_detail',array('picking_id'=>$pickId,'delivery_detail_id'=>(int)$did,'line_no'=>$line,'material_code'=>$mat[$i],'material_name'=>$matName[$i],'store'=>$store[$i],'delivery_qty'=>$dq,'picked_qty'=>$pq,'uom'=>$uom[$i],'batch_no'=>$batch[$i],'source_bin'=>$bin[$i],'remarks'=>$rem[$i]));$db->query("UPDATE erp_outbound_delivery_detail SET picked_qty=LEAST(delivery_qty,COALESCE(picked_qty,0)+?) WHERE id=?",array($pq,(int)$did));$line+=10;$saved++;}
  if($saved<=0)pick_json(array('status'=>'error','error_message'=>'Minimal satu picked qty lebih dari 0.'));
  $sum=$db->fetch("SELECT COALESCE(SUM(delivery_qty),0) dq,COALESCE(SUM(picked_qty),0) pq FROM erp_outbound_delivery_detail WHERE delivery_id=?",array($deliveryId));$pickStatus=((float)$sum->pq<=0)?'NOT_STARTED':(((float)$sum->pq>=(float)$sum->dq)?'COMPLETE':'PARTIAL');$odStatus=$pickStatus==='COMPLETE'?'PICKED':'PICKING';$pickDocStatus=$pickStatus==='COMPLETE'?'PICKED':'IN_PROCESS';
  $db->update('erp_picking',array('status'=>$pickDocStatus,'updated_by'=>$user,'updated_at'=>date('Y-m-d H:i:s')),'id',$pickId);$db->update('erp_outbound_delivery',array('picking_status'=>$pickStatus,'status'=>$odStatus,'updated_by'=>$user,'updated_at'=>date('Y-m-d H:i:s')),'id',$deliveryId);
  if(function_exists('simpan_log'))simpan_log('User '.$user.' membuat Picking '.$pickingNo.' dari Outbound Delivery '.$od->delivery_no.' pada '.date('Y-m-d H:i:s'),$user);
  pick_json(array('status'=>'good','id'=>$pickId,'picking_no'=>$pickingNo));
}

if($act==='detail'){
  session_check_json();$id=(int)pick_input('id');$h=$db->fetch(pick_select_sql().pick_base_sql()." WHERE pk.id=? LIMIT 1",array($id));if(!$h){echo '<div class="alert alert-danger">Picking tidak ditemukan.</div>';exit;}$items=$db->query("SELECT * FROM erp_picking_detail WHERE picking_id=? ORDER BY line_no,id",array($id)); ?>
  <h3 style="margin-top:0"><?=pick_h($h->picking_no);?> <small>Delivery <?=pick_h($h->delivery_no);?></small></h3><p><?=pick_status_label($h->status);?> SO <?=pick_h($h->no_sales_order);?> | <?=pick_h($h->customer_name);?></p>
  <div class="row"><div class="col-md-6"><table class="table table-bordered table-condensed"><tr><th>'.sd_h('sales_date', 'Date').'</th><td><?=pick_h($h->picking_date);?></td></tr><tr><th>'.sd_h('sales_warehouse', 'Warehouse').'</th><td><?=pick_h($h->warehouse);?></td></tr></table></div><div class="col-md-6"><table class="table table-bordered table-condensed"><tr><th>Picker</th><td><?=pick_h($h->picker);?></td></tr><tr><th>Progress</th><td><?=number_format((float)$h->picked_percent,2,',','.');?>%</td></tr></table></div></div>
  <div class="table-responsive"><table class="table table-bordered table-striped table-condensed"><thead><tr><th>'.sd_h('common_no', 'No').'</th><th>'.sd_h('sales_material', 'Material').'</th><th>Store</th><th class="text-right">Delivery Qty</th><th class="text-right">Picked Qty</th><th>'.sd_h('sales_uom', 'UOM').'</th><th>Batch</th><th>Source Bin</th><th>Remark</th></tr></thead><tbody><?php foreach($items as $it){ ?><tr><td><?=intval($it->line_no);?></td><td><strong><?=pick_h($it->material_code);?></strong><br><small><?=pick_h($it->material_name);?></small></td><td><?=pick_h($it->store);?></td><td class="text-right"><?=number_format((float)$it->delivery_qty,5,',','.');?></td><td class="text-right"><?=number_format((float)$it->picked_qty,5,',','.');?></td><td><?=pick_h($it->uom);?></td><td><?=pick_h($it->batch_no);?></td><td><?=pick_h($it->source_bin);?></td><td><?=pick_h($it->remarks);?></td></tr><?php } ?></tbody></table></div>
  <?php exit;
}

if($act==='excel'){
  $initialOutputBufferLevel=ob_get_level();ob_start();ini_set('display_errors','0');error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);require '../../inc/lib/PHPExcel.php';require_once '../../inc/excel_style_helper.php';PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $in=pick_filters();$rows=pick_load($db,$in);$from=pick_date($in['tgl_awal'],date('Y-01-01'));$to=pick_date($in['tgl_akhir'],date('Y-m-d'));$excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Picking'));$headers=array(erp_export_label("No"),erp_export_label("Picking No"),erp_export_label("Picking Date"),erp_export_label("Delivery No"),erp_export_label("Sales Order"),erp_export_label("Customer"),erp_export_label("Status"),erp_export_label("Items"),erp_export_label("Delivery Qty"),erp_export_label("Picked Qty"),erp_export_label("Picked %"),erp_export_label("Warehouse"),erp_export_label("Picker"),erp_export_label("Remarks"));foreach($headers as $c=>$h)$sheet->setCellValueByColumnAndRow($c,4,$h);$r=5;$n=1;foreach($rows as $row){$vals=array($n++,$row->picking_no,$row->picking_date,$row->delivery_no,$row->no_sales_order,$row->customer_name,$row->status,(float)$row->item_count,(float)$row->delivery_qty,(float)$row->picked_qty,(float)$row->picked_percent,$row->warehouse,$row->picker,$row->remarks);foreach($vals as $c=>$v)$sheet->setCellValueByColumnAndRow($c,$r,$v);$r++;}
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('PICKING REPORT - SAP SD/WM'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>14,'numeric_columns'=>array('I','J'),'decimal_columns'=>array('K'),'filters'=>array('Periode'=>$from.' s/d '.$to,'Customer'=>$in['customer']?:erp_export_all_text(),'Status'=>$in['status']?:erp_export_all_text(),'Picker'=>$in['picker']?:erp_export_all_text(),'Keyword'=>$in['keyword']),'widths'=>array('A'=>6,'B'=>18,'C'=>14,'D'=>18,'E'=>22,'F'=>28,'G'=>14,'H'=>10,'I'=>14,'J'=>14,'K'=>12,'L'=>18,'M'=>18,'N'=>40)));
  $tmp=erpkb_excel_temp_file('picking_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="picking_'.$from.'_sd_'.$to.'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
}
pick_json(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
