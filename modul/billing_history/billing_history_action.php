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
function bha_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function bha_num($v,$d=2){return number_format((float)$v,$d,',','.');}
$act=isset($_GET['act'])?$_GET['act']:'';
switch($act){
  case 'detail':
    $id=isset($_POST['id'])?(int)$_POST['id']:0;
    $h=$db->fetch("SELECT si.*,bill.nama bill_name,bill.alamat bill_address,ship.nama ship_name,ship.alamat ship_address FROM sales_invoice si LEFT JOIN penerima bill ON bill.kode_penerima=si.bill_to LEFT JOIN penerima ship ON ship.kode_penerima=si.ship_to WHERE si.id_sales=? LIMIT 1",array($id));
    if(!$h){echo '<div class="alert alert-warning">Billing document tidak ditemukan.</div>';break;}
    $rows=$db->query("SELECT * FROM sales_invoice_detail WHERE id_sales=? ORDER BY id_sales_detail",array($id));
    $items=array();$subtotal=0;$qty=0;foreach($rows as $r){$items[]=$r;$subtotal+=(float)$r->nilai;$qty+=(float)$r->qty;}
    $taxAmt=($h->tax==='1')?$subtotal*0.11:0;$grand=$subtotal+$taxAmt;
    echo '<h3 style="margin-top:0">'.bha_h($h->no_sales_invoice?:$h->invoice_no).' <small>'.bha_h($h->invoice_date.' / '.$h->valuta).'</small></h3><div class="row"><div class="col-sm-3"><strong>Bill To</strong><br>'.bha_h($h->bill_name?:$h->bill_to).'<br><small>'.bha_h($h->bill_address).'</small></div><div class="col-sm-3"><strong>Ship To</strong><br>'.bha_h($h->ship_name?:$h->ship_to).'<br><small>'.bha_h($h->ship_address).'</small></div><div class="col-sm-3"><strong>'.sd_h('sales_reference', 'Reference').'</strong><br>SO '.bha_h($h->no_sales_order?:'-').'<br><small>SJ/DO '.bha_h($h->no_do?:'-').' | PO '.bha_h($h->nopo?:'-').'</small></div><div class="col-sm-3"><strong>Terms</strong><br>'.bha_h($h->term?:'-').'<br><small>Ship Date '.bha_h($h->ship_date?:'-').'</small></div></div><hr>';
    echo '<div class="row"><div class="col-sm-3"><div class="well well-sm"><strong>Subtotal</strong><br>'.bha_num($subtotal).'</div></div><div class="col-sm-3"><div class="well well-sm"><strong>PPN</strong><br>'.bha_num($taxAmt).'</div></div><div class="col-sm-3"><div class="well well-sm"><strong>Grand Total</strong><br>'.bha_num($grand).'</div></div><div class="col-sm-3"><div class="well well-sm"><strong>Total Qty</strong><br>'.bha_num($qty,4).'</div></div></div>';
    echo '<h4>Billing Items</h4><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>'.sd_h('sales_material', 'Material').'</th><th>Material Customer</th><th class="text-right">'.sd_h('sales_qty', 'Qty').'</th><th>'.sd_h('sales_uom', 'UOM').'</th><th class="text-right">'.sd_h('sales_price', 'Price').'</th><th class="text-right">'.sd_h('sales_amount', 'Amount').'</th></tr></thead><tbody>';
    foreach($items as $r)echo '<tr><td><strong>'.bha_h($r->kd_barang).'</strong><br><small>'.bha_h($r->nm_barang).'</small></td><td><strong>'.bha_h($r->material_number?:'-').'</strong><br><small>'.bha_h($r->material_description?:'-').'</small></td><td class="text-right">'.bha_num($r->qty,4).'</td><td>'.bha_h($r->unit).'</td><td class="text-right">'.bha_num($r->harga).'</td><td class="text-right">'.bha_num($r->nilai).'</td></tr>';
    echo '</tbody><tfoot><tr class="bg-gray"><th colspan="2">TOTAL</th><th class="text-right">'.bha_num($qty,4).'</th><th></th><th></th><th class="text-right">'.bha_num($subtotal).'</th></tr></tfoot></table>';
    if($h->catatan)echo '<hr><strong>Catatan</strong><br>'.bha_h($h->catatan);
    break;
  case 'excel':
    $initial=ob_get_level();ob_start();require_once "../../inc/lib/PHPExcel.php";require_once "../../inc/excel_style_helper.php";PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $from=isset($_GET['tgl_awal'])?$_GET['tgl_awal']:date('Y-m-01');$to=isset($_GET['tgl_akhir'])?$_GET['tgl_akhir']:date('Y-m-d');$p=array($from,$to);$w=" WHERE si.invoice_date BETWEEN ? AND ? ";
    if(!empty($_GET['customer'])){$w.=" AND si.bill_to=? ";$p[]=$_GET['customer'];}if(!empty($_GET['currency'])){$w.=" AND si.valuta=? ";$p[]=$_GET['currency'];}if(!empty($_GET['tax_status'])){if($_GET['tax_status']==='TAXED')$w.=" AND si.tax='1' ";if($_GET['tax_status']==='NO_TAX')$w.=" AND (si.tax='0' OR si.tax IS NULL OR si.tax='') ";}
    $rows=$db->query("SELECT si.*,bill.nama bill_name,ship.nama ship_name,d.kd_barang,d.nm_barang,d.material_number,d.material_description,d.qty,d.unit,d.harga,d.nilai FROM sales_invoice si LEFT JOIN penerima bill ON bill.kode_penerima=si.bill_to LEFT JOIN penerima ship ON ship.kode_penerima=si.ship_to LEFT JOIN sales_invoice_detail d ON d.id_sales=si.id_sales $w ORDER BY si.invoice_date DESC,si.id_sales DESC,d.id_sales_detail",$p);
    $excel=new PHPExcel();$sh=$excel->setActiveSheetIndex(0);$sh->setTitle(erp_export_sheet_title('Billing History'));$heads=array(erp_export_label("No"),erp_export_label("Invoice No"),erp_export_label("Invoice Date"),erp_export_label("Bill To"),erp_export_label("Ship To"),erp_export_label("Sales Order"),erp_export_label("SJ/DO"),erp_export_label("PO No"),erp_export_label("Term"),erp_export_label("Currency"),erp_export_label("Tax"),erp_export_label("Material"),erp_export_label("Material Name"),erp_export_label("Customer Material"),erp_export_label("Qty"),erp_export_label("UOM"),erp_export_label("Price"),erp_export_label("Amount"));foreach($heads as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$v);$r=5;$n=1;foreach($rows as $row){$vals=array($n++,$row->no_sales_invoice?:$row->invoice_no,$row->invoice_date,$row->bill_name?:$row->bill_to,$row->ship_name?:$row->ship_to,$row->no_sales_order,$row->no_do,$row->nopo,$row->term,$row->valuta,$row->tax==='1'?'TAXED':'NO_TAX',$row->kd_barang,$row->nm_barang,trim($row->material_number.' '.$row->material_description),(float)$row->qty,$row->unit,(float)$row->harga,(float)$row->nilai);foreach($vals as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r,$v);$r++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sh,'title'=>erp_export_title('BILLING HISTORY'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>18,'numeric_columns'=>array('O','Q','R'),'filters'=>array('Invoice Date'=>$from.' s/d '.$to,'Customer'=>isset($_GET['customer'])?$_GET['customer']:erp_export_all_text(),'Currency'=>isset($_GET['currency'])?$_GET['currency']:erp_export_all_text())));$tmp=erpkb_excel_temp_file('billing_history_');PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp);$size=@filesize($tmp);$sig=@file_get_contents($tmp,false,null,0,2);if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;}while(ob_get_level()>$initial)ob_end_clean();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="billing_history_'.date('Ymd_His').'.xlsx"');header('Content-Length: '.$size);header('Cache-Control: max-age=0');header('Pragma: public');readfile($tmp);@unlink($tmp);exit;
  default:header('Content-Type: application/json');echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
}
?>
