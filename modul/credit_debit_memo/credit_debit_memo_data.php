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
session_check_json();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);

function cdm_h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function cdm_num($v,$d=2){ return number_format((float)$v,$d,',','.'); }
function cdm_badge($s,$type=''){
  $c = $s === 'POSTED' ? 'success' : ($s === 'CANCELLED' ? 'danger' : 'default');
  $t = $type === 'CM' ? 'Credit Memo' : ($type === 'DM' ? 'Debit Memo' : $s);
  return '<span class="label label-'.$c.'">'.cdm_h($t).'</span>';
}

$draw = isset($_REQUEST['draw']) ? (int)$_REQUEST['draw'] : 0;
$start = isset($_REQUEST['start']) ? max(0,(int)$_REQUEST['start']) : 0;
$length = isset($_REQUEST['length']) ? (int)$_REQUEST['length'] : 25;
if ($length <= 0) $length = 25;
$from = !empty($_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-01');
$to = !empty($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d');
$p = array($from,$to);
$w = " WHERE si.billing_type IN ('CM','DM') AND si.invoice_date BETWEEN ? AND ? ";
if (!empty($_POST['customer'])) { $w .= " AND si.bill_to=? "; $p[] = $_POST['customer']; }
if (!empty($_POST['type'])) { $w .= " AND si.billing_type=? "; $p[] = $_POST['type']; }
if (!empty($_POST['status'])) { $w .= " AND si.billing_status=? "; $p[] = $_POST['status']; }
if (!empty($_POST['keyword'])) {
  $kw = '%'.trim($_POST['keyword']).'%';
  $w .= " AND (si.no_sales_invoice LIKE ? OR si.reference_no LIKE ? OR si.memo_reason_text LIKE ? OR p.nama LIKE ? OR oi.no_sales_invoice LIKE ?) ";
  $p[]=$kw; $p[]=$kw; $p[]=$kw; $p[]=$kw; $p[]=$kw;
}
$joins = " LEFT JOIN penerima p ON p.kode_penerima=si.bill_to LEFT JOIN sales_invoice oi ON oi.id_sales=si.original_invoice_id ";
$cnt = $db->fetch("SELECT COUNT(*) jml FROM sales_invoice si $joins $w", $p);
$rows = $db->query("SELECT si.*,p.nama customer_name,oi.no_sales_invoice original_invoice_no FROM sales_invoice si $joins $w ORDER BY si.invoice_date DESC,si.id_sales DESC LIMIT $start,$length", $p);
$data = array(); $no = $start + 1;
foreach ($rows as $r) {
  $act = '<button class="btn btn-info btn-xs btn-cdm-detail" data-id="'.(int)$r->id_sales.'" title="'.sd_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> ';
  $act .= '<a target="_blank" class="btn btn-success btn-xs" href="'.base_url().'modul/sales_invoice/print.php?id='.(int)$r->id_sales.'" title="'.sd_h('common_print', 'Print').'"><i class="fa fa-print"></i></a> ';
  if ($r->billing_status === 'POSTED') $act .= '<button class="btn btn-warning btn-xs btn-cdm-cancel" data-id="'.(int)$r->id_sales.'" title="'.sd_h('common_cancel', 'Cancel').'"><i class="fa fa-undo"></i></button>';
  $doc = '<strong>'.cdm_h($r->no_sales_invoice).'</strong><br><small>'.cdm_h($r->invoice_date).' / '.cdm_h($r->valuta).'</small>';
  $cust = '<strong>'.cdm_h($r->customer_name ?: $r->bill_to).'</strong><br><small>'.cdm_h($r->bill_to).'</small>';
  $orig = '<strong>'.cdm_h($r->original_invoice_no ?: $r->reference_no).'</strong><br><small>SO '.cdm_h($r->no_sales_order ?: '-').'</small>';
  $reason = '<strong>'.cdm_h($r->memo_reason_code ?: '-').'</strong><br><small>'.cdm_h($r->memo_reason_text ?: $r->catatan).'</small>';
  $amount = 'DPP '.cdm_num($r->net_amount).'<br><small>PPN '.cdm_num($r->tax_amount).' | Total '.cdm_num($r->gross_amount).'</small>';
  $created = cdm_h($r->created_by ?: '-').'<br><small>'.cdm_h($r->date_created ?: '-').'</small>';
  $data[] = array($no++,$act,$doc,$cust,$orig,$reason,$amount,cdm_badge($r->billing_status,$r->billing_type),$created);
}
echo json_encode(array('draw'=>$draw,'recordsTotal'=>intval($cnt?$cnt->jml:0),'recordsFiltered'=>intval($cnt?$cnt->jml:0),'data'=>$data));
?>
