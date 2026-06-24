<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function cfim_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
function cfim_json($status, $message = '', $extra = array()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(array('status'=>$status, 'message'=>$message), $extra)); exit; }
function cfim_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function cfim_num($value) { return number_format((float)$value, 2, '.', ','); }
function cfim_req($key, $default = '') { return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default; }
function cfim_lower($value) { return strtolower(trim((string)$value)); }
function cfim_month_ok($value) { return preg_match('/^\d{4}\-\d{2}$/', (string)$value) && strtotime($value.'-01') !== false; }
function cfim_prev_date($date) { $dt = new DateTime($date); $dt->modify('-1 day'); return $dt->format('Y-m-d'); }
function cfim_report_title_key() { return defined('CFIM_REPORT_TITLE_KEY') ? CFIM_REPORT_TITLE_KEY : 'finance_report_cash_flow_indirect_multi_period'; }
function cfim_report_title_fallback() { return defined('CFIM_REPORT_TITLE_FALLBACK') ? CFIM_REPORT_TITLE_FALLBACK : 'Arus Kas Multi Period (Tak Langsung)'; }
function cfim_report_export_fallback() { return defined('CFIM_REPORT_EXPORT_FALLBACK') ? CFIM_REPORT_EXPORT_FALLBACK : 'ARUS KAS MULTI PERIOD TAK LANGSUNG'; }
function cfim_report_file_prefix() { return defined('CFIM_REPORT_FILE_PREFIX') ? CFIM_REPORT_FILE_PREFIX : 'arus_kas_multi_period_tak_langsung_'; }

function cfim_months($start, $end)
{
  $months = array();
  $cursor = new DateTime($start.'-01');
  $limit = new DateTime($end.'-01');
  while ($cursor <= $limit) { $months[] = $cursor->format('Y-m'); $cursor->modify('+1 month'); }
  return $months;
}

function cfim_month_label($month) { return date('M Y', strtotime($month.'-01')); }

function cfim_filters()
{
  $start = cfim_req('start_month', date('Y-m'));
  $end = cfim_req('end_month', date('Y-m'));
  if (!cfim_month_ok($start) || !cfim_month_ok($end)) throw new Exception(cfim_t('finance_invalid_month', 'Format bulan tidak valid.'));
  if (strtotime($start.'-01') > strtotime($end.'-01')) throw new Exception(cfim_t('finance_start_month_after_end', 'Start month tidak boleh lebih besar dari end month.'));
  $months = cfim_months($start, $end);
  if (count($months) > 12) throw new Exception(cfim_t('finance_month_range_too_large', 'Rentang bulan maksimal 12 bulan.'));
  return array('start_month'=>$start, 'end_month'=>$end, 'months'=>$months);
}

function cfim_table_exists($db, $table)
{
  return (bool)$db->fetch("SHOW TABLES LIKE ?", array($table));
}

function cfim_blank($months)
{
  $blank = array();
  foreach ($months as $month) $blank[$month] = 0;
  return $blank;
}

function cfim_empty_sections($months)
{
  $blank = cfim_blank($months);
  return array(
    'operating'=>array('title'=>cfim_t('finance_operating_cash_flow', 'ARUS KAS DARI AKTIVITAS OPERASI'), 'rows'=>array(), 'total'=>$blank),
    'investing'=>array('title'=>cfim_t('finance_investing_cash_flow', 'ARUS KAS DARI AKTIVITAS INVESTASI'), 'rows'=>array(), 'total'=>$blank),
    'financing'=>array('title'=>cfim_t('finance_financing_cash_flow', 'ARUS KAS DARI AKTIVITAS PENDANAAN'), 'rows'=>array(), 'total'=>$blank)
  );
}

function cfim_add_row(&$section, $months, $month, $account, $label, $amount, $type, $source)
{
  $amount = (float)$amount;
  if (abs($amount) < 0.005) return;
  $key = $type.'|'.$account.'|'.$label.'|'.$source;
  if (!isset($section['rows'][$key])) $section['rows'][$key] = array('account'=>$account, 'label'=>$label, 'type'=>$type, 'source'=>$source, 'values'=>cfim_blank($months));
  $section['rows'][$key]['values'][$month] += $amount;
  $section['total'][$month] += $amount;
}

function cfim_mapping_warnings($db, $hasMapping)
{
  $warnings = array();
  if (!$hasMapping) {
    $warnings[] = cfim_t('finance_cash_flow_mapping_missing', 'Tabel cash_flow_mapping belum tersedia.');
    $warnings[] = cfim_t('finance_cash_mapping_detail_missing_warning', 'Mapping non-cash/operasi/investasi/pendanaan belum lengkap; beberapa akun memakai fallback kategori COA.');
    return $warnings;
  }
  $row = $db->fetch("SELECT COUNT(*) total,SUM(cash_flow_group='cash_equivalent') cash_count,SUM(cash_flow_group='non_cash') noncash_count,SUM(cash_flow_group IN ('operating','investing','financing')) activity_count FROM cash_flow_mapping WHERE is_active='Y'");
  if (!$row || (int)$row->total < 1) $warnings[] = cfim_t('finance_cash_flow_mapping_empty', 'cash_flow_mapping belum diisi; klasifikasi memakai fallback kategori COA resmi.');
  if ($row && (int)$row->cash_count < 1) $warnings[] = cfim_t('finance_cash_mapping_missing_warning', 'Mapping kas/bank belum ada; kas/bank ditentukan dari kategori COA Kas & Bank.');
  if ($row && ((int)$row->noncash_count < 1 || (int)$row->activity_count < 1)) $warnings[] = cfim_t('finance_cash_mapping_detail_missing_warning', 'Mapping non-cash/operasi/investasi/pendanaan belum lengkap; beberapa akun memakai fallback kategori COA.');
  return $warnings;
}

function cfim_opening_warning($db, $year)
{
  $row = $db->fetch("SELECT COUNT(*) cnt,COALESCE(SUM(debet),0) debet,COALESCE(SUM(kredit),0) kredit FROM saldo_awal WHERE periode=?", array($year));
  if ($row === false) throw new Exception('Query saldo_awal gagal: '.$db->getErrorMessage());
  if (!$row || (int)$row->cnt < 1) return cfim_t('finance_opening_balance_warning', 'Saldo awal periode belum diisi; saldo awal akun dianggap 0.').' '.$year;
  if (abs((float)$row->debet - (float)$row->kredit) > 0.01) return cfim_t('finance_opening_balance_unbalanced_warning', 'Saldo awal periode tidak balance.').' '.$year;
  return '';
}

function cfim_net_income($db, $start, $end)
{
  $row = $db->fetch(
    "SELECT COALESCE(SUM(CASE
       WHEN k.kategori_akun='pendapatan' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0)
       WHEN k.kategori_akun='beban' THEN COALESCE(d.kredit,0)-COALESCE(d.debet,0)
       ELSE 0 END),0) amount,
       COUNT(*) line_count
     FROM jurnal_detail d
     INNER JOIN jurnal_header h ON h.id=d.id_header
     INNER JOIN rekening r ON r.no_rek=d.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     WHERE h.tgl_jurnal BETWEEN ? AND ?
       AND h.posting_status='POSTED'
       AND k.kategori_akun IN ('pendapatan','beban')",
    array($start, $end)
  );
  if ($row === false) throw new Exception('Query laba bersih gagal: '.$db->getErrorMessage());
  return array('amount'=>(float)$row->amount, 'line_count'=>(int)$row->line_count);
}

function cfim_balance_rows($db, $asOfDate, $hasMapping, $openingOnly = false)
{
  $year = (int)date('Y', strtotime($asOfDate));
  $yearStart = $year.'-01-01';
  $params = array($year);
  if ($openingOnly) {
    $journalSql = "SELECT d.no_rek,0 debet,0 kredit FROM jurnal_detail d WHERE 1=0 GROUP BY d.no_rek";
  } else {
    $journalSql = "SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' GROUP BY d.no_rek";
    $params[] = $yearStart;
    $params[] = $asOfDate;
  }
  $mappingSelect = $hasMapping ? "COALESCE(m.cash_flow_group,'') cash_flow_group,COALESCE(m.cash_flow_type,'') cash_flow_type" : "'' cash_flow_group,'' cash_flow_type";
  $mappingJoin = $hasMapping ? "LEFT JOIN cash_flow_mapping m ON m.no_rek=r.no_rek AND m.is_active='Y'" : "";
  $rows = $db->query(
    "SELECT r.no_rek,r.nama_rek,k.kategori,k.kategori_akun,k.saldo_normal,$mappingSelect,
            CASE WHEN k.saldo_normal='kredit'
              THEN (COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))-(COALESCE(sa.debet,0)+COALESCE(j.debet,0))
              ELSE (COALESCE(sa.debet,0)+COALESCE(j.debet,0))-(COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))
            END saldo
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN (SELECT no_rek,SUM(COALESCE(debet,0)) debet,SUM(COALESCE(kredit,0)) kredit FROM saldo_awal WHERE periode=? GROUP BY no_rek) sa ON sa.no_rek=r.no_rek
     LEFT JOIN ($journalSql) j ON j.no_rek=r.no_rek
     $mappingJoin
     WHERE k.kategori_akun IN ('aset','kewajiban','modal')
     ORDER BY k.kategori_akun,k.kategori,LENGTH(r.no_rek),r.no_rek",
    $params
  );
  if ($rows === false) throw new Exception('Query saldo akun gagal: '.$db->getErrorMessage());
  $map = array();
  foreach ($rows as $row) $map[$row->no_rek] = $row;
  return $map;
}

function cfim_begin_rows($db, $startDate, $hasMapping)
{
  if (substr($startDate, 5) === '01-01') return cfim_balance_rows($db, $startDate, $hasMapping, true);
  return cfim_balance_rows($db, cfim_prev_date($startDate), $hasMapping, false);
}

function cfim_is_cash($row)
{
  $group = cfim_lower($row->cash_flow_group);
  $category = cfim_lower($row->kategori);
  return $group === 'cash_equivalent' || strpos($category, 'kas') !== false || strpos($category, 'bank') !== false;
}

function cfim_balance_class($row)
{
  $group = cfim_lower($row->cash_flow_group);
  if (in_array($group, array('operating','investing','financing','non_cash'))) return $group;
  $category = cfim_lower($row->kategori);
  if (strpos($category, 'akumulasi') !== false || strpos($category, 'penyusutan') !== false || strpos($category, 'amortisasi') !== false) return 'non_cash';
  if ($row->kategori_akun === 'aset' && (strpos($category, 'aset tetap') !== false || strpos($category, 'aset lainnya') !== false)) return 'investing';
  if ($row->kategori_akun === 'kewajiban' && strpos($category, 'jangka panjang') !== false) return 'financing';
  if ($row->kategori_akun === 'modal') return 'financing';
  return 'operating';
}

function cfim_delta_cash_effect($row, $delta, $group)
{
  if ($group === 'operating' || $group === 'investing' || $group === 'financing') return $row->kategori_akun === 'aset' ? -$delta : $delta;
  if ($group === 'non_cash') {
    if ($row->kategori_akun === 'aset') return $row->saldo_normal === 'kredit' ? $delta : -$delta;
    return $delta;
  }
  return 0;
}

function cfim_cash_total($rows)
{
  $total = 0;
  foreach ($rows as $row) if (cfim_is_cash($row)) $total += (float)$row->saldo;
  return $total;
}

function cfim_non_cash_profit_loss($db, $start, $end, $hasMapping)
{
  if (!$hasMapping) return array();
  $rows = $db->query(
    "SELECT r.no_rek,r.nama_rek,k.kategori_akun,
      SUM(CASE WHEN k.kategori_akun='pendapatan' THEN -(COALESCE(d.kredit,0)-COALESCE(d.debet,0)) ELSE COALESCE(d.debet,0)-COALESCE(d.kredit,0) END) amount
     FROM jurnal_detail d
     INNER JOIN jurnal_header h ON h.id=d.id_header
     INNER JOIN rekening r ON r.no_rek=d.no_rek
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     INNER JOIN cash_flow_mapping m ON m.no_rek=r.no_rek AND m.is_active='Y' AND m.cash_flow_group='non_cash'
     WHERE h.tgl_jurnal BETWEEN ? AND ?
       AND h.posting_status='POSTED'
       AND k.kategori_akun IN ('pendapatan','beban')
     GROUP BY r.no_rek,r.nama_rek,k.kategori_akun
     ORDER BY r.no_rek",
    array($start, $end)
  );
  if ($rows === false) throw new Exception('Query adjustment non-cash gagal: '.$db->getErrorMessage());
  return $rows;
}

function cfim_data($db, $filters)
{
  $months = $filters['months'];
  $hasMapping = cfim_table_exists($db, 'cash_flow_mapping');
  $sections = cfim_empty_sections($months);
  $warnings = cfim_mapping_warnings($db, $hasMapping);
  $summary = array('opening_cash'=>cfim_blank($months), 'ending_cash'=>cfim_blank($months), 'net_cash_flow'=>cfim_blank($months), 'cash_delta'=>cfim_blank($months), 'reconciliation_diff'=>cfim_blank($months));
  $yearsChecked = array();
  foreach ($months as $month) {
    $year = (int)substr($month, 0, 4);
    if (!isset($yearsChecked[$year])) {
      $warning = cfim_opening_warning($db, $year);
      if ($warning !== '') $warnings[] = $warning;
      $yearsChecked[$year] = true;
    }
    $start = $month.'-01';
    $end = date('Y-m-t', strtotime($start));
    $pl = cfim_net_income($db, $start, $end);
    if ($pl['line_count'] < 1) $warnings[] = cfim_t('finance_no_pl_posted_warning', 'Tidak ada jurnal P&L POSTED pada periode ini.').' '.$month;
    cfim_add_row($sections['operating'], $months, $month, '', cfim_t('finance_net_income_period', 'Laba (Rugi) Bersih Periode'), $pl['amount'], 'net_income', 'posted_journal');
    foreach (cfim_non_cash_profit_loss($db, $start, $end, $hasMapping) as $row) {
      cfim_add_row($sections['operating'], $months, $month, $row->no_rek, cfim_t('finance_non_cash_adjustment', 'Adjustment non-cash').' - '.$row->nama_rek, (float)$row->amount, 'non_cash', 'mapping');
    }
    $beginRows = cfim_begin_rows($db, $start, $hasMapping);
    $endRows = cfim_balance_rows($db, $end, $hasMapping, false);
    $summary['opening_cash'][$month] = cfim_cash_total($beginRows);
    $summary['ending_cash'][$month] = cfim_cash_total($endRows);
    $accounts = array_unique(array_merge(array_keys($beginRows), array_keys($endRows)));
    sort($accounts);
    foreach ($accounts as $account) {
      $row = isset($endRows[$account]) ? $endRows[$account] : $beginRows[$account];
      if (cfim_is_cash($row)) continue;
      $begin = isset($beginRows[$account]) ? (float)$beginRows[$account]->saldo : 0;
      $ending = isset($endRows[$account]) ? (float)$endRows[$account]->saldo : 0;
      $delta = $ending - $begin;
      if (abs($delta) < 0.005) continue;
      $group = cfim_balance_class($row);
      $amount = cfim_delta_cash_effect($row, $delta, $group);
      $source = $row->cash_flow_group !== '' ? 'mapping' : 'coa';
      if ($group === 'non_cash') cfim_add_row($sections['operating'], $months, $month, $row->no_rek, cfim_t('finance_non_cash_adjustment', 'Adjustment non-cash').' - '.$row->nama_rek, $amount, 'non_cash', $source);
      elseif ($group === 'operating') cfim_add_row($sections['operating'], $months, $month, $row->no_rek, cfim_t('finance_working_capital_change', 'Perubahan aset/kewajiban operasional').' - '.$row->nama_rek, $amount, 'working_capital', $source);
      elseif ($group === 'investing') cfim_add_row($sections['investing'], $months, $month, $row->no_rek, cfim_t('finance_balance_change', 'Perubahan saldo akun').' - '.$row->nama_rek, $amount, 'balance_change', $source);
      elseif ($group === 'financing') cfim_add_row($sections['financing'], $months, $month, $row->no_rek, cfim_t('finance_balance_change', 'Perubahan saldo akun').' - '.$row->nama_rek, $amount, 'balance_change', $source);
    }
  }
  foreach ($months as $month) {
    foreach ($sections as $section) $summary['net_cash_flow'][$month] += $section['total'][$month];
    $summary['cash_delta'][$month] = $summary['ending_cash'][$month] - $summary['opening_cash'][$month];
    $summary['reconciliation_diff'][$month] = $summary['net_cash_flow'][$month] - $summary['cash_delta'][$month];
    if (abs($summary['reconciliation_diff'][$month]) > 0.01) $warnings[] = cfim_t('finance_cash_reconciliation_warning', 'Net cash flow belum sama dengan perubahan saldo kas; cek jurnal transfer kas, mapping kas/bank, atau jurnal multi-akun.').' '.$month.' ('.cfim_num($summary['reconciliation_diff'][$month]).')';
  }
  foreach ($sections as &$section) {
    $section['rows'] = array_values($section['rows']);
    usort($section['rows'], function($a, $b) { return strcmp($a['type'].$a['account'].$a['label'], $b['type'].$b['account'].$b['label']); });
  }
  unset($section);
  return array($sections, array_values(array_unique($warnings)), $summary);
}

function cfim_row_total($values)
{
  return array_sum($values);
}

function cfim_html($sections, $warnings, $summary, $filters)
{
  $months = $filters['months'];
  $html = '';
  if (count($warnings)) $html .= '<div class="alert alert-warning"><strong>'.cfim_h(cfim_t('common_warning', 'Peringatan')).':</strong> '.cfim_h(implode(' ', $warnings)).'</div>';
  $html .= '<div class="alert alert-info"><i class="fa fa-info-circle"></i> '.cfim_h(cfim_t('finance_source_summary', 'Sumber: jurnal_header/detail POSTED, rekening, coa_kategori, saldo_awal, dan cash_flow_mapping.')).' '.cfim_h($filters['start_month'].' s/d '.$filters['end_month']).'</div>';
  $colspan = count($months) + 3;
  $html .= '<div class="table-responsive"><table class="table table-bordered table-condensed cfim-table"><thead><tr class="bg-primary"><th>COA</th><th>'.cfim_h(cfim_t('common_description', 'Uraian')).'</th>';
  foreach ($months as $month) $html .= '<th class="text-right">'.cfim_h(cfim_month_label($month)).'</th>';
  $html .= '<th class="text-right">'.cfim_h(cfim_t('common_total', 'Total')).'</th></tr></thead><tbody>';
  foreach ($sections as $section) {
    $html .= '<tr class="cfim-section"><th colspan="'.$colspan.'">'.cfim_h($section['title']).'</th></tr>';
    if (!count($section['rows'])) $html .= '<tr><td></td><td class="text-muted cfim-account"><em>'.cfim_h(cfim_t('finance_no_cash_movement', 'Tidak ada mutasi')).'</em></td>'.str_repeat('<td class="text-right">0.00</td>', count($months) + 1).'</tr>';
    $lastType = '';
    foreach ($section['rows'] as $row) {
      if ($row['type'] !== $lastType) {
        $lastType = $row['type'];
        $label = $lastType === 'net_income' ? cfim_t('finance_net_income_period', 'Laba (Rugi) Bersih Periode') : ($lastType === 'non_cash' ? cfim_t('finance_non_cash_adjustment', 'Adjustment non-cash') : ($lastType === 'working_capital' ? cfim_t('finance_working_capital_change', 'Perubahan aset/kewajiban operasional') : cfim_t('finance_balance_change', 'Perubahan saldo akun')));
        $html .= '<tr class="cfim-subsection"><td></td><td colspan="'.(count($months)+2).'">'.cfim_h($label).'</td></tr>';
      }
      $html .= '<tr><td>'.cfim_h($row['account']).'</td><td class="cfim-account">'.cfim_h($row['label']).'</td>';
      foreach ($months as $month) $html .= '<td class="text-right">'.cfim_num($row['values'][$month]).'</td>';
      $html .= '<td class="text-right">'.cfim_num(cfim_row_total($row['values'])).'</td></tr>';
    }
    $html .= '<tr class="cfim-total"><th></th><th>Subtotal</th>';
    foreach ($months as $month) $html .= '<td class="text-right">'.cfim_num($section['total'][$month]).'</td>';
    $html .= '<td class="text-right">'.cfim_num(cfim_row_total($section['total'])).'</td></tr>';
  }
  foreach (array(
    cfim_t('finance_net_cash_flow', 'Net Cash Flow')=>$summary['net_cash_flow'],
    cfim_t('finance_opening_cash', 'Saldo Kas Awal')=>$summary['opening_cash'],
    cfim_t('finance_ending_cash', 'Saldo Kas Akhir')=>$summary['ending_cash'],
    cfim_t('finance_cash_reconciliation_diff', 'Selisih Rekonsiliasi Kas')=>$summary['reconciliation_diff']
  ) as $label=>$values) {
    $class = $label === cfim_t('finance_net_cash_flow', 'Net Cash Flow') ? 'cfim-grand' : 'cfim-check';
    $html .= '<tr class="'.$class.'"><th></th><th>'.cfim_h($label).'</th>';
    foreach ($months as $month) $html .= '<td class="text-right">'.cfim_num($values[$month]).'</td>';
    $html .= '<td class="text-right">'.cfim_num(cfim_row_total($values)).'</td></tr>';
  }
  return $html.'</tbody></table></div>';
}

function cfim_print_page($sections, $warnings, $summary, $filters)
{
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = cfim_html($sections, $warnings, $summary, $filters);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  $title = cfim_t(cfim_report_title_key(), cfim_report_title_fallback());
  echo '<!doctype html><html><head><meta charset="utf-8"><title>'.cfim_h($title).'</title><link rel="stylesheet" href="'.cfim_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:10px}.print-wrap{max-width:1180px;margin:18px auto}.cfim-table{width:100%;border-collapse:collapse!important}.cfim-table th,.cfim-table td{font-size:10px;border:1px solid #d2d6de!important;vertical-align:middle!important}.cfim-section th{background:#1d4ed8!important;color:#fff!important}.cfim-subsection td{background:#eef2ff!important;font-weight:bold}.cfim-total th,.cfim-total td{background:#f3f4f6!important;font-weight:bold}.cfim-grand th,.cfim-grand td{background:#0f766e!important;color:#fff!important;font-weight:bold}.cfim-check th,.cfim-check td{background:#fff7ed!important;font-weight:bold}.cfim-account{padding-left:18px!important;min-width:220px}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:7mm}.table-responsive{overflow:visible!important}.cfim-table tr{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.cfim_h($company).'</h3><h4 style="margin:0 0 14px">'.cfim_h($title).'</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

function cfim_excel($sections, $warnings, $summary, $filters)
{
  global $initialOutputBufferLevel;
  ini_set('display_errors','0');
  error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
  require '../../inc/lib/PHPExcel.php';
  require_once '../../inc/excel_style_helper.php';
  PHPExcel_Shared_File::setUseUploadTempDirectory(true);
  $months = $filters['months'];
  $excel = new PHPExcel();
  $sheet = $excel->setActiveSheetIndex(0);
  $sheet->setTitle(erp_export_sheet_title('Arus Kas Indirect'));
  $sheet->setCellValue('A4', 'COA');
  $sheet->setCellValue('B4', cfim_t('common_description', 'Uraian'));
  $col = 2;
  foreach ($months as $month) $sheet->setCellValueByColumnAndRow($col++, 4, cfim_month_label($month));
  $sheet->setCellValueByColumnAndRow($col, 4, cfim_t('common_total', 'Total'));
  $r = 5;
  foreach ($sections as $section) {
    $sheet->setCellValue('A'.$r, $section['title']);
    $sheet->mergeCellsByColumnAndRow(0, $r, count($months) + 2, $r);
    $r++;
    foreach ($section['rows'] as $row) {
      $sheet->setCellValueExplicit('A'.$r, $row['account'], PHPExcel_Cell_DataType::TYPE_STRING);
      $sheet->setCellValue('B'.$r, $row['label']);
      $col = 2;
      foreach ($months as $month) $sheet->setCellValueByColumnAndRow($col++, $r, $row['values'][$month]);
      $sheet->setCellValueByColumnAndRow($col, $r, cfim_row_total($row['values']));
      $r++;
    }
    $sheet->setCellValue('B'.$r, 'Subtotal');
    $col = 2;
    foreach ($months as $month) $sheet->setCellValueByColumnAndRow($col++, $r, $section['total'][$month]);
    $sheet->setCellValueByColumnAndRow($col, $r, cfim_row_total($section['total']));
    $r++;
  }
  foreach (array(cfim_t('finance_net_cash_flow', 'Net Cash Flow')=>$summary['net_cash_flow'], cfim_t('finance_opening_cash', 'Saldo Kas Awal')=>$summary['opening_cash'], cfim_t('finance_ending_cash', 'Saldo Kas Akhir')=>$summary['ending_cash'], cfim_t('finance_cash_reconciliation_diff', 'Selisih Rekonsiliasi Kas')=>$summary['reconciliation_diff']) as $label=>$values) {
    $sheet->setCellValue('B'.$r, $label);
    $col = 2;
    foreach ($months as $month) $sheet->setCellValueByColumnAndRow($col++, $r, $values[$month]);
    $sheet->setCellValueByColumnAndRow($col, $r, cfim_row_total($values));
    $r++;
  }
  erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet, 'title'=>erp_export_title(cfim_t(cfim_report_title_key(), cfim_report_export_fallback())), 'header_row'=>4, 'first_data_row'=>5, 'last_data_row'=>max(5, $r - 1), 'column_count'=>count($months) + 3, 'money_columns'=>range('C', PHPExcel_Cell::stringFromColumnIndex(count($months) + 2)), 'filters'=>array(cfim_t('finance_period', 'Periode')=>$filters['start_month'].' s/d '.$filters['end_month'], 'Status'=>'POSTED')));
  $tmp = erpkb_excel_temp_file(cfim_report_file_prefix());
  PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
  $size = @filesize($tmp);
  $sig = @file_get_contents($tmp, false, null, 0, 2);
  if (!$size || $sig !== 'PK') { @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="'.cfim_report_file_prefix().$filters['start_month'].'_sd_'.$filters['end_month'].'.xlsx"');
  header('Content-Length: '.$size);
  header('Cache-Control: max-age=0');
  header('Pragma: public');
  readfile($tmp);
  @unlink($tmp);
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  $filters = cfim_filters();
  list($sections, $warnings, $summary) = cfim_data($db, $filters);
  if ($act === 'filter') cfim_json('success', 'OK', array('html'=>cfim_html($sections, $warnings, $summary, $filters), 'warnings'=>$warnings));
  if ($act === 'print') cfim_print_page($sections, $warnings, $summary, $filters);
  if ($act === 'excel') cfim_excel($sections, $warnings, $summary, $filters);
  cfim_json('error', cfim_t('common_unknown_action', 'Action tidak dikenal.'));
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  cfim_json('error', $e->getMessage());
}
?>
