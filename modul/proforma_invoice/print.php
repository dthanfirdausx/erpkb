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
include "../../inc/config.php";
require_once __DIR__ . "/../print_pdf_helper.php";
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$h = $db->fetch("SELECT si.*,p.nama customer_name,p.alamat customer_address,p.npwp FROM sales_invoice si LEFT JOIN penerima p ON p.kode_penerima=si.bill_to WHERE si.id_sales=? AND si.billing_type='PF' LIMIT 1", array($id));
if (!$h) die(erp_t('export_document_not_found','Dokumen tidak ditemukan.'));
$info = info_pt();
$items = $db->query("SELECT * FROM sales_invoice_detail WHERE id_sales=? ORDER BY line_no,id_sales_detail", array($id));
function pf_print_h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function pf_print_num($v){ return function_exists('erp_format_number') ? erp_format_number($v,2) : number_format((float)$v,2); }
ob_start();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?=sd_h('sales_proforma_invoice', 'Proforma Invoice');?></title>
<style>
body{font-family:Arial,sans-serif;font-size:12px;margin:0;background:#fff}.wrap{width:190mm;margin:auto;border:2px solid #111}.head{display:flex;align-items:center;padding:12px}.logo{font-size:22px;font-weight:bold;color:#2673b9}.title{flex:1;text-align:center;font-size:28px;font-weight:bold}table{width:100%;border-collapse:collapse}td,th{border:1px solid #111;padding:6px;vertical-align:top}.right{text-align:right}.center{text-align:center}.bold{font-weight:bold}.note{padding:8px;border-top:1px solid #111;background:#f8fafc}.signature{height:95px}
</style>
</head>
<body onload="window.print()">
<div class="wrap">
  <div class="head"><div class="logo">GBLIGHT</div><div class="title"><?=erp_export_title('PROFORMA INVOICE');?></div></div>
  <table><tr><td class="bold" width="18%"><?=erp_export_label('PROFORMA NO.');?></td><td><?=pf_print_h($h->no_sales_invoice);?></td><td class="bold" width="15%"><?=erp_export_label('DATE');?></td><td><?=pf_print_h($h->invoice_date);?></td></tr><tr><td class="bold"><?=erp_export_label('SOURCE');?></td><td><?=pf_print_h($h->reference_type.' '.$h->reference_no);?></td><td class="bold"><?=erp_export_label('VALID UNTIL');?></td><td><?=pf_print_h($h->proforma_valid_until ?: '-');?></td></tr></table>
  <table><tr><td width="50%"><b><?=erp_export_label('TO');?>:</b><br><br><?=pf_print_h($h->customer_name ?: $h->bill_to);?><br><?=nl2br(pf_print_h($h->customer_address));?><br>NPWP: <?=pf_print_h($h->npwp ?: '-');?></td><td width="50%"><b><?=pf_print_h(namaPT);?></b><br><?=nl2br(pf_print_h($info->alamat));?><br>NPWP: <?=pf_print_h(isset($info->npwp)?$info->npwp:'-');?></td></tr></table>
  <table><thead><tr class="center bold"><th width="5%"><?=sd_h('common_no', 'No');?></th><th><?=erp_export_label('Item Description');?></th><th width="12%"><?=sd_h('sales_qty', 'Qty');?></th><th width="10%"><?=sd_h('sales_uom', 'UOM');?></th><th width="16%"><?=sd_h('sales_price', 'Price');?></th><th width="16%"><?=sd_h('sales_amount', 'Amount');?></th></tr></thead><tbody>
  <?php $n=1; foreach($items as $it){ ?>
    <tr><td class="center"><?=$n++;?></td><td><b><?=pf_print_h($it->kd_barang);?></b><br><?=pf_print_h($it->nm_barang);?></td><td class="right"><?=pf_print_num($it->qty);?></td><td><?=pf_print_h($it->unit);?></td><td class="right"><?=pf_print_h($h->valuta);?> <?=pf_print_num($it->harga);?></td><td class="right"><?=pf_print_h($h->valuta);?> <?=pf_print_num($it->nilai);?></td></tr>
  <?php } ?>
  </tbody><tfoot><tr class="bold"><td colspan="5" class="right"><?=erp_export_label('DPP');?></td><td class="right"><?=pf_print_h($h->valuta);?> <?=pf_print_num($h->net_amount);?></td></tr><tr class="bold"><td colspan="5" class="right"><?=erp_export_label('PPN');?> <?=pf_print_num($h->tax_rate);?>%</td><td class="right"><?=pf_print_h($h->valuta);?> <?=pf_print_num($h->tax_amount);?></td></tr><tr class="bold"><td colspan="5" class="right">GRAND <?=erp_export_label('TOTAL');?></td><td class="right"><?=pf_print_h($h->valuta);?> <?=pf_print_num($h->gross_amount);?></td></tr></tfoot></table>
  <div class="note"><b><?=erp_export_label('Note');?>:</b> This proforma invoice is for administrative purpose only and does not represent final billing, tax invoice, or accounting posting.</div>
  <table><tr><td width="33%" class="signature"><?=erp_export_label('Buyer Signature');?><br><br><?=erp_export_label('Name');?>:<br><?=erp_export_label('Title');?>:<br><?=erp_export_label('Date');?>:</td><td width="34%"><b><?=erp_export_label('Payment Details');?>:</b><br><?=nl2br(pf_print_h($h->bank_detail));?></td><td width="33%" class="signature"><?=erp_export_label('Seller Signature');?><br><br><b><?=pf_print_h($h->ttd ?: '-');?></b><br><?=erp_export_label('Date');?>: <?=pf_print_h($h->invoice_date);?></td></tr></table>
</div>
</body>
</html>
<?php
$html = ob_get_clean();
erpkb_pdf_output($html, 'proforma_invoice_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)$h->no_sales_invoice).'.pdf', 'P');
?>
