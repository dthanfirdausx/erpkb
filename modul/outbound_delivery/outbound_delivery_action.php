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
include "outbound_delivery_lib.php";
$act=isset($_GET['act'])?$_GET['act']:'';
function obd_json($payload){header('Content-Type: application/json; charset=utf-8');echo json_encode($payload);exit;}

if($act==='so_search'){
  session_check_json();
  $term=obd_input('term');$params=array();$where=" WHERE so.approval_status='APPROVED' ";
  if($term!==''){$kw='%'.$term.'%';$where.=" AND (so.no_sales_order LIKE ? OR so.no_po LIKE ? OR p.nama LIKE ? OR so.kode_penerima LIKE ?) ";for($i=0;$i<4;$i++)$params[]=$kw;}
  $rows=$db->query("SELECT so.id_sales_order,so.no_sales_order,so.no_po,so.kode_penerima,p.nama,so.shipping_address FROM sales_order so LEFT JOIN penerima p ON p.kode_penerima=so.kode_penerima $where ORDER BY so.so_date DESC LIMIT 30",$params);
  $results=array();foreach($rows as $r)$results[]=array('id'=>$r->id_sales_order,'text'=>$r->no_sales_order.' - '.$r->nama.' - PO '.$r->no_po,'address'=>$r->shipping_address);
  obd_json(array('results'=>$results));
}

if($act==='so_items'){
  session_check_json();
  $id=(int)obd_input('id_sales_order');
  $rows=$db->query("SELECT d.*,b.nm_barang,b.satuan FROM sales_order_detail d LEFT JOIN barang b ON b.kd_barang=d.kd_barang WHERE d.id_sales_order=? ORDER BY d.id_detail",array($id));
  $no=1;$count=0;foreach($rows as $r){$count++;$amount=(float)$r->qty*(float)$r->price; ?>
    <tr>
      <td class="text-center"><?=$no++;?><input type="hidden" name="sales_order_detail_id[]" value="<?=intval($r->id_detail);?>"><input type="hidden" name="material_code[]" value="<?=obd_h($r->kd_barang);?>"><input type="hidden" name="material_name[]" value="<?=obd_h($r->nm_barang);?>"></td>
      <td><strong><?=obd_h($r->kd_barang);?></strong><br><small><?=obd_h($r->nm_barang);?></small></td>
      <td><input name="store[]" class="form-control" value="<?=obd_h($r->store);?>"></td>
      <td class="text-right"><input name="order_qty[]" class="form-control text-right" readonly value="<?=obd_h($r->qty);?>"></td>
      <td><input name="delivery_qty[]" class="form-control text-right delivery-qty" value="<?=obd_h($r->qty);?>"></td>
      <td><input name="uom[]" class="form-control" value="<?=obd_h($r->satuan);?>"></td>
      <td><input name="price[]" class="form-control text-right item-price" value="<?=obd_h($r->price);?>"></td>
      <td><input class="form-control text-right item-amount" readonly value="<?=number_format($amount,2,'.','');?>"></td>
      <td><input name="item_remarks[]" class="form-control" value="<?=obd_h($r->ket);?>"></td>
    </tr>
  <?php }
  if($count===0)echo '<tr><td colspan="9" class="text-center text-muted">Sales Order tidak memiliki item.</td></tr>';
  exit;
}

if($act==='save'){
  session_check_json();
  $username=obd_username();$soId=(int)obd_input('id_sales_order');
  $so=$db->fetch("SELECT so.*,p.nama AS customer_name FROM sales_order so LEFT JOIN penerima p ON p.kode_penerima=so.kode_penerima WHERE so.id_sales_order=? LIMIT 1",array($soId));
  if(!$so)obd_json(array('status'=>'error','error_message'=>'Sales Order tidak ditemukan.'));
  if($so->approval_status!=='APPROVED')obd_json(array('status'=>'error','error_message'=>'Sales Order harus approved sebelum dibuat Outbound Delivery.'));
  $detailIds=isset($_POST['sales_order_detail_id'])&&is_array($_POST['sales_order_detail_id'])?$_POST['sales_order_detail_id']:array();
  if(!count($detailIds))obd_json(array('status'=>'error','error_message'=>'Item delivery wajib diisi.'));
  $deliveryNo=obd_next_no($db);
  $header=array('delivery_no'=>$deliveryNo,'delivery_date'=>obd_valid_date(obd_input('delivery_date'),date('Y-m-d')),'planned_gi_date'=>obd_input('planned_gi_date')?obd_valid_date(obd_input('planned_gi_date'),date('Y-m-d')):null,'id_sales_order'=>$soId,'no_sales_order'=>$so->no_sales_order,'customer_code'=>$so->kode_penerima,'customer_name'=>$so->customer_name,'shipping_point'=>obd_input('shipping_point'),'route'=>obd_input('route'),'carrier'=>obd_input('carrier'),'vehicle_no'=>obd_input('vehicle_no'),'driver_name'=>obd_input('driver_name'),'ship_to_address'=>obd_input('ship_to_address') ?: $so->shipping_address,'status'=>'CREATED','remarks'=>obd_input('remarks'),'created_by'=>$username,'updated_by'=>$username,'updated_at'=>date('Y-m-d H:i:s'));
  if(!$db->insert('erp_outbound_delivery',$header))obd_json(array('status'=>'error','error_message'=>$db->getErrorMessage()));
  $deliveryId=(int)$db->last_insert_id();$saved=0;$line=10;
  $mat=$_POST['material_code'];$matName=$_POST['material_name'];$store=$_POST['store'];$orderQty=$_POST['order_qty'];$delQty=$_POST['delivery_qty'];$uom=$_POST['uom'];$price=$_POST['price'];$remarks=$_POST['item_remarks'];
  foreach($detailIds as $i=>$detailId){$dq=(float)str_replace(',','.',$delQty[$i]);if($dq<=0)continue;$p=(float)str_replace(',','.',$price[$i]);$db->insert('erp_outbound_delivery_detail',array('delivery_id'=>$deliveryId,'sales_order_detail_id'=>(int)$detailId,'line_no'=>$line,'material_code'=>$mat[$i],'material_name'=>$matName[$i],'store'=>$store[$i],'order_qty'=>(float)$orderQty[$i],'delivery_qty'=>$dq,'uom'=>$uom[$i],'price'=>$p,'amount'=>$dq*$p,'remarks'=>$remarks[$i]));$line+=10;$saved++;}
  if($saved<=0)obd_json(array('status'=>'error','error_message'=>'Minimal satu item delivery qty lebih dari 0.'));
  if(function_exists('simpan_log'))simpan_log('User '.$username.' membuat Outbound Delivery '.$deliveryNo.' dari Sales Order '.$so->no_sales_order.' pada '.date('Y-m-d H:i:s'),$username);
  obd_json(array('status'=>'good','id'=>$deliveryId,'delivery_no'=>$deliveryNo));
}

if($act==='detail'){
  session_check_json();$id=(int)obd_input('id');
  $h=$db->fetch(obd_select_sql().obd_base_sql()." WHERE od.id=? LIMIT 1",array($id));if(!$h){echo '<div class="alert alert-danger">Delivery tidak ditemukan.</div>';exit;}
  $items=$db->query("SELECT * FROM erp_outbound_delivery_detail WHERE delivery_id=? ORDER BY line_no,id",array($id));
  ?>
  <div class="row"><div class="col-md-8"><h3 style="margin-top:0"><?=obd_h($h->delivery_no);?> <small>SO <?=obd_h($h->no_sales_order);?></small></h3><p><?=obd_status_label($h->status);?> <?=obd_small_label($h->picking_status);?> <?=obd_small_label($h->packing_status);?> <?=obd_small_label($h->gi_status);?></p></div><div class="col-md-4 text-right"><a href="<?=base_index();?>sales-order/detail/<?=intval($h->id_sales_order);?>" target="_blank" class="btn btn-info btn-sm">Open SO</a></div></div>
  <div class="row"><div class="col-md-6"><table class="table table-bordered table-condensed"><tr><th>'.sd_h('sales_customer', 'Customer').'</th><td><?=obd_h($h->customer_code.' - '.$h->customer_name);?></td></tr><tr><th>'.sd_h('sales_delivery_date', 'Delivery Date').'</th><td><?=obd_h($h->delivery_date);?></td></tr><tr><th>Planned GI</th><td><?=obd_h($h->planned_gi_date);?></td></tr><tr><th>Ship To</th><td><?=obd_h($h->ship_to_address);?></td></tr></table></div><div class="col-md-6"><table class="table table-bordered table-condensed"><tr><th>'.sd_h('sales_shipping_point', 'Shipping Point').'</th><td><?=obd_h($h->shipping_point);?></td></tr><tr><th>Route/Carrier</th><td><?=obd_h(trim($h->route.' / '.$h->carrier,' /'));?></td></tr><tr><th>Vehicle/Driver</th><td><?=obd_h(trim($h->vehicle_no.' / '.$h->driver_name,' /'));?></td></tr><tr><th>References</th><td><?=obd_h(trim($h->reference_packing_list.' '.$h->reference_surat_jalan.' '.$h->reference_gi));?></td></tr></table></div></div>
  <div class="table-responsive"><table class="table table-bordered table-striped table-condensed"><thead><tr><th>'.sd_h('common_no', 'No').'</th><th>'.sd_h('sales_material', 'Material').'</th><th>Store</th><th class="text-right">Order Qty</th><th class="text-right">Delivery Qty</th><th class="text-right">Picked</th><th class="text-right">Packed</th><th class="text-right">GI</th><th>'.sd_h('sales_uom', 'UOM').'</th><th class="text-right">'.sd_h('sales_amount', 'Amount').'</th><th>Remark</th></tr></thead><tbody>
  <?php foreach($items as $it){ ?><tr><td><?=intval($it->line_no);?></td><td><strong><?=obd_h($it->material_code);?></strong><br><small><?=obd_h($it->material_name);?></small></td><td><?=obd_h($it->store);?></td><td class="text-right"><?=number_format((float)$it->order_qty,5,',','.');?></td><td class="text-right"><?=number_format((float)$it->delivery_qty,5,',','.');?></td><td class="text-right"><?=number_format((float)$it->picked_qty,5,',','.');?></td><td class="text-right"><?=number_format((float)$it->packed_qty,5,',','.');?></td><td class="text-right"><?=number_format((float)$it->gi_qty,5,',','.');?></td><td><?=obd_h($it->uom);?></td><td class="text-right"><?=number_format((float)$it->amount,2,',','.');?></td><td><?=obd_h($it->remarks);?></td></tr><?php } ?>
  </tbody></table></div>
  <?php exit;
}

if($act==='excel'){
  $initialOutputBufferLevel=ob_get_level();ob_start();ini_set('display_errors','0');error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';require_once '../../inc/excel_style_helper.php';PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $input=obd_filters();$rows=obd_load_rows($db,$input);$from=obd_valid_date($input['tgl_awal'],date('Y-01-01'));$to=obd_valid_date($input['tgl_akhir'],date('Y-m-d'));
  $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('Outbound Delivery'));$headers=array(erp_export_label("No"),erp_export_label("Delivery No"),erp_export_label("Delivery Date"),erp_export_label("Planned GI"),erp_export_label("Sales Order"),erp_export_label("Customer"),erp_export_label("Status"),erp_export_label("Picking"),erp_export_label("Packing"),erp_export_label("GI"),erp_export_label("Items"),erp_export_label("Delivery Qty"),erp_export_label("GI Qty"),erp_export_label("GI %"),erp_export_label("Value"),erp_export_label("Shipping Point"),erp_export_label("Vehicle"),erp_export_label("Driver"),erp_export_label("Remarks"));foreach($headers as $c=>$h)$sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5;$n=1;foreach($rows as $row){$values=array($n++,$row->delivery_no,$row->delivery_date,$row->planned_gi_date,$row->no_sales_order,$row->customer_name,$row->status,$row->picking_status,$row->packing_status,$row->gi_status,(float)$row->item_count,(float)$row->delivery_qty,(float)$row->gi_qty,(float)$row->gi_percent,(float)$row->total_amount,$row->shipping_point,$row->vehicle_no,$row->driver_name,$row->remarks);foreach($values as $c=>$v)$sheet->setCellValueByColumnAndRow($c,$r,$v);$r++;}
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('OUTBOUND DELIVERY REPORT - SAP SD'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>19,'numeric_columns'=>array('L','M'),'decimal_columns'=>array('N'),'money_columns'=>array('O'),'filters'=>array('Periode'=>$from.' s/d '.$to,'Customer'=>$input['customer']?:erp_export_all_text(),'Status'=>$input['status']?:erp_export_all_text(),'Shipping Point'=>$input['shipping_point']?:erp_export_all_text(),'Keyword'=>$input['keyword']),'widths'=>array('A'=>6,'B'=>18,'C'=>14,'D'=>14,'E'=>22,'F'=>28,'G'=>12,'H'=>14,'I'=>14,'J'=>12,'K'=>10,'L'=>14,'M'=>14,'N'=>10,'O'=>16,'P'=>16,'Q'=>16,'R'=>18,'S'=>40)));
  $tmp=erpkb_excel_temp_file('outbound_delivery_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$signature=@file_get_contents($tmp,false,null,0,2);if(!$size||$signature!=='PK'){@unlink($tmp);while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
  while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="outbound_delivery_'.$from.'_sd_'.$to.'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
}
obd_json(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
