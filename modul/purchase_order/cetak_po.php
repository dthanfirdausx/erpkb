<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

include __DIR__ . "/../../inc/config.php";
require_once __DIR__ . "/../../assets/plugins/html2pdf/html2pdf.class.php";

function po_pdf_h($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function po_pdf_t($key, $fallback = '')
{
  return lang_text($key, $fallback);
}

function po_pdf_text($value, $width = 48)
{
  $text = trim(preg_replace('/\s+/', ' ', str_replace(array("\r", "\n"), ' ', (string)$value)));
  if ($text === '') {
    return '-';
  }
  return nl2br(po_pdf_h(wordwrap($text, $width, "\n", true)));
}

function po_pdf_date($date)
{
  if (!$date || $date === '0000-00-00') {
    return '-';
  }
  return function_exists('tgl_indo') ? tgl_indo($date) : date('d-m-Y', strtotime($date));
}

function po_pdf_money($value)
{
  return number_format((float)$value, 2, ',', '.');
}

function po_pdf_qty($value)
{
  return number_format((float)$value, 2, ',', '.');
}

function po_pdf_company_logo($company)
{
  if (!$company || empty($company->logo)) {
    return '';
  }

  $logo = trim((string)$company->logo);
  if ($logo === '' || preg_match('/^https?:\/\//i', $logo)) {
    return '';
  }

  $relative = ltrim($logo, '/');
  $candidates = array(
    $logo,
    rtrim(SITE_ROOT, '/') . '/' . $relative,
    dirname(__DIR__, 2) . '/' . $relative,
    dirname(__DIR__, 2) . '/upload/infokb/' . basename($relative),
    dirname(__DIR__, 2) . '/assets/' . $relative,
  );

  foreach ($candidates as $path) {
    if ($path && is_file($path) && is_readable($path)) {
      $info = @getimagesize($path);
      if ($info !== false && in_array($info[2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF), true)) {
        return $path;
      }
    }
  }

  return '';
}

function po_pdf_logo_render_src($path)
{
  if ($path === '' || !is_file($path) || !is_readable($path)) {
    return '';
  }

  $info = @getimagesize($path);
  if ($info === false || empty($info['mime'])) {
    return '';
  }

  if (!in_array($info['mime'], array('image/png', 'image/jpeg', 'image/gif'), true)) {
    return '';
  }

  $cacheDir = dirname(__DIR__, 2).'/upload/infokb/pdf_cache';
  $cachePath = $cacheDir.'/logo_po_pdf_'.md5($path.filemtime($path)).'.jpg';
  if (is_file($cachePath) && is_readable($cachePath)) {
    return $cachePath;
  }

  if (!function_exists('imagecreatetruecolor')) {
    return '';
  }

  switch ($info['mime']) {
    case 'image/png':
      $src = @imagecreatefrompng($path);
      break;
    case 'image/jpeg':
      $src = @imagecreatefromjpeg($path);
      break;
    case 'image/gif':
      $src = @imagecreatefromgif($path);
      break;
    default:
      $src = false;
  }

  if (!$src) {
    return '';
  }

  $srcW = imagesx($src);
  $srcH = imagesy($src);
  $maxW = 180;
  $maxH = 110;
  $scale = min($maxW / max(1, $srcW), $maxH / max(1, $srcH), 1);
  $dstW = max(1, (int)floor($srcW * $scale));
  $dstH = max(1, (int)floor($srcH * $scale));
  $dst = imagecreatetruecolor($dstW, $dstH);
  $white = imagecolorallocate($dst, 255, 255, 255);
  imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $white);

  if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
  }
  $tmp = is_dir($cacheDir) && is_writable($cacheDir)
    ? $cachePath
    : sys_get_temp_dir().'/erpkb_po_logo_'.md5($path.filemtime($path)).'.jpg';
  imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
  @imagejpeg($dst, $tmp, 85);
  imagedestroy($src);
  imagedestroy($dst);

  if (!is_file($tmp) || !is_readable($tmp)) {
    return '';
  }

  return $tmp;
}

$id = isset($_GET['po_no']) ? (int)$_GET['po_no'] : 0;
$po = $db->fetch("SELECT po.*, ep.plant_name, es.storage_name
                  FROM purchase_order po
                  LEFT JOIN erp_plant ep ON ep.plant_code=po.plant
                  LEFT JOIN erp_storage_location es ON es.storage_code=po.storage_location
                  WHERE po.id=?
                  LIMIT 1", array($id));

if (!$po) {
  http_response_code(404);
  echo po_pdf_h(po_pdf_t('purchase_order_not_found', 'Purchase Order tidak ditemukan.'));
  exit;
}

$details = $db->query("SELECT * FROM purchase_order_detail WHERE po_no=? ORDER BY id", array($po->purchase_order_no));
$company = $db->fetch("SELECT * FROM infokb LIMIT 1");
$companyName = $company && trim((string)$company->nama) !== '' ? $company->nama : (defined('namaPT') ? namaPT : 'Company');
$companyAddress = $company ? trim((string)$company->alamat.' '.(string)$company->kota.' '.(string)$company->prop) : '';
$companyPhone = $company ? $company->telp : '';
$companyEmail = $company ? $company->email : '';
$companyNpwp = $company ? $company->npwp : '';
$logoPath = po_pdf_company_logo($company);
$logoRenderSrc = po_pdf_logo_render_src($logoPath);

$itemsHtml = '';
$no = 1;
$subtotal = 0;
if ($details) {
  foreach ($details as $d) {
    $qty = (float)$d->qty;
    $price = (float)$d->harga;
    $amount = $d->amount !== null ? (float)$d->amount : ($qty * $price);
    $subtotal += $amount;
    $rowClass = ($no % 2 === 0) ? ' class="alt"' : '';
    $itemsHtml .= '<tr'.$rowClass.'>
      <td class="center">'.($no++).'</td>
      <td><b>'.po_pdf_h($d->kode_barang).'</b><br>'.po_pdf_text($d->nama_barang, 30).'</td>
      <td>'.po_pdf_text($d->spec, 24).'</td>
      <td class="right">'.po_pdf_qty($qty).'</td>
      <td class="center">'.po_pdf_h($d->unit).'</td>
      <td class="right">'.po_pdf_money($price).'</td>
      <td class="right">'.po_pdf_money($amount).'</td>
    </tr>';
  }
}
if ($itemsHtml === '') {
  $itemsHtml = '<tr><td colspan="7" class="center muted">'.po_pdf_h(po_pdf_t('purchase_order_no_item', 'No PO item.')).'</td></tr>';
}

$taxRate = strtolower((string)$po->pajak) === 'ya' ? 0.11 : 0;
$taxAmount = $subtotal * $taxRate;
$grandTotal = $subtotal + $taxAmount;
$logoHtml = $logoRenderSrc !== ''
  ? '<img src="'.po_pdf_h($logoRenderSrc).'" class="brand-logo">'
  : '<div class="brand-mark">'.po_pdf_text($companyName, 14).'</div>';
$filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $po->purchase_order_no);

$html = '<page backtop="7mm" backbottom="7mm" backleft="7mm" backright="7mm">
<style>
  body{font-family:helvetica;font-size:7.8px;color:#2b3a4a}
  table{border-collapse:collapse}
  .doc{width:156mm}
  .items{width:156mm;margin-top:2mm}
  .summary{width:156mm}
  .wide{width:156mm}
  .bottom-wide{width:169mm}

  /* ===== HEADER ===== */
  .header td{vertical-align:top;padding:0}
  .brand-mark{
    border:0.4mm solid #1f3a5f;
    border-radius:1.5mm;
    color:#1f3a5f;
    font-weight:bold;
    font-size:7.2px;
    text-align:center;
    padding:5mm 1mm;
  }
  .brand-logo{
    max-width:24mm;
    max-height:18mm;
  }
  .brand-slot{
    width:24mm;
    height:18mm;
  }
  .company-name{font-size:9.4px;font-weight:bold;color:#13233a;line-height:1.25}
  .company-meta{color:#5b6b7d;line-height:1.45;margin-top:.8mm;font-size:7.2px}
  .doc-subtitle{font-size:6.8px;color:#8a96a3;letter-spacing:1px}
  .po-head{margin-top:1.5mm}
  .po-head td{vertical-align:middle;padding:1mm 0}
  .doc-title{font-size:12.4px;font-weight:bold;letter-spacing:.7px;color:#1f3a5f;line-height:1.15}
  .po-head-info{text-align:right;color:#5b6b7d;font-size:6.8px;line-height:1.35}
  .po-no-tag{
    background-color:#1f3a5f;
    color:#ffffff;
    font-weight:bold;
    font-size:8.4px;
    padding:0.8mm 1.8mm;
    border-radius:0.8mm;
  }
  .gen-date{color:#9aa5b1;font-size:6.4px;margin-top:1mm}

  .rule-table{width:176mm;margin-top:.6mm;margin-bottom:2mm;border-collapse:collapse}
  .rule-thick-cell{width:176mm;border-bottom:0.6mm solid #1f3a5f;height:1mm;font-size:1px;line-height:1px}
  .rule-thin-cell{width:176mm;border-bottom:0.2mm solid #c7d0db;height:1mm;font-size:1px;line-height:1px}

  /* ===== META PANELS ===== */
  .meta-wrap td{vertical-align:top;padding:0}
  .panel{width:76mm;border:0.25mm solid #d7dee6;border-radius:0.8mm}
  .panel-title{
    background-color:#1f3a5f;
    color:#ffffff;
    font-weight:bold;
    font-size:7.1px;
    letter-spacing:.5px;
    padding:1.2mm 2mm;
    border-radius:0.8mm 0.8mm 0 0;
  }
  .panel-body table{width:76mm}
  .panel-body th, .panel-body td{
    padding:0.9mm 1.6mm;
    border-bottom:0.2mm solid #eef1f5;
    text-align:left;
    vertical-align:top;
  }
  .panel-body th{
    width:24mm;
    font-weight:bold;
    color:#6b7787;
    font-size:6.9px;
    background-color:#fafbfc;
  }
  .panel-body td{color:#28344a;font-size:7.4px;line-height:1.35}
  .panel-table{width:76mm;border:0.25mm solid #d7dee6;border-collapse:collapse}
  .panel-table .panel-head{background-color:#1f3a5f;color:#ffffff;font-weight:bold;font-size:7.1px;letter-spacing:.45px;padding:1.05mm 1.7mm}
  .panel-table th,.panel-table td{padding:0.85mm 1.4mm;border-bottom:0.2mm solid #eef1f5;text-align:left;vertical-align:top}
  .panel-table th{width:24mm;font-weight:bold;color:#6b7787;font-size:6.9px;background-color:#fafbfc;line-height:1.3}
  .panel-table td{color:#28344a;font-size:7.4px;line-height:1.35}
  .panel-table .panel-value{width:52mm}
  .panel-table td.panel-head{color:#ffffff}

  /* ===== ITEMS TABLE ===== */
  .items th{
    background-color:#1f3a5f;
    color:#ffffff;
    border:0.2mm solid #1f3a5f;
    padding:.8mm .6mm;
    font-size:7.1px;
    text-align:center;
  }
  .items td{
    border:0.2mm solid #e2e7ed;
    padding:.7mm .6mm;
    vertical-align:top;
    font-size:7.1px;
  }
  .items tr.alt td{background-color:#f8fafc}
  .items tr.item-summary td{
    background-color:#ffffff;
    border-top:0.2mm solid #d7dee6;
    font-size:7.2px;
  }
  .items tr.item-summary .blank{
    border-right:0.2mm solid #e2e7ed;
  }
  .items tr.item-summary .lbl{
    text-align:right;
    color:#5b6b7d;
    font-weight:bold;
  }
  .items tr.item-summary .val{
    text-align:right;
    color:#28344a;
  }
  .items tr.item-grand td{
    background-color:#ffffff;
    border-top:0.45mm solid #1f3a5f;
    font-weight:bold;
    font-size:7.8px;
    color:#1f3a5f;
    padding-top:1mm;
    padding-bottom:1mm;
  }

  /* ===== SUMMARY ===== */
  .bottom-space{height:4mm;font-size:1px;line-height:1px}

  /* ===== NOTE ===== */
  .note-table{width:169mm;border-collapse:collapse;margin-top:0}
  .note-table td{border:0 solid #ffffff}
  .note-table .note-title-cell{
    border:0 solid #ffffff;
    padding:0 0 .8mm 0;
    font-weight:bold;
    color:#1f3a5f;
    font-size:7.2px;
    line-height:1.2;
  }
  .note-table .note-content-cell{
    width:169mm;
    border:0.25mm solid #d7dee6;
    background-color:#fafbfc;
    padding:1.4mm 1.8mm;
    min-height:6mm;
    font-size:7.4px;
    line-height:1.35;
    color:#3a4658;
  }

  /* ===== SIGNATURES ===== */
  .sign-table{width:174mm;margin-top:3mm;border-collapse:collapse}
  .sign-table td{
    border:0 solid #ffffff;
    padding:0;
  }
  .sign-table .sign-line{
    width:174mm;
    border-top:0.25mm solid #c7d0db;
    height:1.4mm;
    font-size:1px;
    line-height:1px;
  }
  .sign-table .sign-label{
    text-align:center;
    vertical-align:top;
    font-size:7.2px;
    color:#5b6b7d;
    font-weight:bold;
    height:8mm;
  }

  .right{text-align:right}.center{text-align:center}
  .muted{color:#9aa5b1}
</style>

<table class="doc header">
  <tr>
    <td style="width:25mm">
      <table style="width:26mm"><tr><td style="text-align:center;vertical-align:middle">'.$logoHtml.'</td></tr></table>
    </td>
    <td style="width:131mm;padding-left:2mm">
      <div class="company-name">'.po_pdf_h($companyName).'</div>
      <div class="company-meta">'.po_pdf_text($companyAddress, 20).'<br>
      '.po_pdf_h(po_pdf_t('purchase_order_phone','Phone')).': '.po_pdf_h($companyPhone).' &nbsp;|&nbsp; '.po_pdf_h(po_pdf_t('purchase_order_email','Email')).': '.po_pdf_text($companyEmail, 20).'<br>
      NPWP: '.po_pdf_h($companyNpwp).'</div>
    </td>
  </tr>
</table>

<table class="wide po-head">
  <tr>
    <td style="width:100mm">
      <div class="doc-subtitle">'.po_pdf_h(po_pdf_t('purchase_order_document_subtitle','DOKUMEN PEMBELIAN')).'</div>
      <div class="doc-title">'.po_pdf_h(po_pdf_t('purchase_order_document_title','PURCHASE ORDER')).'</div>
    </td>
    <td style="width:76mm;text-align:right" class="po-head-info">
      <span class="po-no-tag">'.po_pdf_h($po->purchase_order_no).'</span><br>
      '.po_pdf_h(po_pdf_t('purchase_order_generated','Generated')).': '.date('Y-m-d H:i').'
    </td>
  </tr>
</table>
<table class="rule-table">
  <tr><td class="rule-thick-cell" style="width:176mm">&nbsp;</td></tr>
  <tr><td class="rule-thin-cell" style="width:176mm">&nbsp;</td></tr>
</table>

<table class="doc meta-wrap">
  <tr>
    <td style="width:78mm;padding-right:1mm">
      <table class="panel-table" style="width:76mm">
        <tr><td colspan="2" class="panel-head">'.po_pdf_h(po_pdf_t('purchase_order_vendor','VENDOR')).'</td></tr>
        <tr><th>'.po_pdf_h(po_pdf_t('purchase_order_seller','Seller')).'</th><td class="panel-value">'.po_pdf_text(trim($po->seller_code.' - '.$po->seller_name, ' -'), 20).'</td></tr>
        <tr><th>'.po_pdf_h(po_pdf_t('purchase_order_address','Address')).'</th><td class="panel-value">'.po_pdf_text($po->seller_address, 20).'</td></tr>
        <tr><th>'.po_pdf_h(po_pdf_t('purchase_order_phone_pic','Phone / PIC')).'</th><td class="panel-value">'.po_pdf_text($po->seller_phone.' / '.$po->seller_pic, 20).'</td></tr>
        <tr><th>'.po_pdf_h(po_pdf_t('purchase_order_email','Email')).'</th><td class="panel-value">'.po_pdf_text($po->seller_email, 20).'</td></tr>
      </table>
    </td>
    <td style="width:78mm;padding-left:1mm">
      <table class="panel-table" style="width:76mm">
        <tr><td colspan="2" class="panel-head">'.po_pdf_h(po_pdf_t('purchase_order_information','PO INFORMATION')).'</td></tr>
        <tr><th>'.po_pdf_h(po_pdf_t('purchase_order_po_date','PO Date')).'</th><td class="panel-value">'.po_pdf_h(po_pdf_date($po->po_date)).'</td></tr>
        <tr><th>'.po_pdf_h(po_pdf_t('purchase_order_delivery_date','Delivery Date')).'</th><td class="panel-value">'.po_pdf_h(po_pdf_date($po->delivery_date)).'</td></tr>
        <tr><th>'.po_pdf_h(po_pdf_t('purchase_order_arrival_date','Arrival Date')).'</th><td class="panel-value">'.po_pdf_h(po_pdf_date($po->arrival_date)).'</td></tr>
        <tr><th>'.po_pdf_h(po_pdf_t('purchase_order_status_currency','Currency / Status')).'</th><td class="panel-value">'.po_pdf_h($po->currency).' / '.po_pdf_h($po->approval_status ?: 'Pending').'</td></tr>
      </table>
    </td>
  </tr>
  <tr><td colspan="2" style="height:3mm">&nbsp;</td></tr>
  <tr>
    <td style="width:78mm;padding-right:1mm">
      <table class="panel-table" style="width:76mm">
        <tr><td colspan="2" class="panel-head">'.po_pdf_h(po_pdf_t('purchase_order_ship_to','SHIP TO')).'</td></tr>
        <tr><th>'.po_pdf_h(po_pdf_t('purchase_order_consignee','Consignee')).'</th><td class="panel-value">'.po_pdf_text($po->consignee_name, 34).'</td></tr>
        <tr><th>'.po_pdf_h(po_pdf_t('purchase_order_address','Address')).'</th><td class="panel-value">'.po_pdf_text($po->consignee_address, 34).'</td></tr>
        <tr><th>'.po_pdf_h(po_pdf_t('purchase_order_phone_email','Phone / Email')).'</th><td class="panel-value">'.po_pdf_text($po->consignee_phone.' / '.$po->consignee_email, 34).'</td></tr>
      </table>
    </td>
    <td style="width:78mm;padding-left:1mm">
      <table class="panel-table" style="width:76mm">
        <tr><td colspan="2" class="panel-head">'.po_pdf_h(po_pdf_t('purchase_order_terms_location','TERMS & LOCATION')).'</td></tr>
        <tr><th>'.po_pdf_h(po_pdf_t('common_plant','Plant')).'</th><td class="panel-value">'.po_pdf_text(trim($po->plant.' - '.$po->plant_name, ' -'), 34).'</td></tr>
        <tr><th>'.po_pdf_h(po_pdf_t('purchase_order_sloc','SLoc')).'</th><td class="panel-value">'.po_pdf_text(trim($po->storage_location.' - '.$po->storage_name, ' -'), 34).'</td></tr>
        <tr><th>'.po_pdf_h(po_pdf_t('purchase_order_terms','Term')).'</th><td class="panel-value">'.po_pdf_text($po->delivery_term.' | '.$po->payment_term.' | '.$po->shipped_via, 30).'</td></tr>
      </table>
    </td>
  </tr>
</table>

<table class="doc items">
  <tr>
    <th style="width:6mm">No</th>
    <th style="width:44mm">'.po_pdf_h(po_pdf_t('purchase_order_material_description','Material / Description')).'</th>
    <th style="width:26mm">'.po_pdf_h(po_pdf_t('common_spec','Spec')).'</th>
    <th style="width:12mm">'.po_pdf_h(po_pdf_t('purchase_order_qty','Qty')).'</th>
    <th style="width:10mm">'.po_pdf_h(po_pdf_t('purchase_order_uom','UOM')).'</th>
    <th style="width:25mm">'.po_pdf_h(po_pdf_t('purchase_order_unit_price','Unit Price')).'</th>
    <th style="width:33mm">'.po_pdf_h(po_pdf_t('purchase_order_amount','Amount')).'</th>
  </tr>
  '.$itemsHtml.'
  <tr class="item-summary">
    <td colspan="5" class="blank">&nbsp;</td>
    <td class="lbl">'.po_pdf_h(po_pdf_t('purchase_order_subtotal','Subtotal')).'</td>
    <td class="val">'.po_pdf_money($subtotal).'</td>
  </tr>
  <tr class="item-summary">
    <td colspan="5" class="blank">&nbsp;</td>
    <td class="lbl">'.po_pdf_h(po_pdf_t('purchase_order_tax','Tax')).' '.($taxRate > 0 ? '11%' : '0%').'</td>
    <td class="val">'.po_pdf_money($taxAmount).'</td>
  </tr>
  <tr class="item-grand" >
    <td colspan="5" class="blank">&nbsp;</td>
    <td class="lbl" style="text-align:right">'.po_pdf_h(po_pdf_t('purchase_order_grand_total','Grand Total')).'</td>
    <td class="val" style="text-align:right">'.po_pdf_h($po->currency).' '.po_pdf_money($grandTotal).'</td>
  </tr>
</table>

<div class="bottom-space">&nbsp;</div>
<table class="note-table">
  <tr>
    <td class="note-title-cell" style="width:169mm;border:0 solid #ffffff">'.po_pdf_h(po_pdf_t('purchase_order_note','NOTE')).'</td>
  </tr>
  <tr>
    <td class="note-content-cell" style="width:169mm">'.nl2br(po_pdf_h($po->catatan)).'</td>
  </tr>
</table>

<table class="sign-table">
  <tr>
    <td colspan="3" class="sign-line" style="width:174mm">&nbsp;</td>
  </tr>
  <tr>
    <td class="sign-label" style="width:58mm">'.po_pdf_h(po_pdf_t('purchase_order_order_approved_by','ORDER APPROVED BY')).'</td>
    <td class="sign-label" style="width:58mm">'.po_pdf_h(po_pdf_t('purchase_order_requested_by','REQUESTED BY')).'</td>
    <td class="sign-label" style="width:58mm">'.po_pdf_h(po_pdf_t('purchase_order_vendor_confirmation','VENDOR CONFIRMATION')).'</td>
  </tr>
</table>
</page>';
// echo $html;
// die();

$pdfName = 'purchase_order_'.$filename.'.pdf';
$previousDisplayErrors = ini_get('display_errors');
$previousErrorReporting = error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', '0');

ob_start();
try {
  $html2pdf = new HTML2PDF('P', 'A4', 'en', true, 'UTF-8', array(7, 7, 7, 7));
  $html2pdf->pdf->SetTitle('Purchase Order '.$po->purchase_order_no);
  $html2pdf->pdf->SetAuthor($companyName);
  $html2pdf->writeHTML($html);
  $pdfContent = $html2pdf->Output($pdfName, 'S');
} catch (Exception $e) {
  ob_end_clean();
  ini_set('display_errors', $previousDisplayErrors);
  error_reporting($previousErrorReporting);
  http_response_code(500);
  echo po_pdf_h(po_pdf_t('purchase_order_pdf_failed', 'Gagal membuat PDF Purchase Order')).': '.po_pdf_h($e->getMessage());
  exit;
}
ob_end_clean();

ini_set('display_errors', $previousDisplayErrors);
error_reporting($previousErrorReporting);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="'.$pdfName.'"');
header('Content-Length: '.strlen($pdfContent));
echo $pdfContent;
exit;
