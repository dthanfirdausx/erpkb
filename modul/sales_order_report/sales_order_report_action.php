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
session_check_json();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
include "sales_order_report_lib.php";
$act=isset($_GET['act'])?$_GET['act']:'';
switch($act){
  case 'detail':
    $id=isset($_POST['id'])?(int)$_POST['id']:0;
    $input=sor_filters();$input['tgl_awal']='2000-01-01';$input['tgl_akhir']='2099-12-31';
    $h=$db->fetch(sor_select_sql().sor_base_sql()." WHERE v.id_sales_order=? LIMIT 1",array($id));
    if(!$h){echo '<div class="alert alert-warning">Sales Order tidak ditemukan.</div>';break;}
    $items=$db->query("SELECT d.*,b.nm_barang,b.satuan FROM sales_order_detail d LEFT JOIN barang b ON b.kd_barang=d.kd_barang WHERE d.id_sales_order=? ORDER BY d.id_detail",array($id));
    $prod=$db->query("SELECT no_production_order,order_strategy,material_code,material_name,order_qty,completed_qty,scrap_qty,uom,start_date,finish_date,status FROM production_order WHERE id_sales_order=? OR no_sales_order=? ORDER BY id_production_order",array($id,$h->no_sales_order));
    $deliveries=$db->query("SELECT no_surat_jalan,tgl_surat_jalan,posting_date,total_qty,status,gi_no,packing_list_no,no_invoice FROM surat_jalan WHERE id_sales_order=? OR no_sales_order=? ORDER BY tgl_surat_jalan,id",array($id,$h->no_sales_order));
    $invoices=$db->query("SELECT si.id_sales,COALESCE(si.no_sales_invoice,si.invoice_no) invoice_no,si.invoice_date,si.valuta,si.term,COUNT(sid.id_sales_detail) item_count,COALESCE(SUM(sid.nilai),0) amount FROM sales_invoice si LEFT JOIN sales_invoice_detail sid ON sid.id_sales=si.id_sales WHERE si.no_sales_order=? GROUP BY si.id_sales ORDER BY si.invoice_date,si.id_sales",array($h->no_sales_order));
    echo '<h3 style="margin-top:0">'.sor_h($h->no_sales_order).' <small>'.sor_h($h->approval_status.' / '.$h->status_so).'</small></h3>';
    echo '<div class="row"><div class="col-sm-3"><strong>'.sd_h('sales_customer', 'Customer').'</strong><br>'.sor_h($h->customer_name).'<br><small>'.sor_h($h->customer_address).'</small></div><div class="col-sm-3"><strong>'.sd_h('sales_customer_po', 'Customer PO').'</strong><br>'.sor_h($h->no_po?:'-').'<br><small>Quotation ID: '.sor_h($h->id_quotation?:'-').'</small></div><div class="col-sm-3"><strong>Schedule</strong><br>SO '.sor_h($h->so_date).'<br><small>Delivery '.sor_h($h->delivery_date?:'-').'</small></div><div class="col-sm-3"><strong>Commercial</strong><br>'.sor_h($h->currency?:'-').' / Term '.sor_h($h->term?:'-').'<br><small>'.sor_h($h->delivery_term?:'-').'</small></div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><div class="well well-sm"><strong>Order Qty</strong><br>'.sor_num($h->qty_so,4).'</div></div><div class="col-sm-3"><div class="well well-sm"><strong>Produced</strong><br>'.sor_num($h->qty_produksi,4).' ('.sor_num($h->production_percent).'%)</div></div><div class="col-sm-3"><div class="well well-sm"><strong>Delivered</strong><br>'.sor_num($h->qty_kirim,4).' ('.sor_num($h->delivery_percent).'%)</div></div><div class="col-sm-3"><div class="well well-sm"><strong>Billed Qty</strong><br>'.sor_num($h->invoice_qty,4).' ('.sor_num($h->billing_percent).'%)<br><small>Amount '.sor_num($h->invoice_amount).'</small></div></div></div>';
    echo '<h4>Sales Order Items</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>'.sd_h('sales_material', 'Material').'</th><th>Description</th><th class="text-right">'.sd_h('sales_qty', 'Qty').'</th><th>'.sd_h('sales_uom', 'UOM').'</th><th class="text-right">'.sd_h('sales_price', 'Price').'</th><th class="text-right">'.sd_h('sales_amount', 'Amount').'</th><th>Store</th><th>'.sd_h('common_status', 'Status').'</th></tr></thead><tbody>';
    foreach($items as $it)echo '<tr><td><strong>'.sor_h($it->kd_barang).'</strong></td><td>'.sor_h($it->nm_barang?:$it->ket).'</td><td class="text-right">'.sor_num($it->qty,4).'</td><td>'.sor_h($it->satuan).'</td><td class="text-right">'.sor_num($it->price).'</td><td class="text-right">'.sor_num($it->nilai).'</td><td>'.sor_h($it->store).'</td><td>'.sor_h($it->status).'</td></tr>';
    echo '</tbody></table><div class="row"><div class="col-md-4"><h4>Production Orders</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Prod Order</th><th>'.sd_h('sales_material', 'Material').'</th><th class="text-right">'.sd_h('sales_qty', 'Qty').'</th><th>'.sd_h('common_status', 'Status').'</th></tr></thead><tbody>';
    foreach($prod as $p)echo '<tr><td>'.sor_h($p->no_production_order).'<br><small>'.sor_h($p->order_strategy).'</small></td><td>'.sor_h($p->material_code).'<br><small>'.sor_h($p->material_name).'</small></td><td class="text-right">'.sor_num($p->completed_qty,4).' / '.sor_num($p->order_qty,4).' '.sor_h($p->uom).'</td><td>'.sor_h($p->status).'</td></tr>';
    echo '</tbody></table></div><div class="col-md-4"><h4>Deliveries / Surat Jalan</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Document</th><th>'.sd_h('sales_date', 'Date').'</th><th class="text-right">'.sd_h('sales_qty', 'Qty').'</th><th>'.sd_h('common_status', 'Status').'</th></tr></thead><tbody>';
    foreach($deliveries as $d)echo '<tr><td>'.sor_h($d->no_surat_jalan).'<br><small>GI '.sor_h($d->gi_no?:'-').' / PL '.sor_h($d->packing_list_no?:'-').'</small></td><td>'.sor_h($d->tgl_surat_jalan?:$d->posting_date).'</td><td class="text-right">'.sor_num($d->total_qty,4).'</td><td>'.sor_h($d->status).'</td></tr>';
    echo '</tbody></table></div><div class="col-md-4"><h4>Billing Documents</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>Invoice</th><th>'.sd_h('sales_date', 'Date').'</th><th>'.sd_h('sales_items', 'Items').'</th><th class="text-right">'.sd_h('sales_amount', 'Amount').'</th></tr></thead><tbody>';
    foreach($invoices as $inv)echo '<tr><td>'.sor_h($inv->invoice_no).'</td><td>'.sor_h($inv->invoice_date).'</td><td>'.(int)$inv->item_count.'</td><td class="text-right">'.sor_num($inv->amount).'</td></tr>';
    echo '</tbody></table></div></div>';
    if($h->catatan)echo '<div class="alert alert-info"><strong>Catatan:</strong> '.sor_h($h->catatan).'</div>';
    break;
  case 'excel':
    $initial=ob_get_level();ob_start();require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $filters=sor_filters();$rows=sor_load_rows($db,$filters,0,0);
    $excel=new PHPExcel();$sh=$excel->setActiveSheetIndex(0);$sh->setTitle(erp_export_sheet_title('SO Report'));
    $heads=array(erp_export_label("No"),erp_export_label("Sales Order"),erp_export_label("SO Date"),erp_export_label("Customer"),erp_export_label("Customer PO"),erp_export_label("Currency"),erp_export_label("Sales"),erp_export_label("Approval"),erp_export_label("SO Status"),erp_export_label("Fulfillment"),erp_export_label("Delivery Date"),erp_export_label("Item Count"),erp_export_label("Order Qty"),erp_export_label("Produced Qty"),erp_export_label("Production %"),erp_export_label("Delivered Qty"),erp_export_label("Delivery %"),erp_export_label("Order Amount"),erp_export_label("Invoice Count"),erp_export_label("Invoice Qty"),erp_export_label("Invoice Amount"),erp_export_label("Billing %"),erp_export_label("Production Orders"),erp_export_label("Delivery Docs"),erp_export_label("Invoice Docs"),erp_export_label("Remarks"));
    foreach($heads as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$v);
    $r=5;$n=1;foreach($rows as $row){
      $fulfillment=((int)$row->is_overdue===1)?'OVERDUE':(((float)$row->qty_so>0&&(float)$row->qty_kirim>=(float)$row->qty_so)?'COMPLETED':(((float)$row->qty_kirim>0)?'PARTIAL':(((float)$row->qty_produksi>0)?'READY/PROD':'OPEN')));
      $vals=array($n++,$row->no_sales_order,$row->so_date,$row->customer_name,$row->no_po,$row->currency,$row->sales_id,$row->approval_status,$row->status_so,$fulfillment,$row->delivery_date,(int)$row->item_count,(float)$row->qty_so,(float)$row->qty_produksi,(float)$row->production_percent,(float)$row->qty_kirim,(float)$row->delivery_percent,(float)$row->total_amount,(int)$row->invoice_count,(float)$row->invoice_qty,(float)$row->invoice_amount,(float)$row->billing_percent,$row->production_orders,$row->delivery_docs,$row->invoice_docs,$row->catatan);
      foreach($vals as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r,$v);$r++;
    }
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sh,'title'=>erp_export_title('SALES ORDER REPORT'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>26,'numeric_columns'=>array('L','M','N','O','P','Q','R','S','T','U','V'),'filters'=>array('SO Date'=>$filters['tgl_awal'].' s/d '.$filters['tgl_akhir'],'Customer'=>$filters['customer']?:erp_export_all_text(),'Approval'=>$filters['approval_status']?:erp_export_all_text(),'Fulfillment'=>$filters['fulfillment_status']?:erp_export_all_text())));
    $tmp=erpkb_excel_temp_file('sales_order_report_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);
    if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}
    while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="sales_order_report_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:
    header('Content-Type: application/json');echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
}
?>
