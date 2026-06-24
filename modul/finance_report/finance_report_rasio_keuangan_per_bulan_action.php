<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function frm_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
function frm_json($status, $message = '', $extra = array()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(array('status'=>$status, 'message'=>$message), $extra)); exit; }
function frm_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function frm_num($value) { return number_format((float)$value, 2, '.', ','); }
function frm_pct($value) { return $value === null ? '-' : number_format((float)$value, 2, '.', ',').'%'; }
function frm_ratio($value) { return $value === null ? '-' : number_format((float)$value, 2, '.', ','); }
function frm_req($key, $default = '') { return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default; }
function frm_lower($value) { return strtolower(trim((string)$value)); }
function frm_month_ok($value) { return preg_match('/^\d{4}\-\d{2}$/', (string)$value) && strtotime($value.'-01') !== false; }

function frm_filters()
{
  $start = frm_req('start_month', date('Y-m'));
  $end = frm_req('end_month', date('Y-m'));
  if (!frm_month_ok($start) || !frm_month_ok($end)) throw new Exception(frm_t('finance_invalid_month', 'Format bulan tidak valid.'));
  $startDate = $start.'-01';
  $endDate = date('Y-m-t', strtotime($end.'-01'));
  if (strtotime($startDate) > strtotime($endDate)) throw new Exception(frm_t('finance_start_month_after_end', 'Start month tidak boleh lebih besar dari end month.'));
  $months = frm_months($start, $end);
  if (count($months) > 12) throw new Exception(frm_t('finance_month_range_too_large', 'Rentang bulan maksimal 12 bulan.'));
  return array('start_month'=>$start, 'end_month'=>$end);
}

function frm_months($start, $end)
{
  $months = array();
  $cursor = new DateTime($start.'-01');
  $limit = new DateTime($end.'-01');
  while ($cursor <= $limit) {
    $months[] = $cursor->format('Y-m');
    $cursor->modify('+1 month');
  }
  return $months;
}

function frm_month_label($month)
{
  return date('M Y', strtotime($month.'-01'));
}

function frm_opening_warning($db, $year)
{
  $row = $db->fetch("SELECT COUNT(*) cnt,COALESCE(SUM(debet),0) debet,COALESCE(SUM(kredit),0) kredit FROM saldo_awal WHERE periode=?", array($year));
  if (!$row || (int)$row->cnt < 1) return frm_t('finance_opening_balance_warning', 'Saldo awal periode belum diisi; saldo awal akun dianggap 0.').' '.$year;
  if (abs((float)$row->debet - (float)$row->kredit) > 0.01) return frm_t('finance_opening_balance_unbalanced_warning', 'Saldo awal periode tidak balance.').' '.$year;
  return '';
}

function frm_is_cogs($category)
{
  $category = frm_lower($category);
  return strpos($category, 'hpp') !== false || strpos($category, 'pokok') !== false || strpos($category, 'persediaan') !== false || strpos($category, 'cost') !== false;
}

function frm_is_current_asset($category)
{
  $category = frm_lower($category);
  return strpos($category, 'lancar') !== false || strpos($category, 'kas') !== false || strpos($category, 'bank') !== false || strpos($category, 'piutang') !== false || strpos($category, 'persediaan') !== false || strpos($category, 'dibayar di muka') !== false;
}

function frm_is_current_liability($category)
{
  $category = frm_lower($category);
  return strpos($category, 'jangka pendek') !== false || strpos($category, 'hutang usaha') !== false || strpos($category, 'hutang pajak') !== false || strpos($category, 'hutang biaya') !== false;
}

function frm_pl_totals($db, $start, $end)
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
  if ($rows === false) throw new Exception('Query rasio P&L bulanan gagal: '.$db->getErrorMessage());
  $totals = array('revenue'=>0, 'expense'=>0, 'cogs'=>0, 'pl_lines'=>0);
  foreach ($rows as $row) {
    $amount = (float)$row->amount;
    if (abs($amount) >= 0.005) $totals['pl_lines']++;
    if ($row->kategori_akun === 'pendapatan') $totals['revenue'] += $amount;
    if ($row->kategori_akun === 'beban') {
      $totals['expense'] += $amount;
      if (frm_is_cogs($row->kategori)) $totals['cogs'] += $amount;
    }
  }
  $totals['gross_profit'] = $totals['revenue'] - $totals['cogs'];
  $totals['net_income'] = $totals['revenue'] - $totals['expense'];
  return $totals;
}

function frm_balance_totals($db, $year, $end)
{
  $yearStart = $year.'-01-01';
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
    array($year, $yearStart, $end)
  );
  if ($rows === false) throw new Exception('Query rasio neraca bulanan gagal: '.$db->getErrorMessage());
  $totals = array('assets'=>0, 'current_assets'=>0, 'liabilities'=>0, 'current_liabilities'=>0, 'equity'=>0, 'bs_lines'=>0);
  foreach ($rows as $row) {
    $saldo = (float)$row->saldo;
    if (abs($saldo) < 0.005) continue;
    $totals['bs_lines']++;
    if ($row->kategori_akun === 'aset') {
      $totals['assets'] += $saldo;
      if (frm_is_current_asset($row->kategori)) $totals['current_assets'] += $saldo;
    } elseif ($row->kategori_akun === 'kewajiban') {
      $totals['liabilities'] += $saldo;
      if (frm_is_current_liability($row->kategori)) $totals['current_liabilities'] += $saldo;
    } elseif ($row->kategori_akun === 'modal') {
      $totals['equity'] += $saldo;
    }
  }
  return $totals;
}

function frm_div($num, $den)
{
  return abs((float)$den) < 0.005 ? null : ((float)$num / (float)$den);
}

function frm_data($db, $months)
{
  $warnings = array();
  $data = array();
  $yearsChecked = array();
  foreach ($months as $month) {
    $year = (int)substr($month, 0, 4);
    if (!isset($yearsChecked[$year])) {
      $warning = frm_opening_warning($db, $year);
      if ($warning !== '') $warnings[] = $warning;
      $yearsChecked[$year] = true;
    }
    $start = $month.'-01';
    $end = date('Y-m-t', strtotime($start));
    $pl = frm_pl_totals($db, $start, $end);
    $bs = frm_balance_totals($db, $year, $end);
    if ($pl['pl_lines'] < 1) $warnings[] = frm_t('finance_no_pl_posted_warning', 'Tidak ada jurnal P&L POSTED pada periode ini.').' '.$month;
    if ($bs['bs_lines'] < 1) $warnings[] = frm_t('finance_no_balance_data_warning', 'Data neraca belum tersedia untuk tahun ini.').' '.$month;
    if ($bs['current_assets'] == 0 && $bs['current_liabilities'] == 0) $warnings[] = frm_t('finance_ratio_category_mapping_warning', 'Kategori aset/kewajiban lancar belum cukup untuk menghitung current ratio.').' '.$month;
    $data[$month] = array_merge($pl, $bs);
    $data[$month]['current_ratio'] = frm_div($bs['current_assets'], $bs['current_liabilities']);
    $data[$month]['debt_ratio'] = frm_div($bs['liabilities'], $bs['assets']);
    $data[$month]['gross_margin'] = frm_div($pl['gross_profit'], $pl['revenue']);
    $data[$month]['net_margin'] = frm_div($pl['net_income'], $pl['revenue']);
    $data[$month]['roa'] = frm_div($pl['net_income'], $bs['assets']);
    $data[$month]['roe'] = frm_div($pl['net_income'], $bs['equity']);
  }
  return array($data, array_values(array_unique($warnings)));
}

function frm_metric_defs()
{
  return array(
    'current_ratio'=>array('label'=>frm_t('finance_current_ratio', 'Current Ratio'), 'formula'=>frm_t('finance_formula_current_ratio', 'Aset lancar / kewajiban lancar'), 'format'=>'ratio'),
    'debt_ratio'=>array('label'=>frm_t('finance_debt_ratio', 'Debt Ratio'), 'formula'=>frm_t('finance_formula_debt_ratio', 'Total kewajiban / total aset'), 'format'=>'percent'),
    'gross_margin'=>array('label'=>frm_t('finance_gross_margin', 'Gross Margin'), 'formula'=>frm_t('finance_formula_gross_margin', '(Pendapatan - HPP) / pendapatan'), 'format'=>'percent'),
    'net_margin'=>array('label'=>frm_t('finance_net_margin', 'Net Margin'), 'formula'=>frm_t('finance_formula_net_margin', 'Laba bersih / pendapatan'), 'format'=>'percent'),
    'roa'=>array('label'=>frm_t('finance_roa', 'ROA'), 'formula'=>frm_t('finance_formula_roa', 'Laba bersih / total aset'), 'format'=>'percent'),
    'roe'=>array('label'=>frm_t('finance_roe', 'ROE'), 'formula'=>frm_t('finance_formula_roe', 'Laba bersih / total modal'), 'format'=>'percent')
  );
}

function frm_value_text($value, $format)
{
  return $format === 'percent' ? frm_pct($value === null ? null : $value * 100) : frm_ratio($value);
}

function frm_html($data, $months, $warnings)
{
  $metrics = frm_metric_defs();
  $html = '';
  if (count($warnings)) $html .= '<div class="alert alert-warning"><strong>'.frm_h(frm_t('common_warning', 'Peringatan')).':</strong> '.frm_h(implode(' ', $warnings)).'</div>';
  $html .= '<div class="alert alert-info"><i class="fa fa-info-circle"></i> '.frm_h(frm_t('finance_ratio_monthly_source_summary', 'Sumber: saldo_awal, jurnal_header/detail POSTED, rekening, dan coa_kategori. P&L per bulan; neraca sampai akhir bulan.')).'</div>';
  $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed frm-table"><thead><tr class="bg-primary"><th style="min-width:220px">'.frm_h(frm_t('finance_ratio', 'Rasio')).'</th><th class="frm-formula">'.frm_h(frm_t('finance_formula', 'Formula')).'</th>';
  foreach ($months as $month) $html .= '<th class="text-right" style="min-width:115px">'.frm_h(frm_month_label($month)).'</th>';
  $html .= '</tr></thead><tbody>';
  $html .= '<tr class="frm-group"><th colspan="'.(count($months) + 2).'">'.frm_h(frm_t('finance_monthly_ratios', 'RASIO KEUANGAN BULANAN')).'</th></tr>';
  foreach ($metrics as $key=>$metric) {
    $html .= '<tr><td>'.frm_h($metric['label']).'</td><td class="frm-formula">'.frm_h($metric['formula']).'</td>';
    foreach ($months as $month) $html .= '<td class="text-right">'.frm_value_text($data[$month][$key], $metric['format']).'</td>';
    $html .= '</tr>';
  }
  $html .= '<tr class="frm-total"><th>'.frm_h(frm_t('finance_revenue', 'Pendapatan')).'</th><td class="frm-formula">'.frm_h(frm_t('finance_formula_revenue', 'Kategori akun pendapatan')).'</td>';
  foreach ($months as $month) $html .= '<td class="text-right">'.frm_num($data[$month]['revenue']).'</td>';
  $html .= '</tr><tr class="frm-total"><th>'.frm_h(frm_t('finance_net_income_period', 'Laba (Rugi) Bersih Periode')).'</th><td class="frm-formula">'.frm_h(frm_t('finance_formula_net_income', 'Pendapatan - total beban')).'</td>';
  foreach ($months as $month) $html .= '<td class="text-right">'.frm_num($data[$month]['net_income']).'</td>';
  return $html.'</tr></tbody></table></div>';
}

function frm_summary($data, $months)
{
  $last = $months[count($months) - 1];
  return array(
    'current_ratio_text'=>frm_value_text($data[$last]['current_ratio'], 'ratio'),
    'debt_ratio_text'=>frm_value_text($data[$last]['debt_ratio'], 'percent'),
    'net_margin_text'=>frm_value_text($data[$last]['net_margin'], 'percent'),
    'roe_text'=>frm_value_text($data[$last]['roe'], 'percent')
  );
}

function frm_print_page($data, $months, $warnings)
{
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = frm_html($data, $months, $warnings);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>'.frm_h(frm_t('finance_report_ratio_monthly', 'Rasio Keuangan (Per Bulan)')).'</title><link rel="stylesheet" href="'.frm_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1280px;margin:18px auto}.frm-table{width:100%;border-collapse:collapse!important}.frm-table th,.frm-table td{font-size:11px;border:1px solid #d2d6de!important;vertical-align:middle!important}.frm-group th{background:#1d4ed8!important;color:#fff!important}.frm-formula{color:#4b5563;white-space:normal!important}.frm-total th,.frm-total td{background:#f3f4f6!important;font-weight:bold}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.table-responsive{overflow:visible!important}.frm-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.frm_h($company).'</h3><h4 style="margin:0 0 14px">'.frm_h(frm_t('finance_report_ratio_monthly', 'Rasio Keuangan (Per Bulan)')).'</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  $filters = frm_filters();
  $months = frm_months($filters['start_month'], $filters['end_month']);
  list($data, $warnings) = frm_data($db, $months);
  if ($act === 'filter') frm_json('success', 'OK', array('html'=>frm_html($data, $months, $warnings), 'warnings'=>$warnings, 'summary'=>frm_summary($data, $months)));
  if ($act === 'print') frm_print_page($data, $months, $warnings);
  if ($act === 'excel') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $metrics = frm_metric_defs();
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('Rasio Bulanan'));
    $sheet->setCellValue('A4', frm_t('finance_ratio', 'Rasio'));
    $sheet->setCellValue('B4', frm_t('finance_formula', 'Formula'));
    $col = 2;
    foreach ($months as $month) $sheet->setCellValueByColumnAndRow($col++, 4, frm_month_label($month));
    $lastCol = $col - 1;
    $row = 5;
    foreach ($metrics as $key=>$metric) {
      $sheet->setCellValue('A'.$row, $metric['label']);
      $sheet->setCellValue('B'.$row, $metric['formula']);
      $col = 2;
      foreach ($months as $month) {
        $value = $data[$month][$key];
        $sheet->setCellValueByColumnAndRow($col++, $row, $value === null ? null : ($metric['format'] === 'percent' ? $value * 100 : $value));
      }
      $row++;
    }
    foreach (array(frm_t('finance_revenue', 'Pendapatan')=>'revenue', frm_t('finance_net_income_period', 'Laba (Rugi) Bersih Periode')=>'net_income') as $label=>$key) {
      $sheet->setCellValue('A'.$row, $label);
      $col = 2;
      foreach ($months as $month) $sheet->setCellValueByColumnAndRow($col++, $row, $data[$month][$key]);
      $sheet->getStyle('A'.$row.':'.PHPExcel_Cell::stringFromColumnIndex($lastCol).$row)->getFont()->setBold(true);
      $row++;
    }
    $decimalCols = array();
    for ($i = 2; $i <= $lastCol; $i++) $decimalCols[] = PHPExcel_Cell::stringFromColumnIndex($i);
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet, 'title'=>erp_export_title(frm_t('finance_report_ratio_monthly', 'RASIO KEUANGAN PER BULAN')), 'header_row'=>4, 'first_data_row'=>5, 'last_data_row'=>max(5, $row - 1), 'column_count'=>$lastCol + 1, 'decimal_columns'=>$decimalCols, 'filters'=>array(frm_t('finance_period', 'Periode')=>$filters['start_month'].' s/d '.$filters['end_month'], 'Status'=>'POSTED')));
    $tmp = erpkb_excel_temp_file('rasio_keuangan_per_bulan_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = @filesize($tmp);
    $sig = @file_get_contents($tmp, false, null, 0, 2);
    if (!$size || $sig !== 'PK') { @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="rasio_keuangan_per_bulan_'.$filters['start_month'].'_sd_'.$filters['end_month'].'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp);
    @unlink($tmp);
    exit;
  }
  frm_json('error', frm_t('common_unknown_action', 'Action tidak dikenal.'));
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  frm_json('error', $e->getMessage());
}
?>
