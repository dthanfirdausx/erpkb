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
include "sales_order_monitoring_lib.php";

$act=isset($_GET['act'])?$_GET['act']:'';
if($act==='detail'){
  session_check_json();
  $id=(int)som_input('id');
  $row=$db->fetch(som_select_sql().som_base_sql()." WHERE v.id_sales_order=? LIMIT 1",array($id));
  if(!$row){echo '<div class="alert alert-danger">Sales Order tidak ditemukan.</div>';exit;}
  $items=$db->query("SELECT d.*,b.nm_barang,b.satuan FROM sales_order_detail d LEFT JOIN barang b ON b.kd_barang=d.kd_barang WHERE d.id_sales_order=? ORDER BY d.id_detail",array($id));
  ?>
  <div class="row">
    <div class="col-md-8"><h3 style="margin-top:0"><?=som_h($row->no_sales_order);?> <small>PO <?=som_h($row->no_po);?></small></h3><p><?=som_status_label($row->status_so);?> <?=som_approval_label($row->approval_status);?> <?php if($row->is_overdue){ ?><span class="label label-danger">OVERDUE</span><?php } ?></p></div>
    <div class="col-md-4 text-right"><a href="<?=base_index();?>sales-order/detail/<?=intval($row->id_sales_order);?>" target="_blank" class="btn btn-info btn-sm"><i class="fa fa-external-link"></i> Open Sales Order</a></div>
  </div>
  <div class="row">
    <div class="col-md-6"><table class="table table-bordered table-condensed som-detail-table">
      <tr><th>'.sd_h('sales_customer', 'Customer').'</th><td><?=som_h(trim((string)$row->kode_penerima.' - '.(string)$row->nama,' -'));?></td></tr>
      <tr><th>SO Date</th><td><?=som_h($row->so_date);?></td></tr>
      <tr><th>'.sd_h('sales_delivery_date', 'Delivery Date').'</th><td><?=som_h($row->delivery_date);?></td></tr>
      <tr><th>Sales</th><td><?=som_h($row->sales_id ?: $row->user);?></td></tr>
      <tr><th>Shipping Address</th><td><?=som_h($row->shipping_address);?></td></tr>
    </table></div>
    <div class="col-md-6"><table class="table table-bordered table-condensed som-detail-table">
      <tr><th>Approval</th><td><?=som_h($row->approval_status.' '.$row->approved_by.' '.$row->approved_at);?></td></tr>
      <tr><th>Qty SO</th><td><?=number_format((float)$row->qty_so,5,',','.');?></td></tr>
      <tr><th>Qty Produksi</th><td><?=number_format((float)$row->qty_produksi,5,',','.');?> (<?=number_format((float)$row->production_percent,2,',','.');?>%)</td></tr>
      <tr><th>Qty Kirim</th><td><?=number_format((float)$row->qty_kirim,5,',','.');?> (<?=number_format((float)$row->delivery_percent,2,',','.');?>%)</td></tr>
      <tr><th>Value</th><td><?=som_h($row->currency);?> <?=number_format((float)$row->total_amount,2,',','.');?></td></tr>
    </table></div>
  </div>
  <h4>Sales Order Items</h4>
  <div class="table-responsive"><table class="table table-bordered table-striped table-condensed"><thead><tr><th>'.sd_h('common_no', 'No').'</th><th>'.sd_h('sales_material', 'Material').'</th><th>Store</th><th class="text-right">'.sd_h('sales_qty', 'Qty').'</th><th>'.sd_h('sales_uom', 'UOM').'</th><th class="text-right">'.sd_h('sales_price', 'Price').'</th><th class="text-right">Value</th><th>Remark</th></tr></thead><tbody>
  <?php $no=1;$tq=0;$tv=0;foreach($items as $it){$tq+=(float)$it->qty;$tv+=(float)$it->nilai; ?>
    <tr><td class="text-center"><?=$no++;?></td><td><strong><?=som_h($it->kd_barang);?></strong><br><small><?=som_h($it->nm_barang);?></small></td><td><?=som_h($it->store);?></td><td class="text-right"><?=number_format((float)$it->qty,5,',','.');?></td><td><?=som_h($it->satuan);?></td><td class="text-right"><?=number_format((float)$it->price,2,',','.');?></td><td class="text-right"><?=number_format((float)$it->nilai,2,',','.');?></td><td><?=som_h($it->ket);?></td></tr>
  <?php } ?>
  </tbody><tfoot><tr class="bg-gray"><th colspan="3" class="text-right">'.sd_h('sales_total', 'Total').'</th><th class="text-right"><?=number_format($tq,5,',','.');?></th><th colspan="2"></th><th class="text-right"><?=number_format($tv,2,',','.');?></th><th></th></tr></tfoot></table></div>
  <?php
  exit;
}

if($act==='excel'){
  $initialOutputBufferLevel=ob_get_level();ob_start();
  ini_set('display_errors','0');error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';require_once '../../inc/excel_style_helper.php';PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $input=som_filters();$rows=som_load_rows($db,$input);$from=som_valid_date($input['tgl_awal'],date('Y-01-01'));$to=som_valid_date($input['tgl_akhir'],date('Y-m-d'));
  $excel=new PHPExcel();$sheet=$excel->setActiveSheetIndex(0);$sheet->setTitle(erp_export_sheet_title('SO Monitoring'));
  $headers=array(erp_export_label("No"),erp_export_label("Sales Order"),erp_export_label("SO Date"),erp_export_label("Delivery Date"),erp_export_label("Customer"),erp_export_label("Customer PO"),erp_export_label("Approval"),erp_export_label("SO Status"),erp_export_label("Qty SO"),erp_export_label("Qty Produksi"),erp_export_label("Production %"),erp_export_label("Qty Kirim"),erp_export_label("Delivery %"),erp_export_label("Value"),erp_export_label("Currency"),erp_export_label("Sales"),erp_export_label("Overdue"),erp_export_label("Note"));
  foreach($headers as $c=>$h)$sheet->setCellValueByColumnAndRow($c,4,$h);
  $r=5;$n=1;foreach($rows as $row){$values=array($n++,$row->no_sales_order,$row->so_date,$row->delivery_date,$row->nama,$row->no_po,$row->approval_status,$row->status_so,(float)$row->qty_so,(float)$row->qty_produksi,(float)$row->production_percent,(float)$row->qty_kirim,(float)$row->delivery_percent,(float)$row->total_amount,$row->currency,$row->sales_id ?: $row->user,$row->is_overdue?'YES':'NO',$row->alasan ?: $row->catatan);foreach($values as $c=>$v)$sheet->setCellValueByColumnAndRow($c,$r,$v);$r++;}
  erpkb_excel_apply_standard_style($excel,array('sheet'=>$sheet,'title'=>erp_export_title('SALES ORDER MONITORING REPORT - SAP SD'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>18,'numeric_columns'=>array('I','J','L'),'decimal_columns'=>array('K','M'),'money_columns'=>array('N'),'filters'=>array('Periode'=>$from.' s/d '.$to,'Customer'=>$input['customer']?:erp_export_all_text(),'SO Status'=>$input['status_so']?:erp_export_all_text(),'Approval'=>$input['approval_status']?:erp_export_all_text(),'Delivery'=>$input['delivery_status']?:erp_export_all_text(),'Sales'=>$input['sales_person']?:erp_export_all_text(),'Overdue Only'=>$input['overdue_only']?'Ya':'Tidak','Keyword'=>$input['keyword']),'widths'=>array('A'=>6,'B'=>22,'C'=>14,'D'=>14,'E'=>28,'F'=>20,'G'=>14,'H'=>20,'I'=>14,'J'=>14,'K'=>13,'L'=>14,'M'=>13,'N'=>16,'O'=>10,'P'=>18,'Q'=>10,'R'=>40)));
  $tmp=erpkb_excel_temp_file('sales_order_monitoring_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$signature=@file_get_contents($tmp,false,null,0,2);
  if(!$size||$signature!=='PK'){@unlink($tmp);while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
  while(ob_get_level()>$initialOutputBufferLevel)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="sales_order_monitoring_'.$from.'_sd_'.$to.'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
?>
