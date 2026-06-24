<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function fry_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
function fry_json($status, $message = '', $extra = array()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(array('status'=>$status, 'message'=>$message), $extra)); exit; }
function fry_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function fry_num($value) { return number_format((float)$value, 2, '.', ','); }
function fry_pct($value) { return $value === null ? '-' : number_format((float)$value, 2, '.', ',').'%'; }
function fry_ratio($value) { return $value === null ? '-' : number_format((float)$value, 2, '.', ','); }
function fry_req($key, $default = '') { return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default; }
function fry_lower($value) { return strtolower(trim((string)$value)); }

function fry_table_exists($db, $table)
{
  return (bool)$db->fetch("SHOW TABLES LIKE ?", array($table));
}

function fry_filters()
{
  $end = fry_req('end_year', date('Y'));
  $start = fry_req('start_year', (string)((int)$end - 2));
  if (!preg_match('/^\d{4}$/', $start) || !preg_match('/^\d{4}$/', $end)) throw new Exception(fry_t('finance_invalid_year', 'Format tahun tidak valid.'));
  $start = (int)$start;
  $end = (int)$end;
  if ($start > $end) throw new Exception(fry_t('finance_start_year_after_end', 'Start year tidak boleh lebih besar dari end year.'));
  if (($end - $start + 1) > 10) throw new Exception(fry_t('finance_year_range_too_large', 'Rentang tahun maksimal 10 tahun agar performa aman.'));
  return array('start_year'=>$start, 'end_year'=>$end);
}

function fry_years($filters)
{
  $years = array();
  for ($year = $filters['start_year']; $year <= $filters['end_year']; $year++) $years[] = (string)$year;
  return $years;
}

function fry_fiscal_bounds($db, $year)
{
  if (fry_table_exists($db, 'erp_financial_period')) {
    $row = $db->fetch("SELECT MIN(start_date) start_date,MAX(end_date) end_date FROM erp_financial_period WHERE period_code LIKE ?", array($year.'-%'));
    if ($row && $row->start_date && $row->end_date) return array($row->start_date, $row->end_date, 'erp_financial_period');
  }
  return array($year.'-01-01', $year.'-12-31', 'calendar_year');
}

function fry_opening_warning($db, $year)
{
  $row = $db->fetch("SELECT COUNT(*) cnt,COALESCE(SUM(debet),0) debet,COALESCE(SUM(kredit),0) kredit FROM saldo_awal WHERE periode=?", array($year));
  if (!$row || (int)$row->cnt < 1) return fry_t('finance_opening_balance_warning', 'Saldo awal periode belum diisi; saldo awal akun dianggap 0.').' '.$year;
  if (abs((float)$row->debet - (float)$row->kredit) > 0.01) return fry_t('finance_opening_balance_unbalanced_warning', 'Saldo awal periode tidak balance.').' '.$year;
  return '';
}

function fry_is_cogs($category)
{
  $category = fry_lower($category);
  return strpos($category, 'hpp') !== false || strpos($category, 'pokok') !== false || strpos($category, 'persediaan') !== false || strpos($category, 'cost') !== false;
}

function fry_is_current_asset($category)
{
  $category = fry_lower($category);
  return strpos($category, 'lancar') !== false || strpos($category, 'kas') !== false || strpos($category, 'bank') !== false || strpos($category, 'piutang') !== false || strpos($category, 'persediaan') !== false || strpos($category, 'dibayar di muka') !== false;
}

function fry_is_current_liability($category)
{
  $category = fry_lower($category);
  return strpos($category, 'jangka pendek') !== false || strpos($category, 'hutang usaha') !== false || strpos($category, 'hutang pajak') !== false || strpos($category, 'hutang biaya') !== false;
}

function fry_pl_totals($db, $start, $end)
{
  $rows = $db->query(
    "SELECT k.kategori,k.kategori_akun,
      CASE
        WHEN k.kategori_akun='pendapatan' THEN SUM(COALESCE(d.kredit,0))-SUM(COALESCE(d.debet,0))
        WHEN k.kategori_akun='beban' THEN SUM(COALESCE(d.debet,0))-SUM(COALESCE(d.kredit,0))
        ELSE 0
      END amount
     FROM jurnal_detail d
     INNER JOIN jurnal_header h ON h.id=d.id_header
     INNER JOIN rekening r ON r.no_rek=d.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     WHERE h.tgl_jurnal BETWEEN ? AND ?
       AND h.posting_status='POSTED'
       AND k.kategori_akun IN ('pendapatan','beban')
     GROUP BY k.id,k.kategori,k.kategori_akun",
    array($start, $end)
  );
  if ($rows === false) throw new Exception('Query rasio P&L gagal: '.$db->getErrorMessage());
  $totals = array('revenue'=>0, 'expense'=>0, 'cogs'=>0, 'pl_lines'=>0);
  foreach ($rows as $row) {
    $amount = (float)$row->amount;
    if (abs($amount) >= 0.005) $totals['pl_lines']++;
    if ($row->kategori_akun === 'pendapatan') $totals['revenue'] += $amount;
    if ($row->kategori_akun === 'beban') {
      $totals['expense'] += $amount;
      if (fry_is_cogs($row->kategori)) $totals['cogs'] += $amount;
    }
  }
  $totals['gross_profit'] = $totals['revenue'] - $totals['cogs'];
  $totals['net_income'] = $totals['revenue'] - $totals['expense'];
  return $totals;
}

function fry_balance_totals($db, $year, $start, $end)
{
  $rows = $db->query(
    "SELECT k.kategori,k.kategori_akun,k.saldo_normal,
      CASE WHEN k.saldo_normal='kredit'
        THEN (COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))-(COALESCE(sa.debet,0)+COALESCE(j.debet,0))
        ELSE (COALESCE(sa.debet,0)+COALESCE(j.debet,0))-(COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))
      END saldo
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN (SELECT no_rek,SUM(COALESCE(debet,0)) debet,SUM(COALESCE(kredit,0)) kredit FROM saldo_awal WHERE periode=? GROUP BY no_rek) sa ON sa.no_rek=r.no_rek
     LEFT JOIN (SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' GROUP BY d.no_rek) j ON j.no_rek=r.no_rek
     WHERE k.kategori_akun IN ('aset','kewajiban','modal')",
    array($year, $start, $end)
  );
  if ($rows === false) throw new Exception('Query rasio neraca gagal: '.$db->getErrorMessage());
  $totals = array('assets'=>0, 'current_assets'=>0, 'liabilities'=>0, 'current_liabilities'=>0, 'equity'=>0, 'bs_lines'=>0);
  foreach ($rows as $row) {
    $saldo = (float)$row->saldo;
    if (abs($saldo) < 0.005) continue;
    $totals['bs_lines']++;
    if ($row->kategori_akun === 'aset') {
      $totals['assets'] += $saldo;
      if (fry_is_current_asset($row->kategori)) $totals['current_assets'] += $saldo;
    } elseif ($row->kategori_akun === 'kewajiban') {
      $totals['liabilities'] += $saldo;
      if (fry_is_current_liability($row->kategori)) $totals['current_liabilities'] += $saldo;
    } elseif ($row->kategori_akun === 'modal') {
      $totals['equity'] += $saldo;
    }
  }
  return $totals;
}

function fry_div($num, $den)
{
  return abs((float)$den) < 0.005 ? null : ((float)$num / (float)$den);
}

function fry_data($db, $years)
{
  $warnings = array();
  $bounds = array();
  $rowsByYear = array();
  $usedFiscalPeriod = false;
  foreach ($years as $year) {
    list($start, $end, $source) = fry_fiscal_bounds($db, $year);
    if ($source === 'erp_financial_period') $usedFiscalPeriod = true;
    $bounds[$year] = $start.' s/d '.$end;
    $openWarning = fry_opening_warning($db, $year);
    if ($openWarning !== '') $warnings[] = $openWarning;
    $pl = fry_pl_totals($db, $start, $end);
    $bs = fry_balance_totals($db, $year, $start, $end);
    if ($pl['pl_lines'] < 1) $warnings[] = fry_t('finance_no_pl_posted_warning', 'Tidak ada jurnal P&L POSTED pada periode ini.').' '.$year;
    if ($bs['bs_lines'] < 1) $warnings[] = fry_t('finance_no_balance_data_warning', 'Data neraca belum tersedia untuk tahun ini.').' '.$year;
    $rowsByYear[$year] = array_merge($pl, $bs);
    $rowsByYear[$year]['current_ratio'] = fry_div($bs['current_assets'], $bs['current_liabilities']);
    $rowsByYear[$year]['debt_to_asset'] = fry_div($bs['liabilities'], $bs['assets']);
    $rowsByYear[$year]['debt_to_equity'] = fry_div($bs['liabilities'], $bs['equity']);
    $rowsByYear[$year]['gross_margin'] = fry_div($pl['gross_profit'], $pl['revenue']);
    $rowsByYear[$year]['net_margin'] = fry_div($pl['net_income'], $pl['revenue']);
    $rowsByYear[$year]['roa'] = fry_div($pl['net_income'], $bs['assets']);
    $rowsByYear[$year]['roe'] = fry_div($pl['net_income'], $bs['equity']);
  }
  if (!$usedFiscalPeriod) $warnings[] = fry_t('finance_fiscal_calendar_fallback_warning', 'Fiscal period tidak tersedia; report memakai calendar year.');
  return array($rowsByYear, $bounds, array_values(array_unique($warnings)));
}

function fry_metric_defs()
{
  return array(
    'current_ratio'=>array('label'=>fry_t('finance_current_ratio', 'Current Ratio'), 'formula'=>fry_t('finance_formula_current_ratio', 'Aset lancar / kewajiban lancar'), 'format'=>'ratio'),
    'debt_to_asset'=>array('label'=>fry_t('finance_debt_to_asset', 'Debt to Asset'), 'formula'=>fry_t('finance_formula_debt_to_asset', 'Total kewajiban / total aset'), 'format'=>'percent'),
    'debt_to_equity'=>array('label'=>fry_t('finance_debt_to_equity', 'Debt to Equity'), 'formula'=>fry_t('finance_formula_debt_to_equity', 'Total kewajiban / total modal'), 'format'=>'ratio'),
    'gross_margin'=>array('label'=>fry_t('finance_gross_margin', 'Gross Margin'), 'formula'=>fry_t('finance_formula_gross_margin', '(Pendapatan - HPP) / pendapatan'), 'format'=>'percent'),
    'net_margin'=>array('label'=>fry_t('finance_net_margin', 'Net Margin'), 'formula'=>fry_t('finance_formula_net_margin', 'Laba bersih / pendapatan'), 'format'=>'percent'),
    'roa'=>array('label'=>fry_t('finance_roa', 'ROA'), 'formula'=>fry_t('finance_formula_roa', 'Laba bersih / total aset'), 'format'=>'percent'),
    'roe'=>array('label'=>fry_t('finance_roe', 'ROE'), 'formula'=>fry_t('finance_formula_roe', 'Laba bersih / total modal'), 'format'=>'percent')
  );
}

function fry_value_text($value, $format)
{
  return $format === 'percent' ? fry_pct($value === null ? null : $value * 100) : fry_ratio($value);
}

function fry_html($data, $years, $bounds, $warnings)
{
  $metrics = fry_metric_defs();
  $html = '';
  if (count($warnings)) $html .= '<div class="alert alert-warning"><strong>'.fry_h(fry_t('common_warning', 'Peringatan')).':</strong> '.fry_h(implode(' ', $warnings)).'</div>';
  $pairs = array();
  foreach ($bounds as $year=>$bound) $pairs[] = fry_h($year).': '.fry_h($bound);
  $html .= '<div class="alert alert-info"><i class="fa fa-info-circle"></i> '.fry_h(fry_t('finance_ratio_source_summary', 'Sumber: saldo_awal, jurnal_header/detail POSTED, rekening, dan coa_kategori.')).' '.implode('; ', $pairs).'</div>';
  $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed fry-table"><thead><tr class="bg-primary"><th style="min-width:220px">'.fry_h(fry_t('finance_ratio', 'Rasio')).'</th><th class="fry-formula">'.fry_h(fry_t('finance_formula', 'Formula')).'</th>';
  foreach ($years as $year) $html .= '<th class="text-right" style="min-width:115px">'.fry_h($year).'</th>';
  $html .= '</tr></thead><tbody>';
  $html .= '<tr class="fry-group"><th colspan="'.(count($years) + 2).'">'.fry_h(fry_t('finance_yearly_ratios', 'RASIO KEUANGAN TAHUNAN')).'</th></tr>';
  foreach ($metrics as $key=>$metric) {
    $html .= '<tr><td>'.fry_h($metric['label']).'</td><td class="fry-formula">'.fry_h($metric['formula']).'</td>';
    foreach ($years as $year) $html .= '<td class="text-right">'.fry_value_text($data[$year][$key], $metric['format']).'</td>';
    $html .= '</tr>';
  }
  $html .= '<tr class="fry-total"><th>'.fry_h(fry_t('finance_revenue', 'Pendapatan')).'</th><td class="fry-formula">'.fry_h(fry_t('finance_formula_revenue', 'Kategori akun pendapatan')).'</td>';
  foreach ($years as $year) $html .= '<td class="text-right">'.fry_num($data[$year]['revenue']).'</td>';
  $html .= '</tr><tr class="fry-total"><th>'.fry_h(fry_t('finance_net_income_period', 'Laba (Rugi) Bersih Periode')).'</th><td class="fry-formula">'.fry_h(fry_t('finance_formula_net_income', 'Pendapatan - total beban')).'</td>';
  foreach ($years as $year) $html .= '<td class="text-right">'.fry_num($data[$year]['net_income']).'</td>';
  return $html.'</tr></tbody></table></div>';
}

function fry_summary($data, $years)
{
  $last = $years[count($years) - 1];
  return array(
    'current_ratio_text'=>fry_value_text($data[$last]['current_ratio'], 'ratio'),
    'debt_to_asset_text'=>fry_value_text($data[$last]['debt_to_asset'], 'percent'),
    'net_margin_text'=>fry_value_text($data[$last]['net_margin'], 'percent'),
    'roe_text'=>fry_value_text($data[$last]['roe'], 'percent')
  );
}

function fry_print_page($data, $years, $bounds, $warnings)
{
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = fry_html($data, $years, $bounds, $warnings);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>'.fry_h(fry_t('finance_report_ratio_yearly', 'Rasio Keuangan (Per Tahun)')).'</title><link rel="stylesheet" href="'.fry_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1280px;margin:18px auto}.fry-table{width:100%;border-collapse:collapse!important}.fry-table th,.fry-table td{font-size:11px;border:1px solid #d2d6de!important;vertical-align:middle!important}.fry-group th{background:#1d4ed8!important;color:#fff!important}.fry-formula{color:#4b5563;white-space:normal!important}.fry-total th,.fry-total td{background:#f3f4f6!important;font-weight:bold}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.fry-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.fry_h($company).'</h3><h4 style="margin:0 0 14px">'.fry_h(fry_t('finance_report_ratio_yearly', 'Rasio Keuangan (Per Tahun)')).'</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  $filters = fry_filters();
  $years = fry_years($filters);
  list($data, $bounds, $warnings) = fry_data($db, $years);
  if ($act === 'filter') fry_json('success', 'OK', array('html'=>fry_html($data, $years, $bounds, $warnings), 'warnings'=>$warnings, 'summary'=>fry_summary($data, $years)));
  if ($act === 'print') fry_print_page($data, $years, $bounds, $warnings);
  if ($act === 'excel') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $metrics = fry_metric_defs();
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('Rasio Tahunan'));
    $sheet->setCellValue('A4', fry_t('finance_ratio', 'Rasio'));
    $sheet->setCellValue('B4', fry_t('finance_formula', 'Formula'));
    $col = 2;
    foreach ($years as $year) $sheet->setCellValueByColumnAndRow($col++, 4, $year);
    $lastCol = $col - 1;
    $row = 5;
    foreach ($metrics as $key=>$metric) {
      $sheet->setCellValue('A'.$row, $metric['label']);
      $sheet->setCellValue('B'.$row, $metric['formula']);
      $col = 2;
      foreach ($years as $year) {
        $value = $data[$year][$key];
        $sheet->setCellValueByColumnAndRow($col++, $row, $value === null ? null : ($metric['format'] === 'percent' ? $value * 100 : $value));
      }
      $row++;
    }
    foreach (array(fry_t('finance_revenue', 'Pendapatan')=>'revenue', fry_t('finance_net_income_period', 'Laba (Rugi) Bersih Periode')=>'net_income') as $label=>$key) {
      $sheet->setCellValue('A'.$row, $label);
      $col = 2;
      foreach ($years as $year) $sheet->setCellValueByColumnAndRow($col++, $row, $data[$year][$key]);
      $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row)->getFont()->setBold(true);
      $row++;
    }
    $decimalCols = array();
    for ($i = 2; $i <= $lastCol; $i++) $decimalCols[] = PHPExcel_Cell::stringFromColumnIndex($i);
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet, 'title'=>erp_export_title(fry_t('finance_report_ratio_yearly', 'RASIO KEUANGAN PER TAHUN')), 'header_row'=>4, 'first_data_row'=>5, 'last_data_row'=>max(5, $row - 1), 'column_count'=>$lastCol + 1, 'decimal_columns'=>$decimalCols, 'filters'=>array(fry_t('finance_period', 'Periode')=>$filters['start_year'].' s/d '.$filters['end_year'], 'Status'=>'POSTED')));
    $tmp = erpkb_excel_temp_file('rasio_keuangan_per_tahun_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = @filesize($tmp);
    $sig = @file_get_contents($tmp, false, null, 0, 2);
    if (!$size || $sig !== 'PK') { @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="rasio_keuangan_per_tahun_'.$filters['start_year'].'_sd_'.$filters['end_year'].'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp);
    @unlink($tmp);
    exit;
  }
  fry_json('error', fry_t('common_unknown_action', 'Action tidak dikenal.'));
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  fry_json('error', $e->getMessage());
}
?>
