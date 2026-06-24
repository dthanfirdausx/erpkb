<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function groa_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
function groa_json($status, $message = '', $extra = array()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(array('status'=>$status, 'message'=>$message), $extra)); exit; }
function groa_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function groa_num($value) { return number_format((float)$value, 2, '.', ','); }
function groa_pct($value) { return $value === null ? '-' : number_format((float)$value, 2, '.', ',').'%'; }
function groa_req($key, $default = '') { return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default; }
function groa_month_ok($value) { return preg_match('/^\d{4}\-\d{2}$/', (string)$value) && strtotime($value.'-01') !== false; }

function groa_months($start, $end)
{
  $months = array();
  $cursor = new DateTime($start.'-01');
  $limit = new DateTime($end.'-01');
  while ($cursor <= $limit) { $months[] = $cursor->format('Y-m'); $cursor->modify('+1 month'); }
  return $months;
}

function groa_month_label($month) { return date('M Y', strtotime($month.'-01')); }

function groa_filters()
{
  $start = groa_req('start_month', date('Y-m'));
  $end = groa_req('end_month', date('Y-m'));
  if (!groa_month_ok($start) || !groa_month_ok($end)) throw new Exception(groa_t('finance_invalid_month', 'Format bulan tidak valid.'));
  if (strtotime($start.'-01') > strtotime($end.'-01')) throw new Exception(groa_t('finance_start_month_after_end', 'Start month tidak boleh lebih besar dari end month.'));
  $months = groa_months($start, $end);
  if (count($months) > 12) throw new Exception(groa_t('finance_month_range_too_large', 'Rentang bulan maksimal 12 bulan.'));
  return array('start_month'=>$start, 'end_month'=>$end, 'months'=>$months);
}

function groa_opening_warning($db, $year)
{
  $row = $db->fetch("SELECT COUNT(*) cnt,COALESCE(SUM(debet),0) debet,COALESCE(SUM(kredit),0) kredit FROM saldo_awal WHERE periode=?", array($year));
  if (!$row || (int)$row->cnt < 1) return groa_t('finance_opening_balance_warning', 'Saldo awal periode belum diisi; saldo awal akun dianggap 0.').' '.$year;
  if (abs((float)$row->debet - (float)$row->kredit) > 0.01) return groa_t('finance_opening_balance_unbalanced_warning', 'Saldo awal periode tidak balance.').' '.$year;
  return '';
}

function groa_net_income($db, $start, $end)
{
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
  if ($row === false) throw new Exception('Query ROA laba bersih gagal: '.$db->getErrorMessage());
  return array('net_income'=>($row ? (float)$row->revenue : 0) - ($row ? (float)$row->expense : 0), 'line_count'=>$row ? (int)$row->line_count : 0);
}

function groa_total_assets($db, $year, $end)
{
  $yearStart = $year.'-01-01';
  $row = $db->fetch(
    "SELECT
       SUM(CASE WHEN k.saldo_normal='kredit'
         THEN (COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))-(COALESCE(sa.debet,0)+COALESCE(j.debet,0))
         ELSE (COALESCE(sa.debet,0)+COALESCE(j.debet,0))-(COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))
       END) assets,
       COUNT(r.no_rek) line_count
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN (SELECT no_rek,SUM(COALESCE(debet,0)) debet,SUM(COALESCE(kredit,0)) kredit FROM saldo_awal WHERE periode=? GROUP BY no_rek) sa ON sa.no_rek=r.no_rek
     LEFT JOIN (SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' GROUP BY d.no_rek) j ON j.no_rek=r.no_rek
     WHERE k.kategori_akun='aset'",
    array($year, $yearStart, $end)
  );
  if ($row === false) throw new Exception('Query ROA total aset gagal: '.$db->getErrorMessage());
  return array('assets'=>$row ? (float)$row->assets : 0, 'line_count'=>$row ? (int)$row->line_count : 0);
}

function groa_data($db, $filters)
{
  $rows = array(); $warnings = array(); $yearsChecked = array();
  foreach ($filters['months'] as $month) {
    $year = (int)substr($month, 0, 4);
    if (!isset($yearsChecked[$year])) {
      $warning = groa_opening_warning($db, $year);
      if ($warning !== '') $warnings[] = $warning;
      $yearsChecked[$year] = true;
    }
    $start = $month.'-01';
    $end = date('Y-m-t', strtotime($start));
    $pl = groa_net_income($db, $start, $end);
    $assets = groa_total_assets($db, $year, $end);
    if ($pl['line_count'] < 1) $warnings[] = groa_t('finance_no_pl_posted_warning', 'Tidak ada jurnal P&L POSTED pada periode ini.').' '.$month;
    if ($assets['line_count'] < 1 || abs($assets['assets']) < 0.005) $warnings[] = groa_t('finance_no_balance_data_warning', 'Data neraca belum tersedia untuk tahun ini.').' '.$month;
    $roa = abs($assets['assets']) < 0.005 ? null : ($pl['net_income'] / $assets['assets'] * 100);
    $rows[] = array('month'=>$month, 'net_income'=>$pl['net_income'], 'assets'=>$assets['assets'], 'roa'=>$roa);
  }
  return array($rows, array_values(array_unique($warnings)));
}

function groa_chart($rows)
{
  $labels = array(); $values = array();
  foreach ($rows as $row) { $labels[] = groa_month_label($row['month']); $values[] = $row['roa']; }
  return array('labels'=>$labels, 'datasets'=>array(array('label'=>groa_t('finance_roa_percent', 'ROA %'), 'data'=>$values, 'borderColor'=>'#0f766e', 'backgroundColor'=>'#0f766e', 'tension'=>0.25, 'fill'=>false)));
}

function groa_html($rows, $warnings)
{
  $html = '';
  if (count($warnings)) $html .= '<div class="alert alert-warning"><strong>'.groa_h(groa_t('common_warning', 'Peringatan')).':</strong> '.groa_h(implode(' ', $warnings)).'</div>';
  $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed groa-table"><thead><tr class="bg-primary"><th>'.groa_h(groa_t('finance_month', 'Bulan')).'</th><th class="text-right">'.groa_h(groa_t('finance_net_income_period', 'Laba Bersih')).'</th><th class="text-right">'.groa_h(groa_t('finance_total_assets', 'Total Aset')).'</th><th class="text-right">'.groa_h(groa_t('finance_roa_percent', 'ROA %')).'</th></tr></thead><tbody>';
  if (!count($rows)) $html .= '<tr><td colspan="4" class="text-center text-muted">'.groa_h(groa_t('finance_empty_period_warning', 'Tidak ada data POSTED pada periode ini.')).'</td></tr>';
  foreach ($rows as $row) $html .= '<tr><td>'.groa_h(groa_month_label($row['month'])).'</td><td class="text-right">'.groa_num($row['net_income']).'</td><td class="text-right">'.groa_num($row['assets']).'</td><td class="text-right">'.groa_pct($row['roa']).'</td></tr>';
  return $html.'</tbody></table></div>';
}

function groa_svg_chart($rows)
{
  $width = 1060; $height = 300; $left = 70; $right = 24; $top = 28; $bottom = 52;
  $values = array();
  foreach ($rows as $row) if ($row['roa'] !== null) $values[] = (float)$row['roa'];
  if (!count($values)) $values = array(0);
  $min = min(0, min($values)); $max = max($values);
  if (abs($max - $min) < 0.005) { $max += 1; $min -= 1; }
  $plotW = $width - $left - $right; $plotH = $height - $top - $bottom;
  $xFor = function($i) use ($left, $plotW, $rows) { return $left + (count($rows) <= 1 ? 0 : ($plotW * $i / (count($rows) - 1))); };
  $yFor = function($v) use ($top, $plotH, $min, $max) { return $top + $plotH - (($v - $min) / ($max - $min) * $plotH); };
  $points = array();
  foreach ($rows as $i=>$row) if ($row['roa'] !== null) $points[] = round($xFor($i),2).','.round($yFor((float)$row['roa']),2);
  $svg = '<svg class="groa-print-chart" width="100%" viewBox="0 0 '.$width.' '.$height.'" xmlns="http://www.w3.org/2000/svg">';
  $svg .= '<rect x="0" y="0" width="'.$width.'" height="'.$height.'" fill="#fff"/><line x1="'.$left.'" y1="'.$top.'" x2="'.$left.'" y2="'.($top+$plotH).'" stroke="#94a3b8"/><line x1="'.$left.'" y1="'.($top+$plotH).'" x2="'.($left+$plotW).'" y2="'.($top+$plotH).'" stroke="#94a3b8"/>';
  foreach ($rows as $i=>$row) $svg .= '<text x="'.round($xFor($i),2).'" y="'.($height-18).'" font-size="10" text-anchor="middle" fill="#475569">'.groa_h(groa_month_label($row['month'])).'</text>';
  $svg .= '<polyline points="'.implode(' ', $points).'" fill="none" stroke="#0f766e" stroke-width="2.5"/>';
  $svg .= '<text x="'.$left.'" y="16" font-size="11" fill="#0f766e">'.groa_h(groa_t('finance_roa_percent', 'ROA %')).'</text>';
  return $svg.'</svg>';
}

function groa_print_page($rows, $warnings, $filters)
{
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = groa_svg_chart($rows).groa_html($rows, $warnings);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>'.groa_h(groa_t('finance_report_roa_chart', 'Grafik Pengembalian Aset')).'</title><link rel="stylesheet" href="'.groa_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1180px;margin:18px auto}.groa-table th,.groa-table td{font-size:11px;border:1px solid #d2d6de!important}.no-print{margin-bottom:12px}@media print{.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.groa_h($company).'</h3><h4 style="margin:0 0 14px">'.groa_h(groa_t('finance_report_roa_chart', 'Grafik Pengembalian Aset')).'</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  $filters = groa_filters();
  list($rows, $warnings) = groa_data($db, $filters);
  if ($act === 'filter') groa_json('success', 'OK', array('html'=>groa_html($rows, $warnings), 'warnings'=>$warnings, 'chart'=>groa_chart($rows)));
  if ($act === 'print') groa_print_page($rows, $warnings, $filters);
  if ($act === 'excel') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('ROA'));
    $sheet->setCellValue('A4', groa_t('finance_month', 'Bulan'));
    $sheet->setCellValue('B4', groa_t('finance_net_income_period', 'Laba Bersih'));
    $sheet->setCellValue('C4', groa_t('finance_total_assets', 'Total Aset'));
    $sheet->setCellValue('D4', groa_t('finance_roa_percent', 'ROA %'));
    $r = 5;
    foreach ($rows as $row) {
      $sheet->setCellValue('A'.$r, groa_month_label($row['month']));
      $sheet->setCellValue('B'.$r, $row['net_income']);
      $sheet->setCellValue('C'.$r, $row['assets']);
      if ($row['roa'] === null) $sheet->setCellValue('D'.$r, '-'); else $sheet->setCellValue('D'.$r, $row['roa']);
      $r++;
    }
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet, 'title'=>erp_export_title(groa_t('finance_report_roa_chart', 'GRAFIK PENGEMBALIAN ASET')), 'header_row'=>4, 'first_data_row'=>5, 'last_data_row'=>max(5, $r - 1), 'column_count'=>4, 'money_columns'=>array('B','C'), 'filters'=>array(groa_t('finance_period', 'Periode')=>$filters['start_month'].' s/d '.$filters['end_month'], 'Status'=>'POSTED')));
    $tmp = erpkb_excel_temp_file('grafik_pengembalian_aset_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = @filesize($tmp);
    $sig = @file_get_contents($tmp, false, null, 0, 2);
    if (!$size || $sig !== 'PK') { @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="grafik_pengembalian_aset_'.$filters['start_month'].'_sd_'.$filters['end_month'].'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp);
    @unlink($tmp);
    exit;
  }
  groa_json('error', groa_t('common_unknown_action', 'Action tidak dikenal.'));
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  groa_json('error', $e->getMessage());
}
?>
