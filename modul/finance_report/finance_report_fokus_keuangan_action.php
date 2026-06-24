<?php
$initialOutputBufferLevel = ob_get_level();
ob_start();
session_start();
include "../../inc/config.php";
session_check_json();

function ffocus_t($key, $fallback = '') { return function_exists('lang_text') ? lang_text($key, $fallback) : $fallback; }
function ffocus_json($status, $message = '', $extra = array()) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(array('status'=>$status, 'message'=>$message), $extra)); exit; }
function ffocus_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function ffocus_num($value) { return number_format((float)$value, 2, '.', ','); }
function ffocus_req($key, $default = '') { return isset($_REQUEST[$key]) ? trim((string)$_REQUEST[$key]) : $default; }
function ffocus_lower($value) { return strtolower(trim((string)$value)); }
function ffocus_valid_date($value) { return preg_match('/^\d{4}\-\d{2}\-\d{2}$/', (string)$value) && strtotime($value) !== false; }

function ffocus_filters()
{
  $start = ffocus_req('start_date', date('Y-m-01'));
  $end = ffocus_req('end_date', date('Y-m-d'));
  if (!ffocus_valid_date($start) || !ffocus_valid_date($end)) throw new Exception(ffocus_t('finance_invalid_date', 'Format tanggal tidak valid.'));
  if (strtotime($start) > strtotime($end)) throw new Exception(ffocus_t('finance_start_after_end', 'Start date tidak boleh lebih besar dari end date.'));
  return array('start_date'=>$start, 'end_date'=>$end);
}

function ffocus_prev_period($filters)
{
  $start = new DateTime($filters['start_date']);
  $end = new DateTime($filters['end_date']);
  $days = $start->diff($end)->days;
  $prevEnd = clone $start;
  $prevEnd->modify('-1 day');
  $prevStart = clone $prevEnd;
  $prevStart->modify('-'.$days.' day');
  return array('start_date'=>$prevStart->format('Y-m-d'), 'end_date'=>$prevEnd->format('Y-m-d'));
}

function ffocus_is_cogs($category)
{
  $category = ffocus_lower($category);
  return strpos($category, 'hpp') !== false || strpos($category, 'pokok') !== false || strpos($category, 'persediaan') !== false || strpos($category, 'cost') !== false;
}

function ffocus_is_operating_expense($category)
{
  $category = ffocus_lower($category);
  return !ffocus_is_cogs($category) && strpos($category, 'lain') === false;
}

function ffocus_is_cash($category)
{
  $category = ffocus_lower($category);
  return strpos($category, 'kas') !== false || strpos($category, 'bank') !== false;
}

function ffocus_pl($db, $filters)
{
  $rows = $db->query(
    "SELECT k.id kategori_id,k.kategori,k.kategori_akun,
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
     GROUP BY k.id,k.kategori,k.kategori_akun
     ORDER BY k.id",
    array($filters['start_date'], $filters['end_date'])
  );
  if ($rows === false) throw new Exception('Query fokus P&L gagal: '.$db->getErrorMessage());
  $totals = array('revenue'=>0, 'cogs'=>0, 'gross_profit'=>0, 'operating_expense'=>0, 'other_income'=>0, 'other_expense'=>0, 'net_income'=>0, 'line_count'=>0, 'rows'=>array());
  foreach ($rows as $row) {
    $amount = (float)$row->amount;
    if (abs($amount) >= 0.005) $totals['line_count']++;
    if ($row->kategori_akun === 'pendapatan') {
      if (strpos(ffocus_lower($row->kategori), 'lain') !== false) $totals['other_income'] += $amount; else $totals['revenue'] += $amount;
    } elseif ($row->kategori_akun === 'beban') {
      if (ffocus_is_cogs($row->kategori)) $totals['cogs'] += $amount;
      elseif (ffocus_is_operating_expense($row->kategori)) $totals['operating_expense'] += $amount;
      else $totals['other_expense'] += $amount;
    }
    $totals['rows'][] = array('label'=>$row->kategori, 'type'=>$row->kategori_akun, 'amount'=>$amount);
  }
  $totals['gross_profit'] = $totals['revenue'] - $totals['cogs'];
  $totals['net_income'] = $totals['revenue'] + $totals['other_income'] - $totals['cogs'] - $totals['operating_expense'] - $totals['other_expense'];
  return $totals;
}

function ffocus_balance($db, $asOfDate)
{
  $year = (int)date('Y', strtotime($asOfDate));
  $yearStart = $year.'-01-01';
  $rows = $db->query(
    "SELECT k.id kategori_id,k.kategori,k.kategori_akun,k.saldo_normal,
      CASE WHEN k.saldo_normal='kredit'
        THEN (COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))-(COALESCE(sa.debet,0)+COALESCE(j.debet,0))
        ELSE (COALESCE(sa.debet,0)+COALESCE(j.debet,0))-(COALESCE(sa.kredit,0)+COALESCE(j.kredit,0))
      END saldo
     FROM rekening r
     INNER JOIN coa_kategori k ON k.id=r.kat_coa
     LEFT JOIN (SELECT no_rek,SUM(COALESCE(debet,0)) debet,SUM(COALESCE(kredit,0)) kredit FROM saldo_awal WHERE periode=? GROUP BY no_rek) sa ON sa.no_rek=r.no_rek
     LEFT JOIN (SELECT d.no_rek,SUM(COALESCE(d.debet,0)) debet,SUM(COALESCE(d.kredit,0)) kredit FROM jurnal_detail d INNER JOIN jurnal_header h ON h.id=d.id_header WHERE h.tgl_jurnal BETWEEN ? AND ? AND h.posting_status='POSTED' GROUP BY d.no_rek) j ON j.no_rek=r.no_rek
     WHERE k.kategori_akun IN ('aset','kewajiban','modal')
     ORDER BY k.kategori_akun,k.id",
    array($year, $yearStart, $asOfDate)
  );
  if ($rows === false) throw new Exception('Query fokus neraca gagal: '.$db->getErrorMessage());
  $totals = array('assets'=>0, 'liabilities'=>0, 'equity'=>0, 'cash'=>0, 'line_count'=>0, 'rows'=>array());
  foreach ($rows as $row) {
    $amount = (float)$row->saldo;
    if (abs($amount) < 0.005) continue;
    $totals['line_count']++;
    if ($row->kategori_akun === 'aset') {
      $totals['assets'] += $amount;
      if (ffocus_is_cash($row->kategori)) $totals['cash'] += $amount;
    } elseif ($row->kategori_akun === 'kewajiban') {
      $totals['liabilities'] += $amount;
    } elseif ($row->kategori_akun === 'modal') {
      $totals['equity'] += $amount;
    }
    $key = $row->kategori_akun.'|'.$row->kategori;
    if (!isset($totals['rows'][$key])) $totals['rows'][$key] = array('label'=>$row->kategori, 'type'=>$row->kategori_akun, 'amount'=>0);
    $totals['rows'][$key]['amount'] += $amount;
  }
  $totals['rows'] = array_values($totals['rows']);
  return $totals;
}

function ffocus_opening_warning($db, $date)
{
  $year = (int)date('Y', strtotime($date));
  $row = $db->fetch("SELECT COUNT(*) cnt,COALESCE(SUM(debet),0) debet,COALESCE(SUM(kredit),0) kredit FROM saldo_awal WHERE periode=?", array($year));
  if (!$row || (int)$row->cnt < 1) return ffocus_t('finance_opening_balance_warning', 'Saldo awal periode belum diisi; saldo awal akun dianggap 0.').' '.$year;
  if (abs((float)$row->debet - (float)$row->kredit) > 0.01) return ffocus_t('finance_opening_balance_unbalanced_warning', 'Saldo awal periode tidak balance.').' '.$year;
  return '';
}

function ffocus_trend($current, $previous)
{
  $diff = (float)$current - (float)$previous;
  $pct = abs((float)$previous) < 0.005 ? null : ($diff / abs((float)$previous) * 100);
  return array('diff'=>$diff, 'pct'=>$pct);
}

function ffocus_trend_text($trend)
{
  $diff = $trend['diff'];
  $pct = $trend['pct'];
  $sign = $diff > 0 ? '+' : '';
  return $sign.ffocus_num($diff).($pct === null ? '' : ' ('.$sign.ffocus_num($pct).'%)');
}

function ffocus_data($db, $filters)
{
  $previous = ffocus_prev_period($filters);
  $pl = ffocus_pl($db, $filters);
  $prevPl = ffocus_pl($db, $previous);
  $balance = ffocus_balance($db, $filters['end_date']);
  $prevBalance = ffocus_balance($db, $previous['end_date']);
  $balance['equity'] += $pl['net_income'];
  $prevBalance['equity'] += $prevPl['net_income'];
  $warnings = array();
  $openingWarning = ffocus_opening_warning($db, $filters['end_date']);
  if ($openingWarning !== '') $warnings[] = $openingWarning;
  if ($pl['line_count'] < 1 && $balance['line_count'] < 1) $warnings[] = ffocus_t('finance_empty_period_warning', 'Tidak ada data POSTED pada periode ini.');
  $balanceDiff = $balance['assets'] - ($balance['liabilities'] + $balance['equity']);
  if (abs($balanceDiff) > 0.01) $warnings[] = ffocus_t('finance_balance_not_balanced_warning', 'Saldo neraca belum balance.').' '.ffocus_num($balanceDiff);
  return array($pl, $prevPl, $balance, $prevBalance, $previous, array_values(array_unique($warnings)));
}

function ffocus_card($label, $value, $trend, $class)
{
  return '<div class="col-md-3 col-sm-6"><div class="ffocus-card '.$class.'"><div class="label-text">'.ffocus_h($label).'</div><div class="value-text">'.ffocus_num($value).'</div><div class="trend-text">'.ffocus_h(ffocus_t('finance_vs_previous_period', 'vs periode sebelumnya')).': '.ffocus_h(ffocus_trend_text($trend)).'</div></div></div>';
}

function ffocus_html($pl, $prevPl, $balance, $prevBalance, $previous, $warnings, $filters)
{
  $html = '';
  if (count($warnings)) $html .= '<div class="alert alert-warning"><strong>'.ffocus_h(ffocus_t('common_warning', 'Peringatan')).':</strong> '.ffocus_h(implode(' ', $warnings)).'</div>';
  $html .= '<div class="alert alert-info"><i class="fa fa-info-circle"></i> '.ffocus_h(ffocus_t('finance_focus_source_summary', 'Sumber: jurnal_header/detail POSTED, saldo_awal, rekening, dan coa_kategori.')).' '.ffocus_h($filters['start_date'].' s/d '.$filters['end_date']).'</div>';
  $html .= '<div class="row">';
  $html .= ffocus_card(ffocus_t('finance_total_revenue', 'Total Pendapatan'), $pl['revenue'], ffocus_trend($pl['revenue'], $prevPl['revenue']), 'green');
  $html .= ffocus_card(ffocus_t('finance_gross_profit', 'Laba Kotor'), $pl['gross_profit'], ffocus_trend($pl['gross_profit'], $prevPl['gross_profit']), 'green');
  $html .= ffocus_card(ffocus_t('finance_net_income_period', 'Laba Bersih'), $pl['net_income'], ffocus_trend($pl['net_income'], $prevPl['net_income']), $pl['net_income'] < 0 ? 'red' : 'orange');
  $html .= ffocus_card(ffocus_t('finance_ending_cash', 'Kas Akhir'), $balance['cash'], ffocus_trend($balance['cash'], $prevBalance['cash']), 'gray');
  $html .= '</div><div class="row">';
  $html .= ffocus_card(ffocus_t('finance_total_assets', 'Total Aset'), $balance['assets'], ffocus_trend($balance['assets'], $prevBalance['assets']), 'green');
  $html .= ffocus_card(ffocus_t('finance_total_liabilities', 'Total Kewajiban'), $balance['liabilities'], ffocus_trend($balance['liabilities'], $prevBalance['liabilities']), 'red');
  $html .= ffocus_card(ffocus_t('finance_total_equity', 'Total Modal'), $balance['equity'], ffocus_trend($balance['equity'], $prevBalance['equity']), 'gray');
  $html .= ffocus_card(ffocus_t('finance_operating_expense', 'Beban Operasional'), $pl['operating_expense'], ffocus_trend($pl['operating_expense'], $prevPl['operating_expense']), 'orange');
  $html .= '</div>';
  $html .= '<div class="row"><div class="col-md-6"><table class="table table-bordered table-condensed ffocus-table"><tbody>';
  $html .= '<tr class="ffocus-section"><th colspan="2">'.ffocus_h(ffocus_t('finance_profit_loss_summary', 'Ringkasan Laba Rugi')).'</th></tr>';
  $rows = array(
    ffocus_t('finance_total_revenue', 'Total Pendapatan')=>$pl['revenue'],
    ffocus_t('finance_cogs', 'HPP')=>$pl['cogs'],
    ffocus_t('finance_gross_profit', 'Laba Kotor')=>$pl['gross_profit'],
    ffocus_t('finance_operating_expense', 'Beban Operasional')=>$pl['operating_expense'],
    ffocus_t('finance_other_income', 'Pendapatan Lainnya')=>$pl['other_income'],
    ffocus_t('finance_other_expense', 'Beban Lainnya')=>$pl['other_expense']
  );
  foreach ($rows as $label=>$amount) $html .= '<tr><td>'.ffocus_h($label).'</td><td class="text-right">'.ffocus_num($amount).'</td></tr>';
  $html .= '<tr class="ffocus-grand"><th>'.ffocus_h(ffocus_t('finance_net_income_period', 'Laba Bersih')).'</th><td class="text-right">'.ffocus_num($pl['net_income']).'</td></tr></tbody></table></div>';
  $html .= '<div class="col-md-6"><table class="table table-bordered table-condensed ffocus-table"><tbody>';
  $html .= '<tr class="ffocus-section"><th colspan="2">'.ffocus_h(ffocus_t('finance_balance_summary', 'Ringkasan Neraca')).'</th></tr>';
  foreach (array(ffocus_t('finance_total_assets', 'Total Aset')=>$balance['assets'], ffocus_t('finance_total_liabilities', 'Total Kewajiban')=>$balance['liabilities'], ffocus_t('finance_total_equity', 'Total Modal')=>$balance['equity'], ffocus_t('finance_ending_cash', 'Kas Akhir')=>$balance['cash']) as $label=>$amount) {
    $html .= '<tr><td>'.ffocus_h($label).'</td><td class="text-right">'.ffocus_num($amount).'</td></tr>';
  }
  $html .= '<tr class="ffocus-total"><th>'.ffocus_h(ffocus_t('finance_balance_difference', 'Selisih Balance')).'</th><td class="text-right">'.ffocus_num($balance['assets'] - ($balance['liabilities'] + $balance['equity'])).'</td></tr></tbody></table></div></div>';
  return $html;
}

function ffocus_print_page($pl, $prevPl, $balance, $prevBalance, $previous, $warnings, $filters)
{
  $info = function_exists('info_pt') ? info_pt() : null;
  $company = $info && isset($info->nama_pt) ? $info->nama_pt : shortTittle;
  $body = ffocus_html($pl, $prevPl, $balance, $prevBalance, $previous, $warnings, $filters);
  $assetBase = rtrim(base_url(), '/').'/assets/';
  while (ob_get_level() > $GLOBALS['initialOutputBufferLevel']) ob_end_clean();
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><html><head><meta charset="utf-8"><title>'.ffocus_h(ffocus_t('finance_report_focus', 'Fokus Keuangan')).'</title><link rel="stylesheet" href="'.ffocus_h($assetBase).'bootstrap/css/bootstrap.min.css"><style>html,body{background:#fff!important;color:#111;font-size:11px}.print-wrap{max-width:1180px;margin:18px auto}.ffocus-card{border:1px solid #d2d6de;border-left:4px solid #1d4ed8;padding:8px;margin-bottom:8px;min-height:78px}.ffocus-card .label-text{font-size:11px;color:#555;text-transform:uppercase}.ffocus-card .value-text{font-size:16px;font-weight:bold}.ffocus-card .trend-text{font-size:10px;color:#555}.ffocus-table th,.ffocus-table td{font-size:11px;border:1px solid #d2d6de!important}.ffocus-section th{background:#1d4ed8!important;color:#fff!important}.ffocus-total th,.ffocus-total td{background:#f3f4f6!important;font-weight:bold}.ffocus-grand th,.ffocus-grand td{background:#0f766e!important;color:#fff!important;font-weight:bold}.no-print{margin-bottom:12px}@media print{*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}.no-print{display:none!important}.print-wrap{margin:0;max-width:none;width:100%}@page{size:A4 landscape;margin:8mm}.row{page-break-inside:avoid}}</style></head><body><div class="print-wrap"><div class="no-print"><button onclick="window.print()" class="btn btn-primary btn-sm">Print / PDF</button></div><h3 style="margin:0 0 2px">'.ffocus_h($company).'</h3><h4 style="margin:0 0 14px">'.ffocus_h(ffocus_t('finance_report_focus', 'Fokus Keuangan')).'</h4>'.$body.'</div><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script></body></html>';
  exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
try {
  $filters = ffocus_filters();
  list($pl, $prevPl, $balance, $prevBalance, $previous, $warnings) = ffocus_data($db, $filters);
  if ($act === 'filter') ffocus_json('success', 'OK', array('html'=>ffocus_html($pl, $prevPl, $balance, $prevBalance, $previous, $warnings, $filters), 'warnings'=>$warnings));
  if ($act === 'print') ffocus_print_page($pl, $prevPl, $balance, $prevBalance, $previous, $warnings, $filters);
  if ($act === 'excel') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    require '../../inc/lib/PHPExcel.php';
    require_once '../../inc/excel_style_helper.php';
    PHPExcel_Shared_File::setUseUploadTempDirectory(true);
    $excel = new PHPExcel();
    $sheet = $excel->setActiveSheetIndex(0);
    $sheet->setTitle(erp_export_sheet_title('Fokus Keuangan'));
    $sheet->setCellValue('A4', ffocus_t('common_description', 'Uraian'));
    $sheet->setCellValue('B4', ffocus_t('common_amount', 'Nilai'));
    $rows = array(
      ffocus_t('finance_total_revenue', 'Total Pendapatan')=>$pl['revenue'],
      ffocus_t('finance_cogs', 'HPP')=>$pl['cogs'],
      ffocus_t('finance_gross_profit', 'Laba Kotor')=>$pl['gross_profit'],
      ffocus_t('finance_operating_expense', 'Beban Operasional')=>$pl['operating_expense'],
      ffocus_t('finance_net_income_period', 'Laba Bersih')=>$pl['net_income'],
      ffocus_t('finance_total_assets', 'Total Aset')=>$balance['assets'],
      ffocus_t('finance_total_liabilities', 'Total Kewajiban')=>$balance['liabilities'],
      ffocus_t('finance_total_equity', 'Total Modal')=>$balance['equity'],
      ffocus_t('finance_ending_cash', 'Kas Akhir')=>$balance['cash'],
      ffocus_t('finance_balance_difference', 'Selisih Balance')=>$balance['assets'] - ($balance['liabilities'] + $balance['equity'])
    );
    $r = 5;
    foreach ($rows as $label=>$amount) {
      $sheet->setCellValue('A'.$r, $label);
      $sheet->setCellValue('B'.$r, $amount);
      $r++;
    }
    erpkb_excel_apply_standard_style($excel, array('sheet'=>$sheet, 'title'=>erp_export_title(ffocus_t('finance_report_focus', 'FOKUS KEUANGAN')), 'header_row'=>4, 'first_data_row'=>5, 'last_data_row'=>max(5, $r - 1), 'column_count'=>2, 'money_columns'=>array('B'), 'filters'=>array(ffocus_t('finance_period', 'Periode')=>$filters['start_date'].' s/d '.$filters['end_date'], 'Status'=>'POSTED')));
    $tmp = erpkb_excel_temp_file('fokus_keuangan_');
    PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($tmp);
    $size = @filesize($tmp);
    $sig = @file_get_contents($tmp, false, null, 0, 2);
    if (!$size || $sig !== 'PK') { @unlink($tmp); throw new Exception('File Excel gagal dibuat dengan benar.'); }
    while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="fokus_keuangan_'.$filters['start_date'].'_sd_'.$filters['end_date'].'.xlsx"');
    header('Content-Length: '.$size);
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    readfile($tmp);
    @unlink($tmp);
    exit;
  }
  ffocus_json('error', ffocus_t('common_unknown_action', 'Action tidak dikenal.'));
} catch (Exception $e) {
  while (ob_get_level() > $initialOutputBufferLevel) ob_end_clean();
  ffocus_json('error', $e->getMessage());
}
?>
