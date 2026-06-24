<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function gib_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
function gib_json($status, $message = '', $extra = array()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(array('status'=>$status, 'message'=>$message), $extra)); exit; }
function gib_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function gib_num($value) { return number_format((float)$value, 2, '.', ','); }
function gib_req($key, $default = '') { return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default; }
function gib_month_ok($value) { return preg_match('/^\d{4}\-\d{2}$/', (string)$value) && strtotime($value.'-01') !== false; }

function gib_months($start, $end)
{
  $months = array();
  $cursor = new DateTime($start.'-01');
  $limit = new DateTime($end.'-01');
  while ($cursor <= $limit) { $months[] = $cursor->format('Y-m'); $cursor->modify('+1 month'); }
  return $months;
}

function gib_month_label($month) { return date('M Y', strtotime($month.'-01')); }

function gib_filters()
{
  $start = trim((string)gib_req('start_month', date('Y-m')));
  $end = trim((string)gib_req('end_month', date('Y-m')));
  if (!gib_month_ok($start) || !gib_month_ok($end)) throw new Exception(gib_t('finance_invalid_month', 'Format bulan tidak valid.'));
  if (strtotime($start.'-01') > strtotime($end.'-01')) throw new Exception(gib_t('finance_start_month_after_end', 'Start month tidak boleh lebih besar dari end month.'));
  $months = gib_months($start, $end);
  if (count($months) > 12) throw new Exception(gib_t('finance_month_range_too_large', 'Rentang bulan maksimal 12 bulan.'));
  return array('start_month'=>$start, 'end_month'=>$end, 'months'=>$months);
}

function gib_data($db, $filters)
{
  $rows = array();
  $warnings = array();
  $totals = array('revenue'=>0, 'expense'=>0, 'net'=>0, 'line_count'=>0);
  foreach ($filters['months'] as $month) {
    $start = $month.'-01';
    $end = date('Y-m-t', strtotime($start));
    $row = $db->fetch(
      "SELECT
         SUM(CASE WHEN k.kategori_akun='pendapatan' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0) ELSE 0 END) revenue,
         SUM(CASE WHEN k.kategori_akun='beban' THEN COALESCE(d.debet,0)-COALESCE(d.kredit,0) ELSE 0 END) expense,
         COUNT(d.id) line_count
       FROM jurnal_detail d
       INNER JOIN jurnal_header h ON h.id=d.id_header
       INNER JOIN rekening r ON r.no_rek=d.no_rek
       INNER JOIN coa_kategori k ON k.id=r.kat_coa
       WHERE h.tgl_jurnal BETWEEN ? AND ?
         AND h.posting_status='POSTED'
         AND k.kategori_akun IN ('pendapatan','beban')",
      array($start, $end)
    );
    if ($row === false) throw new Exception('Query grafik pendapatan biaya gagal: '.$db->getErrorMessage());
    $revenue = $row ? (float)$row->revenue : 0;
    $expense = $row ? (float)$row->expense : 0;
    $lineCount = $row ? (int)$row->line_count : 0;
    $net = $revenue - $expense;
    $rows[] = array('month'=>$month, 'revenue'=>$revenue, 'expense'=>$expense, 'net'=>$net, 'line_count'=>$lineCount);
    $totals['revenue'] += $revenue;
    $totals['expense'] += $expense;
    $totals['net'] += $net;
    $totals['line_count'] += $lineCount;
  }
  if ($totals['line_count'] === 0) $warnings[] = gib_t('finance_no_income_expense_warning', 'Tidak ada jurnal pendapatan/biaya POSTED pada periode ini.');
  return array($rows, $totals, $warnings);
}

function gib_chart($rows)
{
  $labels = array(); $revenue = array(); $expense = array(); $net = array();
  foreach ($rows as $row) {
    $labels[] = gib_month_label($row['month']);
    $revenue[] = $row['revenue'];
    $expense[] = $row['expense'];
    $net[] = $row['net'];
  }
  return array('labels'=>$labels, 'datasets'=>array(
    array('type'=>'bar', 'label'=>gib_t('finance_revenue', 'Pendapatan'), 'data'=>$revenue, 'backgroundColor'=>'#1d4ed8'),
    array('type'=>'bar', 'label'=>gib_t('finance_expense', 'Biaya'), 'data'=>$expense, 'backgroundColor'=>'#f97316'),
    array('type'=>'line', 'label'=>gib_t('finance_net_profit_loss', 'Laba/Rugi'), 'data'=>$net, 'borderColor'=>'#0f766e', 'backgroundColor'=>'#0f766e', 'tension'=>0.25, 'fill'=>false)
  ));
}

function gib_html($rows, $totals, $warnings, $filters)
{
  $html = '';
  if (count($warnings)) $html .= '<div class="alert alert-warning"><strong>'.gib_h(gib_t('common_warning', 'Peringatan')).':</strong> '.gib_h(implode(' ', $warnings)).'</div>';
  $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed gib-table"><thead><tr class="bg-primary"><th>'.gib_h(gib_t('finance_month', 'Bulan')).'</th><th class="text-right">'.gib_h(gib_t('finance_revenue', 'Pendapatan')).'</th><th class="text-right">'.gib_h(gib_t('finance_expense', 'Biaya')).'</th><th class="text-right">'.gib_h(gib_t('finance_net_profit_loss', 'Laba/Rugi')).'</th></tr></thead><tbody>';
  if (!count($rows)) $html .= '<tr><td colspan="4" class="text-center text-muted">'.gib_h(gib_t('finance_empty_period_warning', 'Tidak ada data POSTED pada periode ini.')).'</td></tr>';
  foreach ($rows as $row) $html .= '<tr><td>'.gib_h(gib_month_label($row['month'])).'</td><td class="text-right">'.gib_num($row['revenue']).'</td><td class="text-right">'.gib_num($row['expense']).'</td><td class="text-right">'.gib_num($row['net']).'</td></tr>';
  $html .= '</tbody><tfoot><tr><th>'.gib_h(gib_t('common_total', 'Total')).'</th><th class="text-right">'.gib_num($totals['revenue']).'</th><th class="text-right">'.gib_num($totals['expense']).'</th><th class="text-right">'.gib_num($totals['net']).'</th></tr></tfoot></table></div>';
  return $html;
}

function gib_svg_chart($rows)
{
  $width = 1060; $height = 320; $left = 70; $right = 24; $top = 28; $bottom = 52;
  $values = array();
  foreach ($rows as $row) { $values[] = $row['revenue']; $values[] = $row['expense']; $values[] = $row['net']; }
  if (!count($values)) $values = array(0);
  $min = min(0, min($values)); $max = max($values);
  if (abs($max - $min) < 0.005) { $max += 1; $min -= 1; }
  $plotW = $width - $left - $right; $plotH = $height - $top - $bottom;
  $xBand = count($rows) ? $plotW / count($rows) : $plotW; $barW = min(26, $xBand / 5);
  $yFor = function($v) use ($top, $plotH, $min, $max) { return $top + $plotH - (($v - $min) / ($max - $min) * $plotH); };
  $svg = '<svg class="gib-print-chart" width="100%" viewBox="0 0 '.$width.' '.$height.'" xmlns="http://www.w3.org/2000/svg">';
  $svg .= '<rect x="0" y="0" width="'.$width.'" height="'.$height.'" fill="#fff"/><line x1="'.$left.'" y1="'.$top.'" x2="'.$left.'" y2="'.($top+$plotH).'" stroke="#94a3b8"/><line x1="'.$left.'" y1="'.$yFor(0).'" x2="'.($left+$plotW).'" y2="'.$yFor(0).'" stroke="#94a3b8"/>';
  $netPoints = array();
  foreach ($rows as $i=>$row) {
    $cx = $left + ($xBand * $i) + ($xBand / 2);
    foreach (array(array('revenue','#1d4ed8',-$barW-2), array('expense','#f97316',2)) as $bar) {
      $value = (float)$row[$bar[0]];
      $y = $yFor(max(0, $value)); $zero = $yFor(0);
      $svg .= '<rect x="'.round($cx+$bar[2],2).'" y="'.round(min($y,$zero),2).'" width="'.round($barW,2).'" height="'.round(abs($zero-$y),2).'" fill="'.$bar[1].'"/>';
    }
    $netPoints[] = round($cx,2).','.round($yFor((float)$row['net']),2);
    $svg .= '<text x="'.round($cx,2).'" y="'.($height-18).'" font-size="10" text-anchor="middle" fill="#475569">'.gib_h(gib_month_label($row['month'])).'</text>';
  }
  $svg .= '<polyline points="'.implode(' ', $netPoints).'" fill="none" stroke="#0f766e" stroke-width="2.5"/>';
  $svg .= '<text x="'.$left.'" y="16" font-size="11" fill="#1d4ed8">'.gib_h(gib_t('finance_revenue', 'Pendapatan')).'</text><text x="'.($left+160).'" y="16" font-size="11" fill="#f97316">'.gib_h(gib_t('finance_expense', 'Biaya')).'</text><text x="'.($left+300).'" y="16" font-size="11" fill="#0f766e">'.gib_h(gib_t('finance_net_profit_loss', 'Laba/Rugi')).'</text>';
  return $svg.'</svg>';
}

function gib_print_page($rows, $totals, $warnings, $filters)
{
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = gib_svg_chart($rows).gib_html($rows, $totals, $warnings, $filters);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>'.gib_h(gib_t('finance_report_income_vs_expense_chart', 'Grafik Pendapatan berbanding Biaya')).'</title><link rel="stylesheet" href="'.gib_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1180px;margin:18px auto}.gib-table th,.gib-table td{font-size:11px;border:1px solid #d2d6de!important}.no-print{margin-bottom:12px}@media print{.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.gib_h($company).'</h3><h4 style="margin:0 0 14px">'.gib_h(gib_t('finance_report_income_vs_expense_chart', 'Grafik Pendapatan berbanding Biaya')).'</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  $filters = gib_filters();
  list($rows, $totals, $warnings) = gib_data($db, $filters);
  if ($act === 'filter') gib_json('success', 'OK', array('html'=>gib_html($rows, $totals, $warnings, $filters), 'warnings'=>$warnings, 'chart'=>gib_chart($rows)));
  if ($act === 'print') gib_print_page($rows, $totals, $warnings, $filters);
  if ($act === 'excel') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('Income vs Expense'));
    $sheet->setCellValue('A4', gib_t('finance_month', 'Bulan'));
    $sheet->setCellValue('B4', gib_t('finance_revenue', 'Pendapatan'));
    $sheet->setCellValue('C4', gib_t('finance_expense', 'Biaya'));
    $sheet->setCellValue('D4', gib_t('finance_net_profit_loss', 'Laba/Rugi'));
    $r = 5;
    foreach ($rows as $row) {
      $sheet->setCellValue('A'.$r, gib_month_label($row['month']));
      $sheet->setCellValue('B'.$r, $row['revenue']);
      $sheet->setCellValue('C'.$r, $row['expense']);
      $sheet->setCellValue('D'.$r, $row['net']);
      $r++;
    }
    $sheet->setCellValue('A'.$r, gib_t('common_total', 'Total'));
    $sheet->setCellValue('B'.$r, $totals['revenue']);
    $sheet->setCellValue('C'.$r, $totals['expense']);
    $sheet->setCellValue('D'.$r, $totals['net']);
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet, 'title'=>erp_export_title(gib_t('finance_report_income_vs_expense_chart', 'GRAFIK PENDAPATAN BERBANDING BIAYA')), 'header_row'=>4, 'first_data_row'=>5, 'last_data_row'=>$r, 'column_count'=>4, 'money_columns'=>array('B','C','D'), 'filters'=>array(gib_t('finance_period', 'Periode')=>$filters['start_month'].' s/d '.$filters['end_month'], 'Status'=>'POSTED')));
    $tmp = erpkb_excel_temp_file('grafik_pendapatan_berbanding_biaya_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = @filesize($tmp);
    $sig = @file_get_contents($tmp, false, null, 0, 2);
    if (!$size || $sig !== 'PK') { @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="grafik_pendapatan_berbanding_biaya_'.$filters['start_month'].'_sd_'.$filters['end_month'].'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp);
    @unlink($tmp);
    exit;
  }
  gib_json('error', gib_t('common_unknown_action', 'Action tidak dikenal.'));
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  gib_json('error', $e->getMessage());
}
?>
