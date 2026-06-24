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
include "../../inc/config.php";session_check_json();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
include "billing_report_lib.php";
$act=isset($_GET['act'])?$_GET['act']:'';
switch($act){
  case 'detail':
    $id=(int)br_input('id');
    $h=$db->fetch(br_select_sql().br_base_sql()." WHERE si.id_sales=? LIMIT 1",array($id));
    if(!$h){echo '<div class="alert alert-warning">Billing document tidak ditemukan.</div>';break;}
    $items=$db->query("SELECT * FROM sales_invoice_detail WHERE id_sales=? ORDER BY id_sales_detail",array($id));
    $sj=$db->query("SELECT * FROM surat_jalan WHERE BINARY no_surat_jalan=BINARY ? OR BINARY no_invoice=BINARY ? OR BINARY no_sales_order=BINARY ? ORDER BY posting_date,id",array($h->no_do,$h->no_sales_invoice,$h->no_sales_order));
    echo '<h3 style="margin-top:0">'.br_h($h->no_sales_invoice?:$h->invoice_no).' <small>'.br_h($h->invoice_date.' / '.$h->valuta).'</small></h3><div class="row"><div class="col-sm-3"><strong>Bill To</strong><br>'.br_h($h->bill_name).'<br><small>'.br_h($h->bill_address).'</small></div><div class="col-sm-3"><strong>Ship To</strong><br>'.br_h($h->ship_name).'<br><small>'.br_h($h->ship_address).'</small></div><div class="col-sm-3"><strong>'.sd_h('sales_reference', 'Reference').'</strong><br>SO '.br_h($h->no_sales_order?:'-').'<br><small>SJ/DO '.br_h($h->surat_jalan_no?:'-').' | PO '.br_h($h->nopo?:'-').'</small></div><div class="col-sm-3"><strong>Due Status</strong><br>'.br_due_badge($h).'<br><small>Due '.br_h($h->due_date?:'-').' / '.(int)$h->overdue_days.' hari</small></div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><div class="well well-sm"><strong>DPP</strong><br>'.br_num($h->subtotal).'</div></div><div class="col-sm-3"><div class="well well-sm"><strong>PPN</strong><br>'.br_num($h->tax_amount).'</div></div><div class="col-sm-3"><div class="well well-sm"><strong>Grand Total</strong><br>'.br_num($h->grand_total).'</div></div><div class="col-sm-3"><div class="well well-sm"><strong>Total Qty</strong><br>'.br_num($h->total_qty,4).'</div></div></div>';
    echo '<h4>Billing Items</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>'.sd_h('sales_material', 'Material').'</th><th>Customer Material</th><th class="text-right">'.sd_h('sales_qty', 'Qty').'</th><th>'.sd_h('sales_uom', 'UOM').'</th><th class="text-right">'.sd_h('sales_price', 'Price').'</th><th class="text-right">'.sd_h('sales_amount', 'Amount').'</th></tr></thead><tbody>';
    foreach($items as $it)echo '<tr><td><strong>'.br_h($it->kd_barang).'</strong><br><small>'.br_h($it->nm_barang).'</small></td><td><strong>'.br_h($it->material_number?:'-').'</strong><br><small>'.br_h($it->material_description?:'-').'</small></td><td class="text-right">'.br_num($it->qty,4).'</td><td>'.br_h($it->unit).'</td><td class="text-right">'.br_num($it->harga).'</td><td class="text-right">'.br_num($it->nilai).'</td></tr>';
    echo '</tbody><tfoot><tr class="bg-gray"><th colspan="2">TOTAL</th><th class="text-right">'.br_num($h->total_qty,4).'</th><th></th><th></th><th class="text-right">'.br_num($h->subtotal).'</th></tr></tfoot></table>';
    echo '<div class="row"><div class="col-md-6"><h4>Delivery References</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>'.sd_h('sales_surat_jalan', 'Surat Jalan').'</th><th>Posting</th><th>'.sd_h('common_status', 'Status').'</th><th class="text-right">'.sd_h('sales_qty', 'Qty').'</th></tr></thead><tbody>';
    foreach($sj as $s)echo '<tr><td>'.br_h($s->no_surat_jalan).'</td><td>'.br_h($s->posting_date?:$s->tgl_surat_jalan).'</td><td>'.br_h(strtoupper($s->status)).'</td><td class="text-right">'.br_num($s->total_qty,4).'</td></tr>';
    $bank=trim(strip_tags(html_entity_decode((string)$h->bank_detail,ENT_QUOTES,'UTF-8')));
    echo '</tbody></table></div><div class="col-md-6"><h4>Commercial</h4><table class="table table-bordered table-condensed"><tr><th>'.sd_h('sales_tax', 'Tax').'</th><td>'.br_tax_badge($h->tax).'</td></tr><tr><th>Term</th><td>'.br_h($h->term?:'-').'</td></tr><tr><th>Bank</th><td>'.nl2br(br_h($bank?:'-')).'</td></tr><tr><th>Signed By</th><td>'.br_h($h->ttd?:'-').'</td></tr></table></div></div>';
    if($h->catatan)echo '<div class="alert alert-info"><strong>Catatan:</strong> '.br_h($h->catatan).'</div>';
    break;
  case 'excel':
    $initial=ob_get_level();ob_start();require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $f=br_filters();$rows=br_load_rows($db,$f,0,0);$excel=new PHPExcel();$sh=$excel->setActiveSheetIndex(0);$sh->setTitle(erp_export_sheet_title('Billing Report'));
    $heads=array(erp_export_label("No"),erp_export_label("Invoice No"),erp_export_label("Invoice Date"),erp_export_label("Bill To"),erp_export_label("Ship To"),erp_export_label("Sales Order"),erp_export_label("SJ/DO"),erp_export_label("PO No"),erp_export_label("Currency"),erp_export_label("Sales"),erp_export_label("Term"),erp_export_label("Due Date"),erp_export_label("Overdue Days"),erp_export_label("Tax Status"),erp_export_label("Item Count"),erp_export_label("Qty"),erp_export_label("DPP"),erp_export_label("PPN"),erp_export_label("Grand Total"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Customer Material"),erp_export_label("UOM"),erp_export_label("Price"),erp_export_label("Line Amount"),erp_export_label("Notes"));
    foreach($heads as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$v);$r=5;$n=1;
    foreach($rows as $row){$details=$db->query("SELECT * FROM sales_invoice_detail WHERE id_sales=? ORDER BY id_sales_detail",array($row->id_sales));if(!$details)$details=array((object)array('kd_barang'=>'','nm_barang'=>'','material_number'=>'','material_description'=>'','unit'=>'','harga'=>0,'nilai'=>0,'qty'=>0));foreach($details as $it){$vals=array($n++,$row->no_sales_invoice?:$row->invoice_no,$row->invoice_date,$row->bill_name,$row->ship_name,$row->no_sales_order,$row->surat_jalan_no,$row->nopo,$row->valuta,$row->sales_id,$row->term,$row->due_date,(int)$row->overdue_days,$row->tax==='1'?'TAXED':'NO_TAX',(int)$row->item_count,(float)$row->total_qty,(float)$row->subtotal,(float)$row->tax_amount,(float)$row->grand_total,$it->kd_barang,$it->nm_barang,trim($it->material_number.' '.$it->material_description),$it->unit,(float)$it->harga,(float)$it->nilai,$row->catatan);foreach($vals as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r,$v);$r++;}}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sh,'title'=>erp_export_title('BILLING REPORT - SAP SD BILLING ANALYTICS'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>26,'numeric_columns'=>array('M','O','P','Q','R','S','X','Y'),'filters'=>array('Invoice Date'=>$f['tgl_awal'].' s/d '.$f['tgl_akhir'],'Customer'=>$f['customer']?:erp_export_all_text(),'Currency'=>$f['currency']?:erp_export_all_text(),'Tax'=>$f['tax_status']?:erp_export_all_text(),'Due'=>$f['due_status']?:erp_export_all_text())));
    $tmp=erpkb_excel_temp_file('billing_report_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="billing_report_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:header('Content-Type: application/json');echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
}
?>
