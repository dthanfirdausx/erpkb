<?php
if (!function_exists('erpkb_pdf_clean_html')) {
  function erpkb_pdf_clean_html($html)
  {
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', (string)$html);
    $html = preg_replace('/\s+onload=["\'][^"\']*window\.print\(\)[^"\']*["\']/i', '', $html);
    $html = preg_replace('/<style>\s*<style>/i', '<style>', $html);
    $html = preg_replace('/<\/style>\s*<\/style>/i', '</style>', $html);
    $html = str_replace(array('@page{', '@page {'), array('/* @page disabled for html2pdf */ .page-rule{', '/* @page disabled for html2pdf */ .page-rule{'), $html);
    return $html;
  }
}

if (!function_exists('erpkb_pdf_output')) {
  function erpkb_pdf_output($html, $filename, $orientation = 'P')
  {
    require_once __DIR__ . "/../assets/plugins/html2pdf/html2pdf.class.php";

    $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', (string)$filename);
    if ($filename === '' || substr($filename, -4) !== '.pdf') {
      $filename .= '.pdf';
    }

    $previousDisplayErrors = ini_get('display_errors');
    $previousErrorReporting = error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');

    $html = erpkb_pdf_clean_html($html);
    if (stripos($html, '<page') === false) {
      $html = '<page backtop="7mm" backbottom="7mm" backleft="7mm" backright="7mm">'.$html.'</page>';
    }

    ob_start();
    try {
      $pdf = new HTML2PDF($orientation, 'A4', 'en', true, 'UTF-8', array(7, 7, 7, 7));
      $pdf->pdf->SetTitle($filename);
      $pdf->writeHTML($html);
      $content = $pdf->Output($filename, 'S');
    } catch (Exception $e) {
      ob_end_clean();
      ini_set('display_errors', $previousDisplayErrors);
      error_reporting($previousErrorReporting);
      http_response_code(500);
      echo erp_t('export_pdf_failed','Gagal membuat PDF').': '.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
      exit;
    }
    ob_end_clean();

    ini_set('display_errors', $previousDisplayErrors);
    error_reporting($previousErrorReporting);

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="'.$filename.'"');
    header('Content-Length: '.strlen($content));
    echo $content;
    exit;
  }
}
