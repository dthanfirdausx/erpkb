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

function dpi_h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function dpi_num($v,$d=2){ return number_format((float)$v,$d,',','.'); }
function dpi_badge($s){
  $c = $s === 'POSTED' ? 'success' : ($s === 'CANCELLED' ? 'danger' : 'default');
  return '<span class="label label-'.$c.'">'.dpi_h($s).'</span>';
}

$req = $_REQUEST;
$draw = isset($req['draw']) ? (int)$req['draw'] : 0;
$start = isset($req['start']) ? max(0,(int)$req['start']) : 0;
$length = isset($req['length']) ? (int)$req['length'] : 25;
if ($length <= 0) $length = 25;

$from = !empty($_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-01');
$to = !empty($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d');
$p = array($from,$to);
$w = " WHERE si.billing_type='DP' AND si.invoice_date BETWEEN ? AND ? ";
if (!empty($_POST['customer'])) { $w .= " AND si.bill_to=? "; $p[] = $_POST['customer']; }
if (!empty($_POST['status'])) { $w .= " AND si.billing_status=? "; $p[] = $_POST['status']; }
if (!empty($_POST['keyword'])) {
  $kw = '%'.trim($_POST['keyword']).'%';
  $w .= " AND (si.no_sales_invoice LIKE ? OR si.no_sales_order LIKE ? OR si.nopo LIKE ? OR p.nama LIKE ?) ";
  $p[]=$kw; $p[]=$kw; $p[]=$kw; $p[]=$kw;
}

$joins = " LEFT JOIN penerima p ON p.kode_penerima=si.bill_to ";
$cnt = $db->fetch("SELECT COUNT(*) jml FROM sales_invoice si $joins $w", $p);
$rows = $db->query("SELECT si.*,p.nama customer_name FROM sales_invoice si $joins $w ORDER BY si.invoice_date DESC,si.id_sales DESC LIMIT $start,$length", $p);
$data = array();
$no = $start + 1;
foreach ($rows as $r) {
  $act = '<button class="btn btn-info btn-xs btn-dpi-detail" data-id="'.(int)$r->id_sales.'" title="'.sd_h('common_detail', 'Detail').'"><i class="fa fa-eye"></i></button> ';
  $act .= '<a target="_blank" class="btn btn-success btn-xs" href="'.base_url().'modul/sales_invoice/print.php?id='.(int)$r->id_sales.'" title="'.sd_h('common_print', 'Print').'"><i class="fa fa-print"></i></a> ';
  if ($r->billing_status === 'POSTED') {
    $act .= '<button class="btn btn-warning btn-xs btn-dpi-cancel" data-id="'.(int)$r->id_sales.'" title="'.sd_h('common_cancel', 'Cancel').'"><i class="fa fa-undo"></i></button>';
  }
  $doc = '<strong>'.dpi_h($r->no_sales_invoice).'</strong><br><small>'.dpi_h($r->invoice_date).' / '.dpi_h($r->valuta).'</small>';
  $cust = '<strong>'.dpi_h($r->customer_name ?: $r->bill_to).'</strong><br><small>Bill To: '.dpi_h($r->bill_to).'</small>';
  $ref = '<strong>'.dpi_h($r->no_sales_order ?: $r->reference_no).'</strong><br><small>PO '.dpi_h($r->nopo ?: '-').' | DP '.dpi_num($r->dp_percent,2).'%</small>';
  $amount = 'DPP '.dpi_num($r->net_amount).'<br><small>PPN '.dpi_num($r->tax_amount).' | Total '.dpi_num($r->gross_amount).'</small>';
  $open = '<strong>'.dpi_num($r->dp_open_amount).'</strong><br><small>Applied '.dpi_num($r->dp_applied_amount).'</small>';
  $created = dpi_h($r->created_by ?: '-').'<br><small>'.dpi_h($r->date_created ?: '-').'</small>';
  $data[] = array($no++,$act,$doc,$cust,$ref,$amount,$open,dpi_badge($r->billing_status),$created);
}

echo json_encode(array(
  'draw'=>$draw,
  'recordsTotal'=>intval($cnt ? $cnt->jml : 0),
  'recordsFiltered'=>intval($cnt ? $cnt->jml : 0),
  'data'=>$data
));
?>
