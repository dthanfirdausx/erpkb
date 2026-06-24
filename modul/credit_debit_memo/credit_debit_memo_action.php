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
if (session_status() === PHP_SESSION_NONE) session_start();
include "../../inc/config.php";
require_once "../../inc/accounting_journal.php";
session_check_json();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);

function cdm_json($status,$message='',$extra=array()){ echo json_encode(array(array_merge(array('status'=>$status,'error_message'=>$message),$extra))); exit; }
function cdm_h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function cdm_num($v,$d=2){ return number_format((float)$v,$d,',','.'); }
function cdm_user(){ return isset($_SESSION['username']) ? $_SESSION['username'] : 'system'; }
function cdm_number($v){ if(function_exists('formatNumber')) return formatNumber($v); return (float)str_replace(array(',',' '),array('',''),(string)$v); }
function cdm_term_days($term){ return preg_match('/(\d+)/',(string)$term,$m) ? (int)$m[1] : 0; }
function cdm_due_date($date,$term){ $d=cdm_term_days($term); return ($date && $d>0) ? date('Y-m-d',strtotime($date.' +'.$d.' days')) : null; }
function cdm_memo_no($type){
  global $db;
  $type = $type === 'DM' ? 'DM' : 'CM';
  $prefix = $type.'/'.date('Y').'/'.date('m').'/';
  $row = $db->fetch("SELECT MAX(no_sales_invoice) max_no FROM sales_invoice WHERE no_sales_invoice LIKE ?", array($prefix.'%'));
  $next = 1;
  if ($row && $row->max_no) { $parts = explode('/',$row->max_no); $next = ((int)end($parts))+1; }
  return $prefix.str_pad($next,4,'0',STR_PAD_LEFT);
}
function cdm_post_journal($invoiceId){
  global $db;
  if (!function_exists('finance_post_journal')) return 'Helper finance_post_journal belum tersedia.';
  $h = $db->fetch("SELECT * FROM sales_invoice WHERE id_sales=? LIMIT 1", array($invoiceId));
  if (!$h) return 'Memo tidak ditemukan.';
  if ($h->billing_type !== 'CM' && $h->billing_type !== 'DM') return 'Billing type memo tidak valid.';
  $noBukti = $h->no_sales_invoice;
  $valuta = $h->valuta ?: 'IDR';
  if ($h->billing_type === 'DM') {
    $lines = array(
      array('no_rek'=>'12199','debet'=>$h->gross_amount,'kredit'=>0,'line_text'=>'Debit memo receivable '.$noBukti,'expected_category'=>'aset','valuta'=>$valuta,'kurs'=>1),
      array('no_rek'=>'41100','debet'=>0,'kredit'=>$h->net_amount,'line_text'=>'Debit memo revenue '.$noBukti,'expected_category'=>'pendapatan','valuta'=>$valuta,'kurs'=>1)
    );
    if ((float)$h->tax_amount > 0) $lines[] = array('no_rek'=>'21807','debet'=>0,'kredit'=>$h->tax_amount,'line_text'=>'Output VAT debit memo '.$noBukti,'expected_category'=>'kewajiban','valuta'=>$valuta,'kurs'=>1);
  } else {
    $lines = array(array('no_rek'=>'41100','debet'=>$h->net_amount,'kredit'=>0,'line_text'=>'Credit memo revenue reversal '.$noBukti,'expected_category'=>'pendapatan','valuta'=>$valuta,'kurs'=>1));
    if ((float)$h->tax_amount > 0) $lines[] = array('no_rek'=>'21807','debet'=>$h->tax_amount,'kredit'=>0,'line_text'=>'Output VAT credit memo reversal '.$noBukti,'expected_category'=>'kewajiban','valuta'=>$valuta,'kurs'=>1);
    $lines[] = array('no_rek'=>'12199','debet'=>0,'kredit'=>$h->gross_amount,'line_text'=>'Credit memo receivable reversal '.$noBukti,'expected_category'=>'aset','valuta'=>$valuta,'kurs'=>1);
  }
  $res = finance_post_journal(array(
    'document_type'=>$h->billing_type,
    'posting_status'=>'POSTED',
    'tgl_jurnal'=>$h->posting_date ?: $h->invoice_date,
    'ket'=>'AUTO: '.($h->billing_type==='CM'?'Credit':'Debit').' Memo '.$noBukti,
    'no_bukti'=>$noBukti,
    'source_module'=>'CREDIT_DEBIT_MEMO',
    'source_document_no'=>$noBukti,
    'valuta'=>$valuta,
    'kurs'=>1,
    'lines'=>$lines
  ));
  return $res === true ? 'OK' : $res;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
switch ($act) {
  case 'get_no':
    header('Content-Type: application/json');
    echo json_encode(array('no'=>cdm_memo_no(isset($_GET['type'])?$_GET['type']:'CM')));
    break;

  case 'get_invoice':
    header('Content-Type: application/json');
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $h = $db->fetch("SELECT si.*,p.nama customer_name,p.alamat customer_address FROM sales_invoice si LEFT JOIN penerima p ON p.kode_penerima=si.bill_to WHERE si.id_sales=? AND si.billing_status='POSTED' LIMIT 1", array($id));
    if (!$h) { echo json_encode(array('status'=>'error','error_message'=>'Invoice asal tidak ditemukan atau belum POSTED.')); break; }
    $rows = $db->query("SELECT * FROM sales_invoice_detail WHERE id_sales=? ORDER BY id_sales_detail", array($id));
    $html = '<div class="row"><div class="col-sm-3"><strong>Original Invoice</strong><br>'.cdm_h($h->no_sales_invoice).'<br><small>'.cdm_h($h->invoice_date).'</small></div><div class="col-sm-3"><strong>'.sd_h('sales_customer', 'Customer').'</strong><br>'.cdm_h($h->customer_name ?: $h->bill_to).'</div><div class="col-sm-3"><strong>'.sd_h('sales_reference', 'Reference').'</strong><br>SO '.cdm_h($h->no_sales_order ?: '-').'<br><small>SJ '.cdm_h($h->no_do ?: '-').'</small></div><div class="col-sm-3"><strong>'.sd_h('sales_total', 'Total').'</strong><br>'.cdm_h($h->valuta).' '.cdm_num($h->gross_amount).'</div></div><hr><div class="table-responsive"><table class="table table-bordered table-condensed"><thead><tr class="bg-gray"><th>'.sd_h('sales_material', 'Material').'</th><th class="text-right">'.sd_h('sales_qty', 'Qty').'</th><th class="text-right">'.sd_h('sales_price', 'Price').'</th><th class="text-right">'.sd_h('sales_amount', 'Amount').'</th></tr></thead><tbody>';
    foreach($rows as $r) $html .= '<tr><td><strong>'.cdm_h($r->kd_barang).'</strong><br><small>'.cdm_h($r->nm_barang).'</small></td><td class="text-right">'.cdm_num($r->qty,4).'</td><td class="text-right">'.cdm_num($r->harga).'</td><td class="text-right">'.cdm_num($r->nilai).'</td></tr>';
    $html .= '</tbody></table></div>';
    echo json_encode(array('status'=>'good','bill_to'=>$h->bill_to,'ship_to'=>$h->ship_to,'no_sales_order'=>$h->no_sales_order,'nopo'=>$h->nopo,'valuta'=>$h->valuta ?: 'IDR','tax'=>(string)$h->tax==='1'?'1':'0','gross_amount'=>(float)$h->gross_amount,'html'=>$html));
    break;

  case 'in':
    $type = isset($_POST['memo_type']) && $_POST['memo_type']==='DM' ? 'DM' : 'CM';
    $origId = isset($_POST['original_invoice_id']) ? (int)$_POST['original_invoice_id'] : 0;
    $orig = $db->fetch("SELECT * FROM sales_invoice WHERE id_sales=? AND billing_status='POSTED' LIMIT 1", array($origId));
    if (!$orig) cdm_json('error','Invoice asal tidak ditemukan atau belum POSTED.');
    if ($orig->billing_type === 'CM' || $orig->billing_type === 'DM') cdm_json('error','Memo tidak boleh dibuat dari memo lain.');
    $memoNo = trim($_POST['no_sales_invoice']);
    if ($memoNo === '') $memoNo = cdm_memo_no($type);
    if ($db->fetch("SELECT id_sales FROM sales_invoice WHERE no_sales_invoice=? LIMIT 1", array($memoNo))) cdm_json('error','Nomor memo sudah dipakai.');
    $net = round(cdm_number($_POST['memo_amount']),2);
    if ($net <= 0) cdm_json('error','Nilai memo wajib lebih dari nol.');
    if ($type === 'CM' && (float)$orig->gross_amount > 0 && $net > (float)$orig->gross_amount) cdm_json('error','Credit memo tidak boleh lebih besar dari nilai invoice asal.');
    $date = $_POST['invoice_date'] ?: date('Y-m-d');
    $tax = isset($_POST['tax']) ? $_POST['tax'] : '0';
    $taxRate = $tax === '1' ? 11 : 0;
    $taxAmount = round($net * ($taxRate/100),2);
    $gross = $net + $taxAmount;
    $data = array(
      'billing_type'=>$type,
      'reference_type'=>'INVOICE',
      'reference_no'=>$orig->no_sales_invoice,
      'original_invoice_id'=>$orig->id_sales,
      'memo_reason_code'=>$_POST['memo_reason_code'],
      'memo_reason_text'=>$_POST['memo_reason_text'],
      'bill_to'=>$orig->bill_to,
      'ship_to'=>$orig->ship_to,
      'invoice_date'=>$date,
      'posting_date'=>$date,
      'invoice_no'=>'',
      'no_sales_order'=>$orig->no_sales_order,
      'no_sales_invoice'=>$memoNo,
      'nopo'=>$orig->nopo,
      'ttd'=>$_POST['ttd'],
      'term'=>$orig->term,
      'due_date'=>cdm_due_date($date,$orig->term),
      'valuta'=>$orig->valuta ?: 'IDR',
      'ship_date'=>$orig->ship_date,
      'catatan'=>$_POST['catatan'],
      'no_do'=>$orig->no_do,
      'bank_detail'=>$orig->bank_detail,
      'tax'=>$tax,
      'tax_code'=>$tax==='1' ? 'PPN11' : 'NON_TAX',
      'tax_rate'=>$taxRate,
      'net_amount'=>$net,
      'tax_amount'=>$taxAmount,
      'gross_amount'=>$gross,
      'billing_status'=>'POSTED',
      'created_by'=>cdm_user(),
      'posted_by'=>cdm_user(),
      'posted_at'=>date('Y-m-d H:i:s')
    );
    if (!$db->insert('sales_invoice',$data)) cdm_json('error',$db->getErrorMessage() ?: sd_t('sales_memo_save_failed', 'Memo failed to save.'));
    $id = $db->last_insert_id();
    $desc = ($type === 'CM' ? 'Credit Memo' : 'Debit Memo').' for invoice '.$orig->no_sales_invoice;
    $db->insert('sales_invoice_detail', array('id_sales'=>$id,'line_no'=>10,'billing_item_type'=>$type === 'CM' ? 'CREDIT_MEMO' : 'DEBIT_MEMO','kd_barang'=>$type,'nm_barang'=>$desc,'material_number'=>$type,'material_description'=>$_POST['memo_reason_text'],'qty'=>1,'harga'=>$net,'unit'=>'LS','nilai'=>$net,'tax_code'=>$tax==='1'?'PPN11':'NON_TAX','tax_rate'=>$taxRate,'tax_amount'=>$taxAmount,'gross_amount'=>$gross));
    $jr = cdm_post_journal($id);
    if ($jr !== 'OK') cdm_json('error','Memo tersimpan tetapi jurnal gagal: '.$jr);
    if (function_exists('simpan_log')) simpan_log('User '.cdm_user().' posting '.($type==='CM'?'Credit':'Debit').' Memo '.$memoNo.' referensi invoice '.$orig->no_sales_invoice.' pada '.date('Y-m-d H:i:s'), cdm_user());
    cdm_json('good','',array('id'=>$id,'memo_no'=>$memoNo));
    break;

  case 'cancel':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $h = $db->fetch("SELECT * FROM sales_invoice WHERE id_sales=? AND billing_type IN ('CM','DM') LIMIT 1", array($id));
    if (!$h) cdm_json('error','Memo tidak ditemukan.');
    if ($h->billing_status !== 'POSTED') cdm_json('error','Hanya memo POSTED yang bisa dicancel.');
    if ($reason === '') cdm_json('error','Alasan cancel wajib diisi.');
    $db->update('sales_invoice', array('billing_status'=>'CANCELLED','cancelled_by'=>cdm_user(),'cancelled_at'=>date('Y-m-d H:i:s'),'cancel_reason'=>$reason), 'id_sales', $id);
    if (function_exists('accounting_reverse_auto_journal')) accounting_reverse_auto_journal($h->no_sales_invoice, $h->no_sales_invoice.'-C', array('tgl_jurnal'=>date('Y-m-d'),'ket'=>'Cancel '.$h->no_sales_invoice));
    if (function_exists('simpan_log')) simpan_log('User '.cdm_user().' cancel memo '.$h->no_sales_invoice.' alasan '.$reason.' pada '.date('Y-m-d H:i:s'), cdm_user());
    cdm_json('good','');
    break;

  case 'detail':
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $h = $db->fetch("SELECT si.*,p.nama customer_name,oi.no_sales_invoice original_invoice_no FROM sales_invoice si LEFT JOIN penerima p ON p.kode_penerima=si.bill_to LEFT JOIN sales_invoice oi ON oi.id_sales=si.original_invoice_id WHERE si.id_sales=? AND si.billing_type IN ('CM','DM') LIMIT 1", array($id));
    if (!$h) { echo '<div class="alert alert-warning">Memo tidak ditemukan.</div>'; break; }
    echo '<h3 style="margin-top:0">'.cdm_h($h->no_sales_invoice).' <small>'.cdm_h($h->billing_type==='CM'?'Credit Memo':'Debit Memo').' / '.cdm_h($h->billing_status).'</small></h3>';
    echo '<div class="row"><div class="col-sm-3"><strong>'.sd_h('sales_customer', 'Customer').'</strong><br>'.cdm_h($h->customer_name ?: $h->bill_to).'</div><div class="col-sm-3"><strong>Original Invoice</strong><br>'.cdm_h($h->original_invoice_no ?: $h->reference_no).'</div><div class="col-sm-3"><strong>Reason</strong><br>'.cdm_h($h->memo_reason_code).'<br><small>'.cdm_h($h->memo_reason_text).'</small></div><div class="col-sm-3"><strong>'.sd_h('sales_date', 'Date').'</strong><br>'.cdm_h($h->invoice_date).'</div></div><hr>';
    echo '<div class="row"><div class="col-sm-4"><div class="well well-sm"><strong>DPP</strong><br>'.cdm_num($h->net_amount).'</div></div><div class="col-sm-4"><div class="well well-sm"><strong>PPN</strong><br>'.cdm_num($h->tax_amount).'</div></div><div class="col-sm-4"><div class="well well-sm"><strong>Grand Total</strong><br>'.cdm_num($h->gross_amount).'</div></div></div>';
    if ($h->cancel_reason) echo '<div class="alert alert-warning"><strong>Cancel Reason:</strong> '.cdm_h($h->cancel_reason).'</div>';
    break;

  case 'excel':
    $initial=ob_get_level(); ob_start(); require_once "../../inc/lib/PHPExcel.php"; require_once "../../inc/excel_style_helper.php"; PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $from=!empty($_GET['tgl_awal'])?$_GET['tgl_awal']:date('Y-m-01'); $to=!empty($_GET['tgl_akhir'])?$_GET['tgl_akhir']:date('Y-m-d'); $p=array($from,$to); $w=" WHERE si.billing_type IN ('CM','DM') AND si.invoice_date BETWEEN ? AND ? ";
    if(!empty($_GET['customer'])){$w.=" AND si.bill_to=? ";$p[]=$_GET['customer'];} if(!empty($_GET['type'])){$w.=" AND si.billing_type=? ";$p[]=$_GET['type'];} if(!empty($_GET['status'])){$w.=" AND si.billing_status=? ";$p[]=$_GET['status'];}
    $rows=$db->query("SELECT si.*,p.nama customer_name,oi.no_sales_invoice original_invoice_no FROM sales_invoice si LEFT JOIN penerima p ON p.kode_penerima=si.bill_to LEFT JOIN sales_invoice oi ON oi.id_sales=si.original_invoice_id $w ORDER BY si.invoice_date DESC,si.id_sales DESC",$p);
    $excel=new PHPExcel(); $sh=$excel->setActiveSheetIndex(0); $sh->setTitle(erp_export_sheet_title('Credit Debit Memo')); $heads=array(erp_export_label("No"),erp_export_label("Memo Type"),erp_export_label("Memo No"),erp_export_label("Memo Date"),erp_export_label("Customer"),erp_export_label("Original Invoice"),erp_export_label("Sales Order"),erp_export_label("Currency"),erp_export_label("Reason Code"),erp_export_label("Reason Text"),erp_export_label("DPP"),erp_export_label("PPN"),erp_export_label("Gross"),erp_export_label("Status"),erp_export_label("Created By"),erp_export_label("Notes")); foreach($heads as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).'4',$v);
    $r=5;$n=1;foreach($rows as $row){$vals=array($n++,$row->billing_type,$row->no_sales_invoice,$row->invoice_date,$row->customer_name?:$row->bill_to,$row->original_invoice_no?:$row->reference_no,$row->no_sales_order,$row->valuta,$row->memo_reason_code,$row->memo_reason_text,(float)$row->net_amount,(float)$row->tax_amount,(float)$row->gross_amount,$row->billing_status,$row->created_by,$row->catatan);foreach($vals as $i=>$v)$sh->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i).$r,$v);$r++;}
    erpkb_excel_apply_standard_style($excel,array('sheet'=>$sh,'title'=>erp_export_title('CREDIT DEBIT MEMO'),'header_row'=>4,'first_data_row'=>5,'last_data_row'=>max(5,$r-1),'column_count'=>16,'numeric_columns'=>array('K','L','M'),'filters'=>array('Memo Date'=>$from.' s/d '.$to,'Type'=>isset($_GET['type'])?$_GET['type']:erp_export_all_text(),'Status'=>isset($_GET['status'])?$_GET['status']:erp_export_all_text()))); $tmp=erpkb_excel_temp_file('credit_debit_memo_'); PHPExcel_IOFactory::createWriter($excel,'Excel2007')->save($tmp); $size=@filesize($tmp); $sig=@file_get_contents($tmp,false,null,0,2); if(!$size||$sig!=='PK'){@unlink($tmp);while(ob_get_level()>$initial)ob_end_clean();header('Content-Type:text/plain; charset=utf-8');echo erp_t('export_excel_invalid_file','File Excel gagal dibuat dengan benar.');exit;} while(ob_get_level()>$initial)ob_end_clean(); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="credit_debit_memo_'.date('Ymd_His').'.xlsx"'); header('Content-Length: '.$size); header('Cache-Control: max-age=0'); header('Pragma: public'); readfile($tmp); @unlink($tmp); exit;

  default:
    header('Content-Type: application/json'); echo json_encode(array('status'=>'error','error_message'=>'Action tidak dikenal.'));
}
?>
